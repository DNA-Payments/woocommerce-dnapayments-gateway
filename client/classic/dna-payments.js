'use strict'

import 'whatwg-fetch'
import {
    createCardError,
    createSetLoading,
    getSelectedPaymentGateway,
    wrapMessage,
    scrollToNotices,
    setLoading,
} from './utils/ui'
import { createPlaceOrder } from './utils/place-order'
import { renderHostedFields } from './utils/render-hosted-fields'
import { getGlobalVariables, getRequiredFields } from './utils/data'
import { validate } from './utils/validate'

import { fetchPaymentAndAuthData } from '../common/api/fetch-payment-and-auth-data'
import { getPaymentComponentErrorMessage } from '../common/payment-component-helper'
import { completePayment, getOrderIdFromPaymentData } from '../common/complete-payment'
import { request } from '../common/api/request'
import { debounce } from '../common/debounce'
import errors from '../common/errors'
import { tryParse } from '../common/try-parse'
import { checkApplePayAvailability } from '../common/validater'

/* global wc_dna_params */
const orderId = Number(wc_dna_params.order_id) || 0
const isPayForOrderPage = Boolean(orderId)

let isUpdating = false // is event updated_checkout will be triggered
let hostedFieldsInstance = null
let paymentData = null
let authData = null
let globalError = null

