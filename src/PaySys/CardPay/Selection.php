<?php
public class Selection($config)
{
  protected $mid;
  
  protected $hmac;
  
  public $url;
  
  public $response;
  
  public $p;
  
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
