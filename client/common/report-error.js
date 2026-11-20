import { GATEWAY_ID, NONCE_FIELD } from './constants'
import { getNonce } from './utils'

/**
 * Sending checkout errors that happen in the customer's browser to the store's dnapayments log
 */

const ACTION = GATEWAY_ID + '_client_log'
const MAX_REPORTS = 3

// Shopper input mistakes, already shown inline next to the card fields
const SKIP_CODES = ['NOT_VALID_CARD_DATA']

const seen = new Set()
let context = {}

export function setLogContext(values) {
    context = { ...context, ...values }
}

export function getAjaxUrl(action) {
    /* global wc_checkout_params */
    if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.wc_ajax_url) {
        return wc_checkout_params.wc_ajax_url.replace('%%endpoint%%', action)
    }

    return '/?wc-ajax=' + action
}

export function reportError(err, details = {}) {
    try {
        if (seen.size >= MAX_REPORTS || typeof navigator === 'undefined' || !navigator.sendBeacon) {
            return
        }

        const code = String(err?.code ?? err?.name ?? '').slice(0, 40)
        const message = String(err?.message ?? (typeof err === 'string' ? err : '')).slice(0, 200)

        if (SKIP_CODES.includes(code)) {
            return
        }

        const key = code + '|' + message
        const nonce = getNonce(ACTION)

        if (seen.has(key) || !nonce) {
            return
        }

        seen.add(key)

        const fields = { ...context, ...details, code, message }
        const data = new FormData()

        data.append(NONCE_FIELD, nonce)
        Object.keys(fields).forEach((name) => {
            if (fields[name] !== undefined && fields[name] !== null && fields[name] !== '') {
                data.append(name, String(fields[name]))
            }
        })

        navigator.sendBeacon(getAjaxUrl(ACTION), data)
    } catch (e) {
        // Reporting must never break checkout.
    }
}
