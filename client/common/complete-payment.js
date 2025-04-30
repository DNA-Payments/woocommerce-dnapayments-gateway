import { updateOrderStatus } from './api/update-order-status'
import { tryParse } from './try-parse'

export async function completePayment({ paymentResult, redirect, setLoading = () => {}, setErrors = () => {} }) {
    if (paymentResult) {
        setLoading(true)
        try {
            const orderId = getOrderIdFromPaymentData(paymentResult)
            const { success, data } = await updateOrderStatus(orderId, paymentResult)

            if (success) {
                return (window.location.href = data.redirect)
            } else {
                setErrors(data.errors)
            }
        } catch (err) {
            setErrors([err.message])
        }
        setLoading(false)
    }

    if (redirect) {
        window.location.href = redirect
    }
}

export function getOrderIdFromPaymentData(data) {
    const customData = tryParse(data?.merchantCustomData)
    return customData?.orderId || null
}
