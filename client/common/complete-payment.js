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
    waitForNavigation = true,
}) {
    // Helper function to handle redirects while keeping loading state active
    const handleRedirect = (url) => {
        setLoading(true)
        window.location.href = url

        // Navigation is already in flight; the loading state stays active. Callers that resolve
        // the checkout themselves (e.g. the Blocks payment component) pass waitForNavigation:false
        // so they aren't held back by the fallback timeout when navigation is slow/blocked.
        return waitForNavigation ? new Promise((resolve) => setTimeout(resolve, 5000)) : Promise.resolve()
    }

    if (paymentResult) {
        setLoading(true)
        try {
            const orderId = getOrderIdFromPaymentData(paymentResult)
            const { success, data } = await updateOrderStatus(orderId, paymentResult, page)

            if (success) {
                return handleRedirect(data.redirect)
            } else {
                setErrors(data.errors)
                setLoading(false)
            }
        } catch (err) {
            setErrors([err.message])
            setLoading(false)
        }
    }

    if (redirect) {
        return handleRedirect(redirect)
    }
}

/**
 * Extracts order ID from payment data
 */
export function getOrderIdFromPaymentData(data) {
    const customData = tryParse(data?.merchantCustomData)
    return customData?.orderId || null
}
