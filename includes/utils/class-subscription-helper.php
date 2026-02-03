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
        add_filter( 'woocommerce_subscription_payment_meta', array( $this, 'subscription_payment_meta' ), 10, 2 );
        add_filter( 'woocommerce_subscription_payment_method_to_display', array( $this, 'subscription_payment_method_to_display' ), 10, 2 );
        add_action( 'woocommerce_subscription_token_changed', array( $this, 'subscription_token_changed' ), 10, 3 );
        foreach ( $this->supported_gateways as $gateway_id ) {
            add_action( 'woocommerce_scheduled_subscription_payment_' . $gateway_id, array( $this, 'scheduled_subscription_payment' ), 10, 2 );
        }

        self::$hooks_initialized = true;
    }

    /**
     * Add DNA Payments-specific payment meta to subscription.
     * This data is shown in the subscription admin screen and used during renewal.
     *
     * @param array            $payment_meta Existing payment meta array.
     * @param \WC_Subscription $subscription Subscription object.
     * @return array Updated payment meta.
     */
    public function subscription_payment_meta( $payment_meta, $subscription ) {
        if ( ! Helper::is_dna_payments($subscription->get_payment_method())) {
            return $payment_meta;
        }

        if ( is_array($payment_meta) ) {
            $payment_meta[ $subscription->get_payment_method() ] = array(
                'post_meta' => array(                    
                    Helper::META_TOKEN => array(
                        'label' => __( 'DNA Payments Payment Token', \WC_DNA_Payments::$text_domain ),
                        'value' => $subscription->get_meta( Helper::META_TOKEN ) ?? '',
                    ),
                ),
            );
        }

        return $payment_meta;
    }

    /**
     * Handle subscription token change
     *
     * @param \WC_Subscription $subscription Subscription object.
     * @param \WC_Payment_Token_CC $new_token New token object.
     * @param \WC_Payment_Token_CC $old_token Old token object.
     */
    public function subscription_token_changed( $subscription, $new_token, $old_token ) {
        if ( ! Helper::is_dna_payments($subscription->get_payment_method())) {
            return;
        }

        $subscription->update_meta_data( Helper::META_CARD_TYPE, $new_token->get_card_type() );
        $subscription->update_meta_data( Helper::META_CARD_LAST4, $new_token->get_last4() );

        $parent_transaction_id = $new_token->get_meta( Helper::META_PARENT_TRANSACTION_ID );
        if ( empty( $parent_transaction_id ) ) {
            // This ensures the next payment requires the user to log in and pay manually.
            $subscription->set_requires_manual_renewal( true );

            // Add an Order Note for Admins
            $subscription->add_order_note( __( 'DNA Payments: Subscription payment token changed, but the new token is missing Parent transaction ID. Switched to Manual Renewal to ensure customer authentication on next payment.', \WC_DNA_Payments::$text_domain ) );

            // Add a Customer Notice if they are currently logged in (e.g., on My Account page)
            if ( is_user_logged_in() && ! defined( 'DOING_CRON' ) ) {
                wc_add_notice( __( 'Your subscription payment method has been updated, but you may need to verify your card details to ensure future renewals process successfully.', \WC_DNA_Payments::$text_domain ), 'notice' );
            }
        }

        $subscription->save();
    }

    /**
     * Display payment method to customer on subscription admin screen
     *
     * @param string           $payment_method_to_display The payment method to display.
     * @param \WC_Subscription $subscription Subscription object.
     * @return string Updated payment method to display.
     */
    public function subscription_payment_method_to_display( $payment_method_to_display, $subscription ) {
        if ( ! Helper::is_dna_payments($subscription->get_payment_method())) {
            return $payment_method_to_display;
        }

        $card_type = $subscription->get_meta( Helper::META_CARD_TYPE );
        $card_last4 = $subscription->get_meta( Helper::META_CARD_LAST4 );
        if ( ! empty( $card_type ) && ! empty( $card_last4 ) ) {
            $payment_method_to_display = sprintf( __( '%s (%s ending in %s)', \WC_DNA_Payments::$text_domain ), $subscription->get_payment_method_title(), $card_type, $card_last4 );
        }
        return $payment_method_to_display;
    }

    /**
     * Process scheduled subscription payment
     * Called by WooCommerce Subscriptions for automatic renewals
     * Uses parent order transaction ID instead of saved tokens
     * Invoked by per-gateway wrappers and records the gateway ID
     * 
     * @param float    $amount_to_charge The amount to charge
     * @param WC_Order $renewal_order    The renewal order
     */
    public function scheduled_subscription_payment( $amount_to_charge, $renewal_order ) {
        $renewal_order_id = $renewal_order instanceof \WC_Order ? $renewal_order->get_id() : null;
        $name = 'Scheduled subscription payment #' . $renewal_order_id;
        $gateway_id = $renewal_order->get_payment_method();
        $base_context = [
            'renewalOrderId' => $renewal_order_id,
            'amount'         => $amount_to_charge,
            'gateway_id'  => $gateway_id,
        ];

        try {
            $this->gateway->logger->info( $name . ' started:', $base_context );

            if ( ! function_exists( 'wcs_get_subscriptions_for_renewal_order' ) ) {
                throw new \Exception( 'WooCommerce Subscriptions function not available' );
            }

            $subscriptions = wcs_get_subscriptions_for_renewal_order( $renewal_order );
            if ( empty( $subscriptions ) ) {
                throw new \Exception( 'No subscription found' );
            }
            $subscription = array_shift( $subscriptions );

            $token_str = $subscription->get_meta( Helper::META_TOKEN );
            if ( empty( $token_str ) ) {
                throw new \Exception( 'No payment token found in subscription #' . $subscription->get_id() );
            }

            $token = $this->gateway->paymentTokenHelper->find_token( $renewal_order->get_user_id(), $gateway_id, $token_str );
            if ( ! $token ) {
                throw new \Exception( 'Payment token not found for user #' . $renewal_order->get_user_id() . ' and token_str #' . $token_str );
            }

            $renewal_order->update_meta_data( Helper::META_CARD_TYPE, $token->get_card_type() );
            $renewal_order->update_meta_data( Helper::META_CARD_LAST4, $token->get_last4() );
            $renewal_order->save();

            $transaction_id = $token->get_meta( Helper::META_PARENT_TRANSACTION_ID );
            if ( empty( $transaction_id ) ) {
                throw new \Exception( 'No parent transaction ID found in token #' . $token->get_token_id() );
            }

            $context = array_merge(
                $base_context,
                $this->get_subscription_context( $subscription ),
            );

            $request_data = [
                'client_id' => $this->gateway->client_id,
                'client_secret' => $this->gateway->client_secret,
                'terminal' => $this->gateway->terminal,
                'invoiceId' => strval($renewal_order->get_order_number()),
                'amount' => (float) $amount_to_charge,
                'parentTransactionId' => $transaction_id,
                'merchantCustomData' => json_encode([
                    'renewalOrderId' => $renewal_order->get_id(),
                    'subscriptionId' => $subscription->get_id(),
                    'gatewayId' => $gateway_id,
                ])
            ];

            if ( ! empty( $this->gateway->configHelper->get_transaction_type() ) ) {
                $request_data['transactionType'] = $this->gateway->configHelper->get_transaction_type();
            }

            $this->gateway->logger->info(
                $name . ' processing:',
                array_merge(
                    $context,
                    [
                        'invoiceId'       => $request_data['invoiceId'] ?? null,
                        'transactionType' => $request_data['transactionType'] ?? null,
                    ]
                )
            );

            $result = $this->gateway->dnaPayment->recurring($request_data);

            if ( $result && isset( $result['success'] ) && $result['success'] === true ) {
                $new_status = $this->gateway->orderHelper->payment_complete( $renewal_order, $result, $result['settled'] ?? false, $name );
                $this->gateway->logger->info(
                    $name . ' finished:' ,
                    array_merge(
                        $context,
                        $this->get_recurring_result_log_context( $result ),
                        [ 'orderNewStatus' => $new_status ]
                    )
                );
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
            $this->gateway->logger->error(
                $name . ' failed:',
                array_merge(
                    $base_context,
                    isset( $subscription ) ? $this->get_subscription_context( $subscription ) : [],
                    [ 'errorMessage' => $e->getMessage() ]
                )
            );
            $renewal_order->update_status( 'failed', sprintf( __( 'DNA Payments: %s failed: %s', \WC_DNA_Payments::$text_domain ), $name, $e->getMessage() ) );
        }
    }

    public function save_payment_meta_to_subscriptions( \WC_Order $order, array $input ) {
        if ( ! $this->is_subscriptions_active() ) {
            return;
        }
        if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) {
            return;
        }

        $subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'any' ) );
        foreach ( $subscriptions as $subscription ) {
            if ( $subscription->get_payment_method() !== $order->get_payment_method() ) {
                $this->change_subscription_payment_method( $subscription, $order->get_payment_method() );
            }
            $this->save_payment_meta_to_subscription( $subscription, $input );
        }
    }

    private function save_payment_meta_to_subscription( \WC_Subscription $subscription, array $input ) {
        $card_info = $this->gateway->paymentTokenHelper->get_card_info( $input );
        $subscription->update_meta_data( Helper::META_TOKEN,  $card_info['token'] ?? '' );
        $subscription->update_meta_data( Helper::META_CARD_TYPE, $card_info['card_type'] ?? '' );
        $subscription->update_meta_data( Helper::META_CARD_LAST4, $card_info['last4'] ?? '' );
        $subscription->update_meta_data( Helper::META_PAYMENT_METHOD, empty( $input['paymentMethod'] ) ? '' : $input['paymentMethod'] );
        $subscription->save();
        $this->gateway->logger->info('Saved subscription payment metadata', $this->get_subscription_context( $subscription ));
    }

    /**
     * Change the payment method for a subscription.
     *
     * @param \WC_Subscription $subscription The subscription object.
     * @param array             $input       Raw input data (must contain gateway_id).
     * @throws \Exception If the payment method cannot be changed.
     */
    public function handle_change_subscription_payment_method( \WC_Subscription $subscription, array $input ) {
        $custom_data = Helper::parse_merchant_custom_data( $input );
        if ( ! empty( $custom_data['error'] ) ) {
            $this->gateway->logger->error( $custom_data['error'] );
        }
        $gateway_id = $custom_data['gateway_id'] ?? '';
        $name    = 'Change subscription payment method #' . $subscription->get_id();
        $context = array_merge(
            [
                'gatewayId'     => $gateway_id,
                'paymentMethod' => $input['paymentMethod'] ?? null,
                'cardTokenId'   => $input['cardTokenId'] ?? null,
                'transactionId' => $input['id'] ?? null,
            ],
            $this->get_subscription_context( $subscription ),
        );

        $this->gateway->logger->info( $name . ' started', $context );

        $this->change_subscription_payment_method( $subscription, $gateway_id );

        $token_result = $this->gateway->paymentTokenHelper->add_token($input, $gateway_id, (bool) ($custom_data['allowed_recurring'] ?? false));
        if ( empty( $token_result['error'] ) ) {
            $this->gateway->logger->info($name . ': Card token saved ');
        } else {
            $this->gateway->logger->error($name . ': Card token not saved. Error: ' . $token_result['error']);
        }

        $this->save_payment_meta_to_subscription( $subscription, $input );

        $this->gateway->logger->info( $name . ' finished:', $context );
    }

    /**
     * Change the payment method for a subscription.
     *
     * @param \WC_Subscription $subscription The subscription object.
     * @param string            $payment_method The payment method ID.
     * @throws \Exception If the payment method cannot be changed.
     */
    public function change_subscription_payment_method( \WC_Subscription $subscription, string $payment_method ) {
        if ( empty( $payment_method ) ) {
            throw new \Exception( 'Gateway (Payment method) ID is missing' );
        }

        if ( ! class_exists( 'WC_Subscriptions_Change_Payment_Gateway' )
            || ! method_exists( 'WC_Subscriptions_Change_Payment_Gateway', 'update_payment_method' )
        ) {
            throw new \Exception( 'WC_Subscriptions_Change_Payment_Gateway::update_payment_method does not exist' );
        }

        \WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $subscription, $payment_method );
    }

    /**
     * Build a standard subscription log context.
     *
     * @param \WC_Subscription|null $subscription Subscription.
     * @param array                 $extra        Additional key/value pairs.
     * @return array
     */
    private function get_subscription_context( $subscription = null, array $extra = [] ): array {
        if ( ! $subscription instanceof \WC_Subscription ) {
            return [];
        }

        $context = [
            'id'            => $subscription->get_id(),
            'paymentMethod' => $subscription->get_payment_method(),
            'meta' => [
                'paymentMethod' => $subscription->get_meta( Helper::META_PAYMENT_METHOD ),
                'paymentToken'  => $subscription->get_meta( Helper::META_TOKEN ),
                'cardType'      => $subscription->get_meta( Helper::META_CARD_TYPE ),
                'cardLast4'     => $subscription->get_meta( Helper::META_CARD_LAST4 ),
            ]
        ];

        return [
            'subscription' => array_merge( $context, $extra ),
        ];
    }

    /**
     * Reduce recurring response data to safe log context.
     *
     * @param array $result Recurring payment result.
     * @return array
     */
    private function get_recurring_result_log_context( array $result ): array {
        return [
            'recurring_result' => [
                'transactionId' => $result['id'] ?? null,
                'success'       => $result['success'] ?? null,
                'settled'       => $result['settled'] ?? null,
                'status'        => $result['status'] ?? null,
                'errorCode'     => $result['errorCode'] ?? null,
                'message'       => $result['message'] ?? null,
            ]
        ];
    }
}
