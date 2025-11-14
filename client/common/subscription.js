export function hasSubscription(paymentData) {
    return paymentData?.periodic?.periodicType === 'ucof'
}

export function getSubscriptionPaymentMethods() {
    return ['BankCard', 'ApplePay', 'GooglePay']
}
