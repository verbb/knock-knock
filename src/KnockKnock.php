<?php
namespace verbb\knockknock;

use verbb\knockknock\base\PluginTrait;
use verbb\knockknock\helpers\IpHelper;
use verbb\knockknock\models\Settings;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\Application;
use craft\web\UrlManager;

use yii\base\Event;

use Throwable;

class KnockKnock extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.1.1';
    public string $minVersionRequired = '1.2.16';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_checkDeprecations();

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->_registerSiteRoutes();
        }
        
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        // Defer most setup tasks until Craft is fully initialized:
        Craft::$app->onInit(function() {
            $this->_testAccess();
        });
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('knock-knock/settings'));
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _testAccess(): void
    {
        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        // Only care if the plugin is enabled
        if (!$settings->getEnabled()) {
            return;
        }

        $request = Craft::$app->getRequest();
        $user = Craft::$app->getUser()->getIdentity();
        $token = $request->getToken();

        // Console and action requests are excluded, as well as for cross-site preview tokens
        if ($request->getIsConsoleRequest() || $request->getIsActionRequest() || $token !== null) {
            return;
        }

        // Live Preview requests are fine, but only for authenticated users
        if ($user && ($request->getIsLivePreview() || $request->getIsPreview())) {
            return;
        }

        // Only site requests are blocked and for guests
        if (!$request->getIsSiteRequest() || $user) {
            // Only CP requests are blocked if we're checking against that
            if ($settings->enableCpProtection && $request->getIsCpRequest()) {
                // We want to show the login screen
            } else {
                return;
            }
        }

        // Normalise the URLs a little, just in case to prevent infinite loops
        $url = $request->getAbsoluteUrl();
        $cookie = $request->getCookies()->get('siteAccessToken');
        $loginPath = UrlHelper::siteUrl($settings->getLoginPath());

        // Check for the site access cookie, and check we're not causing a loop
        if ($cookie != '' || stripos($url, $loginPath) !== false) {
            return;
        }

        $ipAddress = IpHelper::getUserIp();

        // Check if this IP is in the exclusion list
        if (IpHelper::ipInCidrList($ipAddress, $settings->allowIps)) {
            return;
        }

        // Check if the requested URL is explicitly unprotected. If yes, allow the request.
        if ($settings->getUnprotectedUrls()) {
            $match = false;
            $currentUrl = UrlHelper::stripQueryString($url);

            foreach ($settings->getUnprotectedUrls() as $unprotectedUrl) {
                // See if the URL matches exactly (without query string)
                if ($currentUrl === $unprotectedUrl) {
                    $match = true;

                    break;
                }

                // See if it matches a Regex patten
                if (strstr($unprotectedUrl, '(')) {
                    try {
                        if (preg_match('`' . $unprotectedUrl . '`i', $currentUrl) === 1) {
                            $match = true;

                            break;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            if ($match) {
                return;
            }
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
                if (strstr($protectedUrl, '(')) {
                    try {
                        if (preg_match('`' . $protectedUrl . '`i', $currentUrl) === 1) {
                            $noMatch = false;

                            break;
                        }
                    } catch (Throwable) {
                        continue;
                    }
                }
            }

            if ($noMatch) {
                return;
            }
        }

        if ($request->getIsSiteRequest()) {
            Craft::$app->getCache()->set('knockknock-redirect', $url);
        }

        Craft::$app->getResponse()->setNoCacheHeaders();
        Craft::$app->getResponse()->redirect($loginPath);
        Craft::$app->end();
    }

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'knock-knock/settings' => 'knock-knock/default/settings',
            ]);
        });
    }

    private function _registerSiteRoutes(): void
    {
        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();
        $loginPath = $settings->getLoginPath();

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use ($loginPath) {
            $event->rules = array_merge($event->rules, [
                $loginPath => 'knock-knock/default/ask',
            ]);
        });
    }

    private function _checkDeprecations(): void
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
