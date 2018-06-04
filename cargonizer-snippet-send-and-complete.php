add_action( 'woocommerce_order_actions', 'add_order_meta_box_actions');
function add_order_meta_box_actions( $actions ) {
	$actions['create_cargonizer_consignment'] = __( 'Send and complete', 'my-textdomain' );
	$actions['create_cargonizer_consignment_test'] = __( 'Send and complete [TEST]', 'my-textdomain' );
	return $actions;
}

add_action( 'woocommerce_order_action_create_cargonizer_consignment', 'process_create_cargonizer_consignment_action' );
function process_create_cargonizer_consignment_action( $order ) {
	cargonizer_request_consignment($order);
    // $order->update_status('completed');
}

add_action( 'woocommerce_order_action_create_cargonizer_consignment_test', 'process_create_cargonizer_consignment_test_action' );
function process_create_cargonizer_consignment_test_action( $order ) {
	cargonizer_request_consignment($order, 1);
}

add_action( 'woocommerce_order_status_completed', 'mb_handle_order_status_completed' );
function mb_handle_order_status_completed( $order_id ) { 
    $order = new WC_Order( $order_id );
    cargonizer_request_consignment($order, 1);
}

/**
* Call cargonizer service
*/
function cargonizer_request_consignment( $order, $useSandbox = 0 ) {
  	$order_emballage_weight = 150;
	$crg_api_key = "";
	$crg_sender_id = "";
	$crg_consignment_url = "http://cargonizer.no/consignments.xml";
	$crg_transport_url = "http://cargonizer.no/transport_agreements.xml";
	$crg_transport_agreement = "9389";
	
	if($useSandbox == 1) {
		$crg_api_key = "";
		$crg_sender_id = "";
		$crg_consignment_url = "http://sandbox.cargonizer.no/consignments.xml";
		$crg_transport_url = "http://sandbox.cargonizer.no/transport_agreements.xml";
		$crg_transport_agreement = "1061";
	}
	
	$debug = 0;
	
	$wc_order = new WC_Order($order->ID);
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
  
	$crg = new cargonizer($crg_api_key, $crg_sender_id, $crg_transport_agreement, $crg_consignment_url);
	$response = $crg->requestConsignment($wc_order, $total_weight, $debug);
	
	// TODO: check response for errors and 40x, 50x => Add error note on product (and log etc.)
	
    // Add the sent flag
    update_post_meta( $order->id, '_wc_order_sent_to_cargonizer', true );
  
	// Add a note to order saying its been sent
	if($useSandbox == 1) {
		$message = '[TEST] Order sent to Cargonizer Sandbox';
	}  else {
		$message = sprintf( __( 'Order sent to Cargonizer by %s', 'my-textdomain' ), wp_get_current_user()->display_name);
	}
    $order->add_order_note( $message );
}