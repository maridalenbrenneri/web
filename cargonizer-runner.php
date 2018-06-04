<?php
require_once("cargonizer.php");
require_once("cargonizer-settings.php");
/*
 * This wrapper converts array to xml
 * It transfers to Cargonizer using array keys and values as: <array_key>array_value</array_key>
 * 
 * _attribs are added to the parent element as attributes (<array_key attrib_key = 'attrib_value'>)
 * 
 * For field values, see Logistra's documentation of API
 * http://www.logistra.no/api-documentation/12-utviklerinformasjon/16-api-consignments.html
 * 
 */
//Attain API key and sender ID from Logistra
// $crg_api_key = "776f7sdf6sdf55sdf";
// $crg_sender_id = "xx";
// $crg_consignment_url = "http://cargonizer.no/consignments.xml";
// $crg_transport_url = "http://cargonizer.no/transport_agreements.xml";

$crg_settings = new cargonizer_settings;

$debug = 0;
$crg = new cargonizer($crg_settings->api_key, $crg_settings->api_sender, $crg_settings->transport_agreement);

$crg->requestConsignment(NULL, 500);

// Transport agreements
// echo "Transport agreements<br>\n";
// $xml = $crg->requestTransportAgreements($crg_transport_url);
// echo "<pre>".print_r($xml,1)."</pre>";

// Service partners


// Consignments


?>