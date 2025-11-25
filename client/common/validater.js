import errors from './errors'

export function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
}

export function isEmpty(value) {
    return !value || value.toString().trim() === ''
}

export function validateAddress(address, section) {
    if (!address) {
        return [section === 'shipping' ? errors.SHIPPING_ADDRESS_REQUIRED : errors.BILLING_ADDRESS_REQUIRED]
    }

    const errorsMap = {
        firstName: section === 'shipping' ? errors.SHIPPING_FIRST_NAME_REQUIRED : errors.BILLING_FIRST_NAME_REQUIRED,
        lastName: section === 'shipping' ? errors.SHIPPING_LAST_NAME_REQUIRED : errors.BILLING_LAST_NAME_REQUIRED,
        addressLine1: section === 'shipping' ? errors.SHIPPING_ADDRESS_REQUIRED : errors.BILLING_ADDRESS_REQUIRED,
        city: section === 'shipping' ? errors.SHIPPING_CITY_REQUIRED : errors.BILLING_CITY_REQUIRED,
        postalCode: section === 'shipping' ? errors.SHIPPING_POSTCODE_REQUIRED : errors.BILLING_POSTCODE_REQUIRED,
    }

    return Object.keys(errorsMap)
        .filter((key) => isEmpty(address[key]))
        .map((key) => errorsMap[key].message)
}

export function validatePaymentData(paymentData) {
    const messages = []
    const email = paymentData?.customerDetails?.email

    if (isEmpty(email)) {
        messages.push(errors.BILLING_EMAIL_REQUIRED.message)
    } else if (!isValidEmail(email)) {
        messages.push(errors.BILLING_EMAIL_INVALID.message)
    }

    messages.push(...validateAddress(paymentData?.customerDetails?.billingAddress, 'billing'))
    messages.push(...validateAddress(paymentData?.customerDetails?.deliveryDetails?.deliveryAddress, 'shipping'))
    messages.push(...validateTermsAndConditions())

    return messages
}

export function validateTermsAndConditions(isClassic = false) {
    const checkbox = document.getElementById('terms-and-conditions')

    if (checkbox && !checkbox.checked) {
        return [errors.TERMS_NOT_ACCEPTED.message]
    }
    return []
}

export function shouldHideOrderLines(terminalConfig) {
    const settings = terminalConfig?.paymentMethodsSettings
    if (!settings || typeof settings !== 'object') return false

    const isPayPalOrKlarnaActive = ['paypal', 'klarna'].some((method) => settings[method]?.status === 'active')
    return !isPayPalOrKlarnaActive
}

export const checkApplePayAvailability = async () => {
    if (window.DNAPayments?.ApplePayComponent?.isAvailable !== 'function') {
        return false
    }

    try {
        return await window.DNAPayments.ApplePayComponent.isAvailable()
    } catch (err) {
        console.error('Error in checkApplePayAvailability', err)
        return false
    }
}
