import { GATEWAY_ID, NONCE_FIELD } from '../constants'
import { getNonce } from '../utils'
import errors from '../errors'

export async function request(...args) {
    try {
        const response = await fetch(...args)
        return await response.json()
    } catch (err) {
        return { success: false, data: { errors: [errors.CRITICAL_WEBSITE_ERROR.message] } }
    }
}

export async function requestAction(action, data = {}) {
    const _formData = new FormData()

    for (const key in data) {
        _formData.append(key, data[key])
    }

    return await requestActionWithFormData(action, _formData)
}

export async function requestActionWithFormData(action, formData) {
    const _action = GATEWAY_ID + '_' + action

    formData.append(NONCE_FIELD, getNonce(_action))

    /* global wc_checkout_params */
    let url = '/?wc-ajax=' + _action
    if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.wc_ajax_url) {
         url = wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', _action)
    }

    return await request(url, {
        method: 'POST',
        body: formData,
    })
}
