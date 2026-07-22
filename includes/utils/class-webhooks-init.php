<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WCPG_DNA_Payments\Utils\Helper;

class WebhooksInit {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    private static $hooks_initialized = false;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;
        $this->init_hooks();
    }

    private function init_hooks() {
        if ( self::$hooks_initialized ) {
            return;
        }

        add_action( 'rest_api_init', array( $this, 'register_routes' ));
        add_action( 'woocommerce_api_' . $this->gateway->id, array( $this, 'handle_payment_return_page' ) );
        add_action( 'woocommerce_before_thankyou', array( $this, 'handle_order_received_cart_cleanup' ), 5 );
        add_action( 'shutdown', array( $this, 'prevent_stale_cart_replay_on_shutdown' ), -1 );

        self::$hooks_initialized = true;
    }

    public function prevent_stale_cart_replay_on_shutdown() {
        $this->prevent_stale_cart_replay();
    }

    private function prevent_stale_cart_replay() {
        if (
            ( is_admin() && ! wp_doing_ajax() )
            || wp_doing_cron()
            || ! function_exists( 'WC' )
            || ! WC()->cart
            || WC()->cart->is_empty()
        ) {
            return;
        }

        Helper::empty_cart_if_stale_replay_request();
    }

    /**
     * Empty the cart on the order-received (thank-you) page once a DNA order is confirmed paid.
     * Backstop for the checkpoints that run earlier in the async payment flow.
     */
    public function handle_order_received_cart_cleanup( $order_id ) {
        $order = wc_get_order( absint( $order_id ) );

        if ( ! $order || ! Helper::is_dna_payments_order( $order ) ) {
            return;
        }

        if ( Helper::is_paid_status( $order->get_status() ) ) {
            Helper::empty_cart_and_persist();
        }
    }

    public function register_routes() {
        register_rest_route( $this->gateway->id, 'success', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'success_webhook'),
            'permission_callback' => array( $this, 'validate_webhook_permission' )
        ) );

        register_rest_route( $this->gateway->id, 'success-add-card', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'success_webhook_add_card'),
            'permission_callback' => array( $this, 'validate_webhook_permission' )
        ) );

        register_rest_route( $this->gateway->id, 'change-payment-method', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'subscription_change_payment_method'),
            'permission_callback' => array( $this, 'validate_webhook_permission' )
        ) );

        register_rest_route( $this->gateway->id, 'failure', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'fail_webhook'),
            'permission_callback' => array( $this, 'validate_webhook_permission' )
        ) );
    }
    
    /**
     * Validates that the incoming webhook request is authorized
     * 
     * @param \WP_REST_Request $request The request object
     * @return bool Whether the request is authorized
     */
    public function validate_webhook_permission( $request ) {
        // Allow requests only if they have the required headers or parameters
        if ( empty( $request->get_params() ) ) {
            $this->gateway->logger->error('Webhook permission denied: Empty parameters');
            return false;
        }

        // Check for signature parameter
        $params = $request->get_params();
        if ( ! isset( $params['signature'] ) || empty( $params['signature'] ) ) {
            $this->gateway->logger->error('Webhook permission denied: Missing signature parameter');
            return false;
        }

        // Note: Signature validation is intentionally handled in the webhook handler methods
        // rather than at the permission callback level. This allows us to receive the webhook
        // and properly log invalid signatures while still maintaining security.
        // The actual signature validation occurs in validate_webhook_input method.
        return true;
    }

    public function success_webhook( \WP_REST_Request $input ) {
        $name = 'Success webhook';
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $data, true );

            $order = $this->parse_webhook_order( $data );
    
            $order_id = $order->get_id();
            $status = $order->get_status();
            $log_context = $this->get_webhook_log_context( $data );

            $this->gateway->logger->info( $name . ' started: order ' . $order_id . ', status ' . $status, $log_context );

            $result = $this->gateway->orderHelper->process_payment( $order, $data, $name );

            $new_status = $result['status'] ?? '';
            $result_context = $this->get_webhook_result_log_context( $result );
            $this->gateway->logger->info(
                $name . ' finished: order ' . $order_id . ', status ' . $new_status,
                array_merge( $log_context, $result_context )
            );

            return rest_ensure_response([
                'success' => true,
                'message' => isset($result['message']) ? $result['message'] : $name . ' processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, $name, $data );
        }
    }

    public function fail_webhook( \WP_REST_Request $input ) {
        $name = 'Failure webhook';
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $data, false );

            $order = $this->parse_webhook_order( $data );
            
            $order_id = $order->get_id();
            $status = $order->get_status();
            $log_context = $this->get_webhook_log_context( $data );

            $this->gateway->logger->info( $name . ' started: order ' . $order_id . ', status ' . $status, $log_context );
            
            $result = $this->gateway->orderHelper->process_payment( $order, $data, $name );
            
            $new_status = $result['status'] ?? '';
            $result_context = $this->get_webhook_result_log_context( $result );
            $this->gateway->logger->info(
                $name . ' finished: order ' . $order_id . ', status ' . $new_status,
                array_merge( $log_context, $result_context )
            );
        
            return rest_ensure_response([
                'success' => true,
                'message' => isset($result['message']) ? $result['message'] : $name . ' processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, $name, $data );
        }
    }

    public function success_webhook_add_card( \WP_REST_Request $input ) {
        $name = 'Add-card webhook';
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $data, true );

            $log_context = $this->get_webhook_log_context( $data );
            $this->gateway->logger->info( $name . ' started', $log_context );

            $this->gateway->paymentTokenHelper->add_token( $data, $this->gateway->id );

            $this->gateway->logger->info( $name . ' finished', $log_context );

            return rest_ensure_response([
                'success' => true,
                'message' => $name . ' processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, $name, $data );
        }
    }

    /**
     * Handle webhook for subscription payment method change
     *
     * @param \WP_REST_Request $input Incoming request with payment data
     * @return \WP_REST_Response|\WP_Error JSON response on success or WP_Error on failure
     */
    public function subscription_change_payment_method( \WP_REST_Request $input ) {
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $data, true );

            $subscription = $this->parse_webhook_order( $data, true );

            $this->gateway->subscriptionHelper->handle_change_subscription_payment_method( $subscription, $data );

            return rest_ensure_response([
                'success' => true,
                'message' => 'Subscription payment method change processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, 'subscription_change_payment_method', $data );
        }
    }

    /**
     * Handle WooCommerce legacy wc-api endpoint return page for payment flows.
     *
     * This method processes the return page after payment flows such as subscription
     * payment method changes, adding a payment method, or paying for an order, or checkout.
     *
     * URL format: /?wc-api={gateway-id}&page={page}&order_id={order_id}&state={success|failed}
     *
     * @return void
     */
    public function handle_payment_return_page() {
        $order_id = absint( $_GET['order_id'] ?? 0 );
        $state    = sanitize_text_field( $_GET['state'] ?? '' );
        $page     = sanitize_text_field( $_GET['page'] ?? '' );

        // Handle subscription change-method flow
        if ( $page === 'change_payment_method' ) {
            $subscription = wcs_get_subscription( $order_id );

            if ( ! $subscription ) {
                wp_safe_redirect( wc_get_account_endpoint_url( 'subscriptions' ) );
                exit;
            }

            if ( $state === 'failed' ) {
                wc_add_notice( 'Payment method update failed.', 'error' );
                wp_safe_redirect( $subscription->get_change_payment_method_url() );
                exit;
            }

            wc_add_notice( 'Payment method updated successfully.', 'success' );
            wp_safe_redirect( $subscription->get_view_order_url() );
            exit;
        }

        // Handle add-payment-method flow
        if ( $page === 'add_payment_method' ) {
            if ( $state === 'failed' ) {
                wc_add_notice( 'Payment method could not be added.', 'error' );
            } else {
                wc_add_notice( 'Payment method added successfully.', 'success' );
            }

            wp_safe_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
            exit;
        }

        // Handle pay-for-order flow
        if ( $page === 'pay_for_order' && $order_id > 0 ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wp_safe_redirect( wc_get_account_endpoint_url( 'orders' ) );
                exit;
            }

            if ( $state === 'failed' ) {
                wc_add_notice( 'Payment failed.', 'error' );
                wp_safe_redirect( $order->get_checkout_payment_url() );
                exit;
            }

            wp_safe_redirect( $order->get_checkout_order_received_url() );
            exit;
        }

        // Handle checkout flow
        if ( $page === 'checkout' && $order_id > 0 ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                wp_safe_redirect( wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() ) );
                exit;
            }

            if ( $state === 'success' && $order && Helper::is_paid_status( $order->get_status() ) ) {
                Helper::empty_cart_and_persist();
            }

            $return_url = $this->gateway->get_option( $state === 'failed' ? 'failureBackLink' : 'backLink' );
            if ( ! empty($return_url ) ) {
                wp_safe_redirect( Helper::is_url_absolute($return_url) ? $return_url : get_site_url(null, $return_url) );
                exit;
            }

            $redirect_url = $order->get_checkout_order_received_url();
            if ( $state === 'failed' ) {
                $redirect_url = add_query_arg( 'status', 'failed', $redirect_url );
            }
            wp_safe_redirect( $redirect_url );
            exit;
        }

        // No recognized action; terminate to prevent further output
        exit;
    }

    /**
     * Extract a safe, useful subset of webhook fields for logging.
     *
     * @param array $data Webhook request params.
     * @return array Key/value pairs.
     */
    private function get_webhook_log_context( array $data ): array {
        $custom_data = Helper::parse_merchant_custom_data( $data );
        if ( ! empty( $custom_data['error'] ) ) {
            $this->gateway->logger->error( $custom_data['error'] );
        }

        return [
            'invoiceId'        => $data['invoiceId'] ?? null,
            'transactionId'    => $data['id'] ?? null,
            'paymentMethod'    => $data['paymentMethod'] ?? null,
            'gatewayId'        => $custom_data['gateway_id'] ?? null,
            'orderId'          => $custom_data['order_id'] ?? null,
        ];
    }

    /**
     * Extract a small subset of process result fields for logging.
     *
     * @param array $result Processing result from OrderHelper::process_payment().
     * @return array Key/value pairs.
     */
    private function get_webhook_result_log_context( array $result ): array {
        return [
            'code'    => $result['code'] ?? null,
            'message' => $result['message'] ?? null,
        ];
    }

    /**
     * Parse the webhook input and return either a WC_Order or a WC_Subscription.
     *
     * @param array|\WP_REST_Request $input         Incoming data.
     * @param bool                   $return_subscription Whether to return a subscription instead of an order.
     * @return \WC_Order|\WC_Subscription
     * @throws \Exception If invoice ID is missing, order/subscription not found, or invalid type.
     */
    private function parse_webhook_order( $input, $return_subscription = false ) {
        if ( empty( $input['invoiceId'] ) ) {
            throw new \Exception( 'Invoice ID is missing or invalid.', 400 );
        }

        $custom_data = Helper::parse_merchant_custom_data( $input );
        if ( ! empty( $custom_data['error'] ) ) {
            $this->gateway->logger->error( $custom_data['error'] );
        }

        $order_id = $custom_data['order_id'] ?? null;
        $input_order_number = Helper::extract_prefix_from_invoice_id( $input['invoiceId'] );

        // Find order/subscription ID if not already set
        if ( empty( $order_id ) ) {
            $order_id = \WC_DNA_Payments_Order_Admin_Helpers::findOrderByOrderNumber( $input_order_number );
            if ( empty( $order_id ) ) {
                throw new \Exception( $return_subscription ? 'Subscription' : 'Order' . ' ID could not be determined for invoiceId: ' . $input['invoiceId'], 400 );
            }
        }

        if ( $return_subscription ) {
            $subscription = $this->gateway->subscriptionHelper->get_subscription( $order_id );
            if ( ! $subscription ) {
                throw new \Exception( 'Subscription not found for ID: ' . $order_id, 400 );
            }
            if ( $input_order_number !== $subscription->get_order_number() ) {
                throw new \Exception( 'Subscription number mismatch: expected ' . $subscription->get_order_number() . ', got ' . $input_order_number, 400 );
            }
            return $subscription;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            throw new \Exception( 'Order not found for ID: ' . $order_id, 400 );
        }
        $this->validate_input_against_order( $input, $order );
        return $order;
    }

    private function validate_input_against_order( $input, $order ) {
        $input_order_number = Helper::extract_prefix_from_invoice_id( $input['invoiceId'] );
        $input_total        = isset( $input['amount'] ) ? wc_format_decimal( $input['amount'], 2 ) : null;
        $input_currency     = isset( $input['currency'] ) ? strtoupper( sanitize_text_field( $input['currency'] ) ) : null;

        $order_number   = $order->get_order_number();
        $order_total    = wc_format_decimal( $order->get_total(), 2 );
        $order_currency = strtoupper( $order->get_currency() );

        if ( $input_order_number !== $order_number ) {
            throw new \Exception( 'Order number mismatch: expected ' . $order_number . ', got ' . $input_order_number, 400 );
        }

        if ( $input_total === null || $input_total !== $order_total ) {
            throw new \Exception( 'Order total mismatch: expected ' . $order_total . ', got ' . $input_total, 400 );
        }

        if ( $input_currency === null || $input_currency !== $order_currency ) {
            throw new \Exception( 'Order currency mismatch: expected ' . $order_currency . ', got ' . $input_currency, 400 );
        }
    }

    private function validate_webhook_input( $input, $should_be_successfull ) {
        if ( empty($input) ) {
            throw new \Exception('Input data is empty.', 400);
        }

        if ( $should_be_successfull && ! $input['success']) {
            throw new \Exception('Transaction was not successful.', 400);
        }

        if ( ! $should_be_successfull && $input['success']) {
            throw new \Exception('Transaction was successful.', 400);
        }

        if ( ! $this->gateway->dnaPayment::isValidSignature($input, $this->gateway->client_secret) ) {
            throw new \Exception('Invalid signature.', 403);
        }

        return true;
    }

    private function get_wp_error(\Exception $e, $hook_name, $data) {
        $log_context = is_array( $data )
            ? $this->get_webhook_log_context( $data )
            : null;
        $this->gateway->logger->error( $hook_name . ' failed: ' . $e->getMessage(), $log_context );
        $this->gateway->logger->error( 'Stack trace: ' . $e->getTraceAsString(), $log_context );
            
        // Respond with the error message and code
        return new \WP_Error(
            $this->gateway->id . '_error',
            $e->getMessage(),
            array(
                'status' => $e->getCode() ? $e->getCode() : 500,
                'plugin_version' => \WC_DNA_Payments::$version,
                'stack_trace' => $e->getTraceAsString(),
            )
        );
    }
}
