<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WCPG_DNA_Payments\Utils\Helper;

class OrderHelper {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway  = $gateway;
    }

    public function process_payment_using_payment_result( $order, $result_string, $user_id = '', $source = '' ) {
        $order_id = $order->get_id();
        $input = Helper::parse_json_to_array($result_string);

        if ( empty($input['id']) ) {
            throw new \Exception( __( 'Transaction ID is missing or invalid.', \WC_DNA_Payments::$text_domain ) );
        }

        $charge_result = $this->get_transaction_status($order, $input['id'], $user_id);

        if ( $input['success'] && $charge_result !== 'success' ) {
            throw new \Exception( __( 'No successful transaction has been processed for this order ID.', \WC_DNA_Payments::$text_domain ) );
        }

        if ( !$input['success'] && $charge_result !== 'failed' ) {
            throw new \Exception( __( 'No failed transaction has been processed for this order ID.', \WC_DNA_Payments::$text_domain ) );
        }

        $result = $this->process_payment( $order, $input, $source );
        $status = $result['status'];

        // Check if there's a code indicating the transaction is already being processed
        if (isset($result['code']) && $result['code'] === 'transaction_in_process') {
            $this->gateway->logger->info('AJAX update skipped for order ' . $order_id . ' as it is being processed by another request');
            // Fetch the order again to get the updated status after waiting 2 second
            sleep(2);
            $order = wc_get_order( $order_id );
            $status = $order->get_status();
        }

        // Only clear cart if the order status was successfully updated to a paid status
        if ( Helper::is_paid_status( $status ) ) {
            Helper::empty_cart_and_persist();
        }

        return $result;
    }

    public function process_payment( \WC_Order $order, array $input, string $source = '' ) {

        $transaction_id = $input['id'];
        $order_id       = $order->get_id();
        $status         = $order->get_status();
        $settled        = $this->determine_settlement_status($input);

        // Check if this transaction is already being processed
        $lock_key = 'dnapayments_processing_' . $transaction_id;
        $is_processing = get_transient($lock_key);
        
        if ($is_processing) {
            $this->gateway->logger->info('Transaction ' . $transaction_id . ' for order ' . $order_id . ' is already being processed by another request');

            return [ 
                'status'  => $status,
                'code'    => 'transaction_in_process',
                'message' => 'Transaction is already being processed'
            ];
        }

        // Set a transient to lock this transaction for processing (expires after 10 seconds)
        set_transient($lock_key, true, 10);

        try {
            $order->read_meta_data(true);
            // Added to fix the issue in third party plugin WooCommerce Giveaways
            if ( class_exists( 'LTY_Order_Handler' ) && property_exists( 'LTY_Order_Handler', 'order_object_saved' ) ) {
                /** @disregard P1009 */
                \LTY_Order_Handler::$order_object_saved = true;
            }

            if ( ! $input['success'] ) {
                if( ! empty($input['paypalCaptureStatus']) ) {
                    $this->save_pay_pal_order_detail( $order, $input, false );
                }

                if ( $status !== 'pending' ) {
                    throw new \Exception('Order with ID ' . $order_id . ' is already processed with status: ' . $status, 400);
                }

                $message = __( 'DNA Payments: Could not process payment. Source: ' . $source, \WC_DNA_Payments::$text_domain );
                $order->update_status( 'failed', $message );
                $order->update_meta_data('_dnapayments_state', 'failed');
                $order->save();

                return [ 'status' => 'failed', 'message' => $message ];
            }

            // If the order status is 'on-hold' and the settled parameter is true, the webhook should complete the order.
            if ( ! in_array($status, ['checkout-draft', 'draft', 'pending', 'failed', 'cancelled']) && ($status !== 'on-hold' || ! $settled) ) {
                if( ! empty($input['paypalCaptureStatus']) ) {
                    $this->save_pay_pal_order_detail( $order, $input, true );
                }

                $message = __( 'Order with ID ' . $order_id . ' is already processed with status: ' . $status, \WC_DNA_Payments::$text_domain );
                return [ 'status' => $status, 'message' => $message ];
            }

            if ( !Helper::is_dna_payments_order($order) ) {
                $this->gateway->logger->error( 'Order with ID ' . $order_id . ' processed by a different payment method: ' . $order->get_payment_method() . '. But the payment process will continue.' );
            }

            if( ! empty($input['paypalCaptureStatus']) ) {
                $this->save_pay_pal_order_detail( $order, $input, false );
            }

            $custom_data = Helper::parse_merchant_custom_data( $input );
            if ( ! empty( $custom_data['error'] ) ) {
                $this->gateway->logger->error( $custom_data['error'] );
            }
            $this->update_payment_method_from_custom_data( $order, $custom_data );
            $new_status = $this->payment_complete( $order, $input, $settled, $source );

            // Handle saving card tokens. Status "on-hold" means that add_token already processed
            $is_processed = $new_status !== $status && $status === 'on-hold';
            $should_add_token = $this->gateway->enabled_saved_cards && ($input['storeCardOnFile'] || $custom_data['store_card_on_file']);
            if ( ! $is_processed && ($should_add_token || $custom_data['allowed_recurring']) ) {
                $token_result = $this->gateway->paymentTokenHelper->add_token($input, $order->get_payment_method(), (bool) ($custom_data['allowed_recurring'] ?? false));

                if ( empty( $token_result['error'] ) ) {
                    $this->gateway->logger->info('Card token saved for order ID ' . $order_id);
                } else {
                    $this->gateway->logger->info('Card token not saved for order ID ' . $order_id . '. Error: ' . $token_result['error']);
                }
            }

            $this->gateway->subscriptionHelper->save_payment_meta_to_subscriptions( $order, $input );
            
            // Release the transaction lock
            delete_transient($lock_key);
            $this->gateway->logger->info('Released processing lock for transaction ' . $transaction_id);

            return [ 'status' => $new_status ];
        } catch (\Exception $e) {
            // Make sure to release the lock even if an error occurs
            delete_transient($lock_key);
            $this->gateway->logger->error('Error updating order status for order ID ' . $order_id . ': ' . $e->getMessage());
            throw $e; // Re-throw the exception to be handled by the caller
        }
    }

    /**
     * Mark the order as paid and set its status based on settlement flag.
     *
     * @param \WC_Order $order  WooCommerce order object.
     * @param array     $input  Payment gateway response data.
     * @param bool      $settled Whether the payment is already settled.
     * @param string    $source  Origin of the call (e.g., webhook, AJAX).
     * @return string   New order status after processing.
     */
    public function payment_complete( \WC_Order $order, $input, $settled, $source = '' ) {
        $transaction_id = $input['id'];
        $order_id       = $order->get_id();
        $status         = $order->get_status();

        // Handle settlement
        if ($settled) {
            $new_status = $order->needs_processing() ? 'processing' : 'completed';
            if ( ! $order->payment_complete( $transaction_id ) ) {
                $this->gateway->logger->error( 'Could not complete payment for order #' . $order_id .' with status ' . $status );
                $order->add_order_note(sprintf(__('DNA Payments: Could not complete payment order with status %s. Source: %s.'), \WC_DNA_Payments::$text_domain), $status, $source);
                $order->save();
                throw new \Exception( 'Could not complete payment for order #' . $order_id .' with status ' . $status );
            }
            $order->add_order_note(sprintf(__( 'DNA Payments: Payment was charged (Transaction ID: %s). Order status updated from %s to %s. Source: %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status), $source ));

            if ($new_status === 'processing' && 'yes' === $this->gateway->get_option('enable_order_complete')) {
                $old_status = $order->get_status();
                $order->update_status('completed');
                $new_status = 'completed';
                // Log status change
                $order->add_order_note(sprintf(__('DNA Payments: Order status updated from %s to %s.', \WC_DNA_Payments::$text_domain), ucfirst($old_status), ucfirst($new_status)));
            }
        } else {
            $new_status = 'on-hold';
            $order->update_status('on-hold');
            $order->set_transaction_id( $transaction_id );
            $order->add_order_note(sprintf(__( 'DNA Payments: Payment was authorized (Transaction ID: %s). Order status updated from %s to %s. Source: %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status), $source ));
        }

        // Update metadata
        $card_info = $this->gateway->paymentTokenHelper->get_card_info($input);
        if ( ! empty( $card_info ) ) {
            $order->update_meta_data( Helper::META_CARD_TYPE, $card_info['card_type'] ?? '');
            $order->update_meta_data( Helper::META_CARD_LAST4, $card_info['last4'] ?? '');
        }
        $order->update_meta_data('_dnapayments_state', $settled ? 'charged' : 'authorized');
        $order->update_meta_data('_dnapayments_transaction_id', $transaction_id);
        $order->update_meta_data('rrn', $input['rrn'] ?? '');
        $order->update_meta_data('payment_method', $input['paymentMethod'] ?? '');

        $manage_stock_option = get_option('woocommerce_manage_stock');
        // if the order status changed from pending to processing (on-hold), woocommerce automatically reduces stock
        if ($manage_stock_option !== 'yes' || $status !== 'pending') {
            wc_reduce_stock_levels($order_id);
            $order->add_order_note( sprintf( __( 'DNA Payments: Order stock was reduced (Transaction ID: %s). Source: %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, $source ) );
        }

        $order->save();
        return $new_status;
    }

    /**
     * Return the current DNA Payments state for an order.
     *
     * @param \WC_Order $order WooCommerce order object.
     * @return string One of: 'charged', 'authorized', 'initiated', 'failed'.
     */
    public function get_order_state( \WC_Order $order ) {
        if ( $order->meta_exists( '_dnapayments_state' ) ) {
            return $order->get_meta( '_dnapayments_state', true );
        }

        // TODO: 'is_finished_payment' is deprecated and will be removed in a future version
        if ( $order->meta_exists( 'is_finished_payment' ) ) {
            return $order->get_meta( 'is_finished_payment', true ) === 'yes' ? 'charged' : 'authorized';
        }

        return '';
    }

    /**
     * TODO: investigate why we need this function
     */
    public function save_pay_pal_order_detail( \WC_Order $order, $input, $should_add_order_note ) {
        $status = $input['paypalOrderStatus'];
        $capture_status = $input['paypalCaptureStatus'];
        $reason = isset($input['paypalCaptureStatusReason']) ? $input['paypalCaptureStatusReason'] : null;

        if ( empty( $capture_status ) ) {
            return;
        }

        if ( $should_add_order_note ) {
            $error_text = '';

            $old_status = $order->get_meta('paypal_status', true);
            if( ! empty($status) && $old_status !== $status ) {
                $error_text .= sprintf(__( 'DNA Payments paypal status was changed from "%s" to "%s". ', \WC_DNA_Payments::$text_domain ), $old_status, $status);
            }

            $old_capture_status = $order->get_meta('paypal_capture_status', true);
            if( ! empty($capture_status) && $old_capture_status !== $capture_status ) {
                if($error_text === '') {
                    $error_text .= sprintf(__( 'DNA Payments paypal capture status was changed from "%s" to "%s". ', \WC_DNA_Payments::$text_domain ), $old_capture_status, $capture_status);
                } else {
                    $error_text .= sprintf(__( 'Capture status was changed from "%s" to "%s". ', \WC_DNA_Payments::$text_domain ), $old_capture_status, $capture_status);
                }
            }

            $old_reason = $order->get_meta('paypal_capture_status_reason', true);
            if( ! empty($reason) && $old_reason !== $reason) {
                if($error_text === '') {
                    $error_text .= __( 'DNA Payments paypal capture status reason was changed: ', \WC_DNA_Payments::$text_domain ). $reason . '.';
                } else {
                    $error_text .= __( 'Reason:  ', \WC_DNA_Payments::$text_domain ) . $reason . '.';
                }
            }

            if( ! empty($error_text) ) {
                $order->add_order_note('DNA Payments: ' . $error_text);
            }
        }

        $order->update_meta_data( 'paypal_status',  $status);
        $order->update_meta_data( 'paypal_capture_status',  $capture_status);

        if ( ! empty($reason) ) {
            $order->update_meta_data( 'paypal_capture_status_reason',  $reason);
        }

        $order->save();
    }

    /**
     * Update the order’s payment-method from gateway_id found in merchantCustomData.
     *
     * @param \WC_Order $order
     * @param array     $custom_data Already-decoded merchantCustomData array.
     * @return \WC_Payment_Gateway|false
     */
    public function update_payment_method_from_custom_data( \WC_Order $order, array $custom_data ) {
        $gateway_id = $custom_data['gateway_id'] ?? '';
        $old_payment_method = $order->get_payment_method();

        if ( empty( $gateway_id ) || $gateway_id === $old_payment_method ) {
            return false;
        }

        $all_gateways = WC()->payment_gateways()->payment_gateways();
        $target_gateway = $all_gateways[ $gateway_id ] ?? null;

        if ( is_null( $target_gateway ) ) {
            return false;
        }

        $order->set_payment_method( $target_gateway );
        $log = sprintf( 'DNA Payments: Payment method changed from %s to %s via webhook.', $old_payment_method, $target_gateway->id );

        $this->gateway->logger->info(
            sprintf(
                'Order ID %s. ',
                $order->get_id()
            ) . $log
        );
        $order->add_order_note( $log );

        return $target_gateway;
    }

    /**
     * Get transaction status by order and transaction ID, returning tri-state result.
     *
     * @param \WC_Order $order       The WooCommerce order to check.
     * @param string      $transaction_id The transaction ID to find (optional).
     * @param string      $user_id        The user ID to match against accountId (optional).
     * @return string     Result code. 'success', 'failed', or 'not_found'.
     */
    public function get_transaction_status( \WC_Order $order, $transaction_id = null, $user_id = null ) {
        $info = $this->get_transaction_info($order, $transaction_id, $user_id);
        $state = $info['state'] ?? '';
        if (in_array($state, ['charged', 'authorized', 'verified'])) {
            return 'success';
        }
        if ($state === 'not_found') {
            return 'not_found';
        }
        return 'failed';
    }

    /**
     * Evaluate DNA transactions for an order with optional filters.
     *
     * Precedence rules:
     * 1. If any transaction has state REFUND or CREDITED, sum refund amounts and
     *    return 'refunded' for full amount or 'partial_refunded' otherwise.
     * 2. Else, collect states for transactions matching the order amount/currency and optional filters.
     * 3. If states include CHARGE or AUTH, return 'charged' or 'authorized' respectively.
     * 4. If states include TOKENIZED or VERIFIED, return 'verified'.
     * 5. If any transactions matched but not above states, return 'failed'.
     * 6. If none matched, return 'not_found'.
     *
     * Note: refund amount aggregation assumes invoice currency is consistent.
     *
     * @param \WC_Order  $order           The WooCommerce order to check.
     * @param string|null $transaction_id  Optional specific transaction id to match.
     * @param string|null $user_id         Optional accountId to match.
     * @return array                       ['state', 'id', 'paymentMethod', 'rrn']
     */
    public function get_transaction_info( $order, $transaction_id = null, $user_id = null ) {
        if ( ! $this->gateway->is_ready() ) {
            return [ 'state' => '', 'id' => '', 'paymentMethod' => '', 'rrn' => '' ];
        }

        $client_token = $this->gateway->authDataHelper->get_client_token();

        $transactions = $this->gateway->dnaPayment->get_transactions_by_invoice_id(
            $client_token['access_token'],
            (string) $order->get_order_number()
        );

        $matched_states = [];
        $refunded_amount = 0;
        $state_details = [];
        $default_info = [ 'id' => '', 'paymentMethod' => '', 'rrn' => '' ];

        foreach ( $transactions as $item ) {
            $state = $item['transactionState'];
            $info = [
                'id' => $item['id'] ?? '',
                'rrn' => $item['rrn'] ?? '',
                'paymentMethod' => $item['paymentMethod'] ?? '',
            ];

            if (in_array($state, ['REFUND', 'CREDITED'])) {
                $refunded_amount += (float) $item['amount'];
                $state_details['REFUND'] = $info;
                continue;
            }

            if ( $item['currency'] !== $order->get_currency() ) {
                continue;
            }

            if ( (float) $item['amount'] !== (float) $order->get_total() ) {
                continue;
            }

            if (in_array($state, ['CANCEL'])) {
                return ['state' => 'refunded'] + $info;
            }

            if ( ! is_null( $transaction_id ) && $item['id'] !== $transaction_id ) {
                continue;
            }

            if ( ! is_null( $user_id ) ) {
                $account_id = isset($item['accountId']) && !empty($item['accountId']) ? (string) $item['accountId'] : '';
                if ( $account_id !== $user_id ) {
                    continue;
                }
            }

            $matched_states[] = $state;
            $state_details[$state] = $info;
        }

        if ( $refunded_amount > 0 ) {
            $is_full = $refunded_amount === (float) $order->get_total();
            $info = $state_details['REFUND'] ?? $default_info;
            return ['state' => $is_full ? 'refunded' : 'partial_refunded'] + $info;
        }

        if ( in_array( 'CHARGE', $matched_states, true ) ) {
            $info = $state_details['CHARGE'] ?? $default_info;
            return ['state' => 'charged'] + $info;
        }

        if ( in_array( 'AUTH', $matched_states, true ) ) {
            $info = $state_details['AUTH'] ?? $default_info;
            return ['state' => 'authorized'] + $info;
        }

        if ( array_intersect( $matched_states, ['TOKENIZED', 'VERIFIED'] ) ) {
            $info = $state_details['VERIFIED'] ?? ($state_details['TOKENIZED'] ?? $default_info);
            return ['state' => 'verified'] + $info;
        }

        if (count($matched_states) > 0) {
            $any_state = $matched_states[0];
            $info = $state_details[$any_state] ?? $default_info;
            return ['state' => 'failed'] + $info;
        }

        return ['state' => 'not_found'] + $default_info;
    }

    /**
     * Determine if the transaction should be considered settled based on transaction type and payment method
     *
     * @param array $input Payment data from the payment result
     * @return bool True if the transaction should be considered settled, false otherwise
     */
    public function determine_settlement_status( $input ) {
        $transaction_type = $this->gateway->configHelper->get_transaction_type();
        $settled = isset($input['settled']) ? (bool)$input['settled'] : false;

        if (in_array($transaction_type, ['SALE', 'AUTH'])) {
            $settled = $transaction_type === 'SALE';
        }

        if ( in_array( $input['paymentMethod'], array(
            'paybybankapp',
            'ecospend',
            'alipay',
            'wechatpay',
            'astropay',
        ) ) ) {
            $settled = true;
        }

        return $settled;
    }
}
