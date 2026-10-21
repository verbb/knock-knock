<?php

declare(strict_types=1);

// Requires installed Craft dependencies, optionally provided by KK_VENDOR_PATH.
$vendor = getenv('KK_VENDOR_PATH') ?: dirname(__DIR__, 2) . '/vendor';
$loader = require $vendor . '/autoload.php';
require_once $vendor . '/yiisoft/yii2/Yii.php';
require_once $vendor . '/craftcms/cms/src/Craft.php';
$loader->addPsr4('verbb\\knockknock\\', dirname(__DIR__, 2) . '/src');

class AliasFixtureApplication
{
    public mixed $request;
    public string $siteBase = 'https://example.test/';
    public int $siteId = 1;

    public function getRequest(): mixed
    {
        return $this->request;
    }

    public function getIsMultiSite(): bool
    {
        return true;
    }

    public function getConfig(): object
    {
        return new class {
            public function getGeneral(): \craft\config\GeneralConfig
            {
                return (new ReflectionClass(\craft\config\GeneralConfig::class))->newInstanceWithoutConstructor();
            }
        };
    }

    public function getSites(): \craft\services\Sites
    {
        return new class($this->siteId, $this->siteBase) extends \craft\services\Sites {
            public function __construct(private int $siteId, private string $siteBase)
            {
            }

            public function getCurrentSite(): \craft\models\Site
            {
                return new \craft\models\Site(['id' => $this->siteId, 'handle' => 'site' . $this->siteId, 'baseUrl' => $this->siteBase]);
            }
        };
    }
}

$app = new AliasFixtureApplication();
Craft::$app = $app;
$settings = new \verbb\knockknock\models\Settings();
$checks = 0;
$assertSame = static function(mixed $expected, mixed $actual, string $label) use (&$checks): void {
    if ($expected !== $actual) {
        throw new RuntimeException($label);
    }

    $checks++;
};
$setup = static function(string $base = '', string $siteBase = 'https://example.test/', ?string $explicitAlias = null) use ($app): void {
    $request = (new ReflectionClass(\craft\web\Request::class))->newInstanceWithoutConstructor();
    $request->generalConfig = $app->getConfig()->getGeneral();
    $request->setIsConsoleRequest(false);
    $request->setIsCpRequest(false);
    $request->setHostInfo('https://request.example.test');
    $request->setBaseUrl($base);
    $request->setScriptUrl($base . '/index.php');
    $request->isWebAliasSetDynamically = $explicitAlias === null;
    $app->request = $request;
    $app->siteBase = $siteBase;
    Craft::setAlias('@web', null);
    Craft::setAlias('@web', $explicitAlias ?? 'https://request.example.test' . $base);
};
$checkRules = static function(array $rules, array $expected, string $label) use ($settings, $assertSame): void {
    $aliases = Craft::$aliases;
    $settings->protectedUrls = $rules;
    $settings->unprotectedUrls = $rules;
    $assertSame($expected, $settings->getProtectedUrls(), $label . ': protected');
    $assertSame($expected, $settings->getUnprotectedUrls(), $label . ': unprotected');
    $assertSame($aliases, Craft::$aliases, $label . ': aliases restored');
};

$setup();
$checkRules(['@web/private', 'private', 'https://other.test/private', '//other.test/private'], ['https://example.test/private', 'private', 'https://other.test/private', '//other.test/private'], 'dynamic root and foreign URLs');
putenv('KK_ALIAS_ROOT=@web');
putenv('KK_ALIAS_PATH=private');
$checkRules(['$KK_ALIAS_ROOT', '$KK_ALIAS_ROOT/private', '${KK_ALIAS_ROOT}/${KK_ALIAS_PATH}', '@web/$KK_ALIAS_PATH'], ['https://example.test', 'https://example.test/private', 'https://example.test/private', 'https://example.test/private'], 'environment aliases');
Craft::setAlias('@web/assets', 'https://assets.test');
Craft::setAlias('@trusted', 'https://trusted.test');
$checkRules(['@web/assets/icon.svg', '@trusted/private', '@web/private'], ['https://assets.test/icon.svg', 'https://trusted.test/private', 'https://example.test/private'], 'explicit child and custom aliases');
$checkRules(['@web/private%20area', '@web/private/.*'], ['https://example.test/private%20area', 'https://example.test/private/.*'], 'encoded and regex paths');
$setup();
$app->request->setHostInfo('https://request.example.test' . chr(92));
Craft::setAlias('@web', $app->request->getHostInfo());
$checkRules(['@web/private'], ['https://example.test/private'], 'Yii root alias trimming');

$settings->protectedUrls = [['invalid']];
$aliases = Craft::$aliases;
$thrown = false;
try {
    $settings->getProtectedUrls();
} catch (TypeError) {
    $thrown = true;
}
$assertSame(true, $thrown, 'invalid configuration retains its error');
$assertSame($aliases, Craft::$aliases, 'aliases restored after error');

foreach (['en', 'fr'] as $index => $locale) {
    $setup('/craft', 'https://' . $locale . '.example.test/craft/' . $locale . '/');
    $app->siteId = $index + 1;
    $checkRules(['@web/' . $locale . '/private'], ['https://' . $locale . '.example.test/craft/' . $locale . '/private'], 'site ' . $locale);
}
$setup('/craft', 'https://example.test:8443/craft/en/');
$checkRules(['@web/en/private'], ['https://example.test:8443/craft/en/private'], 'site port');
$setup('/craft', '@web/en/');
$checkRules(['@web/en/private'], ['https://request.example.test/craft/en/private'], 'dynamic site base');
$setup('/craft', '/craft/en/');
$checkRules(['@web/en/private'], ['https://request.example.test/craft/en/private'], 'relative site base');
$setup('/craft', 'https://example.test/craft/', 'https://configured.test/custom');
$checkRules(['@web/private'], ['https://configured.test/custom/private'], 'explicit root');
$setup();
Craft::setAlias('@web', 'https://trusted.test/custom');
$checkRules(['@web/private'], ['https://trusted.test/custom/private'], 'late PHP override');
$app->request = new \craft\console\Request();
Craft::setAlias('@web', '/');
$checkRules(['@web/private'], ['/private'], 'console API');
putenv('KK_ALIAS_ROOT');
putenv('KK_ALIAS_PATH');
echo "URL alias security fixture passed ($checks assertions).\n";
