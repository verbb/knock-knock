<?php
namespace verbb\knockknock\helpers;

use Craft;
use craft\helpers\UrlHelper;

class ReturnUrlHelper
{
    // Constants
    // =========================================================================

    private const SESSION_KEY_PREFIX = 'knockknock.returnUrl.';


    // Public Methods
    // =========================================================================

    public static function store(string $url): void
    {
        Craft::$app->getSession()->set(self::_sessionKey(), self::_normalize($url));
    }

    public static function get(): string
    {
        $url = Craft::$app->getSession()->get(self::_sessionKey());

        return is_string($url) ? self::_normalize($url) : self::_fallbackUrl();
    }

    public static function forget(): void
    {
        Craft::$app->getSession()->remove(self::_sessionKey());
    }


    // Private Methods
    // =========================================================================

    private static function _sessionKey(): string
    {
        return self::SESSION_KEY_PREFIX . Craft::$app->getSites()->getCurrentSite()->id;
    }

    private static function _normalize(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $decodedPath = is_string($path) ? rawurldecode($path) : '';

        if (
            !UrlHelper::isRootRelativeUrl($url) ||
            !UrlHelper::isRootRelativeUrl($decodedPath) ||
            preg_match('/[\\x00-\\x1F\\x7F\\\\]/', $url) ||
            preg_match('/[\\x00-\\x1F\\x7F\\\\]/', $decodedPath)
        ) {
            return self::_fallbackUrl();
        }

        return $url;
    }

    private static function _fallbackUrl(): string
    {
        $path = parse_url(UrlHelper::siteUrl(), PHP_URL_PATH);

        return is_string($path) && UrlHelper::isRootRelativeUrl($path) ? $path : '/';
    }
}
