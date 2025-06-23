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

    public function update_status_from_payment_result( $order, $result_string ) {
        $input = json_decode( $result_string, true);

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new \Exception(json_last_error());
        }

        if ( is_null( $input )) {
            throw new \Exception( __( 'Invalid JSON format', \WC_DNA_Payments::$text_domain ));
        }

        if ( $input['success'] ) {
            $client_token = $this->gateway->dnaPayment->get_client_token(
                $this->gateway->client_id,
                $this->gateway->client_secret
            );

            if ( empty($input['id']) ) {
                throw new \Exception( __( 'Transaction ID is missing or invalid.', \WC_DNA_Payments::$text_domain ) );
            }

            $transactions = $this->gateway->dnaPayment->get_transactions_by_id(
                $client_token['access_token'],
                $input['id']
            );

            $has_charge = false;
            foreach ($transactions as $item) {
                if ( in_array( $item['transactionState'], [ 'CHARGE', 'AUTH' ]) ) {
                    $has_charge = true;
                }
            }

            if ( ! $has_charge ) {
                throw new \Exception( __( 'No successful transaction has been processed for this order ID.', \WC_DNA_Payments::$text_domain ) );
            }
        }

        $settled = strtoupper( $this->gateway->get_option('transactionType') ) === 'SALE';

        if ( in_array( $input['paymentMethod'], array(
            'paybybankapp',
            'ecospend',
            'alipay',
            'wechatpay',
            'astropay',
        ) ) ) {
            $settled = true;
        }

        $result = $this->update_status( $order, $input, $settled );

        if ( in_array($result['status'], ['on-hold', 'processing', 'completed']) ) {
            // Remove cart
            WC()->cart->empty_cart();
        }

        return $result;
    }

    public function update_status( $order, $input, $settled ) {

        $transaction_id = $input['id'];
        $order_id       = $order->get_id();
        $status         = $order->get_status();

        if ( ! $input['success'] ) {
            if( ! empty($input['paypalCaptureStatus']) ) {
                $this->save_pay_pal_order_detail( $order, $input, false );
            }

            if ( $status !== 'pending' ) {
                throw new \Exception('Order with ID ' . $orderId . ' is already processed with status: ' . $status, 400);
            }

            $message = __( 'Could not process payment.', \WC_DNA_Payments::$text_domain );
            $order->update_status( 'failed', $message );

            return [ 'status' => 'failed', 'message' => $message ];
        }

        if ( !\WC_DNA_Payments_Order_Client_Helpers::isDNAPaymentOrder($order) ) {
            throw new \Exception(__('Order processed by a different payment method: ', \WC_DNA_Payments::$text_domain ) . $order->get_payment_method(), 400);
        }

        // If the order status is 'on-hold' and the settled parameter is true, the webhook should complete the order.
        if ( ! in_array($status, ['draft', 'pending', 'failed', 'cancelled']) && ($status !== 'on-hold' || ! $settled) ) {
            if( ! empty($input['paypalCaptureStatus']) ) {
                $this->save_pay_pal_order_detail( $order, $input, true );
            }

            $message = __( 'Order with ID ' . $order_id . ' is already processed with status: ' . $status, \WC_DNA_Payments::$text_domain );
            return [ 'status' => $status, 'message' => $message ];
        }

        // Handle settlement
        if ($settled) {
            $new_status = $order->needs_processing() ? 'processing' : 'completed';
            $order->payment_complete();
            $order->add_order_note(sprintf(__( 'DNA Payments transaction complete (Transaction ID: %s). Order status changed from %s to %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status) ));

            if ($new_status === 'processing' && 'yes' === $this->gateway->get_option('enable_order_complete')) {
                $order->update_status('completed');
                $new_status = 'completed';
                // Log status change
                $order->add_order_note(sprintf(__('DNA Payments updated order status from %s to %s.', \WC_DNA_Payments::$text_domain), ucfirst($status), ucfirst($new_status)));
            }
        } else {
            $new_status = 'on-hold';
            $order->update_status('on-hold');
            $order->add_order_note(sprintf(__( 'DNA Payments awaiting payment completion (Transaction ID: %s). Order status changed from %s to %s.', \WC_DNA_Payments::$text_domain ), $transaction_id, ucfirst($status), ucfirst($new_status) ));
        }

        // Update order transaction ID
        $order->set_transaction_id( $transaction_id );
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
        if ( ! $is_processed && $this->gateway->enabled_saved_cards && ($input['storeCardOnFile'] || $this->parse_merchant_custom_data( $input )['store_card_on_file']) ) {
            \WC_DNA_Payments_Order_Client_Helpers::saveCardToken($input, $this->gateway->id);
            $this->gateway->logger->info('Card token saved for order ID ' . $order_id);
        }

        return [ 'status' => $new_status ];
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
                return [ 'order_id' => $customData->orderId, 'store_card_on_file' => $customData->storeCardOnFile ?? false];
            } catch (\Exception $e) {
                $this->gateway->logger->warning('Error parsing merchantCustomData: ' . $e->getMessage());
            }
        }
        return [ 'order_id' => null, 'store_card_on_file' => false ];
    }
}
