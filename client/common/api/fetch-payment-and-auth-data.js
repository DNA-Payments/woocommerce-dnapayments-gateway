import { requestAction } from './request'

export async function fetchPaymentAndAuthData(orderId, page, orderKey = '') {
    return await requestAction('get_payment_and_auth_data', {
        order_id: orderId,
        page,
        order_key: orderKey,
    })
}
