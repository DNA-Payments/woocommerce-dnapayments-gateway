import { debounce } from '../../common/debounce'
import errors from '../../common/errors'
import { payHostedFields } from '../../common/pay-hosted-fields'
import { getSubscriptionPaymentMethods, hasSubscription } from '../../common/subscription'
import { shouldHideOrderLines } from '../../common/validater'
import { getGlobalVariables } from './data'

export const createPlaceOrder = ({
    setFormLoading,
    allowSavingCards,
    cards,
    paymentMethods,
    cardError,
    fetchPaymentData,
    onComplete,
}) =>
    debounce(async (hostedFieldsInstance) => {
        const { isTestMode, integrationType, terminalConfig } = getGlobalVariables()

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

        const events =
            integrationType === 'embedded'
                ? {
                      paid: () => {
                          setFormLoading(true)
                      },
                  }
                : undefined

        const config = {
            isTestMode,
            cards,
            allowSavingCards,
            events,
        }

        const _paymentMethods = hasSubscription(paymentData) ? getSubscriptionPaymentMethods() : paymentMethods
        if (_paymentMethods) {
            config.paymentMethods = _paymentMethods
        }

        window.DNAPayments.configure(config)

        switch (integrationType) {
            case 'seamless': {
                const result = await payHostedFields(hostedFieldsInstance, paymentData, auth)

                if (result.error) {
                    setFormLoading(false)
                    cardError.show(result.error)
                }

                if (onComplete) {
                    onComplete(result)
                } else {
                    setFormLoading(false)
                }
                break
            }
            case 'embedded':
                window.DNAPayments.openPaymentIframeWidget({ ...paymentData, auth })
                setFormLoading(false)
                break
            default:
                window.DNAPayments.openPaymentPage({ ...paymentData, auth })
                setFormLoading(false)
        }
    })
