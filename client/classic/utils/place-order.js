/* global wc_dna_params */

import { debounce } from '../../common/debounce'
import errors from '../../common/errors'
import { payHostedFields } from '../../common/pay-hosted-fields'
import { shouldHideOrderLines } from '../../common/validater'
import { getGlobalVariables } from './data'

export const createPlaceOrder = ({ setFormLoading, cardError, fetchPaymentData, onComplete }) =>
    debounce(async (hostedFieldsInstance) => {
        const { isTestMode, integrationType, cards, allowSavingCards, terminalConfig } = getGlobalVariables()

        setFormLoading(true)

        if (integrationType === 'seamless') {
            const { isValid } = await hostedFieldsInstance.validate()
            if (!isValid) {
                cardError.show(errors.CARD_DETAILS_INVALID.message)
                return setFormLoading(false)
            }
        }

        const { paymentData, auth } = (await fetchPaymentData()) || {}

        if (!paymentData || !auth) {
            return setFormLoading(false)
        }

        if (shouldHideOrderLines(terminalConfig) && paymentData?.orderLines) {
            delete paymentData.orderLines
        }

        window.DNAPayments.configure({ isTestMode, cards, allowSavingCards })

        switch (integrationType) {
            case 'seamless':
                const result = await payHostedFields(hostedFieldsInstance, paymentData, auth)

                if (result.error) {
                    cardError.show(result.error)
                }
                onComplete && onComplete(result)
                break
            case 'embedded':
                window.DNAPayments.openPaymentIframeWidget({ ...paymentData, auth })
                break
            default:
                window.DNAPayments.openPaymentPage({ ...paymentData, auth })
        }

        setFormLoading(false)
    })
