export function hasSubscription(paymentData) {
    return paymentData?.periodic?.periodicType === 'ucof'
}

export function getSubscriptionPaymentMethods() {
    return ['BankCard', 'ApplePay', 'GooglePay']
}

/**
 * Returns the verification configuration for subscription
 * change payment method flows.
 */
export function getSubscriptionVerificationConfig() {
    return {
        paymentMethods: [
            { name: 'BankCard' },
            { name: 'ApplePay' },
            { name: 'GooglePay' }
        ],
        paymentMethodsSettings: {
            bankCard: {
                allowVerification: true,
                allowSavedCardVerification: true,
            },
            applepay: {
                allowVerification: true,
            },
            googlepay: {
                allowVerification: true,
            }
        }
    };
}