<?php

namespace PaySys\CardPay\Application\UI;

use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use PaySys\CardPay\Security\Response;
use Tracy\Debugger;


class CardPayPresenter extends Presenter
{

	/** @var IRequest @inject */
	public $httpRequest;

	/** @var Response @inject */
	public $bankResponse;

	public function actionProcess()
	{
		try {
			$this->bankResponse->paid($this->httpRequest->getQuery());
		} catch (\PaySys\PaySys\Exception $e) {
			$this->logException($e);
			$this->error('Invalid response from bank.', IResponse::S400_BAD_REQUEST);
		}
		$this->terminate();
	}

	/**
	 * A rejected response may be an attack, not a routine outcome, so it must leave a trace.
	 * Tracy is not a hard dependency of this library, log only when the application has it ready.
	 */
	private function logException(\PaySys\PaySys\Exception $e)
	{
		if (class_exists(Debugger::class) && Debugger::$logDirectory !== NULL) {
			Debugger::log($e, 'cardpay');
		}
	}
}
