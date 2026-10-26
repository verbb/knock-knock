<?php

declare(strict_types=1);

namespace yii\web {
    class Response
    {
        public array $data = [];
        public ?string $redirect = null;
        public ?string $template = null;
        public ?string $templateMode = null;
    }

    class BadRequestHttpException extends \RuntimeException
    {
    }

    class Cookie
    {
        public function __construct(public array $config)
        {
        }
    }

    class HttpException extends \RuntimeException
    {
        public function __construct(public int $statusCode)
        {
            parent::__construct('', $statusCode);
        }
    }

    class NotFoundHttpException extends HttpException
    {
        public function __construct()
        {
            parent::__construct(404);
        }
    }

    class MethodNotAllowedHttpException extends HttpException
    {
        public function __construct()
        {
            parent::__construct(405);
        }
    }
}

namespace craft\web {
    use yii\web\MethodNotAllowedHttpException;
    use yii\web\Response;

    class Controller
    {
        public mixed $request = null;
        public mixed $response = null;
        public FakeView $view;

        public function __construct()
        {
            $this->view = new FakeView();
        }

        public function getView(): FakeView
        {
            return $this->view;
        }

        public function redirect(string $url): Response
        {
            $response = new Response();
            $response->redirect = $url;

            return $response;
        }

        public function renderTemplate(string $template, array $data, ?string $templateMode = null): Response
        {
            $response = new Response();
            $response->data = $data;
            $response->template = $template;
            $response->templateMode = $templateMode;

            return $response;
        }

        public function requirePostRequest(): void
        {
            if (!$this->request->getIsPost()) {
                throw new MethodNotAllowedHttpException();
            }
        }
    }

    class FakeView
    {
        public const TEMPLATE_MODE_CP = 'cp';
        public const TEMPLATE_MODE_SITE = 'site';

        public string $templateMode = self::TEMPLATE_MODE_CP;

        public function doesTemplateExist(string $template): bool
        {
            return true;
        }

        public function getTemplateMode(): string
        {
            return $this->templateMode;
        }

        public function setTemplateMode(string $templateMode): void
        {
            $this->templateMode = $templateMode;
        }
    }
}

namespace verbb\knockknock\models {
    class Login
    {
        public ?string $ipAddress = null;
    }

    class Settings
    {
        public array $allowIps = [];
        public bool $checkInvalidLogins = true;
        public array $denyIps = [];
        public bool $enabled = true;
        public bool $enableStaticCaching = false;
        public string $password = 'correct-password';

        public function getAllowIps(): array
        {
            return $this->allowIps;
        }

        public function getCookieDuration(): int
        {
            return 3600;
        }

        public function getDefaultTemplate(): string
        {
            return 'ask';
        }

        public function getDenyIps(): array
        {
            return $this->denyIps;
        }

        public function getEnabled(): bool
        {
            return $this->enabled;
        }

        public function getEnableStaticCaching(): bool
        {
            return $this->enableStaticCaching;
        }

        public function getStaticCacheCookieName(): string
        {
            return 'knockKnockAccess';
        }

        public function getPassword(): string
        {
            return $this->password;
        }

        public function getTemplate(): string
        {
            return '';
        }

        public string $forcedRedirect = '';
    }
}

namespace verbb\knockknock\helpers {
    class AccessTokenHelper
    {
        public static function create(string $password, int $duration): string
        {
            return "token:$password:$duration";
        }

        public static function getCookieName(): string
        {
            return 'knock-knock-access';
        }

        public static array $presentCookies = [];

        public static function createStatic(string $password): string
        {
            return "static:$password";
        }

        public static function hasCookie(string $cookieName): bool
        {
            return in_array($cookieName, self::$presentCookies, true);
        }
    }

    class IpHelper
    {
        public const ACCESS_DENIED = -1;
        public const ACCESS_NEUTRAL = 0;
        public const ACCESS_ALLOWED = 1;

        public static function getUserIp(): string
        {
            return '203.0.113.10';
        }

        public static function ipInCidrList(string $ipAddress, array $cidrs): bool
        {
            return in_array($ipAddress, $cidrs, true);
        }

        public static function getAccessStatus(string $ipAddress, array $allowIps, array $denyIps): int
        {
            if (self::ipInCidrList($ipAddress, $allowIps)) {
                return self::ACCESS_ALLOWED;
            }

            if (self::ipInCidrList($ipAddress, $denyIps)) {
                return self::ACCESS_DENIED;
            }

            return self::ACCESS_NEUTRAL;
        }
    }

    class ReturnUrlHelper
    {
        public static int $forgetCalls = 0;

        public static function forget(): void
        {
            self::$forgetCalls++;
        }

        public static function get(): string
        {
            return '/protected';
        }

