import { logError } from './log'

export const tryParse = (str) => {
    if (!str) return null

    if (typeof str !== 'string') return str

    try {
        return JSON.parse(str)
    } catch (err) {
        logError(err, 'JSON parse error')
        return null
    }
}
