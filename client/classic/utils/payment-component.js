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

        // Watch the WooCommerce payment-methods list so we can detect if the user switches
        // to another gateway (which removes the current container from the DOM).
        // When that happens we stop observing and resolve the promise so the caller
        // can clean up or abort the component initialisation.
        const parentElement = document.querySelector('.wc_payment_methods')
        const observer = new MutationObserver((mutations) => {
            if (!parentElement.contains(options.containerElement)) {
                observer.disconnect()
                resolve()
            }
        })
        observer.observe(parentElement, { childList: true, subtree: true })

        const events = {
            ...options.events,
            onError: (err) => {
                observer.disconnect()
                const message = getPaymentComponentErrorMessage(err, initErrorMessage)
                if (!paymentMethodObject.isLoaded) {
                    reject(initErrorMessage)
                } else {
                    options.events.onError(message)
                }
            },
            onLoad: () => {
                paymentMethodObject.isLoaded = true
                observer.disconnect()
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
