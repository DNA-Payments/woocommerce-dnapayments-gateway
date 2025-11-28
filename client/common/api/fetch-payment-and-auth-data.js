import { requestAction } from './request'

export async function fetchPaymentAndAuthData(orderId, page) {
    return await requestAction('get_payment_and_auth_data', {
        order_id: orderId,
        page,
    })
}
