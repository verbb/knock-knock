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
        $view = $this->getView();
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        $data['redir'] = Craft::$app->getSession()->getFlash('redir');
        
        if ($data['redir'] == '') {
            $data['redir'] = '/';
        }

        return $this->renderTemplate('knock-knock/ask', $data);
    }

    public function actionAnswer()
    {
        $request = Craft::$app->getRequest();

        $password = $request->getParam('password');
        $accessPassword = KnockKnock::$plugin->getSettings()->password;
        
        if ($accessPassword == $password) {
            $cookie = new Cookie();
            $cookie->name = 'siteAccessToken';
            $cookie->value = $request->csrfToken;
            $cookie->expire = time() + 3600;
            
            Craft::$app->getResponse()->getCookies()->add($cookie);
            return $this->redirect($request->getParam('redir'));
        } else {
            $data['redir'] = $request->getParam('redir');
            $data['errors']['password'] = Craft::t('knock-knock', 'Invalid password');
            
            $view = $this->getView();
            $view->setTemplateMode($view::TEMPLATE_MODE_CP);

            return $this->renderTemplate('knock-knock/ask', $data);
        }
    }
}