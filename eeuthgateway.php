<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
Plugin Name: Research Committee of UTH - WooCommerce Gateway
Plugin URI: https://github.com/mafoti/ResearchCommitteeUthWooCommerceGateway
Description: Extends WooCommerce by Adding custom gateway
Version: 1.0
Author: Magda Foti
Author URI: https://github.com/mafoti
*/

// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'gateway_init', 0 );
function gateway_init() {
	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// Include our Gateway Class
	include_once( 'eeuthgateway_class.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'add_mygateway' );
	function add_mygateway( $methods ) {
		$methods[] = 'WC_Gateway_EEUth';
		return $methods;
	}
}