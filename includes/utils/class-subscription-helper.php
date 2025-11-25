<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Subscription Helper Class
 * 
 * Handles all WooCommerce Subscriptions related functionality for DNA Payments Gateway
 */
class SubscriptionHelper {

    /**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    /**
     * List of gateway IDs that support subscription payments.
     * These gateways will have scheduled subscription payment hooks registered.
     *
     * @var string[]
     */
    private $supported_gateways = [ 'dnapayments', 'dnapayments_google_pay', 'dnapayments_apple_pay' ];

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
		// Call WooCommerce Subscriptions function if available
		return call_user_func( 'wcs_order_contains_subscription', $order );
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
        foreach ( $this->supported_gateways as $gateway_id ) {
            add_action( 'woocommerce_scheduled_subscription_payment_' . $gateway_id, array( $this, 'scheduled_subscription_payment_handler' ), 10, 2 );
        }
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

            $subscriptions = call_user_func( 'wcs_get_subscriptions_for_renewal_order', $renewal_order );
            if ( empty( $subscriptions ) ) {
                throw new \Exception( 'No subscription found for renewal order #' . $renewal_order->get_id() );
            }

            $subscription = array_shift( $subscriptions );

            // Get parent order from subscription
            $parent_order = $subscription->get_parent();
            if ( ! $parent_order ) {
                throw new \Exception( 'No parent order found for subscription #' . $subscription->get_id() );
            }
            
            // Get transaction ID from parent order
            $transaction_id = $parent_order->get_meta( '_dnapayments_transaction_id' );
            if ( empty( $transaction_id ) ) {
                $transaction_id = $parent_order->get_meta( 'transaction_id' );
            }
            if ( empty( $transaction_id ) ) {
                throw new \Exception( 'No transaction ID found in parent order #' . $parent_order->get_id() );
            }
            
            $this->gateway->logger->info( 'Using parent order #' . $parent_order->get_id() . ' transaction ID: ' . $transaction_id );

            $request_data = [
                'client_id' => $this->gateway->client_id,
                'client_secret' => $this->gateway->client_secret,
                'terminal' => $this->gateway->terminal,
                'invoiceId' => strval($renewal_order->get_order_number()),
                'amount' => $amount_to_charge,
                'parentTransactionId' => $transaction_id,
                'merchantCustomData' => json_encode([
                    'orderId' => $renewal_order->get_id(),
                    'parentOrderId' => $parent_order->get_id(),
                    'subscriptionId' => $subscription->get_id(),
                    'gatewayId' => $gateway_id,
                ])
            ];

            if ( ! empty( $this->gateway->configHelper->get_transaction_type() ) ) {
                $request_data['transactionType'] = $this->gateway->configHelper->get_transaction_type();
            }

            $result = $this->gateway->dnaPayment->recurring($request_data);

            if ( $result && isset( $result['success'] ) && $result['success'] === true ) {
                $renewal_order->payment_complete( $result['id'] );
                $this->gateway->logger->info( 'Subscription payment completed for order #' . $renewal_order->get_id() );
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
}