        public static function sanitize(?string $url): string
        {
            return is_string($url) && str_starts_with($url, '/') && !str_starts_with($url, '//') ? $url : '/';
        }
    }
}

namespace verbb\knockknock {
    class KnockKnock
    {
        public static mixed $plugin = null;
    }
}

namespace {
    use verbb\knockknock\KnockKnock;
    use verbb\knockknock\controllers\DefaultController;
    use verbb\knockknock\helpers\ReturnUrlHelper;
    use verbb\knockknock\models\Login;
    use verbb\knockknock\models\Settings;
    use verbb\knockknock\helpers\AccessTokenHelper;
    use yii\web\BadRequestHttpException;
    use yii\web\MethodNotAllowedHttpException;
    use yii\web\NotFoundHttpException;

    class Craft
    {
        public static object $app;

        public static function cookieConfig(array $config): array
        {
            return $config;
        }

        public static function t(string $category, string $message): string
        {
            return $message;
        }

        public static function warning(string $message, string $method): void
        {
        }
    }

    final class FakeApplication
    {
        public FakeResponse $response;

        public function __construct(public bool $storeUserIps = true)
        {
            $this->response = new FakeResponse();
        }

        public function getConfig(): object
        {
            return new class($this->storeUserIps) {
                public function __construct(private readonly bool $storeUserIps)
                {
                }

                public function getGeneral(): object
                {
                    return (object)['storeUserIps' => $this->storeUserIps];
                }
            };
        }

        public function getResponse(): FakeResponse
        {
            return $this->response;
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
    }

    final class FakeCookies
    {
        public array $cookies = [];

        public function add(object $cookie): void
        {
            $this->cookies[] = $cookie;
        }
    }

    final class FakeLogins
    {
        public bool $locked = false;
        public int $lockoutChecks = 0;
        public array $saved = [];

        public function checkLockout(string $ipAddress): bool
        {
            $this->lockoutChecks++;

            return $this->locked;
        }

        public function saveLogin(Login $login): bool
        {
            $this->saved[] = clone $login;

            return true;
        }
    }

    final class FakePlugin
    {
        public int $settingsReads = 0;

        public function __construct(
            private readonly Settings $settings,
            private readonly FakeLogins $logins,
        ) {
        }

        public function getLogins(): FakeLogins
        {
            return $this->logins;
        }

        public function getSettings(): Settings
        {
            $this->settingsReads++;

            return $this->settings;
        }
    }

    final class FakeRequest
    {
        public int $bodyReads = 0;
        public int $combinedReads = 0;

        public function __construct(
            private readonly bool $isPost,
            private readonly array $query = [],
            private readonly array $body = [],
        ) {
        }

        public function getBodyParam(string $name, mixed $defaultValue = null): mixed
        {
            $this->bodyReads++;

            return $this->body[$name] ?? $defaultValue;
        }

        public function getIsPost(): bool
        {
            return $this->isPost;
        }

        public function getIsGet(): bool
        {
            return !$this->isPost;
        }

        public function getValidatedBodyParam(string $name): ?string
        {
            $value = $this->body[$name] ?? null;

            if ($value === null) {
                return null;
            }

            // Simulates Craft’s hashed body params: only values signed by the app are accepted
            if (!is_string($value) || !str_starts_with($value, 'signed:')) {
                throw new BadRequestHttpException('Request contained an invalid body param');
            }

            return substr($value, strlen('signed:'));
        }

        public function getParam(string $name, mixed $defaultValue = null): mixed
        {
            $this->combinedReads++;

            return $this->query[$name] ?? $this->body[$name] ?? $defaultValue;
        }
    }

    final class FakeResponse
    {
        public FakeCookies $cookies;
        public int $noCacheCalls = 0;

        public function __construct()
        {
            $this->cookies = new FakeCookies();
        }

        public function getCookies(): FakeCookies
        {
            return $this->cookies;
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

    function assertThrows(string $expectedClass, callable $callback, string $message): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            if ($exception instanceof $expectedClass) {
                return;
            }

            throw new RuntimeException($message . '\nUnexpected exception: ' . $exception::class);
        }

        throw new RuntimeException($message . '\nNo exception was thrown.');
    }

    function createController(bool $isPost, array $query, array $body, ?Settings $settings = null, ?FakeLogins $logins = null, bool $storeUserIps = true): array
    {
        $settings ??= new Settings();
        $logins ??= new FakeLogins();
        $request = new FakeRequest($isPost, $query, $body);
        $plugin = new FakePlugin($settings, $logins);
        $application = new FakeApplication($storeUserIps);
        Craft::$app = $application;
        KnockKnock::$plugin = $plugin;

        $controller = new DefaultController();
        $controller->request = $request;
        $controller->response = $application->response;

        return [$controller, $request, $plugin, $logins, $application];
    }

