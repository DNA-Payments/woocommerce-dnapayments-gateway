<?php
/**
 * DNA Payments ApplePay Cart and Checkout Blocks Support
 */

/**
 * DNA Payments ApplePay Blocks integration
 *
 * @since 3.0.0
 */
final class WC_Gateway_DNA_Payments_ApplePay_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name       = 'dnapayments_apple_pay';
		$this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments_applepay.asset.php';
		$this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments_applepay.js';
		$this->script_dependencies = array( 'dna-apple-pay' );
	}
}
