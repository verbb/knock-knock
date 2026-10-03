<?php

declare(strict_types=1);

namespace yii\base {
    class Component
    {
    }

    class Exception extends \Exception
    {
    }
}

namespace craft\db {
    use verbb\knockknock\records\Login;

    class Query
    {
        private int $offsetValue = 0;
        private array $order = [];
        private array $selection = [];

        public function andWhere(array $condition): self
        {
            return $this;
        }

        public function all(): array
        {
            return Login::$rows;
        }

        public function count(): int
        {
            return count(Login::$rows);
        }

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
            $rows = Login::$rows;

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

            if ($row === false || !$this->selection) {
                return $row;
            }

            return array_intersect_key($row, array_flip($this->selection));
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

        public function where(array $condition): self
        {
            return $this;
        }
    }
}

namespace craft\helpers {
    use DateInterval;
    use DateTimeImmutable;
    use DateTimeInterface;
    use DateTimeZone;

    class DateTimeHelper
    {
        public static function currentUTCDateTime(): DateTimeImmutable
        {
            return new DateTimeImmutable('2026-10-03 12:00:00', new DateTimeZone('UTC'));
        }

        public static function secondsToInterval(int $seconds): DateInterval
        {
            return new DateInterval('PT' . $seconds . 'S');
        }
    }

    class Db
    {
        public static function prepareDateForDb(DateTimeInterface $date): string
        {
            return $date->format('Y-m-d H:i:s');
        }
    }
}

namespace verbb\knockknock\helpers {
    class IpHelper
    {
        public const ACCESS_ALLOWED = 1;
        public const ACCESS_DENIED = -1;

        public static function getAccessStatus(string $ipAddress, array $allowIps, array $denyIps): int
        {
            return 0;
        }
    }
}

namespace verbb\knockknock\models {
    class Login
    {
        public ?int $id = null;
        public string $ipAddress = '';

        public function __construct(array $config = [])
        {
            foreach ($config as $name => $value) {
                $this->{$name} = $value;
            }
        }

        public function validate(): bool
        {
            return true;
        }
    }

    class Settings
    {
        public string $invalidLoginWindowDuration = '3600';
        public int $maxInvalidLogins = 10;

        public function getAllowIps(): array
        {
            return [];
        }

        public function getDenyIps(): array
        {
            return [];
        }
    }
}

namespace verbb\knockknock\records {
    use craft\helpers\DateTimeHelper;

    class Login
    {
        public static array $deleteConditions = [];
        public static bool $failNextSave = false;
        public static array $rows = [];

        public ?int $id = null;
        public string $ipAddress = '';

        public static function deleteAll(array $condition): int
        {
            self::$deleteConditions[] = $condition;
            $originalCount = count(self::$rows);
            self::$rows = array_values(array_filter(
                self::$rows,
                fn(array $row): bool => !self::matches($row, $condition),
            ));

            return $originalCount - count(self::$rows);
        }

        public static function findOne(array $condition): ?self
        {
            return null;
        }

        public function save(bool $runValidation): bool
        {
            if (self::$failNextSave) {
                self::$failNextSave = false;

                return false;
            }

            $this->id = self::$rows ? max(array_column(self::$rows, 'id')) + 1 : 1;
            self::$rows[] = [
                'id' => $this->id,
                'ipAddress' => $this->ipAddress,
                'dateCreated' => DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s'),
                'dateUpdated' => DateTimeHelper::currentUTCDateTime()->format('Y-m-d H:i:s'),
            ];

            return true;
        }

        private static function matches(array $row, array $condition): bool
        {
            if (array_is_list($condition)) {
                $operator = array_shift($condition);

                if ($operator === 'or') {
                    foreach ($condition as $nested) {
                        if (self::matches($row, $nested)) {
                            return true;
                        }
                    }

                    return false;
                }

                if ($operator === 'and') {
                    foreach ($condition as $nested) {
                        if (!self::matches($row, $nested)) {
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

namespace verbb\knockknock {
    use verbb\knockknock\models\Settings;

    class FakePlugin
    {
        public function __construct(private Settings $settings)
        {
        }

        public function getSettings(): Settings
        {
            return $this->settings;
        }
    }

    class KnockKnock
    {
        public static FakePlugin $plugin;

        public static function info(string $message): void
        {
        }
    }
}

namespace {
    use verbb\knockknock\FakePlugin;
    use verbb\knockknock\KnockKnock;
    use verbb\knockknock\models\Login;
    use verbb\knockknock\models\Settings;
    use verbb\knockknock\records\Login as LoginRecord;
    use verbb\knockknock\services\Logins;

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    function recentRows(int $count, string $dateCreated = '2026-10-03 11:30:00', int $firstId = 1): array
    {
        $rows = [];

        for ($index = 0; $index < $count; $index++) {
            $id = $firstId + $index;
            $rows[] = [
                'id' => $id,
                'ipAddress' => '192.0.2.' . ($id % 255),
                'dateCreated' => $dateCreated,
                'dateUpdated' => $dateCreated,
            ];
        }

        return $rows;
    }

    require dirname(__DIR__, 2) . '/src/services/Logins.php';

    KnockKnock::$plugin = new FakePlugin(new Settings());
    $service = new Logins();

    LoginRecord::$rows = [
        ...recentRows(1, '2026-10-03 10:59:59', 1),
        ...recentRows(1, '2026-10-03 11:00:00', 2),
    ];
    $saved = $service->saveLogin(new Login(['ipAddress' => '203.0.113.10']));
    assertSame(true, $saved, 'A valid failed-login record must save successfully.');
    assertSame([2, 3], array_column(LoginRecord::$rows, 'id'), 'Rows older than the lockout window must be pruned while the inclusive cutoff is preserved.');

    LoginRecord::$rows = recentRows(9999);
    $service->saveLogin(new Login(['ipAddress' => '203.0.113.11']));
    assertSame(10000, count(LoginRecord::$rows), 'Exactly 10,000 recent records must be retained without overflow pruning.');

    LoginRecord::$rows = recentRows(10000);
    $service->saveLogin(new Login(['ipAddress' => '203.0.113.12']));
    assertSame(10000, count(LoginRecord::$rows), 'The global history cap must retain at most 10,000 records.');
    assertSame(2, min(array_column(LoginRecord::$rows, 'id')), 'Equal timestamps must be pruned deterministically by ascending ID.');
    assertSame(10001, max(array_column(LoginRecord::$rows, 'id')), 'The newest equal-timestamp record must be retained.');

    LoginRecord::$rows = [
        ...recentRows(10, '2026-10-03 10:00:00', 1),
        ...recentRows(10000, '2026-10-03 11:45:00', 11),
    ];
    $service->saveLogin(new Login(['ipAddress' => '203.0.113.13']));
    assertSame(10000, count(LoginRecord::$rows), 'Expiry and overflow pruning must combine to enforce the cap.');
    assertSame(12, min(array_column(LoginRecord::$rows, 'id')), 'Combined pruning must remove expired rows and the oldest in-window overflow row.');

    LoginRecord::$rows = recentRows(1, '2026-10-03 10:00:00');
    LoginRecord::$deleteConditions = [];
    LoginRecord::$failNextSave = true;
    $saved = $service->saveLogin(new Login(['ipAddress' => '203.0.113.14']));
    assertSame(false, $saved, 'A failed record write must be reported to the caller.');
    assertSame(1, count(LoginRecord::$rows), 'Cleanup must not run after a failed record write.');
    assertSame([], LoginRecord::$deleteConditions, 'A failed record write must not issue retention deletes.');

    echo "Knock Knock login retention security fixture passed.\n";
}
