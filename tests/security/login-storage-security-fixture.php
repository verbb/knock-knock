<?php

declare(strict_types=1);

namespace craft\base {
    class Model
    {
        public function __get(string $name): mixed
        {
            return $this->{'get' . ucfirst($name)}();
        }

        public function __set(string $name, mixed $value): void
        {
            $this->{'set' . ucfirst($name)}($value);
        }
    }
}

namespace craft\db {
    final class FakeColumn
    {
        public function notNull(): self
        {
            return $this;
        }
    }

    final class FakeDb
    {
        public int $columnChecks = 0;

        public function __construct(
            public bool $tableExistsResult,
            public bool $columnExistsResult,
        ) {
        }

        public function columnExists(string $table, string $column): bool
        {
            $this->columnChecks++;

            return $this->columnExistsResult;
        }

        public function tableExists(string $table): bool
        {
            return $this->tableExistsResult;
        }
    }

    class Migration
    {
        public array $archivedPasswordColumns = [];
        public array $createdTables = [];
        public FakeDb $db;
        public array $droppedColumns = [];

        public function __construct(?FakeDb $db = null)
        {
            $this->db = $db ?? new FakeDb(false, false);
        }

        public function archiveTableIfExists(string $table): void
        {
            if (!$this->db->tableExistsResult) {
                return;
            }

            $this->archivedPasswordColumns[] = $this->db->columnExistsResult;
            $this->db->tableExistsResult = false;
        }

        public function createTable(string $table, array $columns): void
        {
            $this->createdTables[$table] = $columns;
            $this->db->tableExistsResult = true;
            $this->db->columnExistsResult = array_key_exists('password', $columns);
        }

        public function dateTime(): FakeColumn
        {
            return new FakeColumn();
        }

        public function dropColumn(string $table, string $column): void
        {
            $this->droppedColumns[] = [$table, $column];
            $this->db->columnExistsResult = false;
        }

        public function dropTableIfExists(string $table): void
        {
        }

        public function primaryKey(): FakeColumn
        {
            return new FakeColumn();
        }

        public function string(): FakeColumn
        {
            return new FakeColumn();
        }

        public function uid(): FakeColumn
        {
            return new FakeColumn();
        }
    }
}

namespace {
    use craft\db\FakeDb;
    use verbb\knockknock\migrations\Install;
    use verbb\knockknock\migrations\m261003_000000_remove_login_password;
    use verbb\knockknock\models\Login;

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    require dirname(__DIR__, 2) . '/src/models/Login.php';
    require dirname(__DIR__, 2) . '/src/migrations/Install.php';
    require dirname(__DIR__, 2) . '/src/migrations/m261003_000000_remove_login_password.php';

    $login = new Login();
    assertSame(false, property_exists($login, 'password'), 'Login attempt models must not expose retained password data.');
    $login->password = 'legacy-secret';
    assertSame(null, $login->password, 'Legacy password property access must remain non-fatal without retaining its value.');

    $install = new Install();
    assertSame(true, $install->safeUp(), 'A fresh install migration must complete successfully.');
    $freshColumns = $install->createdTables['{{%knockknock_logins}}'] ?? [];
    assertSame(true, array_key_exists('ipAddress', $freshColumns), 'Fresh installs must retain IP metadata for lockout counting.');
    assertSame(false, array_key_exists('password', $freshColumns), 'Fresh installs must not create password storage.');

    $staleDb = new FakeDb(true, true);
    $install = new Install($staleDb);
    assertSame(true, $install->safeUp(), 'An install over a stale login table must complete successfully.');
    assertSame([['{{%knockknock_logins}}', 'password']], $install->droppedColumns, 'Install must remove retained secrets from a stale table.');
    assertSame([false], $install->archivedPasswordColumns, 'Install must scrub a stale table before Craft archives it.');

    $existingDb = new FakeDb(true, true);
    $migration = new m261003_000000_remove_login_password($existingDb);
    assertSame(true, $migration->safeUp(), 'The existing-install migration must complete successfully.');
    assertSame([['{{%knockknock_logins}}', 'password']], $migration->droppedColumns, 'The existing-install migration must remove the live plaintext column.');

    $updatedDb = new FakeDb(true, false);
    $migration = new m261003_000000_remove_login_password($updatedDb);
    assertSame(true, $migration->safeUp(), 'An already-updated schema must be a successful no-op.');
    assertSame([], $migration->droppedColumns, 'An already-removed column must not be dropped again.');

    $missingDb = new FakeDb(false, true);
    $migration = new m261003_000000_remove_login_password($missingDb);
    assertSame(true, $migration->safeUp(), 'A missing login table must be a successful no-op.');
    assertSame(0, $missingDb->columnChecks, 'The migration must not inspect a column on a missing table.');

    ob_start();
    $canRevert = $migration->safeDown();
    $rollbackMessage = ob_get_clean();
    assertSame(false, $canRevert, 'The destructive plaintext-removal migration must not claim to be reversible.');
    assertSame(true, str_contains($rollbackMessage, 'cannot be reverted'), 'The migration must explain its one-way rollback behavior.');

    $pluginSource = file_get_contents(dirname(__DIR__, 2) . '/src/KnockKnock.php');
    $controllerSource = file_get_contents(dirname(__DIR__, 2) . '/src/controllers/DefaultController.php');
    $serviceSource = file_get_contents(dirname(__DIR__, 2) . '/src/services/Logins.php');
    assertSame(true, str_contains($pluginSource, "schemaVersion = '1.1.2'"), 'The plugin schema version must schedule the removal migration.');
    assertSame(false, str_contains($controllerSource, '$login->password'), 'Failed submissions must not copy the request secret into attempt metadata.');
    assertSame(false, str_contains($serviceSource, '$loginRecord->password'), 'The login service must not write password data.');
    assertSame(false, str_contains($serviceSource, "'password'"), 'The login service must not select password data.');

    echo "Knock Knock login storage security fixture passed.\n";
}
