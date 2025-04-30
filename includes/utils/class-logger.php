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

    public function __construct( $gateway ) {
        $this->gateway  = $gateway;
        $this->logger   = wc_get_logger();
        $this->source   = $this->gateway->id;

    }

    public function error( $message ) {
        $this->logger->error( $message, ['source' => $this->source] );
    }

    public function warning( $message ) {
        $this->logger->warning( $message, ['source' => $this->source] );
    }

    public function info( $message ) {
        $this->logger->info( $message, ['source' => $this->source] );
    }
}