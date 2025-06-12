<?php

use WCPG_DNA_Payments\Utils\Helper;

class WC_Gateway_DNA_Base_Payment_Component extends WC_Payment_Gateway {
    /**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields = true;

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
        $this->supports     = array( 'products', 'refunds' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
               'title'       => __( 'Enable/Disable', WC_DNA_Payments::$text_domain ),
               'label'       => __( 'Enabled ' . $this->method_title, WC_DNA_Payments::$text_domain ),
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

    // Returns settings data used in frontend (js file)
    public function get_settings_for_frontend() {
        return array();
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
        $dnapayments_gateway = WC()->payment_gateways->payment_gateways()[WC_DNA_Payments::$id];
        return $dnapayments_gateway->process_refund( $order_id, $amount, $reason );
    }

    /**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        $dnapayments_gateway = WC()->payment_gateways->payment_gateways()[WC_DNA_Payments::$id];
        return $dnapayments_gateway->process_payment( $order_id );
	}

    /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		global $wp;
		ob_start();

        $description = $this->get_description();
        if ( $description ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }

        echo '<div id="' . esc_attr( $this->id . '_container' ) . '"></div>';

        ob_end_flush();
    }

}
