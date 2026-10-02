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
