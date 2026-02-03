<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WCPG_DNA_Payments\Utils\Helper;

class AjaxInit {
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

		add_action('wc_ajax_' . $this->get_payment_and_auth_data_for_saving_card_action(), array($this, 'handle_get_payment_and_auth_data_for_saving_card'));

		add_action('wc_ajax_' . $this->get_payment_and_auth_data_action(), array($this, 'handle_get_payment_and_auth_data'));

		add_action('wc_ajax_' . $this->get_payment_data_from_cart_action(), array($this, 'handle_get_payment_data_from_cart'));

        add_action('wc_ajax_' . $this->get_update_order_status_action(), array($this, 'handle_update_order_status'));

        self::$hooks_initialized = true;
    }

    public function get_nonces() {
        return [
            $this->get_update_order_status_action() => wp_create_nonce( $this->get_update_order_status_action() ),
            $this->get_payment_and_auth_data_action() => wp_create_nonce( $this->get_payment_and_auth_data_action() ),
            $this->get_payment_data_from_cart_action() => wp_create_nonce( $this->get_payment_data_from_cart_action() ),
            $this->get_payment_and_auth_data_for_saving_card_action() => wp_create_nonce( $this->get_payment_and_auth_data_for_saving_card_action() ),
        ];
    }

    private function get_update_order_status_action() {
        return $this->gateway->id . '_update_order_status';
    }

    private function get_payment_and_auth_data_action() {
        return $this->gateway->id . '_get_payment_and_auth_data';
    }

    private function get_payment_data_from_cart_action() {
        return $this->gateway->id . '_get_payment_data_from_cart';
    }

    private function get_payment_and_auth_data_for_saving_card_action() {
        return $this->gateway->id . '_get_payment_and_auth_data_for_saving_card';
    }

    private function get_nonce_field() {
        return '_' . $this->gateway->id . '_nonce';
    }

	public function handle_get_payment_and_auth_data() {
        check_ajax_referer($this->get_payment_and_auth_data_action(), $this->get_nonce_field(), true);

        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 'checkout';

		try {
			$order = wc_get_order( $order_id );
            $invoice_id = Helper::build_invoice_id_with_prefix($order->get_order_number());

            if ( ! $order instanceof \WC_Order ) {
                throw new \Exception( 'Order not found' );
            }

			$auth_data = $page === 'change_payment_method'
                ? $this->gateway->authDataHelper->get_auth_data( $invoice_id, 0.0, $order->get_currency() ) 
                : $this->gateway->authDataHelper->get_auth_data_from_order( $order );

			$payment_data = $this->gateway->paymentDataHelper->get_payment_data_from_order( $order, array(
                'invoice_id' => $invoice_id,
                'page' => $page,
            ) );

            $order->add_order_note( sprintf( __( 'DNA Payments: Fetched payment and auth data', \WC_DNA_Payments::$text_domain )) );
            $order->save();

			wp_send_json_success( array(
				'auth'			=> $auth_data,
				'paymentData'	=> $payment_data,
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Error in handle_get_payment_and_auth_data for order ' . $order_id . ': ' . $e->getMessage() );
            }
			wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
		}
    }

	public function handle_get_payment_data_from_cart() {
        check_ajax_referer($this->get_payment_data_from_cart_action(), $this->get_nonce_field(), true);

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
                $this->gateway->logger->error( 'Error in handle_get_payment_data_from_cart: ' . $e->getMessage() );
            }
            wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
        }
    }

	public function handle_get_payment_and_auth_data_for_saving_card() {
        check_ajax_referer($this->get_payment_and_auth_data_for_saving_card_action(), $this->get_nonce_field(), true);

        $user_id    = get_current_user_id();
        $invoice_id = Helper::build_invoice_id_with_prefix($user_id);
		$customer 	= new \WC_Customer( $user_id );

		try {
            wp_send_json_success( array(
				'auth'			=> $this->gateway->authDataHelper->get_auth_data( $invoice_id, 0, 'GBP', 'handle_get_payment_and_auth_data_for_saving_card' ),
				'paymentData'	=> $this->gateway->paymentDataHelper->get_payment_data_from_customer( $customer, $invoice_id )
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Error in handle_get_payment_and_auth_data_for_saving_card: ' . $e->getMessage() );
            }
            wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
        }
    }

    public function handle_update_order_status() {
	    check_ajax_referer($this->get_update_order_status_action(), $this->get_nonce_field(), true);

        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 'checkout';

        $result_string = Helper::get_posted_value('wc-' . $this->gateway->id . '-result');

		try {
			$order 	= wc_get_order( $order_id );

            if ( ! $order ) {
                throw new \Exception('Order not found for ID: ' . $order_id, 400);
            }

            // Check if AJAX order status update is enabled and skip is false
            if ( $this->is_ajax_update_enabled() && $page === 'checkout' ) {
                $result = $this->gateway->orderHelper->process_payment_using_payment_result( $order, $result_string, Helper::get_current_user_id(), 'update_order_status' );
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

            $success = $status !== 'failed';
            $redirect = $this->gateway->paymentDataHelper->get_payment_return_url( $order_id, $page, $success );

			wp_send_json_success( array(
                'status'    => $status,
                'redirect'  => $redirect,
                'message'   => $message,
			) );
		} catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Error in handle_update_order_status for order ' . $order_id . ': ' . $e->getMessage() );
            }
			wp_send_json_error([
                'errors' => [ $e->getMessage() ]
            ], 500);
		}
    }

	private function is_ajax_update_enabled(): bool {
		$raw = (string) ($this->gateway->enable_ajax_order_status_update ?? '');

		return wc_string_to_bool($raw);
	}
}
