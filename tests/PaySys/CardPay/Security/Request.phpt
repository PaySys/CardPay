<?php

use PaySys\CardPay\Configuration;
use PaySys\CardPay\Payment;
use PaySys\CardPay\Security\Request;
use Tester\Assert;

require __DIR__ . "/../../../bootstrap.php";

$rurl = "https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/example.jsp";

$config = new Configuration("9999", $rurl, "31323334353637383930313233343536373839303132333435363738393031323132333435363738393031323334353637383930313233343536373839303132");
$config->setIpc("1.2.3.4");
$config->setMode(Configuration::PRODUCTION);

$payment = new Payment("1234.50", "1111", "Jan Pokusny");
$payment->setTimestamp("01092014125505");

$request = new Request($config);
Assert::same("99991234.509781111https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/example.jsp1.2.3.4Jan Pokusny01092014125505", $request->getSignString($payment));

Assert::same("574b763f4afd4167b10143d71dc2054615c3fa76877dc08a7cc9592a741b3eb5", $request->getSign($payment));

Assert::same("https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay?MID=9999&AMT=1234.50&CURR=978&VS=1111&RURL=" . urlencode($rurl) . "&IPC=1.2.3.4&NAME=Jan%20Pokusny&TIMESTAMP=01092014125505&HMAC=574b763f4afd4167b10143d71dc2054615c3fa76877dc08a7cc9592a741b3eb5", (string) $request->getUrl($payment));
