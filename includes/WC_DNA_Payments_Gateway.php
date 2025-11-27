<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( is_readable( WC_DNA_PLUGIN_PATH . '/vendor/autoload.php' ) ) {
    require WC_DNA_PLUGIN_PATH . '/vendor/autoload.php';
}

require_once WC_DNA_PLUGIN_PATH . '/includes/admin/fields.php';

use WCPG_DNA_Payments\Utils\Helper;

class WC_DNA_Payments_Gateway extends WC_Gateway_Abstract_Dnapayments {

    /**
	 * Whether the gateway is visible for non-admin users.
	 * @var boolean
	 *
	 */
	protected $hide_for_non_admin_users;
    /**
	 * Whether to enable AJAX order status updates.
	 * @var boolean
	 *
	 */
	public $enable_ajax_order_status_update;
    /**
	 * True if the gateway shows fields on the checkout.
	 *
	 * @var bool
	 */
	public $has_fields;
    /**
     * @var bool
     */
    public $is_test_mode;
    /**
     * @var string
     */
    public $terminal;
    /**
     * @var string
     */
    public $client_id;
    /**
     * @var string
     */
    public $client_secret;
    /**
     * @var boolean
     */
    public $enabled_saved_cards;
    /**
     * @var string
     */
    public $integration_type;
    /**
     * @var \DNAPayments\DNAPayments
     */
    public $dnaPayment;

    /**
     * @var \WCPG_DNA_Payments\Utils\Logger
     */
    public $logger;

    /**
     * @var \WCPG_DNA_Payments\Utils\CheckoutValidation
     */
    public $checkoutValidation;

    /**
     * @var \WCPG_DNA_Payments\Utils\PaymentDataHelper
     */
    public $paymentDataHelper;

    /**
     * @var \WCPG_DNA_Payments\Utils\AuthDataHelper
     */
    public $authDataHelper;

    /**
     * @var \WCPG_DNA_Payments\Utils\OrderHelper
     */
    public $orderHelper;

    /**
     * @var \WCPG_DNA_Payments\Utils\AjaxInit
     */
    public $ajaxInit;

    /**
     * @var \WCPG_DNA_Payments\Utils\WebhooksInit
     */
    public $webhooksInit;

	/**
	 * @var \WCPG_DNA_Payments\Utils\AnalyticsHelper
	 */
    public $analyticsHelper;

    /**
	 * @var \WCPG_DNA_Payments\Utils\RequestHelper
	 */
    public $requestHelper;

    /**
     * @var \WCPG_DNA_Payments\Utils\ConfigHelper
     */
    public $configHelper;

    /**
     * Subscription helper instance
     * @var \WCPG_DNA_Payments\Utils\SubscriptionHelper
     */
    public $subscriptionHelper;

    public function __construct() {

        $this->id = 'dnapayments';
        $this->icon = WC_DNA_Payments::plugin_url() . '/assets/img/scheme.svg';
        $this->method_title = 'DNA Payments Gateway';
        $this->method_description = 'Accept card payments via DNA Payments.';

        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->hide_for_non_admin_users = $this->get_option( 'hide_for_non_admin_users' );
        $this->enable_ajax_order_status_update = $this->get_option( 'enable_ajax_order_status_update' );
        $this->is_test_mode = 'yes' === $this->get_option( 'is_test_mode' );
        $integration_type = $this->get_option( 'integration_type' );
        $this->integration_type = $integration_type === 'hosted-fields' ? 'seamless' : $integration_type;
        $this->has_fields = $this->integration_type == 'seamless';
        $this->enabled_saved_cards = 'yes' === $this->get_option( 'enabled_saved_cards' );
        $this->client_id = $this->is_test_mode ? $this->get_option( 'test_client_id' ) : $this->get_option( 'client_id' );
        $this->client_secret = $this->is_test_mode ? $this->get_option( 'test_client_secret' ) : $this->get_option( 'client_secret' );
        $this->terminal = $this->is_test_mode ? $this->get_option( 'test_terminal' ) : $this->get_option('terminal');

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
        // Enqueue scripts for classic checkout
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts') );

        $this->dnaPayment = new DNAPayments\DNAPayments($this->get_config());
        $this->logger = new WCPG_DNA_Payments\Utils\Logger( $this );
        $this->checkoutValidation = new WCPG_DNA_Payments\Utils\CheckoutValidation();
        $this->paymentDataHelper = new WCPG_DNA_Payments\Utils\PaymentDataHelper( $this );
        $this->authDataHelper = new WCPG_DNA_Payments\Utils\AuthDataHelper( $this );
        $this->orderHelper = new WCPG_DNA_Payments\Utils\OrderHelper( $this );
        $this->ajaxInit = new WCPG_DNA_Payments\Utils\AjaxInit( $this );
        $this->webhooksInit = new WCPG_DNA_Payments\Utils\WebhooksInit( $this );
        $this->analyticsHelper = new WCPG_DNA_Payments\Utils\AnalyticsHelper($this);
        $this->requestHelper = new WCPG_DNA_Payments\Utils\RequestHelper($this);
        $this->configHelper = new WCPG_DNA_Payments\Utils\ConfigHelper($this);
        $this->subscriptionHelper = new \WCPG_DNA_Payments\Utils\SubscriptionHelper( $this );

        // Define gateway support features
        $this->supports = array( 
            'products', 
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'multiple_subscriptions'
        );

        if ( $this->enabled_saved_cards ) {
            array_push($this->supports, 'tokenization' );
        }
    }

