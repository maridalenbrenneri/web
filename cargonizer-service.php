 <?php 

echo '<h1>Running cargonizer stuff</h1>'; 

$debug = 0;
$cargonizer = new cargonizer;
// $response =  $cargonizer->getTransportAgreements(1);
// echo "<pre>".print_r($response,1)."</pre>";

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
				"name" => "Bjoda Man",
				"address1" => "Bakken 1",
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
$cargonizer->createConsignment($crg_data,$debug);
echo "Package number: ".$cargonizer->getPkgNumber()."<br>\n";
//Display the entire xml response from cargonizer
$result_xml = $cargonizer->getResultXml();
echo "<pre>".print_r($result_xml,1)."</pre>";

class cargonizer {
    private $crg_api_key = "3929bc31e5d97fe928ab0c1b3dde7ff71bf85ad2";
    private $crg_sender_id = "1319";
    private $crg_consignment_url = "http://sandbox.cargonizer.no/consignments.xml";
    private $crg_transport_url = "http://sandbox.cargonizer.no/transport_agreements.xml";
    
    private $curl;
    private $error = array();
    private $error_flag = 0;
    private $pkg_number;
    private $sxml;

    public function __construct() {

        $this->curl = curl_init();
        
		// curl_setopt($this->curl, CURLOPT_URL, $this->consignment_url); 
		// curl_setopt($this->curl, CURLOPT_VERBOSE, 1); 
		// curl_setopt($this->curl, CURLOPT_HEADER, 0); 
		// curl_setopt($this->curl, CURLOPT_POST, 1); 
		// curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1); 
	}
	
	public function __destruct() {
		curl_close($this->curl);
    }

    public function getPkgNumber() {
		return $this->pkg_number;
    }
    
    public function getResultXml() {
		return $this->sxml;
    }
    
    public function getTransportAgreements($debug=0) {
        echo "getTransportAgreements, URL: $this->crg_transport_url<br>\n";
        
        curl_setopt($this->curl, CURLOPT_URL, $this->crg_transport_url);
        curl_setopt($this->curl, CURLOPT_POST, 0);
        
        $headers = array(
            "X-Cargonizer-Key:".$this->crg_api_key,
            "X-Cargonizer-Sender:".$this->crg_sender_id,
            "Content-type:application/xml",
            "Content-length:0",
        );
        
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        
        return $this->runRequest($debug);
    }   
    
	public function createConsignment($data,$debug=0) {
		$this->pkg_number = "0";
		$this->urls = array();
		$this->cost_estimate = 0;
		$this->data = $data;
				
		$xw = new cargonizerXmlWriter;
		$xw = $this->parseArray($data,$xw);
		$xml = $xw->getXml();
		curl_setopt($this->curl, CURLOPT_URL, $this->consignment_url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
		
		if($debug == 1) echo "XML<br>\n".print_r($xml,1)."<br>\n";
		
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
        
        echo $response;
		
	//	if($debug == 0) $this->parseResponse($response,$debug);
		
		return "hello"; // $response;
    }
    
    function runRequest($debug=0) {
        
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
			$this->errors['parsing'] .= $sxml."\n".print_r($this->data,1);
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
    
	/**
	 * General array to xml parser
	 */
	private function parseArray($data,&$xw) {
		foreach($data as $k=>$v) {
			if($k == "_attribs" and !is_numeric($k)) {
				continue;
			}
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
}

class cargonizerXmlWriter {
    private $xml;
    private $indent;
    private $stack = array();
    function CRG_Xmlwriter($indent = '  ',$encoding = 'UTF-8') {
        $this->indent = $indent;
        $this->xml = "<?xml version=\"1.0\" encoding=\"$encoding\"?>"."\n";
    }
    function _indent() {
        for ($i = 0, $j = count($this->stack); $i < $j; $i++) {
            $this->xml .= $this->indent;
        }
    }
    //* Push
    function push($element, $attributes = array(), $ns = "") {
        $this->_indent();
        $this->xml .= '<'.$element;
        if($ns != '') $this->xml .= " ".$ns; 
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.$value.'"';
        }
        $this->xml .= ">\n";
        $this->stack[] = $element;
    }
    function push_cdata($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="<![CDATA['.$value.']]>"';
        }
        $this->xml .= ">\n";
        $this->stack[] = $element;
    }
    function push_htmlentities($element, $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.htmlentities($value).'"';
        }
        $this->xml .= ">\n";
        $this->stack[] = $element;
    }
    //* Element
    function element($element, $content = '', $attributes = array(), $ns = '', $nil = '') {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.$value.'"';
        }
        if($content == '') {
        	if($nil != '') $this->xml .= " ".$nil;
        	if($ns != '') $this->xml .= " ".$ns;
        	$this->xml .= " />\n";
        } else {
        	if($ns != '') $this->xml .= " ".$ns;
        	$this->xml .= '>'.$content.'</'.$element.'>'."\n";
        }
    }
    function element_cdata($element, $content = '', $attributes = array(), $length = 0) {
    	
    	if($length > 0) {
	    	$c_len = strlen("![CDATA[]]");
	    	if(strlen($content)+$c_len > $length) {
	    		$real_length = $length-$c_len;
	    		$content = substr($content,0,$real_length);
	    	}
    	}
    	
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="<![CDATA['.$value.']]>"';
        }
        if($content == '') {
        	$this->xml .= " />\n";
        } else {
        	$this->xml .= '><![CDATA['.$content.']]></'.$element.'>'."\n";
        }
    }
    function element_htmlentities($element, $content = '', $attributes = array()) {
        $this->_indent();
        $this->xml .= '<'.$element;
        foreach ($attributes as $key => $value) {
            $this->xml .= ' '.$key.'="'.htmlentities($value).'"';
        }
        if($content == '') {
        	$this->xml .= " />\n";
        } else {
        	$this->xml .= '>'.htmlentities($content).'</'.$element.'>'."\n";
        }
    } 
    function pop() {
        $element = array_pop($this->stack);
        $this->_indent();
        $this->xml .= "</$element>\n";
    }
    function getXml() {
        return $this->xml;
    }
}

?> 
