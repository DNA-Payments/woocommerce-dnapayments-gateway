<?php

use PHPUnit\Framework\TestCase;
use DNAPayments\DNAPayments;

class DNAPaymentsTest extends TestCase
{
    public $currency = 'GBP';
    public $terminal = '___ENTER_TEST_TERMINAL___';
    public $client_id = '___ENTER_TEST_CLIENT_ID___';
    public $client_secret = '___ENTER_TEST_CLIENT_SECRET___';

    /**
     * Generate a unique invoice ID for testing
     * @return string
     */
    private function get_invoice_id() {
        return 'SDK_PHP_' . date('d-m-y h:i:s');
    }

    /**
     * Get common test data for DNA Payments operations
     * @return array
     */
    private function get_test_data() {
        return [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'terminal' => $this->terminal,
            'currency' => $this->currency,
            'amount' => 0.01,
            'invoiceId' => $this->get_invoice_id()
        ];
    }

    public function test_auth_data() {
        try {
            \DNAPayments\DNAPayments::configure($this->get_config());
            $auth = \DNAPayments\DNAPayments::auth($this->get_test_data());

            print_r($auth);
            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    public function test_generate_url() {
        $test_data = $this->get_test_data();

        try {
            \DNAPayments\DNAPayments::configure($this->get_full_config());

            $auth = \DNAPayments\DNAPayments::auth($test_data);

            $url = \DNAPayments\DNAPayments::generateUrl($this->get_payment_data($test_data['invoiceId'], $test_data['amount']), $auth);

            print_r($url);

            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    public function test_get_transactions_by_id() {
        $transaction_id = 'f41e38c0-8d51-48be-aa02-0ff6dd875d48';

        try {
            $dnapayments = new \DNAPayments\DNAPayments($this->get_config());
            $auth = $dnapayments->get_client_token($this->client_id, $this->client_secret);
            $transaction_data = $dnapayments->get_transactions_by_id( $auth['access_token'], $transaction_id );

            print_r($transaction_data);

            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    public function test_get_transactions_by_invoice_id() {
        $invoice_id = '1721893675976';

        try {
            $dnapayments = new \DNAPayments\DNAPayments($this->get_config());
            $auth = $dnapayments->get_client_token($this->client_id, $this->client_secret);
            $transaction_data = $dnapayments->get_transactions_by_invoice_id( $auth['access_token'], $invoice_id );

            print_r($transaction_data);

            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    public function test_refund() {
        try {
            $dnapayments = new \DNAPayments\DNAPayments($this->get_config());
            $test_data = $this->get_test_data();

            $result = $dnapayments->refund(array_merge($test_data, [
                "invoiceId" => "254",
                "amount" => 21.60,
                "transaction_id" => "9a599ca5-5efa-499b-b1d1-be69af20fcec"
            ]));

            print_r($result);

            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    public function test_recurring() {
        try {
            $dnapayments = new \DNAPayments\DNAPayments($this->get_config());
            $test_data = $this->get_test_data();

            $result = $dnapayments->recurring(array_merge($test_data, [
                'parentTransactionId' => '8e8accd8-4505-4420-ab99-0c5e11f31aca',
                'transactionType' => 'AUTH'
            ]));

            print_r($result);

            $this->assertTrue(true);
        } catch (Error $e) {
            echo $e;
            $this->assertTrue(false);
        }
    }

    private function get_config() {
        return [
            'isTestMode' => true,
            'scopes' => [
                'allowHosted' => true,
                'allowEmbedded' => true
            ]
        ];
    }

    private function get_full_config() {
        return [
            'isTestMode' => true,
            'scopes' => [
                'allowHosted' => true,
                'allowEmbedded' => true,
                'allowSeamless' => true
            ],
            'isEnableDonation' => false,
            'autoRedirectDelayInMs' => 20000,
            'paymentTimeoutInSeconds' => 600,
            'allowSavingCards' => true,
            'cards' => [
                [
                    'merchantTokenId' => '3UGTOmzrP+Y8onM5wsQCc2eIjeZDpoBKqP3Mem80Re0fMQ==',
                    'panStar' => '***************1111',
                    'cardSchemeId' => 11,
                    'cardSchemeName' => 'VISA',
                    'cardName' => 'JOHN DOE',
                    'expiryDate' => '05/29',
                    'cscState' => 'required', // optional, hidden
                    'useStoredBillingData' => false
                ]
            ],
            'disabledCardSchemes' => [
                [
                    'cardSchemeId' => 1,
                    'cardSchemeName' => 'Amex'
                ]
            ],
            'locale' => [
                'targetLocale' => 'en_GB'
            ]            
        ];
    }

    private function get_payment_data($invoice_id, $amount) {
        return [
            'invoiceId' => $invoice_id,
            'description' => 'Payment description if needed',
            'amount' => $amount,
            'currency' => $this->currency,
            'language' => 'en-gb',
            'paymentSettings' => [
                'terminalId' => $this->terminal,
                'returnUrl' => 'https://test-pay.dnapayments.com/checkout/success.html',
                'failureReturnUrl' => 'https://test-pay.dnapayments.com/checkout/failure.html',
                'callbackUrl' => 'https://pay.dnapayments.com/checkout',
                'failureCallbackUrl' => 'https://testmerchant/order/1123/fail'
            ],
            'customerDetails' => [
                'email' => 'test@dnapayments.com',
                'accountDetails' => [
                    'accountId' => 'uuid000001',
                ],
                'billingAddress' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'addressLine1' => 'Fulham Rd',
                    'postalCode' => 'SW6 1HS',
                    'city' => 'London',
                    'country' => 'GB'
                ],
                'deliveryDetails' => [
                    'deliveryAddress' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                        'addressLine1' => 'Fulham Rd',
                        'addressLine2' => 'Fulham',
                        'postalCode' => 'SW6 1HS',
                        'city' => 'London',
                        'phone' => '0475662834',
                        'country' => 'GB'
                    ],
                ]
            ],
            'orderLines' => [
                [
                    'name' => 'Running shoe',
                    'quantity' => 1,
                    'unitPrice' => 24,
                    'taxRate' => 20,
                    'totalAmount' => 24,
                    'totalTaxAmount' => 4,
                    'imageUrl' => 'https://www.exampleobjects.com/logo.png',
                    'productUrl' => 'https://.../AD6654412.html'
                ]
            ]
        ];
    }
}

