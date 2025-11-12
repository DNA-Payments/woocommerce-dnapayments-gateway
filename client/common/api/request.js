import errors from '../errors'

export async function request(input, init = {}) {
    const opts = { credentials: init.credentials ?? 'same-origin', ...init }

    try {
        const response = await fetch(input, opts)
        return await response.json()
    } catch (err) {
        return { success: false, data: { errors: [errors.CRITICAL_WEBSITE_ERROR.message] } }
    }
}
