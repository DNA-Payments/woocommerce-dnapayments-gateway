import { useEffect, useRef } from '@wordpress/element'
import { GATEWAY_ID_APPLE_PAY, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_PAYPAL } from '../../common/constants'
import { useCheckoutUpdate } from '../hooks/use-checkout-update'

export function isPlaceOrderButtonDisabled(activePaymentMethod) {
    return [GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_APPLE_PAY, GATEWAY_ID_PAYPAL].includes(activePaymentMethod)
}

export function setPlaceOrderButtonDisabled(isDisabled, button) {
    const placeOrderButton = button || getPlaceOrderButton()

    if (!placeOrderButton) {
        return
    }

    if (isDisabled) {
        placeOrderButton.setAttribute('disabled', 'disabled')
    } else {
        placeOrderButton.removeAttribute('disabled')
    }
}

export function triggerPlaceOrderButtonClick() {
    const placeOrderButton = getPlaceOrderButton()

    if (placeOrderButton) {
        placeOrderButton.removeAttribute('disabled')
        placeOrderButton.click()
    }
}

export function getPlaceOrderButton() {
    return document.querySelector('button.wc-block-components-checkout-place-order-button')
}

export function useTogglePlaceOrderButtonDisabled(activePaymentMethod) {
    const refActivePaymentMethod = useRef(activePaymentMethod)
    const update = () => {
        const isDisabled = isPlaceOrderButtonDisabled(refActivePaymentMethod.current)
        setPlaceOrderButtonDisabled(isDisabled)
    }

    useEffect(() => {
        refActivePaymentMethod.current = activePaymentMethod
        update()
    }, [activePaymentMethod])

    useCheckoutUpdate(update)

    update()
}
