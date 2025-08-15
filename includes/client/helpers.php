<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WCPG_DNA_Payments\Utils\Helper;

class WC_DNA_Payments_Order_Client_Helpers {
    public static function numberFormat( $price ) {
        return floatval(number_format( $price, 2, '.', '' ));
    }

    public static function round( $price ) {
        $precision = 2;
        return round( $price, $precision );
    }

    /**
     * Validates if order line items are compatible with PayPal requirements
     * 
     * PayPal requires:
     * 1. No negative line item amounts
     * 2. Total calculation must match order total exactly
     * 
     * @param \WC_Order $order The WooCommerce order object to validate
     * @return bool True if order is valid for PayPal, false otherwise
     */
    public static function isPaypalLineItemsValid( \WC_Order $order ) {
        $negativeItemAmount = false;
        $calculatedTotal = 0;

        // Validate line items and fees
        foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item ) {
            $itemLineTotal = 0;
            
            if ( 'fee' === $item['type'] ) {
                $itemLineTotal = self::numberFormat( $item['line_total'] );
                $calculatedTotal += $itemLineTotal;
            } else {
                // Use subtotal to get price before discounts (consistent with order line generation)
                $itemLineTotal = self::numberFormat( $order->get_item_subtotal( $item, false ) );
                $calculatedTotal += $itemLineTotal * $item->get_quantity();
            }

            // PayPal doesn't accept negative amounts
            if ( $itemLineTotal < 0 ) {
                $negativeItemAmount = true;
            }
        }

        // Verify total calculation matches order total
        $expectedTotal = $calculatedTotal + $order->get_total_tax() + self::round( $order->get_shipping_total() ) - self::round( $order->get_total_discount() );
        $actualTotal = self::numberFormat( $order->get_total() );
        $mismatched_totals = self::numberFormat( $expectedTotal ) !== $actualTotal;

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
            $item_name = Helper::remove_non_latin1( $item->get_name() );
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

    public static function getSingItemOrderLines(\WC_Order $order) {
        return array(
            array(
                'name' => self::limitLength(self::getOrderItemNames($order)),
                'quantity' => 1,
                'unitPrice' => self::numberFormat($order->get_subtotal()),
                'totalAmount' => self::numberFormat($order->get_subtotal())
            )
        );
    }

    public static function isDNAPaymentOrder(WC_Order $order): bool {
        return in_array($order->get_payment_method(), [
            'dnapayments',
            'dnapayments_google_pay',
            'dnapayments_apple_pay',
        ]);
    }

    public static function saveCardToken( $input, $gateway_id ) {
        $user_id = $input['accountId'];
        $token_id = $input['cardTokenId'];

        $error_message = '';
            
        if (!isset($user_id) || empty($user_id)) {
            $error_message .= 'User ID is missing. ';
        }
        
        if (!isset($token_id) || empty($token_id)) {
            $error_message .= 'Card token is missing. ';
        }
        
        if (isset($input['paymentMethod']) && $input['paymentMethod'] != 'card') {
            $error_message .= 'Payment method must be card. ';
        }

        if (!empty($error_message)) {
            return 'Invalid card data provided. ' . $error_message;
        }

        function save_token($token, $input, $user_id, $token_id, $gateway_id) {
            $date_arr = explode("/", $input['cardExpiryDate']);

            // Set the token details
            $token->set_token( $token_id ); // Token ID
            $token->set_gateway_id( $gateway_id ); // Payment gateway ID
            
            // Check if methods exist before calling them
            if (method_exists($token, 'set_card_type')) {
                $token->set_card_type( $input['cardSchemeName'] ); // Card type
            } else if (method_exists($token, 'set_card_brand')) {
                // Alternative method name in some WooCommerce versions
                $token->set_card_brand( $input['cardSchemeName'] );
            }
            
            if (method_exists($token, 'set_last4')) {
                $token->set_last4( substr( $input['cardPanStarred'], -4 ) ); // Last 4 digits of the card number
            }
            
            $token->set_expiry_month( $date_arr[0] ); // Expiry month
            $token->set_expiry_year( '20' . $date_arr[1] ); // Expiry year
            $token->set_user_id( $user_id ); // User ID
    
            $token->update_meta_data( 'extra_data', [
                'cardSchemeId' => $input['cardSchemeId'],
                'cardName' => $input['cardholderName'],
                'panStar' => $input['cardPanStarred'],
                'expiryDate' => $input['cardExpiryDate'],
                'last4' => substr( $input['cardPanStarred'], -4 ),
                'cardType' => $input['cardSchemeName']
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
                // Get last4 from token, either via method or metadata
                $token_last4 = '';
                if (method_exists($token, 'get_last4')) {
                    try {
                        // @phpstan-ignore-next-line
                        $token_last4 = $token->get_last4();
                    } catch (Exception $e) {
                        // Fallback if method exists but throws an error
                        $extra_data = $token->get_meta('extra_data');
                        $token_last4 = $extra_data['last4'] ?? '';
                    }
                } else {
                    $extra_data = $token->get_meta('extra_data');
                    $token_last4 = $extra_data['last4'] ?? '';
                }
                
                if ( $token_last4 != substr( $input['cardPanStarred'], -4 ) ) {
                    save_token($token, $input, $user_id, $token_id, $gateway_id);
                    return '';
                }
            }

            return 'Card already exists and cannot be updated.';
        }

        // Create a new payment token
        $token = new WC_Payment_Token_CC();
        save_token($token, $input, $user_id, $token_id, $gateway_id);

        return '';
    }

    public static function getCardTokens( $user_id, $gateway_id ) {

        if ( ! isset( $user_id ) || empty( $user_id ) || $user_id == 0) {
            return [];
        }

        $tokens = WC_Payment_Tokens::get_customer_tokens( $user_id, $gateway_id );

        return array_values( array_map( function ($token) {
            $extra_data = $token->get_meta( 'extra_data' );
            
            // Get card type/brand, either via method or metadata
            $card_type = '';
            if (method_exists($token, 'get_card_type')) {
                try {
                    $card_type = $token->get_card_type();
                } catch (Exception $e) {
                    // Fallback if method exists but throws an error
                    $card_type = $extra_data['cardType'] ?? '';
                }
            } else if (method_exists($token, 'get_card_brand')) {
                // Alternative method name in some WooCommerce versions
                try {
                    $card_type = $token->get_card_brand();
                } catch (Exception $e) {
                    // Fallback if method exists but throws an error
                    $card_type = $extra_data['cardType'] ?? '';
                }
            } else {
                // Fall back to metadata
                $card_type = $extra_data['cardType'] ?? '';
            }

            return [
                'id' => $token->get_id(),
                'merchantTokenId' => $token->get_token(),
                'cardSchemeName' => $card_type,
                'cardSchemeId' => $extra_data['cardSchemeId'] ?? '',
                'cardName' => $extra_data['cardName'] ?? '',
                'panStar' => $extra_data['panStar'] ?? '',
                'expiryDate' => $extra_data['expiryDate'] ?? ''
            ];
        }, $tokens ) );
    }
}
