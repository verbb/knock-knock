<?php
namespace verbb\knockknock\controllers;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\helpers\AccessTokenHelper;
use verbb\knockknock\helpers\IpHelper;
use verbb\knockknock\helpers\ReturnUrlHelper;
use verbb\knockknock\models\Login;
use verbb\knockknock\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Cookie;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends Controller
{
    // Properties
    // =========================================================================

    protected array|int|bool $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionAsk(): Response
    {
        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        return $this->renderPrompt($this->_getRedirect($settings));
    }

    /**
     * Renders the password prompt, for a visitor who should be returned to the given URL once they’ve entered
     * the password.
     *
     * @internal
     */
    public function renderPrompt(string $redirect): Response
    {
        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        $template = $this->_getTemplate($settings->getDefaultTemplate(), $settings->getTemplate());

        return $this->renderTemplate($template, [
            'redirect' => $redirect,
        ], $this->getView()->getTemplateMode());
    }

    public function actionAnswer(): Response
    {
        $this->requirePostRequest();

        $data = [];

        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        if (!$settings->getEnabled()) {
            throw new NotFoundHttpException();
        }

        $accessPassword = $settings->getPassword();

        if ($accessPassword === '') {
            Craft::warning('Knock Knock refused an access attempt because the effective password is empty.', __METHOD__);

            throw new HttpException(503);
        }

        $template = $this->_getTemplate($settings->getDefaultTemplate(), $settings->getTemplate());
        $ipAddress = IpHelper::getUserIp();
        $ipAccessStatus = IpHelper::getAccessStatus($ipAddress, $settings->getAllowIps(), $settings->getDenyIps());

        $password = $this->request->getBodyParam('password', '');

        if (!is_string($password)) {
            $password = '';
        }

        $hasLockout = $ipAccessStatus === IpHelper::ACCESS_DENIED;

        // Check dynamic lockouts only when no static IP policy applies.
        if ($ipAccessStatus === IpHelper::ACCESS_NEUTRAL && Craft::$app->getConfig()->getGeneral()->storeUserIps && $settings->checkInvalidLogins) {
            $hasLockout = KnockKnock::$plugin->getLogins()->checkLockout($ipAddress);
        }

        if ($hasLockout) {
            $data['redirect'] = $this->_getRedirect($settings);
            $data['errors']['password'] = Craft::t('knock-knock', 'Too many invalid attempts');

            return $this->renderTemplate($template, $data);
        }

        if (Craft::$app->getSecurity()->compareString($accessPassword, $password)) {
            // Resolve the return URL first, so an invalid one doesn’t leave an access cookie behind
            $redirect = $this->_getRedirect($settings);
            $duration = $settings->getCookieDuration();
            $staticCaching = $settings->getEnableStaticCaching();

            $cookie = new Cookie(Craft::cookieConfig([
                'name' => $staticCaching ? $settings->getStaticCacheCookieName() : AccessTokenHelper::getCookieName(),
                'value' => $staticCaching ? AccessTokenHelper::createStatic($accessPassword) : AccessTokenHelper::create($accessPassword, $duration),
                'expire' => $duration ? time() + $duration : 0,
            ]));

            Craft::$app->getResponse()->getCookies()->add($cookie);
            Craft::$app->getResponse()->setNoCacheHeaders();

            if (!$staticCaching) {
                ReturnUrlHelper::forget();
            }

            return $this->redirect($redirect);
        }

        $data['redirect'] = $this->_getRedirect($settings);
        $data['errors']['password'] = Craft::t('knock-knock', 'Invalid password');

        // Log this login to the database
        if (Craft::$app->getConfig()->getGeneral()->storeUserIps && $settings->checkInvalidLogins) {
            $login = new Login();
            $login->ipAddress = $ipAddress;

            // No need to log allow list
            if (!IpHelper::ipInCidrList($ipAddress, $settings->getAllowIps())) {
                KnockKnock::$plugin->getLogins()->saveLogin($login);
            }
        }

        return $this->renderTemplate($template, $data);
    }


    // Private Methods
    // =========================================================================

    private function _getRedirect(Settings $settings): string
    {
        if ($settings->forcedRedirect) {
            return $settings->forcedRedirect;
        }

        // When static caching is enabled, the return URL is passed through the prompt’s form rather than the
        // session, so that the prompt doesn’t start a session (which would prevent it from being cached)
        if ($settings->getEnableStaticCaching()) {
            $redirect = $this->request->getIsPost() ? $this->request->getValidatedBodyParam('redirect') : null;

            return ReturnUrlHelper::sanitize($redirect);
        }

        return ReturnUrlHelper::get();
    }

    private function _getTemplate($defaultTemplate, $template = ''): string
    {
        $template = $template ?: $defaultTemplate;

        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        // Set “no-cache” headers for Craft Cloud, unless static caching is enabled. Even then, the prompt
        // must only be cached for visitors without the access cookie, as the cookie’s value is part of the
        // cache key for the protected pages (and a visitor could have a valid cookie but a denied IP).
        if (
            !$settings->getEnableStaticCaching() ||
            !$this->request->getIsGet() ||
            AccessTokenHelper::hasCookie($settings->getStaticCacheCookieName())
        ) {
            $this->response->setNoCacheHeaders();
        }

        $view = $this->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        // Try CP template first
        if ($view->doesTemplateExist($template)) {
            return $template;
        }

        // Try site template if cp template does not exist
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

        if ($view->doesTemplateExist($template)) {
            return $template;
        }

        // Reset back to CP just in case to return the default template
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        return $defaultTemplate;
    }
}
