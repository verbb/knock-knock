<?php

declare(strict_types=1);

namespace yii\web {
    class Response
    {
        public array $data = [];
        public ?string $redirect = null;
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

        public function renderTemplate(string $template, array $data): Response
        {
            $response = new Response();
            $response->data = $data;

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
        public ?string $password = null;
    }

    class Settings
    {
        public bool $checkInvalidLogins = true;
        public bool $enabled = true;
        public string $password = 'correct-password';

        public function getAllowIps(): array
        {
            return [];
        }

        public function getCookieDuration(): int
        {
            return 3600;
        }

        public function getDefaultTemplate(): string
        {
            return 'ask';
        }

        public function getEnabled(): bool
        {
            return $this->enabled;
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
    }

    class IpHelper
    {
        public static function getUserIp(): string
        {
            return '203.0.113.10';
        }

        public static function ipInCidrList(string $ipAddress, array $cidrs): bool
        {
            return false;
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

    function createController(bool $isPost, array $query, array $body, ?Settings $settings = null, ?FakeLogins $logins = null): array
    {
        $settings ??= new Settings();
        $logins ??= new FakeLogins();
        $request = new FakeRequest($isPost, $query, $body);
        $plugin = new FakePlugin($settings, $logins);
        $application = new FakeApplication();
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
    assertSame('wrong', $logins->saved[0]->password ?? null, 'Only the invalid body password may be recorded.');
    assertSame(0, count($application->response->cookies->cookies), 'An invalid body password must not set an access cookie.');

    [$controller, $request, $plugin, $logins] = createController(true, ['password' => 'correct-password'], []);
    $controller->actionAnswer();
    assertSame('', $logins->saved[0]->password ?? null, 'A missing body password must retain the existing empty-password failure behavior.');

    [$controller, $request, $plugin, $logins] = createController(true, [], ['password' => ['wrong']]);
    $controller->actionAnswer();
    assertSame('', $logins->saved[0]->password ?? null, 'A non-string body password must retain the existing empty-password failure behavior.');

    $lockedLogins = new FakeLogins();
    $lockedLogins->locked = true;
    [$controller, $request, $plugin, $logins, $application] = createController(true, [], ['password' => 'correct-password'], null, $lockedLogins);
    $response = $controller->actionAnswer();
    assertSame('Too many invalid attempts', $response->data['errors']['password'] ?? null, 'Existing lockouts must retain their current response.');
    assertSame(0, count($logins->saved), 'A locked request must not add another failed attempt.');
    assertSame(0, count($application->response->cookies->cookies), 'A locked request must not set an access cookie.');

    $disabledSettings = new Settings();
    $disabledSettings->enabled = false;
    [$controller] = createController(true, [], ['password' => 'correct-password'], $disabledSettings);
    assertThrows(NotFoundHttpException::class, $controller->actionAnswer(...), 'A disabled gate must retain its existing not-found behavior.');

    echo "Knock Knock access-attempt request security fixture passed.\n";
}
