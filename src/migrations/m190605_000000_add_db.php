<?php
namespace verbb\knockknock\migrations;

use craft\db\Migration;

class m190605_000000_add_db extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%knockknock_logins}}')) {
            $install = new Install();
            $install->safeUp();
        }
        
        return true;
    }

    public function safeDown(): bool
    {
        echo "m190605_000000_add_db cannot be reverted.\n";
        return false;
    }
}
