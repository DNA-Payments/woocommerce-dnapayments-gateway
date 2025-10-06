<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AjaxInit {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;

		add_action('wp_ajax_get_payment_and_auth_data_for_saving_card', array($this, 'handle_get_payment_and_auth_data_for_saving_card'));

		add_action('wp_ajax_get_payment_and_auth_data', array($this, 'handle_get_payment_and_auth_data'));
        add_action('wp_ajax_nopriv_get_payment_and_auth_data', array($this, 'handle_get_payment_and_auth_data'));

		add_action('wp_ajax_get_payment_data_from_cart', array($this, 'handle_get_payment_data_from_cart'));
        add_action('wp_ajax_nopriv_get_payment_data_from_cart', array($this, 'handle_get_payment_data_from_cart'));

        add_action('wp_ajax_update_order_status', array($this, 'handle_update_order_status'));
        add_action('wp_ajax_nopriv_update_order_status', array($this, 'handle_update_order_status'));
    }

	public function handle_get_payment_and_auth_data() {
        $order_id     = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $total_amount = isset( $_POST['total'] ) ? sanitize_text_field( wp_unslash( $_POST['total'] ) ) : '';

		try {
			$order 			= wc_get_order( $order_id );
			$total_amount 	= floatval( empty($total_amount) || $total_amount === 'null' ? $order->get_total() : $total_amount );

			$auth_data      = $this->gateway->authDataHelper->get_auth_data_from_order( $order, $total_amount );
			$payment_data   = $this->gateway->paymentDataHelper->get_payment_data_from_order( $order );
			$payment_data['amount'] = $total_amount;

			wp_send_json_success( array(
				'auth'			=> $auth_data,
				'paymentData'	=> $payment_data,
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'handle_get_payment_and_auth_data: ' . $e->getMessage() );
            }
			wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
		}
    }

	public function handle_get_payment_data_from_cart() {

        try {
            $cart = WC()->cart;
            $customer = WC()->customer;
            $checkout = WC()->checkout();

            $errors = $this->gateway->checkoutValidation->validate_checkout_data( $checkout, $cart );

            if ( ! is_null( $errors ) ) {
                wp_send_json_error([
                    'errors' => $errors->get_error_messages(),
                ], 400);
            }

            wp_send_json_success( array(
                'paymentData' => $this->gateway->paymentDataHelper->get_payment_data_from_cart( $checkout, $cart, $customer )
            ) );
        } catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'handle_get_payment_data_from_cart: ' . $e->getMessage() );
            }
            wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
        }
    }

	public function handle_get_payment_and_auth_data_for_saving_card() {

        $user_id    = get_current_user_id();
        $invoice_id = date('d-m-y h:i:s');
		$customer 	= new \WC_Customer( $user_id );

		try {
            wp_send_json_success( array(
				'auth'			=> $this->gateway->authDataHelper->get_auth_data( $invoice_id, 0, 'GBP' ),
				'paymentData'	=> $this->gateway->paymentDataHelper->get_payment_data_from_customer( $customer, $invoice_id )
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'handle_get_payment_and_auth_data_for_saving_card: ' . $e->getMessage() );
            }
            wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
        }
    }

    public function handle_update_order_status() {
        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $order_number = isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
        $result_string = Helper::get_posted_value('wc-' . $this->gateway->id . '-result');

		try {
            $order = null;
            
            // Try to get order by order_id first
            if (!empty($order_id)) {
                $order = wc_get_order($order_id);
            }
            
            // If order not found and order_number is provided, try to find by order_number
            if (!$order && !empty($order_number)) {
                // Search for orders with matching order number
                $orders = wc_get_orders(array(
                    'order_number' => $order_number,
                    'limit' => 1
                ));
                
                if (!empty($orders)) {
                    $order = $orders[0];
                }
            }

            if (!$order) {
                $error_msg = !empty($order_id) ? 'Order not found for ID: ' . $order_id : 'Order not found for number: ' . $order_number;
                throw new \Exception($error_msg, 400);
            }

            // Check if AJAX order status update is enabled
            if ( isset( $this->gateway->enable_ajax_order_status_update ) && $this->gateway->enable_ajax_order_status_update === 'yes' ) {
                $result = $this->gateway->orderHelper->update_status_from_payment_result( $order, $result_string, 'update_order_status' );
                $status = $result['status'];
                $message = $result['message'];
            } else {
                // Wait for 2 seconds
                sleep(2);

                // Refresh order data
                $order = wc_get_order( $order_id );
                $status = $order->get_status();
                $message = __( 'Refreshed order data', \WC_DNA_Payments::$text_domain );
            }

            $redirect = $this->gateway->paymentDataHelper->get_return_url_from_order( $order, $status === 'failed' );

			wp_send_json_success( array(
                'status'    => $status,
                'redirect'  => $redirect,
                'message'   => $message,
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Error in handle_update_order_status: ' . $e->getMessage() );
            }
			wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
		}
    }
}
