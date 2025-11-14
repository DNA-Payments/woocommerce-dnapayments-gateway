## Running Tests

To run all tests, use the following command:

```bash
./vendor/bin/phpunit ./tests
```

### Running Specific Test Methods

To run only a specific test method, use the `--filter` option:

```bash
# Run a specific test method
./vendor/bin/phpunit --filter test_auth_data ./tests/DNAPaymentsTest.php

# Run multiple specific methods using regex pattern
./vendor/bin/phpunit --filter "test_auth_data|test_generate_url" ./tests/DNAPaymentsTest.php

# Run all methods containing a specific word
./vendor/bin/phpunit --filter "transaction" ./tests/DNAPaymentsTest.php

# Run a specific test class and method
./vendor/bin/phpunit --filter "DNAPaymentsTest::test_auth_data" ./tests/
```

Available test methods:
- `test_auth_data` - Test authentication data
- `test_generate_url` - Test URL generation
- `test_get_transactions_by_id` - Test getting transactions by ID
- `test_get_transactions_by_invoice_id` - Test getting transactions by invoice ID
- `test_refund` - Test refund functionality
- `test_recurring` - Test recurring payments

### Configuration data

```php
[
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
```

### Payment data

```php
[
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
```