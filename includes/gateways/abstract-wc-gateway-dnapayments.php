<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Abstract class for DNA Payments gateways
 * 
 * This abstract class provides a common interface for all DNA Payments gateways
 * and is inherited by both WC_Gateway_DNA_Base_Payment_Component and WC_DNA_Payments_Gateway
 */
abstract class WC_Gateway_Abstract_Dnapayments extends WC_Payment_Gateway {
    
    /**
     * Returns the settings data exposed to the frontend JavaScript.
     *
     * This method should be overridden by child classes to provide
     * configuration data consumed by frontend scripts.
     *
     * @return array Associative array of settings for the frontend.
     */
    public function get_settings_for_frontend() {
        return array();
    }
}