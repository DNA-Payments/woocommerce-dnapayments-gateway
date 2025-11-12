import { GATEWAY_ID } from '../constants'
import { request } from './request'

export async function updateOrderStatus(orderId, paymentResult) {
    const formData = new FormData()
    formData.append('order_id', orderId)
    formData.append('wc-dnapayments-result', JSON.stringify(paymentResult))
    formData.append('_dna_nonce', getNonce())

    return await request('/wp-admin/admin-ajax.php?action=' + GATEWAY_ID + '_update_order_status', {
        method: 'POST',
        body: formData,
    })
}

function getNonce() {
    return (window.wc_dna_params?.nonces?.update_order_status) || ''
}