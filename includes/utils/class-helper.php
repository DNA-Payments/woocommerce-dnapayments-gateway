<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Helper {
    public const META_PARENT_TRANSACTION_ID = '_dnapayments_parent_transaction_id';
    public const META_CARD_TYPE = '_dnapayments_card_type';
    public const META_CARD_LAST4 = '_dnapayments_card_last4';
    public const META_TOKEN = '_dnapayments_payment_token';
    public const META_PAYMENT_METHOD = '_dnapayments_payment_method';

    /**
     * Safely gets a sanitized string value from $_POST.
     *
     * @since 5.5.0
     *
     * @param string $key posted data key
     * @param string $default default value to return (default empty string)
     * @return string sanitized posted data value if key found, or default
     */
    public static function get_posted_value( $key, $default = '' ) {        
        // Only proceed if the key exists in $_POST
        if ( isset( $_POST[ $key ] ) ) {
            return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
        }
		// Return default value to avoid undefined variable issues
        return $default;
    }

	public static function number_format( $price ) {
        return floatval(number_format( $price, 2, '.', '' ));
    }

	public static function is_url_absolute($url)
    {
        $pattern = "/^(?:ftp|https?|feed):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
    (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
    (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
    (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

        return (bool) preg_match($pattern, $url);
    }

	public static function add_notice($message, $notice_type = 'success', $data = array()) {
        if( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice($message, $notice_type, $data);
        }
    }

    public static function is_empty( $value ): bool
    {
        if ( $value === null ) return true;
        if ( is_string($value) ) return trim($value) === '';
        if ( is_array($value) || $value instanceof \Countable ) return count($value) === 0;
        return false;
    }

	public static function merge_if_empty( $base, $override ) {
		if ( empty($base) ) {
			return $override;
		}

		foreach ($override as $key => $value) {
			if ( ! array_key_exists($key, $base) || empty($base[$key])) {
				$base[$key] = $value;
			}
		}

		return $base;
	}

	public static function clean_array($input) {
		$filtered = array_filter($input, function ($value) {
			return ! empty($value);
		});
	
		return empty($filtered) ? null : $filtered;
	}

    /**
     * Remove emojis from a string
     *
     * @param string $text The text to clean
     * @return string The text without emojis
     */
    public static function remove_emojis( $text ) {
        // Remove emojis using regex pattern
        $text = preg_replace('/[\x{1F000}-\x{1FFFF}]/u', '', $text);

		// Remove any remaining whitespace that might be left after emoji removal
        return trim( $text );
    }

    /**
     * Remove non-Latin1 characters from a string
     *
     * @param string $string The input string to clean
     * @return string The string with only Latin-1 characters
     */
    public static function remove_non_latin1($text) {
        // Keep only Latin-1 (ISO-8859-1) characters: 0x00-0xFF
        $text = preg_replace('/[^\x00-\xFF]/u', '', $text);

		// Remove any remaining whitespace that might be left after non-Latin1 removal
        return trim( $text );
    }

    /**
     * Safely decode a JSON string to an associative array.
     *
     * @param string $result_string Raw JSON string.
     * @return array Decoded array.
     * @throws \Exception If JSON decoding fails or result is null.
     */
    public static function parse_json_to_array( $result_string ) {
        $input = json_decode( $result_string, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new \Exception( json_last_error() );
        }

        if ( is_null( $input ) ) {
            throw new \Exception( __( 'Invalid JSON format', \WC_DNA_Payments::$text_domain ) );
        }

        return $input;
    }

    /**
	 * Check if current user has DNA email domain
	 * 
	 * @return bool
	 */
	public static function current_user_has_dna_email_domain() {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		$email = $user->user_email;
		$domain = substr( strrchr( $email, '@' ), 1 ); // get domain part

		$allowed_domains = [
			'dnapayments.com',
			'dnapaymentsgroup.com',
		];

		return in_array( strtolower( $domain ), $allowed_domains, true );
	}

    public static function get_current_user_id() {
        $user_id = (string) get_current_user_id();
        $is_guest = !isset($user_id) || empty($user_id) || $user_id === '0';
        return $is_guest ? '' : $user_id;
    }

    public static function build_invoice_id_with_prefix($prefix) {
        $delimiter = '|';
        $unique = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : date('d-m-y h:i:s');
        $prefix_str = trim(strval($prefix));

        // If prefix is empty, return only the unique part without delimiter
        if (empty($prefix_str)) {
            return $unique;
        }

        return $prefix_str . $delimiter . $unique;
    }

    public static function extract_prefix_from_invoice_id($invoice_id) {
        $delimiter = '|';
        $parts = explode($delimiter, strval($invoice_id), 2);

        // If no delimiter is found, return invoice id as prefix
        if (count($parts) === 1) {
            return $invoice_id;
        }

        return $parts[0];
    }

    // Parse merchant custom data
    public static function parse_merchant_custom_data( $input ) {
        $default_value = [
            'order_id' => null, 
            'store_card_on_file' => false,
            'gateway_id' => '',
            'allowed_recurring' => false,
            'error' => ''
        ];

        if ( isset($input['merchantCustomData']) ) {
            try {
                $customData = json_decode($input['merchantCustomData']);
                return [ 
                    'order_id' => $customData->orderId ?? null, 
                    'store_card_on_file' => $customData->storeCardOnFile ?? false,
                    'gateway_id' => $customData->gatewayId ?? '',
                    'allowed_recurring' => $customData->allowedRecurring ?? false,
                    'error' => '',
                ];
            } catch (\Exception $e) {
                $default_value['error'] = 'Error parsing merchantCustomData: ' . $e->getMessage();
            }
        }

        return $default_value;
    }

    /**
     * Normalize card scheme name to match WooCommerce standard
     *
     * @param string $scheme_name Card scheme name from API
     * @return string Normalized card scheme name
     */
    public static function normalize_card_scheme_name($scheme_name) {
        $normalized = strtolower(trim($scheme_name));

        switch ($normalized) {
            case 'amex':
            case 'amexcard': 
            case 'americanexpress':
            case 'american express':
            case 'american-express':
                return 'amex';

            case 'dci':
            case 'diners':
            case 'dinersclub':
            case 'diners club':
            case 'diners-club':
                return 'diners';

            case 'mc':
            case 'mastercard':
            case 'master card':
            case 'master-card':
                return 'mastercard';

            case 'upi':
            case 'unionpay':
            case 'union pay':
            case 'union-pay':
                return 'unionpay';

            case 'visa':
            case 'visacard':
            case 'visa card':
            case 'visa-card':
                return 'visa';

            case 'maestro':
            case 'maestrocard':
            case 'maestro card':
            case 'maestro-card':
                return 'mastercard';

            case 'discover':
            case 'discovercard':
            case 'discover card':
            case 'discover-card':
                return 'discover';

            default:
                return $normalized;
        }
    }

    public static function normalize_card_brand($brand) {
        $allowed_brands = [ 'amex', 'diners', 'discover', 'interac', 'jsb', 'mastercard', 'visa', 'unknown' ];

        if ( ! in_array( $brand, $allowed_brands, true ) ) {
            $brand = 'unknown';
        }

        return $brand;
    }

    /**
     * Check if the given order was placed using one of DNA Payments' gateways.
     *
     * @param \WC_Order $order WooCommerce order instance.
     * @return bool True if the order was paid via any DNA Payments gateway.
     */
    public static function is_dna_payments_order(\WC_Order $order): bool {
        return self::is_dna_payments($order->get_payment_method());
    }

    public static function is_dna_payments(string $payment_method): bool {
        return in_array($payment_method, [
            'dnapayments',
            'dnapayments_google_pay',
            'dnapayments_apple_pay',
            'dnapayments_paypal',
        ]);
    }
}
