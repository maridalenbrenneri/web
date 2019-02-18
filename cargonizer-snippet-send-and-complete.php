add_filter( 'woocommerce_order_actions', 'add_order_meta_box_actions');
function add_order_meta_box_actions( $actions ) {
	$actions['send_to_cargonizer'] = __( '-= POSTNORD + Complete =-', 'my-textdomain' );
    $actions['send_to_cargonizer_bring'] = __( '-= BRING + Complete =-', 'my-textdomain' );
	return $actions;
}

add_action( 'woocommerce_order_action_send_to_cargonizer', 'process_send_to_cargonizer_action' );
function process_send_to_cargonizer_action( $order ) {
    // cancel request for orders that shouldn't be shipped via Cargonizer
	foreach($order->get_used_coupons() as $item_id => $item_data) {
	  if($item_data === 'nullfrakt2018') {
		$order->add_order_note( 'This order should not be sent to cargonizer, cancelled request.');
		return;
	  }
	}
  
	cargonizer_request_consignment($order, "postnord");
    $order->update_status('completed');
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
  
	cargonizer_request_consignment($order, 'bring');
    $order->update_status('completed');
}

add_filter( "bulk_actions-edit-shop_order", "mb_add_send_to_cargonizer_bulk_action");
function mb_add_send_to_cargonizer_bulk_action ($actions) {
  	// Comment out for now, since it does not work.. 13/1 BA
    // $actions['send_to_cargonizer'] = __( '-= Send to Cargonizer and change status to completed =-', 'my-textdomain' );
    return $actions;
}

add_action( "admin_init", function() {
	
}, 0 );

/**
* Call cargonizer service
*/
function cargonizer_request_consignment( $order, $provider, $useSandbox = 1 ) {
  	$order_emballage_weight = 150;
	$crg_api_key = "";
	$crg_sender_id = "";
	$crg_consignment_url = "https://cargonizer.no/consignments.xml";
	$crg_transport_url = "http://cargonizer.no/transport_agreements.xml";
	$crg_transport_agreement = "";
  	$crg_product = "postnord_parcel_letter_mypack";
  
  	if($provider == 'bring'){
	  $crg_product = 'bring_pa_doren';
	  $crg_transport_agreement = '';
	  
	} else {
	  $crg_product = 'postnord_parcel_letter_mypack';
	  $crg_transport_agreement = '';
	}  
  
	if($useSandbox == 1) {
		$crg_api_key = "";
		$crg_sender_id = "";
		$crg_consignment_url = "https://sandbox.cargonizer.no/consignments.xml";
		$crg_transport_url = "http://sandbox.cargonizer.no/transport_agreements.xml";
	   	
	  	if($provider == 'bring'){
		  $crg_transport_agreement = '';
		  
		} else {
		  $crg_transport_agreement = '';
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
    
	$crg = new cargonizer($crg_api_key, $crg_sender_id, $crg_transport_agreement, $crg_product, $crg_consignment_url);
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
}