<?php

use PaySys\CardPay\Configuration;
use PaySys\CardPay\Payment;
use PaySys\CardPay\Security\Request;
use PaySys\CardPay\Security\Response;
use Tester\Assert;

require __DIR__ . "/../../../bootstrap.php";

$config = new Configuration('9999', 'https://www.obchodnik.sk/potvrdenie_platby.php', '78787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878787878');
$config->setIpc("188.200.192.180");
$config->setRem("online_platby@obchodnik.sk");
Assert::same("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", $config->getKey());

$payment = new Payment("20.78", "78945210", "Peter Novak");
$payment->setTimestamp("01112014100000");

$request = new Request($config);
Assert::same("999920.7897878945210https://www.obchodnik.sk/potvrdenie_platby.php188.200.192.180Peter Novakonline_platby@obchodnik.sk01112014100000", $request->getSignString($payment));

Assert::same("4d56aa232d22fbd5756954c5728e111858f65bb10f978f5fd8a55cc2d04eca3a", $request->getSign($payment));

$response = new Response($config);

$r = [
	'AMT' => '20.78',
	'CURR' => '978',
	'VS' => '78945210',
	'RES' => 'OK',
	'AC' => '832',
	'TID' => '45678',
	'TIMESTAMP' => '01112014100000',
	'HMAC' => '9fbf5a7d1a914d7806a545565b971fa480feb48e402f7a8df80dcea0fdea6049',
	'ECDSA_KEY' => '1',
	'ECDSA' => '304502201fb6e376a6b7bb8fe34d931e5e409721c80fb481710dac947cf913a6a3f98f5e022100f1f3066ce4a87cd139742edcd15bdb0c100ccbd7b524e6a1a866d81c273472f7',
];

Assert::same("20.7897878945210OK8324567801112014100000", $response->getSignString($r));
Assert::same("9fbf5a7d1a914d7806a545565b971fa480feb48e402f7a8df80dcea0fdea6049", $response->getHmac($r));

//Assert::true($response->verified($r));
