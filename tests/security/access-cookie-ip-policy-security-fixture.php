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
            return 'https://example.test/' . ltrim($path, '/');
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
        public static bool $valid = true;
        public static int $validationCalls = 0;

        public static function validate(string $password, int $duration): bool
        {
            self::$validationCalls++;

            return self::$valid;
        }
    }

    class IpHelper
    {
        public const ACCESS_DENIED = -1;
        public const ACCESS_NEUTRAL = 0;
        public const ACCESS_ALLOWED = 1;

        public static int $accessStatus = self::ACCESS_NEUTRAL;

        public static function getUserIp(): string
        {
            return '203.0.113.10';
        }

        public static function getAccessStatus(string $ip, array $allowIps, array $denyIps): int
        {
            return self::$accessStatus;
        }
    }

    class ReturnUrlHelper
    {
        public static int $storeCalls = 0;

        public static function store(string $url): void
        {
            self::$storeCalls++;
        }
    }
}

namespace verbb\knockknock\models {
    class Settings extends \craft\base\Model
    {
        public array $protectedUrls = [];
        public array $unprotectedUrls = [];

        public function getAllowIps(): array
        {
            return [];
        }

        public function getCookieDuration(): int
        {
            return 3600;
        }

        public function getDenyIps(): array
        {
            return [];
        }

        public function getEnableCpProtection(): bool
        {
            return false;
        }

        public function getEnabled(): bool
        {
            return true;
        }

        public function getLoginPath(): string
        {
            return 'knock-knock/who-is-there';
        }

        public function getPassword(): string
        {
            return 'correct-password';
        }

        public function getProtectedUrls(): array
        {
            return $this->protectedUrls;
        }

        public function getUnprotectedUrls(): array
        {
            return $this->unprotectedUrls;
        }
    }
}

namespace {
    use verbb\knockknock\helpers\AccessTokenHelper;
    use verbb\knockknock\helpers\IpHelper;
    use verbb\knockknock\helpers\ReturnUrlHelper;
    use verbb\knockknock\KnockKnock;
    use verbb\knockknock\models\Settings;

    class Craft
    {
        public static object $app;

        public static function warning(string $message, string $method): void
        {
        }
    }

    final class FakeApplication
    {
        public int $endCalls = 0;
        public FakeResponse $response;

        public function __construct(private readonly FakeRequest $request)
        {
            $this->response = new FakeResponse();
        }

        public function end(): void
        {
            $this->endCalls++;
        }

        public function getConfig(): object
        {
            return new class {
                public function getGeneral(): object
                {
                    return new class {
                        public function getLoginPath(): string
                        {
                            return 'login';
                        }

                        public function getSetPasswordPath(): string
                        {
                            return 'set-password';
                        }

                        public function getVerifyEmailPath(): string
                        {
                            return 'verify-email';
                        }
                    };
                }
            };
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

        public function getRequest(): FakeRequest
        {
            return $this->request;
        }

        public function getResponse(): FakeResponse
        {
            return $this->response;
        }

        public function getUser(): object
        {
            return new class {
                public function getIdentity(): mixed
                {
                    return null;
                }
            };
        }
    }

    final class FakePlugin
    {
        public function __construct(private readonly Settings $settings)
        {
        }

        public function getSettings(): Settings
        {
            return $this->settings;
        }
    }

    final class FakeRequest
    {
        public function __construct(private readonly string $path)
        {
        }

        public function getIsActionRequest(): bool
        {
            return false;
        }

        public function getIsConsoleRequest(): bool
        {
            return false;
        }

        public function getIsCpRequest(): bool
        {
            return false;
        }

        public function getIsLivePreview(): bool
        {
            return false;
        }

        public function getIsPreview(): bool
        {
            return false;
        }

        public function getIsSiteRequest(): bool
        {
            return true;
        }

        public function getPathInfo(): string
        {
            return $this->path;
        }

        public function getToken(): mixed
        {
            return null;
        }

        public function getUrl(): string
        {
            return '/' . $this->path;
        }
    }

    final class FakeResponse
    {
        public int $noCacheCalls = 0;
        public ?string $redirect = null;

        public function redirect(string $url): self
        {
            $this->redirect = $url;

            return $this;
        }

        public function setNoCacheHeaders(): void
        {
            $this->noCacheCalls++;
        }
    }

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    function runGate(int $accessStatus, string $path = 'members', array $unprotectedUrls = [], array $protectedUrls = []): array
    {
        $settings = new Settings();
        $settings->unprotectedUrls = $unprotectedUrls;
        $settings->protectedUrls = $protectedUrls;
        $request = new FakeRequest($path);
        $application = new FakeApplication($request);

        Craft::$app = $application;
        KnockKnock::$plugin = new FakePlugin($settings);
        IpHelper::$accessStatus = $accessStatus;
        AccessTokenHelper::$valid = true;
        AccessTokenHelper::$validationCalls = 0;
        ReturnUrlHelper::$storeCalls = 0;

        $plugin = new KnockKnock();
        $testAccess = new ReflectionMethod(KnockKnock::class, '_testAccess');
        $testAccess->invoke($plugin);

        return [$application, AccessTokenHelper::$validationCalls, ReturnUrlHelper::$storeCalls];
    }

    require dirname(__DIR__, 2) . '/src/KnockKnock.php';

    [$application, $validationCalls, $storeCalls] = runGate(IpHelper::ACCESS_DENIED);
    assertSame(0, $validationCalls, 'A denied IP must not be granted access by an existing cookie.');
    assertSame(1, $application->endCalls, 'A denied cookie holder must reach the password gate.');
    assertSame(1, $storeCalls, 'A denied cookie holder must preserve the protected return URL.');

    [$application, $validationCalls] = runGate(IpHelper::ACCESS_NEUTRAL);
    assertSame(1, $validationCalls, 'A neutral IP must retain normal cookie validation.');
    assertSame(0, $application->endCalls, 'A valid cookie must continue to grant a neutral IP access.');

    [$application, $validationCalls] = runGate(IpHelper::ACCESS_ALLOWED);
    assertSame(0, $validationCalls, 'An allowed IP must bypass the gate without consulting the cookie.');
    assertSame(0, $application->endCalls, 'An allowed IP must retain its gate bypass.');

    [$application, $validationCalls] = runGate(IpHelper::ACCESS_DENIED, 'public', ['public']);
    assertSame(0, $validationCalls, 'A denied IP must not regain gated access through its cookie.');
    assertSame(0, $application->endCalls, 'A deliberately unprotected URL must remain public for a denied IP.');

    [$application] = runGate(IpHelper::ACCESS_DENIED, 'public', [], ['members']);
    assertSame(0, $application->endCalls, 'A URL outside the configured protected set must remain public for a denied IP.');

    echo "Knock Knock access-cookie IP policy security fixture passed.\n";
}
