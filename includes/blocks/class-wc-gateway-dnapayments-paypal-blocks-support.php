<?php

final class WC_Gateway_DNA_Payments_PayPal_Blocks_Support extends WC_Gateway_Base_DNA_Payments_Blocks_Support {
    public function __construct() {
        $this->name       = 'dnapayments_paypal';
        $this->asset_path = WC_DNA_Payments::plugin_abspath() . '/assets/js/blocks/dnapayments_paypal.asset.php';
        $this->script_url = WC_DNA_Payments::plugin_url() . '/assets/js/blocks/dnapayments_paypal.js';
    }
}