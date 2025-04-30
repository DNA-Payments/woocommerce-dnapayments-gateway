<?php

namespace WCPG_DNA_Payments\Utils;

class CheckoutValidation {

    public function validate_checkout_data( \WC_Checkout $checkout, \WC_Cart $cart ) {
        $errors = new \WP_Error();
        $posted_data = $checkout->get_posted_data();
    
        $this->validate_required_fields( $checkout, $posted_data, $errors );
        $this->validate_billing_field_lengths( $posted_data, $errors );
        $this->validate_cart_items( $cart, $errors );
    
        return $errors->has_errors() ? $errors : null;
    }

    private function validate_required_fields( \WC_Checkout $checkout, $data, &$errors ) {
        $checkout_fields = $checkout->get_checkout_fields();

        foreach ($checkout_fields as $section => $fields) {
            if ( $section === 'shipping' && empty( $data['ship_to_different_address'] ) ) {
                continue;
            }

            if ( $section === 'account' && empty( $data['createaccount'] ) ) {
                continue;
            }

            foreach ($fields as $field_key => $field_props) {
                if (!empty($field_props['required']) && empty($data[$field_key])) {
                    $errors->add($field_key, sprintf(__('The %s field is required.', \WC_DNA_Payments::$text_domain), $field_props['label'] ?? $field_key));
                }
            }

            $this->validate_postcode( $section, $data, $errors );
            $this->validate_phone( $section, $data, $errors );
        }
    
        // Validate email format
        if (!empty($data['billing_email']) && !is_email($data['billing_email'])) {
            $errors->add('billing_email', __('Invalid email address.', \WC_DNA_Payments::$text_domain));
        }

        // Validate Terms & Conditions checkbox
        if ( $data['terms-field'] === 1 && $data['terms'] === 0 ) {
            $errors->add( 'terms', __( 'You must accept the terms and conditions.', \WC_DNA_Payments::$text_domain ) );
        }
    }
    
    /**
     * Validate cart items.
     * @param WP_Error $errors
     */
    private function validate_cart_items( \WC_Cart $cart, &$errors ) {
        if ( $cart->is_empty() ) {
            $errors->add( 'cart', __( 'Your cart is empty.', \WC_DNA_Payments::$text_domain ) );
        }
    
        foreach ( $cart->get_cart() as $cart_item ) {
            $product = $cart_item['data'];
    
            if ( ! $product->is_in_stock() ) {
                $errors->add( 'cart', sprintf( __( '%s is out of stock.', \WC_DNA_Payments::$text_domain ), $product->get_name() ) );
            }
        }
    }

    /**
     * Validate billing field value lengths.
     * @param array    $data
     * @param WP_Error $errors
     */
    public function validate_billing_field_lengths( $data, &$errors ) {
        $field_limits = [
            'billing_country'    => [2, __('Country must be less than 2 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_city'       => [50, __('City must be less than 50 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_address_1'  => [50, __('Address must be less than 50 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_email'      => [256, __('Email must be less than 256 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_last_name'  => [32, __('Lastname must be less than 32 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_first_name' => [32, __('Firstname must be less than 32 symbols', \WC_DNA_Payments::$text_domain)],
            'billing_postcode'   => [13, __('Postcode must be less than 13 symbols', \WC_DNA_Payments::$text_domain)],
        ];
    
        foreach ( $field_limits as $field => [$max_length, $error_message] ) {
            if ( !empty( $data[$field] ) && strlen( $data[$field] ) > $max_length ) {
                $errors->add( $field, $error_message );
            }
        }
    }

        /**
     * Validate postcode using country-specific rules.
     * @param string   $section
     * @param array    $data
     * @param WP_Error $errors
     */
    private function validate_postcode( $section, $data, &$errors ) {
        $country_key = $section . '_country';
        $postcode_key = $section . '_postcode';
        $country = ! empty( $data[$country_key] ) ? $data[$country_key] : '';

        if (
            in_array( $section, [ 'billing', 'shipping' ] ) && 
            ! empty( $data[$postcode_key] ) &&
            ! \WC_Validation::is_postcode( $data[$postcode_key], $country )
        ) {
            $errors->add( $postcode_key, __( 'Postcode is not valid for the selected country.', \WC_DNA_Payments::$text_domain ) );
        }
    }

    /**
     * Validate phone number.
     * @param string   $section
     * @param array    $data
     * @param WP_Error $errors
     */
    private function validate_phone( $section, $data, &$errors ) {
        $phone_key = $section . '_phone';

        if (
            in_array( $section, [ 'billing', 'shipping' ] ) && 
            ! empty( $data[$phone_key] ) &&
            ! \WC_Validation::is_phone($data[$phone_key])
        ) {
            $errors->add( $phone_key, __( 'Phone number is invalid.', \WC_DNA_Payments::$text_domain ) );
        }
    }
}