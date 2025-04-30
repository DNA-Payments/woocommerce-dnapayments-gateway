<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_DNA_Payments_Order_Client_Helpers {
    public static function numberFormat( $price ) {
        return floatval(number_format( $price, 2, '.', '' ));
    }

    public static function round( $price ) {
        $precision = 2;
        return round( $price, $precision );
    }

    public static function isPaypalLineItemsValid( $order ) {
        $negativeItemAmount = false;
        $calculatedTotal     = 0;

        // Products.
        foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
            if ( 'fee' === $item['type'] ) {
                $itemLineTotal   = self::numberFormat( $item['line_total'] );
                $calculatedTotal += $itemLineTotal;
            } else {
                $itemLineTotal   = self::numberFormat( $order->get_item_subtotal( $item, false ) );
                $calculatedTotal += $itemLineTotal * $item->get_quantity();
            }

            if ( $itemLineTotal < 0 ) {
                $negativeItemAmount = true;
            }
        }

        $mismatched_totals = self::numberFormat( $calculatedTotal + $order->get_total_tax() + self::round( $order->get_shipping_total() ) - self::round( $order->get_total_discount() ) ) !== self::numberFormat( $order->get_total() );
        return ! $negativeItemAmount && ! $mismatched_totals;
    }

    /**
     * Limit length of an arg.
     *
     * @param  string  $string Argument to limit.
     * @param  integer $limit Limit size in characters.
     * @return string
     */
    public static function limitLength( $string, $limit = 127 ) {
        $str_limit = $limit - 3;
        if ( function_exists( 'mb_strimwidth' ) ) {
            if ( mb_strlen( $string ) > $limit ) {
                $string = mb_strimwidth( $string, 0, $str_limit ) . '...';
            }
        } else {
            if ( strlen( $string ) > $limit ) {
                $string = substr( $string, 0, $str_limit ) . '...';
            }
        }
        return $string;
    }


    /**
     * Get order item names as a string.
     *
     * @param  WC_Order $order Order object.
     * @return string
     */
    public static function getOrderItemNames( $order ) {
        $item_names = array();

        foreach ( $order->get_items() as $item ) {
            $item_name = $item->get_name();
            $item_meta = wp_strip_all_tags(
                wc_display_item_meta(
                    $item,
                    array(
                        'before'    => '',
                        'separator' => ', ',
                        'after'     => '',
                        'echo'      => false,
                        'autop'     => false,
                    )
                )
            );

            if ( $item_meta ) {
                $item_name .= ' (' . $item_meta . ')';
            }

            $item_names[] = $item_name . ' x ' . $item->get_quantity();
        }

        return apply_filters( 'woocommerce_paypal_get_order_item_names', implode( ', ', $item_names ), $order );
    }

    public static function getAmountBreakdown(WC_Abstract_order $order) {
        return array(
            'itemTotal' => array('totalAmount' => self::numberFormat($order->get_subtotal())),
            'shipping' => array('totalAmount' => self::numberFormat($order->get_shipping_total())),
            'taxTotal' => array('totalAmount' => self::numberFormat($order->get_total_tax())),
            'discount' => array('totalAmount' => self::numberFormat($order->get_total_discount()))
        );
    }

    public static function getBillingAddress(WC_Abstract_order $order) {
        $state = $order->get_billing_state();
        return array(
            'firstName' => $order->get_billing_first_name(),
            'lastName'  => $order->get_billing_last_name(),
            'addressLine1'  => $order->get_billing_address_1(),
            'addressLine2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            // User can write text that does not match ISO 3166 state code
            // 'region'      => strlen($state) >= 0 && strlen($state) <= 3 ? $state : '',
            'postalCode'   => $order->get_billing_postcode(),
            'phone'      => $order->get_billing_phone(),
            'country'    => $order->get_billing_country()
        );
    }

    public static function getShippingAddress(WC_Abstract_order $order) {
        if(!$order->needs_shipping_address()) return null;
        $state = $order->get_shipping_state();

        return array(
            'firstName' => $order->get_shipping_first_name(),
            'lastName'  => $order->get_shipping_last_name(),
            'addressLine1'  => $order->get_shipping_address_1(),
            'addressLine2'  => $order->get_shipping_address_2(),
            'city'       => $order->get_shipping_city(),
            // User can write text that does not match ISO 3166 state code
            // 'region'      => strlen($state) >= 0 && strlen($state) <= 3 ? $state : '',
            'postalCode'   => $order->get_shipping_postcode(),
            'phone'      => self::getShippingPhone($order),
            'country'    => $order->get_shipping_country()
        );
    }

    public static function getShippingPhone(WC_Abstract_order $order) {
        $shipping_phone = '';
        if (version_compare( WC_VERSION, '5.6.0', '<' )) {
            $shipping_phone = $order->get_meta('_shipping_phone');
        } else {
            $shipping_phone = $order->get_shipping_phone();
        }
        return $shipping_phone ? $shipping_phone : $order->get_billing_phone();
    }

    public static function getSingItemOrderLines(WC_Abstract_order $order) {
        return array(
            array(
                'name' => self::limitLength(self::getOrderItemNames($order)),
                'quantity' => 1,
                'unitPrice' => self::numberFormat($order->get_subtotal()),
                'totalAmount' => self::numberFormat($order->get_subtotal())
            )
        );
    }

    public static function getOrderLines(WC_Abstract_order $order, $forceOneItem = false) {
        if($forceOneItem) {
            return self::getSingItemOrderLines($order);
        }
        $orderLines = [];

        foreach ($order->get_items() as $item_id => $item) {
            $total = self::numberFormat($item->get_total());
            $product = $item->get_product();
            $image_id  = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
            
            $orderLines[] = array(
                'reference' => strval($product->get_id()),
                'name' => html_entity_decode( wc_trim_string( $item->get_name() ? wp_strip_all_tags( $item->get_name() ) : __( 'Item', 'woocommerce' ), 127 ), ENT_NOQUOTES, 'UTF-8' ),
                'quantity' => $item->get_quantity(),
                'unitPrice' => self::numberFormat(($item->get_total()/$item->get_quantity())),
                'imageUrl' => $image_url,
                'productUrl' => $product->get_permalink(),
                'totalAmount' => $total
            );
        }

        return $orderLines;
    }

    public static function isDNAPaymentOrder(WC_Order $order): bool {
        return in_array($order->get_payment_method(), [
            'dnapayments',
            'dnapayments_google_pay',
            'dnapayments_apple_pay',
        ]);
    }

    public static function saveCardToken( WP_REST_Request $input, $gateway_id ) {
        $user_id = $input['accountId'];
        $token_id = $input['cardTokenId'];

        if (
            !isset( $user_id ) || empty( $user_id ) || !isset($token_id) ||  empty($token_id) ||
            $input['paymentMethod'] != 'card'
        ) {
            return false;
        }

        function save_token($token, $input, $user_id, $token_id, $gateway_id) {
            $date_arr = explode("/", $input['cardExpiryDate']);

            // Set the token details
            $token->set_token( $token_id ); // Token ID
            $token->set_gateway_id( $gateway_id ); // Payment gateway ID
            $token->set_card_type( $input['cardSchemeName'] ); // Card type
            $token->set_last4( substr( $input['cardPanStarred'], -4 ) ); // Last 4 digits of the card number
            $token->set_expiry_month( $date_arr[0] ); // Expiry month
            $token->set_expiry_year( '20' . $date_arr[1] ); // Expiry year
            $token->set_user_id( $user_id ); // User ID
    
            $token->update_meta_data( 'extra_data', [
                'cardSchemeId' => $input['cardSchemeId'],
                'cardName' => $input['cardholderName'],
                'panStar' => $input['cardPanStarred'],
                'expiryDate' => $input['cardExpiryDate']
            ] );

            // Save token
            $token->save();
        }

        $tokens = array_filter(
            WC_Payment_Tokens::get_customer_tokens( $user_id, $gateway_id ),
            function ($token) use ($token_id) {
                return $token->get_token() == $token_id;
            }
        );

        if ( count($tokens) > 0 ) {
            foreach ($tokens as $token) {
                if ( $token->get_last4() != substr( $input['cardPanStarred'], -4 ) ) {
                    save_token($token, $input, $user_id, $token_id, $gateway_id);
                    return true;
                }
            }

            return false;
        }

        // Create a new payment token
        $token = new WC_Payment_Token_CC();
        save_token($token, $input, $user_id, $token_id, $gateway_id);

        return true;
    }

    public static function getCardTokens( $user_id, $gateway_id ) {

        if ( ! isset( $user_id ) || empty( $user_id ) || $user_id == 0) {
            return [];
        }

        $tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, $gateway_id );

        return array_values( array_map( function ($token) {
            $extra_data = $token->get_meta( 'extra_data' );

            return [
                'id' => $token->get_id(),
                'merchantTokenId' => $token->get_token(),
                'cardSchemeName' => $token->get_card_type(),
                'cardSchemeId' => $extra_data['cardSchemeId'],
                'cardName' => $extra_data['cardName'],
                'panStar' => $extra_data['panStar'],
                'expiryDate' => $extra_data['expiryDate']
            ];
        }, $tokens ) );
    }
}
