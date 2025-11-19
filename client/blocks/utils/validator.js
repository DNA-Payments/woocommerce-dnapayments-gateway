import { __ } from '@wordpress/i18n'
import { select } from '@wordpress/data'
import { TEXT_DOMAIN } from '../../common/constants'

const fieldIds = [
    // Billing fields
    'billing_country',
    'billing_city',
    'billing_state',
    'billing_address_1',
    'billing_email',
    'billing_last_name',
    'billing_first_name',
    'billing_postcode',
    'billing_phone',
    // Shipping fields
    'shipping_country',
    'shipping_city',
    'shipping_state',
    'shipping_address_1',
    'shipping_last_name',
    'shipping_first_name',
    'shipping_postcode',
    'shipping_phone',
]

export const getValidationErrors = () => {
    const errorMessages = []
    const validationStore = select(window.wc.wcBlocksData.VALIDATION_STORE_KEY)

    if (validationStore.hasValidationErrors()) {
        fieldIds.forEach((fieldId) => {
            const validationError = validationStore.getValidationError(fieldId)
            if (validationError) {
                errorMessages.push(validationError.message)
            }
        })

        if (!errorMessages.length) {
            errorMessages.push(__('A validation error occurred. Please check all fields and try again.', TEXT_DOMAIN))
        }
    }

    return errorMessages
}
