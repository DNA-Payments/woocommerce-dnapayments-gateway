import { logError } from './log'
import errors from './errors'
import { GATEWAY_ID_APPLE_PAY, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_PAYPAL, GATEWAY_ID_ALIPAY, GATEWAY_ID_WECHAT_PAY, GATEWAY_ID_ALIPAY_PLUS } from './constants'

export function getPaymentComponentErrorMessage(err, initErrorMessage) {
    logError(err)
    let message = ''

    // A funding-type decline carries the normalised decision object in additionalInfo,
    // which has a `reason` rather than a `message`, so read it explicitly.
    if (isFundingDeclined(err)) {
        return err.additionalInfo?.reason || err.message || errors.CARD_PAYMENT_FAIL.message
    }

    // TODO: rejected text in additionalInfo
    if (typeof err.additionalInfo === 'string') {
        message = err.additionalInfo
    } else if (err.additionalInfo?.message) {
        message = err.additionalInfo.message
    }

    if (!message?.trim() || ['rejected', 'unknown error'].includes(message.trim().toLocaleLowerCase())) {
        message = err.message || errors.CARD_PAYMENT_FAIL.message
    }

    if (initErrorMessage && isInitFailed(err)) {
        message = initErrorMessage
    }

    return message
}

export const isInitFailed = (err) => [1002, 1003].includes(err.code) // Failed to initialize / validate the Google / Apple Pay button

export const isFundingDeclined = (err) => err?.code === 1011 // FUNDING_DECLINED_BY_MERCHANT

export const isProcessFailed = (err) => [1005].includes(err.code) // Failed to process the Google Pay / Apple Pay payment

export const getPaymentComponentErrorMessages = (paymentMethodId) => {
    switch (paymentMethodId) {
        case GATEWAY_ID_APPLE_PAY:
            return {
                initErrorMessage: errors.APPLE_PAY_INIT_FAIL.message,
                validationErrorMessage: errors.APPLE_PAY_VALIDATION_FAIL.message,
            }
        case GATEWAY_ID_GOOGLE_PAY:
            return {
                initErrorMessage: errors.GOOGLE_PAY_INIT_FAIL.message,
                validationErrorMessage: errors.GOOGLE_PAY_VALIDATION_FAIL.message,
            }
        case GATEWAY_ID_PAYPAL:
            return {
                initErrorMessage: errors.PAYPAL_INIT_FAIL.message,
                validationErrorMessage: errors.PAYPAL_VALIDATION_FAIL.message,
            }
        case GATEWAY_ID_ALIPAY:
            return {
                initErrorMessage: errors.ALIPAY_INIT_FAIL.message,
                validationErrorMessage: errors.ALIPAY_VALIDATION_FAIL.message,
            }
        case GATEWAY_ID_WECHAT_PAY:
            return {
                initErrorMessage: errors.WECHAT_PAY_INIT_FAIL.message,
                validationErrorMessage: errors.WECHAT_PAY_VALIDATION_FAIL.message,
            }
        case GATEWAY_ID_ALIPAY_PLUS:
            return {
                initErrorMessage: errors.ALIPAY_PLUS_INIT_FAIL.message,
                validationErrorMessage: errors.ALIPAY_PLUS_VALIDATION_FAIL.message,
            }
        default:
            return {}
    }
}

export const getPaymentComponentObject = (paymentMethodId) => {
    switch (paymentMethodId) {
        case GATEWAY_ID_APPLE_PAY:
            return window.DNAPayments.ApplePayComponent
        case GATEWAY_ID_GOOGLE_PAY:
            return window.DNAPayments.GooglePayComponent
        case GATEWAY_ID_PAYPAL:
            return window.DNAPayments.PayPalComponent
        case GATEWAY_ID_ALIPAY:
            return window.DNAPayments.AlipayComponent
        case GATEWAY_ID_WECHAT_PAY:
            return window.DNAPayments.WeChatPayComponent
        case GATEWAY_ID_ALIPAY_PLUS:
            return window.DNAPayments.AlipayPlusComponent
        default:
            return null
    }
}

const WALLET_SETTINGS_KEY = {
    [GATEWAY_ID_APPLE_PAY]: 'applepay',
    [GATEWAY_ID_GOOGLE_PAY]: 'googlepay',
}

/**
 * Map the server-supplied acceptance rules onto the shape a wallet component reads.
 *
 * The wallet components take the merchant-side funding rules as FLAT keys on the object
 * passed to init() - the same object they read `environment` from. The nested
 * `paymentMethodsSettings` shape is how they read the *terminal* configuration fetched from
 * DNA, so anything nested we pass from the page is simply ignored. Merchant values win over
 * the terminal ones.
 *
 * This is what narrows the wallet sheet: Google Pay turns the list into
 * `allowCreditCards` / `allowPrepaidCards`, Apple Pay into `merchantCapabilities`.
 *
 * Returns null for PayPal, Alipay and WeChat Pay - they carry no card BIN, so there is
 * nothing to restrict and nothing to send.
 */
export const getWalletFundingConfig = (paymentMethodsSettings, paymentMethodId) => {
    const key = WALLET_SETTINGS_KEY[paymentMethodId]

    if (!key || !paymentMethodsSettings) {
        return null
    }

    const rules = paymentMethodsSettings[key] || paymentMethodsSettings.bankCard

    if (!rules || !Array.isArray(rules.acceptedCardFundingTypes) || !rules.acceptedCardFundingTypes.length) {
        return null
    }

    const config = { acceptedCardFundingTypes: rules.acceptedCardFundingTypes }

    if (rules.acceptedCardFundingTypesErrorMessage) {
        config.acceptedCardFundingTypesErrorMessage = rules.acceptedCardFundingTypesErrorMessage
    }

    return config
}
