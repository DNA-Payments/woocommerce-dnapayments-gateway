import { useEffect } from '@wordpress/element'
import { useSelect, select, dispatch } from '@wordpress/data'
import { __ } from '@wordpress/i18n'
import { VALIDATION_STORE_KEY, CART_STORE_KEY } from '@woocommerce/block-data'
import { TEXT_DOMAIN } from '../../common/constants'
import { logData } from '../../common/log'

export function useCheckoutValidation({ onCheckoutValidation, onMessages }) {
    const validationStore = useSelect((select) => {
        return select(VALIDATION_STORE_KEY)
    }, [])

    const cartStore = useSelect((select) => {
        return select(CART_STORE_KEY)
    }, [])

    useEffect(() => {
        const handler = async (payload) => {
            let errorMessages = []
            if (typeof validationStore?.getValidationErrors === 'function') {
                const errorObj = validationStore.getValidationErrors()

                const { billingAddress, shippingAddress } = cartStore.getCartData()
                /*
                 Workaround: WooCommerce block-based checkout incorrectly validates 'state' as required
                 for country 'GB', even though 'state' is optional. This is not a regular error.
                 To avoid the block-based checkout validation issue, we clear 'billing-state' and
                 'shipping-state' errors when the selected country is 'GB'.
                */
                if (billingAddress?.country === 'GB' && errorObj['billing-state']) {
                    await Promise.resolve(dispatch(VALIDATION_STORE_KEY).clearValidationError('billing-state'))
                    delete errorObj['billing-state']
                }
                if (shippingAddress?.country === 'GB' && errorObj['shipping-state']) {
                    await Promise.resolve(dispatch(VALIDATION_STORE_KEY).clearValidationError('shipping-state'))
                    delete errorObj['shipping-state']
                }

                errorMessages = Object.values(errorObj)
                    .filter((error) => error?.message)
                    .map((error) => error.message)
            } else if (validationStore.hasValidationErrors()) {
                errorMessages = [__('A validation error occurred. Please check all fields and try again.', TEXT_DOMAIN)]
            }

            logData('onCheckoutValidation', payload, errorMessages)
            if (errorMessages.length) {
                onMessages && onMessages(errorMessages, payload)
            }
            return !errorMessages.length
        }

        return onCheckoutValidation(handler)
    }, [onCheckoutValidation, onMessages, validationStore, cartStore])
}
