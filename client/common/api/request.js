import errors from '../errors'

export async function request(...args) {
    try {
        const response = await fetch(...args)
        return await response.json()
    } catch (err) {
        return { success: false, data: { errors: [errors.CRITICAL_WEBSITE_ERROR.message] } }
    }
}
