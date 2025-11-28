<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Subscription Helper Class
 * 
 * This class handles all WooCommerce Subscriptions related functionality for DNA Payments Gateway.
 * It includes methods for initializing subscription hooks, updating subscription meta data, and checking if subscriptions are active.
 */
class SubscriptionHelper {

    private static $hooks_initialized = false;

    /**
     * @var WC_DNA_Payments_Gateway
     */
    private $gateway;

    /**
     * List of gateway IDs that support subscription payments.
     * These gateways will have scheduled subscription payment hooks registered.
     *
     * @var string[]
     */
    private $supported_gateways = [ 'dnapayments', 'dnapayments_google_pay', 'dnapayments_apple_pay' ];

    /**
     * Returns the meta key used to store the DNA Payments transaction ID or parent order ID.
     *
     * @param string $type Either 'transaction_id' or 'parent_order_id'.
     * @return string The meta key for the specified storage.
     */
    public function get_meta_key( $type ) {

        switch ( $type ) {
            case 'parent_transaction_id':
                return '_' . $this->gateway->id . '_parent_transaction_id';
            case 'parent_order_id':
                return '_' . $this->gateway->id . '_parent_order_id';
            case 'payment_method':
                return '_' . $this->gateway->id . '_payment_method';
            case 'card_token_id':
                return '_' . $this->gateway->id . '_card_token_id';
            default:
                return '_' . $this->gateway->id . '_data';
        }
    }

    /**
     * Check if the payment details in the input match those stored on the subscription.
     *
     * Used to prevent changing a subscription’s payment method to the same one.
     *
     * @param \WC_Subscription $subscription The subscription to compare against.
     * @param array             $input       Raw gateway input containing payment details.
     * @return bool True when gateway, payment method and card token all match the subscription.
     */
    public function is_same_payment_string( \WC_Subscription $subscription, array $input ): bool {
        $subscription_gateway_id = $subscription->get_payment_method() ?? '';
        $subscription_payment_method = (string) $subscription->get_meta( $this->get_meta_key( 'payment_method' ) );
        $subscription_card_token_id = (string) $subscription->get_meta( $this->get_meta_key( 'card_token_id' ) );

        $custom_data = $this->gateway->orderHelper->parse_merchant_custom_data( $input );
        $input_gateway_id = $custom_data['gateway_id'] ?? '';
        $input_payment_method = $input['paymentMethod'] ?? '';
        $input_card_token_id = $input['cardTokenId'] ?? '';

        $subscription_string = implode( '_', array_filter( [
            $subscription_gateway_id,
            $subscription_payment_method,
            $subscription_card_token_id
        ] ) );

        $input_string = implode( '_', array_filter( [
            $input_gateway_id,
            $input_payment_method,
            $input_card_token_id
        ] ) );

        return $subscription_string === $input_string;
    }

    /**
     * Constructor
     * 
     * @param WC_DNA_Payments_Gateway $gateway The main gateway instance
     */
    public function __construct( $gateway ) {
        $this->gateway = $gateway;
        $this->init_subscription_hooks();
    }


    /**
     * Check if WooCommerce Subscriptions is active
     *
     * @return bool Whether WooCommerce Subscriptions is active
     */
    public function is_subscriptions_active() {
        return class_exists( 'WC_Subscriptions_Order' );
    }

    /**
	 * Whether order contains a subscription.
	 *
	 * Wrapper function for wcs_order_contains_subscription
	 *
	 * @param WC_Order $order Order object.
	 * @return bool Whether order contains a subscription.
	 */
	public function order_contains_subscription( $order ) {
        if ( ! $this->is_subscriptions_active() || ! function_exists( 'wcs_order_contains_subscription' ) ) {
            return false;
        }
        return wcs_order_contains_subscription( $order, array( 'parent', 'resubscribe', 'switch', 'renewal' ) );
    }

    /**
	 * Get subscription object from order.
	 *
	 * Wrapper function for wcs_get_subscription
	 *
	 * @param mixed $order Order object or ID.
	 * @return \WC_Subscription|false Subscription object or false on failure.
	 */
	public function get_subscription( $order ) {
        if ( ! $this->is_subscriptions_active() || ! function_exists( 'wcs_get_subscription' ) ) {
            return false;
        }
        return wcs_get_subscription( $order );
    }

