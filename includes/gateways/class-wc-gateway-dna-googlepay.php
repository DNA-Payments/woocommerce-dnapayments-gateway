<?php

class WC_Gateway_DNA_GooglePay extends WC_Gateway_DNA_Base_Payment_Component {

    public function __construct() {

        $this->id = 'dnapayments_google_pay';
        $this->icon = WC_DNA_Payments::plugin_url() . '/assets/img/googlepay.svg';                    
        $this->method_title = 'Google Pay';
        $this->method_description = 'Google Pay provided by DNA Payments';
        $this->supports     = array(
            'products', 
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'multiple_subscriptions',
        );

        parent::__construct();
    }
}
