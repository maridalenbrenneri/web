 <?php 

echo '<h1>Running cargonizer stuff</h1>'; 

$cargonizer = new cargonizer;
$response =  $cargonizer->getTransportAgreements(1);
echo "<pre>".print_r($response,1)."</pre>";

class cargonizer {
    private $crg_api_key = "3929bc31e5d97fe928ab0c1b3dde7ff71bf85ad2";
    private $crg_sender_id = "1319";
    private $crg_consignment_url = "http://sandbox.cargonizer.no/consignments.xml";
    private $crg_transport_url = "http://sandbox.cargonizer.no/transport_agreements.xml";
    
    private $curl;
    private $error = array();
    private $error_flag = 0;
    
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

    function getTransportAgreements($debug=0) {
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
}

?> 
