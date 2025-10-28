import { GATEWAY_ID } from '../constants'
import { request } from './request'

export async function updateOrderStatus(orderId, paymentResult) {
    const formData = new FormData()
    formData.append('order_id', orderId)
    formData.append('wc-dnapayments-result', JSON.stringify(paymentResult))

    return await request('/wp-admin/admin-ajax.php?action=' + GATEWAY_ID + '_update_order_status', {
        method: 'POST',
        body: formData,
    })
}
