<?php
namespace verbb\knockknock\controllers;

use verbb\knockknock\KnockKnock;
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
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
            $template = $settings->getTemplate();
        }

        $redirect = Craft::$app->getSession()->get('redirect');

        $data['redirect'] = $redirect ?? '/';
        
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
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
            $template = $settings->getTemplate();
        }

        $ipAddress = Craft::$app->getRequest()->getUserIP();

        $password = $request->getParam('password');
        $accessPassword = $settings->getPassword();

        // Check for lockout
        if ($settings->checkInvalidLogins) {
            $hasLockout = KnockKnock::$plugin->getLogins()->checkLockout($ipAddress);

            if ($hasLockout) {
                $data['redirect'] = $request->getParam('redirect');
                $data['errors']['password'] = Craft::t('knock-knock', 'Too many invalid attempts');
                
                return $this->renderTemplate($template, $data);
            }
        }

        if ($accessPassword == $password) {
            $cookie = new Cookie();
            $cookie->name = 'siteAccessToken';
            $cookie->value = $request->csrfToken;
            $cookie->expire = time() + 3600;
            
            Craft::$app->getResponse()->getCookies()->add($cookie);
            return $this->redirect($request->getParam('redirect'));
        } else {
            $data['redirect'] = $request->getParam('redirect');
            $data['errors']['password'] = Craft::t('knock-knock', 'Invalid password');

            // Log this login to the database
            if ($settings->checkInvalidLogins) {
                $login = new Login();
                $login->ipAddress = $ipAddress;
                $login->password = $password;

                // No need to log whitelist
                if (!in_array($ipAddress, $settings->getWhitelistIps())) {
                    KnockKnock::$plugin->getLogins()->saveLogin($login);
                }
            }
            
            return $this->renderTemplate($template, $data);
        }
    }
}