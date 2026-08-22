<?php

namespace PaySys\CardPay\DI;

use Nette\Application\IPresenterFactory;
use Nette\Application\Routers\Route;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Routing\Router;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use PaySys\CardPay\Configuration;
use PaySys\CardPay\IButtonFactory;
use PaySys\CardPay\Security\Request;
use PaySys\CardPay\Security\Response;


class CardPayExtension extends CompilerExtension
{
	const BASE_ROUTE = "CardPay:CardPay:process";
	const BASE_ROUTE_MASK = "cardpay-process";
	const PRESENTER_MAPPING = ['CardPay' => 'PaySys\CardPay\Application\UI\*Presenter'];

	public function getConfigSchema() : Schema
	{
		return Expect::structure([
			'mid' => Expect::string()->required(),
			'key' => Expect::string()->required(),
			'rurl' => Expect::anyOf(Expect::string(), Expect::array())->default(self::BASE_ROUTE),
			'mode' => Expect::anyOf(Configuration::TEST, Configuration::PRODUCTION)->default(Configuration::PRODUCTION),
		]);
	}

	public function loadConfiguration()
	{
		$config = $this->getConfiguration();
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('config'))
			->setFactory(Configuration::class, [
				'mid' => $config->mid,
				'rurl' => $config->rurl,
				'key' => $config->key,
			])
			->addSetup('setMode', [
				$config->mode,
			]);

		$builder->addFactoryDefinition($this->prefix('button'))
			->setImplement(IButtonFactory::class)
			->getResultDefinition()
			->setFactory('PaySys\PaySys\Button', [
				'config' => $this->prefix('@config'),
			]);

		$builder->addDefinition($this->prefix('request'))
			->setFactory(Request::class);

		$builder->addDefinition($this->prefix('response'))
			->setFactory(Response::class);
	}

	public function beforeCompile()
	{
		if ($this->getConfiguration()->rurl !== self::BASE_ROUTE)
			return;

		$builder = $this->getContainerBuilder();

		// services are looked up by type, their names differ between applications
		if (
			($name = $builder->getByType(Router::class))
			&& ($router = $builder->getDefinition($name)) instanceof ServiceDefinition
		) {
			$router->addSetup('$service->prepend(new ' . Route::class . '(?, ?))', [self::BASE_ROUTE_MASK, self::BASE_ROUTE]);
		}

		if (
			($name = $builder->getByType(IPresenterFactory::class))
			&& ($presenterFactory = $builder->getDefinition($name)) instanceof ServiceDefinition
		) {
			$presenterFactory->addSetup('setMapping', [
				self::PRESENTER_MAPPING,
			]);
		}
	}

	/**
	 * The schema above always produces a structure, this narrows the parent's array|object config to it.
	 */
	private function getConfiguration() : \stdClass
	{
		$config = $this->getConfig();
		if (!$config instanceof \stdClass)
			throw new \PaySys\PaySys\ConfigurationException(sprintf("Expected configuration structure, %s given.", get_debug_type($config)));

		return $config;
	}
}
