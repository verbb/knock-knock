<?php
namespace verbb\knockknock\models;

use craft\base\Model;

class Login extends Model
{
    // Properties
    // =========================================================================

    public string $id;
    public string $ipAddress;
    public string $password;
    public string $loginPath;

}
