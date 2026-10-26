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

        public function createControllerByID(string $id): object
        {
            return new \FakePromptController();
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

        public static bool $staticValid = false;
        public static int $staticValidationCalls = 0;

        public static function validateStatic(string $password, string $cookieName): bool
        {
            self::$staticValidationCalls++;

            return $cookieName === 'knockKnockAccess' && self::$staticValid;
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

        public static function sanitize(?string $url): string
        {
            return is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//') ? $url : '/';
        }
    }
}

namespace verbb\knockknock\models {
    class Settings extends \craft\base\Model
    {
        public array $protectedUrls = [];
        public array $unprotectedUrls = [];
        public bool $enableStaticCaching = false;

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

        public function getEnableStaticCaching(): bool
        {
            return $this->enableStaticCaching;
        }

        public function getStaticCacheCookieName(): string
        {
            return 'knockKnockAccess';
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
        public mixed $endResponse = null;
        public FakeResponse $response;

        public function __construct(private readonly FakeRequest $request, private readonly mixed $identity = null)
        {
            $this->response = new FakeResponse();
        }

        public function end(int $status = 0, mixed $response = null): void
        {
            $this->endCalls++;
            $this->endResponse = $response;
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
            return new class($this->identity) {
                public function __construct(private readonly mixed $identity)
                {
                }

                public function getIdentity(): mixed
                {
                    return $this->identity;
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
        public function __construct(
            private readonly string $path,
            private readonly bool $isGet = true,
            private readonly ?string $token = null,
        ) {
        }

        public function getIsGet(): bool
        {
            return $this->isGet;
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
            return $this->token;
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

    final class FakePromptController
    {
        public static array $prompts = [];

        public function renderPrompt(string $redirect): string
        {
            self::$prompts[] = $redirect;

            return "prompt:$redirect";
        }
    }

    function runGate(int $accessStatus, string $path = 'members', array $unprotectedUrls = [], array $protectedUrls = [], array $options = []): array
    {
        $settings = new Settings();
        $settings->unprotectedUrls = $unprotectedUrls;
        $settings->protectedUrls = $protectedUrls;
        $settings->enableStaticCaching = $options['static'] ?? false;
        $request = new FakeRequest($path, $options['get'] ?? true, $options['token'] ?? null);
        $application = new FakeApplication($request, $options['user'] ?? null);

        Craft::$app = $application;
        KnockKnock::$plugin = new FakePlugin($settings);
        IpHelper::$accessStatus = $accessStatus;
        AccessTokenHelper::$valid = $options['valid'] ?? true;
        AccessTokenHelper::$validationCalls = 0;
        AccessTokenHelper::$staticValid = $options['staticValid'] ?? false;
        AccessTokenHelper::$staticValidationCalls = 0;
        ReturnUrlHelper::$storeCalls = 0;
        FakePromptController::$prompts = [];

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

    foreach (['#' => '#', '?' => '\\?'] as $character => $expression) {
        [$application] = runGate(IpHelper::ACCESS_DENIED, 'members/article' . $character . 'section', [], ['members/(.*)' . $expression . 'section']);
        assertSame(1, $application->endCalls, 'Literal request punctuation must reach protected regex matching intact.');
    }

    [$application] = runGate(IpHelper::ACCESS_DENIED, 'members/%23section', [], ['members/(%2523section)']);
    assertSame(1, $application->endCalls, 'An already decoded request path must not be decoded a second time.');

    foreach (['login', 'set-password', 'verify-email', 'knock-knock/who-is-there'] as $accountPath) {
        [$application] = runGate(IpHelper::ACCESS_DENIED, $accountPath);
        assertSame(0, $application->endCalls, 'Normal account and challenge routes must remain available.');
        [$application] = runGate(IpHelper::ACCESS_DENIED, $accountPath . '#section');
        assertSame(1, $application->endCalls, 'A literal path suffix must not become an account-route exemption.');
    }

    // Static caching mode
    // -------------------------------------------------------------------------

    $static = ['static' => true];

    [$application, $validationCalls, $storeCalls] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], $static);
    assertSame(['/members'], FakePromptController::$prompts, 'Static caching must show the prompt at the protected URL.');
    assertSame('prompt:/members', $application->endResponse, 'Static caching must end the request with the prompt response.');
    assertSame(null, $application->response->redirect, 'Static caching must not redirect to the login path.');
    assertSame(0, $storeCalls, 'Static caching must not store the return URL in the session.');
    assertSame(0, $application->response->noCacheCalls, 'The gate must leave the prompt cacheable for visitors without the access cookie.');
    assertSame(0, $validationCalls, 'Static caching must not accept the default access cookie.');
    assertSame(1, AccessTokenHelper::$staticValidationCalls, 'Static caching must check the static access cookie.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], $static + ['staticValid' => true]);
    assertSame(0, $application->endCalls, 'A valid static access cookie must grant access.');
    assertSame(0, $application->response->noCacheCalls, 'Pages accessed with the static access cookie must remain cacheable.');

    [$application] = runGate(IpHelper::ACCESS_DENIED, 'members', [], [], $static + ['staticValid' => true]);
    assertSame(0, AccessTokenHelper::$staticValidationCalls, 'A denied IP must not be granted access by a static access cookie.');
    assertSame(['/members'], FakePromptController::$prompts, 'A denied IP must still be shown the prompt.');

    [$application] = runGate(IpHelper::ACCESS_ALLOWED, 'members', [], [], $static);
    assertSame(0, $application->endCalls, 'An allowed IP must retain its gate bypass.');
    assertSame(1, $application->response->noCacheCalls, 'A protected page served to an allowed IP must not be cached for visitors without the access cookie.');

    [$application] = runGate(IpHelper::ACCESS_ALLOWED, 'public', ['public'], [], $static);
    assertSame(0, $application->response->noCacheCalls, 'An unprotected page served to an allowed IP may be cached.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], $static + ['user' => new stdClass()]);
    assertSame(0, $application->endCalls, 'Logged-in users must retain their gate bypass.');
    assertSame(1, $application->response->noCacheCalls, 'A protected page served to a logged-in user must not be cached for visitors without the access cookie.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], ['user' => new stdClass()]);
    assertSame(0, $application->response->noCacheCalls, 'Without static caching, logged-in users’ responses must be left alone.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], $static + ['token' => 'preview-token']);
    assertSame(0, $application->endCalls, 'Token requests must retain their gate bypass.');
    assertSame(1, $application->response->noCacheCalls, 'A protected page served for a token must not be cached for visitors without the access cookie.');

    [$application, , $storeCalls] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], $static + ['get' => false]);
    assertSame([], FakePromptController::$prompts, 'Non-GET requests must not be shown an in-place prompt.');
    assertSame(1, $storeCalls, 'Non-GET requests must use the existing redirect flow.');
    assertSame(1, $application->response->noCacheCalls, 'The redirect to the login path must not be cached.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'public', ['public'], [], $static);
    assertSame(0, $application->endCalls, 'Unprotected URLs must remain public with static caching.');

    [$application] = runGate(IpHelper::ACCESS_NEUTRAL, 'members', [], [], ['valid' => false]);
    assertSame(1, $application->endCalls, 'Without static caching, visitors without a valid access cookie must reach the gate.');
    assertSame([], FakePromptController::$prompts, 'Without static caching, the prompt must not be shown in place.');
    assertSame(1, $application->response->noCacheCalls, 'Without static caching, the gate must keep sending no-cache headers.');

    echo "Knock Knock access-cookie IP policy security fixture passed.\n";
}
