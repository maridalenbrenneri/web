<?php
class cargonizer {
	private $consignment_url = "http://cargonizer.no/consignments.xml";
	private $transport_agreement_url = "http://cargonizer.no/transport_agreements.xml";
	private $service_partners_url = "http://cargonizer.no/service_partners.xml";
	private $print_label_url = "https://cargonizer.no/consignments/label_direct";
	private $api_key;
	private $sender_id;
	private $curl; 
	private $data_xml = "<xml></xml>";
	private $data = array();
	private $pkg_number;
	private $urls = array();
	private $cost_estimate = 0;
	private $errors = array();
	private $error_flag = 0;
	private $sxml;
	private $transport_agreement;
    private $crg_product;
    private $crg_product_srv;

	public function __construct($api_key, $sender_id, $transport_agreement, $product, $crg_product_srv, $url = '') {
		if($url != '') $this->consignment_url = $url;
	  
		$this->api_key = $api_key;
		$this->sender_id = $sender_id;
		$this->transport_agreement = $transport_agreement;
	    $this->crg_product = $product;
	  	$this->crg_product_srv = $crg_product_srv;
		
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->consignment_url); 
		curl_setopt($this->curl, CURLOPT_VERBOSE, 1); 
		curl_setopt($this->curl, CURLOPT_HEADER, 0); 
		curl_setopt($this->curl, CURLOPT_POST, 1); 
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1); 
	}
	
	public function __destruct() {
		curl_close($this->curl);
	}
	
	public function getPkgNumber() {
		return $this->pkg_number;
	}
	
	/**
	 * 
	 * Returns an array with the consignment document urls
	 * @return array
	 */
	public function getUrls() {
		return $this->urls;
	}
	
	public function getErrorFlag() {
		return $this->error_flag;
	}
	
	public function getErrors() {
		return $this->errors;
	}
	
	public function getCostEstimate() {
		return $this->cost_estimate;
	}
	
	/**
	 * 
	 * returns the resulting xml response from cargonizer consignment call
	 * @return simplexml_object
	 */
	public function getResultXml() {
		return $this->sxml;
	}
	
	/**
	 * 
	 * Creates a consignment
	 * @param array $wc_order WooCommerce order
	 * @param int $debug [0|1] [optional]
	 */
	public function requestConsignment($wc_order, $wc_order_weight) {
	  
		$this->pkg_number = "0";
		$this->urls = array();
		$this->cost_estimate = 0;
		$this->wc_order = $wc_order;

		$service_partner = NULL;
		$servicePartnerResponse = $this->requestServicePartners('NO', $wc_order->get_shipping_postcode());
		
		$sxml = simplexml_load_string($servicePartnerResponse);
	  
	//	echo "<pre>".print_r($sxml->{'service-partners'}->{'service-partner'},1)."</pre>";

		if($sxml->{'service-partners'}->{'service-partner'} != NULL) {
			$partner = $sxml->{'service-partners'}->{'service-partner'}[0];
			$address = new mb_address(
				$partner->{'name'},
				$partner->{'address1'},
				$partner->{'address2'},
				$partner->{'postcode'},
				$partner->{'city'},
				$partner->{'country'}
			);
			$service_partner = new mb_service_partner(
				$partner->{'number'},
				$address
			);
		}

		$xml = $this->toXmlFromWcOrder($wc_order, $wc_order_weight, $service_partner); 

		curl_setopt($this->curl, CURLOPT_URL, $this->consignment_url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
		
		// echo "XML<br>\n".print_r($xml,1)."<br>\n";
		
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $xml);
		$headers = array(
			"X-Cargonizer-Key:".$this->api_key,
			"X-Cargonizer-Sender:".$this->sender_id,
			"Content-type:application/xml",
			"Content-length:".strlen($xml),
		);	
	  
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers); 
		
		$response = $this->runRequest(0);

		$sxml = simplexml_load_string($response);
		$id = 0;
		foreach($sxml->consignment as $consignment) {
			$id = $consignment->{'id'};
		}

		$this->printLabel($id);
				
		return $response;
	}

	/**
	 * 
	 * Fetches the transport agreements for the set API key and Sender ID
	 * You need the transport ID and Product for the consignment call
	 * @param string $url [optional]
	 * @return simplexml_object
	 */
	public function requestTransportAgreements($url = "") {
		if($url == '') $url = $this->transport_agreement_url;
		echo "URL: $url<br>\n";
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POST, 0);
		$headers = array(
			"X-Cargonizer-Key:".$this->api_key,
			"X-Cargonizer-Sender:".$this->sender_id,
			"Content-type:application/xml",
			"Content-length:0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		$response = $this->runRequest(0);
		
		return $response;
	}
		
	/**
	 * Print label
	 */
	public function printLabel($consignmentId, $url = "") {
		$printer_normal_id = 1057;
		$printer_rfid_id = 1698;

		$printer = $printer_normal_id;
		if($this->crg_product == "bring_pose_pa_doren_rfid") {
			$printer = $printer_rfid_id;
		}

		if($url == '') $url = $this->print_label_url;

		$url = $url . "?printer_id=" . $printer . "&consignment_ids[]=" . $consignmentId;

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POST, 0);
		$headers = array(
			"X-Cargonizer-Key:".$this->api_key,
			"X-Cargonizer-Sender:".$this->sender_id,
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		$response = $this->runRequest(0);
		
		return $response;
	}

	 /**
	 * 
	 * Fetches the service partners for post code
	 * @param string $url [optional]
	 * @return simplexml_object
	 */
	public function requestServicePartners($country, $postcode, $url = "") {
		if($url == '') $url = $this->service_partners_url;
		
		$url = $url . '?country=' . $country . '&postcode=' . $postcode;

		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POST, 0);
		$headers = array(
			"X-Cargonizer-Key:".$this->api_key,
			"X-Cargonizer-Sender:".$this->sender_id,
			"Content-type:application/xml",
			"Content-length:0",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		$response = $this->runRequest();
		
		return $response;
	}

	private function runRequest($debug=0) {
		$response = curl_exec($this->curl); 
		if(!curl_errno($this->curl)) { 
			$info = curl_getinfo($this->curl); 
			if($debug == 1) echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url']."<br>\n"; 
		} else { 
			if($debug == 1) echo 'Curl error: ' . curl_error($this->curl)."<br>\n";
			$this->error_flag = 1;
			$this->errors['curl_request'] .= curl_error($this->curl)."\n";
		} 
		return $response;
	}
	
	private function parseResponse($xml,$debug=0) {
		$sxml = simplexml_load_string($xml);
		$this->sxml = $sxml;
		
		if($sxml->getName() == "errors") {
			if($debug == 1) echo "SXML<br><pre>".print_r($sxml,1)."</pre>";
			$this->error_flag = 1;
			if(array_key_exists('parsing', $this->errors)) $this->errors['parsing'] .= $sxml."\n".print_r($this->data,1);
		} else {
			if($debug == 1) echo "SXML<br><pre>".print_r($sxml,1)."</pre>";
		}
		foreach($sxml->consignment as $consignment) {
			$this->pkg_number = (string)$consignment->{'number-with-checksum'};
			if($debug == 1) echo "PDF: ".$consignment->{'consignment-pdf'}."<br>\n";
			$this->urls['consignment-pdf'] = $consignment->{'consignment-pdf'};
			$this->urls['collection-pdf'] = (string)$consignment->{'collection-pdf'};
			$this->urls['waybill-pdf'] = (string)$consignment->{'waybill-pdf'};
			$this->urls['tracking-url'] = (string)$consignment->{'tracking-url'};
			if($debug == 1) echo "Values: ".print_r((string)$consignment->{'cost-estimate'}->gross,1)."<br>\n";
			$this->cost_estimate = (string)$consignment->{'cost-estimate'}->gross;
		}
	}

	private function toXmlFromWcOrder($order, $wc_order_weight, $service_partner) {
		$order_senders_ref = '';
	  
		// Adding product names in short format for ref on printed labels
		foreach ($order->get_items() as $item_id => $item_data) {
			$product = $item_data->get_product();
			$product_name = $product->get_name(); 
			$item_quantity = $item_data->get_quantity();

			if (strpos($product_name, 'Gaveabonnement') !== false) {
				// ignore gabo (we never need to print those on the label)
			} elseif (strpos($product_name, 'Kaffeabonnement') !== false) {
				$name = str_ireplace('Kaffeabonnement - ', 'ABO', $product_name);
				$name2 = str_ireplace(', Månedlig', '', $name);
				$shortName = str_ireplace(', Annenhver uke', '', $name2);
				$order_senders_ref = $order_senders_ref . $shortName;

			} else {
				$pos1 = strpos($product_name, '(');
				$pos2 = strpos($product_name, ')');
				if($pos1 === false || $pos2 === false) {
					$order_senders_ref = $order_senders_ref + $item_quantity . $product_name . ' ';
				} else {
					$shortName = substr($product_name, $pos1 + 1, $pos2 - $pos1 - 1);
					$order_senders_ref = $order_senders_ref . $item_quantity . $shortName . ' ';
				}
			}
		}
	  
		$order_customer_number = $order->get_customer_id();
		$order_name = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
		$order_address1 = $order->get_shipping_address_1();
		$order_address2 = $order->get_shipping_address_2();
		$order_postcode = $order->get_shipping_postcode();
		$order_city = $order->get_shipping_city();
		$order_country = $order->get_shipping_country();

		$order_email = $order->get_billing_email();
		$order_mobile = $order->get_billing_phone();

		$order_weight = $wc_order_weight / 1000; // cargonizer wants kilogram

		$xw = xmlwriter_open_memory();
		xmlwriter_set_indent($xw, 1);
		$res = xmlwriter_set_indent_string($xw, ' ');

		xmlwriter_start_document($xw, '1.0', 'UTF-8');

		// Root element
		xmlwriter_start_element($xw, 'consignments');

		xmlwriter_start_element($xw, 'consignment');

		$this->createXmlAttr($xw, "transport_agreement", $this->transport_agreement);
	  
	    $this->createXmlAttr($xw, "print", "false");

		xmlwriter_start_element($xw, 'product');
	    xmlwriter_text($xw, $this->crg_product);
		xmlwriter_end_element($xw); // product

		xmlwriter_start_element($xw, 'parts');
		
			xmlwriter_start_element($xw, 'consignee');

				xmlwriter_start_element($xw, 'number');
				xmlwriter_text($xw, $order_customer_number);
				xmlwriter_end_element($xw); // number			
				xmlwriter_start_element($xw, 'name');
				xmlwriter_text($xw, $order_name);
				xmlwriter_end_element($xw); // name
				xmlwriter_start_element($xw, 'address1');
				xmlwriter_text($xw, $order_address1);
				xmlwriter_end_element($xw); // address1		
				xmlwriter_start_element($xw, 'address2');
				xmlwriter_text($xw, $order_address2);
				xmlwriter_end_element($xw); // address2			
				xmlwriter_start_element($xw, 'postcode');
				xmlwriter_text($xw, $order_postcode);
				xmlwriter_end_element($xw); // postcode
				xmlwriter_start_element($xw, 'city');
				xmlwriter_text($xw, $order_city);
				xmlwriter_end_element($xw); // city
				xmlwriter_start_element($xw, 'country');
				xmlwriter_text($xw, $order_country);
				xmlwriter_end_element($xw); // country
				xmlwriter_start_element($xw, 'email');
				xmlwriter_text($xw, $order_email);
				xmlwriter_end_element($xw); // email
				xmlwriter_start_element($xw, 'mobile');
				xmlwriter_text($xw, $order_mobile);
				xmlwriter_end_element($xw); // mobile

			xmlwriter_end_element($xw); // consignee

			if($service_partner != NULL) {
				xmlwriter_start_element($xw, 'service_partner');
					xmlwriter_start_element($xw, 'number');
					xmlwriter_text($xw, $service_partner->service_partner_number);
					xmlwriter_end_element($xw); // number
					xmlwriter_start_element($xw, 'name');
					xmlwriter_text($xw,  $service_partner->address->name);
					xmlwriter_end_element($xw); // name
					xmlwriter_start_element($xw, 'address1');
					xmlwriter_text($xw, $service_partner->address->address1);
					xmlwriter_end_element($xw); // address1
					xmlwriter_start_element($xw, 'address2');
					xmlwriter_text($xw, $service_partner->address->address2);
					xmlwriter_end_element($xw); // address2
					xmlwriter_start_element($xw, 'postcode');
					xmlwriter_text($xw, $service_partner->address->postcode);
					xmlwriter_end_element($xw); // postcode
					xmlwriter_start_element($xw, 'city');
					xmlwriter_text($xw, $service_partner->address->city);
					xmlwriter_end_element($xw); // city
					xmlwriter_start_element($xw, 'country');
					xmlwriter_text($xw, $service_partner->address->country);
					xmlwriter_end_element($xw); // country
				xmlwriter_end_element($xw); // service_partner
			}

		xmlwriter_end_element($xw); // parts

		xmlwriter_start_element($xw, 'items');

			xmlwriter_start_element($xw, 'item');
			$this->createXmlAttr($xw, "type", "package");
			$this->createXmlAttr($xw, "amount", "1");
			$this->createXmlAttr($xw, "weight", '' . $order_weight);
			xmlwriter_end_element($xw); // item

		xmlwriter_end_element($xw); // items

		xmlwriter_start_element($xw, 'services');
	  
	  		xmlwriter_start_element($xw, 'service');
			$this->createXmlAttr($xw, "id", $this->crg_product_srv);
			xmlwriter_end_element($xw); // service
	  
		xmlwriter_end_element($xw); // services

		xmlwriter_start_element($xw, 'references');
			xmlwriter_start_element($xw, 'consignor');
			xmlwriter_text($xw, $order_senders_ref);
			xmlwriter_end_element($xw); // consignor
		xmlwriter_end_element($xw); // references

		xmlwriter_start_element($xw, 'return_address');
			xmlwriter_start_element($xw, 'name');
			xmlwriter_text($xw, 'Maridalen Brenneri AS');
			xmlwriter_end_element($xw); // name
			xmlwriter_start_element($xw, 'address1');
			xmlwriter_text($xw, 'Sørbråtveien 36');
			xmlwriter_end_element($xw); // address1
			xmlwriter_start_element($xw, 'postcode');
			xmlwriter_text($xw, '0891');
			xmlwriter_end_element($xw); // postcode
			xmlwriter_start_element($xw, 'city');
			xmlwriter_text($xw, 'Oslo');
			xmlwriter_end_element($xw); // city
			xmlwriter_start_element($xw, 'country');
			xmlwriter_text($xw, 'NO');
			xmlwriter_end_element($xw); // country
		xmlwriter_end_element($xw); // return_address
		
		xmlwriter_end_element($xw); // consignment

		xmlwriter_end_element($xw); // consignments

		xmlwriter_end_document($xw);

		return xmlwriter_output_memory($xw);
	}

	private function createXmlAttr($xw, $name, $value){
		xmlwriter_start_attribute($xw, $name);
		xmlwriter_text($xw, $value);
		xmlwriter_end_attribute($xw);
	}
}

class mb_address {
	public $name;
	public $address1;
	public $address2;
	public $postcode;
  	public $city;
	public $country;

	public function __construct($name, $address1, $address2, $postcode, $city, $country) {
		$this->name = $name;
		$this->address1 = $address1;
		$this->address2 = $address2;
		$this->postcode = $postcode;
	  	$this->city = $city;
		$this->country = $country;
	}
}

class mb_service_partner {
	public $service_partner_number;
	public $address;

	public function __construct($service_partner_number, $address) {
		$this->service_partner_number = $service_partner_number;
		$this->address = $address;
	}
}

?>