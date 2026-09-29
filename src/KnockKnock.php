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
            if ($settings->getEnableCpProtection() && $request->getIsCpRequest()) {
                // We want to show the login screen
            } else {
                return;
            }
        }

        $url = $request->getAbsoluteUrl();
        $currentPath = $this->_normalizePath($request->getPathInfo());
        $cookie = $request->getCookies()->get('siteAccessToken');
        $loginPath = $this->_normalizePath($settings->getLoginPath());

        // The challenge route must remain reachable, but its URL in a query string grants nothing.
        if ($currentPath === $loginPath) {
            return;
        }

        // An empty effective password cannot make an existing bare cookie authoritative.
        if ($settings->getPassword() !== '' && $cookie != '') {
            return;
        }

        $ipAddress = IpHelper::getUserIp();

        // Check if this IP is in the exclusion list
        if (IpHelper::ipInCidrList($ipAddress, $settings->getAllowIps())) {
            return;
        }

        // Check if the requested URL is explicitly unprotected. If yes, allow the request.
        if ($settings->getUnprotectedUrls()) {
            if ($this->_matchesUrlRules($currentPath, $settings->getUnprotectedUrls())) {
                return;
            }
        }

        // Check to see if we're watching only specific URLs. By default, protect everything though
        if ($settings->getProtectedUrls()) {
            if (!$this->_matchesUrlRules($currentPath, $settings->getProtectedUrls())) {
                return;
            }
        }

        if ($request->getIsSiteRequest()) {
            Craft::$app->getCache()->set('knockknock-redirect', $url);
        }

        Craft::$app->getResponse()->setNoCacheHeaders();
        Craft::$app->getResponse()->redirect(UrlHelper::siteUrl($settings->getLoginPath()));
        Craft::$app->end();
    }

    private function _matchesUrlRules(string $currentPath, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (!$this->_ruleBelongsToCurrentSite($rule)) {
                continue;
            }

            $rulePath = $this->_normalizePath($rule, true);

            if ($currentPath === $rulePath) {
                return true;
            }

            // Preserve the plugin's established convention that rules containing `(` are regexes.
            if (str_contains($rulePath, '(') && @preg_match('`' . $rulePath . '`i', $currentPath) === 1) {
                return true;
            }
        }

        return false;
    }

    private function _ruleBelongsToCurrentSite(string $rule): bool
    {
        $ruleHost = parse_url($rule, PHP_URL_HOST);

        if ($ruleHost === null) {
            return true;
        }

        $siteHost = parse_url(UrlHelper::siteUrl(), PHP_URL_HOST);

        return is_string($siteHost) && strcasecmp($ruleHost, $siteHost) === 0;
    }

    private function _normalizePath(string $url, bool $relativeToSite = false): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($path)) {
            $path = $url;
        }

        $path = trim(rawurldecode($path), '/');

        if ($relativeToSite && parse_url($url, PHP_URL_HOST) !== null) {
            $sitePath = trim((string)parse_url(UrlHelper::siteUrl(), PHP_URL_PATH), '/');

            if ($sitePath !== '' && ($path === $sitePath || str_starts_with($path, $sitePath . '/'))) {
                $path = ltrim(substr($path, strlen($sitePath)), '/');
            }
        }

        return $path;
    }

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'knock-knock/settings' => 'knock-knock/settings/index',
                'knock-knock/settings/<settingsNavItem:{handle}>' => 'knock-knock/settings/index',
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
}
