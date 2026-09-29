<?php
namespace verbb\knockknock\helpers;

use verbb\knockknock\KnockKnock;

use Craft;
use craft\web\Request;

use yii\validators\IpValidator;

class IpHelper
{
    // Public Methods
    // =========================================================================

    public static function getUserIp(): string
    {
        $request = Craft::$app->getRequest();

        /* @var Settings $settings */
        $settings = KnockKnock::$plugin->getSettings();

        if ($settings->useRemoteIp) {
            return $request->getRemoteIP() ?? '';
        }

        return self::_getTrustedClientIp($request);
    }

    /**
     * Check if IP exists in list of IP addresses and CIDR blocks
     *
     * @param string $ip
     * @param array $cidrList
     * @return bool
     */
    public static function ipInCidrList(string $ip, array $cidrList): bool
    {
        $ipBits = self::_ipToBits($ip);

        if ($ipBits === false) {
            return false;
        }

        foreach ($cidrList as $cidrnet) {
            $maskbits = false;
            $ipNetBits = $ipBits;

            if (!str_contains($cidrnet, '/')) {
                $net = $cidrnet;
            } else {
                [$net, $maskbits] = explode('/', $cidrnet);
            }

            $netBits = self::_ipToBits($net);

            if (!empty($maskbits)) {
                $ipNetBits = substr($ipNetBits, 0, $maskbits);
                $netBits = substr($netBits, 0, $maskbits);
            }

            if ($ipNetBits === $netBits) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate IP or CIDR block
     */
    public static function validIpOrCidr(string $cidr): bool
    {
        $return = (bool)preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$#", $cidr);

        if ($return == true) {
            $parts = explode("/", $cidr);
            $ip = $parts[0];
            $netmask = $parts[1] ?? '';

            $octets = explode(".", $ip);

            foreach ($octets as $octet) {
                if ($octet > 255) {
                    $return = false;
                }
            }

            if (($netmask != "") && ($netmask > 32)) {
                $return = false;
            }
        } else if (preg_match("#^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}\d){0,1}\d)\.){3,3}(25[0-5]|(2[0-4]|1{0,1}\d){0,1}\d)|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}\d){0,1}\d)\.){3,3}(25[0-5]|(2[0-4]|1{0,1}\d){0,1}\d))(\/\d{1,2})?$#", $cidr)) {
            // Seems invalid, check if valid ipv6
            $return = true;
        }

        return $return;
    }

    // Private Methods
    // =========================================================================
    /**
     * Converts inet_pton output to string with bits
     */
    private static function _ipToBits(string $ip): bool|string
    {
        $inet = @inet_pton($ip);

        if ($inet === false) {
            return false;
        }

        $unpacked = str_contains($ip, ":") ? unpack('a16', $inet) : unpack('a4', $inet);
        $unpacked = str_split($unpacked[1]);
        $binaryip = '';

        foreach ($unpacked as $char) {
            $binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        return $binaryip;
    }

    /**
     * Forwarded addresses are accepted only from a concrete proxy range that also permits the header.
     */
    private static function _getTrustedClientIp(Request $request): string
    {
        $remoteIp = $request->getRemoteIP();

        if (!$remoteIp) {
            return '';
        }

        $trustedHeaders = self::_trustedHeadersForPeer($request, $remoteIp);

        foreach ($request->ipHeaders as $header) {
            if (!in_array(mb_strtolower($header), $trustedHeaders, true)) {
                continue;
            }

            $value = $request->getHeaders()->get($header);

            if (is_string($value) && ($clientIp = self::_clientIpFromHeader($value, $request->trustedHosts))) {
                return $clientIp;
            }
        }

        return $remoteIp;
    }

    private static function _trustedHeadersForPeer(Request $request, string $remoteIp): array
    {
        $validator = new IpValidator();

        foreach ($request->trustedHosts as $cidr => $headers) {
            if (!is_array($headers)) {
                $cidr = $headers;
                $headers = $request->secureHeaders;
            }

            // Universal ranges, including Craft's default `any`, are not an explicit proxy boundary.
            if (!self::_isConcreteTrustedRange($cidr)) {
                continue;
            }

            $validator->setRanges($cidr);

            if ($validator->validate($remoteIp)) {
                return array_map('mb_strtolower', $headers);
            }
        }

        return [];
    }

    private static function _clientIpFromHeader(string $value, array $trustedHosts): ?string
    {
        $ips = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        if (!$ips) {
            return null;
        }

        $trustedRanges = [];

        foreach ($trustedHosts as $cidr => $headers) {
            $range = is_array($headers) ? $cidr : $headers;

            if (self::_isConcreteTrustedRange($range)) {
                $trustedRanges[] = $range;
            }
        }

        $validator = new IpValidator();
        $clientIp = null;

        foreach (array_reverse($ips) as $ip) {
            $ip = trim($ip);

            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                break;
            }

            $clientIp = $ip;
            $validator->setRanges($trustedRanges);

            if (!$trustedRanges || !$validator->validate($ip)) {
                break;
            }
        }

        return $clientIp;
    }

    private static function _isConcreteTrustedRange(mixed $range): bool
    {
        return is_string($range) && !in_array(mb_strtolower(trim($range)), ['*', 'any', 'ipv4', 'ipv6', '0.0.0.0/0', '::/0'], true);
    }
}
