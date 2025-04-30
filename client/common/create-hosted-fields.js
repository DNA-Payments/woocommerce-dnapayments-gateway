import { DnaPaymentsError } from './models/DnaPaymentsError'
import errors from './errors'
import { logError } from './log'

export async function createHostedFields({
    isTestMode,
    accessToken,
    threeDSModal,
    domElements: { number, name, expDate, cvv, cvvToken },
    sendCallbackEveryFailedAttempt = 0,
    showPlaceholderOnlyOnFocus = false,
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
        isTestMode,
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

    try {
        const hostedFieldsInstance = await window.dnaPayments.hostedFields.create(options)

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
