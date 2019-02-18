<?php
require_once("./cargonizer.php");
require_once("./cargonizer-settings.php");

class WC_OrderMock {
  public function get_shipping_postcode() {
    return "0467";
  }
  public function get_order_number() {
    return "1";
  }
  public function get_items() {
    return [];
  }
  public function get_customer_id() {
    return "1";
  }
  public function get_shipping_first_name() {
    return "Kaffe";
  }
  public function get_shipping_last_name() {
    return "Mannen";
  }
  public function get_shipping_address_1() {
    return "Badebakken 8";
  }
  public function get_shipping_address_2() {
    return null;
  }
  public function get_shipping_city() {
    return "Oslo";
  }
  public function get_shipping_country() {
    return "NO";
  }
  public function get_billing_email() {
    return "bjuffe@gmail.com";
  }    
  public function get_billing_phone() {
    return "12345678";
  }    
}

$crg_settings = new cargonizer_settings;

$crg = new cargonizer(
  $crg_settings->api_key, 
  $crg_settings->api_sender, 
  $crg_settings->transport_agreement,
  $crg_settings->product, 
  $crg_settings->consignment_url
);

$order = new WC_OrderMock();

$response = $crg->requestConsignment($order, 500);

error_log($response);
?>