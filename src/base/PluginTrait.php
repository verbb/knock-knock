<?php
namespace verbb\knockknock\base;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\services\Logins;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static KnockKnock $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('knock-knock', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'knock-knock');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('knock-knock', $message, $params);

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

    private function _registerComponents(): void
    {
        $this->setComponents([
            'logins' => Logins::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('knock-knock');
    }

}