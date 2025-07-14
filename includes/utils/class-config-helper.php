<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ConfigHelper {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    /**
     * List of available payment card schemes
     * @var array
     */
    public $available_schemes = ['visa', 'mastercard', 'amex', 'unionpay', 'diners', 'discover', 'maestro'];

    /**
     * Constructor
     * 
     * @param WC_DNA_Payments_Gateway $gateway The gateway instance
     */
    public function __construct( $gateway ) {
        $this->gateway = $gateway;
    }

    /**
     * Get terminal configuration from the API
     * 
     * @return array|null Terminal configuration or null on error
     */
    public function get_terminal_config() {
        try {
            return $this->gateway->requestHelper->get('/payments/form/configuration');
        } catch (\Exception $e) {
            $this->gateway->logger->error('Code: ' . $e->getCode() . '; Message: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get card schemes to display on payment gateway
     * 
     * @return array List of enabled scheme names, limited to max 3
     */
    public function get_payment_gateway_schemes(): array
    {
        $default_schemes = $this->available_schemes;
        $enabled_schemes = $this->get_normalized_enabled_schemes();
        $display_schemes = [];
        $max_icons = 3;

        foreach ($default_schemes as $scheme) {
            if (count($display_schemes) >= $max_icons) {
                break;
            }
            if (in_array($scheme, $enabled_schemes)) {
                $display_schemes[] = $scheme;
            }
        }

        return $display_schemes;
    }

    /**
     * Get card scheme icon URLs
     * 
     * @return array List of icon URLs for enabled schemes
     */
    public function get_payment_gateway_icon_urls(): array
    {
        $schemes = $this->get_payment_gateway_schemes();
        $urls = [];

        foreach ($schemes as $scheme) {
            $urls[] = $this->get_scheme_icon_url($scheme);
        }

        return $urls;
    }

    /**
     * Get payment gateway icons as HTML string
     * 
     * @return string HTML string containing payment gateway icons
     */
    public function get_payment_gateway_icons(): string
    {
        $schemes = $this->get_payment_gateway_schemes();
        $icon_str = '';

        foreach ($schemes as $scheme) {
            $icon_str = $this->get_scheme_icon($scheme) . $icon_str;
        }

        return $icon_str;
    }

     /**
     * Get enabled card schemes from terminal configuration
     *
     * @return array List of enabled card schemes
     */
    public function get_enabled_schemes() {
        $config = $this->gateway->terminal_config ?? [];
        $schemes = [];

        if (
            isset($config['paymentMethodsSettings']['bankCard']['acceptedCardSchemes']) &&
            is_array($config['paymentMethodsSettings']['bankCard']['acceptedCardSchemes'])
        ) {
            $schemes = array_map(
                fn($scheme) => $scheme['cardSchemeName'] ?? null,
                $config['paymentMethodsSettings']['bankCard']['acceptedCardSchemes']
            );

            // Filter out nulls in case some entries don't have cardSchemeName
            $schemes = array_filter($schemes, fn($name) => $name !== null);
        }

        return $schemes;
    }

    /**
     * Get normalized enabled card schemes
     *
     * @return array List of normalized enabled card schemes
     */
    public function get_normalized_enabled_schemes(): array {
        $schemes = $this->get_enabled_schemes();
        $normalized = [];

        foreach ($schemes as $scheme) {
            $converted = $this->normalize_card_scheme_name($scheme);
            if ($converted !== null) {
                $normalized[] = $converted;
            }
        }

        return array_unique($normalized);
    }

    /**
     * Get the URL for a payment scheme icon
     *
     * @param string $scheme The payment scheme identifier (e.g. 'visa', 'mastercard')
     * @return string The complete URL to the scheme's SVG icon
     */
    public function get_scheme_icon_url($scheme) {
        return \WC_DNA_Payments::plugin_url() . '/assets/img/schemes/' . $scheme . '.svg';
    }

    /**
     * Generate HTML markup for a payment scheme icon
     *
     * @param string $scheme The payment scheme identifier (e.g. 'visa', 'mastercard')
     * @return string HTML img tag with the scheme icon
     */
    public function get_scheme_icon($scheme) {
        return sprintf(
            '<img src="%s" class="wc-%s-scheme-icon" alt="%s" />',
            esc_url($this->get_scheme_icon_url($scheme)),
            \WC_DNA_Payments::$id,
            esc_attr($scheme)
        );
    }

    /**
     * Normalize card scheme name
     *
     * @param string $strCardScheme Card scheme to normalize
     * @return ?string Normalized card scheme or null if not recognized
     */
    public function normalize_card_scheme_name(string $strCardScheme): ?string 
    {
        $normalized = strtolower(trim($strCardScheme));

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
                return 'maestro';

            case 'discover':
            case 'discovercard':
            case 'discover card':
            case 'discover-card':
                return 'discover';

            default:
                return $normalized;
        }
    }
}