<?php
namespace verbb\knockknock\helpers;

class IpHelper
{
	// Public Methods
    // =========================================================================
	
	/**
	 * Check if IP exists in list of IP addresses and CIDR blocks
	 *
	 * @param string $ip
	 * @param array $cidrList
	 */
	public static function ipInCidrList($ip, $cidrList)
	{
		$ip_bits = self::_ipToBits($ip);

		if($ip_bits === false)
			return false;

		foreach($cidrList as $cidrnet){
			$maskbits = false;
			$ip_net_bits = $ip_bits;

			if(strpos($cidrnet, '/') === false){
				$net = $cidrnet;
			}else{
				list($net,$maskbits)=explode('/',$cidrnet);
			}

			$net_bits = self::_ipToBits($net);

			if(!empty($maskbits)){
				$ip_net_bits	= substr($ip_net_bits, 0, $maskbits);
				$net_bits		= substr($net_bits, 0, $maskbits);
			}

			if($ip_net_bits === $net_bits)
				return true;
		}

		return false;
	}

	/**
	 * Validate IP or CIDR block
	 *
	 * @param string $cidr
	 */
	public static function validIpOrCidr($cidr)
	{
		if (!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?$/", $cidr))
		{
			$return = false;
		} else
		{
			$return = true;
		}
		if ($return == true)
		{
			$parts = explode("/", $cidr);
			$ip = $parts[0];
			$netmask = '';
			if(isset($parts[1]))
				$netmask = $parts[1];
			$octets = explode(".", $ip);
			foreach ($octets as $octet)
			{
				if ($octet > 255)
				{
					$return = false;
				}
			}
			if (($netmask != "") && ($netmask > 32))
			{
				$return = false;
			}
		} elseif (preg_match("/^(([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))(\/[0-9]{1,2})?$/", $cidr))
		{ /* Seems invalid, check if valid ipv6 */
			$return = true;
		}		
		return $return;
	}
	
	// Private methods
    // =========================================================================
	
	/**
	 * Converts inet_pton output to string with bits
	 *
	 * @param string $ip
	 */
	private static function _ipToBits($ip)
	{
		$inet = @inet_pton($ip);
		if($inet === false)
			return false;

		if(strpos($ip, ":") === false){
			$unpacked = unpack('a4', $inet);
		}else{
			$unpacked = unpack('a16', $inet);
		}

		$unpacked = str_split($unpacked[1]);

		$binaryip = '';
		foreach ($unpacked as $char) {
			$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
		}
		return $binaryip;
	}
	
}
