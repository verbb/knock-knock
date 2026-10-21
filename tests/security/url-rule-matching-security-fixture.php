<?php

declare(strict_types=1);

namespace craft\base {
    class Element
    {
        public const HOMEPAGE_URI = '__home__';
    }

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
        public object|false $matchedElement = false;
        public int $matchedElementCalls = 0;

        public function getUrlManager(): self
        {
            return $this;
        }

        public function getMatchedElement(): object|false
        {
            $this->matchedElementCalls++;

            return $this->matchedElement;
        }

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

    Craft::$app->matchedElement = (object)['uri'=>'some-channel/news'];
    assertSame(1, $match('somé-channel/news', ['some-channel/(.*)'], true), 'A protected regex must cover equivalent paths to its matched element.');
    assertSame(1, $match('somé-channel/news', ['https://example.test/subfolder/some-channel/(.*)'], true), 'Canonical matching must preserve same-site base paths.');
    assertSame(0, $match('somé-channel/news', ['https://other.test/subfolder/some-channel/(.*)'], true), 'Canonical matching must not activate foreign-site rules.');
    assertSame(0, $match('somé-channel/news', ['some-channel/(.*)']), 'Canonical matching must not expand unprotected regex rules.');
    assertSame(1, $match('somé-channel/news', ['(somé-channel/news)'], true), 'Direct request-path matches must remain supported.');

    Craft::$app->matchedElementCalls = 0;
    assertSame(1, $match('somé-channel/news', ['unrelated/(.*)', 'another/(.*)', 'some-channel/(.*)'], true), 'Later protected rules must still check the canonical path.');
    assertSame(1, Craft::$app->matchedElementCalls, 'Resolve the matched element only once across regex rules.');
    Craft::$app->matchedElement = false;
    Craft::$app->matchedElementCalls = 0;
    assertSame(0, $match('template-page', ['unrelated/(.*)', 'another/(.*)'], true), 'Template routes without a matched element must retain direct-rule behavior.');
    assertSame(1, Craft::$app->matchedElementCalls, 'Cache missing matched elements across regex rules.');

    Craft::$app->matchedElement = (object)['uri'=>'some-channel/evil'];
    assertSame(0, $match('somé-channel/evil', ['some-channel/(*ACCEPT)news'], true), 'Canonical matching must preserve control-verb full-path safeguards.');
    Craft::$app->matchedElement = (object)['uri'=>'news/news'];
    assertSame(1, $match('néws/news', ['(news)/\\1'], true), 'Canonical matching must preserve backreferences.');
    Craft::$app->matchedElement = (object)['uri'=>'some%20channel/news'];
    assertSame(1, $match('some channel/news', ['some%20channel/(.*)'], true), 'A direct request for an encoded canonical URI must remain protected.');
    assertSame(1, $match('somé channel/news', ['some%20channel/(.*)'], true), 'Canonical URI normalization must agree with direct request and rule normalization.');
    Craft::$app->matchedElement = (object)['uri'=>'__home__'];
    assertSame(1, $match('alternate-home', ['()'], true), 'The homepage canonical URI must use the empty URL path.');
    Craft::$app->matchedElement = false;

    $source = file_get_contents(dirname(__DIR__, 2) . '/src/KnockKnock.php');
    assertSame(true, str_contains($source, '_matchesUrlRules($currentPath, $settings->getUnprotectedUrls()) === self::URL_RULE_MATCH'), 'Only a valid unprotected match may bypass the gate.');
    assertSame(true, str_contains($source, '_matchesUrlRules($currentPath, $settings->getProtectedUrls(), true) === self::URL_RULE_NO_MATCH'), 'An invalid protected rule must keep the gate enabled.');

    echo "Knock Knock URL rule matching security fixture passed.\n";
}
