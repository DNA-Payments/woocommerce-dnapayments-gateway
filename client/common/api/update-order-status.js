import { GATEWAY_ID } from '../constants'
import { requestAction } from './request'

export async function updateOrderStatus(orderId, paymentResult, page) {
    return await requestAction('update_order_status', {
        order_id: orderId,
        page,
        [`wc-${GATEWAY_ID}-result`]: JSON.stringify(paymentResult),
    })
}
