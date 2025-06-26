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

/* global wc_dna_params */
const orderId = Number(wc_dna_params.order_id) || 0
const isPayForOrderPage = Boolean(orderId)

let isUpdating = false // is event updated_checkout will be triggered
let hostedFieldsInstance = null
let paymentData = null
let authData = null

jQuery(function ($) {
    const { gateway_id, cards, isHostedFields, tempToken } = getGlobalVariables()

    const $form = isPayForOrderPage ? $('form#order_review') : $('form.woocommerce-checkout')
    const cardError = createCardError()
    const setFormLoading = createSetLoading($form)

    const placeOrder = createPlaceOrder({
        cardError,
        cards,
        setFormLoading,
        fetchPaymentData: async () => {
            try {
                return await postProcessPayment()
            } catch (err) {
                cardError.show(err.message)
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
        const name = e.target?.getAttribute('name')
        const isShippingIncluded = $form.find('[name="ship_to_different_address"]').is(':checked')

        if (!isUpdating && getRequiredFields(isShippingIncluded).includes(name)) {
            render({ shouldFetchPaymentData: name !== 'terms' })
        }
    })

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
            onBeforeProcessPayment: postProcessPayment,
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
        paymentMethodObject.create($container[0], paymentData, events, authData ? authData.access_token : tempToken)
        paymentMethodObject.isLoading = true
    }

    function onSubmit(e) {
        if (getSelectedPaymentGateway() === gateway_id) {
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

    function showError(error_message, shouldScrollToNotices = true) {
        hideError()
        $form.prepend(
            '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' +
                wrapMessage(error_message) +
                '</div>',
        )
        $form.removeClass('processing').unblock()
        $.unblockUI()
        if (shouldScrollToNotices) {
            $form.find('.input-text, select, input:checkbox').trigger('validate').blur()
            console.log('scrollToNotices $form', $form)
            scrollToNotices($form)
        }
        $(document.body).trigger('checkout_error')
    }
})
