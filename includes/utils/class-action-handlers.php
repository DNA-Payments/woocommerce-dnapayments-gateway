<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Handles and process orders from asyncronous flows.
 *
 */
class ActionHandlers {
    /**
     * @var \WC_DNA_Payments_Gateway
     */
    public $gateway;

    /**
     * Prevent duplicate hook registration.
     *
     * @var bool
     */
    private static $hooks_initialized = false;

    /**
     * Initialize with gateway and register hooks.
     *
     * @param \WC_DNA_Payments_Gateway $gateway
     */
    public function __construct( $gateway ) {
        $this->gateway = $gateway;
        $this->init_hooks();
    }

    /**
     * Register WooCommerce hooks once.
     */
    private function init_hooks() {
        if ( self::$hooks_initialized ) {
            return;
        }

        add_action( 'woocommerce_order_status_changed', array( $this, 'capture_payment' ), 10, 3 );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_payment' ) );
        add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_auto_cancel' ), 10, 2 );

        self::$hooks_initialized = true;
    }

    /**
     * Capture payment when the order is changed from on-hold to complete or processing.
     *
     * @param  int $order_id
     */
    public function capture_payment( $order_id, $previous_status, $next_status ) {
        if ( ! in_array($next_status, [ 'processing', 'completed' ]) ) {
            return false;
        }

        if ( $previous_status !== 'on-hold' ) {
            return false;
        }

        $context = [
            'order_id' => $order_id,
            'prev_status' => $previous_status,
            'next_status' => $next_status,
        ];
        
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof \WC_Order ) {
            $this->gateway->logger->warning('Capture skipped: order not found', $context);
            return false;
        }

        $transaction_id = $order->get_transaction_id();
        $context['transaction_id'] = $transaction_id ?: '';
        $context['order_state'] = $this->gateway->orderHelper->get_order_state($order);

        if ( !$transaction_id || $context['order_state'] !== 'authorized' ) {
            $this->gateway->logger->info('Capture skipped: missing transaction ID or state is not authorized', $context);
            return false;
        }

        $paymentMethod = $order->get_meta( 'payment_method', true );
        $context['order_payment_method'] = $paymentMethod ?: '';

        if( $paymentMethod === 'paypal' && !\WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order) ) {
            $paypalCaptureStatus = $order->get_meta( 'paypal_capture_status', true );
            $order->add_order_note( sprintf( __('DNA Payments: Paypal payment could not be captured with status: %s', \WC_DNA_Payments::$text_domain ), $paypalCaptureStatus) );
            $this->gateway->logger->info('Capture skipped: PayPal status is not valid for capture', array_merge($context, [
                'payment_method' => $paymentMethod,
                'paypal_capture_status' => $paypalCaptureStatus
            ]));
            return false;
        }

        $order_total = $order->get_total();

        if ( 0 < $order->get_total_refunded() ) {
            $order_total = $order_total - $order->get_total_refunded();
        }

        try {
            $result = $this->gateway->dnaPayment->charge([
                'client_id' => $this->gateway->client_id,
                'client_secret' => $this->gateway->client_secret,
                'terminal' => $this->gateway->terminal,
                'invoiceId' => strval($order->get_order_number()),
                'amount' => $order_total,
                'currency' => $order->get_currency(),
                'transaction_id' => $transaction_id
            ]);

            if( !empty($result) && $result['success'] ) {
                $new_transaction_id = $result['id'];
                $this->gateway->logger->info('Capture successful', array_merge($context, [
                    'new_transaction_id' => $new_transaction_id
                ]));
                $order->add_order_note(sprintf(__( 'DNA Payments: Payment was charged (Transaction ID: %s). Order status updated from %s to %s. Source: %s.', \WC_DNA_Payments::$text_domain ), $new_transaction_id, ucfirst($previous_status), ucfirst($next_status), 'capture_payment' ));
                $order->update_meta_data( '_dnapayments_state', 'charged' );
                $order->set_transaction_id( $new_transaction_id );
                $order->save();

                return true;
            }

            $this->gateway->logger->warning('Capture failed: unexpected charge response', array_merge($context, [
                'charge_success' => isset($result['success']) ? ($result['success'] ? 'true' : 'false') : 'undefined',
                'order_payment_method' => $paymentMethod ?: ''
            ]));
        } catch (\Exception $e) {
            $this->gateway->logger->error(
                'Capture failed: Code: ' . $e->getCode() . '; Message: ' . $e->getMessage(),
                $context
            );
        }

        return false;
    }

    /**
     * Cancel pre-auth on refund/cancellation.
     *
     * @param  int $order_id
     */
    public function cancel_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( $this->gateway->orderHelper->get_order_state($order) === 'authorized' ) {
            $paymentMethod = $order->get_meta( 'payment_method', true );

            if( $paymentMethod === 'paypal' && !\WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order) ) {
                $paypalCaptureStatus = $order->get_meta( 'paypal_capture_status', true );
                $order->add_order_note( sprintf( __( 'DNA Payments: Paypal payment could not be refund/cancel with status: %s', \WC_DNA_Payments::$text_domain ), $paypalCaptureStatus) );
                $order->save();
                return;
            }

            $this->gateway->process_cancel( $order );
        }
    }

    public function prevent_auto_cancel( $cancel, $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return $cancel;
        }

        if ( $this->gateway->orderHelper->get_order_state($order) !== 'initiated' ) {
            return $cancel;
        }

        $info = $this->gateway->orderHelper->get_transaction_info( $order );
        $state = $info['state'] ?? '';

        if ( !in_array($state, ['charged', 'authorized', 'failed']) ) {
            return $cancel;
        }

        if ( in_array($state, ['charged', 'authorized']) ) {
            $this->gateway->orderHelper->payment_complete( $order, $info, $state === 'charged', 'prevent_auto_cancel' );
        } else if ( $state === 'failed' ) {
            $order->update_status( 'failed', __( 'DNA Payments: Marked as failed to prevent automatic cancellation.', \WC_DNA_Payments::$text_domain ) );
            $order->update_meta_data('_dnapayments_state', 'failed');
            $order->save();
        }

        return false;
    }
}
