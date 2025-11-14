<?php
/**
 * Plugin Name: WooCommerce DNA Payments Gateway
 * Plugin URI: https://www.dnapayments.com
 * Description: Take credit card payments on your store.
 * Version: 4.0.10
 *
 * Author: DNA Payments Integration
 * Author URI: https://www.dnapayments.com
 *
 * Text Domain: woocommerce-gateway-dna
 * Domain Path: /languages/
 *
 * Requires at least: 4.2
 * Tested up to: 6.5
 * WC requires at least: 4.8
 * WC tested up to: 9.4
 * Requires PHP: 7.4
 * PHP tested up to: 8.3
 *
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_DNA_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WC_DNA_MAIN_FILE', __FILE__ );

/**
 * WC DnaPayments payment gateway plugin class.
 *
 * @class WC_DNA_Payments
 */
class WC_DNA_Payments {

	// Main DNA Payments gateway id / name
	public static $id = 'dnapayments';

	// Plugin version
	public static $version = '4.0.10';

	// Wordpress supported min version
	public static $wp_min_version = '4.2';

	// WooCommerce supported min version
	public static $wc_min_version = '4.8';

	// PHP supported min version
	public static $php_min_version = '7.4';

	// Text Domain
	public static $text_domain = 'woocommerce-gateway-dna';

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		add_action( 'before_woocommerce_init', array( __CLASS__, 'before_woocommerce_hpos' ) );
		
		// Load translations at init hook to ensure WordPress core is fully loaded
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );

		// Register DNA scripts globally for blocks compatibility (runs early)
		add_action( 'init', array( __CLASS__, 'register_dna_scripts_globally' ) );

		// This hook is used to execute code after all active plugins have fully loaded, 
		// ensuring that WooCommerce is loaded before executing WooCommerce-specific code.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// This hook registers our PHP classes as a WooCommerce payment gateways
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_block_support' ) );

		// Add custom dom elements after "Place Order" button (Works only in CLASSIC theme)
		add_action('woocommerce_review_order_after_submit', array( __CLASS__, 'add_custom_elements' ));

		// Change "Thank you" ("Order received") page title when order status failed
		add_filter( 'woocommerce_endpoint_order-received_title', array( __CLASS__, 'custom_woocommerce_endpoint_order_received_title' ), 10, 3 );

		// Change "Thank you" ("Order received") page text when order status failed		
		add_filter('woocommerce_thankyou_order_received_text', array( __CLASS__, 'custom_order_received_text' ), 10, 3);		
	}

	public static function before_woocommerce_hpos() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) { 
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	public static function custom_woocommerce_endpoint_order_received_title( $title, $endpoint, $action ) {
		if ($endpoint == 'order-received') {
			global $wp;

			// Order status is sent via query string because the user might be redirected to the thank you page before the status is updated in the database. 
			$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
			$order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $wp->query_vars['order-received'] ) );

			if ($order_id) {
				$order = wc_get_order($order_id);
				if ( ($order && $order->get_status() == 'failed') || ($status == 'failed')) {
					return __( 'Order failed!', WC_DNA_Payments::$text_domain );
				}
			}
		}
		return $title;
	}

	public static function custom_order_received_text($text, $order) {
		$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
		if ($order && ($order->get_status() == 'failed' || $status == 'failed')) {
			return __( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', WC_DNA_Payments::$text_domain );
		}
		return $text;
	}

	/**
	 * Add the DNA Payments gateways to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {
		$gateways[] = 'WC_DNA_Payments_Gateway';
		$gateways[] = 'WC_Gateway_DNA_GooglePay';
		$gateways[] = 'WC_Gateway_DNA_ApplePay';
		$gateways[] = 'WC_Gateway_DNA_PayPal';

		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {
		require_once 'includes/admin/helpers.php';
		require_once 'includes/client/helpers.php';
		require_once 'includes/utils/class-helper.php';
		require_once 'includes/utils/class-logger.php';
		require_once 'includes/utils/class-ajax-init.php';
		require_once 'includes/utils/class-webhooks-init.php';
		require_once 'includes/utils/class-payment-data-helper.php';
		require_once 'includes/utils/class-auth-data-helper.php';
		require_once 'includes/utils/class-order-helper.php';
		require_once 'includes/utils/class-checkout-validation.php';
		require_once 'includes/utils/class-analytics-helper.php';
		require_once 'includes/utils/class-request-helper.php';
		require_once 'includes/utils/class-config-helper.php';
		require_once 'includes/utils/class-subscription-helper.php';
		require_once 'includes/gateways/abstract-wc-gateway-dnapayments.php';
		require_once 'includes/WC_DNA_Payments_Gateway.php';
		require_once 'includes/gateways/class-wc-gateway-dna-base-component.php';
		require_once 'includes/gateways/class-wc-gateway-dna-googlepay.php';
		require_once 'includes/gateways/class-wc-gateway-dna-applepay.php';
		require_once 'includes/gateways/class-wc-gateway-dna-paypal.php';
		require_once 'includes/admin/handlers.php';
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin absolute path.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 */
	public static function woocommerce_gateway_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-gateway-base-dnapayments-blocks-support.php';
			require_once 'includes/blocks/class-wc-gateway-dnapayments-blocks-support.php';
			require_once 'includes/blocks/class-wc-gateway-dnapayments-googlepay-blocks-support.php';
			require_once 'includes/blocks/class-wc-gateway-dnapayments-applepay-blocks-support.php';
			require_once 'includes/blocks/class-wc-gateway-dnapayments-paypal-blocks-support.php';

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_DNA_Payments_Blocks_Support() );
					$payment_method_registry->register( new WC_Gateway_DNA_Payments_GooglePay_Blocks_Support() );
					$payment_method_registry->register( new WC_Gateway_DNA_Payments_ApplePay_Blocks_Support() );
					$payment_method_registry->register( new WC_Gateway_DNA_Payments_PayPal_Blocks_Support() );
				}
			);
		}
	}

	public static function add_custom_elements() {
		echo '<div class="dnapayments-footer" style="display: none">';
		echo '<p>' . esc_html__( 'Powered by', \WC_DNA_Payments::$text_domain ) . '</p>';
		echo '<img src="' . esc_url( plugins_url( 'assets/img/dnapayments-logo.svg', WC_DNA_MAIN_FILE ) ) . '" alt="' . esc_attr__( 'DNA Payments', \WC_DNA_Payments::$text_domain ) . '" />';
		echo '</div>';
	}

	/**
	 * Register DNA payment scripts globally for WooCommerce Blocks compatibility
	 * This ensures scripts are available when blocks integration needs them
	 */
	public static function register_dna_scripts_globally() {
		wp_register_script( 'dna-payment-api', 'https://pay.dnapayments.com/checkout/payment-api.js' , array(), self::$version, true );
		wp_register_script( 'dna-hosted-fields', 'https://cdn.dnapayments.com/js/hosted-fields/hosted-fields.js' , array(), self::$version, true );
		wp_register_script( 'dna-google-pay', 'https://pay.dnapayments.com/components/google-pay/google-pay-component.js', array('dna-payment-api'), self::$version, true );
		wp_register_script( 'dna-apple-pay', 'https://pay.dnapayments.com/components/apple-pay/apple-pay-component.js', array('dna-payment-api'), self::$version, true );
		wp_register_script( 'dna-paypal', 'https://pay.dnapayments.com/components/paypal/paypal-component.js', array('dna-payment-api'), self::$version, true );
	}

	/**
	 * Load plugin text domain.
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( self::$text_domain, false, self::plugin_abspath() . '/languages' );
	}
}

WC_DNA_Payments::init();
