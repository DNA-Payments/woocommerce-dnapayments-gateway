<?php

namespace WCPG_DNA_Payments\Utils;

use WCPG_DNA_Payments\Utils\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PaymentDataHelper {

    /**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;
    }

    public function get_payment_data_from_order( \WC_Order $order, $options = array() ) {
        $store_card_on_file = isset( $options['store_card_on_file'] ) ? (bool) $options['store_card_on_file'] : false;
        $invoice_id = isset( $options['invoice_id'] ) ? $options['invoice_id'] : null;
        $page = isset( $options['page'] ) ? $options['page'] : 'checkout';
        $is_change_payment_method = $page === 'change_payment_method';

        $payment_data = array_merge(
            array(
                'currency' => $order->get_currency(),
                'language' => 'en-gb',
                'customerDetails' => [
                    'email' => $order->get_billing_email(),
                    'accountDetails' => [
                        'accountId' => $order->get_customer_id() ? strval($order->get_customer_id()) : '',
                    ],
                    'billingAddress' => $this->get_address_from_order( $order, 'billing' ),
                    'deliveryDetails' => [
                        'deliveryAddress' => $this->get_address_from_order( $order, 'shipping' ),
                    ]
                ],
                'merchantCustomData' => json_encode( array(
                    'orderId' => $order->get_id(),
                    'storeCardOnFile' => $store_card_on_file
                ) ),
            ),
            $is_change_payment_method
                ? array(
                    'invoiceId' => $invoice_id,
                    'amount' => 0.0,
                    'description' => 'Subscription payment method change',
                    'transactionType' => 'VERIFICATION',
                    'paymentSettings' => array(
                        'terminalId' => $this->gateway->terminal,
                        'callbackUrl' => get_rest_url(null, 'dnapayments/change-payment-method'),
                        'returnUrl' => $this->get_payment_return_url( $order->get_id(), 'change_payment_method', true ),
                        'failureReturnUrl' => $this->get_payment_return_url( $order->get_id(), 'change_payment_method', false ),
                    ),
                )
                : array(
                    'invoiceId' => strval( $order->get_order_number() ),
                    'amount' => floatval( $order->get_total() ),
                    'description' => $this->gateway->get_option('gatewayOrderDescription'),
                    'amountBreakdown' => array(
                        'itemTotal' => array('totalAmount' => Helper::number_format($order->get_subtotal())),
                        'shipping' => array('totalAmount' => Helper::number_format($order->get_shipping_total())),
                        'taxTotal' => array('totalAmount' => Helper::number_format($order->get_total_tax())),
                        'discount' => array('totalAmount' => Helper::number_format($order->get_total_discount()))
                    ),
                    'orderLines' => $this->get_order_lines_from_order( $order ),
                    'paymentSettings' => array_merge(
                        $this->get_payment_settings(),
                        array(
                            'returnUrl' => $this->get_payment_return_url($order->get_id(), $page, true),
                            'failureReturnUrl' => $this->get_payment_return_url($order->get_id(), $page, false),
                        ),
                    ),
                )
        );

        $has_subscription = $is_change_payment_method || $this->gateway->subscriptionHelper->order_contains_subscription( $order );
        if ($has_subscription) {
            $payment_data['periodic'] = array(
                'periodicType' => 'ucof'
            );
        }

        if ( !$is_change_payment_method ) {
            $this->update_transaction_type( $payment_data );
        }

        return $payment_data;
    }

    public function get_payment_data_from_cart( \WC_Checkout $checkout, \WC_Cart $cart, \WC_Customer $customer ) {
        // Force WooCommerce to run all price hooks
        $cart->calculate_totals();

        $posted_data = $checkout->get_posted_data();
        $payment_data = [
            'description' => $this->gateway->get_option('gatewayOrderDescription'),
            'amount' => floatval($cart->get_total('edit')),
            'currency' => get_woocommerce_currency(),
            'language' => 'en-gb',
            'paymentSettings' => $this->get_payment_settings(),
            'customerDetails' => [
                'email' => $posted_data['billing_email'],
                'accountDetails' => [
                    'accountId' => $customer->get_id() ? strval( $customer->get_id() ) : '',
                ],
                'billingAddress' => $this->get_address_from_post_data( $posted_data, 'billing' ),
                'deliveryDetails' => [
                    'deliveryAddress' => $this->get_address_from_post_data( $posted_data, 'shipping' ),
                ]
            ],
            'amountBreakdown' => array(
                'itemTotal' => array('totalAmount' => Helper::number_format($cart->get_subtotal())),
                'shipping' => array('totalAmount' => Helper::number_format($cart->get_shipping_total())),
                'taxTotal' => array('totalAmount' => Helper::number_format($cart->get_total_tax())),
                'discount' => array('totalAmount' => Helper::number_format($cart->get_discount_total())),
            ),
            'orderLines' => $this->get_order_lines_from_cart( $cart ),
        ];

        $this->update_transaction_type( $payment_data );
        return $payment_data;
    }

    /**
     * Generate payment data for adding a new payment method (card) to a customer.
     *
     * @param \WC_Customer $customer   The WooCommerce customer object.
     * @param string       $invoice_id Unique invoice identifier for the verification transaction.
     * @return array Payment data payload used to initiate the card-addition flow.
     */
    public function get_payment_data_from_customer( \WC_Customer $customer, $invoice_id ) {
        return [
            'transactionType'   => 'VERIFICATION',
            'invoiceId'         => $invoice_id,
            'description'       => 'Add card to ' . get_bloginfo('name'),
            'amount'            => 0,
            'currency'          => 'GBP',
            'language'          => 'en-gb',
            'paymentSettings' => [
                'terminalId'        => $this->gateway->terminal,
                'callbackUrl'       => get_rest_url(null, 'dnapayments/success-add-card'),
                'returnUrl'         => $this->get_payment_return_url( 0, 'add_payment_method', true ),
                'failureReturnUrl'  => $this->get_payment_return_url( 0, 'add_payment_method', false ),
                
            ],
            'customerDetails' => [
                'email'             => $customer->get_billing_email(),
                'accountDetails' => [
                    'accountId'     => strval( $customer->get_id() )
                ],
                'billingAddress'    => $this->get_address_from_customer( $customer, 'billing' ),
                'deliveryDetails' => [
                    'deliveryAddress' => $this->get_address_from_customer( $customer, 'shipping' ),
                ]
            ]
        ];
    }

    private function update_transaction_type( &$payment_data ) {
        $transactionType = $this->gateway->configHelper->get_transaction_type();
        if ( !empty($transactionType) ) {
            $payment_data['transactionType'] = $transactionType;
        }
    }

    private function get_payment_settings() {
        return array(
            'terminalId' => $this->gateway->terminal,
            'callbackUrl' => get_rest_url(null, 'dnapayments/success'),
            'failureCallbackUrl' => get_rest_url(null, 'dnapayments/failure'),
        );
    }

    private function get_order_lines_from_cart( \WC_Cart $cart ) {
        $cart_lines = [];
        foreach ( $cart->get_cart() as $cart_item ) {
            $cart_lines[] = $this->get_order_line( $cart_item['data'], $cart_item['line_subtotal'], $cart_item['quantity'] );
        }
        return $cart_lines;
    }

    private function get_order_lines_from_order( \WC_Order $order ) {
        $isForcePayment = ! \WC_DNA_Payments_Order_Client_Helpers::isPaypalLineItemsValid( $order );

        if ( $isForcePayment ) {
            return \WC_DNA_Payments_Order_Client_Helpers::getSingItemOrderLines( $order, $isForcePayment );
        }

        $order_lines = [];
        foreach ($order->get_items() as $item) {
            /** @disregard P1013 Method get_product not found */
            $order_lines[] = $this->get_order_line( $item->get_product(), $item->get_subtotal(), $item->get_quantity() );
        }
        return $order_lines;
    }

    private function get_order_line( \WC_Product $product, float $total, int $quantity ) {
        $image_id  = $product->get_image_id();
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
        
        // Get product name and remove emojis
        $product_name = $product->get_name() ? wp_strip_all_tags($product->get_name()) : __('Item', 'woocommerce');
        $product_name = Helper::remove_non_latin1( $product_name );

        return array(
            'reference'     => strval( $product->get_id() ),
            'name'          => html_entity_decode( wc_trim_string($product_name, 127), ENT_NOQUOTES, 'UTF-8'),
            'imageUrl'      => $image_url,
            'productUrl'    => $product->get_permalink(),
            'quantity'      => $quantity,
            'unitPrice'     => Helper::number_format( $total / $quantity ),
            'totalAmount'   => Helper::number_format( $total ),
        );
    }

    private function get_field($data, $field) {
        return isset( $data[ $field ] ) ? sanitize_text_field( $data[ $field ] ) : '';
    }

    public function get_address_from_post_data($data, $section) {
        return Helper::clean_array( array(
            'firstName'     => $this->get_field( $data, $section . '_first_name' ),
            'lastName'      => $this->get_field( $data, $section . '_last_name' ),
            'addressLine1'  => $this->get_field( $data, $section . '_address_1' ),
            'addressLine2'  => $this->get_field( $data, $section . '_address_2' ),
            'city'          => $this->get_field( $data, $section . '_city' ),
            'postalCode'    => $this->get_field( $data, $section . '_postcode' ),
            'phone'         => $this->get_field( $data, $section . '_phone' ),
            'country'       => $this->get_field( $data, $section . '_country' ), 
        ) );
    }

    private function get_address_from_order( \WC_Order $order, $section ) {
        if ( $section === 'billing' ) {
            return Helper::clean_array( array(
                'firstName'     => $order->get_billing_first_name(),
                'lastName'      => $order->get_billing_last_name(),
                'addressLine1'  => $order->get_billing_address_1(),
                'addressLine2'  => $order->get_billing_address_2(),
                'city'          => $order->get_billing_city(),
                'postalCode'    => $order->get_billing_postcode(),
                'phone'         => $order->get_billing_phone(),
                'country'       => $order->get_billing_country()
            ) );
        }

        if( ! $order->needs_shipping_address() ) {
            return null;
        }

        $shipping_phone = '';
        if (version_compare( WC()->version, '5.6.0', '<' )) {
            $shipping_phone = $order->get_meta('_shipping_phone');
        } else {
            $shipping_phone = $order->get_shipping_phone();
        }
        if ( empty($shipping_phone) ) {
            $shipping_phone = $order->get_billing_phone();
        }

        return Helper::clean_array( array(
            'firstName'     => $order->get_shipping_first_name(),
            'lastName'      => $order->get_shipping_last_name(),
            'addressLine1'  => $order->get_shipping_address_1(),
            'addressLine2'  => $order->get_shipping_address_2(),
            'city'          => $order->get_shipping_city(),
            'postalCode'    => $order->get_shipping_postcode(),
            'phone'         => $shipping_phone,
            'country'       => $order->get_shipping_country()
        ) );
    }

    private function get_address_from_customer( \WC_Customer $customer, $section ) {
        if ($section === 'billing') {
            return Helper::clean_array( [
                'firstName'    => $customer->get_billing_first_name(),
                'lastName'     => $customer->get_billing_last_name(),
                'addressLine1' => $customer->get_billing_address_1(),
                'addressLine2' => $customer->get_billing_address_2(),
                'city'         => $customer->get_billing_city(),
                'postalCode'   => $customer->get_billing_postcode(),
                'phone'        => $customer->get_billing_phone(),
                'country'      => $customer->get_billing_country(),
            ] );
        }
    
        if (!$customer->get_shipping_address_1()) {
            return null;
        }

        $shipping_phone = '';
        if (version_compare( WC()->version, '5.6.0', '<' )) {
            $shipping_phone = $customer->get_meta( '_shipping_phone', true );
        } else {
            $shipping_phone = $customer->get_shipping_phone();
        }
        if ( empty($shipping_phone) ) {
            $shipping_phone = $customer->get_billing_phone();
        }
    
        return Helper::clean_array( [
            'firstName'    => $customer->get_shipping_first_name(),
            'lastName'     => $customer->get_shipping_last_name(),
            'addressLine1' => $customer->get_shipping_address_1(),
            'addressLine2' => $customer->get_shipping_address_2(),
            'city'         => $customer->get_shipping_city(),
            'postalCode'   => $customer->get_shipping_postcode(),
            'phone'        => $shipping_phone,
            'country'      => $customer->get_shipping_country(),
        ] );
    }

    /**
     * Build the return URL for a payment flow.
     *
     * @param int    $order_id The order (or subscription) ID.
     * @param string $page     Page identifier. Can be: change_payment_method, add_payment_method, pay_for_order
     * @param bool   $success  Whether the flow succeeded.
     * @return string The fully-qualified return URL.
     */
    public function get_payment_return_url( $order_id, $page, $success ) {
        // Build the base API URL for the gateway
        $base = WC()->api_request_url( $this->gateway->id );
        // Append query parameters to indicate order, page and state
        $url  = add_query_arg(
            [
                'order_id' => $order_id,
                'page'     => $page,
                'state'    => $success ? 'success' : 'failed',
            ],
            $base
        );
        return $url;
    }

    /**
     * Determine the current payment-related page context.
     *
     * Detects whether the shopper is on “Add Payment Method”, changing the payment method
     * for a subscription, or paying for a specific order.  Returns a canonical string
     * identifier that can be used to branch logic elsewhere in the gateway.
     *
     * @return string  One of: 'add_payment_method', 'change_payment_method', 'pay_for_order', 'checkout', 'cart', or empty string.
     */
    public function get_current_payment_page() {
        if ( is_add_payment_method_page() ) {
            return 'add_payment_method';
        }

        if ( is_wc_endpoint_url( 'order-pay' ) ) {
            return isset( $_GET['change_payment_method'] ) ? 'change_payment_method' : 'pay_for_order';
        }

        if ( is_checkout() ) {
            return 'checkout';
        }

        if ( is_cart() ) {
            return 'cart';
        }

        return '';
    }
}
