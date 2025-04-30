<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RequestHelper {

    const API_URL_PROD = 'https://api.dnapayments.com';
	const API_URL_TEST = 'https://test-api.dnapayments.com';
	const REQUEST_TIMEOUT = 5;

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;
    }

    private function get_url( string $url ): string {
        $base_url = rtrim( $this->gateway->is_test_mode ? self::API_URL_TEST : self::API_URL_PROD, '/' );
        $path = '/' . ltrim($url, '/');
        return $base_url . $path;
    }

    public function get( string $url ) {
		return $this->request( 'GET', $url );
	}

    public function post( string $url, array $data ) {
		return $this->request( 'POST', $url, $data );
	}

    public function request( string $method, string $url, array $data = [] ): array {
        $access_token = $this->gateway->authDataHelper->get_temp_token();

        $args = [
            'method'  => strtoupper( $method ),
            'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
			],
            'timeout' => self::REQUEST_TIMEOUT,
        ];
    
        if ( ! empty( $data ) ) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode( $data );
        }
    
        $response = wp_remote_request( $this->get_url( $url ), $args );
        return $this->handle_response( $response );
    }

    private function handle_response( $response ): array {
        if ( is_wp_error( $response ) ) {
            throw new \Exception( 'Request failed: ' . $response->get_error_message() );
        }
    
        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );
    
        if ( $status_code < 200 || $status_code >= 300 ) {
            throw new \Exception( 'API Error [' . $status_code . ']: ' . $body );
        }
    
        $decoded = json_decode( $body, true );
    
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new \Exception( 'Invalid JSON response: ' . json_last_error_msg() );
        }
    
        return $decoded;
    }
}