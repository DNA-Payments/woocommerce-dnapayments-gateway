import { updateOrderStatus } from './api/update-order-status'
import { tryParse } from './try-parse'

/**
 * Completes the payment process and handles redirection
 * Keeps loading state active during page navigation
 */
export async function completePayment({
    paymentResult,
    redirect,
    setLoading = () => {},
    setErrors = () => {},
    page = 'checkout',
}) {
    // Helper function to handle redirects while keeping loading state active
    const handleRedirect = (url) => {
        setLoading(true)

        // Use setTimeout to ensure the loading state remains active during the entire redirection process
        // This addresses the 4-5 second gap some merchants experience during redirection
        setTimeout(() => {
            window.location.href = url
            // We don't call setLoading(false) here because the page will be unloaded anyway
            // and we want to keep the loading indicator visible during the entire navigation process
        }, 100)
    }

    if (paymentResult) {
        setLoading(true)
        try {
            const orderId = getOrderIdFromPaymentData(paymentResult)
            const { success, data } = await updateOrderStatus(orderId, paymentResult, page)

            if (success) {
                handleRedirect(data.redirect)
                return
            } else {
                const responseErrors = data?.errors || ['DNA Payments could not update the order status.']
                setErrors(responseErrors)
                setLoading(false)
                return
            }
        } catch (err) {
            setErrors([err.message])
            setLoading(false)
            return
        }
    }

    if (redirect) {
        handleRedirect(redirect)
    }
}

/**
 * Extracts order ID from payment data
 */
export function getOrderIdFromPaymentData(data) {
    const customData = tryParse(data?.merchantCustomData)
    return customData?.orderId || null
}
