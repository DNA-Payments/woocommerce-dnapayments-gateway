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

    /**
     * @var string|null Temporary authentication token
     */
    public $temp_token = null;

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

	/**
     * Attempts to get authentication data and handles errors gracefully
     *
     * @param string $invoice_id The invoice ID
     * @param float $amount The payment amount
     * @param string $currency The payment currency
     * @return array Authentication data with access_token or null if failed
     */
    public function get_auth_data_with_try( $invoice_id, $amount, $currency ) {
        try {            
            return $this->get_auth_data( $invoice_id, $amount, $currency );
        } catch (\Exception $e) {
            // Log the error
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Failed to get authentication token: ' . $e->getMessage() );
            }
            
            return array(
                'access_token' => null
            );
        }
    }

    /**
     * Fetches a temporary authentication token
     * Uses current timestamp as invoice ID and zero amount in GBP currency
     * 
     * @return string|null Authentication token or null if request fails
     */
    public function fetch_temp_token() {
        return $this->get_auth_data_with_try( date('d-m-y h:i:s'), 0, 'GBP' )['access_token'];
    }

    /**
     * Gets a temporary authentication token
     * Returns cached token if available, otherwise fetches a new one
     * 
     * @return string|null Authentication token or null if request fails
     */
    public function get_temp_token() {
        if ( $this->temp_token === null ) {
            $this->temp_token = $this->fetch_temp_token();
        }
        
        return $this->temp_token;
    }

}