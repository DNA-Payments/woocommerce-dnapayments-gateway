import { logError } from './log'
import errors from './errors'
import { GATEWAY_ID_APPLE_PAY, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_PAYPAL, GATEWAY_ID_ALIPAY, GATEWAY_ID_WECHAT_PAY, GATEWAY_ID_ALIPAY_PLUS } from './constants'

export function getPaymentComponentErrorMessage(err, initErrorMessage) {
    logError(err)
    let message = ''

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
