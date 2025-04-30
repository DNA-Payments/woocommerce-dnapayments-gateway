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

import { TEXT_DOMAIN } from '../constants'
import { dnaPaymentsSettingsData } from '../utils/get-settings'
import { completePayment } from '../../common/complete-payment'
import { shouldHideOrderLines } from '../../common/validater'

export const usePaymentForm = ({ props, hostedFieldsInstance }) => {
    const {
        emitResponse: { responseTypes, noticeContexts },
        eventRegistration: { onCheckoutSuccess, onPaymentSetup },
        shouldSavePayment,
    } = props
    const { isTestMode, integrationType, allowSavingCards, cards: _cards, terminalConfig } = dnaPaymentsSettingsData
    const cards = allowSavingCards ? _cards : []

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
                            completePayment({
                                paymentResult: result.data,
                                redirect: result.redirect,
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
                            isTestMode,
                            cards,
                            allowSavingCards,
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
                        window.DNAPayments.configure({ isTestMode, cards, allowSavingCards })
                        window.DNAPayments.openPaymentPage({ ...paymentData, auth })
                    }
                }
            })

        return onCheckoutSuccess(handler)
    }, [onCheckoutSuccess, hostedFieldsInstance, responseTypes, noticeContexts])
}
