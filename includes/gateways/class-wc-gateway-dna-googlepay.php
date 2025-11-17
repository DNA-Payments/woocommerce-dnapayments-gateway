<?php

class WC_Gateway_DNA_GooglePay extends WC_Gateway_DNA_Base_Payment_Component {

    public function __construct() {

        $this->id = 'dnapayments_google_pay';
        $this->icon = WC_DNA_Payments::plugin_url() . '/assets/img/googlepay.svg';                    
        $this->method_title = 'Google Pay';
        $this->method_description = 'Google Pay provided by DNA Payments';

        parent::__construct();
    }
}
