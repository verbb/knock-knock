<?php
namespace verbb\knockknock\models;

use verbb\knockknock\KnockKnock;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================
    
    public $enabled = false;
    public $password;
    public $template;
    public $siteSettings = [];


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

        if (Craft::$app->getIsMultiSite() && $siteSettings) {
            return $siteSettings[$value];
        }

        return null;
    }
}
