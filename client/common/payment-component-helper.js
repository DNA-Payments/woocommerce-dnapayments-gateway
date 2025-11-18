import { logError } from './log'
import errors from './errors'
import { GATEWAY_ID_APPLE_PAY, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_PAYPAL } from './constants'

export function getPaymentComponentErrorMessage(err, initErrorMessage) {
    logError(err)

    let message = errors.CARD_PAYMENT_FAIL.message

    if (err.message) {
        message = err.message
    }

    // TODO: rejected text in additionalInfo
    if (err.additionalInfo?.message) {
        message = err.additionalInfo.message
    }

    if (initErrorMessage && isInitFailed(err)) {
        message = initErrorMessage
    }

    // Replace "Window is closed" error with cancellation message
    if (message && message.includes('Window is closed')) {
        message = errors.CARD_PAYMENT_CANCEL.message
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
        default:
            return null
    }
}
