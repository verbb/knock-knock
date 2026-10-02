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
        $data = [];

        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        $template = $this->_getTemplate($settings->getDefaultTemplate(), $settings->getTemplate());

        $data['redirect'] = $this->_getRedirect($settings);

        return $this->renderTemplate($template, $data);
    }

    public function actionAnswer(): Response
    {
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

        $password = $this->request->getParam('password', '');

        if (!is_string($password)) {
            $password = '';
        }

        // Check for lockout
        if (Craft::$app->getConfig()->getGeneral()->storeUserIps && $settings->checkInvalidLogins) {
            $hasLockout = KnockKnock::$plugin->getLogins()->checkLockout($ipAddress);

            if ($hasLockout) {
                $data['redirect'] = $this->_getRedirect($settings);
                $data['errors']['password'] = Craft::t('knock-knock', 'Too many invalid attempts');

                return $this->renderTemplate($template, $data);
            }
        }

        if (Craft::$app->getSecurity()->compareString($accessPassword, $password)) {
            $duration = $settings->getCookieDuration();

            $cookie = new Cookie(Craft::cookieConfig([
                'name' => AccessTokenHelper::getCookieName(),
                'value' => AccessTokenHelper::create($accessPassword, $duration),
                'expire' => $duration ? time() + $duration : 0,
            ]));

            Craft::$app->getResponse()->getCookies()->add($cookie);
            Craft::$app->getResponse()->setNoCacheHeaders();

            $redirect = $this->_getRedirect($settings);

            ReturnUrlHelper::forget();

            return $this->redirect($redirect);
        }

        $data['redirect'] = $this->_getRedirect($settings);
        $data['errors']['password'] = Craft::t('knock-knock', 'Invalid password');

        // Log this login to the database
        if (Craft::$app->getConfig()->getGeneral()->storeUserIps && $settings->checkInvalidLogins) {
            $login = new Login();
            $login->ipAddress = $ipAddress;
            $login->password = $password;

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
        return $settings->forcedRedirect ?: ReturnUrlHelper::get();
    }

    private function _getTemplate($defaultTemplate, $template = ''): string
    {
        $template = $template ?: $defaultTemplate;

        // Set “no-cache” headers for Craft Cloud
        $this->response->setNoCacheHeaders();

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
