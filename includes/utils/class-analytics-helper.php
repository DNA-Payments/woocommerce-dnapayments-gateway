<?php

namespace WCPG_DNA_Payments\Utils;

class AnalyticsHelper {

	const TELEMETRY_API_URL_PROD = 'https://telemetry-api.dnapayments.com/v1/cms-plugins-versions';
	const TELEMETRY_API_URL_TEST = 'https://test-telemetry-api.dnapayments.com/v1/cms-plugins-versions';
	const REQUEST_TIMEOUT = 10;

	/**
	 * @var WC_DNA_Payments_Gateway
	 */
	public $gateway;

	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	public function get_analytics_data(): array {
		$integration_type = $this->gateway->integration_type;
		$terminal_id      = $this->gateway->terminal;

		return [
			'integrationType'    => $integration_type,
			'terminalId'         => $terminal_id,
			'phpVersion'         => phpversion(),
			'domainName'         => $_SERVER['HTTP_HOST'] ?? parse_url( site_url(), PHP_URL_HOST ),
			'pluginVersion'      => $this->get_plugin_version(),
			'cmsPlatformName'    => 'WordPress',
			'cmsPlatformVersion' => get_bloginfo( 'version' ),
			'woocommerceVersion' => defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown',
		];
	}

	public function get_current_hash(): string {
		return hash( 'sha256', json_encode( $this->get_analytics_data() ) );
	}

	public function has_hash_changed(): bool {
		$hash_file = $this->get_hash_file_path();

		if ( ! file_exists( $hash_file ) ) {
			$this->create_hash_file( $hash_file );

			return true;
		}

		$previous_hash = file_get_contents( $hash_file );

		return $this->get_current_hash() !== $previous_hash;
	}

	public function update_hash() {
		file_put_contents( $this->get_hash_file_path(), $this->get_current_hash() );
	}

	protected function get_hash_file_path(): string {
		return WP_CONTENT_DIR . '/uploads/dna_payment_analytics_hash.txt';
	}

	protected function create_hash_file( $file_path ) {
		$directory = dirname( $file_path );

		if ( ! file_exists( $directory ) ) {
			wp_mkdir_p( $directory );
		}

		file_put_contents( $file_path, $this->get_current_hash() );
	}

	private function get_plugin_version(): string {
		return \WC_DNA_Payments::$version;
	}

	public function send_analytics() {
		try {
			if ( $this->has_hash_changed() ) {
				$access_token   = $this->gateway->authDataHelper->get_temp_token();
				$analytics_data = $this->get_analytics_data();
				$response = $this->post(
					$access_token,
					$analytics_data
				);

				$this->update_hash();
			}
		} catch ( \Exception $e ) {
			if ( isset( $this->gateway->logger ) ) {
				$this->gateway->logger->error( 'DNA Analytics error: ' . $e->getMessage() );
			}
		}
	}

	private function post( $access_token, $data ) {
		$url = $this->gateway->is_test_mode ? self::TELEMETRY_API_URL_TEST : self::TELEMETRY_API_URL_PROD;

		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $data ),
			'timeout' => self::REQUEST_TIMEOUT,
		];

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( 'Request failed: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code !== 200 ) {
			throw new \Exception( 'Telemetry API Error [' . $status_code . ']: ' . $body );
		}

		return json_decode( $body, true );
	}
}