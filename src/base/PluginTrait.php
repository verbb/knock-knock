<?php
namespace verbb\knockknock\base;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\services\Logins;

use Craft;
use craft\log\FileTarget;
use craft\web\View;

use yii\base\Event;
use yii\log\Logger;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getLogins()
    {
        return $this->get('logins');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'knock-knock');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'knock-knock');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'logins' => Logins::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/knock-knock.log'),
            'categories' => ['knock-knock'],
        ]);
    }

}