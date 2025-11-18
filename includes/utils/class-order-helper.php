<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OrderHelper {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway  = $gateway;
    }

    public function update_status_from_payment_result( $order, $result_string, $user_id = '', $source = '' ) {
        $input = json_decode( $result_string, true);
        $order_id = $order->get_id();

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new \Exception(json_last_error());
        }

        if ( is_null( $input )) {
            throw new \Exception( __( 'Invalid JSON format', \WC_DNA_Payments::$text_domain ));
        }

        if ( empty($input['id']) ) {
            throw new \Exception( __( 'Transaction ID is missing or invalid.', \WC_DNA_Payments::$text_domain ) );
        }

        $charge_result = $this->has_transaction($order, $input['id'], $user_id);

        if ( $input['success'] && $charge_result !== 'success' ) {
            throw new \Exception( __( 'No successful transaction has been processed for this order ID.', \WC_DNA_Payments::$text_domain ) );
        }

        if ( !$input['success'] && $charge_result !== 'failed' ) {
            throw new \Exception( __( 'No failed transaction has been processed for this order ID.', \WC_DNA_Payments::$text_domain ) );
        }

        $settled = $this->determine_settlement_status($input);

        $result = $this->update_status( $order, $input, $settled, $source );
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
        if ( in_array($status, ['on-hold', 'processing', 'completed']) ) {
            // Remove cart
            WC()->cart->empty_cart();
        }

        return $result;
    }

    public function update_status( $order, $input, $settled, $source = '' ) {

        $transaction_id = $input['id'];
        $order_id       = $order->get_id();
        $status         = $order->get_status();

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

            if ( ! $input['success'] ) {
                if( ! empty($input['paypalCaptureStatus']) ) {
                    $this->save_pay_pal_order_detail( $order, $input, false );
                }

                if ( $status !== 'pending' ) {
                    throw new \Exception('Order with ID ' . $order_id . ' is already processed with status: ' . $status, 400);
                }

                $message = __( 'Could not process payment.', \WC_DNA_Payments::$text_domain );
                $order->update_status( 'failed', $message );

                return [ 'status' => 'failed', 'message' => $message ];
            }

            // If the order status is 'on-hold' and the settled parameter is true, the webhook should complete the order.
            if ( ! in_array($status, ['draft', 'pending', 'failed', 'cancelled']) && ($status !== 'on-hold' || ! $settled) ) {
                if( ! empty($input['paypalCaptureStatus']) ) {
                    $this->save_pay_pal_order_detail( $order, $input, true );
                }

                $message = __( 'Order with ID ' . $order_id . ' is already processed with status: ' . $status, \WC_DNA_Payments::$text_domain );
                return [ 'status' => $status, 'message' => $message ];
            }

            if ( !\WC_DNA_Payments_Order_Client_Helpers::isDNAPaymentOrder($order) ) {
                $this->gateway->logger->error( 'Order with ID ' . $order_id . ' processed by a different payment method: ' . $order->get_payment_method() . '. But the payment process will continue.' );
            }

            $custom_data = $this->parse_merchant_custom_data( $input );
            $gateway_id  = $custom_data['gateway_id'];

            // Update payment method if gateway_id is provided in custom data
            if ( ! empty($gateway_id) && $gateway_id !==  $order->get_payment_method() ) {
                // Get the actual gateway object
                $gateway = WC()->payment_gateways->payment_gateways()[ $gateway_id ] ?? null;
                if ( ! is_null( $gateway ) ) {
                    $order->set_payment_method( $gateway );
                    $this->gateway->logger->info('Order with ID ' . $order_id . ' set payment method ' . $gateway->get_title() . 'by webhook before status update');
                }
            }

            // Handle settlement
            if ($settled) {
                $new_status = $order->needs_processing() ? 'processing' : 'completed';
                $order->payment_complete( $transaction_id );
                $order->add_order_note(sprintf(__( 'DNA Payments transaction complete (Transaction ID: %s). Order status changed from %s to %s. Source: %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status), $source ));

                if ($new_status === 'processing' && 'yes' === $this->gateway->get_option('enable_order_complete')) {
                    $old_status = $order->get_status();
                    $order->update_status('completed');
                    $new_status = 'completed';
                    // Log status change
                    $order->add_order_note(sprintf(__('DNA Payments updated order status from %s to %s.', \WC_DNA_Payments::$text_domain), ucfirst($old_status), ucfirst($new_status)));
                }
            } else {
                $new_status = 'on-hold';
                $order->update_status('on-hold');
                $order->set_transaction_id( $transaction_id );
                $order->add_order_note(sprintf(__( 'DNA Payments awaiting payment completion (Transaction ID: %s). Order status changed from %s to %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status) ));
            }

            // Update metadata
            $order->update_meta_data('rrn', $input['rrn'] ?? '');
            $order->update_meta_data('payment_method', $input['paymentMethod'] ?? '');
            $order->update_meta_data('is_finished_payment', $settled ? 'yes' : 'no');

            if( ! empty($input['paypalCaptureStatus']) ) {
                $this->save_pay_pal_order_detail( $order, $input, false );
            }

            $manage_stock_option = get_option('woocommerce_manage_stock');
            // if the order status changed from pending to processing (on-hold), woocommerce automatically reduces stock
            if ($manage_stock_option !== 'yes' || $status !== 'pending') {
                wc_reduce_stock_levels($order_id);
                $order->add_order_note( sprintf( __( 'DNA Payments reduced order stock by transaction (Transaction ID: %s)', \WC_DNA_Payments::$text_domain ), $transaction_id ) );
            }

            $order->save();

            // Handle saving card tokens. Status "on-hold" means that saveCardToken already processed
            $is_processed = $new_status !== $status && $status === 'on-hold';
            if ( ! $is_processed && $this->gateway->enabled_saved_cards && ($input['storeCardOnFile'] || $custom_data['store_card_on_file']) ) {
                $msg_save_token = \WC_DNA_Payments_Order_Client_Helpers::saveCardToken($input, $this->gateway->id);
                if ( empty( $msg_save_token ) ) {
                    $this->gateway->logger->info('Card token saved for order ID ' . $order_id);
                } else {
                    $this->gateway->logger->info('Card token not saved for order ID ' . $order_id . '. Error: ' . $msg_save_token);
                }
            }
            
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
                $order->add_order_note($error_text);
            }
        }

        $order->update_meta_data( 'paypal_status',  $status);
        $order->update_meta_data( 'paypal_capture_status',  $capture_status);

        if ( ! empty($reason) ) {
            $order->update_meta_data( 'paypal_capture_status_reason',  $reason);
        }

        $order->save();
    }

    // Parse merchant custom data
    public function parse_merchant_custom_data( $input ) {
        if ( isset($input['merchantCustomData']) ) {
            try {
                $customData = json_decode($input['merchantCustomData']);
                return [ 
                    'order_id' => $customData->orderId, 
                    'store_card_on_file' => $customData->storeCardOnFile ?? false,
                    'gateway_id' => $customData->gatewayId ?? ''
                ];
            } catch (\Exception $e) {
                $this->gateway->logger->warning('Error parsing merchantCustomData: ' . $e->getMessage());
            }
        }
        return [ 
            'order_id' => null, 
            'store_card_on_file' => false,
            'gateway_id' => ''
        ];
    }

    /**
     * Check transaction by order and transaction ID, returning tri-state result.
     *
     * @param \WC_Order $order The WooCommerce order to check.
     * @param string $transaction_id The transaction ID to find.
     * @param string $user_id The user ID to check.
     * 
     * @return string One of 'success', 'failed', 'not_found'.
     */
    public function has_transaction( $order, $transaction_id, $user_id = '' ) {
        $client_token = $this->gateway->dnaPayment->get_client_token(
            $this->gateway->client_id,
            $this->gateway->client_secret
        );

        $transactions = $this->gateway->dnaPayment->get_transactions_by_invoice_id(
            $client_token['access_token'],
            (string) $order->get_order_number()
        );

        $matched = false;
        foreach ($transactions as $item) {
            $account_id = isset($item['accountId']) && !empty($item['accountId']) ? (string) $item['accountId'] : '';

            if (
                $item['id'] === $transaction_id &&
                (string) $item['amount'] === (string) $order->get_total() &&
                $item['currency'] === $order->get_currency() &&
                $account_id === $user_id
            ) {
                $matched = true;
                if (in_array($item['transactionState'], ['CHARGE', 'AUTH'])) {
                    return 'success';
                }
            }
        }

        return $matched ? 'failed' : 'not_found';
    }

    /**
     * Determine if the transaction should be considered settled based on transaction type and payment method
     *
     * @param array $input Payment data from the payment result
     * @return bool True if the transaction should be considered settled, false otherwise
     */
    public function determine_settlement_status( $input ) {
        $transaction_type = $this->gateway->configHelper->get_transaction_type();
        $settled = false;

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
