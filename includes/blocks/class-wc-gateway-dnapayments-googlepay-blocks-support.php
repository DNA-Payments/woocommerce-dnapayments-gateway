<?php
/**
 * DNA Payments GooglePay Cart and Checkout Blocks Support
 */

/**
 * DNA Payments GooglePay Blocks integration
 *
 * @since 3.0.0
 */
final class WC_Gateway_DNA_Payments_GooglePay_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name       = 'dnapayments_google_pay';
		$this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments_googlepay.asset.php';
		$this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments_googlepay.js';
		$this->script_dependencies = array( 'dna-google-pay' );
	}
}
