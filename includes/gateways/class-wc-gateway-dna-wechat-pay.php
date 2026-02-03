<?php

/**
 * WeChat Pay Payment Gateway
 *
 * @package WooCommerce DNA Payments Gateway
 */

class WC_Gateway_DNA_WeChat_Pay extends WC_Gateway_DNA_Base_Payment_Component {

	public function __construct() {
		$this->id                 = 'dnapayments_wechat_pay';
		$this->icon               = WC_DNA_Payments::plugin_url() . '/assets/img/wechat-pay.svg';
		$this->method_title       = 'WeChat Pay';
		$this->method_description = 'WeChat Pay provided by DNA Payments';
		parent::__construct();
	}
}
