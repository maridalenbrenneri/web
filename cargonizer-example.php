<?php
require_once("include/cargonizer.php");
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
$crg_api_key = "776f7sdf6sdf55sdf";
$crg_sender_id = "xx";
$crg_consignment_url = "http://cargonizer.no/consignments.xml";
$crg_transport_url = "http://cargonizer.no/transport_agreements.xml";
$debug = 1;
//Instantiate class
$crg = new cargonizer($crg_api_key,$crg_sender_id,$crg_consignment_url);
//Find your transport agreements
echo "Transport agreements<br>\n";
$xml = $crg->requestTransportAgreements($crg_transport_url);
echo "<pre>".print_r($xml,1)."</pre>";
/*
 * Uses transport agreement
 */
$crg_data['consignments'] = array(
	"consignment" => array(
		//_attribs will be parsed as attributes on the parent xml element eg. <consignment key='value' />
		"_attribs" => array(
			"transport_agreement" => "34", //From transport agreement request
			"cost_estimate" => "true",
		),
		"values" => array(
			"value" => array(
				"_attribs" => array(
					"name" => "ordre_id",
					"value" => "123456",
				),
			),
		),		
		"product" => "bring_servicepakke", //From products in transport agreement request
		"parts" => array(
			"consignee" => array(
				"customer-number" => "123456789",
				"name" => "Test Testersen",
				"address1" => "Testveien 1",
				"country" => "NO",
				"postcode" => "0000",
				"city" => "Test",
				"phone" => "66006600",
			),
		),
		"items" => array(
			array("item" => array(
				"_attribs" => array(
					"amount" => "1",
					"description" => "Pakke#1",
					"type" => "PK",
					"weight" => "1",
				),
			)),
			array("item" => array(
				"_attribs" => array(
					"amount" => "1",
					"description" => "Pakke#2",
					"type" => "PK",
					"weight" => "1",
				),
			)),
		),
		//Note that if you use Tollpost instead of Bring, the service block will have more options
		//See logistra API documentation on Tollpost
		"services" => array(
			array("service" => array(
				"_attribs" => array("id"=>"bring_oppkrav"),
				"amount" => "100",
				"account_number" => "123456789",
				"kid" => "123456789",
			)),
		),
		"references" => array(
			"consignor" => "123456",
			"consignee" => "Ordre.nr: 123456",
		),
		"messages" => array(
			"carrier" => "test",
			"consignee" => "test",
		),
	),
);
//Example for adding/changing parts of the array
//$crg_data['consignments']['consignment']['items'][] = array("item"=>array("_attribs"=>array("amount" => "1","description" => "Pakke#1","type" => "PK","weight" => "1")));
//$crg_data['consignments']['consignment']['items'][] = array("item"=>array("_attribs"=>array("amount" => "1","description" => "Pakke#2","type" => "PK","weight" => "1")));
//Request a consignment
echo "Consignment<br>\n";
$crg->requestConsignment($crg_data,$debug,$crg_consignment_url);
echo "Package number: ".$crg->getPkgNumber()."<br>\n";
//Display the entire xml response from cargonizer
$result_xml = $crg->getResultXml();
echo "<pre>".print_r($result_xml,1)."</pre>";
?>