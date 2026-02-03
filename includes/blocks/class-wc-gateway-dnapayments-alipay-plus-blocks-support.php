<?php
/**
 * DNA Payments Alipay Plus Cart and Checkout Blocks Support
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * DNA Payments Alipay Plus Blocks integration
 *
 * @since 4.2.0
 */
final class WC_Gateway_DNA_Payments_Alipay_Plus_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {

	public function __construct() {
		$this->name       = 'dnapayments_alipay_plus';
		$this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments_alipay_plus.asset.php';
		$this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments_alipay_plus.js';
	}
}
