<?php

/**
 * Alipay Payment Gateway
 *
 * @package WooCommerce DNA Payments Gateway
 */

class WC_Gateway_DNA_Alipay extends WC_Gateway_DNA_Base_Payment_Component {

	public function __construct() {
		$this->id                 = 'dnapayments_alipay';
		$this->icon               = WC_DNA_Payments::plugin_url() . '/assets/img/alipay.svg';
		$this->method_title       = 'Alipay';
		$this->method_description = 'Alipay provided by DNA Payments';
		parent::__construct();
	}
}
