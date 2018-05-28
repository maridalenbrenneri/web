<?php
/*
 * Wrapper for Logistra Cargonizer API
 * w\ general array to xml converter
 * + xml writer class
 *
 * Copyright (C) 2011 by ServiceLogistikk AS
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 */
/**
 * @param string api_key
 * @param int sender_id
 * @param string url [optional]
 */
class cargonizer {
	private $consignment_url = "http://cargonizer.no/consignments.xml";
	private $transport_agreement_url = "http://cargonizer.no/transport_agreements.xml";
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
	
	public function __construct($api_key,$sender_id,$url = "") {
		if($url != '') $this->consignment_url = $url;
		$this->api_key = $api_key;
		$this->sender_id = $sender_id;
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
	 * @param array $data
	 * @param int $debug [0|1] [optional]
	 */
	public function requestConsignment($data,$debug=0) {
		$this->pkg_number = "0";
		$this->urls = array();
		$this->cost_estimate = 0;
		$this->data = $data;

		// echo "<pre>".print_r($data,1)."</pre>";

		$xml = $this->toXml(); 
		echo "<pre> ".print_r($xml,1)."</pre>";

		curl_setopt($this->curl, CURLOPT_URL, $this->consignment_url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
		
		if($debug == 0) echo "XML<br>\n".print_r($xml,1)."<br>\n";
		
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $xml);
		$headers = array(
			"X-Cargonizer-Key:".$this->api_key,
			"X-Cargonizer-Sender:".$this->sender_id,
			"Content-type:application/xml",
			"Content-length:".strlen($xml),
		);
		if($debug == 1) echo "Header\n".print_r($headers,1)."<br>\n";	
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers); 
		
		if($debug == 0) $response = $this->runRequest($debug);
		
		if($debug == 0) $this->parseResponse($response,$debug);
		//echo $response;
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
		$response = $this->runRequest($debug);
		
		return $response;
	}
	
	/**
	 * General array to xml parser
	 */
	private function parseArray($data,&$xw) {

		// echo "<pre>".print_r($data,1)."</pre>";
		echo count($data);
		echo "----";

		foreach($data as $k=>$v) {

			if(!is_array($v)) {
				continue;
			}

			if(!array_key_exists('_attribs', $v)){
				echo '_attribs DOES NOT EXIST';
				continue;
			}

			if($k == "_attribs" and !is_numeric($k)) {
				continue;
			}
			
			if(!array_key_exists('_attribs', $v)){
				//echo '_attribs DOES NOT EXIST';
				continue;
			}

			echo '_attribs EXIST';

			if(is_numeric($k)) {
				$xw = $this->parseArray($v,$xw);
			} else if(is_array($v)) {
				if(count($v) == 1 and count($v['_attribs']) > 0) {
					$xw->element($k,'',$v['_attribs']);
				} else {
					$xw->push($k,$v['_attribs']);
					$xw = $this->parseArray($v,$xw);
					$xw->pop();
				}
			} else {
				$xw->element($k,$v);
			}
		}
		return $xw;
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

	private function toXml() {
		$xw = xmlwriter_open_memory();
		xmlwriter_set_indent($xw, 1);
		$res = xmlwriter_set_indent_string($xw, ' ');

		xmlwriter_start_document($xw, '1.0', 'UTF-8');

		// Root element
		xmlwriter_start_element($xw, 'consignments');

		xmlwriter_start_element($xw, 'consignment');

		$this->createXmlAttr($xw, "transport_agreement", "1061");

		xmlwriter_start_element($xw, 'product');
		xmlwriter_text($xw, 'tg_dpd_innland');
		xmlwriter_end_element($xw); // product

		xmlwriter_start_element($xw, 'parts');
		
			xmlwriter_start_element($xw, 'consignee');

				xmlwriter_start_element($xw, 'name');
				xmlwriter_text($xw, 'Bjoda Dev');
				xmlwriter_end_element($xw); // name
				xmlwriter_start_element($xw, 'postcode');
				xmlwriter_text($xw, '0467');
				xmlwriter_end_element($xw); // postcode
				xmlwriter_start_element($xw, 'country');
				xmlwriter_text($xw, 'NO');
				xmlwriter_end_element($xw); // country

			xmlwriter_end_element($xw); // consignee

		xmlwriter_end_element($xw); // parts

		xmlwriter_start_element($xw, 'items');

			xmlwriter_start_element($xw, 'item');
			$this->createXmlAttr($xw, "type", "package");
			$this->createXmlAttr($xw, "amount", "1");
			$this->createXmlAttr($xw, "weight", "1.7");
			xmlwriter_end_element($xw); // item

		xmlwriter_end_element($xw); // items

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
?>