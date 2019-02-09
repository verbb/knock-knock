<?php
namespace verbb\knockknock;

use verbb\knockknock\base\PluginTrait;
use verbb\knockknock\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\UrlManager;

use yii\base\Event;

class KnockKnock extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerSiteRoutes();
        $this->_registerCpRoutes();

        $this->_testAccess();
    }

    public function getSettingsResponse()
    {
        Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('knock-knock/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _testAccess()
    {
        $request = Craft::$app->getRequest();
        $settings = KnockKnock::$plugin->getSettings();

        if ($request->getIsConsoleRequest()) {
            return;
        }

        $url = $request->getUrl();
        $token = $request->getCookies()->get('siteAccessToken');
        $user = Craft::$app->getUser()->getIdentity();

        // Force challenge for non authenticated site visitors
        if ($settings->enabled && $request->getIsSiteRequest() && (!$user) && ($token == '') && (stripos($url, 'knock-knock') === false) ) {
            Craft::$app->getSession()->setFlash('redir', $url);

            Craft::$app->getResponse()->redirect(UrlHelper::siteUrl('knock-knock/who-is-there'));
            Craft::$app->end();
        }
    }

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'knock-knock/settings' => 'knock-knock/default/settings',
            ]);
        });
    }

    private function _registerSiteRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'knock-knock/who-is-there' => 'knock-knock/default/ask',
            ]);
        });
    }
}
