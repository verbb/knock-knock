<?php
namespace verbb\knockknock\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use yii\base\Exception;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool $enabled = false;
    public string $password = '';
    public string $loginPath = '';
    public string $template = '';
    public string $forcedRedirect = '';
    public array $siteSettings = [];
    public bool $enableCpProtection = false;

    public bool $checkInvalidLogins = false;
    public string $invalidLoginWindowDuration = '3600';
    public int $maxInvalidLogins = 10;
    public array $allowIps = [];
    public array $denyIps = [];
    public bool $useRemoteIp = false;
    
    public array $protectedUrls = [];
    public array $unprotectedUrls = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('allowIps', $config) && !is_array($config['allowIps'])) {
            $config['allowIps'] = array_map('trim', explode(PHP_EOL, $config['allowIps']));
        }

        if (array_key_exists('denyIps', $config) && !is_array($config['denyIps'])) {
            $config['denyIps'] = array_map('trim', explode(PHP_EOL, $config['denyIps']));
        }

        if (array_key_exists('protectedUrls', $config) && !is_array($config['protectedUrls'])) {
            $config['protectedUrls'] = array_map('trim', explode(PHP_EOL, $config['protectedUrls']));
        }

        if (array_key_exists('unprotectedUrls', $config) && !is_array($config['unprotectedUrls'])) {
            $config['unprotectedUrls'] = array_map('trim', explode(PHP_EOL, $config['unprotectedUrls']));
        }

        parent::__construct($config);
    }

    public function getEnabled(): bool
    {
        return $this->_getSettingValue('enabled') ?? false;
    }

    public function getTemplate(): string
    {
        return $this->_getSettingValue('template') ?? '';
    }

    public function getPassword(): string
    {
        return $this->_getSettingValue('password') ?? '';
    }

    public function getLoginPath(): string
    {
        return $this->_getSettingValue('loginPath') ?? 'knock-knock/who-is-there';
    }

    public function getSettingAsMultiline(string $setting): string
    {
        return implode(PHP_EOL, $this->$setting);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getProtectedUrls(): array
    {
        $protectedUrls = [];

        foreach ($this->protectedUrls as $url) {
            $urls = explode(PHP_EOL, trim($url));

            foreach ($urls as $url) {
                if ($url !== '' && $url !== '0') {
                    $protectedUrls[] = trim(UrlHelper::siteUrl(App::parseEnv($url)));
                }
            }
        }

        return array_filter($protectedUrls);
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getUnprotectedUrls(): array
    {
        $unprotectedUrls = [];

        foreach ($this->unprotectedUrls as $url) {
            $urls = explode(PHP_EOL, trim($url));

            foreach ($urls as $url) {
                if ($url !== '' && $url !== '0') {
                    $unprotectedUrls[] = trim(UrlHelper::siteUrl(App::parseEnv($url)));
                }
            }
        }

        return array_filter($unprotectedUrls);
    }


    // Private Methods
    // =========================================================================

    private function _getSettingValue($value)
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        $siteSettings = $this->siteSettings[$currentSite->handle] ?? [];

        // Allow global override
        if ($this->$value) {
            return $this->$value;
        }

        if (Craft::$app->getIsMultiSite() && $siteSettings && isset($siteSettings[$value])) {
            return $siteSettings[$value];
        }

        return null;
    }
}
