<?php

namespace WCPG_DNA_Payments\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PaymentTokenHelper {

    /**
     * @var WC_DNA_Payments_Gateway
     */
    public $gateway;

    public const ALLOWED_PAYMENT_METHODS = [ 'card', 'googlepay', 'applepay' ];
    public const META_EXTRA_DATA = 'extra_data';
    public const META_PARENT_TRANSACTION_ID = '_dnapayments_parent_transaction_id';

    /**
     * Constructor
     *
     * @param WC_DNA_Payments_Gateway $gateway The gateway instance
     */
    public function __construct( \WC_DNA_Payments_Gateway $gateway ) {
        $this->gateway = $gateway;
    }

    /**
     * Add or update a stored card token for a customer.
     *
     * Validates the incoming payload, checks for an existing token for the
     * given customer and gateway, and either updates its metadata (when last4
     * differs) or prevents duplicate cards. Creates a new token when none exists.
     *
     * @param array  $input      DNA Payments webhook payload
     * @param string $gateway_id WooCommerce gateway ID
     * @param bool   $allowed_recurring Whether to allow recurring payments
     * 
     * @return string Empty string on success; error message otherwise
     */
    public function add_token( array $input, string $gateway_id, bool $allowed_recurring = false ): string {
        try {
            $this->validate($input);
            $token_id = $input['cardTokenId'];

            /** @var \WC_Payment_Token_CC[] $tokens */
            $tokens = array_filter(
                \WC_Payment_Tokens::get_customer_tokens( $input['accountId'], $gateway_id ),
                function ($token) use ($token_id) {
                    return $token->get_token() == $token_id;
                }
            );

            if ( count($tokens) > 0 ) {
                foreach ($tokens as $token) {
                    if (
                        $token->get_last4() != substr( $input['cardPanStarred'], -4 ) || 
                        empty( $token->get_meta( self::META_PARENT_TRANSACTION_ID ) )
                    ) {
                        $this->save_token_data($token, $input, $gateway_id, $allowed_recurring);
                        return '';
                    }
                }

                return 'Card already exists and cannot be updated.';
            }

            $token = new \WC_Payment_Token_CC();
            $this->save_token_data($token, $input, $gateway_id, $allowed_recurring);

            return '';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Retrieve saved payment tokens for a customer and gateway.
     *
     * Returns a simplified array of token information suitable for sending
     * to DNA Payments API.
     *
     * @param int    $user_id    WordPress user ID
     * @param string $gateway_id WooCommerce gateway ID
     * @return array List of token data arrays
     */
    public function get_tokens( int $user_id, string $gateway_id ): array {

        if ( empty( $user_id ) ) {
            return [];
        }

        $tokens = \WC_Payment_Tokens::get_customer_tokens( $user_id, $gateway_id );

        return array_values( array_map( function ($token) {
            $extra_data = $token->get_meta( self::META_EXTRA_DATA );

            return [
                'id' => $token->get_id(),
                'merchantTokenId' => $token->get_token(),
                'cardSchemeName' => $extra_data['cardSchemeName'] ?? $extra_data['cardType'] ?? '',
                'cardSchemeId' => $extra_data['cardSchemeId'] ?? '',
                'cardName' => $extra_data['cardName'] ?? '',
                'panStar' => $extra_data['panStar'] ?? '',
                'expiryDate' => $extra_data['expiryDate'] ?? ''
            ];
        }, $tokens ) );
    }

    /**
     * Persist card token details for a customer.
     *
     * Accepts a card token and card details payload, sets core properties
     * (token, gateway id, brand/type, last4, expiry, user id) and stores
     * additional metadata under 'extra_data' before saving the token.
     *
     * @param \WC_Payment_Token_CC $token     Card payment token instance to persist
     * @param array                $input     Webhook payload from DNA Payments
     * @param string               $gateway_id WooCommerce gateway ID associated with this token
     * @param bool                 $allowed_recurring Whether to allow recurring payments
     * 
     * @return void
     * @throws \Exception When token data fails validation
     */
    private function save_token_data(\WC_Payment_Token_CC $token, array $input, string $gateway_id, bool $allowed_recurring): void {
        $date_arr = explode("/", $input['cardExpiryDate']);
        $last4 = substr( $input['cardPanStarred'], -4 );

        if ( $allowed_recurring && ( $token->get_last4() != $last4 || empty( $token->get_meta( self::META_PARENT_TRANSACTION_ID ) ) ) ) {
            $token->update_meta_data( self::META_PARENT_TRANSACTION_ID, $input['id'] );
        }

        $token->set_token( $input['cardTokenId'] );
        $token->set_gateway_id( $gateway_id );
        $token->set_card_type( Helper::normalize_card_scheme_name( $input['cardSchemeName'] ) );        
        $token->set_last4( $last4 );
        $token->set_expiry_month( $date_arr[0] );
        $token->set_expiry_year( '20' . $date_arr[1] );
        $token->set_user_id( $input['accountId'] );

        $token->update_meta_data( self::META_EXTRA_DATA, [
            'cardSchemeId' => $input['cardSchemeId'],
            'cardSchemeName' => $input['cardSchemeName'],
            'cardName' => $input['cardholderName'],
            'panStar' => $input['cardPanStarred'],
            'expiryDate' => $input['cardExpiryDate'],
            'paymentMethod' => $input['paymentMethod'] ?? 'card',
        ] );

        if ( ! $token->validate() ) {
            throw new \Exception('Invalid card token data.');
        }

        $token->save();
    }

    /**
     * Validate the required fields in the card token payload.
     *
     * Ensures `accountId`, `cardTokenId`, and a supported `paymentMethod`
     * are present before proceeding.
     *
     * @param array $input Payload to validate
     * @return bool True if valid
     * @throws \Exception When validation fails
     */
    private function validate( array $input ): bool {
        $error_message = '';

        if ( empty($input['accountId'])) {
            $error_message .= 'User ID is missing. ';
        }
        
        if ( empty($input['cardTokenId'])) {
            $error_message .= 'Card token is missing. ';
        }

        $method = $input['paymentMethod'] ?? null;
        if ( $method === null || ! in_array( $method, self::ALLOWED_PAYMENT_METHODS, true ) ) {
            $error_message .= 'Payment method must be one of the following: ' . implode( ', ', self::ALLOWED_PAYMENT_METHODS ) . '. ';
        }

        if ( !empty($error_message) ) {
            throw new \Exception('Invalid card data provided: ' . $error_message);
        }

        return true;
    }
}
