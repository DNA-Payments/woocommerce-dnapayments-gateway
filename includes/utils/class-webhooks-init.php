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

        self::$hooks_initialized = true;
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

    public function success_webhook($input) {
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $input, true );

            $order = $this->parse_webhook_order( $input );
    
            $order_id = $order->get_id();
            $status = $order->get_status();

            $log_context = $this->format_log_context( $this->get_webhook_log_context( $data ) );
            $this->gateway->logger->info( 'Processing success webhook for order ID ' . $order_id . ' with status ' . $status . $log_context );

            $result = $this->gateway->orderHelper->process_payment( $order, $input, 'success_webhook' );

            $new_status = $result['status'] ?? '';
            $result_context = $this->format_log_context( $this->get_webhook_result_log_context( $result ) );
            $log_context = $this->format_log_context( $this->get_webhook_log_context( $data ) );
            $this->gateway->logger->info( 'Processed success webhook for order ID ' . $order_id . ' with new status ' . $new_status . $log_context . $result_context );

            return rest_ensure_response([
                'success' => true,
                'message' => isset($result['message']) ? $result['message'] : 'Success webhook processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, 'success_webhook', $data );
        }
    }

    public function fail_webhook( \WP_REST_Request $input ) {
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $input, false );

            $order = $this->parse_webhook_order( $input );
            
            $order_id = $order->get_id();
            $status = $order->get_status();
            
            $log_context = $this->format_log_context( $this->get_webhook_log_context( $data ) );
            $this->gateway->logger->info( 'Processing failure webhook for order ID ' . $order_id . ' with status ' . $status . $log_context );
            
            $result = $this->gateway->orderHelper->process_payment( $order, $input, 'fail_webhook' );
            
            $new_status = $result['status'] ?? '';
            $result_context = $this->format_log_context( $this->get_webhook_result_log_context( $result ) );
            $log_context = $this->format_log_context( $this->get_webhook_log_context( $data ) );
            $this->gateway->logger->info( 'Processed failure webhook for order ID ' . $order_id . ' with new status ' . $new_status . $log_context . $result_context );
        
            return rest_ensure_response([
                'success' => true,
                'message' => isset($result['message']) ? $result['message'] : 'Failure webhook processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, 'fail_webhook', $data );
        }
    }

    public function success_webhook_add_card( \WP_REST_Request $input ) {
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $input, true );

            $log_context = $this->format_log_context( $this->get_webhook_log_context( $data ) );
            $this->gateway->logger->info( 'Processing add-card webhook' . $log_context );

            \WC_DNA_Payments_Order_Client_Helpers::saveCardToken( $input, $this->gateway->id );

            $this->gateway->logger->info( 'Processed add-card webhook' . $log_context );

            return rest_ensure_response([
                'success' => true,
                'message' => 'Add card webhook processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, 'success_webhook_add_card', $data );
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
            $this->validate_webhook_input( $input, true );

            $subscription = $this->parse_webhook_order( $input, true );

            $log_context = $this->format_log_context( array_merge(
                [
                    'subscriptionPaymentGatewayId' => $subscription->get_payment_method(),
                    'subscriptionStatus'           => $subscription->get_status(),
                ],
                $this->get_webhook_log_context( $data )
            ) );
            $this->gateway->logger->info( 'Processing subscription change payment method webhook for subscription ID ' . $subscription->get_id() . $log_context );

            $this->gateway->subscriptionHelper->change_subscription_payment_method( $subscription, $data );

            $log_context = $this->format_log_context( array_merge(
                [
                    'subscriptionPaymentGatewayId' => $subscription->get_payment_method(),
                    'subscriptionStatus'           => $subscription->get_status(),
                ],
                $this->get_webhook_log_context( $data )
            ) );
            $this->gateway->logger->info( 'Processed subscription change payment method webhook for subscription ID ' . $subscription->get_id() . $log_context );

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
     * Format log context as a compact key=value list.
     *
     * @param array $context Key/value pairs.
     * @return string A formatted context suffix (or empty string).
     */
    private function format_log_context( array $context ): string {
        $parts = [];
        foreach ( $context as $key => $value ) {
            if ( $value === null || $value === '' ) {
                continue;
            }
            if ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            }
            $parts[] = $key . '=' . $value;
        }

        return empty( $parts ) ? '' : ' (' . implode( ', ', $parts ) . ')';
    }

    /**
     * Extract a safe, useful subset of webhook fields for logging.
     *
     * @param array $data Webhook request params.
     * @return array Key/value pairs.
     */
    private function get_webhook_log_context( array $data ): array {
        $custom_data = $this->gateway->orderHelper->parse_merchant_custom_data( $data );

        return [
            'invoiceId'        => $data['invoiceId'] ?? null,
            'transactionId'    => $data['id'] ?? null,
            'paymentMethod'    => $data['paymentMethod'] ?? null,
            'gatewayId'        => $custom_data['gateway_id'] ?? null,
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

        $parsed = $this->gateway->orderHelper->parse_merchant_custom_data( $input );
        $order_id = $parsed['order_id'] ?? null;

        // Find order/subscription ID if not already set
        if ( empty( $order_id ) ) {
            $order_id = \WC_DNA_Payments_Order_Admin_Helpers::findOrderByOrderNumber( Helper::extract_prefix_from_invoice_id( $input['invoiceId'] ) );
            if ( empty( $order_id ) ) {
                throw new \Exception( $return_subscription ? 'Subscription' : 'Order' . ' ID could not be determined for invoiceId: ' . $input['invoiceId'], 400 );
            }
        }

        if ( $return_subscription ) {
            $subscription = $this->gateway->subscriptionHelper->get_subscription( $order_id );
            if ( ! $subscription ) {
                throw new \Exception( 'Subscription not found for ID: ' . $order_id, 400 );
            }
            return $subscription;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            throw new \Exception( 'Order not found for ID: ' . $order_id, 400 );
        }
        return $order;
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
            ? $this->format_log_context( $this->get_webhook_log_context( $data ) )
            : '';
        $this->gateway->logger->error('Error in ' . $hook_name . ': ' . $e->getMessage() . $log_context);
        $this->gateway->logger->error('Stack trace: ' . $e->getTraceAsString());
            
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
