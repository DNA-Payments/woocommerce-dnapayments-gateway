const SERVER_TIMEOUT = {
    code: 'SERVER_TIMEOUT',
    message: 'The server took too long to respond. Please check your connection and try again.',
}

const UNKNOWN_ERROR = {
    code: 'UNKNOWN_ERROR',
    message: 'Something went wrong. Please contact our support team for assistance.',
}

const CRITICAL_WEBSITE_ERROR = {
    code: 'CRITICAL_WEBSITE_ERROR',
    message: 'A critical error occurred. Please try again later or contact support if the issue continues.',
}

const HOSTED_FIELDS_INIT_FAIL = {
    code: 'HOSTED_FIELDS_INIT_FAIL',
    message: 'We could not initialise the card payment. Please reload the page and try again.',
}

const CARD_PAYMENT_FAIL = {
    code: 'CARD_PAYMENT_FAIL',
    message:
        'Your card was not authorised. Please check your details and try again, contact your bank if the issue continues.',
}

const CARD_PAYMENT_CANCEL = {
    code: 'CARD_PAYMENT_CANCEL',
    message: 'You have cancelled the payment process. Please try again if you wish to complete the order.',
}

const CARD_DETAILS_INVALID = {
    code: 'CARD_DETAILS_INVALID',
    message:
        'Some card details are incorrect. Please double-check the card number, expiry date, and CVV, then try again.',
}

const APPLE_PAY_INIT_FAIL = {
    code: 'APPLE_PAY_INIT_FAIL',
    message:
        'Apple Pay payments are not supported in your current browser. We would advise you to use the latest version of Safari, on a compatible Apple device to complete your transaction',
}

const GOOGLE_PAY_INIT_FAIL = {
    code: 'GOOGLE_PAY_INIT_FAIL',
    message: 'Google Pay is not supported in your current browser.',
}

const APPLE_PAY_VALIDATION_FAIL = {
    code: 'APPLE_PAY_VALIDATION_FAIL',
    message:
        'Apple Pay button not rendered. Please fill all required fields correctly and try again.',
}

const GOOGLE_PAY_VALIDATION_FAIL = {
    code: 'GOOGLE_PAY_VALIDATION_FAIL',
    message:
        'Google Pay button not rendered. Please fill all required fields correctly and try again.',
}

const TERMS_NOT_ACCEPTED = {
    code: 'TERMS_NOT_ACCEPTED',
    message: 'Please accept the terms and conditions to continue.',
}

const BILLING_COUNTRY_REQUIRED = {
    code: 'BILLING_COUNTRY_REQUIRED',
    message: 'Please enter your billing country.',
}

const BILLING_CITY_REQUIRED = {
    code: 'BILLING_CITY_REQUIRED',
    message: 'Please enter your billing city.',
}

const BILLING_ADDRESS_REQUIRED = {
    code: 'BILLING_ADDRESS_REQUIRED',
    message: 'Please enter your billing address.',
}

const BILLING_EMAIL_REQUIRED = {
    code: 'BILLING_EMAIL_REQUIRED',
    message: 'Please enter your billing email address.',
}

const BILLING_EMAIL_INVALID = {
    code: 'BILLING_EMAIL_INVALID',
    message: 'Please enter a valid billing email address.',
}

const BILLING_LAST_NAME_REQUIRED = {
    code: 'BILLING_LAST_NAME_REQUIRED',
    message: 'Please enter your billing last name.',
}

const BILLING_FIRST_NAME_REQUIRED = {
    code: 'BILLING_FIRST_NAME_REQUIRED',
    message: 'Please enter your billing first name.',
}

const BILLING_POSTCODE_REQUIRED = {
    code: 'BILLING_POSTCODE_REQUIRED',
    message: 'Please enter your billing postcode.',
}

const SHIPPING_COUNTRY_REQUIRED = {
    code: 'SHIPPING_COUNTRY_REQUIRED',
    message: 'Please enter your shipping country.',
}

const SHIPPING_CITY_REQUIRED = {
    code: 'SHIPPING_CITY_REQUIRED',
    message: 'Please enter your shipping city.',
}

const SHIPPING_ADDRESS_REQUIRED = {
    code: 'SHIPPING_ADDRESS_REQUIRED',
    message: 'Please enter your shipping address.',
}

const SHIPPING_EMAIL_INVALID = {
    code: 'SHIPPING_EMAIL_INVALID',
    message: 'Please enter a valid shipping email address.',
}

const SHIPPING_LAST_NAME_REQUIRED = {
    code: 'SHIPPING_LAST_NAME_REQUIRED',
    message: 'Please enter your shipping last name.',
}

const SHIPPING_FIRST_NAME_REQUIRED = {
    code: 'SHIPPING_FIRST_NAME_REQUIRED',
    message: 'Please enter your shipping first name.',
}

const SHIPPING_POSTCODE_REQUIRED = {
    code: 'SHIPPING_POSTCODE_REQUIRED',
    message: 'Please enter your shipping postcode.',
}

export default {
    SERVER_TIMEOUT,
    UNKNOWN_ERROR,
    APPLE_PAY_INIT_FAIL,
    GOOGLE_PAY_INIT_FAIL,
    APPLE_PAY_VALIDATION_FAIL,
    GOOGLE_PAY_VALIDATION_FAIL,
    HOSTED_FIELDS_INIT_FAIL,
    CARD_PAYMENT_FAIL,
    CARD_PAYMENT_CANCEL,
    CARD_DETAILS_INVALID,
    CRITICAL_WEBSITE_ERROR,
    TERMS_NOT_ACCEPTED,
    BILLING_COUNTRY_REQUIRED,
    BILLING_CITY_REQUIRED,
    BILLING_ADDRESS_REQUIRED,
    BILLING_EMAIL_REQUIRED,
    BILLING_EMAIL_INVALID,
    BILLING_LAST_NAME_REQUIRED,
    BILLING_FIRST_NAME_REQUIRED,
    BILLING_POSTCODE_REQUIRED,
    SHIPPING_COUNTRY_REQUIRED,
    SHIPPING_CITY_REQUIRED,
    SHIPPING_ADDRESS_REQUIRED,
    SHIPPING_EMAIL_INVALID,
    SHIPPING_LAST_NAME_REQUIRED,
    SHIPPING_FIRST_NAME_REQUIRED,
    SHIPPING_POSTCODE_REQUIRED,
}
