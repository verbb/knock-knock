<?php
namespace verbb\knockknock\controllers;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\helpers\IpHelper;
use verbb\knockknock\models\Login;

use Craft;
use craft\web\Controller;
use yii\web\Cookie;
use yii\web\Response;

class DefaultController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;

    
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = KnockKnock::$plugin->getSettings();

        return $this->renderTemplate('knock-knock/settings', [
            'settings' => $settings,
        ]);
    }

    public function actionAsk()
    {
        $settings = KnockKnock::$plugin->getSettings();
        
        $view = $this->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        $template = 'knock-knock/ask';

        if ($settings->getTemplate()) {
            // try CP template first
            $template = $settings->getTemplate();
            
            if (!$template) {
                // try site template if cp template does not exist
                $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
                $template = $settings->getTemplate();
            }
        }

        $redirect = Craft::$app->getSession()->get('knockknock-redirect');

        $data['redirect'] = $redirect ?? '/';

        // Allow config to override everything
        if ($settings->forcedRedirect) {
            $data['redirect'] = $settings->forcedRedirect;
        }
        
        return $this->renderTemplate($template, $data);
    }

    public function actionAnswer()
    {
        $request = Craft::$app->getRequest();
        $settings = KnockKnock::$plugin->getSettings();

        $view = $this->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);
        $template = 'knock-knock/ask';

        if ($settings->getTemplate()) {
            // try CP template first
            $template = $settings->getTemplate();
            
            if (!$template) {
                // try site template if cp template does not exist
                $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
                $template = $settings->getTemplate();
            }
        }

        $ipAddress = Craft::$app->getRequest()->getRemoteIP();

        $password = $request->getParam('password');
        $accessPassword = $settings->getPassword();
        
        Craft::$app->getSession()->remove('knockknock-redirect');

        // Check for lockout
        if (Craft::$app->getConfig()->getGeneral()->storeUserIps && $settings->checkInvalidLogins) {
            $hasLockout = KnockKnock::$plugin->getLogins()->checkLockout($ipAddress);

            if ($hasLockout) {
                $data['redirect'] = $request->getValidatedBodyParam('redirect');
                $data['errors']['password'] = Craft::t('knock-knock', 'Too many invalid attempts');
                
                return $this->renderTemplate($template, $data);
            }
        }

        if ($accessPassword == $password) {
            $cookie = new Cookie(Craft::cookieConfig([
                'name' => 'siteAccessToken',
                'value' => $request->csrfToken,
                'expire' => time() + 3600,
            ]));
            
            Craft::$app->getResponse()->getCookies()->add($cookie);
            return $this->redirect($request->getValidatedBodyParam('redirect'));
        } else {
            $data['redirect'] = $request->getValidatedBodyParam('redirect');
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
    }
}
