import { requestAction } from './request'

export async function fetchPaymentAndAuthData(orderId, amount = null) {
    return await requestAction('get_payment_and_auth_data', {
        order_id: orderId,
        total: amount,
    })
}
