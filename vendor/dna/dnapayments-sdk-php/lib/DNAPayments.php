<?php

namespace DNAPayments;

use DNAPayments\Util\Scope;
use DNAPayments\Util\HTTPRequester;
use DNAPayments\Util\LZCompressor\LZString;
use DNAPayments\Util\RequestException;

class DNAPayments {
    public function __construct($config = null) {
        self::configure($config);
    }

    private static $fields = [
        'authUrl' => 'https://oauth.dnapayments.com/oauth2/token',
        'testAuthUrl' => 'https://test-oauth.dnapayments.com/oauth2/token',
        'testPaymentUrl' => 'https://test-pay.dnapayments.com',
        'paymentUrl' => 'https://pay.dnapayments.com',
        'testApiUrl' => 'https://test-api.dnapayments.com',
        'apiUrl' => 'https://api.dnapayments.com'
    ];

    private static $config = [
        'isTestMode' => false,
        'scopes' => [],
        'isEnableDonation' => null, // boolean
        'autoRedirectDelayInMs' => null, // int
        'paymentTimeoutInSeconds' => null, // int
        'allowSavingCards' => null, // boolean
        'cards' => null, // array of objects
        'disabledCardSchemes' => null, // array of objects,
        'locale' => null // object
    ];

    public static function configure($config) {
        if(empty($config)) return;

        foreach ($config as $key => $value) {
            if (array_key_exists($key, self::$config)) {
                self::$config[$key] = $value;
            }
        }
    }

    private static function encodeToUrl($data)
    {
        return base64_encode(LZString::compressToEncodedURIComponent(json_encode($data)));
    }

    private static function getPath()
    {
        if (self::$config['isTestMode']) {
            return (object) [
                'authUrl' => self::$fields['testAuthUrl'],
                'paymentUrl' => self::$fields['testPaymentUrl'],
                'apiUrl' => self::$fields['testApiUrl'],
            ];
        }
        return (object) [
            'authUrl' => self::$fields['authUrl'],
            'paymentUrl' => self::$fields['paymentUrl'],
            'apiUrl' => self::$fields['apiUrl']
        ];
    }

    private function getJSONHeader($token) {
        return array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
    }

    public function authApi($data) {
        $authData = [
            'grant_type' => 'client_credentials',
            'scope' => 'webapi',
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret']
        ];

        $optional_fields = [ 'terminal', 'invoiceId', 'amount', 'currency' ];

        foreach ($optional_fields as $key) {
            if ( array_key_exists( $key, $data ) ) {
                $func = $key == 'amount' ? 'floatval' : 'strval';
                $authData[ $key ] = call_user_func( $func, $data[ $key] );
            }
        }

        $response = HTTPRequester::HTTPPost(self::getPath()->authUrl, [], $authData);
        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }

