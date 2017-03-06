# PaySys\CardPay

[![Build Status](https://travis-ci.org/PaySys/CardPay.svg?branch=master)](https://travis-ci.org/PaySys/CardPay)
[![Code Quality](https://scrutinizer-ci.com/g/PaySys/CardPay/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/PaySys/CardPay/)
[![Code Coverage](https://scrutinizer-ci.com/g/PaySys/CardPay/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/PaySys/CardPay/)
[![Packagist](https://img.shields.io/packagist/v/PaySys/CardPay.svg)](https://packagist.org/packages/PaySys/CardPay)

Library for implement CardPay gateway ([v1.5 with HMAC & ECDSA](http://www.tatrabanka.sk/cardpay/CardPay_technicka_prirucka_HMAC.pdf)) from Tatra Banka in Nette framework.

## Requirements

Requires PHP 7.1 or later.

Use universal libraty [PaySys\PaySys](https://github.com/PaySys/PaySys).

## Installation

The best way to install Unique is use [Composer](http://getcomposer.org) package [`PaySys/CardPay`](https://packagist.org/packages/PaySys/CardPay).

```bash
$ composer require paysys/cardpay
```

## Configuration

```yaml
extensions:
	cardPay: PaySys\CardPay\DI\CardPayExtension

cardPay:
	mid: '1234'
	key: '64-bit hexadecimal string'
```

## Events

### Object ```PaySys\PaySys\Button```

| Event               | Parameters                       | Description               |
| :------------------ | :------------------------------- | :------------------------ |
| $onBeforePayRequest | \PaySys\PaySys\IPayment $payment | Occurs before pay request |
| $onPayRequest       | \PaySys\PaySys\IPayment $payment | Occurs on pay request     |

### Service ```PaySys\CardPay\Security\Response```

| Event       | Parameters                                     | Description                                  |
| :---------- | :--------------------------------------------- | :------------------------------------------- |
| $onResponse | array $parameters                              | Occurs on response from bank                 |
| $onSuccess  | array $parameters                              | Occurs on success payment response from bank |
| $onFail     | array $parameters                              | Occurs on fail payment response from bank    |
| $onError    | array $parameters, \PaySys\PaySys\Exception $e | Occurs on damaged response from bank         |

## Example

### Generating payment button

Set ```PaySys\CardPay\Payment```.

Button need ```PaySys\PaySys\IConfiguration``` service. Use DI generated factory ```PaySys\PaySys\IButtonFactory``` for getting configured ```PaySys\PaySys\Button``` component.

Now set ```$onPayRequest``` event on ```PaySys\PaySys\Button``` for redirect to CardPay gateway. Signed redirect URL is genereated by service ```PaySys\CardPay\Security\Request->getUrl(PaySys\CardPay\Payment $payment)```.

```php
class OrderPresenter extends Presenter
{
	/** @var \PaySys\PaySys\IButtonFactory @inject */
	public $cardPayButtonFactory;

	/** @var \PaySys\CardPay\Security\Request @inject */
	public $cardPayRequest;

	protected function createComponentCardPayButton()
	{
		$payment = new \PaySys\CardPay\Payment("12.34", "00456", "John Doe");
		$button = $this->cardPayButtonFactory->create($payment);
		$button->onPayRequest[] = function ($payment) {
			$this->redirectUrl($this->cardPayRequest->getUrl($payment));
		};
		return $button;
	}
}

```

### Process payment response

#### Event-driven processing

Default is Bank response routed to included presenter ```CardPay:CardPay:process```. In this case are automatic called events on service ```PaySys\CardPay\Security\Response```.

For processing payment by events use for example [Kdyby\Events](https://github.com/Kdyby/Events).

#### Own presenter

Too it's possible write own ```Nette\Application\UI\Presenter``` for hnadling payment. In this case are events called same as before example.

```php
class OrderPresenter extends Presenter
{
	/** @var Nette\Http\IRequest @inject */
	public $httpRequest;

	/** @var \PaySys\CardPay\Security\Response @inject */
	public $bankResponse;

	public function actionProcessCardPay()
	{
		try {
			$this->bankResponse->paid($this->httpRequest->getQuery());
			// store info about payment
			$this->flashMessage('Thanks for payment.', 'success');
		} catch (\PaySys\PaySys\Exception $e) {
			// log
			$this->flashMessage('Payment failed. Please try it later.', 'danger');
		}
		$this->redirect('finish');
	}
}
```

Now just add route to configuration:

```yaml
cardPay:
	rurl: Order:processCardPay
```

## Exceptions

```php
class \PaySys\PaySys\Exception extends \Exception {}
class \PaySys\PaySys\SignatureException extends \PaySys\PaySys\Exception {}
class \PaySys\PaySys\ServerException extends \PaySys\PaySys\Exception {}
class \PaySys\PaySys\InvalidArgumentException extends \PaySys\PaySys\Exception {}
class \PaySys\PaySys\ConfigurationException extends \PaySys\PaySys\Exception {}
```