    /**
	 * Whether order is a subscription.
	 *
	 * Wrapper function for wcs_is_subscription
	 *
	 * @param WC_Order $order Order object.
	 * @return bool Whether order is a subscription.
	 */
	public function order_is_subscription( $order ) {
        if ( ! $this->is_subscriptions_active() || ! function_exists( 'wcs_is_subscription' ) ) {
            return false;
        }
        return wcs_is_subscription( $order );
    }

    /**
     * Initialize subscription-related hooks
     * Called during gateway initialization if WooCommerce Subscriptions is active
     * Only handles scheduled subscription payments - all other subscription management is handled by WooCommerce Subscriptions plugin
     */
    public function init_subscription_hooks() {
        if ( ! $this->is_subscriptions_active() ) {
            return;
        }
        if ( self::$hooks_initialized ) {
            return;
        }
        foreach ( $this->supported_gateways as $gateway_id ) {
            add_action( 'woocommerce_scheduled_subscription_payment_' . $gateway_id, array( $this, 'scheduled_subscription_payment_handler' ), 10, 2 );
        }
        self::$hooks_initialized = true;
    }

    public function scheduled_subscription_payment_handler( $amount_to_charge, $renewal_order ) {
        $filter = current_filter();
        $prefix = 'woocommerce_scheduled_subscription_payment_';
        $gateway_id = substr( $filter, strlen( $prefix ) );
        return $this->scheduled_subscription_payment( $gateway_id, $amount_to_charge, $renewal_order );
    }

    /**
     * Process scheduled subscription payment
     * Called by WooCommerce Subscriptions for automatic renewals
     * Uses parent order transaction ID instead of saved tokens
     * Invoked by per-gateway wrappers and records the gateway ID
     * 
     * @param string   $gateway_id       Gateway identifier, sent in merchantCustomData
     * @param float    $amount_to_charge The amount to charge
     * @param WC_Order $renewal_order    The renewal order
     */
    public function scheduled_subscription_payment( $gateway_id, $amount_to_charge, $renewal_order ) {
        try {
            $this->gateway->logger->info( 'Processing scheduled subscription payment for gateway ' . $gateway_id . ' order #' . $renewal_order->get_id() . ' Amount: ' . $amount_to_charge );

            if ( ! function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
                throw new \Exception( 'WooCommerce Subscriptions function not available' );
            }

            $subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
            if ( empty( $subscriptions ) ) {
                throw new \Exception( 'No subscription found' );
            }

            $subscription = array_shift( $subscriptions );
            $transaction_id = $subscription->get_meta( $this->get_meta_key( 'parent_transaction_id' ) );

            if ( empty( $transaction_id ) ) {
                throw new \Exception( 'No parent transaction ID found in subscription #' . $subscription->get_id() );
            }

            $request_data = [
                'client_id' => $this->gateway->client_id,
                'client_secret' => $this->gateway->client_secret,
                'terminal' => $this->gateway->terminal,
                'invoiceId' => strval($renewal_order->get_order_number()),
                'amount' => (float) $amount_to_charge,
                'parentTransactionId' => $transaction_id,
                'merchantCustomData' => json_encode([
                    'renewalOrderId' => $renewal_order->get_id(),
                    'parentOrderId' => $subscription->get_meta( $this->get_meta_key( 'parent_order_id' ) ),
                    'subscriptionId' => $subscription->get_id(),
                    'gatewayId' => $gateway_id,
                ])
            ];

            if ( ! empty( $this->gateway->configHelper->get_transaction_type() ) ) {
                $request_data['transactionType'] = $this->gateway->configHelper->get_transaction_type();
            }

            $this->gateway->logger->info( 'Starting payment for subscription #' . $subscription->get_id() . ' renewal order #' . $renewal_order->get_id() . ' with request data: ' . json_encode( $request_data ) );

            $result = $this->gateway->dnaPayment->recurring($request_data);

            if ( $result && isset( $result['success'] ) && $result['success'] === true ) {
                $new_status = $this->gateway->orderHelper->payment_complete( $renewal_order, $result, $result['settled'], 'scheduled_subscription_payment' );
                $this->gateway->logger->info( 'Payment completed for subscription #' . $subscription->get_id() . ' renewal order #' . $renewal_order->get_id() . ' with transaction ID ' . $result['id'] . ' and new status ' . $new_status );
            } else {
                $error_message = 'Recurring payment failed';
                if (isset($result['id'])) {
                    $error_message .= ' (Transaction ID: ' . $result['id'] . ')';
                }
                if (isset($result['errorCode'])) {
                    $error_message .= ' (Error code: ' . $result['errorCode'] . ')';
                }
                if (isset($result['message'])) {
                    $error_message .= ': ' . $result['message'];
                }
                throw new \Exception($error_message);
            }
        } catch ( \Exception $e ) {
            $this->gateway->logger->error( 'Subscription payment failed for order #' . $renewal_order->get_id() . ': ' . $e->getMessage() );
            $renewal_order->update_status( 'failed', sprintf( __( 'DNA Payments subscription payment failed: %s', \WC_DNA_Payments::$text_domain ), $e->getMessage() ) );
        }
    }

