<?php
namespace verbb\knockknock\models;

use verbb\knockknock\KnockKnock;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;

class Settings extends Model
{
    // Public Properties
    // =========================================================================
    
    public $enabled = false;
    public $password;
    public $loginPath;
    public $template;
    public $forcedRedirect;
    public $siteSettings = [];

    public $checkInvalidLogins = false;
    public $invalidLoginWindowDuration = '3600';
    public $maxInvalidLogins = 10;
    public $allowIps;
    public $denyIps;
    public $useRemoteIp = false;

    public $protectedUrls;
    public $unprotectedUrls;


    // Public Methods
    // =========================================================================

    public function getEnabled()
    {
        return $this->_getSettingValue('enabled') ?? false;
    }

    public function getTemplate()
    {
        return $this->_getSettingValue('template') ?? '';
    }

    public function getPassword()
    {
        return $this->_getSettingValue('password') ?? '';
    }

    public function getLoginPath()
    {
        $cpUrl = UrlHelper::cpUrl('knock-knock/who-is-there');
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $url = str_replace($cpTrigger . '/', '', $cpUrl);

        return $this->_getSettingValue('loginPath') ?? $url;
    }

    public function getAllowIps()
    {
        return $this->_getArrayFromMultiline($this->allowIps);
    }

    public function getDenyIps()
    {
        return $this->_getArrayFromMultiline($this->denyIps);
    }

    public function getProtectedUrls()
    {
        $urls = [];

        foreach ($this->_getArrayFromMultiline($this->protectedUrls) as $url) {
            $url = trim($url);

            if ($url) {
                $urls[] = UrlHelper::siteUrl(Craft::parseEnv($url));
            }
        }

        return $urls;
    }

    public function getUnprotectedUrls()
    {
        $urls = [];

        foreach ($this->_getArrayFromMultiline($this->unprotectedUrls) as $url) {
            $url = trim($url);

            if ($url) {
                $urls[] = UrlHelper::siteUrl(Craft::parseEnv($url));
            }
        }

        return $urls;
    }


    // Private Methods
    // =========================================================================

    private function _getArrayFromMultiline($string)
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
