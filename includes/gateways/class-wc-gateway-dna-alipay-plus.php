<?php

/**
 * Alipay Plus Payment Gateway
 *
 * @package WooCommerce DNA Payments Gateway
 */

class WC_Gateway_DNA_Alipay_Plus extends WC_Gateway_DNA_Base_Payment_Component {

	public function __construct() {
		$this->id                 = 'dnapayments_alipay_plus';
		$this->icon               = WC_DNA_Payments::plugin_url() . '/assets/img/alipay-plus.svg';
		$this->method_title       = 'Alipay+';
		$this->method_description = 'Alipay+ provided by DNA Payments';
		parent::__construct();
	}
}
