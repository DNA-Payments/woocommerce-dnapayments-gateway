import { reportError } from './report-error'

/**
 * Log an error to the console and report it to the store's dnapayments log.
 *
 * @param {*}      err     Error or DNA SDK error object ({ code, message }).
 * @param {string} title   Console label only.
 * @param {object} details Extra fields for the report, e.g. { gatewayId: 'dnapayments_apple_pay' }.
 */
export function logError(err, title = '', details = {}) {
    console.error(title || 'DNA Payments error', err)
    reportError(err, details)
}
