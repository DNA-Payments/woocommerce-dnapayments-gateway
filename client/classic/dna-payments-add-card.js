'use strict'

import 'whatwg-fetch'
import { request } from '../common/api/request'
import { renderHostedFields } from './utils/render-hosted-fields'
import { createCardError, getSelectedPaymentGateway, createSetLoading, wrapMessage } from './utils/ui'
import { createPlaceOrder } from './utils/place-order'
import { getQueryParam } from './utils/url-helper'
import { getGlobalVariables } from './utils/data'

let hostedFieldsInstance = null

jQuery(function ($) {
    const $form = $('form#add_payment_method')
    const cardError = createCardError()
    const message = createMessage()
    const setFormLoading = createSetLoading($form)

    const { gateway_id, isHostedFields, cards } = getGlobalVariables()

    const placeOrder = createPlaceOrder({
        cardError,
        cards,
        setFormLoading,
        fetchPaymentData: async () => {
            const result = await request('/wp-admin/admin-ajax.php?action=get_payment_and_auth_data_for_saving_card')
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
                onError: (errMsg) => cardError.show(errMsg),
            })
        }
    } else {
        const result = getQueryParam('result', true)

        if (!result) {
            message.hide()
        } else if (result === 'success') {
            message.success('Successfully added payment method to your account.')
        } else if (result === 'failure') {
            message.error('Unable to add payment method to your account.')
        }
    }

    function onSubmit(e) {
        if (getSelectedPaymentGateway() === gateway_id) {
            e.preventDefault()
            placeOrder(hostedFieldsInstance)
            return false
        }
    }

    function createMessage() {
        let $woo = $('#content #primary').prev('.woocommerce')
        if (!$woo.length) {
            $woo = $(`<div class="woocommerce"></div>`)
            $('#content #primary').prepend($woo)
        }

        const hide = () => $woo.html('')
        return {
            hide: hide,
            success: (msg) => {
                hide()
                $woo.html(wrapMessage(msg, true))
            },
            error: (msg) => {
                hide()
                $woo.html(wrapMessage(msg, false))
            },
        }
    }
})
