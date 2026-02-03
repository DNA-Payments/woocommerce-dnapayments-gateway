export const GATEWAY_ID = 'dnapayments'
export const GATEWAY_ID_GOOGLE_PAY = 'dnapayments_google_pay'
export const GATEWAY_ID_APPLE_PAY = 'dnapayments_apple_pay'
export const GATEWAY_ID_PAYPAL = 'dnapayments_paypal'
export const GATEWAY_ID_ALIPAY = 'dnapayments_alipay'
export const GATEWAY_ID_WECHAT_PAY = 'dnapayments_wechat_pay'
export const GATEWAY_ID_ALIPAY_PLUS = 'dnapayments_alipay_plus'

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
    googlepay: GATEWAY_ID_GOOGLE_PAY + '_container',
    applepay: GATEWAY_ID_APPLE_PAY + '_container',
    paypal: GATEWAY_ID_PAYPAL + '_container',
    alipay: GATEWAY_ID_ALIPAY + '_container',
    wechatpay: GATEWAY_ID_WECHAT_PAY + '_container',
    alipayplus: GATEWAY_ID_ALIPAY_PLUS + '_container',
}

export const NONCE_FIELD = `_${GATEWAY_ID}_nonce`
