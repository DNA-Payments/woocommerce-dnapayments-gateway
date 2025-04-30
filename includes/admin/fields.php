<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function get_dnapayments_admin_fields() {
    return [
        "enabled" => [
            "title" => __("Enable/Disable", \WC_DNA_Payments::$text_domain),
            "label" => __("Enabled DNA Payments Gateway", \WC_DNA_Payments::$text_domain),
            "type" => "checkbox",
            "description" => "",
            "default" => "no",
        ],
        "title" => [
            "title" => __("Title", \WC_DNA_Payments::$text_domain),
            "type" => "text",
            "description" => __(
                "This controls the title which the user sees during checkout.",
                \WC_DNA_Payments::$text_domain
            ),
            "default" => "Card Payment",
            "desc_tip" => true,
        ],
        "description" => [
            "title" => __("Description", \WC_DNA_Payments::$text_domain),
            "type" => "text",
            "desc_tip" => true,
            "description" => __(
                "This controls the description which the user sees during checkout.",
                \WC_DNA_Payments::$text_domain
            ),
            "default" => "Card payment method",
        ],
        "client_id" => [
            "title" => __("LIVE Client ID", \WC_DNA_Payments::$text_domain),
            "type" => "text",
        ],
        "client_secret" => [
            "title" => __("LIVE Secret ", \WC_DNA_Payments::$text_domain),
            "type" => "password",
        ],
        "terminal" => [
            "title" => __("LIVE Terminal ID", \WC_DNA_Payments::$text_domain),
            "type" => "text",
        ],
        "transactionType" => [
            "title" => __("Transaction type", \WC_DNA_Payments::$text_domain),
            "type" => "select",
            "class" => "wc-enhanced-select",
            "description" => __(
                "Choose whether you wish to capture funds immediately or authorize payment only.",
                \WC_DNA_Payments::$text_domain
            ),
            "default" => "SALE",
            "desc_tip" => true,
            "options" => [
                "SALE" => __("Sale", \WC_DNA_Payments::$text_domain),
                "AUTH" => __("Authorisation", \WC_DNA_Payments::$text_domain),
            ],
        ],
        "integration_type" => [
            "title" => __("Payment form integration type", \WC_DNA_Payments::$text_domain),
            "type" => "select",
            "class" => "wc-enhanced-select",
            "default" => "hosted",
            "desc_tip" => true,
            "options" => [
                "hosted" => __("Full Redirect", \WC_DNA_Payments::$text_domain),
                "embedded" => __("iFrame LightBox", \WC_DNA_Payments::$text_domain),
                "seamless" => __("Hosted Fields", \WC_DNA_Payments::$text_domain),
            ],
        ],
        "enabled_saved_cards" => [
            "title" => __("Enable saved cards", \WC_DNA_Payments::$text_domain),
            "label" => __("Enable payment via saved cards", \WC_DNA_Payments::$text_domain),
            "type" => "checkbox",
            "description" => "",
            "default" => "no",
        ],
        "enable_order_complete" => [
            "title" => __(
                "Order status upon successful payment",
                \WC_DNA_Payments::$text_domain
            ),
            "label" => __('Set to "Completed"', \WC_DNA_Payments::$text_domain),
            "type" => "checkbox",
            "description" => 'Mark this checkbox to automatically set order status to "Completed" upon successful payment. Valid only for "Sale" transactions.',
            "default" => "no",
        ],
        "failed_attempts_limit" => [
            "title" => __(
                "Failed payment attempts limit (Hosted Fields only)",
                \WC_DNA_Payments::$text_domain
            ),
            "type" => "number",
            "description" => 'Specify the number of unsuccessful payment attempts allowed before the system automatically clears the shopping basket and marks the order as failed. A value of "0" or an empty field indicates no limit.',
        ],
        "is_test_mode" => [
            "title" => __("Test mode", \WC_DNA_Payments::$text_domain),
            "label" => "Enable Test Mode",
            "type" => "checkbox",
            "description" => __(
                "Place the payment gateway in test mode using test API keys.", \WC_DNA_Payments::$text_domain),
            "default" => "no",
            "desc_tip" => true,
        ],
        "test_client_id" => [
            "title" => __("Test Client ID", \WC_DNA_Payments::$text_domain),
            "type" => "text",
        ],
        "test_client_secret" => [
            "title" => __("Test Client secret", \WC_DNA_Payments::$text_domain),
            "type" => "password",
        ],
        "test_terminal" => [
            "title" => __("Test Terminal ID", \WC_DNA_Payments::$text_domain),
            "type" => "text",
        ],
        "backLink" => [
            "title" => __("Back link", \WC_DNA_Payments::$text_domain),
            "type" => "textarea",
            "description" => __("URL for success page.", \WC_DNA_Payments::$text_domain), //: TODO: change
            "desc_tip" => true,
        ],
        "failureBackLink" => [
            "title" => __("Failure back link", \WC_DNA_Payments::$text_domain),
            "type" => "textarea",
            "description" => __("URL for failure page.", \WC_DNA_Payments::$text_domain), //: TODO: change
            "desc_tip" => true,
        ],
        "gatewayOrderDescription" => [
            "title" => __("Gateway order description", \WC_DNA_Payments::$text_domain),
            "type" => "textarea",
            "default" => "Pay with your credit card via our payment gateway",
        ],
    ];
}
