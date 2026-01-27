import { DNA_PAYMENTS_GATEWAYS } from './utils/data'

function handlePlaceOrderClick(e) {
    if (e.target && e.target.id === 'place_order') {
        e.preventDefault()
        e.stopImmediatePropagation()
        return false
    }
}

function handlePaymentGatewayChange() {
    if (window.__dnapaymentsReady) return

    const button = document.getElementById('place_order')
    if (!button) return

    const selectedGateway = document.querySelector('input[name="payment_method"]:checked')?.value
    if (selectedGateway && DNA_PAYMENTS_GATEWAYS.indexOf(selectedGateway) !== -1) {
        button.setAttribute('disabled', 'disabled')
    } else {
        button.removeAttribute('disabled')
    }
}

function initPaymentMethodChangeListener() {
    document.removeEventListener('click', handlePlaceOrderClick)

    document.addEventListener('change', function (e) {
        const target = e.target
        if (target && target.matches('input[name="payment_method"]')) {
            handlePaymentGatewayChange()
        }
    })
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        handlePaymentGatewayChange()
        initPaymentMethodChangeListener()
    })
} else {
    handlePaymentGatewayChange()
    initPaymentMethodChangeListener()
}

document.addEventListener('click', handlePlaceOrderClick)
