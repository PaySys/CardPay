<?php

namespace PaySys\CardPay\Security;

use Nette;
use Nette\Utils\Callback;
use Nette\Utils\Strings;
use PaySys\CardPay\Configuration;


final class Response
{
	use Nette\SmartObject;

	const PUBLIC_KEYS = "https://moja.tatrabanka.sk/e-commerce/ecdsa_keys.txt";

	/** @var string[]  parameters required in every response and their expected format */
	private const REQUIRED_PARAMETERS = [
		'AMT' => '~^\d{1,9}(\.\d{1,2})?$~',
		'CURR' => '~^\d{3}$~',
		'VS' => '~^\d{1,10}$~',
		'RES' => '~^[A-Z]{2,10}$~',
		'TIMESTAMP' => '~^\d{14}$~',
		'HMAC' => '~^[0-9a-f]{64}$~i',
		'ECDSA_KEY' => '~^\d{1,9}$~',
		'ECDSA' => '~^([0-9a-f]{2}){1,256}$~i',
	];

	/** @var string[]  parameters which may be absent, but must match the format when present */
	private const OPTIONAL_PARAMETERS = [
		'TXN' => '~^[a-zA-Z0-9]{0,20}$~',
		'AC' => '~^[a-zA-Z0-9]{0,20}$~',
		'TRES' => '~^[A-Z]{0,10}$~',
		'CID' => '~^[a-zA-Z0-9_-]{0,40}$~',
		'CC' => '~^[0-9X*]{0,25}$~i',
		'RC' => '~^[a-zA-Z0-9]{0,10}$~',
		// the bank omits TID on some unsuccessful payments
		'TID' => '~^[a-zA-Z0-9]{0,20}$~',
	];

	/** @var callable[]  function (array $parameters); Occurs on response from bank */
	public $onResponse;

	/** @var callable[]  function (array $parameters); Occurs on success payment response from bank */
	public $onSuccess;

	/** @var callable[]  function (array $parameters); Occurs on fail payment response from bank */
	public $onFail;

	/** @var callable[]  function (array $parameters, \PaySys\PaySys\Exception $e); Occurs on damaged response from bank */
	public $onError;

	/** @var Configuration */
	protected $config;


	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}

	public function paid(array $parameters) : bool
	{
		try {
			$this->checkParameters($parameters);

			if (!hash_equals($this->getHmac($parameters), $parameters['HMAC']))
				throw new \PaySys\PaySys\SignatureException('HMAC sign is not valid.');


			if (!$this->verified($parameters))
				throw new \PaySys\PaySys\SignatureException('ECDSA sign is not valid.');

			$this->onResponse($parameters);

			if ($parameters['RES'] === 'OK') {
				$this->onSuccess($parameters);
				return TRUE;
			} else {
				$this->onFail($parameters);
				return FALSE;
			}
		} catch (\PaySys\PaySys\Exception $e) {
			$this->onError($parameters, $e);
			throw $e;
		}
	}

	public function getSignString(array $parameters) : string
	{
		return $parameters['AMT']
			. $parameters['CURR']
			. $parameters['VS']
			. ($parameters['TXN'] ?? '')
			. $parameters['RES']
			. (($parameters['RES'] === 'OK') ? $parameters['AC'] : '')
			. ($parameters['TRES'] ?? '')
			. ($parameters['CID'] ?? '')
			. ($parameters['CC'] ?? '')
			. ($parameters['RC'] ?? '')
			. ($parameters['TID'] ?? '')
			. $parameters['TIMESTAMP'];
	}

	public function getHmac(array $parameters) : string
	{
		return hash_hmac("sha256", $this->getSignString($parameters), $this->config->getKey());
	}


	public function verified(array $parameters) : bool
	{
		$verified = openssl_verify($this->getSignString($parameters) . $parameters['HMAC'], pack("H*", $parameters['ECDSA']), $this->getPublicKey((int) $parameters['ECDSA_KEY']), "sha256");

		if ($verified === -1) {
			throw new \PaySys\PaySys\SignatureException(sprintf("Error while verify bank response: %s", openssl_error_string()));
		} else {
			return (bool) $verified;
		}
	}

	public function getPublicKey(int $id) : string
	{
		foreach (preg_split('~(?:\r?\n){2,}~', $this->fetchPublicKeys()) as $source) {
			if (!preg_match('~^KEY_ID:[ \t]*(\d+)[ \t\r]*$~m', $source, $tmp))
				continue;

			if ((int) $tmp[1] !== $id)
				continue;

			if (!preg_match('~^STATUS:[ \t]*VALID[ \t\r]*$~m', $source))
				throw new \PaySys\PaySys\ServerException(sprintf("Key '%d' is not valid.", $id));

			if (!preg_match('~-----BEGIN PUBLIC KEY-----.*-----END PUBLIC KEY-----~sU', $source, $key))
				throw new \PaySys\PaySys\ServerException(sprintf("Key '%d' does not contain public key.", $id));

			return Strings::trim($key[0]);
		}

		throw new \PaySys\PaySys\ServerException(sprintf("Key '%d' was not found.", $id));
	}

	private function fetchPublicKeys() : string
	{
		$content = Callback::invokeSafe('file_get_contents', [self::PUBLIC_KEYS], function ($message) {
			throw new \PaySys\PaySys\ServerException(sprintf("Unable to download public keys from '%s': %s", self::PUBLIC_KEYS, $message));
		});

		if (!is_string($content) || $content === '')
			throw new \PaySys\PaySys\ServerException(sprintf("Public keys from '%s' are empty.", self::PUBLIC_KEYS));

		return $content;
	}

	private function checkParameters(array & $parameters)
	{
		foreach (self::REQUIRED_PARAMETERS as $key => $pattern) {
			if (!isset($parameters[$key]))
				throw new \PaySys\PaySys\ServerException(sprintf("Missing parameter '%s'.", $key));

			$parameters[$key] = $this->checkParameter($key, $parameters[$key], $pattern);
		}

		foreach (self::OPTIONAL_PARAMETERS as $key => $pattern) {
			if (isset($parameters[$key]))
				$parameters[$key] = $this->checkParameter($key, $parameters[$key], $pattern);
		}

		if ($parameters['RES'] === 'OK' && !isset($parameters['AC']))
			throw new \PaySys\PaySys\ServerException("Missing parameter 'AC'.");
	}

	private function checkParameter(string $key, $value, string $pattern) : string
	{
		if (!is_string($value))
			throw new \PaySys\PaySys\ServerException(sprintf("Parameter '%s' must be a string, %s given.", $key, gettype($value)));

		$value = Strings::trim($value);

		if (!preg_match($pattern, $value))
			throw new \PaySys\PaySys\ServerException(sprintf("Parameter '%s' has invalid format.", $key));

		return $value;
	}
}
