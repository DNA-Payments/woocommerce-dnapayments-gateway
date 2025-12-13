'use strict'

import 'whatwg-fetch'
import {
    createCardError,
    createSetLoading,
    getSelectedPaymentGateway,
    wrapMessage,
    scrollToNotices,
    setLoading,
    getPlaceOrderButton,
} from './utils/ui'
import { createPlaceOrder } from './utils/place-order'
import { renderHostedFields } from './utils/render-hosted-fields'
import { DNA_PAYMENTS_GATEWAYS, getGlobalVariables, getRequiredFields } from './utils/data'
import { validate } from './utils/validate'
import { initPaymentComponent } from './utils/payment-component'

import { fetchPaymentAndAuthData } from '../common/api/fetch-payment-and-auth-data'
import { isInitFailed } from '../common/payment-component-helper'
import { completePayment, getOrderIdFromPaymentData } from '../common/complete-payment'
import { requestActionWithFormData } from '../common/api/request'
import { tryParse } from '../common/try-parse'
import { checkApplePayAvailability } from '../common/validater'
import { GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_APPLE_PAY, GATEWAY_ID_PAYPAL } from '../common/constants'
import { addGatewayId, setNonces } from '../common/utils'

/* global wc_checkout_params */
/* global wc_dna_params */
const orderId = Number(wc_dna_params.order_id) || 0
const isPayForOrderPage = Boolean(orderId)
const page = wc_dna_params.page || ''

let isUpdating = false // is event updated_checkout will be triggered
let hostedFieldsInstance = null
let paymentData = null
let authData = null
let globalError = null
let isFirstRender = true
let isRendering = false
let serializedFormData = null
let pendingRenderRequest = null

