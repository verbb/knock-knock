<?php

declare(strict_types=1);

namespace craft\base {
    class Model
    {
    }

    class Plugin
    {
        public function init(): void
        {
        }
    }
}

namespace craft\events {
    class RegisterUrlRulesEvent
    {
    }

    class TemplateEvent
    {
    }
}

namespace craft\helpers {
    class UrlHelper
    {
        public static function siteUrl(string $path = ''): string
        {
            return 'https://example.test/subfolder/' . ltrim($path, '/');
        }
    }
}

namespace craft\services {
    class Plugins
    {
    }
}

namespace craft\web {
    class Application
    {
    }

    class UrlManager
    {
        public const EVENT_REGISTER_CP_URL_RULES = 'registerCpUrlRules';
        public const EVENT_REGISTER_SITE_URL_RULES = 'registerSiteUrlRules';
    }

    class View
    {
        public const EVENT_BEFORE_RENDER_PAGE_TEMPLATE = 'beforeRenderPageTemplate';
        public const TEMPLATE_MODE_SITE = 'site';
    }
}

namespace yii\base {
    class Event
    {
        public static function on(string $class, string $name, callable $handler): void
        {
        }
    }
}

namespace verbb\knockknock\base {
    trait PluginTrait
    {
        public static mixed $plugin = null;
    }
}

namespace verbb\knockknock\helpers {
    class AccessTokenHelper
    {
    }

    class IpHelper
    {
    }

    class ReturnUrlHelper
    {
    }
}

namespace verbb\knockknock\models {
    class Settings extends \craft\base\Model
    {
    }
}

namespace {
    use verbb\knockknock\KnockKnock;

    class Craft
    {
        public static object $app;
        public static array $warnings = [];

        public static function warning(string $message, string $method): void
        {
            self::$warnings[] = [$message, $method];
        }
    }

    final class FakeApplication
    {
        public function getDb(): object
        {
            return new class {
                public function getIsMysql(): bool
                {
                    return false;
                }
            };
        }
    }

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    Craft::$app = new FakeApplication();

    require dirname(__DIR__, 2) . '/src/KnockKnock.php';

    $plugin = new KnockKnock();
    $matchesUrlRules = new ReflectionMethod(KnockKnock::class, '_matchesUrlRules');
    $normalizePath = new ReflectionMethod(KnockKnock::class, '_normalizePath');

    $match = fn(string $path, array $rules, bool $caseInsensitive = false): int => $matchesUrlRules->invoke($plugin, $path, $rules, $caseInsensitive);
    $normalize = fn(string $path, bool $relativeToSite = false): string => $normalizePath->invoke($plugin, $path, $relativeToSite);

    assertSame(1, $match('some-channel/news', ['some-channel/(.*)']), 'A documented regex must match the intended complete path.');
    assertSame(0, $match('private/some-channel/news', ['some-channel/(.*)']), 'A documented regex must not match at an inner path boundary.');
    assertSame(1, $match('foo', ['(foo|bar)']), 'A grouped alternative must match its first complete alternative.');
    assertSame(1, $match('bar', ['(foo|bar)']), 'A grouped alternative must match its second complete alternative.');
    assertSame(0, $match('prefix/foo', ['(foo|bar)']), 'A grouped alternative must not match a prefixed path.');
    assertSame(0, $match('bar/suffix', ['(foo|bar)']), 'A grouped alternative must not match a suffixed path.');
    assertSame(1, $match('news/news', ['(news)/\\1']), 'The full-path wrapper must preserve user capture numbering.');

    assertSame(0, $match('Public', ['public']), 'Exact unprotected rules must remain case-sensitive.');
    assertSame(1, $match('Public', ['public'], true), 'Exact protected rules must remain case-insensitive.');
    assertSame(1, $match('SOME-CHANNEL/NEWS', ['some-channel/(.*)']), 'Regex rules must remain case-insensitive.');

    assertSame(1, $match('some-channel/news', ['https://EXAMPLE.test/subfolder/some-channel/(.*)']), 'A same-site absolute rule must remain relative to the site base path.');
    assertSame(0, $match('some-channel/news', ['https://other.test/subfolder/some-channel/(.*)']), 'A foreign-site absolute rule must remain excluded.');
    assertSame(1, $match('some-channel/news', ['some-channel/%28.*%29']), 'Encoded regex markers must retain one-pass decoding behavior.');
    assertSame('some-channel/news', $normalize('/some-channel/news/?page=2'), 'Path normalization must continue to ignore query strings and outer slashes.');

    Craft::$warnings = [];
    assertSame(-1, $match('private', ['private/(']), 'A malformed regex must return the invalid result instead of a no-match result.');
    assertSame(1, count(Craft::$warnings), 'A malformed regex must produce a visible diagnostic.');
    assertSame(1, $match('public', ['private/(', 'public']), 'A valid match must win when another configured regex is malformed.');

    Craft::$warnings = [];
    assertSame(-1, $match('private/some-channel/news', ['x)|some-channel/(.*']), 'A malformed regex must not use the wrapper to restore substring matching.');
    assertSame(1, count(Craft::$warnings), 'A wrapper-breaking malformed regex must produce a visible diagnostic.');
    assertSame(0, $match('some-channel/evil', ['some-channel/(*ACCEPT)news']), 'A regex control verb must not report a match before consuming the complete path.');

    assertSame(1, $match('literal`/value', ['(literal`/value)']), 'A literal backtick must not collide with the regex delimiter.');

    $source = file_get_contents(dirname(__DIR__, 2) . '/src/KnockKnock.php');
    assertSame(true, str_contains($source, '_matchesUrlRules($currentPath, $settings->getUnprotectedUrls()) === self::URL_RULE_MATCH'), 'Only a valid unprotected match may bypass the gate.');
    assertSame(true, str_contains($source, '_matchesUrlRules($currentPath, $settings->getProtectedUrls(), true) === self::URL_RULE_NO_MATCH'), 'An invalid protected rule must keep the gate enabled.');

    echo "Knock Knock URL rule matching security fixture passed.\n";
}
