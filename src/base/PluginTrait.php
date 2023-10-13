<?php
namespace verbb\knockknock\base;

use verbb\knockknock\KnockKnock;
use verbb\knockknock\services\Logins;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?KnockKnock $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('knock-knock');

        return [
            'components' => [
                'logins' => Logins::class,
            ],
        ];
    }

    // Public Methods
    // =========================================================================

    public function getLogins(): Logins
    {
        return $this->get('logins');
    }

}