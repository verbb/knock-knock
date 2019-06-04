<?php
namespace verbb\knockknock\records;

use craft\db\ActiveRecord;

class Login extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%knockknock_logins}}';
    }
}
