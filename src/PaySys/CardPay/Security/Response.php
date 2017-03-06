<?php

namespace PaySys\CardPay\Security;

use Nette;
use Nette\Utils\Strings;
use PaySys\CardPay\Configuration;


final class Response
{
	use Nette\SmartObject;

	const PUBLIC_KEYS = "https://moja.tatrabanka.sk/e-commerce/ecdsa_keys.txt";

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

			if ($parameters['HMAC'] !== $this->getHmac($parameters))
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
			. @$parameters['TXN']
			. $parameters['RES']
			. (($parameters['RES'] === 'OK') ? $parameters['AC'] : '')
			. @$parameters['TRES']
			. @$parameters['CID']
			. @$parameters['RC']
			. $parameters['TID']
			. $parameters['TIMESTAMP'];
	}

	public function getHmac(array $parameters) : string
	{
		return hash_hmac("sha256", $this->getSignString($parameters), $this->config->getKey());
	}


	public function verified(array $parameters) : bool
	{
		$verified = openssl_verify($this->getSignString($parameters) . $parameters['HMAC'], pack("H*", $parameters['ECDSA']), $this->getPublicKey($parameters['ECDSA_KEY']), "sha256");

		if ($verified === -1) {
			throw new \PaySys\PaySys\SignatureException(sprintf("Error while verify bank response: %s", openssl_error_string()));
		} else {
			return (bool) $verified;
		}
	}

	public function getPublicKey(int $id) : string
	{
		foreach (explode("\r\n\r\n", file_get_contents(self::PUBLIC_KEYS)) as $source) {
			preg_match('/KEY_ID: (\d+)/', $source, $tmp);
			$key_id = (int) $tmp[1];

			if ($key_id === $id) {
				if ((bool) Strings::match($source, '~VALID+~')) {

					preg_match_all("/-----BEGIN PUBLIC KEY-----(.*)-----END PUBLIC KEY-----/msU", $source, $key);
					return Strings::trim($key[0][0]);

				} else {
					throw new \PaySys\PaySys\ServerException(sprintf("Key '%d' was revoked.", $id));
				}
			}
		}
	}

	private function checkParameters(array & $parameters)
	{
		foreach (['AMT', 'CURR', 'VS', 'RES', 'TID', 'TIMESTAMP', 'HMAC', 'ECDSA_KEY', 'ECDSA'] as $key) {
			if (isset($parameters[$key])) {
				$parameters[$key] = Strings::trim($parameters[$key]);
			} else {
				throw new \PaySys\PaySys\ServerException(sprintf("Missing parameter '%s'.", $key));
			}
		}
	}
}
