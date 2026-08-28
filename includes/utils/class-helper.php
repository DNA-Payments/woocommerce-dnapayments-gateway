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
    public const CART_CLEANUP_OPTION_PREFIX = 'dna_payments_cart_cleared_';
    public const CART_CLEANUP_REPLAY_WINDOW = 300;

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

    /**
     * Parse merchant custom data from webhook payloads.
     *
     * Used by success, failure, add-card, and subscription change payment method webhooks.
     */
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
     * Whether an order status counts as "paid" for cart-cleanup purposes.
     *
     * @param string $status Order status slug (without the "wc-" prefix).
     * @return bool
     */
    public static function is_paid_status( $status ): bool {
        return in_array( $status, array( 'on-hold', 'processing', 'completed' ), true );
    }

    public static function get_request_started_at(): float {
        if ( isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ) {
            return (float) $_SERVER['REQUEST_TIME_FLOAT'];
        }

        if ( isset( $_SERVER['REQUEST_TIME'] ) ) {
            return (float) $_SERVER['REQUEST_TIME'];
        }

        return microtime( true );
    }

    public static function get_recent_cart_cleanup_time(): float {
        $cleared_at = self::get_cart_cleanup_option_time();

        if ( ! $cleared_at || microtime( true ) - $cleared_at > self::CART_CLEANUP_REPLAY_WINDOW ) {
            return 0.0;
        }

        return $cleared_at;
    }

    private static function get_cart_cleanup_option_time(): float {
        $option_name = self::get_cart_cleanup_option_name();
        if ( ! $option_name ) {
            return 0.0;
        }

        return self::get_cart_cleanup_option_time_from_db( $option_name );
    }

    private static function get_cart_cleanup_option_time_from_db( string $option_name ): float {
        global $wpdb;

        if ( ! $wpdb ) {
            return 0.0;
        }

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                $option_name
            )
        );
    }

    public static function is_stale_cart_replay_request(): bool {
        $cleared_at = self::get_recent_cart_cleanup_time();

        return $cleared_at && self::get_request_started_at() < $cleared_at;
    }

    public static function empty_cart_if_stale_replay_request(): bool {
        if (
            ! self::is_stale_cart_replay_request()
            || ! function_exists( 'WC' )
            || ! WC()->cart
            || WC()->cart->is_empty()
        ) {
            return false;
        }

        return self::empty_cart_and_persist();
    }

    private static function remember_cart_cleanup_time(): void {
        $option_name = self::get_cart_cleanup_option_name();
        if ( ! $option_name ) {
            return;
        }

        self::set_cart_cleanup_option_time( $option_name, microtime( true ) );
        self::delete_expired_cart_cleanup_options();
    }

    private static function set_cart_cleanup_option_time( string $option_name, float $cleared_at ): void {
        global $wpdb;

        if ( ! $wpdb ) {
            return;
        }

        $wpdb->replace(
            $wpdb->options,
            array(
                'option_name'  => $option_name,
                'option_value' => (string) $cleared_at,
                'autoload'     => 'no',
            ),
            array( '%s', '%s', '%s' )
        );
    }

    private static function delete_expired_cart_cleanup_options(): void {
        global $wpdb;

        if ( ! $wpdb ) {
            return;
        }

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS DECIMAL(20,6)) < %f",
                $wpdb->esc_like( self::CART_CLEANUP_OPTION_PREFIX ) . '%',
                microtime( true ) - self::CART_CLEANUP_REPLAY_WINDOW
            )
        );
    }

    private static function get_cart_cleanup_option_name(): string {
        $customer_id = '';

        if ( function_exists( 'WC' ) && WC()->session ) {
            $customer_id = method_exists( WC()->session, 'get_customer_id' )
                ? (string) WC()->session->get_customer_id()
                : '';
        }

        if ( ! $customer_id && get_current_user_id() ) {
            $customer_id = 'user_' . get_current_user_id();
        }

        if ( ! $customer_id ) {
            return '';
        }

        return self::CART_CLEANUP_OPTION_PREFIX . md5( $customer_id );
    }

    public static function empty_cart_and_persist(): bool {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return false;
        }

        /*
         * empty_cart() already handles most of the teardown for us: it fires
         * woocommerce_cart_emptied -> destroy_cart_session() (which nulls every cart session
         * key) and calls persistent_cart_destroy() (which deletes _woocommerce_persistent_cart_).
         * Cart cookies are refreshed for an empty cart by WC core on shutdown. So we only need to
         * add what WC core does NOT do below.
         */
        WC()->cart->empty_cart();
        self::remember_cart_cleanup_time();

        if ( WC()->session ) {
            /*
             * destroy_cart_session() leaves the session 'cart' key as null. On the next request
             * WC_Cart_Session::get_cart_from_session() treats a null cart as "merge the saved
             * persistent cart back in" (WC core, class-wc-cart-session.php ~line 118), which can
             * resurrect a just-cleared cart. An empty array is unambiguously "empty cart" and
             * skips that merge branch entirely.
             */
            WC()->session->set( 'cart', array() );

            if ( method_exists( WC()->session, 'save_data' ) ) {
                WC()->session->save_data();
            }
        }

        return true;
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
            'dnapayments_alipay',
            'dnapayments_wechat_pay',
            'dnapayments_alipay_plus',
        ], true);
    }
}
