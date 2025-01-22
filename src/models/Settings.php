<?php
namespace verbb\knockknock\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use yii\base\Exception;

use Closure;
use Throwable;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool|Closure $enabled = false;
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
        $enabled = $this->_getSettingValue('enabled');

        // Allow the enabled setting to be a callback function
        if (is_callable($enabled)) {
            return $enabled();
        }

        return $enabled ?? false;
    }

    public function getDefaultTemplate(): string
    {
        if (version_compare(Craft::$app->getInfo()->version, '5.6.0', '>=')) {
            return 'knock-knock/ask-craft-5-6';
        }

        return 'knock-knock/ask';
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

        foreach (($this->_getSettingValue('protectedUrls') ?? []) as $url) {
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

        foreach (($this->_getSettingValue('unprotectedUrls') ?? []) as $url) {
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
        try {
            $currentSite = Craft::$app->getSites()->getCurrentSite();
            $siteSettings = $this->siteSettings[$currentSite->handle] ?? [];

            // Allow global override
            if ($this->$value) {
                return $this->$value;
            }

            if (Craft::$app->getIsMultiSite() && $siteSettings && isset($siteSettings[$value])) {
                return $siteSettings[$value];
            }
        } catch (Throwable $e) {
            // In the case where a primary site might not be available yet
            // https://github.com/verbb/knock-knock/issues/75
        }

        return null;
    }
}
