<?php
namespace verbb\knockknock\models;

use craft\base\Model;

class Login extends Model
{
    // Properties
    // =========================================================================

    public ?string $id = null;
    public ?string $ipAddress = null;
    public ?string $password = null;
    public ?string $loginPath = null;

}
