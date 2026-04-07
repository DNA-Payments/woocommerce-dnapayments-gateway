<?php
/**
 * DNA Payments Cart and Checkout Blocks Support
 */

/**
 * DNA Payments Blocks integration
 *
 * @since 3.0.0
 */
final class WC_Gateway_DNA_Payments_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name       = 'dnapayments';
		$this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments.asset.php';
		$this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments.js';
	}

	/**
	 * Initializes the payment method.
	 */
	public function initialize() {
		parent::initialize();

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_before',
			function() {
				add_filter( 'woocommerce_saved_payment_methods_list', array( $this, 'add_saved_payment_methods' ), 10, 2 );
			}
		);
		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				remove_filter( 'woocommerce_saved_payment_methods_list', array( $this, 'add_saved_payment_methods' ), 10, 2 );
			}
		);
	}

	/**
	 * Manually add DNA Payments save tokens to the saved payment methods list.
	 *
	 * @param array $saved_methods The saved payment methods.
	 * @param int   $customer_id The customer ID.
	 * @return array $saved_methods Modified saved payment methods.
	 */
	public function add_saved_payment_methods( $saved_methods, $customer_id ) {
		$name   = 'dnapayments';
		$gateways = WC()->payment_gateways->payment_gateways();
		$gateway   = isset($gateways[ $name ]) ? $gateways[ $name ] : null;

		$hide_dna_cards = ( isset( $gateway ) && ( ! $gateway->enabled_saved_cards || $gateway->integration_type !== 'seamless' ) );
		$wallet_gateways = array( 'dnapayments_google_pay', 'dnapayments_apple_pay', 'dnapayments_paypal' );

		$new_saved_cards = [];

		foreach ( $saved_methods[ 'cc' ] as $item ) {
			$item_gateway = isset($item['method']['gateway']) ? $item['method']['gateway'] : '';

			if ( in_array( $item_gateway, $wallet_gateways ) ) {
				continue;
			}

			if ( $hide_dna_cards && $item_gateway === $name ) {
				continue;
			}

			array_push($new_saved_cards, $item);
		}

		$saved_methods[ 'cc' ] = $new_saved_cards;
		
		return $saved_methods;
	}
}
