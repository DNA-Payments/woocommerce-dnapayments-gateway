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

        add_action('wc_ajax_' . $this->get_client_log_action(), array($this, 'handle_client_log'));

        self::$hooks_initialized = true;
    }

    public function get_nonces() {
        return [
            $this->get_update_order_status_action() => wp_create_nonce( $this->get_update_order_status_action() ),
            $this->get_payment_and_auth_data_action() => wp_create_nonce( $this->get_payment_and_auth_data_action() ),
            $this->get_payment_data_from_cart_action() => wp_create_nonce( $this->get_payment_data_from_cart_action() ),
            $this->get_payment_and_auth_data_for_saving_card_action() => wp_create_nonce( $this->get_payment_and_auth_data_for_saving_card_action() ),
            $this->get_client_log_action() => wp_create_nonce( $this->get_client_log_action() ),
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

    private function get_client_log_action() {
        return $this->gateway->id . '_client_log';
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

            if ( ! $order instanceof \WC_Order ) {
                wp_send_json_error( array(
                    'errors' => array( __( 'Order not found.', \WC_DNA_Payments::$text_domain ) ),
                ), 404 );
            }

            if ( ! $this->current_user_can_access_order( $order ) ) {
                $this->gateway->logger->warning( 'Payment data refused for order #' . $order_id . ': no access', array( 'userId' => get_current_user_id() ?: 'guest' ) );
                wp_send_json_error( array(
                    'errors' => array( __( 'You are not allowed to access this order.', \WC_DNA_Payments::$text_domain ) ),
                ), 403 );
            }

            $invoice_id = Helper::build_invoice_id_with_prefix($order->get_order_number());

            // Mirror the veto applied in process_payment(); this flow bypasses it entirely.
            $can_process = apply_filters(
                'dnapayments_can_process_payment',
                true,
                $order,
                array(
                    'gateway_id'        => $this->gateway->id,
                    'page'              => $page,
                    'is_block_checkout' => false,
                )
            );

            if ( is_wp_error( $can_process ) ) {
                wp_send_json_error( array( 'errors' => array( $can_process->get_error_message() ) ), 500 );
            }

			$auth_data = $page === 'change_payment_method'
                ? $this->gateway->authDataHelper->get_auth_data( $invoice_id, 0.0, $order->get_currency() ) 
                : $this->gateway->authDataHelper->get_auth_data_from_order( $order );

			$payment_data = $this->gateway->paymentDataHelper->get_payment_data_from_order( $order, array(
                'invoice_id' => $invoice_id,
                'page' => $page,
            ) );

            $prior_state = $this->gateway->orderHelper->get_order_state( $order );
            $order->add_order_note( sprintf( __( 'DNA Payments: Fetched payment and auth data', \WC_DNA_Payments::$text_domain )) );
            $order->save();
            // Pay-for-order and change-payment-method start here, not in process_payment().
            $this->gateway->orderHelper->log_payment_initiated( $order, $prior_state );

			wp_send_json_success( array(
				'auth'			=> $auth_data,
				'paymentData'	=> $payment_data,
				'paymentMethodsSettings' => $this->gateway->get_payment_methods_settings( array(
					'source' => 'order',
					'order'  => $order,
					'page'   => $page,
				) ),
			) );
		} catch (\Throwable $e) {
            $this->gateway->logger->exception( $e, 'Payment data request failed for order #' . $order_id );
			wp_send_json_error([
                'errors' => [ $this->get_public_error_message( $e ) ]
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
                'paymentData' => $this->gateway->paymentDataHelper->get_payment_data_from_cart( $checkout, $cart, $customer ),
                'paymentMethodsSettings' => $this->gateway->get_payment_methods_settings( array(
                    'source' => 'cart',
                    'cart'   => $cart,
                ) ),
            ) );
        } catch (\Throwable $e) {
            $this->gateway->logger->exception( $e, 'Cart payment data request failed' );
            wp_send_json_error([
                'errors' => [ $this->get_public_error_message( $e ) ]
            ], 500);
        }
    }

	public function handle_get_payment_and_auth_data_for_saving_card() {
        check_ajax_referer($this->get_payment_and_auth_data_for_saving_card_action(), $this->get_nonce_field(), true);

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'errors' => array( __( 'Authentication required.', \WC_DNA_Payments::$text_domain ) ),
            ), 403 );
        }

        $user_id    = get_current_user_id();
        $invoice_id = Helper::build_invoice_id_with_prefix($user_id);
		$customer 	= new \WC_Customer( $user_id );

		try {
            wp_send_json_success( array(
				'auth'			=> $this->gateway->authDataHelper->get_auth_data( $invoice_id, 0, 'GBP', 'handle_get_payment_and_auth_data_for_saving_card' ),
				'paymentData'	=> $this->gateway->paymentDataHelper->get_payment_data_from_customer( $customer, $invoice_id )
			) );
		} catch (\Throwable $e) {
            $this->gateway->logger->exception( $e, 'Add-card payment data request failed', array( 'userId' => $user_id ) );
            wp_send_json_error([
                'errors' => [ $this->get_public_error_message( $e ) ]
            ], 500);
        }
    }

    public function handle_update_order_status() {
	    check_ajax_referer($this->get_update_order_status_action(), $this->get_nonce_field(), true);

        $order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
        $page = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 'checkout';

        $result_string = Helper::get_posted_value('wc-' . $this->gateway->id . '-result');
        $result        = array();

		try {
			$order 	= wc_get_order( $order_id );

            if ( ! $order instanceof \WC_Order ) {
                $this->gateway->logger->warning( 'Payment return for unknown order #' . $order_id );
                wp_send_json_error( array(
                    'errors' => array( __( 'Order not found.', \WC_DNA_Payments::$text_domain ) ),
                ), 404 );
            }

            if ( ! $this->current_user_can_access_order( $order ) ) {
                $this->gateway->logger->warning( 'Payment return refused for order #' . $order_id . ': no access', array( 'userId' => get_current_user_id() ?: 'guest' ) );
                wp_send_json_error( array(
                    'errors' => array( __( 'You are not allowed to access this order.', \WC_DNA_Payments::$text_domain ) ),
                ), 403 );
            }

            // Check if AJAX order status update is enabled and skip is false
            if ( $this->is_ajax_update_enabled() && $page === 'checkout' ) {
                $result = $this->gateway->orderHelper->process_payment_using_payment_result( $order, $result_string, Helper::get_current_user_id(), 'update_order_status' );
                $status = $result['status'];
                $message = $result['message'] ?? '';
            } else {
                // The webhook records the payment: wait up to 2 seconds for it, returning as soon as it has.
                $status = $this->gateway->orderHelper->wait_for_payment_status( (int) $order->get_id(), 2.0 );

                // Refresh order data
                $order = wc_get_order( $order_id );
                $message = __( 'Refreshed order data', \WC_DNA_Payments::$text_domain );
            }

            $success = $status !== 'failed';
            $redirect = $this->gateway->paymentDataHelper->get_payment_return_url( $order, $page, $success );

            // Proof the shopper came back from the payment, and what they were shown.
            $this->gateway->logger->info( 'Payment return for order #' . $order_id . ': ' . $status, array(
                'result'     => $result['code'] ?? null,
                'tokenError' => $result['token_error'] ?? null,
            ) );

			wp_send_json_success( array(
                'status'    => $status,
                'redirect'  => $redirect,
                'message'   => $message,
			) );
		} catch (\Throwable $e) {
            // dnaTransactions: what DNA has for the invoice when the browser's result could not be confirmed.
            $this->gateway->logger->exception( $e, 'Payment return failed for order #' . $order_id, $this->gateway->orderHelper->get_last_lookup() );
			wp_send_json_error([
                'errors' => [ $this->get_public_error_message( $e ) ]
            ], 500);
		}
    }

    /**
     * Record a checkout error that happened in the shopper's browser, as one line:
     */
    public function handle_client_log() {
        if ( check_ajax_referer( $this->get_client_log_action(), $this->get_nonce_field(), false ) && ! $this->is_client_log_full() ) {
            $code  = preg_replace( '/[^A-Za-z0-9_.:-]/', '', $this->get_posted_text( 'code', 40 ) );
            $order_id = absint( $this->get_posted_text( 'orderId', 20 ) );

            $this->gateway->logger->warning(
                'Browser error ' . ( '' !== $code ? $code : 'unknown' ) . ': ' . $this->get_posted_text( 'message', 200 ),
                array(
                    'gatewayId' => sanitize_key( $this->get_posted_text( 'gatewayId', 40 ) ),
                    'orderId'   => $order_id ? $order_id : null,
                    'browser'   => $this->get_browser_name(),
                )
            );
        }

        exit;
    }

    /**
     * At most 100 browser errors an hour in total, so a broken page or a bot cannot flood the log.
     */
    private function is_client_log_full(): bool {
        $count = (int) get_transient( 'dnapayments_client_log_count' );

        if ( $count >= 100 ) {
            return true;
        }

        set_transient( 'dnapayments_client_log_count', $count + 1, HOUR_IN_SECONDS );

        return false;
    }

    private function get_posted_text( string $key, int $max_length ): string {
        $value = isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) ) : '';

        return substr( $value, 0, $max_length );
    }

    /**
     * Browser name, plus iOS/Android: enough to spot wallets in browsers that do not support
     * them (e.g. Apple Pay in an Instagram in-app browser).
     */
    private function get_browser_name(): string {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
        $os = preg_match( '/iPhone|iPad/', $ua ) ? ' iOS' : ( false !== strpos( $ua, 'Android' ) ? ' Android' : '' );

        // Order matters: in-app browsers, Edge and Chrome on iOS also contain "Safari".
        foreach ( array( 'Instagram' => 'Instagram', 'FBAN' => 'Facebook', 'Edg' => 'Edge', 'SamsungBrowser' => 'Samsung', 'Firefox' => 'Firefox', 'FxiOS' => 'Firefox', 'CriOS' => 'Chrome', 'Chrome' => 'Chrome', 'Safari' => 'Safari' ) as $token => $name ) {
            if ( false !== strpos( $ua, $token ) ) {
                return $name . $os;
            }
        }

        return 'other' . $os;
    }

    /**
     * Error text safe to show the shopper: engine errors are bugs, so their details stay in the log.
     */
    private function get_public_error_message( \Throwable $e ): string {
        return $e instanceof \Error
            ? __( 'Something went wrong. Please try again.', \WC_DNA_Payments::$text_domain )
            : $e->getMessage();
    }

	private function is_ajax_update_enabled(): bool {
		$raw = (string) ($this->gateway->enable_ajax_order_status_update ?? '');

		return wc_string_to_bool($raw);
	}

	/**
	 * Determines whether the current request may access the given order.
	 *
	 *
	 * @param \WC_Order $order
	 * @return bool
	 */
	private function current_user_can_access_order( \WC_Order $order ): bool {
		$order_key = Helper::get_posted_value( 'order_key' );
		return Helper::current_user_can_access_order( $order, $order_key );
	}
}
