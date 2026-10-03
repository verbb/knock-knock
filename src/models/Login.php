<?php
namespace verbb\knockknock\models;

use craft\base\Model;

class Login extends Model
{
    // Properties
    // =========================================================================

    public ?string $id = null;
    public ?string $ipAddress = null;
    public ?string $loginPath = null;


    // Public Methods
    // =========================================================================

    /**
     * @deprecated Attempted passwords are no longer retained.
     */
    public function getPassword(): null
    {
        return null;
    }

    /**
     * @deprecated Attempted passwords are no longer retained.
     */
    public function setPassword(mixed $value): void
    {
    }

}
