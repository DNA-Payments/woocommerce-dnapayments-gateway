<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Gateway_DNA_Base_Payment_Component extends WC_Gateway_Abstract_Dnapayments {
    /**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields = true;
	
	/**
	 * Reference to the main DNA Payments gateway.
	 *
	 * @var WC_DNA_Payments_Gateway
	 */
	protected $dnapayments_gateway;

    public function __construct() {

        $this->init_settings();
        $this->init_form_fields();

        $this->method_description = sprintf(
            /* translators: 1) HTML anchor open tag 2) HTML anchor closing tag */
                __( 'All other general DNA Payments settings can be adjusted %1$shere%2$s ', WC_DNA_Payments::$text_domain ),
                '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dnapayments' ) ) . '">',
                '</a>'
            );

        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->enabled      = $this->get_option( 'enabled' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
               'title'       => __( 'Enable Gateway', WC_DNA_Payments::$text_domain ),
               'label'       => __( 'Enable the ' . $this->method_title, WC_DNA_Payments::$text_domain ),
               'type'        => 'checkbox',
               'description' => '',
               'default'     => 'no'
            ),
            'title' => array(
               'title'       => __( 'Title', WC_DNA_Payments::$text_domain ),
               'type'        => 'text',
               'description' => __( 'This controls the title which the user sees during checkout.', WC_DNA_Payments::$text_domain ),
               'default'     => $this->method_title,
               'desc_tip'    => true,
            ),
            'description' => array(
               'title'       => __( 'Description', WC_DNA_Payments::$text_domain ),
               'type'        => 'text',
               'desc_tip'    => true,
               'description' => __( 'This controls the description which the user sees during checkout.', WC_DNA_Payments::$text_domain ),
               'default'     => '',
            )
         );
    }

    /**
	 * Get the main DNA Payments gateway instance.
	 * Lazy-loads the gateway only when needed to avoid initialization issues.
	 *
	 * @return WC_DNA_Payments_Gateway|null The gateway instance or null if not available
	 */
	protected function get_dnapayments_gateway() {
		if ( null === $this->dnapayments_gateway ) {
			// Make sure WooCommerce is fully loaded
			if ( function_exists( 'WC' ) && WC()->payment_gateways && method_exists( WC()->payment_gateways, 'payment_gateways' ) ) {
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				if ( isset( $payment_gateways[WC_DNA_Payments::$id] ) ) {
					$this->dnapayments_gateway = $payment_gateways[WC_DNA_Payments::$id];
				}
			}
		}
		return $this->dnapayments_gateway;
	}

    /**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
    public function process_refund( $order_id, $amount = null, $reason = '') {
        $gateway = $this->get_dnapayments_gateway();
        if ( $gateway ) {
            return $gateway->process_refund( $order_id, $amount, $reason );
        }
        return new WP_Error( 'dnapayments_error', __( 'Unable to process refund: Main payment gateway not available.', WC_DNA_Payments::$text_domain ) );
    }

    /**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        $gateway = $this->get_dnapayments_gateway();
        if ( $gateway ) {
            return $gateway->process_payment( $order_id );
        }
        return array(
            'result'   => 'failure',
            'messages' => __( 'Unable to process payment: Main payment gateway not available.', WC_DNA_Payments::$text_domain )
        );
	}

    /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		ob_start();

        $description = $this->get_description();
        if ( $description ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }

        echo '<div id="' . esc_attr( $this->id . '_container' ) . '"></div>';

        ob_end_flush();
    }

    /**
	 * Check if this gateway is available for use.
	 *
	 * First checks if the main DNA Payments gateway is available,
	 * then checks if this specific payment component is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		$gateway = $this->get_dnapayments_gateway();
		if ( ! $gateway || ! $gateway->is_available() ) {
			return false;
		}
		
		return parent::is_available();
	}

}
