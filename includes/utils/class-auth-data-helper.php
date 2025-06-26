<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AuthDataHelper {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;
    }

    public function get_auth_data( $invoice_id, $amount, $currency ) {
        \DNAPayments\DNAPayments::configure($this->gateway->get_config());

        return \DNAPayments\DNAPayments::auth(array(
            'client_id' => $this->gateway->client_id,
            'client_secret' => $this->gateway->client_secret,
            'terminal' => $this->gateway->terminal,
            'invoiceId' => $invoice_id,
            'amount' => $amount,
            'currency' => $currency
        ));
    }

    public function get_auth_data_from_order( \WC_Order $order, $total_amount = null ) {
        $total_amount = floatval( empty($total_amount) ? $order->get_total() : $total_amount );

        return $this->get_auth_data(
            strval( $order->get_order_number() ),
            $total_amount,
            $order->get_currency()
        );
    }

	public function get_auth_data_with_try( $invoice_id, $amount, $currency ) {
        try {            
            return $this->get_auth_data( $invoice_id, $amount, $currency );
        } catch (\Error $e) {
            return array(
                'access_token' => null
            );
        }
    }

    public function get_temp_token() {
        return $this->get_auth_data_with_try( date('d-m-y h:i:s'), 0, 'GBP' )['access_token'];
    }

}