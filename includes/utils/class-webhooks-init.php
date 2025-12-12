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

    public function __construct( $gateway ) {
        $this->gateway = $gateway;

        add_action( 'rest_api_init', array( $this, 'register_routes' ));
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

            $this->gateway->logger->info('Processing success webhook for order ID ' . $order_id . ' with status ' . $status);

            $result = $this->gateway->orderHelper->update_status( $order, $input, $input['settled'], 'success_webhook' );

            $this->gateway->logger->info('Processed success webhook for order ID ' . $order_id . ' with new status ' . $result['status']);

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
            
            $this->gateway->logger->info('Processing failure webhook for order ID ' . $order_id . ' with status ' . $status);
            
            $result = $this->gateway->orderHelper->update_status( $order, $input, false, 'fail_webhook' );
            
            $this->gateway->logger->info('Processed failure webhook for order ID ' . $order_id . ' with new status ' . $result['status']);
        
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

            \WC_DNA_Payments_Order_Client_Helpers::saveCardToken( $input, $this->gateway->id );

            return rest_ensure_response([
                'success' => true,
                'message' => 'Add card webhook processed successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->get_wp_error( $e, 'success_webhook_add_card', $data );
        }
    }

    private function parse_webhook_order( $input ) {
        if ( empty($input['invoiceId']) ) {
            throw new \Exception('Invoice ID is missing or invalid.', 400);
        }

        $order_id = $this->gateway->orderHelper->parse_merchant_custom_data( $input )['order_id'];

        // Find order ID if not already set
        if ( empty($order_id) ) {
            $order_id = \WC_DNA_Payments_Order_Admin_Helpers::findOrderByOrderNumber( Helper::extract_prefix_from_invoice_id( $input['invoiceId'] ) );

            if ( empty($order_id) ) {
                throw new \Exception('Order ID could not be determined for invoiceId: ' . $input['invoiceId'], 400);
            }
        }

        // Fetch order
        $order = wc_get_order($order_id);
        if ( ! $order ) {
            throw new \Exception('Order not found for ID: ' . $order_id, 400);
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
        $this->gateway->logger->error('Error in ' . $hook_name . ': ' . $e->getMessage());
        $this->gateway->logger->error('Input: ' . json_encode($data));
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