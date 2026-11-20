<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Logger {

    const MAX_VALUE_LENGTH   = 500;
    const MAX_MESSAGE_LENGTH = 500;
    const MAX_TRACE_FRAMES   = 4;


    /**
     * @var \WC_DNA_Payments_Gateway
     */
    public $gateway;

    /**
     * WooCommerce logger, resolved on first use so page loads that never log pay nothing.
     *
     * @var \WC_Logger_Interface|null
     */
    public $logger;

    /**
     * @var string
     */
    public $source;

    public function __construct( $gateway ) {
        $this->gateway = $gateway;
        $this->source  = $this->gateway->id;
    }

    public function error( $message, $context = null ) {
        $this->write( 'error', $message, $context );
    }

    public function warning( $message, $context = null ) {
        $this->write( 'warning', $message, $context );
    }

    public function info( $message, $context = null ) {
        $this->write( 'info', $message, $context );
    }

    /**
     * @param \Throwable $e       Caught exception.
     * @param string     $message What failed, e.g. "Success webhook failed".
     * @param array      $context Extra key/value pairs.
     */
    public function exception( $e, $message, $context = array() ) {
        $context = is_array( $context ) ? $context : array();

        // Declines, bad requests and DNA errors need only their message. Engine errors
        // (TypeError, ...) are bugs, so they also get where they happened.
        if ( $e instanceof \Error ) {
            $context['errorType']  = get_class( $e );
            $context['thrownAt']   = $this->get_throw_site( $e );
            $context['stackTrace'] = $this->get_short_trace( $e );
        }

        $this->error( $message . ': ' . $e->getMessage(), $context );
    }

    private function write( $level, $message, $context ) {
        if ( null === $this->logger ) {
            $this->logger = wc_get_logger();
        }

        $entry = $this->clean_text( (string) $message, self::MAX_MESSAGE_LENGTH );

        if ( is_array( $context ) && $context ) {
            $formatted = $this->format_log_context( $context );
            if ( '' !== $formatted ) {
                $entry .= ' ' . $formatted;
            }
        }

        $this->logger->log( $level, $entry, array( 'source' => $this->source ) );
    }

    /**
     * Format log context as a compact key=value list.
     *
     * @param array $context Key/value pairs.
     * @return string A formatted context suffix (or empty string).
     */
    private function format_log_context( array $context ): string {
        $parts = array();

        foreach ( $context as $key => $value ) {
            if ( null === $value || '' === $value || array() === $value ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $value = $this->format_log_context( $value );
                if ( '' === $value ) {
                    continue;
                }
            } elseif ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            } elseif ( ! is_scalar( $value ) ) {
                continue;
            } else {
                $value = $this->quote( $this->clean_text( (string) $value, self::MAX_VALUE_LENGTH ) );
            }

            $parts[] = $key . '=' . $value;
        }

        return $parts ? '(' . implode( ', ', $parts ) . ')' : '';
    }

    /**
     * Collapse whitespace and cut long text, so one entry is always one line.
     */
    private function clean_text( string $text, int $max_length ): string {
        $text = trim( preg_replace( '/\s+/', ' ', $text ) );

        return strlen( $text ) > $max_length ? substr( $text, 0, $max_length ) . '…' : $text;
    }

    /**
     * Quote values that would otherwise break the key=value list.
     */
    private function quote( string $value ): string {
        return preg_match( '/[\s,()=]/', $value ) ? '"' . str_replace( '"', "'", $value ) . '"' : $value;
    }

    /**
     * First frame in this plugin's own code, falling back to where the exception was raised.
     *
     * @param \Throwable $e
     * @return string
     */
    private function get_throw_site( $e ): string {
        $frames = array_merge( array( array( 'file' => $e->getFile(), 'line' => $e->getLine() ) ), $e->getTrace() );

        foreach ( $frames as $frame ) {
            if ( ! empty( $frame['file'] ) ) {
                $path = $this->relative_path( $frame['file'] );
                if ( 0 === strpos( $path, 'includes/' ) ) {
                    return $path . ':' . ( $frame['line'] ?? 0 );
                }
            }
        }

        return $this->relative_path( $e->getFile() ) . ':' . $e->getLine();
    }

    /**
     * Up to MAX_TRACE_FRAMES "file:line" entries, newest first.
     *
     * @param \Throwable $e
     * @return string
     */
    private function get_short_trace( $e ): string {
        $frames = array();

        foreach ( $e->getTrace() as $frame ) {
            if ( ! empty( $frame['file'] ) ) {
                $frames[] = $this->relative_path( $frame['file'] ) . ':' . ( $frame['line'] ?? 0 );
                if ( count( $frames ) >= self::MAX_TRACE_FRAMES ) {
                    break;
                }
            }
        }

        return implode( ' < ', $frames );
    }

    /**
     * Path relative to this plugin (e.g. includes/utils/x.php), or to wp-content for other code.
     */
    private function relative_path( string $file ): string {
        $file = wp_normalize_path( $file );

        foreach ( array( WC_DNA_PLUGIN_PATH, WP_CONTENT_DIR, ABSPATH ) as $base ) {
            $base = trailingslashit( wp_normalize_path( $base ) );
            if ( 0 === strpos( $file, $base ) ) {
                return substr( $file, strlen( $base ) );
            }
        }

        return basename( $file );
    }
}
