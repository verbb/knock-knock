<?php
namespace verbb\knockknock\base;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\services\Logins;

use Craft;

use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static KnockKnock $plugin;


    // Static Methods
    // =========================================================================

    public static function log($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'knock-knock');
    }

    public static function error($message): void
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'knock-knock');
    }


    // Public Methods
    // =========================================================================

    public function getLogins(): Logins
    {
        return $this->get('logins');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'logins' => Logins::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging(): void
    {
        BaseHelper::setFileLogging('knock-knock');
    }

}