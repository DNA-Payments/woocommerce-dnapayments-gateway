import { DnaPaymentsError } from './models/DnaPaymentsError'
import errors from './errors'
import { logError } from './log'
import { getValidationEvents, setFieldDecline, traceGate } from './validators'

export async function createHostedFields({
    isTestMode,
    accessToken,
    terminalId,
    threeDSModal,
    domElements: { number, name, expDate, cvv, cvvToken },
    sendCallbackEveryFailedAttempt = 0,
    showPlaceholderOnlyOnFocus = false,
    paymentMethodsSettings = null,
    onFieldError = null,
}) {
    const fields = {
        cardholderName: {
            container: name,
            placeholder: 'ABC',
        },
        cardNumber: {
            container: number,
            placeholder: '1234 1234 1234 1234',
        },
        expirationDate: {
            container: expDate,
            placeholder: 'MM / YY',
        },
        cvv: {
            container: cvv,
            placeholder: 'CVC',
        },
        tokenizedCardCvv: {
            container: cvvToken,
            placeholder: 'CVC',
        },
    }

    let styles = {
        input: {
            'font-size': '16px',
            'font-family': 'Open Sans',
        },
    }

    if (showPlaceholderOnlyOnFocus) {
        styles = {
            ...styles,
            '::placeholder': {
                opacity: '0',
            },
            'input:focus::placeholder': {
                opacity: '0.5',
            },
        }
    } else {
        styles = {
            ...styles,
            '::placeholder': {
                opacity: '0.5',
            },
        }
    }

    const options = {
        // Both spellings on purpose: the CDN build reads `isTestMode`, the newer
        // pay.dnapayments.com build wants `env` and warns that `isTestMode` is deprecated.
        // Sending both keeps either build in the right environment - and getting this wrong
        // would mean running a sandbox checkout against production.
        isTestMode,
        env: isTestMode ? 'sandbox' : 'production',
        terminalId,
        accessToken,
        styles,
        styleConfig: {
            containerClasses: {
                FOCUSED: 'focused',
                INVALID: 'has-error',
            },
        },
        fontNames: ['Open Sans'],
        threeDSecure: {
            container: threeDSModal.body,
        },
        fields,
        sendCallbackEveryFailedAttempt,
    }

    // Card acceptance rules are fixed at create() time and require the CONFIGURATION
    // capability on the terminal. Omit the key entirely when there are no rules so the
    // terminal's own configuration continues to apply.
    if (paymentMethodsSettings && Object.keys(paymentMethodsSettings).length) {
        options.config = { paymentMethodsSettings }
        traceGate('Hosted Fields -> create({ config }): card acceptance rules applied', paymentMethodsSettings)
    } else {
        traceGate('Hosted Fields -> create({ config }): no acceptance rules sent, terminal configuration applies')
    }

    try {
        const hostedFieldsInstance = await window.dnaPayments.hostedFields.create(options)

        // Merchant validation hooks. `cardNumberValidate` gives inline feedback as the
        // customer types; `validate` runs at submit against the card actually being charged
        // and is the enforcement point. Requires the EVENTS capability on the terminal.
        const validationEvents = getValidationEvents('hostedFields')

        if (validationEvents) {
            Object.entries(validationEvents).forEach(([name, handler]) => {
                try {
                    hostedFieldsInstance.on(name, handler)
                } catch (err) {
                    // Never let an unsupported event name break checkout. The payment is
                    // still gated server-side, and a payment that slips through is caught
                    // by post-payment reconciliation.
                    traceGate('Hosted Fields -> on("' + name + '") rejected by the SDK; gate inactive on this surface', err)
                    logError(err)
                }
            })
        }

        // A card refused by the acceptance rules, or by a validate handler, is reported as a
        // field error rather than thrown: DNA marks the card-number field invalid and puts
        // the reason here. Without this listener the shopper sees the field turn red with no
        // explanation - the configured message would be delivered and then dropped.
        if (typeof onFieldError === 'function') {
            hostedFieldsInstance.on('validityChange', function ({ fieldsState }) {
                Object.keys(fields).forEach((fieldKey) => {
                    const field = fieldsState && fieldsState[fieldKey]

                    if (!field) {
                        return
                    }

                    const error = !field.isValid && field.error ? field.error : null

                    if (error && error.code) {
                        traceGate('Hosted Fields -> validityChange(): "' + fieldKey + '" refused', {
                            code: error.code,
                            message: error.message,
                        })
                    }

                    // Remember it so the checkout-level banner can say why, rather than
                    // blaming the card details generically. Cleared as soon as the field
                    // becomes valid again.
                    if ('cardNumber' === fieldKey) {
                        setFieldDecline(error ? { code: error.code, message: error.message } : null)
                    }

                    onFieldError({
                        field: fieldKey,
                        message: error ? error.message : '',
                        code: error ? error.code : '',
                    })
                })
            })
        }

        hostedFieldsInstance.on('blur', function ({ fieldKey, fieldsState }) {
            const fieldContainer = fields[fieldKey]?.container
            const isEmpty = fieldsState[fieldKey]?.isEmpty

            if (fieldContainer) {
                fieldContainer.classList.toggle('empty', isEmpty)
            }
        })

        hostedFieldsInstance.on('clear', function () {
            const containers = [number, name, expDate, cvv, cvvToken]
            containers.forEach((fieldContainer) => {
                if (fieldContainer) {
                    fieldContainer.classList.toggle('empty', true)
                }
            })
        })

        hostedFieldsInstance.on('dna-payments-three-d-secure-show', (data) => {
            if (threeDSModal) {
                threeDSModal.show()
            }
        })

        hostedFieldsInstance.on('dna-payments-three-d-secure-hide', () => {
            if (threeDSModal) {
                threeDSModal.hide()
            }
        })

        return hostedFieldsInstance
    } catch (err) {
        logError(err)
        throw new DnaPaymentsError(errors.HOSTED_FIELDS_INIT_FAIL)
    }
}
