<?php
namespace verbb\knockknock\migrations;

use craft\db\Migration;
use craft\db\Query;

class m261003_010000_limit_login_history extends Migration
{
    // Constants
    // =========================================================================

    private const MAX_LOGIN_RECORDS = 10000;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $table = '{{%knockknock_logins}}';

        if (!$this->db->tableExists($table)) {
            return true;
        }

        $this->createIndexIfMissing($table, ['dateCreated', 'id']);

        $boundary = (new Query())
            ->select(['id', 'dateCreated'])
            ->from([$table])
            ->orderBy([
                'dateCreated' => SORT_DESC,
                'id' => SORT_DESC,
            ])
            ->offset(self::MAX_LOGIN_RECORDS)
            ->one();

        if ($boundary) {
            $this->delete($table, [
                'or',
                ['<', 'dateCreated', $boundary['dateCreated']],
                [
                    'and',
                    ['dateCreated' => $boundary['dateCreated']],
                    ['<=', 'id', $boundary['id']],
                ],
            ]);
        }

        $this->createIndexIfMissing($table, ['ipAddress', 'dateCreated']);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261003_010000_limit_login_history cannot be reverted.\n";

        return false;
    }
}
