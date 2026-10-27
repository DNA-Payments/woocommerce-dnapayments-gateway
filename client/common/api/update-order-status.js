import { GATEWAY_ID } from '../constants'
import { requestAction } from './request'

export async function updateOrderStatus(orderId, paymentResult, page, orderKey = '') {
    return await requestAction('update_order_status', {
        order_id: orderId,
        order_key: orderKey,
        page,
        [`wc-${GATEWAY_ID}-result`]: JSON.stringify(paymentResult),
    })
}
