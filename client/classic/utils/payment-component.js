import {
    getPaymentComponentObject,
    getPaymentComponentErrorMessages,
    getPaymentComponentErrorMessage,
    getWalletFundingConfig,
} from '../../common/payment-component-helper'
import { getValidationEvents, traceGate } from '../../common/validators'

export function initPaymentComponent(paymentMethodId, options, ctx) {
    const { paymentData } = ctx
    const paymentMethodObject = getPaymentComponentObject(paymentMethodId)
    const { initErrorMessage, validationErrorMessage } = getPaymentComponentErrorMessages(paymentMethodId)

    return new Promise((resolve, reject) => {
        paymentMethodObject.isLoaded = false

        if (!paymentData) {
            return reject(validationErrorMessage)
        }

        traceGate('Wallet -> ' + paymentMethodId + '.init({ events })')

        const events = {
            ...getValidationEvents('wallet'),
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

        const initOptions = {
            ...options,
            events,
            paymentData,
        }

        // Card acceptance rules narrow the wallet sheet - Google Pay drops credit cards from
        // the card list, Apple Pay greys them out - and are the declarative half of the
        // funding gate; `onValidate` above is the enforcing half and runs on its own. The
        // keys go in flat: that is the merchant-side contract the components read.
        const fundingConfig = getWalletFundingConfig(options.paymentMethodsSettings, paymentMethodId)

        delete initOptions.paymentMethodsSettings

        if (fundingConfig) {
            Object.assign(initOptions, fundingConfig)
            traceGate('Wallet -> ' + paymentMethodId + '.init(): card acceptance rules applied', fundingConfig)
        } else {
            traceGate('Wallet -> ' + paymentMethodId + '.init(): no acceptance rules sent, terminal configuration applies')
        }

        paymentMethodObject.init(initOptions)
    })
}
