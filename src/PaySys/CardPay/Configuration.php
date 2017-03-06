<?php

namespace PaySys\CardPay;

use Nette;
use Nette\Application\LinkGenerator;
use Nette\Utils\Strings;
use PaySys\PaySys\IConfiguration;


final class Configuration implements IConfiguration
{

	const TEST = "test";
	const PRODUCTION = "production";


	/** @var string */
	private $mid;

	/** @var string */
	private $rurl;

	/** @var string */
	private $lang = 'sk';

	/** @var string */
	private $ipc;

	/** @var string */
	private $key;

	/** @var string */
	private $rem = '';

	/** @var LinkGenerator */
	private $linkGenerator;

	/** @var string */
	private $mode = self::PRODUCTION;

	/** @var string */
	private $buttonTemplate;


	public function __construct(string $mid, $rurl, string $key, LinkGenerator $linkGenerator = NULL)
	{
		$this->linkGenerator = $linkGenerator;
		$this->setMid($mid);
		$this->setRurl($rurl);
		$this->setKey($key);
		$this->setIpc();
		$this->setButtonTemplate(__DIR__ . '/template/button.latte');
	}

	public function setMid(string $mid) : Configuration
	{
		if (!Validator::isMid($mid))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Parameter MID must have 3 or 4 characters. '%s' is invalid.", $mid));

		$this->mid = $mid;
		return $this;
	}

	public function getMid() : string
	{
		return $this->mid;
	}

	public function setRurl($originalRurl) : Configuration
	{
		$supportedTypes = ['string', 'array'];
		if (!in_array(gettype($originalRurl), $supportedTypes))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("RURL type of '%s' is invalid. Must be %s.", gettype($originalRurl), implode(' or ', $supportedTypes)));

		$rurl = $originalRurl;
		if ($this->linkGenerator instanceof LinkGenerator) {
			try {
				if (is_string($originalRurl)) {
					$rurl = $this->linkGenerator->link($originalRurl);
				} elseif (is_array($originalRurl)) {
					$rurl = $this->linkGenerator->link($originalRurl['dest'], @$originalRurl['params']);
				}
			} catch (Nette\Application\UI\InvalidLinkException $e) {}
		}

		if (!Validator::isRurl($rurl))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("RURL '%s' is invalid. Must be valid URL by RFC 1738.", $originalRurl));

		$this->rurl = $rurl;
		return $this;
	}

	public function getRurl() : string
	{
		return $this->rurl;
	}

	public function setKey(string $originalKey) : Configuration
	{
		$key = $this->getNormalizedKey($originalKey);
		if (!Validator::isKey($key))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Key '%s' is invalid. Must have 64 byte standard string or 128 byte in hexadecimal format.", $originalKey));

		$this->key = $key;
		return $this;
	}

	public function getKey() : string
	{
		return $this->key;
	}

	public function setIpc(string $ipc = NULL) : Configuration
	{
		if ($ipc === NULL) {
			$ipc = $this->getIpAddress();
		}

		if (!Validator::isIp($ipc))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("IP '%s' is not valid.", $ipc));

		$this->ipc = $ipc;
		return $this;
	}

	public function getIpc() : string
	{
		return $this->ipc;
	}

	public function setLang(string $originalLang) : Configuration
	{
		$lang = strtolower($originalLang);
		if (!Validator::isLang($lang))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Lang '%s' is not supported.", $originalLang));

		$this->lang = $lang;
		return $this;
	}

	public function getLang() : string
	{
		return $this->lang;
	}

	public function setMode(string $mode) : Configuration
	{
		if (!in_array($mode, [self::TEST, self::PRODUCTION]))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Mode '%s' is not valid. Use please '%s' or '%s'.", $mode, self::TEST, self::PRODUCTION));

		$this->mode = $mode;
		return $this;
	}

	public function getMode() : string
	{
		return $this->mode;
	}

	public function setRem(string $rem) : Configuration
	{
		if (!Nette\Utils\Validators::isEmail($rem))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("'%s' is not valid e-mail.", $rem));

		$this->rem = $rem;
		return $this;
	}

	public function getRem() : string
	{
		return $this->rem;
	}

	public function setButtonTemplate(string $path) : Configuration
	{
		if (!file_exists($path))
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Template file '%s' not exists.", $path));

		$this->buttonTemplate = $path;
		return $this;
	}

	public function getButtonTemplate() : string
	{
		return $this->buttonTemplate;
	}

	private function getNormalizedKey(string $originalKey) : string
	{
		if (strlen($originalKey) === 128 OR strlen($originalKey) === 191) {
			if (strlen($originalKey) === 191) {
				$key = str_replace(':', '', $originalKey);
			} else {
				$key = $originalKey;
			}

			if (strlen($key) === 128)
				return pack("H*", $key);
		}
		return $originalKey;
	}

	private function getIpAddress() : string
	{
		foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
			if (array_key_exists($key, $_SERVER) === TRUE) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = Strings::trim($ip);

					if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE) {
						return $ip;
					}
				}
			}
		}
		return "0.0.0.0";
	}
}
