<?php
namespace verbb\knockknock\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m190605_000000_add_db extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        if (!$this->db->tableExists('{{%knockknock_logins}}')) {
            $install = new Install();
            $install->safeUp();
        }
    }

    public function safeDown()
    {
        echo "m190605_000000_add_db cannot be reverted.\n";
        return false;
    }
}
