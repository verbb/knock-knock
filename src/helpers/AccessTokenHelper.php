<?php
namespace verbb\knockknock\helpers;

use Craft;

class AccessTokenHelper
{
    // Constants
    // =========================================================================

    private const COOKIE_NAME_PREFIX = 'siteAccessToken-';
    private const TOKEN_PURPOSE = 'knock-knock.site-access';
    private const TOKEN_VERSION = 'v1';
    private const STATIC_TOKEN_PURPOSE = 'knock-knock.static-site-access';
    private const STATIC_TOKEN_VERSION = 's1';


    // Static Methods
    // =========================================================================

    public static function getCookieName(): string
    {
        return self::COOKIE_NAME_PREFIX . hash('sha256', self::_getSiteUid());
    }

    public static function create(string $password, int $duration): string
    {
        return self::_buildToken($password, time(), $duration);
    }

    public static function validate(string $password, int $duration): bool
    {
        $cookie = Craft::$app->getRequest()->getCookies()->get(self::getCookieName());

        if (!$cookie || !is_string($cookie->value)) {
            return false;
        }

        $parts = explode('.', $cookie->value);

        if (
            count($parts) !== 4 ||
            $parts[0] !== self::TOKEN_VERSION ||
            !ctype_digit($parts[1]) ||
            !ctype_digit($parts[2])
        ) {
            return false;
        }

        $issuedAt = (int)$parts[1];
        $issuedDuration = (int)$parts[2];
        $currentTime = time();

        if (
            $issuedAt > $currentTime ||
            $issuedDuration !== $duration ||
            ($duration > 0 && $currentTime - $issuedAt >= $duration)
        ) {
            return false;
        }

        return Craft::$app->getSecurity()->compareString(
            self::_buildToken($password, $issuedAt, $issuedDuration),
            $cookie->value,
        );
    }

    /**
     * Returns the access token used when static caching is enabled.
     *
     * Every visitor who enters the password gets the same token, so that a static cache can share responses
     * between them, by including the cookie in its cache key. The token is derived from the password and
     * Craft’s security key, so it can’t be guessed without the password.
     */
    public static function createStatic(string $password): string
    {
        $payload = implode("\0", [
            self::STATIC_TOKEN_PURPOSE,
            self::STATIC_TOKEN_VERSION,
            self::_getSiteUid(),
            $password,
        ]);
        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;

        return implode('.', [self::STATIC_TOKEN_VERSION, hash_hmac('sha256', $payload, $securityKey)]);
    }

    public static function validateStatic(string $password, string $cookieName): bool
    {
        $cookie = Craft::$app->getRequest()->getCookies()->get($cookieName);

        if (!$cookie || !is_string($cookie->value)) {
            return false;
        }

        return Craft::$app->getSecurity()->compareString(self::createStatic($password), $cookie->value);
    }

    /**
     * Returns whether the request includes a cookie by the given name, whether or not its value is valid.
     */
    public static function hasCookie(string $cookieName): bool
    {
        return isset($_COOKIE[$cookieName]);
    }

    private static function _buildToken(string $password, int $issuedAt, int $duration): string
    {
        $payload = implode("\0", [
            self::TOKEN_PURPOSE,
            self::TOKEN_VERSION,
            self::_getSiteUid(),
            (string)$issuedAt,
            (string)$duration,
            $password,
        ]);
        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;
        $signature = hash_hmac('sha256', $payload, $securityKey);

        return implode('.', [self::TOKEN_VERSION, $issuedAt, $duration, $signature]);
    }

    private static function _getSiteUid(): string
    {
        return Craft::$app->getSites()->getCurrentSite()->uid;
    }
}
