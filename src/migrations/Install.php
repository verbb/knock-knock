<?php
namespace verbb\knockknock\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
    }

    public function safeDown()
    {
        $this->dropTables();
    }

    public function createTables()
    {
        $this->createTable('{{%knockknock_logins}}', [
            'id' => $this->primaryKey(),
            'ipAddress' => $this->string(),
            'password' => $this->string(),
            'loginPath' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }
    
    public function dropTables()
    {
        $this->dropTable('{{%knockknock_logins}}');
    }
}
