<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_DNA_PayPal extends WC_Gateway_DNA_Base_Payment_Component {

	public function __construct() {
		$this->id            = 'dnapayments_paypal';
		$this->icon          = WC_DNA_Payments::plugin_url() . '/assets/img/paypal.svg';
		$this->method_title  = 'PayPal';
		$this->method_description = 'PayPal provided by DNA Payments';
		$this->supports     = array(
            'products', 
            'refunds',
        );

		parent::__construct();
	}
}