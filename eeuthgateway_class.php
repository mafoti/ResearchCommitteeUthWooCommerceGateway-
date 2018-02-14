<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 *
 * @class       WC_Gateway_EEUth
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      Magda Foti
 */
class WC_Gateway_EEUth extends WC_Payment_Gateway {
	

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
		//$icon = WC_HTTPS::force_https_url( WC()->plugin_url() . '/includes/gateways/paypal/assets/images/paypal.png' );
        $this->id                 = 'eeuth';
        $this->icon               = apply_filters( 'woocommerce_cod_icon', '' );
        $this->method_title       = __( 'EE Uth', 'woocommerce' );
        $this->method_description = __( 'EE Uth web payment system.', 'woocommerce' );
        $this->has_fields         = false;

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->instructions       = $this->get_option( 'instructions', $this->description );
		$this->ConferenceId       = $this->get_option('ConferenceId');
		
		$this->EEUthUrl = "http://ee.uth.gr/conference/registration.php";

        // Customer Emails
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		
		//Actions
		add_action('woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_'. $this->id, array( $this, 'thankyou_page' ) );
		// Payment listener/API hook
		add_action('woocommerce_api_wc_gateway_'. $this->id, array($this, 'check_response'));
    }
	
		/**
	 * Check if this gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		return true;
	}
    
	 /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
    	$shipping_methods = array();

    	if ( is_admin() )
	    	foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
		    	$shipping_methods[ $method->id ] = $method->get_title();
	    	}

    	$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable UTH Research Committee GateWay', 'woocommerce' ),
				'label'       => __( 'Enabled', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Πληρωμή με Κάρτα', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
				'default'     => __( 'Πληρωμή με Κάρτα μέσω του συστήματος πληρωμών του Πανεπιστημίου Θεσσαλίας', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
				'default'     => __( 'Πληρωμή με Κάρτα', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'ConferenceId' => array(
                    'title' => __('Conference ID', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Enter The Conference ID', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true
			)
 	   );
    }
	
	
	protected function get_eeuth_args( $order, $uniqid) {
		
		$return = WC()->api_request_url( 'WC_Gateway_EEUth' );
		
		$args = array(
			'c'           => $this->ConferenceId,
			'a'           => wc_format_decimal($order->get_total(), 2, false),
			'n'           => $order->get_formatted_billing_full_name(),
			'r'           => $uniqid . 'EEUth' .  ( ( WC()->version >= '3.0.0' ) ? $order->get_id() : $order->id ),
			'payerEmail'  => ( WC()->version >= '3.0.0' ) ? $order->get_billing_email() : $order->billing_email
		);
		
		$args = array_merge($args, array(
			'confirmUrl' => add_query_arg( 'confirm', ( WC()->version >= '3.0.0' ) ? $order->get_id() : $order->id , $return),
			'cancelUrl'  => add_query_arg( 'cancel', ( WC()->version >= '3.0.0' ) ? $order->get_id() : $order->id , $return), 
		));
				
		return apply_filters( 'woocommerce_eeuth_args', $args , $order );
	}
	
	/**
	* Output for the order received page.
	* */
	public function receipt_page($order_id) {
		echo '<p>' . __('Thank you - your order is now pending payment. Please click the button below to proceed.', 'woocommerce') . '</p>';
		$order = wc_get_order( $order_id );
		$uniqid = uniqid();
						
		$form_data = $this->get_eeuth_args($order, $uniqid);

		$html_form_fields = array();
		foreach ($form_data as $key => $value) {
			$html_form_fields[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr($value).'" />';
		}
		
		?>

		<form id="eeUthForm" name="eeUthForm" method="GET" action="<?php echo $this->EEUthUrl ?>" accept-charset="UTF-8" >
			<?php foreach($html_form_fields as $field)
				echo $field;
			?>
			
			<input type="submit" class="button alt" id="submit_twocheckout_payment_form" value="<?php echo __( 'Συνέχεια στο σύστημα πληρωμών του Πανεπιστημίου Θεσσαλίας', 'woocommerce' ) ?>" /> 
			<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() )?>"><?php echo __( 'Ακύρωση Πληρωμής', 'woocommerce' )?></a>
			
		</form>		
		<?php
		
		
		$order->update_status( 'pending', __( 'Sent request to Research Committee with orderID: ' . $form_data['orderid'] , 'woocommerce' ) );
	}
    
    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		 return array(
		 	'result' 	=> 'success',
		 	'redirect'	=> $order->get_checkout_payment_url( true ) // $this->get_return_url( $order )
		);
	}
	
	/**
		* Verify a successful Payment!
	* */
	public function check_response() { 
		
		
		exit();
	}

    /**
     * Output for the order received page.
     */
	public function thankyou_page() {
		if ( $this->instructions ) {
        	echo wpautop( wptexturize( $this->instructions ) );
		}
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}
}
