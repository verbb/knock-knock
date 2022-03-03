<?php
namespace verbb\knockknock\helpers;

class IpHelper
{
	// Public Methods
	// =========================================================================

	/**
	 * Check if IP exists in list of IP addresses and CIDR blocks
	 *
	 * @param mixed[] $cidrList
	 */
	public static function ipInCidrList(string $ip, array $cidrList): bool
	{
		$ipBits = self::_ipToBits($ip);

		if ($ipBits === false) {
            return false;
        }

		foreach($cidrList as $cidrnet) {
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
		$return = (bool) preg_match("#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?$#", $cidr);
		
        if ($return == true) {
			$parts = explode("/", $cidr);
			$ip = $parts[0];
			$netmask = '';

			if (isset($parts[1])) {
                $netmask = $parts[1];
            }

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
	
	// Private methods
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
}