        throw new RequestException($response);
    }

    public function refundRequest($token, $transaction_id, $amount) {
        $refundData = [
            'id' => $transaction_id,
            'amount' => floatval($amount)
        ];
        $response = HTTPRequester::HTTPPost(self::getPath()->apiUrl . '/transaction/operation/refund', $this->getJSONHeader($token), $refundData);

        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response, 'errorCode');
    }


    public function refund($data) {
        $auth = self::authApi($data);
        return self::refundRequest($auth['access_token'], $data['transaction_id'], $data['amount']);
    }

    public function cancelRequest($token, $transaction_id) {
        $response = HTTPRequester::HTTPPost(self::getPath()->apiUrl . '/transaction/operation/cancel', $this->getJSONHeader($token), [
            'id' => $transaction_id
        ]);

        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response, 'errorCode');
    }

    public function cancel($data) {
        $auth = self::authApi($data);
        return self::cancelRequest($auth['access_token'], $data['transaction_id']);
    }

    public function chargeRequest($token, $transaction_id, $amount) {
        $chargeData = [
            'id' => $transaction_id,
            'amount' => floatval($amount)
        ];
        $response = HTTPRequester::HTTPPost(self::getPath()->apiUrl . '/transaction/operation/charge', $this->getJSONHeader($token), $chargeData);

        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response, 'errorCode');
    }

    public function charge($data) {
        $auth = self::authApi($data);
        return self::chargeRequest($auth['access_token'], $data['transaction_id'], $data['amount']);
    }

    /**
     * Process a recurring transaction request
     * @param string $token Access token
     * @param string $parent_transaction_id Parent transaction ID
     * @param string $transaction_type Transaction type (e.g., 'AUTH')
     * @param float $amount Transaction amount
     * @param string $invoice_id Invoice ID
     * @param string $sequence_type Sequence type (default: 'recurring')
     * @param string $periodic_type Periodic type (default: 'ucof')
     * @return array Response data
     * @throws RequestException
     */
    public function recurringRequest($token, $parent_transaction_id, $transaction_type, $amount, $invoice_id, $sequence_type = 'recurring', $periodic_type = 'ucof') {
        $recurringData = [
            'parentTransactionId' => $parent_transaction_id,
            'transactionType' => $transaction_type,
            'amount' => floatval($amount),
            'sequenceType' => $sequence_type,
            'periodicType' => $periodic_type,
            'invoiceId' => strval($invoice_id)
        ];
        $response = HTTPRequester::HTTPPost(self::getPath()->apiUrl . '/transaction/operation/recurring', $this->getJSONHeader($token), $recurringData);

        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response, 'errorCode');
    }

    /**
     * Process a recurring transaction
     * @param array $data Transaction data containing client credentials and transaction details
     * @return array Response data
     */
    public function recurring($data) {
        $auth = self::authApi($data);
        $sequence_type = isset($data['sequenceType']) ? $data['sequenceType'] : 'recurring';
        $periodic_type = isset($data['periodicType']) ? $data['periodicType'] : 'ucof';
        
        return self::recurringRequest(
            $auth['access_token'], 
            $data['parentTransactionId'], 
            $data['transactionType'], 
            $data['amount'], 
            $data['invoiceId'],
            $sequence_type,
            $periodic_type
        );
    }

    public function get_client_token($client_id, $client_secret) {
        return self::authApi([
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ]);
    }

    public function get_transactions_by_field($client_token, $field_value, $field_name = null) {
        $url = self::getPath()->apiUrl . '/v2/transactions/' . $field_value . '/list';

        if ($field_name) {
            $url .= '?field=' . $field_name;
        }

        $response = HTTPRequester::HTTPGet($url, $this->getJSONHeader($client_token));

        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response, 'errorCode');

    }

    public function get_transactions_by_id($client_token, $transaction_id) {
        return self::get_transactions_by_field($client_token, $transaction_id);
    }

    public function get_transactions_by_invoice_id($client_token, $invoice_id) {
        return self::get_transactions_by_field($client_token, $invoice_id, 'invoiceId');
    }

    public static function auth($data) {
        $authData = [
            'grant_type' => 'client_credentials',
            'scope' => Scope::getScopes(self::$config['scopes']),
            'client_id' => $data['client_id'],
            'client_secret' => $data['client_secret'],
            'terminal' => $data['terminal'],
            'invoiceId' => strval($data['invoiceId']),
            'amount' => floatval($data['amount']),
            'currency' => strval($data['currency']),
            'paymentFormURL' => array_key_exists('paymentFormURL', $data) ? $data['paymentFormURL'] : self::getPath()->paymentUrl . '/checkout/' // todo: add
        ];

        $response = HTTPRequester::HTTPPost(self::getPath()->authUrl, [], $authData);
        if ($response != null && $response['status'] >= 200 && $response['status'] < 400) {
            return $response['response'];
        }
        throw new RequestException($response);
    }

    public static function getBaseUrl() {
        return sprintf(
            "%s://%s%s",
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['SERVER_NAME'],
            $_SERVER['REQUEST_URI']
        );
    }

    public static function generateUrl($payment_data, $auth_token)
    {
        $payload = array(
            'auth' => $auth_token
        );

        $params_str = self::encodeToUrl((object) array_merge($payload, $payment_data));

        $filled_configs = [];

        foreach (self::$config as $key => $value) {
            if (!is_null($value)) {
                $filled_configs[$key] = $value;
            }
        }

        return self::getPath()->paymentUrl . '/checkout/?params=' . $params_str . '&data=' . self::encodeToUrl((object) $filled_configs);
    }

    public static function isValidSignature($result, $secret)
    {
        $string = $result['id'] . $result['amount'] . $result['currency'] . $result['invoiceId'] . $result['errorCode'] . json_encode($result['success']);
        return base64_encode(hash_hmac('sha256', $string, $secret, true)) == $result['signature'];
    }
}