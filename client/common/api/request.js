import { GATEWAY_ID, NONCE_FIELD } from '../constants'
import { getNonce } from '../utils'
import errors from '../errors'

export async function request(...args) {
    try {
        const response = await fetch(...args)
        // Error responses can contain HTML or an empty body. Catch JSON/body-read
        // failures here so we can still report the HTTP status below.
        const data = await response.json().catch(() => null)

        // fetch() resolves for HTTP errors (e.g. 403 or 502); it does not throw.
        // Preserve server error data when available, otherwise report the status.
        if (!response.ok) {
            return {
                success: false,
                data: data?.data || {
                    errors: [`DNA Payments AJAX request failed (${response.status})`],
                },
            }
        }

        return data
    } catch (err) {
        // Handles fetch failures, including network errors
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
