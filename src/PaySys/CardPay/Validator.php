<?php

namespace PaySys\CardPay;

use Nette\Utils\Validators;


class Validator
{

	public static function isAmount($s) : bool
	{
		return (is_string($s) && preg_match('/^\d{1,9}(\.\d{1,2})?$/', $s));
	}

	public static function isIp($s) : bool
	{
		return (filter_var($s, FILTER_VALIDATE_IP) !== FALSE);
	}

	public static function isVariableSymbol($s) : bool
	{
		return (is_string($s) && preg_match('/^\d{1,10}$/', $s));
	}

	public static function isCurrency($s) : bool
	{
		return (is_string($s) && in_array($s, [
			'978',
			'203',
			'840',
			'826',
			'348',
			'985',
			'756',
			'208',
		]));
	}

	public static function isLang($s) : bool
	{
		return (is_string($s) && in_array($s, [
			'sk',
			'en',
			'de',
			'hu',
			'cz',
			'es',
			'fr',
			'it',
			'pl',
		]));
	}

	public static function isMid($s) : bool
	{
		return (is_string($s) && preg_match('/^\d{3,4}$/', $s));
	}

	public static function isKey($s) : bool
	{
		return (is_string($s) && preg_match('/^\w{64}$/', $s));
	}

	public static function isRurl($s) : bool
	{
		return (is_string($s) && Validators::isUri($s));
	}

	public static function isName($s) : bool
	{
		return (is_string($s) && preg_match('/^[a-zA-Z0-9 \.\-_@]{1,30}$/', $s));
	}

	public static function isTimestamp($s) : bool
	{
		return (
			is_string($s) &&
			preg_match('/^\d{14}$/', $s) &&
			\DateTime::createFromFormat('dmYHis', $s, new \DateTimeZone('UTC')) instanceof \DateTime &&
			$s === \DateTime::createFromFormat('dmYHis', $s, new \DateTimeZone('UTC'))->format('dmYHis')
		);
	}

}
