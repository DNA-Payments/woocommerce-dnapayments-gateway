import {
    getPaymentComponentObject,
    getPaymentComponentErrorMessages,
    getPaymentComponentErrorMessage,
} from '../../common/payment-component-helper'

export function initPaymentComponent(paymentMethodId, options, ctx) {
    const { paymentData } = ctx
    const paymentMethodObject = getPaymentComponentObject(paymentMethodId)
    const { initErrorMessage, validationErrorMessage } = getPaymentComponentErrorMessages(paymentMethodId)

    return new Promise((resolve, reject) => {
        paymentMethodObject.isLoaded = false

        if (!paymentData) {
            return reject(validationErrorMessage)
        }

        const events = {
            ...options.events,
            onError: (err) => {
                const message = getPaymentComponentErrorMessage(err, initErrorMessage)
                if (!paymentMethodObject.isLoaded) {
                    reject(initErrorMessage)
                } else {
                    options.events.onError(message)
                }
            },
            onLoad: () => {
                paymentMethodObject.isLoaded = true
                resolve()
            },
        }

        paymentMethodObject.init({
            ...options,
            events,
            paymentData,
        })
    })
}
