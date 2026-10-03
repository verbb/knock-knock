<?php

declare(strict_types=1);

namespace craft\db {
    final class FakeDb
    {
        public function __construct(public bool $tableExistsResult)
        {
        }

        public function tableExists(string $table): bool
        {
            return $this->tableExistsResult;
        }
    }

    class Query
    {
        public static array $rows = [];

        private int $offsetValue = 0;
        private array $order = [];
        private array $selection = [];

        public function from(array $tables): self
        {
            return $this;
        }

        public function offset(int $offset): self
        {
            $this->offsetValue = $offset;

            return $this;
        }

        public function one(): array|false
        {
            $rows = self::$rows;

            usort($rows, function(array $a, array $b): int {
                foreach ($this->order as $field => $direction) {
                    $comparison = $a[$field] <=> $b[$field];

                    if ($comparison !== 0) {
                        return $direction === SORT_DESC ? -$comparison : $comparison;
                    }
                }

                return 0;
            });

            $row = $rows[$this->offsetValue] ?? false;

            return $row === false ? false : array_intersect_key($row, array_flip($this->selection));
        }

        public function orderBy(array $columns): self
        {
            $this->order = $columns;

            return $this;
        }

        public function select(array $columns): self
        {
            $this->selection = $columns;

            return $this;
        }
    }

    class Migration
    {
        public array $createdIndexes = [];
        public array $deletions = [];

        public function __construct(public FakeDb $db)
        {
        }

        public function createIndexIfMissing(string $table, array|string $columns, bool $unique = false): void
        {
            $key = json_encode([$table, $columns, $unique], JSON_THROW_ON_ERROR);
            $this->createdIndexes[$key] = [$table, $columns, $unique];
        }

        public function delete(string $table, array $condition): int
        {
            $this->deletions[] = [$table, $condition];
            $originalCount = count(Query::$rows);
            Query::$rows = array_values(array_filter(
                Query::$rows,
                fn(array $row): bool => !$this->matches($row, $condition),
            ));

            return $originalCount - count(Query::$rows);
        }

        private function matches(array $row, array $condition): bool
        {
            if (array_is_list($condition)) {
                $operator = array_shift($condition);

                if ($operator === 'or') {
                    foreach ($condition as $nested) {
                        if ($this->matches($row, $nested)) {
                            return true;
                        }
                    }

                    return false;
                }

                if ($operator === 'and') {
                    foreach ($condition as $nested) {
                        if (!$this->matches($row, $nested)) {
                            return false;
                        }
                    }

                    return true;
                }

                [$field, $value] = $condition;

                return match ($operator) {
                    '<' => $row[$field] < $value,
                    '<=' => $row[$field] <= $value,
                    default => throw new \RuntimeException('Unsupported condition operator: ' . $operator),
                };
            }

            foreach ($condition as $field => $value) {
                if ($row[$field] !== $value) {
                    return false;
                }
            }

            return true;
        }
    }
}

namespace {
    use craft\db\FakeDb;
    use craft\db\Query;
    use verbb\knockknock\migrations\m261003_010000_limit_login_history;

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    function loginRows(int $count): array
    {
        $rows = [];

        for ($id = 1; $id <= $count; $id++) {
            $rows[] = [
                'id' => $id,
                'dateCreated' => '2026-10-03 11:30:00',
            ];
        }

        return $rows;
    }

    require dirname(__DIR__, 2) . '/src/migrations/m261003_010000_limit_login_history.php';

    Query::$rows = loginRows(10001);
    $migration = new m261003_010000_limit_login_history(new FakeDb(true));
    assertSame(true, $migration->safeUp(), 'The retention migration must complete on an existing table.');
    assertSame([
        ['{{%knockknock_logins}}', ['dateCreated', 'id'], false],
        ['{{%knockknock_logins}}', ['ipAddress', 'dateCreated'], false],
    ], array_values($migration->createdIndexes), 'The migration must add both retention and lockout indexes.');
    assertSame(10000, count(Query::$rows), 'The migration must cap oversized existing login history.');
    assertSame(2, min(array_column(Query::$rows, 'id')), 'The migration must remove the oldest equal-timestamp row by ID.');

    assertSame(true, $migration->safeUp(), 'The retention migration must be retry-safe.');
    assertSame(2, count($migration->createdIndexes), 'Retrying the migration must not duplicate indexes.');
    assertSame(10000, count(Query::$rows), 'Retrying the migration must preserve the capped history.');

    Query::$rows = loginRows(10001);
    $missingMigration = new m261003_010000_limit_login_history(new FakeDb(false));
    assertSame(true, $missingMigration->safeUp(), 'A missing login table must be a successful no-op.');
    assertSame([], $missingMigration->createdIndexes, 'A missing table must not receive indexes.');
    assertSame(10001, count(Query::$rows), 'A missing table must not issue retention deletes.');

    ob_start();
    $canRevert = $migration->safeDown();
    $rollbackMessage = ob_get_clean();
    assertSame(false, $canRevert, 'The destructive retention migration must not claim to be reversible.');
    assertSame(true, str_contains($rollbackMessage, 'cannot be reverted'), 'The migration must explain its one-way rollback behavior.');

    echo "Knock Knock login retention migration security fixture passed.\n";
}
