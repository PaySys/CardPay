<?php

namespace PaySys\CardPay\Security;

use Nette\Http\Url;
use PaySys\CardPay\Configuration;
use PaySys\CardPay\Payment;


final class Request
{
	const SERVER_TEST = "https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/example";
	const SERVER_PRODUCTION = "https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay";

	/** @var Configuration */
	protected $config;


	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}

	public function getUrl(Payment $payment) : Url
	{
		$s = $this->getSign($payment);
		$url = new Url(constant("self::SERVER_" . strtoupper($this->config->getMode())));
		$url->appendQuery('MID=' . $this->config->getMid())
			->appendQuery('AMT=' . $payment->getAmount())
			->appendQuery('CURR=' . $payment->getCurrency())
			->appendQuery('VS=' . $payment->getVariableSymbol())
			->appendQuery('RURL=' . $this->config->getRurl())
			->appendQuery('IPC=' . $this->config->getIpc())
			->appendQuery('NAME=' . $payment->getName());
		if ($payment->getTpay()) {
			$url->appendQuery('TPAY=Y');
		}
		$url->appendQuery('TIMESTAMP=' . $payment->getTimestamp())
			->appendQuery('HMAC=' . $this->getSign($payment));
		return $url;
	}

	public function getSign(Payment $payment) : string
	{
		return hash_hmac("sha256", $this->getSignString($payment), $this->config->getKey());
	}

	public function getSignString(Payment $payment) : string
	{
		$s = $this->config->getMid()
			. $payment->getAmount()
			. $payment->getCurrency()
			. $payment->getVariableSymbol()
			. $this->config->getRurl()
			. $this->config->getIpc()
			. $payment->getName();
		if ($payment->getTpay()) {
			$s .= 'Y';
		}
		$s .= $this->config->getRem()
			. $payment->getTimestamp();
		return $s;
	}

}
