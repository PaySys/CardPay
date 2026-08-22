<?php

use Nette\DI\Compiler;
use Nette\DI\ContainerLoader;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use PaySys\CardPay\Configuration;
use PaySys\CardPay\DI\CardPayExtension;
use Tester\Assert;

require __DIR__ . "/../../../bootstrap.php";

$tempDir = __DIR__ . "/../../../tmp/" . getmypid();
Nette\Utils\FileSystem::createDir($tempDir);
Tester\Helpers::purge($tempDir);

$key = str_repeat('a', 64);
$netteServices = [
	'router' => Nette\Application\Routers\RouteList::class,
	'presenterFactory' => Nette\Application\PresenterFactory::class,
];

$build = function (array $config, array $services = []) use ($tempDir) {
	$loader = new ContainerLoader($tempDir, TRUE);
	$class = $loader->load(function (Compiler $compiler) use ($config, $services) {
		$compiler->addExtension('cardPay', new CardPayExtension);
		$compiler->addConfig(['cardPay' => $config, 'services' => $services]);
		return NULL;
	}, serialize([$config, $services]));

	return new $class;
};


$container = $build(['mid' => '9999', 'key' => $key, 'rurl' => 'https://shop.sk/return'], $netteServices);

Assert::type(Configuration::class, $container->getService('cardPay.config'));
Assert::type(PaySys\CardPay\Security\Request::class, $container->getService('cardPay.request'));
Assert::type(PaySys\CardPay\Security\Response::class, $container->getService('cardPay.response'));
Assert::type(PaySys\CardPay\IButtonFactory::class, $container->getService('cardPay.button'));
Assert::same('9999', $container->getService('cardPay.config')->getMid());
Assert::same('https://shop.sk/return', $container->getService('cardPay.config')->getRurl());
Assert::same(Configuration::PRODUCTION, $container->getService('cardPay.config')->getMode());

// a custom RURL means the application handles the response itself, no route is registered
Assert::null($container->getService('router')->match(new HttpRequest(new UrlScript('https://shop.sk/cardpay-process'))));


// the default RURL registers the built-in route and the presenter mapping
$container = $build(['mid' => '9999', 'key' => $key, 'mode' => Configuration::TEST], $netteServices);

Assert::same(Configuration::TEST, $container->getService('cardPay.config')->getMode());

$params = $container->getService('router')->match(new HttpRequest(new UrlScript('https://shop.sk/cardpay-process')));
Assert::same('CardPay:CardPay', $params['presenter']);
Assert::same('process', $params['action']);

$name = 'CardPay:CardPay';
Assert::same(
	PaySys\CardPay\Application\UI\CardPayPresenter::class,
	$container->getService('presenterFactory')->getPresenterClass($name)
);


// the schema rejects an incomplete or unknown configuration
Assert::exception(function () use ($build, $key) {
	$build(['key' => $key]);
}, Nette\DI\InvalidConfigurationException::class);

Assert::exception(function () use ($build) {
	$build(['mid' => '9999']);
}, Nette\DI\InvalidConfigurationException::class);

Assert::exception(function () use ($build, $key) {
	$build(['mid' => '9999', 'key' => $key, 'mode' => 'staging']);
}, Nette\DI\InvalidConfigurationException::class);

Assert::exception(function () use ($build, $key) {
	$build(['mid' => '9999', 'key' => $key, 'typo' => TRUE]);
}, Nette\DI\InvalidConfigurationException::class);
