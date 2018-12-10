<?php

namespace PaySys\CardPay\DI;

use Nette\DI\CompilerExtension;
use PaySys\CardPay\Configuration;


class CardPayExtension extends CompilerExtension
{
	const BASE_ROUTE = "CardPay:CardPay:process";

	/** @var [] */
	private $defaults = [
		"mid" => "",
		"rurl" => self::BASE_ROUTE,
		"key" => "",
		"mode" => Configuration::PRODUCTION,
	];

	public function loadConfiguration()
	{
		$this->validateConfig($this->defaults);

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('config'))
			->setClass('PaySys\CardPay\Configuration', [
				'mid' => $this->config['mid'],
				'rurl' => $this->config['rurl'],
				'key' => $this->config['key'],
			])
			->addSetup('setMode', [
				$this->config['mode'],
			]);

		$builder->addDefinition($this->prefix('button'))
			->setImplement('PaySys\CardPay\IButtonFactory')
			->setFactory('PaySys\PaySys\Button', [
				'config' => $this->prefix('@config'),
			]);

		$builder->addDefinition($this->prefix('request'))
			->setClass('PaySys\CardPay\Security\Request');

		$builder->addDefinition($this->prefix('response'))
			->setClass('PaySys\CardPay\Security\Response');
	}

	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		if ($this->config['rurl'] === self::BASE_ROUTE) {
			if ($builder->hasDefinition('routing.router')) {
				$netteRouter = $builder->getDefinition('routing.router');
				$netteRouter->addSetup('$service->prepend(new Nette\Application\Routers\Route(\'cardpay-process\', ?));', [self::BASE_ROUTE]);
			}

			if ($builder->hasDefinition('nette.presenterFactory')) {
				$builder->getDefinition('nette.presenterFactory')
					->addSetup('setMapping', [
						['CardPay' => 'PaySys\CardPay\Application\UI\*Presenter'],
					]);
			}
		}
	}
}
