<?php

use PaySys\CardPay\Validator;
use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

Assert::false(Validator::isAmount(NULL));
Assert::false(Validator::isAmount(1));
Assert::false(Validator::isAmount(1.4));
Assert::false(Validator::isAmount("1,2"));
Assert::false(Validator::isAmount("1.1a"));
Assert::false(Validator::isAmount("a"));
Assert::false(Validator::isAmount(new StdClass));
Assert::false(Validator::isAmount("123456789.124"));
Assert::false(Validator::isAmount("1234567890.1"));

Assert::true(Validator::isAmount("1.2"));
Assert::true(Validator::isAmount("1.12"));
Assert::true(Validator::isAmount("123456789.12"));


Assert::false(Validator::isIp("a"));
Assert::false(Validator::isIp(new StdClass));
Assert::false(Validator::isIp("123456789.124"));
Assert::false(Validator::isIp("1.2.3.256"));

Assert::true(Validator::isIp("1.2.3.255"));
Assert::true(Validator::isIp("0.0.0.0"));


Assert::false(Validator::isVariableSymbol("a"));
Assert::false(Validator::isVariableSymbol(new StdClass));
Assert::false(Validator::isVariableSymbol("123456789.124"));
Assert::false(Validator::isVariableSymbol(123456789.124));

Assert::true(Validator::isVariableSymbol("05456"));
Assert::true(Validator::isVariableSymbol("1234567890"));


Assert::false(Validator::isCurrency("a"));
Assert::false(Validator::isCurrency(new StdClass));
Assert::false(Validator::isCurrency("123456789.124"));
Assert::false(Validator::isCurrency(123456789.124));

Assert::true(Validator::isCurrency("208"));
Assert::true(Validator::isCurrency("978"));


Assert::false(Validator::isLang("a"));
Assert::false(Validator::isLang(new StdClass));
Assert::false(Validator::isLang("123456789.124"));
Assert::false(Validator::isLang(123456789.124));

Assert::true(Validator::isLang("sk"));
Assert::true(Validator::isLang("pl"));


Assert::false(Validator::isLang("a"));
Assert::false(Validator::isLang(new StdClass));
Assert::false(Validator::isLang("123456789.124"));
Assert::false(Validator::isLang(123456789.124));

Assert::true(Validator::isLang("sk"));
Assert::true(Validator::isLang("pl"));


Assert::false(Validator::isMid("a"));
Assert::false(Validator::isMid(new StdClass));
Assert::false(Validator::isMid("123456789.124"));
Assert::false(Validator::isMid(123456789.124));

Assert::true(Validator::isMid("1111"));
Assert::true(Validator::isMid("589"));


Assert::false(Validator::isKey("a"));
Assert::false(Validator::isKey(new StdClass));
Assert::false(Validator::isKey("123456789.124"));
Assert::false(Validator::isKey(123456789.124));
Assert::false(Validator::isKey(str_repeat("49:", 64) . "49"));

Assert::true(Validator::isKey(Nette\Utils\Random::generate(64)));
Assert::true(Validator::isKey(Nette\Utils\Random::generate(128)));
Assert::true(Validator::isKey(str_repeat("49:", 63) . "49"));


Assert::false(Validator::isRurl("a"));
Assert::false(Validator::isRurl(new StdClass));
Assert::false(Validator::isRurl("123456789.124"));
Assert::false(Validator::isRurl(123456789.124));

Assert::true(Validator::isRurl("http://test.com?do=call-me"));


Assert::false(Validator::isName(new StdClass));
Assert::false(Validator::isName(123456789.124));
Assert::false(Validator::isName("Ján Doe"));

Assert::true(Validator::isName("a"));
Assert::true(Validator::isName("123456789.124"));
Assert::true(Validator::isName("John Doe"));


Assert::false(Validator::isTimestamp("12032017132461"));
Assert::false(Validator::isTimestamp(new StdClass));
Assert::false(Validator::isTimestamp(123456789.124));
Assert::false(Validator::isTimestamp("Ján Doe"));

Assert::true(Validator::isTimestamp("12032017132456"));
