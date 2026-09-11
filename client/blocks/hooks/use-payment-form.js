/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n'
import { useEffect, useCallback } from '@wordpress/element'

/**
 * Internal dependencies
 */
import { useCheckoutValidation } from './use-checkout-validation'
import { tryParse } from '../../common/try-parse'
import { payHostedFields } from '../../common/pay-hosted-fields'
import errors from '../../common/errors'
import { completePayment } from '../../common/complete-payment'
import { shouldHideOrderLines } from '../../common/validater'
import { addGatewayId, setNonces } from '../../common/utils'

import { TEXT_DOMAIN } from '../../common/constants'
import { getSubscriptionPaymentMethods, hasSubscription } from '../../common/subscription'
import { dnaPaymentsSettingsData } from '../utils/get-settings'
import {
    getFieldDecline,
    getValidationEvents,
    publishOrderId,
    setLastDeclineReason,
    takeLastDeclineReason,
    traceGate,
} from '../../common/validators'

export const usePaymentForm = ({ props, hostedFieldsInstance, gatewayId }) => {
    const {
        activePaymentMethod,
        setExpressPaymentError,
        emitResponse: { responseTypes, noticeContexts },
        eventRegistration: { onCheckoutSuccess, onPaymentSetup, onCheckoutValidation },
        shouldSavePayment,
    } = props
    const {
        isTestMode,
        integrationType,
        allowSavingCards,
        cards,
        terminalConfig,
        autoRedirectDelayInMs,
        paymentMethodsSettings,
    } = dnaPaymentsSettingsData

    const onMessages = useCallback(
        (messages) => {
            if (activePaymentMethod !== gatewayId) {
                return
            }

            if (messages.length) {
                setExpressPaymentError(messages)
            }
        },
        [activePaymentMethod, gatewayId, setExpressPaymentError],
    )
    useCheckoutValidation({ onCheckoutValidation, onMessages })

    useEffect(() => {
        const handler = async () => {
            if (activePaymentMethod !== gatewayId) {
                return true
            }

            if (integrationType === 'seamless') {
                const { isValid } = await hostedFieldsInstance.validate()
                if (!isValid) {
                    // Prefer the reason the card was refused. validate() only says a field
                    // is invalid, so the generic message would blame the card details for a
                    // funding restriction the customer cannot fix by re-typing.
                    const decline = getFieldDecline()

                    return {
                        type: responseTypes.ERROR,
                        message: decline ? decline.message : errors.CARD_DETAILS_INVALID.message,
                        messageContext: noticeContexts.PAYMENTS,
                    }
                }
            }
            return true
        }

        return onPaymentSetup(handler)
    }, [onPaymentSetup, hostedFieldsInstance, responseTypes, noticeContexts, activePaymentMethod, gatewayId, integrationType])

    useEffect(() => {
        const handler = ({ processingResponse: { paymentDetails } }) =>
            new Promise((resolve) => {
                if (activePaymentMethod !== gatewayId) {
                    resolve({
                        type: responseTypes.SUCCESS,
                        messageContext: noticeContexts.PAYMENTS,
                    })
                    return
                }

                const paymentData = tryParse(paymentDetails.paymentData)
                const auth = tryParse(paymentDetails.auth)
                const nonces = tryParse(paymentDetails.nonces)

                setNonces(nonces)

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
                publishOrderId(paymentData)
                const dnaPaymentsConfig = { isTestMode, cards, allowSavingCards }

                if (autoRedirectDelayInMs) {
                    dnaPaymentsConfig.autoRedirectDelayInMs = autoRedirectDelayInMs
                }

                // Prefer rules recomputed with this payload; fall back to the page-load value.
                const acceptanceRules = tryParse(paymentDetails.paymentMethodsSettings) || paymentMethodsSettings

                if (acceptanceRules && Object.keys(acceptanceRules).length) {
                    dnaPaymentsConfig.paymentMethodsSettings = acceptanceRules
                    traceGate('DNA Checkout (blocks) -> configure({ paymentMethodsSettings }) [' + integrationType + ']: acceptance rules applied', acceptanceRules)
                } else {
                    traceGate('DNA Checkout (blocks) -> configure() [' + integrationType + ']: no acceptance rules, terminal configuration applies')
                }

                const validationEvents = getValidationEvents('events')

                // The embedded widget shows no failure screen for wallet declines; it calls
                // `declined` instead, with no reason, so keep ours to display there.
                const wrappedValidationEvents = validationEvents && {
                    onCardNumberValidate: validationEvents.onCardNumberValidate,
                    onValidate: async (context) => {
                        const decision = await validationEvents.onValidate(context)
                        setLastDeclineReason(decision === true ? null : decision?.reason)
                        return decision
                    },
                }

                if (wrappedValidationEvents) {
                    dnaPaymentsConfig.events = { ...wrappedValidationEvents }
                }

                if (hasSubscription(paymentData)) {
                    dnaPaymentsConfig.paymentMethods = getSubscriptionPaymentMethods()
                }

                switch (integrationType) {
                    case 'seamless': {
                        window.DNAPayments.configure(dnaPaymentsConfig)

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
                            // Fail fast rather than handing an error result to
                            // completePayment(). It is a no-op for one today only because
                            // payHostedFields never returns `data` alongside `error`.
                            if (result.error) {
                                resolve({ ...failedResponse, message: result.error })

                                // CLOSE_TRANSACTION still navigates to the failure page.
                                if (result.redirect) {
                                    window.location.href = result.redirect
                                }

                                return
                            }

                            if (result.data && !result.data.paymentMethod) {
                                result.data.paymentMethod = 'card'
                            }

                            completePayment({
                                paymentResult: result.data,
                                redirect: result.redirect,
                                page: 'checkout',
                            }).finally(() => {
                                resolve(successResponse)

                                if (result.redirect) {
                                    window.location.href = result.redirect
                                }
                            })
                        })
                        break
                    }
                    case 'embedded': {
                        window.DNAPayments.configure({
                            ...dnaPaymentsConfig,
                            events: {
                                ...wrappedValidationEvents,
                                cancelled: () =>
                                    resolve({
                                        ...failedResponse,
                                        message: __(errors.CARD_PAYMENT_CANCEL.message, TEXT_DOMAIN),
                                    }),
                                paid: () => resolve(successResponse),
                                declined: () => {
                                    const reason = takeLastDeclineReason()
                                    traceGate('DNA Checkout (blocks) -> events.declined() fired', reason || '(no reason from our rule)')

                                    return resolve({
                                        ...failedResponse,
                                        message: reason || __(errors.CARD_PAYMENT_FAIL.message, TEXT_DOMAIN),
                                    })
                                },
                            },
                        })

                        window.DNAPayments.openPaymentIframeWidget({ ...paymentData, auth })
                        break
                    }
                    default: {
                        window.DNAPayments.configure(dnaPaymentsConfig)
                        window.DNAPayments.openPaymentPage({ ...paymentData, auth })
                    }
                }
            })

        return onCheckoutSuccess(handler)
    }, [onCheckoutSuccess, hostedFieldsInstance, responseTypes, noticeContexts, shouldSavePayment, activePaymentMethod, gatewayId])
}
