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

    public function get_payment_data_from_order( \WC_Order $order, $store_card_on_file = false ) {
        $payment_data = array(
            'invoiceId' => strval( $order->get_order_number() ),
            'description' => $this->gateway->get_option('gatewayOrderDescription'),
            'amount' => floatval( $order->get_total() ),
            'currency' => $order->get_currency(),
            'language' => 'en-gb',
            'paymentSettings' => array_merge(
                $this->get_payment_settings(),
                array(
                    'returnUrl' => $this->get_return_url_from_order($order),
                    'failureReturnUrl' => $this->get_return_url_from_order($order, true),
                ),
            ),
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
            'amountBreakdown' => $this->get_amount_breakdown_from_order( $order ),
            'orderLines' => $this->get_order_lines_from_order( $order ),
            'merchantCustomData' => json_encode( array(
                'orderId' => $order->get_id(),
                'storeCardOnFile' => $store_card_on_file
            ) ),
        );

        $this->update_transaction_type( $payment_data );
        return $payment_data;
    }

    public function get_payment_data_from_cart( \WC_Checkout $checkout, \WC_Cart $cart, \WC_Customer $customer ) {
        $posted_data = $checkout->get_posted_data();
        $payment_data = [
            'description' => $this->gateway->get_option('gatewayOrderDescription'),
            'amount' => floatval($cart->total),
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
            'amountBreakdown' => $this->get_amount_breakdown_from_cart( $cart ),
            'orderLines' => $this->get_order_lines_from_cart( $cart ),
        ];

        $this->update_transaction_type( $payment_data );
        return $payment_data;
    }

    public function get_payment_data_from_customer( \WC_Customer $customer, $invoice_id ) {

        function get_return_url($success) {
            $return_url = wc_get_endpoint_url('payment-methods', '', wc_get_page_permalink('myaccount'));
            return add_query_arg('result', $success ? 'success' : 'failure', $return_url);
        }

        return [
            'transactionType'   => 'VERIFICATION',
            'invoiceId'         => $invoice_id,
            'description'       => 'Add card to ' . get_bloginfo('name'),
            'amount'            => 0,
            'currency'          => 'GBP',
            'language'          => 'en-gb',
            'paymentSettings' => [
                'terminalId'        => $this->gateway->terminal,
                'returnUrl'         => get_return_url(true),
                'failureReturnUrl'  => get_return_url(false),
                'callbackUrl'       => get_rest_url(null, 'dnapayments/success-add-card'),
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

    private function get_amount_breakdown_from_cart( \WC_Cart $cart ) {
        return array(
            'itemTotal' => array('totalAmount' => Helper::number_format($cart->get_subtotal())),
            'shipping' => array('totalAmount' => Helper::number_format($cart->get_shipping_total())),
            'taxTotal' => array('totalAmount' => Helper::number_format($cart->get_taxes_total())),
            'discount' => array('totalAmount' => Helper::number_format($cart->get_discount_total()))
        );
    }

    private function get_amount_breakdown_from_order( \WC_Abstract_order $order ) {
        return array(
            'itemTotal' => array('totalAmount' => Helper::number_format($order->get_subtotal())),
            'shipping' => array('totalAmount' => Helper::number_format($order->get_shipping_total())),
            'taxTotal' => array('totalAmount' => Helper::number_format($order->get_total_tax())),
            'discount' => array('totalAmount' => Helper::number_format($order->get_total_discount()))
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

    public function get_return_url_from_order( \WC_Abstract_order $order, $is_failure = false ) {
        $return_url = $this->gateway->get_option( $is_failure ? 'failureBackLink' : 'backLink' );

        if ( empty($return_url ) ) {
            $return_url = $this->gateway->get_return_url( $order );
            return $is_failure ? add_query_arg( 'status', 'failed', $return_url ) : $return_url;
        }

        if ( Helper::is_url_absolute($return_url) ) {
            return $return_url;
        }

        return get_site_url(null, $return_url);
    }
}