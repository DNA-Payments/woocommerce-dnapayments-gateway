/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n'
import { useEffect } from '@wordpress/element'

/**
 * Internal dependencies
 */
import { tryParse } from '../../common/try-parse'
import { payHostedFields } from '../../common/pay-hosted-fields'
import errors from '../../common/errors'
import { completePayment } from '../../common/complete-payment'
import { shouldHideOrderLines } from '../../common/validater'
import { addGatewayId, setNonces } from '../../common/utils'

import { TEXT_DOMAIN } from '../../common/constants'
import { getSubscriptionPaymentMethods, hasSubscription } from '../../common/subscription'
import { dnaPaymentsSettingsData } from '../utils/get-settings'
import { getValidationErrors } from '../utils/validator'

export const usePaymentForm = ({ props, hostedFieldsInstance, gatewayId }) => {
    const {
        setExpressPaymentError,
        emitResponse: { responseTypes, noticeContexts },
        eventRegistration: { onCheckoutSuccess, onPaymentSetup, onCheckoutValidation },
        shouldSavePayment,
    } = props
    const { isTestMode, integrationType, allowSavingCards, cards, terminalConfig } = dnaPaymentsSettingsData

    useEffect(() => {
        const handler = () => {
            const errorMessage = getValidationErrors()
            if (errorMessage.length) {
                setExpressPaymentError(errorMessage)
            }
            return !errorMessage.length
        }

        return onCheckoutValidation(handler)
    }, [onCheckoutValidation])

    useEffect(() => {
        const handler = async () => {
            if (integrationType === 'seamless') {
                const { isValid } = await hostedFieldsInstance.validate()
                if (!isValid) {
                    return {
                        type: responseTypes.ERROR,
                        message: errors.CARD_DETAILS_INVALID.message,
                        messageContext: noticeContexts.PAYMENTS,
                    }
                }
            }
            return true
        }

        return onPaymentSetup(handler)
    }, [onPaymentSetup, hostedFieldsInstance, responseTypes, noticeContexts])

    useEffect(() => {
        const handler = ({ processingResponse: { paymentDetails } }) =>
            new Promise((resolve) => {
                const paymentData = tryParse(paymentDetails.paymentData)
                const auth = tryParse(paymentDetails.auth)
                const nonces = tryParse(paymentDetails.nonces)

                setNonces(nonces)

                const config = {
                    isTestMode,
                    cards,
                    allowSavingCards,
                }

                if (hasSubscription(paymentData)) {
                    config.paymentMethods = getSubscriptionPaymentMethods()
                }

                if (shouldHideOrderLines(terminalConfig) && paymentData?.orderLines) {
                    delete paymentData.orderLines
                }

                const successResponse = {
                    type: responseTypes.SUCCESS,
                    messageContext: noticeContexts.PAYMENTS,
                }
                const failedResponse = {
                    type: responseTypes.ERROR,
                    messageContext: noticeContexts.PAYMENTS,
                }

                paymentData.merchantCustomData = addGatewayId(paymentData.merchantCustomData, gatewayId)

                switch (integrationType) {
                    case 'seamless': {
                        window.DNAPayments.configure({ isTestMode, cards, allowSavingCards })

                        payHostedFields(
                            hostedFieldsInstance,
                            {
                                ...paymentData,
                                merchantCustomData: JSON.stringify({
                                    ...(tryParse(paymentData.merchantCustomData) || {}),
                                    storeCardOnFile: shouldSavePayment,
                                }),
                            },
                            auth,
                        ).then((result) => {
                            if (result.data && !result.data.paymentMethod) {
                                result.data.paymentMethod = 'card'
                            }
                            completePayment({
                                paymentResult: result.data,
                                redirect: result.redirect,
                                page: 'checkout',
                            }).finally(() => {
                                resolve(
                                    !result.error
                                        ? successResponse
                                        : {
                                              ...failedResponse,
                                              message: result.error,
                                          },
                                )
                                if (result.redirect) {
                                    window.location.href = result.redirect
                                }
                            })
                        })
                        break
                    }
                    case 'embedded': {
                        window.DNAPayments.configure({
                            ...config,
                            events: {
                                cancelled: () =>
                                    resolve({
                                        ...failedResponse,
                                        message: __(errors.CARD_PAYMENT_CANCEL.message, TEXT_DOMAIN),
                                    }),
                                paid: () => resolve(successResponse),
                                declined: () =>
                                    resolve({
                                        ...failedResponse,
                                        message: __(errors.CARD_PAYMENT_FAIL.message, TEXT_DOMAIN),
                                    }),
                            },
                        })

                        window.DNAPayments.openPaymentIframeWidget({ ...paymentData, auth })
                        break
                    }
                    default: {
                        window.DNAPayments.configure(config)
                        window.DNAPayments.openPaymentPage({ ...paymentData, auth })
                    }
                }
            })

        return onCheckoutSuccess(handler)
    }, [onCheckoutSuccess, hostedFieldsInstance, responseTypes, noticeContexts, shouldSavePayment])
}
