<?php
namespace verbb\knockknock;

use verbb\knockknock\base\PluginTrait;
use verbb\knockknock\helpers\IpHelper;
use verbb\knockknock\helpers\ReturnUrlHelper;
use verbb\knockknock\models\Settings;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\Application;
use craft\web\UrlManager;
use craft\web\View;

use yii\base\Event;

class KnockKnock extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.1.1';
    public string $minVersionRequired = '1.2.16';

    private bool $_testAccessAfterAction = false;


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
            $this->_registerSiteEvents();
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

    private function _testAccess(bool $allowActionRequests = true): void
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

        // Console requests and cross-site preview tokens remain outside the gate.
        if ($request->getIsConsoleRequest() || $token !== null) {
            return;
        }

        // Action responses stay available, but a later site-page render must run the gate again.
        if ($allowActionRequests && $request->getIsActionRequest()) {
            $this->_testAccessAfterAction = true;

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

        $currentPath = $this->_normalizePath($request->getPathInfo());
        $cookie = $request->getCookies()->get('siteAccessToken');
        $loginPath = $this->_normalizePath($settings->getLoginPath());

        // Challenge and account routes remain reachable, but their URL in a query string grants nothing.
        if ($currentPath === $loginPath || $this->_isCraftAccountPath($currentPath)) {
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
            ReturnUrlHelper::store($request->getUrl());
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

    private function _isCraftAccountPath(string $currentPath): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $paths = [
            $generalConfig->getLoginPath(),
            $generalConfig->getSetPasswordPath(),
            $generalConfig->getVerifyEmailPath(),
        ];

        foreach ($paths as $path) {
            if (is_string($path) && $currentPath === $this->_normalizePath($path)) {
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

    private function _registerSiteEvents(): void
    {
        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function(TemplateEvent $event) {
            if (!$this->_testAccessAfterAction || $event->templateMode !== View::TEMPLATE_MODE_SITE) {
                return;
            }

            // Consume the deferred check before enforcement so nested rendering cannot repeat it.
            $this->_testAccessAfterAction = false;
            $this->_testAccess(false);
        });
    }
}
