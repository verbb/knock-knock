<?php

declare(strict_types=1);

namespace {
    use verbb\knockknock\helpers\AccessTokenHelper;

    class Craft
    {
        public static object $app;
    }

    final class FakeCookie
    {
        public function __construct(public mixed $value)
        {
        }
    }

    final class FakeApplication
    {
        public string $securityKey = 'security-key';
        public string $siteUid = 'site-uid';
        public array $cookies = [];

        public function getConfig(): object
        {
            return new class($this->securityKey) {
                public function __construct(private readonly string $securityKey)
                {
                }

                public function getGeneral(): object
                {
                    return (object)['securityKey' => $this->securityKey];
                }
            };
        }

        public function getRequest(): object
        {
            return new class($this->cookies) {
                public function __construct(private readonly array $cookies)
                {
                }

                public function getCookies(): object
                {
                    return new class($this->cookies) {
                        public function __construct(private readonly array $cookies)
                        {
                        }

                        public function get(string $name): ?FakeCookie
                        {
                            return array_key_exists($name, $this->cookies) ? new FakeCookie($this->cookies[$name]) : null;
                        }
                    };
                }
            };
        }

        public function getSecurity(): object
        {
            return new class {
                public function compareString(string $expected, string $actual): bool
                {
                    return hash_equals($expected, $actual);
                }
            };
        }

        public function getSites(): object
        {
            return new class($this->siteUid) {
                public function __construct(private readonly string $siteUid)
                {
                }

                public function getCurrentSite(): object
                {
                    return (object)['uid' => $this->siteUid];
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

    function tokenFor(string $password, string $securityKey = 'security-key', string $siteUid = 'site-uid'): string
    {
        $application = new FakeApplication();
        $application->securityKey = $securityKey;
        $application->siteUid = $siteUid;
        Craft::$app = $application;

        return AccessTokenHelper::createStatic($password);
    }

    function validates(mixed $cookieValue, string $password = 'correct-password', string $cookieName = 'knockKnockAccess'): bool
    {
        $application = new FakeApplication();
        $application->cookies = ['knockKnockAccess' => $cookieValue];
        Craft::$app = $application;

        return AccessTokenHelper::validateStatic($password, $cookieName);
    }

    require dirname(__DIR__, 2) . '/src/helpers/AccessTokenHelper.php';

    $token = tokenFor('correct-password');

    // Cache keys can only be shared if every visitor who enters the password gets the same token
    assertSame($token, tokenFor('correct-password'), 'The static token must be the same for every visitor.');
    assertSame(1, preg_match('/^s1\.[0-9a-f]{64}$/', $token), 'The static token must be a versioned HMAC.');
    assertSame(false, str_contains($token, 'correct-password'), 'The static token must not contain the password.');

    // It must change whenever anything that should invalidate access changes
    assertSame(false, $token === tokenFor('another-password'), 'The static token must change with the password.');
    assertSame(false, $token === tokenFor('correct-password', 'another-security-key'), 'The static token must depend on the security key.');
    assertSame(false, $token === tokenFor('correct-password', 'security-key', 'another-site-uid'), 'The static token must be specific to each site.');

    // The default token format must not be accepted as a static token, and vice versa
    assertSame(false, str_starts_with($token, 'v1.'), 'The static token must not share the default token’s version.');

    assertSame(true, validates($token), 'The static token must validate.');
    assertSame(false, validates(tokenFor('another-password')), 'A token for another password must not validate.');
    assertSame(false, validates(tokenFor('correct-password', 'security-key', 'another-site-uid')), 'A token for another site must not validate.');
    assertSame(false, validates($token . 'x'), 'A modified token must not validate.');
    assertSame(false, validates(''), 'An empty token must not validate.');
    assertSame(false, validates(['s1', 'array']), 'A non-string token must not validate.');
    assertSame(false, validates($token, 'correct-password', 'anotherCookie'), 'A token must only be read from the configured cookie.');

    $_COOKIE = ['knockKnockAccess' => 'anything'];
    assertSame(true, AccessTokenHelper::hasCookie('knockKnockAccess'), 'The access cookie’s presence must be detected, whatever its value.');
    assertSame(false, AccessTokenHelper::hasCookie('anotherCookie'), 'Other cookies must not be mistaken for the access cookie.');
    $_COOKIE = [];

    echo "Knock Knock static access token security fixture passed.\n";
}
