<?php
$config = new Configuration("111", "http://example.com", Nette\Utils\Random::generate(64));
//Test MID
Assert::same("111", $config->getMid());
//Test URL
Assert::same("http://example.com", $config->getRurl());
//Test HMAC
Assert::match('#^\w{64}$#', $config->getKey());

$checker = new PaySys\CardPay\StatusChecker\Selection($config);
$checker->vs('123444'); // @return self

// Create query, call bank server, check response, parse XML, return array of Transaction
$checker->getAll(); // @return array of PaySys\CardPay\Transaction
?>
