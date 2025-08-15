<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Helper {

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
}