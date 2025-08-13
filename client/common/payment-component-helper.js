import { logError } from './log'
import errors from './errors'

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

    return message
}

export const isInitFailed = (err) => [1002, 1003].includes(err.code) // Failed to initialize / validate the Google / Apple Pay button

export const isProcessFailed = (err) => [1005].includes(err.code) // Failed to process the Google Pay / Apple Pay payment
