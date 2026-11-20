import { GATEWAY_ID, NONCE_FIELD } from '../constants'
import { getNonce } from '../utils'
import errors from '../errors'
import { getAjaxUrl, reportError } from '../report-error'

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
                httpStatus: response.status,
                data: data?.data || {
                    errors: [`DNA Payments AJAX request failed (${response.status})`],
                },
            }
        }

        return data
    } catch (err) {
        // Handles fetch failures, including network errors
        return { success: false, httpStatus: 0, data: { errors: [errors.CRITICAL_WEBSITE_ERROR.message] } }
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

    const result = await request(getAjaxUrl(_action), {
        method: 'POST',
        body: formData,
    })

    // Blocked, failed, or unreachable request (WAF 403, proxy 502, lost connection)
    if (result?.httpStatus !== undefined && result.httpStatus !== 400) {
        reportError({ code: 'AJAX_' + (result.httpStatus || 'NETWORK'), message: action })
    }

    return result
}
