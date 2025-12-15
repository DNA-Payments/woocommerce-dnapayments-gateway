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
        if ( in_array($next_status, [ 'processing', 'completed' ]) ) {
            return false;
        }
        
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof \WC_Order ) {
            return false;
        }

        $transaction_id = $order->get_transaction_id();

        if ( !$transaction_id || $this->gateway->orderHelper->get_order_state($order) !== 'authorized' ) {
            return false;
        }

        $paymentMethod = $order->get_meta( 'payment_method', true );

        if( $paymentMethod === 'paypal' && !\WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order) ) {
            $paypalCaptureStatus = $order->get_meta( 'paypal_capture_status', true );
            $order->add_order_note( sprintf( __('DNA Payments: Paypal payment could not be captured with status: %s', \WC_DNA_Payments::$text_domain ), $paypalCaptureStatus) );
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
                $order->add_order_note(sprintf(__( 'DNA Payments: Payment was charged (Transaction ID: %s). Order status updated from %s to %s. Source: %s.', \WC_DNA_Payments::$text_domain ), $new_transaction_id, ucfirst($previous_status), ucfirst($next_status), 'capture_payment' ));
                $order->update_meta_data( '_dnapayments_state', 'charged' );
                $order->set_transaction_id( $new_transaction_id );
                $order->save();

                return true;
            }
        } catch (\Exception $e) {
            $this->gateway->logger->error('Error in capture_payment; Code: ' . $e->getCode() . '; Message: ' . $e->getMessage());
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
