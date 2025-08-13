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

export const isApplePayAvailable = () => {
    try {
        const ApplePaySession = window.ApplePaySession // it is declared as a class
        return Boolean(ApplePaySession?.canMakePayments())
    } catch (e) {
        console.error('Error in isApplePayAvailable', e)
        return false
    }
}

// @ts-ignore
export const getApplePaySession = () => window.ApplePaySession

export function loadApplePaySDK() {
    return new Promise((resolve, reject) => {
        if (document.getElementById('apple-pay-sdk')) {
            return resolve()
        }

        const script = document.createElement('script')
        script.id = 'apple-pay-sdk'
        script.src = 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js'
        script.async = true
        script.setAttribute('crossorigin', 'anonymous')
        script.onload = () => resolve()
        script.onerror = () => reject(new Error('Failed to load Apple Pay SDK.'))

        document.head.appendChild(script)
    })
}

export const checkApplePayAvailability = async (terminalConfig) => {
    const merchantId = terminalConfig?.merchantId

    if (merchantId && typeof window.DNAPayments?.ApplePayComponent?.isAvailable === 'function') {
        try {
            await loadApplePaySDK()
            return await window.DNAPayments.ApplePayComponent.isAvailable(merchantId)
        } catch (err) {
            console.error('Error in checkApplePayAvailability', err)
            return false
        }
    }

    return isApplePayAvailable()
}
