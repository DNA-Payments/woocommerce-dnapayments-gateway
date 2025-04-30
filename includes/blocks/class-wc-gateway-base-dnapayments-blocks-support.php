<?php
/**
 * DNA Payments Cart and Checkout Blocks Support
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * DNA Payments Blocks integration
 *
 * @since 3.0.0
 */
abstract class WC_Gateway_Base_DNA_Payments_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Payment_Gateway
	 */
	private $gateway;

	/**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Path to assets
	 *
	 * @var string
	 */
	protected $asset_path;

	/**
	 * URL of script
	 *
	 * @var string
	 */
	protected $script_url;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		if ( ! isset( $this->script_url ) ) {
			return array();
		}

		$asset_path   = $this->asset_path;
		$version      = WC_DNA_Payments::$version;
		$script_name  = 'wc-' . $this->name . '-blocks-integration';
		$dependencies = is_admin() || is_login() ? [] : [ 'dna-payment-api', 'dna-hosted-fields', 'dna-google-pay', 'dna-apple-pay'];

		if ( file_exists( $asset_path ) ) {
			$asset = require $asset_path;

			if ( is_array( $asset ) )  {
				if ( isset( $asset['version'] ) ) {
					$version = $asset['version'];
				}

				if ( isset( $asset['dependencies'] ) ) {
					$dependencies = array_merge( $dependencies, $asset['dependencies']);
				}
			}
		}

		wp_register_script(
			$script_name,
			$this->script_url,
			$dependencies,
			$version,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				$script_name,
				WC_DNA_Payments::$text_domain,
				WC_DNA_Payments::plugin_abspath() . 'languages/'
			);
		}

		return array( $script_name );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array_merge(
			array(
				'title'				=> $this->get_setting( 'title' ),
				'description'		=> $this->get_setting( 'description' ),
				'supports'          => $this->get_supported_features(),
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'nonces' 			=> [],
				'icon' 				=> $this->gateway->icon
			),
			$this->gateway->get_settings_for_frontend()
		);
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		return $this->gateway->supports;
	}

}
