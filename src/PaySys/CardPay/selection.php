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

class Selection($config)
{
  public function __construct()
  {
    $this->mid = $config->getMid();
    $this->hmac = $config->getKey();
  }
  
  public function vs($vs)
  {
    $this->vs = $vs;
  }
  
  public function getAll()
  {
    //Set URL
    $url = "https://moja.tatrabanka.sk/cgi-bin/e-commerce/start/cardpay_txn.jsp
            ?MID=".$this->mid."&HMAC=".$this->hmac."&VS=".$this->vs;

    //Get Response
    $response = file_get_contents($url);

    //Parse XML
    $p = xml_parser_create();
    xml_parse_into_struct($p, $response, $vals, $index);
    xml_parser_free($p);
    
    return $vals;
  }
}
?>