    require dirname(__DIR__, 2) . '/src/controllers/DefaultController.php';

    [$controller, $request, $plugin, $logins, $application] = createController(false, ['password' => 'wrong'], []);
    assertThrows(MethodNotAllowedHttpException::class, $controller->actionAnswer(...), 'GET access attempts must be rejected.');
    assertSame(0, $plugin->settingsReads, 'Rejected methods must not reach plugin settings.');
    assertSame(0, $request->bodyReads, 'Rejected methods must not read credentials.');
    assertSame(0, count($logins->saved), 'Rejected methods must not persist failures.');
    assertSame(0, count($application->response->cookies->cookies), 'Rejected methods must not set access cookies.');

    ReturnUrlHelper::$forgetCalls = 0;
    [$controller, $request, $plugin, $logins, $application] = createController(true, ['password' => 'wrong'], ['password' => 'correct-password']);
    $response = $controller->actionAnswer();
    assertSame('/protected', $response->redirect, 'A correct body password must preserve the redirect flow.');
    assertSame(1, count($application->response->cookies->cookies), 'A correct body password must set the access cookie.');
    assertSame(0, count($logins->saved), 'A correct body password must not persist a failed attempt.');
    assertSame(0, $request->combinedReads, 'The query string must not be consulted for the password.');
    assertSame(1, ReturnUrlHelper::$forgetCalls, 'A successful access attempt must clear the return URL.');

    [$controller, $request, $plugin, $logins, $application] = createController(true, ['password' => 'correct-password'], ['password' => 'wrong']);
    $response = $controller->actionAnswer();
    assertSame('Invalid password', $response->data['errors']['password'] ?? null, 'A correct query password must not override an invalid body password.');
    assertSame('203.0.113.10', $logins->saved[0]->ipAddress ?? null, 'A failed attempt must retain the resolved IP metadata.');
    assertSame(false, property_exists($logins->saved[0], 'password'), 'A failed attempt must not retain the submitted password.');
    assertSame(0, count($application->response->cookies->cookies), 'An invalid body password must not set an access cookie.');

    [$controller, $request, $plugin, $logins] = createController(true, ['password' => 'correct-password'], []);
    $response = $controller->actionAnswer();
    assertSame('Invalid password', $response->data['errors']['password'] ?? null, 'A missing body password must retain the existing invalid response.');
    assertSame('203.0.113.10', $logins->saved[0]->ipAddress ?? null, 'A missing body password must still record attempt metadata.');
    assertSame(false, property_exists($logins->saved[0], 'password'), 'A missing body password must not add a password field to attempt metadata.');

    [$controller, $request, $plugin, $logins] = createController(true, [], ['password' => ['wrong']]);
    $response = $controller->actionAnswer();
    assertSame('Invalid password', $response->data['errors']['password'] ?? null, 'A non-string body password must retain the existing invalid response.');
    assertSame('203.0.113.10', $logins->saved[0]->ipAddress ?? null, 'A non-string body password must still record attempt metadata.');
    assertSame(false, property_exists($logins->saved[0], 'password'), 'A non-string body password must not add a password field to attempt metadata.');

    $lockedLogins = new FakeLogins();
    $lockedLogins->locked = true;
    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password'], null, $lockedLogins);
    $response = $controller->actionAnswer();
    assertSame('Too many invalid attempts', $response->data['errors']['password'] ?? null, 'Existing lockouts must retain their current response.');
    assertSame(0, count($logins->saved), 'A locked request must not add another failed attempt.');
    assertSame(0, count($application->response->cookies->cookies), 'A locked request must not set an access cookie.');

