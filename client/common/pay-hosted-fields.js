import errors from './errors'
import { logError } from './log'

export async function payHostedFields(hostedFieldsInstance, paymentData, auth) {
    const { returnUrl, failureReturnUrl } = paymentData.paymentSettings

    try {
        const { data } = await hostedFieldsInstance.submit({
            paymentData,
            token: auth.access_token,
        })

        return { data, redirect: returnUrl }
    } catch (err) {
        logError(err)

        if (err.code === 'NOT_VALID_CARD_DATA') {
            return { error: errors.CARD_DETAILS_INVALID.message }
        }

        hostedFieldsInstance.clear()

        let error = err.message || errors.CARD_PAYMENT_FAIL.message

        if (String(err.code).includes('CLOSE_TRANSACTION')) {
            return { error, redirect: failureReturnUrl }
        }

        return { error }
    }
}
