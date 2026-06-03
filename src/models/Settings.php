<?php
namespace verbb\knockknock\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;
use craft\helpers\UrlHelper;

use yii\base\Exception;

use Closure;
use Throwable;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public bool|Closure $enabled = false;
    public string $password = '';
    public string $loginPath = '';
    public string $template = '';
    public string $forcedRedirect = '';
    public array $siteSettings = [];
    public bool $enableCpProtection = false;

    public bool $checkInvalidLogins = false;
    public string $invalidLoginWindowDuration = '3600';
    public int $maxInvalidLogins = 10;
    public array|string|null $allowIps = [];
    public array|string|null $denyIps = [];
    public bool $useRemoteIp = false;
    
    public array|string|null $protectedUrls = [];
    public array|string|null $unprotectedUrls = [];


    // Public Methods
    // =========================================================================

    public function getEnabled(): bool
    {
        $enabled = $this->_getSettingValue('enabled');

        // Allow the enabled setting to be a callback function
        if (is_callable($enabled)) {
            return $enabled();
        }

        return $enabled ?? false;
    }

    public function getDefaultTemplate(): string
    {
        if (version_compare(Craft::$app->getInfo()->version, '5.6.0', '>=')) {
            return 'knock-knock/ask-craft-5-6';
        }

        return 'knock-knock/ask';
    }

    public function getTemplate(): string
    {
        return $this->_getSettingValue('template') ?? '';
    }

    public function getPassword(): string
    {
        return App::parseEnv($this->_getSettingValue('password')) ?? '';
    }

    public function getLoginPath(): string
    {
        return $this->_getSettingValue('loginPath') ?? 'knock-knock/who-is-there';
    }

    public function getSettingAsMultiline(string $setting): string
    {
        if (is_array($this->$setting)) {
            return implode(PHP_EOL, $this->$setting);
        }

        if (is_string((string)$this->$setting)) {
            return (string)$this->$setting;
        }

        return '';
    }

    public function getAllowIps(): array
    {
        return $this->_normalizeList($this->allowIps);
    }

    public function getDenyIps(): array
    {
        return $this->_normalizeList($this->denyIps);
    }

    public function getProtectedUrls(): array
    {
        return $this->_normalizeUrls($this->protectedUrls);
    }

    public function getUnprotectedUrls(): array
    {
        return $this->_normalizeUrls($this->unprotectedUrls);
    }


    // Private Methods
    // =========================================================================

    private function _normalizeList(array|string|null $value): array
    {
        if (is_array($value)) {
            // Handle legacy format: array with a single multi-line string
            if (count($value) === 1 && is_string($value[0]) && str_contains($value[0], "\n")) {
                return array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value[0])));
            }
            
            return array_filter($value);
        }

        if (is_string($value)) {
            return array_filter(array_map('trim', explode(PHP_EOL, $value)));
        }

        return [];
    }

    private function _normalizeUrls(array|string|null $value): array
    {
        $items = $this->_normalizeList($value);

        return array_map(fn($item) => UrlHelper::siteUrl(App::parseEnv($item)), $items);
    }

    private function _getSettingValue($value)
    {
        try {
            $currentSite = Craft::$app->getSites()->getCurrentSite();
            $siteSettings = $this->siteSettings[$currentSite->handle] ?? [];

            // Allow global override
            if ($this->$value) {
                return $this->$value;
            }

            if (Craft::$app->getIsMultiSite() && $siteSettings && isset($siteSettings[$value])) {
                return $siteSettings[$value];
            }
        } catch (Throwable $e) {
            // In the case where a primary site might not be available yet
            // https://github.com/verbb/knock-knock/issues/75
        }

        return null;
    }
}
