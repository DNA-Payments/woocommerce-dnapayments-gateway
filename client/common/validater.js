import errors from './errors'

export function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
}

export function isEmpty(value) {
    return value === null || value === undefined || (typeof value === 'string' && value.trim() === '')
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

// Determines Apple Pay availability using a single-flight promise stored on `window`.
// Ensures `window.DNAPayments.ApplePayComponent.isAvailable` is invoked only once across bundles,
// and all concurrent callers share the same result. Subsequent calls return the cached value.
export const checkApplePayAvailability = async () => {
    if (typeof window.DNAPayments?.ApplePayComponent?.isAvailable !== 'function') {
        return false
    }

    if (typeof window.__dnaApplePayAvailabilityCached !== 'undefined') {
        return window.__dnaApplePayAvailabilityCached
    }

    if (!window.__dnaApplePayAvailabilityPromise) {
        window.__dnaApplePayAvailabilityPromise = Promise.resolve(window.DNAPayments.ApplePayComponent.isAvailable())
            .then((available) => {
                window.__dnaApplePayAvailabilityCached = Boolean(available)
                return window.__dnaApplePayAvailabilityCached
            })
            .catch((err) => {
                console.error('WooCommerce: Error in checkApplePayAvailability', err)
                window.__dnaApplePayAvailabilityCached = false
                return false
            })
    }

    return window.__dnaApplePayAvailabilityPromise
}
