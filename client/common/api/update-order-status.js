import { GATEWAY_ID, NONCE_FIELD } from '../constants'
import { getNonce } from '../utils'
import { request } from './request'

export async function updateOrderStatus(orderId, paymentResult) {
    const formData = new FormData()
    const action = GATEWAY_ID + '_update_order_status'

    formData.append('order_id', orderId)
    formData.append('wc-dnapayments-result', JSON.stringify(paymentResult))
    formData.append(NONCE_FIELD, getNonce(action))

    return await request('/wp-admin/admin-ajax.php?action=' + action, {
        method: 'POST',
        body: formData,
    })
}
