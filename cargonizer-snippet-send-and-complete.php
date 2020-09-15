add_filter( 'woocommerce_order_actions', 'add_order_meta_box_actions');
function add_order_meta_box_actions( $actions ) {
  	$actions['send_to_cargonizer_bring'] = __( '-= BRING + Complete =-', 'my-textdomain' );
	return $actions;
}

add_action( 'woocommerce_order_action_send_to_cargonizer_bring', 'process_send_to_cargonizer_bring_action' );
function process_send_to_cargonizer_bring_action( $order ) {
    // cancel request for orders that shouldn't be shipped via Cargonizer
	foreach($order->get_used_coupons() as $item_id => $item_data) {
	  if($item_data === 'nullfrakt2018') {
		$order->add_order_note( 'This order should not be sent to cargonizer, cancelled request.');
		return;
	  }
	}
  
	$res = cargonizer_request_consignment($order, 'bring');
  	if($res == 0) {
	 $order->update_status('completed'); 
	}
}

add_filter( 'bulk_actions-edit-shop_order', 'register_my_bulk_actions' );
function register_my_bulk_actions( $bulk_actions ) {
	$bulk_actions['send_to_cargonizer_bring'] = __( '-= BRING + Complete =-', 'domain' );
	return $bulk_actions;
}

add_action( 'admin_action_send_to_cargonizer_bring', 'misha_bulk_process_custom_status' );
function misha_bulk_process_custom_status() {
 
	// if an array with order IDs is not presented, exit the function
	if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
		return;
 
	foreach( $_REQUEST['post'] as $order_id ) {
 
		$order = new WC_Order( $order_id );
	  	$res = cargonizer_request_consignment($order, 'bring');
    	if($res == 0) {
	      $order->update_status('completed'); 
	    } 
	}
}

/**
* Call cargonizer service
*/
function cargonizer_request_consignment( $order, $provider, $useSandbox = 0 ) {
  	$order_emballage_weight = 150;
	$crg_api_key = "";
	$crg_sender_id = "6350";
	$crg_consignment_url = "https://cargonizer.no/consignments.xml";
	$crg_transport_url = "http://cargonizer.no/transport_agreements.xml";
	$crg_transport_agreement = "13654";
  	$crg_product = "bring2_small_parcel_a_no_rfid";
  	$crg_product_srv = "bring2_delivery_to_door_handle"; 
  
	if($useSandbox == 1) {
		$crg_api_key = "";
		$crg_sender_id = "1319";
		$crg_consignment_url = "https://sandbox.cargonizer.no/consignments.xml";
		$crg_transport_url = "http://sandbox.cargonizer.no/transport_agreements.xml";
	   	
	  	if($provider == 'bring'){
		  $crg_transport_agreement = '1197';
		  $crg_product = 'bring_pa_doren';
		  
		} else {
		  $crg_transport_agreement = '1061';
		}
	}

	$wc_order = new WC_Order($order->get_id());
	$total_weight = 0;

	foreach( $order->get_items() as $item_id => $product_item ){
	  $quantity = $product_item->get_quantity(); // get quantity
	  $product = $product_item->get_product(); // get the WC_Product object
	  $product_weight = $product->get_weight(); // get the product weight
	  // Add the line item weight to the total weight calculation
	  $total_weight += floatval( $product_weight * $quantity );
	}

	if($total_weight > 0) {
	  $total_weight += $order_emballage_weight;
	}
  
  	if($total_weight >= 2000) {
		$order->add_order_note( 'WARNING: TOO HEAVY (' . $total_weight . 'g), CANNOT SEND TO CARGONIZER. YOU HAVE TOO HANDLE THIS ONE MANUALLY :P'  );
	    return 1;
	}
    
	$crg = new cargonizer($crg_api_key, $crg_sender_id, $crg_transport_agreement, $crg_product, $crg_product_srv, $crg_consignment_url);
	$crg->requestConsignment($wc_order, $total_weight);
	  
    // Add the sent flag
    update_post_meta( $order->get_id(), '_wc_order_sent_to_cargonizer', true );
  
	// Add a note to order saying its been sent
	if($useSandbox == 1) {
		$message = '[TEST] Order sent to Cargonizer Sandbox. ' . $crg_consignment_url . ' ' . $crg_transport_agreement . ' ' . $crg_product;
	}  else {
		$message = sprintf( __( 'Order sent to Cargonizer by %s', 'my-textdomain' ), wp_get_current_user()->display_name);
	}
    $order->add_order_note( $message);
  
    return 0;
}