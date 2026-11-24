<?php

use DNAPayments\DNAPayments;
use PHPUnit\Framework\TestCase;

class SignatureTest extends TestCase
{
    private function signedResult($success = true, $secret = 'test-secret')
    {
        $result = [
            'id' => 'transaction-123',
            'amount' => '12.34',
            'currency' => 'GBP',
            'invoiceId' => 'order-456',
            'errorCode' => 0,
            'success' => $success,
        ];
        $message = 'transaction-12312.34GBPorder-4560' . ($success ? 'true' : 'false');
        $result['signature'] = base64_encode(hash_hmac('sha256', $message, $secret, true));
        return $result;
    }

    public function testValidSuccessAndFailureSignatures()
    {
        $this->assertTrue(DNAPayments::isValidSignature($this->signedResult(), 'test-secret'));
        $this->assertTrue(DNAPayments::isValidSignature($this->signedResult(false), 'test-secret'));
    }

    public function testRejectsInvalidSignatures()
    {
        foreach ([true, false, 1, 0, 1.5, null, [], new stdClass(), '', '0', 'invalid'] as $signature) {
            $result = $this->signedResult();
            $result['signature'] = $signature;
            $this->assertFalse(DNAPayments::isValidSignature($result, 'test-secret'));
        }
        $result = $this->signedResult();
        unset($result['signature']);
        $this->assertFalse(DNAPayments::isValidSignature($result, 'test-secret'));
    }

    public function testRejectsReportedJsonPayload()
    {
        $result = json_decode('{"invoiceId":"order-456","amount":12.34,"currency":"GBP","success":true,"signature":true}', true);
        $this->assertFalse(DNAPayments::isValidSignature($result, 'test-secret'));
    }

    public function testRejectsWrongSecretAndModifiedSignedFields()
    {
        $this->assertFalse(DNAPayments::isValidSignature($this->signedResult(), 'wrong-secret'));
        foreach (['id' => 'other', 'amount' => '0.01', 'currency' => 'EUR', 'invoiceId' => 'other', 'errorCode' => 1, 'success' => false] as $field => $value) {
            $result = $this->signedResult();
            $result[$field] = $value;
            $this->assertFalse(DNAPayments::isValidSignature($result, 'test-secret'));
        }
    }

    public function testRejectsInvalidSecretsAndPayloads()
    {
        foreach (['', null, true, false, 1, [], new stdClass()] as $secret) {
            $this->assertFalse(DNAPayments::isValidSignature($this->signedResult(true, ''), $secret));
        }
        foreach ([null, true, 'invalid', new stdClass(), []] as $result) {
            $this->assertFalse(DNAPayments::isValidSignature($result, 'test-secret'));
        }
    }
}
