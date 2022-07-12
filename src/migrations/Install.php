<?php
namespace verbb\knockknock\migrations;

use craft\db\Migration;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%knockknock_logins}}');
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

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%knockknock_logins}}');
    }
}
