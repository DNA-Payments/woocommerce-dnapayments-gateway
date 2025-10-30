import { GATEWAY_ID } from '../constants'
import { request } from './request'
import { dnaPaymentsSettingsData } from '../../blocks/utils/get-settings'

export async function updateOrderStatus(orderId, paymentResult) {
    const formData = new FormData()
    formData.append('order_id', orderId)
    formData.append('wc-dnapayments-result', JSON.stringify(paymentResult))
    formData.append('_dna_nonce', dnaPaymentsSettingsData?.nonces?.update_order_status || '')

    return await request('/wp-admin/admin-ajax.php?action=' + GATEWAY_ID + '_update_order_status', {
        method: 'POST',
        body: formData,
    })
}