    public function save_payment_meta_to_subscriptions( \WC_Order $order, $input ) {
        if ( ! $this->is_subscriptions_active() ) {
            return;
        }
        if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            return;
        }

        $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'parent' ) );
        foreach ( $subscriptions as $subscription ) {
            $this->save_payment_meta_to_subscription( $subscription, $input, $order->get_id() );
        }
    }

    private function save_payment_meta_to_subscription( \WC_Subscription $subscription, $input, $parent_order_id = null ) {
        $subscription->update_meta_data( $this->get_meta_key( 'parent_transaction_id' ), $input['id'] );
        $subscription->update_meta_data( $this->get_meta_key( 'payment_method' ), empty( $input['paymentMethod'] ) ? '' : $input['paymentMethod'] );
        $subscription->update_meta_data( $this->get_meta_key( 'card_token_id' ), empty( $input['cardTokenId'] ) ? '' : $input['cardTokenId'] );

        // Do not update parent_order_id here; it should remain the original parent order
        if ( ! is_null( $parent_order_id ) ) {
            $subscription->update_meta_data( $this->get_meta_key( 'parent_order_id' ), $parent_order_id );
        }

        $subscription->save();
        $this->gateway->logger->info( 'Saved subscription parent transaction ID ' . $input['id'] . ' for subscription #' . $subscription->get_id() );
    }

    /**
     * Change the payment method for a subscription.
     *
     * @param \WC_Subscription $subscription The subscription object.
     * @param array             $input       Raw input data (must contain gateway_id).
     * @throws \Exception If the payment method cannot be changed.
     */
    public function change_subscription_payment_method( \WC_Subscription $subscription, array $input ) {
        $custom_data = $this->gateway->orderHelper->parse_merchant_custom_data( $input );
        $gateway_id  = $custom_data['gateway_id'] ?? '';

        if ( empty( $gateway_id ) ) {
            throw new \Exception( 'Gateway (Payment method) ID is missing' );
        }

        if ( $this->is_same_payment_string( $subscription, $input ) ) {
            throw new \Exception( 'New payment method cannot be the same as the current one' );
        }

        $payment_meta = apply_filters( 'woocommerce_subscription_payment_meta', [], $subscription );
        if ( isset( $payment_meta[ $gateway_id ] ) ) {
            $payment_meta = $payment_meta[ $gateway_id ];
        } else {
            $payment_meta = [];
        }

        if ( ! class_exists( 'WC_Subscriptions_Change_Payment_Gateway' )
            || ! method_exists( 'WC_Subscriptions_Change_Payment_Gateway', 'update_payment_method' )
        ) {
            throw new \Exception( 'WC_Subscriptions_Change_Payment_Gateway::update_payment_method does not exist' );
        }

        \WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $subscription, $gateway_id, $payment_meta );
        $this->save_payment_meta_to_subscription( $subscription, $input );

        $account_id = isset( $input['accountId'] ) ? $input['accountId'] : '';
        $this->gateway->logger->info('Processed subscription change payment method for account ID ' . $account_id . ', subscription ID ' . $subscription->get_id() . ', transaction ID ' . $input['id']);
    }
}