jQuery(function ($) {
    const { isTestMode, gatewayId, isHostedFields, tempToken, cards, allowSavingCards, placeOrderButtonText } =
        getGlobalVariables()

    const $form = isPayForOrderPage ? $('form#order_review') : $('form.woocommerce-checkout')
    const cardError = createCardError()
    const setFormLoading = createSetLoading($form)

    // --- Toggling the "Place order" button label ---
    let originalPlaceOrderText = null

    const readButtonText = (btn) => {
        if (!btn) return ''

        if (btn.tagName === 'BUTTON') {
            return btn.textContent
        }

        return btn.getAttribute('data-value') || btn.value || ''
    }

    const writeButtonText = (btn, text) => {
        if (!btn || typeof text !== 'string') {
            return
        }

        if (btn.tagName === 'BUTTON') {
            btn.textContent = text
            return
        }

        btn.value = text
        btn.setAttribute('data-value', text)
    }

    const placeOrder = createPlaceOrder({
        cards,
        cardError,
        allowSavingCards,
        setFormLoading,
        fetchPaymentData: async () => {
            try {
                return await postProcessPayment(gatewayId, $(`#wc-${gatewayId}-new-payment-method`).is(':checked'))
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
                page,
            }),
    })

    const render = async ({ selectedGateway, shouldFetchPaymentData, shouldScrollToError = false }) => {
        if (!selectedGateway) {
            selectedGateway = getSelectedPaymentGateway()
        }

        const placeOrderBtn = getPlaceOrderButton()
        if (!placeOrderBtn) {
            return
        }

        // Remembering the default button label once
        if (originalPlaceOrderText === null) {
            originalPlaceOrderText = readButtonText(placeOrderBtn)
        }

        if (!(await checkApplePayAvailability())) {
            $('.wc_payment_method.payment_method_' + GATEWAY_ID_APPLE_PAY).hide()
            if (selectedGateway === GATEWAY_ID_APPLE_PAY) {
                $('.wc_payment_method.payment_method_dnapayments #payment_method_dnapayments').click()
                return
            }
        } else {
            $('.wc_payment_method.payment_method_' + GATEWAY_ID_APPLE_PAY).show()
        }

        if (!DNA_PAYMENTS_GATEWAYS.includes(selectedGateway)) {
            $form.find('.dnapayments-footer').hide()
            placeOrderBtn.removeAttribute('disabled')
            writeButtonText(placeOrderBtn, originalPlaceOrderText)
            pendingRenderRequest = null
            return
        }

        if (isRendering) {
            pendingRenderRequest = () => render({ selectedGateway, shouldFetchPaymentData, shouldScrollToError })
            return
        }

        // Our gateway is selected then setting custom label if provided
        if (placeOrderButtonText) {
            writeButtonText(placeOrderBtn, placeOrderButtonText)
        }

        serializedFormData = $form.serialize()
        isRendering = true
        isFirstRender = false
        $form.find('.dnapayments-footer').show()

        try {
            switch (selectedGateway) {
                case GATEWAY_ID_GOOGLE_PAY:
                case GATEWAY_ID_APPLE_PAY:
                case GATEWAY_ID_PAYPAL: {
                    const messages = validate($form)
                    if (messages.length) {
                        // scroll to error if rendered payment component disappear because of failed validation
                        showError(messages, shouldScrollToError || Boolean(paymentData))
                        paymentData = null
                        authData = null
                    } else if (!paymentData || shouldFetchPaymentData) {
                        setFormLoading(true)
                        await fetchPaymentData()
                        setFormLoading(false)
                    }
                    placeOrderBtn.setAttribute('disabled', 'disabled')
                    await renderPaymentComponent(selectedGateway)
                    break
                }
                default:
                    placeOrderBtn.removeAttribute('disabled')
            }

            if (isHostedFields) {
                await renderHostedFields({
                    setFormLoading,
                    onSuccess: (instance) => {
                        hostedFieldsInstance = instance
                        cardError.hide()
                    },
                    onError: (errMsg) => cardError.show(errMsg),
                })
            }
        } catch (err) {
            console.error('render error', err)
        }

        isRendering = false
        if (pendingRenderRequest) {
            pendingRenderRequest()
            pendingRenderRequest = null
        }
    }

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

    // WooCommerce updated_checkout - don't scroll as it triggers blur on all fields
    $(document.body).on('updated_checkout', () => {
        render({ shouldFetchPaymentData: true, shouldScrollToError: isFirstRender })
    })

    // Payment method change - allow scroll to show validation errors
    $form.on('change', 'input[name="payment_method"]', function () {
        render({ selectedGateway: $(this).val(), shouldScrollToError: true })
    })
    $form.on('change', 'input, textarea, select', function (e) {
        const elem = e.target

        // Skip processing if the element is missing, or if it's a radio/checkbox that isn't the "terms" checkbox
        if (!elem || ((elem.type === 'radio' || elem.type === 'checkbox') && elem.name !== 'terms')) return
        // we check form data is changed or not to avoid unnessary rendering. We do not check on event updated_checkout, because it reinserts html part where payment components renrder.
        if (serializedFormData && serializedFormData === $form.serialize()) return

        const name = elem.getAttribute('name')
        const isShippingIncluded = $form.find('[name="ship_to_different_address"]').is(':checked')

        const required = elem.getAttribute('aria-required')
        const isRequired = (required && required === 'true') || getRequiredFields(isShippingIncluded).includes(name)

        if (!isUpdating && isRequired) {
            render({ shouldFetchPaymentData: name !== 'terms', shouldScrollToError: false })
        }
    })

    if (!tempToken) {
        globalError = `Authentication failed. Please check that your credentials are correct. If you are using the Hosted Fields integration, make sure it is enabled for your account by your payment provider.`
        displayError(globalError)
    }

    // On the Pay for Order page, ensure initialization
    if (isPayForOrderPage) {
        render({ shouldScrollToError: isFirstRender })
    }

    window.__dnapaymentsReady = true

    async function renderPaymentComponent(paymentMethodId) {
        const $container = $form.find('#' + paymentMethodId + '_container')

        // clear container HTML element
        $container.removeClass('has-error').html('')

        const events = {
            onClick: () => {
                setFormLoading(true)
            },
            onBeforeProcessPayment: () => postProcessPayment(paymentMethodId),
            onPaymentSuccess: (paymentResult) =>
                completePayment({
                    paymentResult,
                    redirect: paymentData?.paymentSettings?.returnUrl,
                    setLoading: setFormLoading,
                    setErrors: showError,
                    page,
                }),
            onCancel: () => {
                setFormLoading(false)
            },
            onError: (message) => {
                setFormLoading(false)
                if (paymentMethodId !== GATEWAY_ID_APPLE_PAY || !isInitFailed(err)) {
                    showError(message, true)
                }
            },
        }

        setLoading($container.parent(), true)
        try {
            await initPaymentComponent(
                paymentMethodId,
                {
                    containerElement: $container[0],
                    events,
                    paymentData,
                    token: authData ? authData.access_token : tempToken,
                    environment: isTestMode ? 'sandbox' : 'production',
                    terminalId: wc_dna_params.terminal_id,
                },
                { paymentData, $form },
            )
        } catch (errMessage) {
            $container.addClass('has-error').html(wrapMessage(errMessage))
        }
        setLoading($container.parent(), false)
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
            ? fetchPaymentAndAuthData(orderId, page)
            : requestActionWithFormData('get_payment_data_from_cart', new FormData($form[0])))

        if (!success) {
            showError(data.errors, true)
            paymentData = null
            authData = null
        } else {
            hideError()
            paymentData = data.paymentData
            authData = data.auth || null
        }

        return success
    }

    async function postProcessPayment(selectedGatewayId, storeCardOnFile = undefined) {
        if (isPayForOrderPage) {
            await fetchPaymentData()
            return {
                paymentData: {
                    ...paymentData,
                    merchantCustomData: JSON.stringify({
                        ...(tryParse(paymentData.merchantCustomData) || {}),
                        storeCardOnFile,
                        gatewayId: selectedGatewayId,
                    }),
                },
                auth: authData,
            }
        }

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
            if (typeof result.nonces === 'string') {
                result.nonces = JSON.parse(result.nonces)
            }
        } catch (err) {
            console.error(err)
        }

        result.paymentData.merchantCustomData = addGatewayId(result.paymentData.merchantCustomData, selectedGatewayId)

        paymentData = result.paymentData
        authData = result.auth
        setNonces(result.nonces)

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
