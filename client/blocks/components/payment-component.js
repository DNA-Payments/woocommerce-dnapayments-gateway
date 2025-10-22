import { useEffect, useRef, useState, useMemo, useCallback } from '@wordpress/element'
import { __ } from '@wordpress/i18n'

import errors from '../../common/errors'
import { logData, logError } from '../../common/log'
import { tryParse } from '../../common/try-parse'
import { validatePaymentData } from '../../common/validater'
import { completePayment } from '../../common/complete-payment'
import { debounce } from '../../common/debounce'
import { addGatewayId } from '../../common/utils'
import { getPaymentComponentErrorMessage } from '../../common/payment-component-helper'

import { triggerPlaceOrderButtonClick, useTogglePlaceOrderButtonDisabled } from '../utils/place-order-button'
import { dnaPaymentsSettingsData } from '../utils/get-settings'
import { getPaymentData } from '../utils/get-payment-data'

import { ErrorMessage } from './error-message'

export const PaymentComponent = ({ containerId, componentInstance, gatewayId, errorMessage, props }) => {
    const {
        activePaymentMethod,
        emitResponse: { responseTypes, noticeContexts },
        components: { LoadingMask },
        eventRegistration: { onCheckoutSuccess, onCheckoutFail, onCheckoutValidation },
    } = props

    const [loadingState, setLoadingState] = useState('idle')
    const [errorMessages, setErrors] = useState([])

    // pay button container
    const containerRef = useRef(null)
    // full payment data
    const paymentDataRef = useRef()
    // preliminary payment data
    const draftPaymentDataRef = useRef()
    // resolve and reject of onBeforeProcessPayment
    const processPromiseRef = useRef()
    // resolve and reject of onCheckoutSuccess
    const checkoutPromiseRef = useRef()

    const { tempToken, isTestMode, terminalId } = dnaPaymentsSettingsData

    const paymentDataJSON = useMemo(() => {
        try {
            return JSON.stringify(getPaymentData(props))
        } catch (err) {
            logError(err)
            return '{}'
        }
    }, [props])

    const rejectCheckoutPromise = useCallback((msg) => {
        if (checkoutPromiseRef.current?.status === 'pending') {
            checkoutPromiseRef.current.resolve({
                type: responseTypes.ERROR,
                message: msg || errors.CARD_PAYMENT_FAIL.message,
                messageContext: noticeContexts.PAYMENTS,
            })
            checkoutPromiseRef.current.status = 'rejected'
        }
    }, [responseTypes, noticeContexts])

    const resolveCheckoutPromise = useCallback((redirect) => {
        if (checkoutPromiseRef.current) {
            checkoutPromiseRef.current.resolve({
                type: responseTypes.SUCCESS,
                messageContext: noticeContexts.PAYMENTS,
                redirectUrl: redirect,
            })
            checkoutPromiseRef.current.status = 'resolved'
        }
    }, [responseTypes, noticeContexts])

    useEffect(() => {
        const handler = (payload) => {
            const messages = validatePaymentData(draftPaymentDataRef.current)
            logData('onCheckoutValidation', payload, messages)
            if (messages.length) {
                processPromiseRef.current?.reject(messages[0])
            }
        }

        return onCheckoutValidation(handler)
    }, [onCheckoutValidation])

    const setupIntegration = useCallback(
        debounce(async () => {
            setLoadingState('loading')

            containerRef.current.innerHTML = ''

            componentInstance.init({
                containerElement: containerRef.current,
                paymentData: draftPaymentDataRef.current,
                events: {
                    onClick: () => {
                        setLoadingState('loading')
                        return { paymentData: draftPaymentDataRef.current }
                    },
                    onBeforeProcessPayment: () =>
                        new Promise((resolve, reject) => {
                            processPromiseRef.current = { resolve, reject }
                            triggerPlaceOrderButtonClick()
                        }),
                    onPaymentSuccess: async (paymentResult) => {
                        logData('onPaymentSuccess', paymentResult)
                        const redirect = paymentDataRef.current?.paymentSettings?.returnUrl
                        await completePayment({
                            paymentResult,
                            redirect,
                            setErrors,
                        })
                        setLoadingState('done')
                        resolveCheckoutPromise(redirect)
                    },
                    onCancel: () => {
                        setLoadingState('done')
                        if (checkoutPromiseRef.current?.status === 'pending') {
                            rejectCheckoutPromise(errors.CARD_PAYMENT_CANCEL.message)
                        }
                    },
                    onError: (err) => {
                        const message = getPaymentComponentErrorMessage(err, errorMessage)
                        setLoadingState('failed')
                        setErrors(Array.isArray(message) ? message : [message])
                        if (checkoutPromiseRef.current?.status === 'pending') {
                            rejectCheckoutPromise(message)
                        }
                    },
                    onLoad: () => {
                        setLoadingState('done')
                    },
                },
                token: tempToken,
                environment: isTestMode ? 'sandbox' : 'production',
                terminalId,
            })
        }),
        [componentInstance, rejectCheckoutPromise],
    )

    useEffect(() => {
        const handler = ({ processingResponse: { paymentDetails } }) =>
            new Promise((resolve, reject) => {
                checkoutPromiseRef.current = { resolve, reject, status: 'pending' }

                const paymentData = tryParse(paymentDetails.paymentData)
                const auth = tryParse(paymentDetails.auth)

                if (paymentData && auth) {
                    paymentData.merchantCustomData = addGatewayId(paymentData.merchantCustomData, gatewayId)
                    paymentDataRef.current = paymentData
                    processPromiseRef.current?.resolve({ paymentData, auth, token: auth.access_token })
                } else {
                    processPromiseRef.current?.reject(errors.CARD_PAYMENT_FAIL.message)
                    rejectCheckoutPromise()
                }
            })

        return onCheckoutSuccess(handler)
    }, [onCheckoutSuccess, rejectCheckoutPromise])

    useEffect(() => {
        const handler = async (params) => {
            logData('onCheckoutFail', params)

            const {
                processingResponse: {
                    message,
                    paymentDetails: { messages },
                },
            } = params

            const errorMessage = message || messages || errors.CARD_PAYMENT_FAIL.message

            if (checkoutPromiseRef.current?.status === 'pending') {
                processPromiseRef.current?.reject(errorMessage)

                return {
                    type: responseTypes.FAIL,
                    message: errorMessage,
                    messageContext: noticeContexts.CHECKOUT,
                }
            }
        }

        return onCheckoutFail(handler)
    }, [onCheckoutFail, responseTypes, noticeContexts])

    useEffect(() => {
        draftPaymentDataRef.current = JSON.parse(paymentDataJSON)
    }, [paymentDataJSON])

    useEffect(() => {
        if (containerRef.current && componentInstance) {
            setupIntegration()
        }
    }, [setupIntegration, props.billing?.cartTotal?.value])

    // if payment gateway selected, disable place order button
    useTogglePlaceOrderButtonDisabled(activePaymentMethod)

    return (
        <>
            <ErrorMessage messages={errorMessages} />
            <LoadingMask isLoading={loadingState === 'loading'} showSpinner={true}>
                <div ref={containerRef} id={containerId}></div>
            </LoadingMask>
        </>
    )
}
