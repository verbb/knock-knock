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

    public bool $checkInvalidLogins = false;
    public string $invalidLoginWindowDuration = '3600';
    public int $maxInvalidLogins = 10;
    public string $allowIps = '';
    public string $denyIps = '';
    public bool $useRemoteIp = false;
    
    public string $protectedUrls = '';
    public string $unprotectedUrls = '';


    // Public Methods
    // =========================================================================

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

    /**
     * @return string[]
     */
    public function getAllowIps(): array
    {
        return $this->_getArrayFromMultiline($this->allowIps);
    }

    /**
     * @return string[]
     */
    public function getDenyIps(): array
    {
        return $this->_getArrayFromMultiline($this->denyIps);
    }

    /**
     * @return string[]
     * @throws Exception
     * @throws Exception
     */
    public function getProtectedUrls(): array
    {
        $urls = [];

        foreach ($this->_getArrayFromMultiline($this->protectedUrls) as $url) {
            $url = trim($url);

            if ($url !== '' && $url !== '0') {
                $urls[] = UrlHelper::siteUrl(App::parseEnv($url));
            }
        }

        return $urls;
    }

    /**
     * @return string[]
     * @throws Exception
     * @throws Exception
     */
    public function getUnprotectedUrls(): array
    {
        $urls = [];

        foreach ($this->_getArrayFromMultiline($this->unprotectedUrls) as $url) {
            $url = trim($url);

            if ($url !== '' && $url !== '0') {
                $urls[] = UrlHelper::siteUrl(App::parseEnv($url));
            }
        }

        return $urls;
    }


    // Private Methods
    // =========================================================================
    
    /**
     * @return string[]
     */
    private function _getArrayFromMultiline($string): array
    {
        $array = [];

        if ($string) {
            $array = array_map('trim', explode(PHP_EOL, $string));
        }

        return $array;
    }

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
