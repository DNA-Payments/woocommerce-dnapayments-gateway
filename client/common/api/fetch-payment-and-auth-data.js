import { request } from './request'

export async function fetchPaymentAndAuthData(orderId, amount = null) {
    const formData = new FormData()
    formData.append('order_id', orderId)
    formData.append('total', amount)

    return await request('/wp-admin/admin-ajax.php?action=get_payment_and_auth_data', {
        method: 'POST',
        body: formData,
    })
}
