<?php
namespace verbb\knockknock;

use verbb\knockknock\base\PluginTrait;
use verbb\knockknock\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\UrlManager;

use yii\base\Event;

class KnockKnock extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.1.1';
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
        $this->_checkDeprecations();

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
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function(Event $event) {
            $request = Craft::$app->getRequest();
            $settings = KnockKnock::$plugin->getSettings();
            $user = Craft::$app->getUser()->getIdentity();
            $token = $request->getToken();

            // Only care if the plugin is enabled
            if (!$settings->getEnabled()) {
                return;
            }

            // Console and Live Preview requests are fine. Also check for cross-site preview tokens
            if ($request->getIsConsoleRequest() || ($request->getIsLivePreview() || $token !== null || $request->getIsPreview())) {
                return;
            }

            // Only site requests are blocked and for guests
            if (!$request->getIsSiteRequest() || $user) {
                return;
            }

            $url = $request->getAbsoluteUrl();
            $cookie = $request->getCookies()->get('siteAccessToken');
            $loginPath = $settings->getLoginPath();

            // Check for the site access cookie, and check we're not causing a loop
            if ($cookie != '' || stripos($url, $loginPath) !== false) {
                return;
            }
            
            $ipAddress = $request->getRemoteIP();

            // Check if this IP is in the exclusion list
            if (in_array($ipAddress, $settings->getAllowIps())) {
                return;
            }

            // Check to see if we're watching only specific URLs. By default, protect everything though
            if ($settings->getProtectedUrls()) {
                $noMatch = true;
                $currentUrl = UrlHelper::stripQueryString($url);

                foreach ($settings->getProtectedUrls() as $protectedUrl) {
                    // See if the URL matches exactly (without query string)
                    if ($currentUrl === $protectedUrl) {
                        $noMatch = false;

                        break;
                    }

                    // See if it matches a Regex patten
                    try {
                        if (preg_match('`' . $protectedUrl . '`i', $currentUrl) === 1) {
                            $noMatch = false;

                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                if ($noMatch) {
                    return;
                }
            }

            Craft::$app->getSession()->set('redirect', $url);

            Craft::$app->getResponse()->redirect(UrlHelper::siteUrl($loginPath));
            Craft::$app->end();
        });
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
        $settings = KnockKnock::$plugin->getSettings();
        $loginPath = $settings->getLoginPath();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use ($loginPath) {
            $event->rules = array_merge($event->rules, [
                $loginPath => 'knock-knock/default/ask',
            ]);
        });
    }

    private function _checkDeprecations()
    {
        $settings = $this->getSettings();

        // Check for renamed settings
        $renamedSettings = [
            'whitelistIps' => 'allowIps',
            'blacklistIps' => 'denyIps',
        ];

        foreach ($renamedSettings as $old => $new) {
            if (property_exists($settings, $old) && isset($settings->$old)) {
                Craft::$app->getDeprecator()->log($old, "The {$old} config setting has been renamed to {$new}.");
                $settings[$new] = $settings[$old];
                unset($settings[$old]);
            }
        }
    }
}
