<?php
namespace verbb\knockknock\migrations;

use craft\db\Migration;

class m261003_000000_remove_login_password extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = '{{%knockknock_logins}}';

        if ($this->db->tableExists($table) && $this->db->columnExists($table, 'password')) {
            $this->dropColumn($table, 'password');
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261003_000000_remove_login_password cannot be reverted.\n";

        return false;
    }
}
