<?php

use PaySys\CardPay\Configuration;
use PaySys\CardPay\Validator;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$config = new Configuration("111", "http://example.com", Nette\Utils\Random::generate(64));

Assert::same("111", $config->getMid());
Assert::same("http://example.com", $config->getRurl());
Assert::match('#^\w{64}$#', $config->getKey());
Assert::true(Validator::isIp($config->getIpc()));
Assert::true(file_exists($config->getButtonTemplate()));


$config->setKey(str_repeat('78', 64));
Assert::match('#^[x]{64}$#', $config->getKey());

$config->setKey(str_repeat('61:', 63) . '61');
Assert::match('#^[a]{64}$#', $config->getKey());

$config->setKey(str_repeat('b', 64));
Assert::match('#^[b]{64}$#', $config->getKey());


Assert::exception(function() use ($config) {
	$config->setMid("a");
}, "PaySys\\PaySys\\ConfigurationException", "Parameter MID must have 3 or 4 characters. 'a' is invalid.");
Assert::same("111", $config->getMid());

$config->setMid("321");
Assert::same("321", $config->getMid());


Assert::exception(function() use ($config) {
	$config->setRurl(new StdClass);
}, "PaySys\\PaySys\\ConfigurationException", "RURL type of 'object' is invalid. Must be string or array.");

Assert::exception(function() use ($config) {
	$config->setRurl("Test:");
}, "PaySys\\PaySys\\ConfigurationException", "RURL 'Test:' is invalid. Must be valid URL by RFC 1738.");
Assert::same("http://example.com", $config->getRurl());

$config->setRurl("http://test.com");
Assert::same("http://test.com", $config->getRurl());


Assert::exception(function() use ($config) {
	$config->setLang("ff");
}, "PaySys\\PaySys\\ConfigurationException", "Lang 'ff' is not supported.");
Assert::same("sk", $config->getLang());

$config->setLang("en");
Assert::same("en", $config->getLang());


Assert::exception(function() use ($config) {
	$config->setIpc("a");
}, "PaySys\\PaySys\\ConfigurationException", "IP 'a' is not valid.");
Assert::true(Validator::isIp($config->getIpc()));

$config->setIpc("12.34.56.78");
Assert::same("12.34.56.78", $config->getIpc());


Assert::exception(function() use ($config) {
	$config->setIpc("a");
}, "PaySys\\PaySys\\ConfigurationException", "IP 'a' is not valid.");
Assert::true(Validator::isIp($config->getIpc()));

$config->setIpc("12.34.56.78");
Assert::same("12.34.56.78", $config->getIpc());


Assert::exception(function() use ($config) {
	$config->setMode("a");
}, "PaySys\\PaySys\\ConfigurationException", "Mode 'a' is not valid. Use please 'test' or 'production'.");
Assert::same("production", $config->getMode());

$config->setMode(Configuration::TEST);
Assert::same("test", $config->getMode());


Assert::exception(function() use ($config) {
	$config->setButtonTemplate("---");
}, "PaySys\\PaySys\\ConfigurationException", "Template file '---' not exists.");



class TestPresenter extends Nette\Application\UI\Presenter
{
	public function actionTest() {}
}

$config = new Configuration("111", "http://example.com", Nette\Utils\Random::generate(64), new Nette\Application\LinkGenerator(
	new Nette\Application\Routers\SimpleRouter('Test:default'),
	new Nette\Http\Url("http://example.com"),
	new Nette\Application\PresenterFactory
));
$config->setRurl("Test:test");
Assert::same("http://example.com/?action=test", $config->getRurl());