    public function get_config() {
        return [
            'isTestMode' => $this->is_test_mode,
            'scopes' => [
                'allowHosted' => true,
                'allowEmbedded' => in_array($this->integration_type, ['embedded', 'seamless']),
                'allowSeamless' => $this->integration_type == 'seamless'
            ]
        ];
    }

    public function save_payment_method_requested() {
		$payment_method = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : $this->id;

		return isset( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] ) && ! empty( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] );
	}

    /**
	 * Check if this gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		// First check parent availability
		if (!parent::is_available()) {
			return false;
		}

		// Check visibility for non-admin users
		if ('yes' === $this->hide_for_non_admin_users) {
			$is_admin = current_user_can('manage_options');
			$has_dna_email = Helper::current_user_has_dna_email_domain();
			
			if (!$is_admin && !$has_dna_email) {
				return false;
			}
		}

		return true;
	}

    /**
     * Return the gateway's icon.
     *
     * @return string
     */
    public function get_icon() {
        $icon_str = $this->configHelper->get_payment_gateway_icons();
        if ( empty( $icon_str ) ) {
            return parent::get_icon();
        }

        return apply_filters( 'woocommerce_gateway_icon', $icon_str, $this->id );
    }

    /**
     * Cancel a charge.
     *
     * @param  WC_Order $order
     * @return bool
     */
    public function process_cancel($order) {
        try {
            $result = $this->dnaPayment->cancel([
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'terminal' => $this->terminal,
                'invoiceId' => strval($order->get_order_number()),
                'amount' => $order->get_total(),
                'currency' => $order->get_currency(),
                'transaction_id' => $order->get_transaction_id()
            ]);

            return !empty($result) && $result['success'];
        } catch (Exception $e) {
            $this->logger->error('Error in process_cancel; Code: ' . $e->getCode() . '; Message: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Can the order be refunded via DNA
     *
     * @param  WC_Order $order Order object.
     * @return bool
     */
    public function can_refund_order( $order ) {
        $paymentMethod = $order->get_meta( 'payment_method', true );
        if($paymentMethod === 'paypal' && !WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order)) {
            return false;
        }
        return true;
    }

    /**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return bool|\WP_Error True or false based on success, or a WP_Error object.
	 */
    public function process_refund( $order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);

        if ( ! $order ) {
            return false;
        }

        if ( ! $order->get_transaction_id() ) {
            return false;
        }

        if( $order->get_meta('is_finished_payment', true) === 'no' ) {
            if ($order->get_total() == $amount) {
                return $this->process_cancel($order);
            }

            $message = __( 'Partial cancellation of this transaction is not allowed.', 'woocommerce-gateway-dna' );
			throw new Exception( $message );
        }

        try {
            $result = $this->dnaPayment->refund([
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'terminal' => $this->terminal,
                'invoiceId' => strval($order->get_order_number()),
                'amount' => $amount,
                'currency' => $order->get_currency(),
                'transaction_id' => $order->get_transaction_id()
            ]);

            return !empty($result) && $result['success'];
        } catch (Exception $e) {
            $this->logger->error('Error in process_refund; Code: ' . $e->getCode() . '; Message: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    public function init_form_fields(){
        $this->form_fields = get_dnapayments_admin_fields();
    }

    /**
     * Returns the settings data exposed to the frontend JavaScript.
     *
     * Overrides the method from WC_Gateway_Abstract_Dnapayments.
     *
     * @return array Associative array of settings used by frontend scripts.
     */
    public function get_settings_for_frontend() {
        $current_user_id = get_current_user_id();
        $is_guest = !isset($current_user_id) || empty($current_user_id) || $current_user_id === '0';

        return array(
            'is_test_mode' => $this->is_test_mode,
            'integration_type' => $this->integration_type,
            'temp_token' => $this->authDataHelper->get_temp_token(),
            'terminal_id' => $this->terminal,
            'terminal_config' => $this->configHelper->get_terminal_config(),
            'transaction_type' => $this->configHelper->get_transaction_type(),
            'current_currency_code' => get_woocommerce_currency(),
            'available_gateways' => array_keys(WC()->payment_gateways->get_available_payment_gateways()),
            'allow_saving_cards' => $this->enabled_saved_cards && !$is_guest,
            'icons' => $this->configHelper->get_payment_gateway_icon_urls(),
            'available_schemes' => $this->configHelper->available_schemes,
            'card_scheme_icon_path' => WC_DNA_Payments::plugin_url() . '/assets/img/schemes',
            'send_callback_every_failed_attempt' => $this->get_option( 'failed_attempts_limit' ),
            'cards' => WC_DNA_Payments_Order_Client_Helpers::getCardTokens( $current_user_id, $this->id ),
            'placeOrderButtonText' => $this->get_option( 'placeOrderButtonText', '' ),
            'nonces' => $this->ajaxInit->get_nonces(),
            'page' => $this->paymentDataHelper->get_current_payment_page(),
        );
    }

    /**
     * Enqueue payment scripts for classic checkout
     */
    public function payment_scripts() {

        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page()) {
            return;
        }

        if ( 'no' === $this->enabled ) {
            return;
        }

        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            return;
        }

        wp_register_style( 'dna_styles', plugins_url( 'assets/css/dna-payment.css', WC_DNA_MAIN_FILE ), [], \WC_DNA_Payments::$version );
		wp_enqueue_style( 'dna_styles' );


        if (is_add_payment_method_page()) {
            wp_register_script('woocommerce_dna_payment', plugins_url('assets/js/classic/dna-payments-add-card.js', WC_DNA_MAIN_FILE), array('jquery', 'dna-payment-api', 'dna-hosted-fields') , \WC_DNA_Payments::$version, true);

            $dna_params = $this->get_settings_for_frontend();
        } else {
            wp_register_script('woocommerce_dna_payment', plugins_url('assets/js/classic/dna-payments.js', WC_DNA_MAIN_FILE), array('jquery', 'dna-hosted-fields', 'dna-google-pay', 'dna-apple-pay', 'dna-paypal', 'dna-payment-api') , \WC_DNA_Payments::$version, true);


            $order_id = absint(get_query_var('order-pay'));
            $dna_params = array_merge(
                array(
                    'order_id' => $order_id,
                    'page' => $order_id && isset($_GET['change_payment_method']),
                ),
                $this->get_settings_for_frontend()
            );
        }

        wp_localize_script( 'woocommerce_dna_payment', 'wc_dna_params', apply_filters( 'wc_dna_params', $dna_params ) );
        wp_enqueue_script('woocommerce_dna_payment');
    }

    public function validate_fields() {

        // Check if this is the Pay for Order page
        if (isset($_GET['pay_for_order']) && isset($_GET['key'])) {
            // Skip validation on Pay for Order page
            return true;
        }

        // Check if this is the Add payment method page
        if (isset($_REQUEST['woocommerce_add_payment_method'])) {
            // Skip validation on Pay for Order page
            return true;
        }

        $checkout = WC()->checkout();
        $errors = new \WP_Error();
        $posted_data = $checkout->get_posted_data();

        $this->checkoutValidation->validate_billing_field_lengths( $posted_data, $errors );
        foreach ($errors->get_error_messages() as $message) {
            Helper::add_notice($message);
        }

        return ! $errors->has_errors();
    }

    public function process_payment( $order_id ) {
        $this->analyticsHelper->send_analytics();

        // Clean up all output buffers to remove unexpected output
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        try {
            $order = wc_get_order( $order_id );
            $result_string = Helper::get_posted_value('wc-' . $this->id . '-result');

            // Check if we are on the "Pay for Order" page
            $is_pay_for_order = is_wc_endpoint_url( 'order-pay' );
            // Check if the user is changing the payment method for subscription
            $is_change_payment_method = $is_pay_for_order && isset($_GET['change_payment_method']);
            // Check if this is a block-based checkout (REST API request)
            $is_block_checkout = WC()->is_rest_api_request();


            // This block is used for shortcode-based checkout
            if ( empty ($result_string) ) {
                $auth_data = $this->authDataHelper->get_auth_data_from_order( $order );
                $payment_data = $this->paymentDataHelper->get_payment_data_from_order( $order, $this->save_payment_method_requested() );

                if ( ! $is_block_checkout ) {
                    $posted_data = WC()->checkout()->get_posted_data();

                    // merge billing and shipping address
                    $payment_data['customerDetails']['billingAddress'] = Helper::merge_if_empty(
                        $this->paymentDataHelper->get_address_from_post_data( $posted_data, 'billing' ),
                        $payment_data['customerDetails']['billingAddress']
                    );

                    $payment_data['customerDetails']['deliveryDetails']['deliveryAddress'] = Helper::merge_if_empty(
                        $this->paymentDataHelper->get_address_from_post_data( $posted_data, 'shipping' ),
                        $payment_data['customerDetails']['deliveryDetails']['deliveryAddress']
                    );
                }

                return array(
                    'result'        => 'success',
                    'paymentData'   => json_encode($payment_data),
                    'auth'          => json_encode($auth_data),
                    'token'         => $auth_data['access_token'],
                ); 
            }

            $result = $this->orderHelper->process_payment_using_payment_result( $order, $result_string, Helper::get_current_user_id(), 'process_payment' );

            if ( $result['status'] === 'failed' ) {
                throw new \Exception( $result['message'] );
            }

            // Return thankyou redirect
            return array(
                'result' 	=> 'success',
                'redirect'	=> $this->paymentDataHelper->get_return_url_from_order( $order )
            );
        } catch (Exception $e) {
            $this->logger->error('Error in process_payment: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());

            return array(
                'result' => 'failure',
                'messages' => $e->getMessage(),
            );
        }
    }

    /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		ob_start();

        $description = $this->get_description();
        if ( $description ) {
            echo wp_kses_post( wpautop( wptexturize( $description ) ) );
        }

        if ($this->integration_type == 'seamless') {
            echo '<div id="wc-' . esc_attr( $this->id ) . '-form">';

            $display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->enabled_saved_cards;

            if ( $display_tokenization ) {
                $this->tokenization_script();
                $this->saved_payment_methods();
            }

            $this->elements_form();

            if ( $display_tokenization && ! is_add_payment_method_page() ) {
                $this->save_payment_method_checkbox();
            }

            echo '</div>';
        }

		ob_end_flush();
	}

    /**
	 * Renders the Hosted fields form.
	 */
	public function elements_form() {
		?>

        <div id="dna-card-cvc-token-container" class="form-row" style="display: none">
            <label for="dna-card-cvc-token"><?php esc_html_e( 'Card code (CVC)', 'woocommerce-gateway-dna' ); ?></label>
            <div id="dna-card-cvc-token" class="wc-classic-dnapayments-gateway-input"></div>
        </div>

		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>

            <div class="wc-classic-dnapayments-card-elements">
                <div class="wc-classic-dnapayments-gateway-container">
                    <label for="dna-card-number"><?php esc_html_e( 'Card number', 'woocommerce-gateway-dna' ); ?></label>
                    <div class="wc-classic-dnapayments-gateway-input-container">
                    <div id="dna-card-number" class="wc-classic-dnapayments-gateway-input"></div>
                        <img id="dna-card-selected" class="wc-dnapayments-card-selected" src="<?php echo esc_url( WC_DNA_Payments::plugin_url() . '/assets/img/schemes/none.svg' ); ?>" alt="<?php esc_attr_e( 'Selected card', \WC_DNA_Payments::$text_domain ); ?>" />
                    </div>
                </div>

                <div class="wc-classic-dnapayments-gateway-container">
                    <label for="dna-card-name"><?php esc_html_e( 'Cardholder name', 'woocommerce-gateway-dna' ); ?></label>
                    <div id="dna-card-name" class="wc-classic-dnapayments-gateway-input"></div>
                </div>

                <div class="wc-classic-dnapayments-gateway-container wc-classic-dnapayments-card-element-small">
                    <label for="dna-card-exp"><?php esc_html_e( 'Expiry date', 'woocommerce-gateway-dna' ); ?></label>
                    <div id="dna-card-exp" class=" wc-classic-dnapayments-gateway-input"></div>
                </div>

                <div class="wc-classic-dnapayments-gateway-container wc-classic-dnapayments-card-element-small">
                    <label for="dna-card-cvc"><?php esc_html_e( 'Card code (CVC)', 'woocommerce-gateway-dna' ); ?></label>
                    <div id="dna-card-cvc" class="wc-classic-dnapayments-gateway-input"></div>
                </div>
            </div>

            <div class="clear"></div>

			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>

            <div class="clear"></div>
		</fieldset>

        <!-- Used to display form errors -->
        <div class="dna-source-errors" role="alert"></div>

		<?php
	}
}
