<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
        register_rest_route( 'dnapayments', 'success', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'success_webhook'),
            // semgrep:ignore audit.php.wp.security.rest-route.permission-callback.return-true
            'permission_callback' => '__return_true'
        ) );

        register_rest_route( 'dnapayments', 'success-add-card', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'success_webhook_add_card'),
            // semgrep:ignore audit.php.wp.security.rest-route.permission-callback.return-true
            'permission_callback' => '__return_true'
        ) );

        register_rest_route( 'dnapayments', 'failure', array(
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => array( $this, 'fail_webhook'),
            // semgrep:ignore audit.php.wp.security.rest-route.permission-callback.return-true
            'permission_callback' => '__return_true'
        ) );
    }

    public function success_webhook($input) {
        $data = $input->get_params();

        try {
            $this->validate_webhook_input( $input, true );

            $order = $this->parse_webhook_order( $input );
    
            $order_id = $order->get_id();
            $status = $order->get_status();

            $this->gateway->logger->info('Processing success webhook for order ID ' . $order_id . ' with status ' . $status);

            $result = $this->gateway->orderHelper->update_status( $order, $input, $input['settled'] );

            $this->gateway->logger->info('Processed success webhook for order ID ' . $order_id . ' with status ' . $status);

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

            $this->gateway->orderHelper->update_status( $order, $input, false );
        
            return rest_ensure_response([
                'success' => true,
                'message' => 'Failure webhook processed successfully.',
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
            $order_id = \WC_DNA_Payments_Order_Admin_Helpers::findOrderByOrderNumber($input['invoiceId']);

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