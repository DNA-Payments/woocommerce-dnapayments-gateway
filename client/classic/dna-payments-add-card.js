'use strict'

import 'whatwg-fetch'
import { requestAction } from '../common/api/request'
import { renderHostedFields } from './utils/render-hosted-fields'
import { createCardError, getSelectedPaymentGateway, createSetLoading, getPlaceOrderButton } from './utils/ui'
import { createPlaceOrder } from './utils/place-order'
import { getGlobalVariables } from './utils/data'

let hostedFieldsInstance = null

jQuery(function ($) {
    const $form = $('form#add_payment_method')
    const cardError = createCardError()
    const setFormLoading = createSetLoading($form)

    const { gatewayId, isHostedFields } = getGlobalVariables()

    const placeOrder = createPlaceOrder({
        cardError,
        cards: [],
        allowSavingCards: false,
        setFormLoading,
        fetchPaymentData: async () => {
            const result = await requestAction('get_payment_and_auth_data_for_saving_card')
            if (result.success) {
                return result.data
            }
            cardError.show(result.data.errors)
        },
        onComplete: (result) => {
            if (result.redirect) {
                window.location.href = result.redirect
            }
        },
    })

    if ($form.length) {
        $form.on('submit', onSubmit)

        if (isHostedFields) {
            renderHostedFields({
                setFormLoading,
                onSuccess: (instance) => {
                    hostedFieldsInstance = instance
                    cardError.hide()
                },
                onError: (errMsg) => (errMsg ? cardError.show(errMsg) : cardError.hide()),
            })
        }
    }

    function onSubmit(e) {
        if (getSelectedPaymentGateway() === gatewayId) {
            e.preventDefault()
            placeOrder(hostedFieldsInstance)
            return false
        }
    }

    const placeOrderBtn = getPlaceOrderButton()
    if (placeOrderBtn && getSelectedPaymentGateway() === gatewayId) {
        placeOrderBtn.removeAttribute('disabled')
        window.__dnapaymentsReady = true
    }
})
