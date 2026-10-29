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

    /**
     * Gets authentication data from DNA Payments API
     *
     * @param string $invoice_id The invoice ID
     * @param float $amount The payment amount
     * @param string $currency The payment currency
     * @param string $source Identifies which part of the code this method is called from
     * @return array Authentication data with access_token
     * @throws \Exception If the authentication request fails
     */
    public function get_auth_data( $invoice_id, $amount, $currency, $source = '' ) {
        $log_context = array( 'invoice_id' => $invoice_id, 'amount' => $amount, 'currency' => $currency, 'source' => $source );

        if ( ! $this->gateway->is_ready() ) {
            throw new \Exception( 'DNA Payments gateway is disabled or missing credentials; skipping auth token request.' );
        }

        try {
            \DNAPayments\DNAPayments::configure($this->gateway->get_config());

            $result = \DNAPayments\DNAPayments::auth(array(
                'client_id' => $this->gateway->client_id,
                'client_secret' => $this->gateway->client_secret,
                'terminal' => $this->gateway->terminal,
                'invoiceId' => $invoice_id,
                'amount' => $amount,
                'currency' => $currency
            ));

            return $result;
        } catch (\Exception $e) {
            // Log the exception before re-throwing it
            if ( isset( $this->gateway->logger ) ) {
                $this->gateway->logger->error( 'Auth request failed: ' . $e->getMessage(), $log_context );
                $this->gateway->logger->error( 'Stack trace: ' . $e->getTraceAsString() );
            }
            
            // Re-throw the exception to be handled by the caller
            throw $e;
        }
    }

    public function get_auth_data_from_order( \WC_Order $order ) {
        return $this->get_auth_data(
            strval( $order->get_order_number() ),
            floatval( $order->get_total() ),
            $order->get_currency(),
            'get_auth_data_from_order'
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
            return $this->get_auth_data( $invoice_id, $amount, $currency, 'get_auth_data_with_try' );
        } catch (\Exception $e) {
            return array(
                'access_token' => null,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Fetches a client-credentials access token.
     *
     * @return array Auth data containing access_token.
     * @throws \Exception If the gateway is not ready or the request fails.
     */
    public function get_client_token() {
        if ( ! $this->gateway->is_ready() ) {
            throw new \Exception( 'DNA Payments gateway is disabled or missing credentials; skipping client token request.' );
        }

        \DNAPayments\DNAPayments::configure( $this->gateway->get_config() );

        return $this->gateway->dnaPayment->get_client_token(
            $this->gateway->client_id,
            $this->gateway->client_secret
        );
    }
}
