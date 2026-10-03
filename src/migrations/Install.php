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
        $this->createIndexes();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
        $table = '{{%knockknock_logins}}';

        // Remove retained secrets before Craft archives a stale table during installation.
        if ($this->db->tableExists($table) && $this->db->columnExists($table, 'password')) {
            $this->dropColumn($table, 'password');
        }

        $this->archiveTableIfExists($table);
        $this->createTable($table, [
            'id' => $this->primaryKey(),
            'ipAddress' => $this->string(),
            'loginPath' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%knockknock_logins}}', ['dateCreated', 'id']);
        $this->createIndex(null, '{{%knockknock_logins}}', ['ipAddress', 'dateCreated']);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%knockknock_logins}}');
    }
}
