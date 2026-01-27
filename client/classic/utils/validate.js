import errors from '../../common/errors'
import { isEmpty, isValidEmail } from '../../common/validater'
import { getRequiredFields } from './data'

export function validate($form) {
    const messages = []
    const errorMaps = {
        terms: errors.TERMS_NOT_ACCEPTED.message,
        billing_email: errors.BILLING_EMAIL_REQUIRED.message,
        billing_first_name: errors.BILLING_FIRST_NAME_REQUIRED.message,
        billing_last_name: errors.BILLING_LAST_NAME_REQUIRED.message,
        billing_country: errors.BILLING_COUNTRY_REQUIRED.message,
        billing_address_1: errors.BILLING_ADDRESS_REQUIRED.message,
        billing_city: errors.BILLING_CITY_REQUIRED.message,
        billing_postcode: errors.BILLING_POSTCODE_REQUIRED.message,
        shipping_first_name: errors.SHIPPING_FIRST_NAME_REQUIRED.message,
        shipping_last_name: errors.SHIPPING_LAST_NAME_REQUIRED.message,
        shipping_country: errors.SHIPPING_COUNTRY_REQUIRED.message,
        shipping_address_1: errors.SHIPPING_ADDRESS_REQUIRED.message,
        shipping_city: errors.SHIPPING_CITY_REQUIRED.message,
        shipping_postcode: errors.SHIPPING_POSTCODE_REQUIRED.message,
    }

    const isShippingIncluded = $form.find('[name="ship_to_different_address"]').is(':checked')
    getRequiredFields(isShippingIncluded).forEach((name) => {
        const $el = $form.find('[name="' + name + '"]')
        const value = $el.val()

        if (!$el.length || !errorMaps[name]) {
            return
        }

        if (name === 'terms') {
            if ($el.length && $el.is(':checked') === false) {
                messages.push(errors.TERMS_NOT_ACCEPTED.message)
            }
            return
        }

        if (isEmpty(value)) {
            messages.push(errorMaps[name])
        } else if (name === 'billing_email' && !isValidEmail(value)) {
            messages.push(errors.BILLING_EMAIL_INVALID.message)
        }
    })

    return messages
}
