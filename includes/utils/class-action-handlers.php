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
        add_action( 'woocommerce_order_status_changed', array( $this, 'log_paid_order_status_regression' ), 20, 4 );
        add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_payment' ) );
        add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_auto_cancel' ), 10, 2 );
        add_filter( 'wc_order_payment_card_info', array( $this, 'get_payment_card_info' ), 10, 2 );

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

        $context = [
            'amount'        => $order_total . ' ' . $order->get_currency(),
            'transactionId' => $transaction_id,
        ];

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

            $this->gateway->logger->error( 'Capture declined by DNA for order #' . $order_id, array_merge( $context, array(
                'errorCode' => is_array( $result ) ? ( $result['errorCode'] ?? null ) : null,
                'message'   => is_array( $result ) ? ( $result['message'] ?? null ) : 'empty response',
            ) ) );
        } catch (\Exception $e) {
            $this->gateway->logger->exception( $e, 'Capture failed for order #' . $order_id, $context );
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

        if ( ! $order instanceof \WC_Order ) {
            return;
        }

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
            $lookup = $this->gateway->orderHelper->get_last_lookup();
            if ( $cancel && 0 === strpos( $lookup['dnaTransactions'] ?? '', 'lookup failed' ) ) {
                // WooCommerce will cancel an order DNA could not be asked about: it may have been paid.
                $this->gateway->logger->warning( 'Auto-cancel of order #' . $order->get_id() . ' goes ahead without a DNA check', $lookup );
            }
            return $cancel;
        }

        $status = $order->get_status();

        if ( in_array($state, ['charged', 'authorized']) ) {
            // The webhook never recorded this payment: a missed webhook worth knowing about.
            $new_status = $this->gateway->orderHelper->payment_complete( $order, $info, $state === 'charged', 'prevent_auto_cancel' );
            $this->gateway->logger->warning(
                'Auto-cancel stopped for order #' . $order->get_id() . ': DNA has it ' . $state . ', recorded now (' . $status . '->' . $new_status . ')',
                array( 'transactionId' => $info['id'] ?? null )
            );
        } else if ( $state === 'failed' ) {
            $order->update_status( 'failed', __( 'DNA Payments: Marked as failed to prevent automatic cancellation.', \WC_DNA_Payments::$text_domain ) );
            $order->update_meta_data('_dnapayments_state', 'failed');
            $order->save();
        }

        return false;
    }

    /**
     * Log when a paid DNA order is moved back to an unpaid status, and by whom.
     *
     * @param int       $order_id Order ID.
     * @param string    $from     Previous status.
     * @param string    $to       New status.
     * @param \WC_Order $order    Order.
     */
    public function log_paid_order_status_regression( $order_id, $from, $to, $order = null ) {
        if ( ! in_array( $to, array( 'pending', 'failed', 'checkout-draft' ), true )
            || ! in_array( $from, array( 'processing', 'completed', 'on-hold' ), true ) ) {
            return;
        }

        if ( ! $order instanceof \WC_Order ) {
            return;
        }

        // Only orders DNA recorded as paid; the state meta is already loaded with the order.
        $state = $this->gateway->orderHelper->get_order_state( $order );
        if ( ! in_array( $state, array( 'charged', 'authorized' ), true ) ) {
            return;
        }

        $this->gateway->logger->warning(
            'Paid order #' . $order_id . ' moved back to ' . $to . ' (' . $from . '->' . $to . ')',
            array(
                'paymentState' => $state,
                'userId'       => get_current_user_id() ?: null,
                'requestType'  => $this->get_request_kind(),
                'changedBy'    => $this->find_external_caller(),
            )
        );
    }

    /**
     * What kind of request changed the status: cron, cli, rest, ajax, admin or web.
     *
     * @return string
     */
    private function get_request_kind(): string {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return 'cli';
        }
        if ( wp_doing_cron() ) {
            return 'cron';
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return 'rest';
        }
        if ( wp_doing_ajax() || ! empty( $_GET['wc-ajax'] ) ) {
            return 'ajax';
        }

        return is_admin() ? 'admin' : 'web';
    }

    /**
     * First caller outside WordPress core, WooCommerce and this plugin, e.g.
     * "plugins/some-lottery/includes/class-orders.php:212".
     *
     * @return string
     */
    private function find_external_caller(): string {
        $content = wp_normalize_path( WP_CONTENT_DIR ) . '/';
        $skip    = array(
            wp_normalize_path( WC_DNA_PLUGIN_PATH ) . '/',
            $content . 'plugins/woocommerce/',
        );

        foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 40 ) as $frame ) {
            if ( empty( $frame['file'] ) ) {
                continue;
            }

            $file = wp_normalize_path( $frame['file'] );
            if ( 0 !== strpos( $file, $content ) ) {
                continue; // WordPress core.
            }

            foreach ( $skip as $prefix ) {
                if ( 0 === strpos( $file, $prefix ) ) {
                    continue 2;
                }
            }

            return substr( $file, strlen( $content ) ) . ':' . ( $frame['line'] ?? 0 );
        }

        return 'core/woocommerce';
    }

    /**
     * Get payment card info.
     *
     * @param  array $info
     * @param  \WC_Order $order
     * @return array
     */
    public function get_payment_card_info( $info, $order ) {
        if ( ! $order instanceof \WC_Order ) {
            return $info;
        }

        if ( ! Helper::is_dna_payments_order($order) ) {
            return $info;
        }

        $card_type = $order->get_meta(Helper::META_CARD_TYPE);
        if ( ! empty( $card_type ) ) {
            $info['brand'] = Helper::normalize_card_brand($card_type);
        }

        $card_last4 = $order->get_meta(Helper::META_CARD_LAST4);
        if ( ! empty( $card_last4 ) ) {
            $info['last4'] = $card_last4;
        }

        return $info;
    }
}
