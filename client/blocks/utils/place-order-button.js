import { useEffect, useRef, useCallback } from '@wordpress/element'
import { GATEWAY_ID_APPLE_PAY, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_PAYPAL, GATEWAY_ID_ALIPAY, GATEWAY_ID_WECHAT_PAY, GATEWAY_ID_ALIPAY_PLUS } from '../../common/constants'
import { useCheckoutUpdate } from '../hooks/use-checkout-update'

export function isPlaceOrderButtonDisabled(activePaymentMethod) {
    return [GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_APPLE_PAY, GATEWAY_ID_PAYPAL, GATEWAY_ID_ALIPAY, GATEWAY_ID_WECHAT_PAY, GATEWAY_ID_ALIPAY_PLUS].includes(activePaymentMethod)
}

const DNA_PLACE_ORDER_DISABLED_ATTR = 'data-dnapayments-place-order-disabled'

export function getPlaceOrderButton() {
    return document.querySelector('button.wc-block-components-checkout-place-order-button')
}

export function setPlaceOrderButtonDisabled(isDisabled, button) {
    const placeOrderButton = button || getPlaceOrderButton()
    if (!placeOrderButton) return

    if (isDisabled) {
        placeOrderButton.setAttribute('disabled', 'disabled')
        placeOrderButton.setAttribute(DNA_PLACE_ORDER_DISABLED_ATTR, '1')
        return
    }

    // Включаем обратно только если мы сами выключали
    if (placeOrderButton.hasAttribute(DNA_PLACE_ORDER_DISABLED_ATTR)) {
        placeOrderButton.removeAttribute('disabled')
        placeOrderButton.removeAttribute(DNA_PLACE_ORDER_DISABLED_ATTR)
    }
}

export function triggerPlaceOrderButtonClick() {
    const placeOrderButton = getPlaceOrderButton()
    if (!placeOrderButton) return

    placeOrderButton.removeAttribute('disabled')
    placeOrderButton.click()
}

export function useTogglePlaceOrderButtonDisabled(activePaymentMethod) {
    const refActivePaymentMethod = useRef(activePaymentMethod)
    const timeoutRef = useRef(null)

    const clearPendingTimeout = useCallback(() => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current)
            timeoutRef.current = null
        }
    }, [])

    const scheduleUpdate = useCallback(() => {
        clearPendingTimeout()

        timeoutRef.current = setTimeout(() => {
            const button = getPlaceOrderButton()
            const isDisabled = isPlaceOrderButtonDisabled(refActivePaymentMethod.current)
            setPlaceOrderButtonDisabled(isDisabled, button)

            timeoutRef.current = null
        }, 100)
    }, [clearPendingTimeout])

    useEffect(() => {
        refActivePaymentMethod.current = activePaymentMethod
        scheduleUpdate()
    }, [activePaymentMethod, scheduleUpdate])

    useCheckoutUpdate(scheduleUpdate)

    useEffect(() => {
        return () => {
            clearPendingTimeout()
            // On unmount, re-enable only if it was disabled by our code.
            setPlaceOrderButtonDisabled(false)
        }
    }, [clearPendingTimeout])
}
