import { requestAction } from './request'

export async function updateOrderStatus(orderId, paymentResult) {
    return await requestAction('update_order_status', {
        order_id: orderId,
        'wc-dnapayments-result': JSON.stringify(paymentResult),
    })
}