    foreach ([[false, false], [false, true], [true, false], [true, true]] as [$checkInvalidLogins, $storeUserIps]) {
        $deniedSettings = new Settings();
        $deniedSettings->checkInvalidLogins = $checkInvalidLogins;
        $deniedSettings->denyIps = ['203.0.113.10'];
        [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password'], $deniedSettings, null, $storeUserIps);
        $response = $controller->actionAnswer();
        assertSame('Too many invalid attempts', $response->data['errors']['password'] ?? null, 'A statically denied IP must be refused independently of invalid-login recording settings.');
        assertSame(0, $logins->lockoutChecks, 'A static deny rule must not require the dynamic lockout query.');
        assertSame(0, count($application->response->cookies->cookies), 'A denied IP must not receive an access cookie.');
        assertSame(0, count($logins->saved), 'A statically denied password submission must not be stored as a failed login.');
    }

    $allowedSettings = new Settings();
    $allowedSettings->checkInvalidLogins = false;
    $allowedSettings->allowIps = ['203.0.113.10'];
    $allowedSettings->denyIps = ['203.0.113.10'];
    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password'], $allowedSettings, null, false);
    $response = $controller->actionAnswer();
    assertSame('/protected', $response->redirect, 'An allow rule must continue to override a deny rule.');
    assertSame(1, count($application->response->cookies->cookies), 'An explicitly allowed IP may still receive an access cookie.');
    assertSame(0, $logins->lockoutChecks, 'A static allow rule must not require the dynamic lockout query.');

    $disabledSettings = new Settings();
    $disabledSettings->enabled = false;
    [$controller] = createController(true, [], ['password' => 'correct-password'], $disabledSettings);
    assertThrows(NotFoundHttpException::class, $controller->actionAnswer(...), 'A disabled gate must retain its existing not-found behavior.');

    // Static caching mode
    // -------------------------------------------------------------------------

    $staticSettings = static function(string $forcedRedirect = ''): Settings {
        $settings = new Settings();
        $settings->enableStaticCaching = true;
        $settings->forcedRedirect = $forcedRedirect;

        return $settings;
    };

    ReturnUrlHelper::$forgetCalls = 0;
    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password', 'redirect' => 'signed:/members?page=2'], $staticSettings());
    $response = $controller->actionAnswer();
    $cookie = $application->response->cookies->cookies[0] ?? null;
    assertSame('knockKnockAccess', $cookie?->config['name'] ?? null, 'Static caching must set the configured access cookie.');
    assertSame('static:correct-password', $cookie?->config['value'] ?? null, 'Static caching must set the shared, password-derived access token.');
    assertSame(true, ($cookie?->config['expire'] ?? 0) > time(), 'The static access cookie must still expire after the cookie duration.');
    assertSame('/members?page=2', $response->redirect, 'Static caching must return the visitor to the signed, posted return URL.');
    assertSame(0, ReturnUrlHelper::$forgetCalls, 'Static caching must not touch the session-based return URL.');
    assertSame(true, $application->response->noCacheCalls > 0, 'A successful access attempt must never be cached.');

    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password', 'redirect' => '/members'], $staticSettings());
    assertThrows(BadRequestHttpException::class, $controller->actionAnswer(...), 'An unsigned return URL must be rejected.');
    assertSame(0, count($application->response->cookies->cookies), 'A rejected return URL must not leave an access cookie behind.');

    [$controller] = createController(true, [], ['password' => 'correct-password'], $staticSettings());
    assertSame('/', $controller->actionAnswer()->redirect, 'A missing return URL must fall back to the site.');

    foreach (['https://evil.test/members', '//evil.test/members'] as $offsiteUrl) {
        [$controller] = createController(true, [], ['password' => 'correct-password', 'redirect' => "signed:$offsiteUrl"], $staticSettings());
        assertSame('/', $controller->actionAnswer()->redirect, 'A return URL must stay on the site, even when signed.');
    }

    [$controller] = createController(true, [], ['password' => 'correct-password', 'redirect' => 'signed:/members'], $staticSettings('/welcome'));
    assertSame('/welcome', $controller->actionAnswer()->redirect, 'A forced redirect must still take precedence.');

    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'wrong', 'redirect' => 'signed:/members'], $staticSettings());
    $response = $controller->actionAnswer();
    assertSame('Invalid password', $response->data['errors']['password'] ?? null, 'Static caching must retain the invalid password response.');
    assertSame('/members', $response->data['redirect'] ?? null, 'An invalid attempt must keep the return URL for the next attempt.');
    assertSame(0, count($application->response->cookies->cookies), 'An invalid attempt must not set an access cookie.');
    assertSame(true, $application->response->noCacheCalls > 0, 'An access attempt response must never be cached.');

    AccessTokenHelper::$presentCookies = [];
    [$controller, $request, $plugin, $logins, $application] = createController(false, [], [], $staticSettings());
    $response = $controller->renderPrompt('/members');
    assertSame('/members', $response->data['redirect'] ?? null, 'The prompt must be given the return URL.');
    assertSame('cp', $response->templateMode, 'The prompt must render in the template mode its template was found in.');
    assertSame(0, $application->response->noCacheCalls, 'With static caching, the prompt must be cacheable for visitors without the access cookie.');

    AccessTokenHelper::$presentCookies = ['knockKnockAccess'];
    [$controller, $request, $plugin, $logins, $application] = createController(false, [], [], $staticSettings());
    $controller->renderPrompt('/members');
    assertSame(1, $application->response->noCacheCalls, 'The prompt must not be cached under a key that includes the access cookie.');
    AccessTokenHelper::$presentCookies = [];

    [$controller, $request, $plugin, $logins, $application] = createController(false, [], []);
    $controller->renderPrompt('/members');
    assertSame(1, $application->response->noCacheCalls, 'Without static caching, the prompt must never be cached.');

    echo "Knock Knock access-attempt request security fixture passed.\n";
}