jQuery(function ($) {
    const { isTestMode, gatewayId, isHostedFields, tempToken, terminalConfig } = getGlobalVariables()

    const $form = isPayForOrderPage ? $('form#order_review') : $('form.woocommerce-checkout')
    const cardError = createCardError()
    const setFormLoading = createSetLoading($form)

    const placeOrder = createPlaceOrder({
        cardError,
        setFormLoading,
        fetchPaymentData: async () => {
            try {
                if (isPayForOrderPage) {
                    await fetchPaymentData()
                    const storeCardOnFile = $(`#wc-${gatewayId}-new-payment-method`).is(':checked')
                    return {
                        paymentData: {
                            ...paymentData,
                            merchantCustomData: JSON.stringify({
                                ...(tryParse(paymentData.merchantCustomData) || {}),
                                storeCardOnFile,
                                gatewayId,
                            }),
                        },
                        auth: authData,
                    }
                }

                return await postProcessPayment()
            } catch (err) {
                showError(err.message, true)
            }
        },
        onComplete: (result) =>
            completePayment({
                paymentResult: result.data,
                redirect: result.redirect,
                setLoading: setFormLoading,
                setErrors: showError,
            }),
    })

    const render = debounce(async ({ selectedGateway, shouldFetchPaymentData, shouldUpdate }) => {
        if (!selectedGateway) {
            selectedGateway = getSelectedPaymentGateway()
        }
        const placeOrderBtn = document.getElementById('place_order')

        if (!['dnapayments', 'dnapayments_google_pay', 'dnapayments_apple_pay'].includes(selectedGateway)) {
            $form.find('.dnapayments-footer').hide()
            placeOrderBtn.removeAttribute('disabled')
            return
        }

        if (!(await checkApplePayAvailability(terminalConfig))) {
            $('.wc_payment_method.payment_method_dnapayments_apple_pay').hide()
            if (selectedGateway === 'dnapayments_apple_pay') {
                $('.wc_payment_method.payment_method_dnapayments #payment_method_dnapayments').click()
                return
            }
        } else {
            $('.wc_payment_method.payment_method_dnapayments_apple_pay').show()
        }

        $form.find('.dnapayments-footer').show()

        switch (selectedGateway) {
            case 'dnapayments_google_pay':
            case 'dnapayments_apple_pay':
                const messages = validate($form)
                if (messages.length) {
                    showError(messages, true)
                    paymentData = null
                    authData = null
                } else if (!paymentData || shouldFetchPaymentData) {
                    setFormLoading(true)
                    await fetchPaymentData()
                    setFormLoading(false)
                }
                placeOrderBtn.setAttribute('disabled', 'disabled')
                renderGoogleOrApplePayComponent(selectedGateway, shouldUpdate)
                break
            default:
                placeOrderBtn.removeAttribute('disabled')
        }

        if (isHostedFields) {
            renderHostedFields({
                setFormLoading,
                onSuccess: (instance) => {
                    hostedFieldsInstance = instance
                    cardError.hide()
                },
                onError: (errMsg) => cardError.show(errMsg),
            })
        }
    })

    // Detect WooCommerce AJAX calls
    $(document).ajaxSend((event, xhr, settings) => {
        if (settings.url.includes('wc-ajax=update_order_review')) {
            isUpdating = true
        }
    })

    $(document).ajaxComplete((event, xhr, settings) => {
        if (settings.url.includes('wc-ajax=update_order_review')) {
            isUpdating = false
        }
    })

    $form.on(isPayForOrderPage ? 'submit' : 'checkout_place_order_dnapayments', onSubmit)
    $(document.body).on('updated_checkout', () => render({ shouldFetchPaymentData: true, shouldUpdate: true }))
    $form.on('change', 'input[name="payment_method"]', () => render({ selectedGateway: $(this).val() }))
    $form.on('change', 'input, textarea, select', function (e) {
        const elem = e.target
        if (!elem) return

        const name = elem.getAttribute('name')
        const isShippingIncluded = $form.find('[name="ship_to_different_address"]').is(':checked')

        const required = elem.getAttribute('aria-required')
        const isRequired = (required && required === 'true') || getRequiredFields(isShippingIncluded).includes(name)

        if (!isUpdating && isRequired) {
            render({ shouldFetchPaymentData: name !== 'terms' })
        }
    })

    if (!tempToken) {
        globalError = `Authentication failed. Please check that your credentials are correct. If you are using the Hosted Fields integration, make sure it is enabled for your account by your payment provider.`
        displayError(globalError)
    }

    // On the Pay for Order page, ensure initialization
    if (isPayForOrderPage) {
        render({})
    }

    function renderGoogleOrApplePayComponent(paymentMethodId, shouldUpdate) {
        const paymentMethodObject =
            paymentMethodId === 'dnapayments_apple_pay'
                ? window.DNAPayments.ApplePayComponent
                : window.DNAPayments.GooglePayComponent
        const errorMessage =
            paymentMethodId === 'dnapayments_apple_pay'
                ? errors.APPLE_PAY_INIT_FAIL.message
                : errors.GOOGLE_PAY_INIT_FAIL.message
        const $container = $form.find('#' + paymentMethodId + '_container')

        if (!shouldUpdate && paymentMethodObject.isLoading) {
            return
        }

        // clear container HTML element
        $container.css('height', '46px').html('')

        if (!paymentData) {
            return
        }

        const events = {
            onClick: () => {
                setFormLoading(true)
            },
            onBeforeProcessPayment: async () => {
                if (isPayForOrderPage) {
                    await fetchPaymentData()
                    return {
                        paymentData: {
                            ...paymentData,
                            merchantCustomData: JSON.stringify({
                                ...(tryParse(paymentData.merchantCustomData) || {}),
                                gatewayId: paymentMethodId,
                            }),
                        },
                        auth: authData,
                    }
                }
                return await postProcessPayment()
            },
            onPaymentSuccess: (paymentResult) =>
                completePayment({
                    paymentResult,
                    redirect: paymentData?.paymentSettings?.returnUrl,
                    setLoading: setFormLoading,
                    setErrors: showError,
                }),
            onCancel: (err) => {
                setFormLoading(false)
            },
            onError: (err) => {
                setFormLoading(false)
                setLoading($container, false)

                const message = getPaymentComponentErrorMessage(err, errorMessage)

                if (message !== errorMessage) {
                    showError(message)
                } else {
                    paymentMethodObject.isLoading = false
                    $container.html(wrapMessage(errorMessage))
                    $container.css('height', 'auto')
                }
            },
            onLoad: () => {
                $container.find('div').css('height', '40px')
                setLoading($container, false)
                paymentMethodObject.isLoading = false
            },
        }

        setLoading($container, true)
        paymentMethodObject.init({
            containerElement: $container[0],
            events,
            paymentData,
            token: authData ? authData.access_token : tempToken,
            environment: isTestMode ? 'sandbox' : 'production',
        })
        paymentMethodObject.isLoading = true
    }

    function onSubmit(e) {
        if (getSelectedPaymentGateway() === gatewayId) {
            e.preventDefault()

            const messages = validate($form)
            if (messages.length) {
                showError(messages, true)
            } else {
                hideError()
                placeOrder(hostedFieldsInstance)
            }

            return false
        }
    }

    async function fetchPaymentData() {
        if (isPayForOrderPage && getOrderIdFromPaymentData(paymentData) === orderId) {
            return true
        }

        const { success, data } = await (isPayForOrderPage
            ? fetchPaymentAndAuthData(orderId)
            : request('/wp-admin/admin-ajax.php?action=get_payment_data_from_cart', {
                  method: 'POST',
                  body: new FormData($form[0]),
              }))

        if (!success) {
            showError(data.errors)
            paymentData = null
            authData = null
        } else {
            hideError()
            paymentData = data.paymentData
            authData = data.auth || null
        }

        return success
    }

    async function postProcessPayment() {
        const response = await fetch(wc_checkout_params.checkout_url, {
            method: 'POST',
            body: new FormData($form[0]),
        })

        const result = await response.json()

        if (result.redirect) {
            window.location.href = result.redirect
            return
        }

        if (result.result !== 'success') {
            throw new Error(result.messages || wc_checkout_params.i18n_checkout_error)
        }

        try {
            if (typeof result.paymentData === 'string') {
                result.paymentData = JSON.parse(result.paymentData)
            }
            if (typeof result.auth === 'string') {
                result.auth = JSON.parse(result.auth)
            }
        } catch (err) {
            console.error(err)
        }

        paymentData = result.paymentData
        authData = result.auth

        return result
    }

    function hideError() {
        $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove()
    }

    function displayError(error_message) {
        $form.prepend(
            '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                wrapMessage(error_message) +
                '</div>',
        )
    }

    function showError(error_message, shouldScrollToNotices = true) {
        hideError()
        displayError(error_message)
        if (globalError) {
            displayError(globalError)
        }
        setTimeout(() => $form.removeClass('processing').unblock())
        $.unblockUI()

        if (shouldScrollToNotices) {
            $form.find('.input-text, select, input:checkbox').trigger('validate').blur()
            scrollToNotices($form)
        }
        $(document.body).trigger('checkout_error')
    }
})
