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

    /**
     * Performs a GET request to the specified URL
     *
     * @param string $url The endpoint URL
     * @param bool $use_token Whether to include the authorization token in the request
     * @return array The response data
     */
    public function get( string $url, bool $use_token = true ) {
		return $this->request( 'GET', $url, [], $use_token );
	}

    /**
     * Performs a POST request to the specified URL with the given data
     *
     * @param string $url The endpoint URL
     * @param array $data The data to send in the request body
     * @param bool $use_token Whether to include the authorization token in the request
     * @return array The response data
     */
    public function post( string $url, array $data, bool $use_token = true ) {
		return $this->request( 'POST', $url, $data, $use_token );
	}

    /**
     * Performs an HTTP request to the specified URL with the given method and data
     *
     * @param string $method The HTTP method (GET, POST, etc.)
     * @param string $url The endpoint URL
     * @param array $data The data to send in the request body
     * @param bool $use_token Whether to include the authorization token in the request
     * @return array The response data
     * @throws \Exception If the token is required but not set, or if the request fails
     */
    public function request( string $method, string $url, array $data = [], bool $use_token = true ): array {
        $args = [
            'method'  => strtoupper( $method ),
            'headers' => [],
            'timeout' => self::REQUEST_TIMEOUT,
        ];
        
        if ( $use_token ) {
            $access_token = $this->gateway->authDataHelper->get_client_token()['access_token'];
            
            if ( null === $access_token ) {
                throw new \Exception( 'Access token is not set' );
            }
            
            $args['headers']['Authorization'] = 'Bearer ' . $access_token;
        }
    
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