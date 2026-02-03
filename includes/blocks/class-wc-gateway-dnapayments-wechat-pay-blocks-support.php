<?php
/**
 * DNA Payments WeChat Pay Cart and Checkout Blocks Support
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * DNA Payments WeChat Pay Blocks integration
 *
 * @since 4.2.0
 */
final class WC_Gateway_DNA_Payments_WeChat_Pay_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {

	public function __construct() {
		$this->name       = 'dnapayments_wechat_pay';
		$this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments_wechat_pay.asset.php';
		$this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments_wechat_pay.js';
	}
}
