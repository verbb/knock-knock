<?php
namespace verbb\knockknock\controllers;

use verbb\knockknock\KnockKnock;

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

        $data['redirect'] = Craft::$app->getSession()->getFlash('redirect');
        
        if ($data['redirect'] == '') {
            $data['redirect'] = '/';
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
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
            $template = $settings->getTemplate();
        }

        $password = $request->getParam('password');
        $accessPassword = $settings->getPassword();
        
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
            
            return $this->renderTemplate($template, $data);
        }
    }
}