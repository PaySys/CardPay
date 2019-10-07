<?php

namespace PaySys\CardPay;

use PaySys\PaySys\IPayment;


class Payment implements IPayment
{
	/** @var string */
	protected $amt;

	/** @var string */
	protected $vs;

	/** @var string */
	protected $curr = '978'; // EUR

	/** @var string */
	protected $name;

	/** @var bool */
	protected $tpay = false;

	/** @var string */
	protected $timestamp;


	public function __construct(string $amt, string $vs, string $name)
	{
		$this->setAmount($amt);
		$this->setVariableSymbol($vs);
		$this->setName($name);
		$this->timestamp = gmdate('dmYHis');
	}

	public function setAmount($amt) : IPayment
	{
		if (!Validator::isAmount($amt))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("Amount must have maximal 9 digits before dot and maximal 2 digits after. '%s' is invalid.", $amt));

		$this->amt = $amt;
		return $this;
	}

	public function getAmount() : string
	{
		return $this->amt;
	}

	public function setVariableSymbol(string $vs) : Payment
	{
		if (!Validator::isVariableSymbol($vs))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("Variable symbol must have minimal 1 and maximal 10 digits. '%s' is invalid.", $vs));

		$this->vs = $vs;
		return $this;
	}

	public function getVariableSymbol() : string
	{
		return $this->vs;
	}

	public function setCurrency(string $curr) : Payment
	{
		$currOriginal = $curr;
		$curr = self::normalizeCurrency($curr);
		if (!Validator::isCurrency($curr))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("Currency '%s' is invalid.", $currOriginal));

		$this->curr = $curr;
		return $this;
	}

	public function getCurrency() : string
	{
		return $this->curr;
	}

	public function setName(string $name) : Payment
	{
		if (!Validator::isName($name))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("Name '%s' is invalid. Allowed characters are [0-9a-zA-Z .-_@].", $name));

		$this->name = $name;
		return $this;
	}

	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * @internal
	 */
	public function setTimestamp(string $timestamp) : Payment
	{
		if (!Validator::isTimestamp($timestamp))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("Timestamp '%s' is invalid.", $timestamp));

		$this->timestamp = $timestamp;
		return $this;
	}

	public function getTimestamp() : string
	{
		return $this->timestamp;
	}

	public function setTpay(bool $tpay = true) : Payment
	{
		if (!is_bool($tpay))
			throw new \PaySys\PaySys\InvalidArgumentException(sprintf("TPAY must be boolean."));

		$this->tpay = $tpay;
		return $this;
	}

	public function getTpay() : bool
	{
		return $this->tpay;
	}


	private static function normalizeCurrency(string $curr) : string
	{
		$curr2code = [
			'EUR' => '978',
			'CZK' => '203',
			'USD' => '840',
			'GBP' => '826',
			'HUF' => '348',
			'PLN' => '985',
			'CHF' => '756',
			'DKK' => '208',
		];

		return $curr2code[strtoupper($curr)] ?? $curr;
	}
}
