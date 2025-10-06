export const GATEWAY_ID = 'dnapayments'
export const GATEWAY_ID_GOOGLE_PAY = 'dnapayments_google_pay'
export const GATEWAY_ID_APPLE_PAY = 'dnapayments_apple_pay'
export const GATEWAY_ID_PAYPAL = 'dnapayments_paypal'
export const GATEWAY_ID_CREDIT_CARD = 'dnapayments_credit_card'

export const TEXT_DOMAIN = 'woocommerce-gateway-dna'

export const HOSTED_FIELD_IDS = {
    number: `wc-${GATEWAY_ID}-card-number-hosted`,
    name: `wc-${GATEWAY_ID}-card-name-hosted`,
    expDate: `wc-${GATEWAY_ID}-expiry-hosted`,
    cvv: `wc-${GATEWAY_ID}-csc-hosted`,
    cvvToken: `wc-${GATEWAY_ID}-csc-token-hosted`,
    threeDS: 'three-d-secure',
}

export const CONTAINER_IDS = {
    googlepay: 'dnapayments_google_pay_container',
    applepay: 'dnapayments_apple_pay_container',
    paypal: 'dnapayments_paypal_container',
}
