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

        self::$hooks_initialized = true;
    }

    /**
     * Capture payment when the order is changed from on-hold to complete or processing.
     *
     * @param  int $order_id
     */
    public function capture_payment( $order_id, $previous_status, $next_status ) {
        $order = wc_get_order( $order_id );

        $complete_statuses = [
            'processing',
            'completed'
        ];

        if (
            \WC_DNA_Payments_Order_Client_Helpers::isDNAPaymentOrder($order) &&
            in_array($next_status, $complete_statuses) &&
            !in_array($previous_status, $complete_statuses)
        ) {

            $paymentMethod = $order->get_meta( 'payment_method', true );

            if($paymentMethod === 'paypal' && !\WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order)) {
                $paypalCaptureStatus = $order->get_meta( 'paypal_capture_status', true );
                $order->add_order_note( sprintf( __('DNA Paypal payment could not be captured with status: %s', 'woocommerce-gateway-dna'), $paypalCaptureStatus) );
                return;
            }

            $transaction_id = $order->get_transaction_id();
            $is_finished = $order->get_meta( 'is_finished_payment', true );

            if ( $transaction_id && 'no' === $is_finished ) {
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
                        $order->add_order_note( sprintf(__( 'DNA Payments transaction complete (Transaction ID: %s)', 'woocommerce-gateway-dna' ), $result['id']) );
                        $order->update_meta_data( 'is_finished_payment', 'yes' );
                        $order->set_transaction_id( $result['id'] );

                        if ( is_callable( array( $order, 'save' ) ) ) {
                            $order->save();
                        }

                        return true;
                    }
                } catch (\Exception $e) {
                    $this->gateway->logger->error('Error in capture_payment; Code: ' . $e->getCode() . '; Message: ' . $e->getMessage());
                }

                return false;
            }
        }
    }

    /**
     * Cancel pre-auth on refund/cancellation.
     *
     * @param  int $order_id
     */
    public function cancel_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        $is_finished = $order->get_meta( 'is_finished_payment', true ) === 'yes';

        if ( \WC_DNA_Payments_Order_Client_Helpers::isDNAPaymentOrder($order) ) {
            $paymentMethod = $order->get_meta( 'payment_method', true );

            if($paymentMethod === 'paypal' && !\WC_DNA_Payments_Order_Admin_Helpers::isValidStatusPayPalStatus($order)) {
                $paypalCaptureStatus = $order->get_meta( 'paypal_capture_status', true );
                $order->add_order_note( sprintf( __( 'DNA Paypal payment could not be refund/cancel with status: %s', 'woocommerce-gateway-dna' ), $paypalCaptureStatus) );
                return;
            }

            if(!$is_finished) {
                $this->gateway->process_refund( $order_id );
            }
        }
    }
}