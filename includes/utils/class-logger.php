<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logger {

	/**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    /**
     * @var WC_Logger
     */
    public $logger;

    /**
     * @var String
     */
    public $source;

    /**
     * @var String
     */
    public $error_source;

    public function __construct( $gateway ) {
        $this->gateway  = $gateway;
        $this->logger   = wc_get_logger();
        $this->source   = $this->gateway->id;
        $this->error_source = $this->source . '_errors';
    }

    public function error( $message, $context = null ) {
        $this->logger->error(
            $this->format_message_with_context( $message, $context ),
            ['source' => $this->error_source]
        );
    }

    public function warning( $message, $context = null ) {
        $this->logger->warning(
            $this->format_message_with_context( $message, $context ),
            ['source' => $this->source]
        );
    }

    public function info( $message, $context = null ) {
        $this->logger->info(
            $this->format_message_with_context( $message, $context ),
            ['source' => $this->source]
        );
    }

    /**
     * Append formatted context to a message when provided.
     *
     * @param string     $message Log message.
     * @param array|null $context Context key/value pairs.
     * @return string
     */
    private function format_message_with_context( $message, $context = null ): string {
        if ( empty( $context ) || ! is_array( $context ) ) {
            return $message;
        }

        $formatted_context = $this->format_log_context( $context );

        return $formatted_context ? $message . $formatted_context : $message;
    }

    /**
     * Format log context as a compact key=value list.
     *
     * @param array $context Key/value pairs.
     * @return string A formatted context suffix (or empty string).
     */
    private function format_log_context( array $context ): string {
        $parts = [];
        foreach ( $context as $key => $value ) {
            if ( $value === null || $value === '' ) {
                continue;
            }
            if ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            }
            $parts[] = $key . '=' . $value;
        }

        return empty( $parts ) ? '' : ' (' . implode( ', ', $parts ) . ')';
    }
}
