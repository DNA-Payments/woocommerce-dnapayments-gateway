import { logError } from './log'

/**
 * Console tracing for the funding-type gate.
 *
 * Off unless something sets `window.wcDnaPayments.debug` - the companion plugin does when
 * its logging level is set to full trace. Checkout is a shopper-facing page, so this stays
 * quiet by default rather than narrating every payment.
 */
const trace = (label, ...args) => {
    if (!window.wcDnaPayments || !window.wcDnaPayments.debug) {
        return
    }

    console.log('%c[dna-funding-gate]%c ' + label, 'color:#7c3aed;font-weight:bold', 'color:inherit', ...args)
}

export const traceGate = trace

/**
 * What actually happened, for the `diagnose()` report below.
 *
 * "Is the gate working?" is otherwise unanswerable from the outside: when DNA declines to
 * call a handler - because the terminal lacks the EVENTS capability, say - the symptom is
 * simply that nothing happens.
 */
const SURFACE_LABELS = {
    hostedFields: 'Hosted Fields',
    events: 'DNA Checkout',
    wallet: 'Apple Pay / Google Pay',
}

const state = {
    surfaces: [],
    invocations: 0,
    lastContext: null,
    lastDecision: null,
    lastEvent: null,
}

/**
 * Registry of merchant validation handlers.
 *
 * Companion plugins (e.g. DNA Payments - Card Controls) push a function onto
 * `window.wcDnaPayments.validators` to take part in DNA's funding-type gate. Each handler
 * receives the DNA validation context and returns the DNA decision:
 *
 *   true | undefined          -> proceed
 *   false                     -> decline with the default message
 *   { proceed, reason, code } -> proceed or decline with your own message
 *
 * Handlers may be async; DNA awaits the result before continuing. The gate is fail-closed:
 * a handler that throws cancels the payment, which is deliberate for a compliance check.
 */
const getRegistry = () => {
    window.wcDnaPayments = window.wcDnaPayments || {}

    if (!Array.isArray(window.wcDnaPayments.validators)) {
        window.wcDnaPayments.validators = []
    }

    return window.wcDnaPayments.validators
}

/**
 * Whether any handler is registered.
 *
 * DNA skips the card BIN lookup entirely when no callback is registered, so the gateway
 * only subscribes when something is actually listening. Without this, every merchant would
 * pay for a lookup they do not use.
 */
export const hasValidators = () => getRegistry().some((fn) => typeof fn === 'function')

/**
 * Run every registered handler in turn. The first decline wins.
 *
 * @param {object} context DNA validation context.
 * @param {object} meta    Which event and surface asked: { event, surface }. DNA's context
 *                         describes the card only, so a handler otherwise cannot tell an
 *                         inline card-entry check from the one at submit.
 * @returns {Promise<true|{proceed: boolean, reason?: string, code?: string}>}
 */
export const runValidators = async (context, meta = {}) => {
    for (const validator of getRegistry()) {
        if (typeof validator !== 'function') {
            continue
        }

        const decision = await validator(context, meta)

        if (decision === false) {
            return { proceed: false }
        }

        if (decision && decision.proceed === false) {
            return decision
        }
    }

    return true
}

/**
 * Build the `events` handlers DNA expects, or undefined when nothing is listening.
 *
 * `onCardNumberValidate` / `cardNumberValidate` is inline UX feedback as the customer types
 * or picks a saved card. `onValidate` / `validate` is the enforcement point: it fires at
 * submit, against the card actually about to be charged, after the final order state is
 * known. Both share the same logic.
 *
 * Apple Pay and Google Pay have no card-number entry, so they expose `onValidate` only.
 *
 * @param {'events'|'hostedFields'|'wallet'} shape Which naming the target surface uses.
 */
export const getValidationEvents = (shape = 'events') => {
    if (!hasValidators()) {
        trace('no validators registered, skipping DNA callbacks for shape:', shape)
        return undefined
    }

    const surface = SURFACE_LABELS[shape] || shape

    const makeHandler = (eventName) => async (context) => {
        const started = Date.now()
        state.invocations += 1
        state.lastContext = context
        state.lastEvent = eventName
        trace(surface + ' -> ' + eventName + '() fired', {
            fundingType: context?.fundingType,
            cardScheme: context?.cardScheme,
            paymentMethodType: context?.paymentMethodType,
            isTokenized: context?.isTokenized,
            issuerCountry: context?.issuerCountry,
            isCorporate: context?.isCorporate,
        })

        try {
            const decision = await runValidators(context, { event: eventName, surface })
            const allowed = decision === true
            const label = eventName + '() ' + (allowed ? 'ALLOWED' : 'DECLINED') + ' (' + (Date.now() - started) + 'ms)'

            // No format specifiers here: the styled %c prefix already consumes two
            // arguments, so a %d would bind to the wrong one.
            state.lastDecision = decision

            if (allowed) {
                trace(label)
            } else {
                trace(label, decision)
            }

            return decision
        } catch (err) {
            // Fail closed: a validation error must never let the payment through.
            trace(eventName + '() threw, payment will be cancelled (fail-closed)', err)
            logError(err)
            throw err
        }
    }

    if (!state.surfaces.includes(shape)) {
        state.surfaces.push(shape)
    }

    const events =
        shape === 'hostedFields'
            ? { validate: makeHandler('validate'), cardNumberValidate: makeHandler('cardNumberValidate') }
            : shape === 'wallet'
              ? { onValidate: makeHandler('onValidate') }
              : { onValidate: makeHandler('onValidate'), onCardNumberValidate: makeHandler('onCardNumberValidate') }

    trace(surface + ': registering ' + Object.keys(events).join('() + ') + '()', {
        validators: getRegistry().filter((fn) => typeof fn === 'function').length,
    })

    return events
}

/**
 * Print a status report for the funding gate.
 *
 * Call `wcDnaPayments.diagnose()` in the browser console. Written for the question that is
 * otherwise impossible to answer by watching: nothing happened - is the gate broken, or was
 * it simply never asked?
 */
export const diagnose = () => {
    const registered = getRegistry().filter((fn) => typeof fn === 'function').length
    const caps = window.wcDnaPayments && window.wcDnaPayments.capabilities

    console.group('%cDNA funding gate - status', 'color:#7c3aed;font-weight:bold')
    console.log('validators registered :', registered || 'NONE - no companion plugin is listening')
    console.log('surfaces wired        :', state.surfaces.length ? state.surfaces.join(', ') : 'none')
    console.log('terminal capabilities :', caps === undefined ? 'unknown' : caps.length ? caps.join(', ') : 'NONE GRANTED')
    console.log('validations so far    :', state.invocations)

    if (state.invocations) {
        console.log('last event            :', state.lastEvent || 'n/a')
        console.log('last card seen        :', state.lastContext)
        console.log('last decision         :', state.lastDecision)
    }

    if (!registered) {
        console.warn('No validator is registered, so no restriction can be applied.')
    } else if (caps && !caps.includes('EVENTS')) {
        console.warn(
            'The terminal does not grant EVENTS, so DNA will never call these handlers and ' +
                'no card restriction will be enforced at checkout. Ask DNA Payments to add ' +
                'allowedExtendedBinCapabilities: CONFIGURATION, EVENTS to this terminal.',
        )
    } else if (!state.invocations) {
        console.info(
            'Registered but not yet called. DNA invokes the handler once a card number ' +
                'resolves to a BIN, or at submit - enter a full card number and watch again.',
        )
    }

    console.groupEnd()

    return {
        registered,
        surfaces: state.surfaces.slice(),
        capabilities: caps,
        invocations: state.invocations,
    }
}

// Always exposed, whatever the logging level: it is a deliberate action by whoever is
// debugging, not background noise for a shopper.
if (typeof window !== 'undefined') {
    window.wcDnaPayments = window.wcDnaPayments || {}
    window.wcDnaPayments.diagnose = diagnose
}

/**
 * Publish the order the customer is currently paying for.
 *
 * DNA's validation context describes the card, not the order, so a validator that needs to
 * price the decision has no way to know which order it is judging. The gateway knows, so it
 * shares it here once the payment payload has been built.
 *
 * @param {object} paymentData Payload about to be sent to DNA.
 */
export const publishOrderId = (paymentData) => {
    window.wcDnaPayments = window.wcDnaPayments || {}

    try {
        const customData = JSON.parse(paymentData?.merchantCustomData || '{}')
        window.wcDnaPayments.orderId = customData?.orderId || null
    } catch (err) {
        window.wcDnaPayments.orderId = null
    }

    trace('order being paid:', window.wcDnaPayments.orderId || '(none - will fall back to the cart)')
}

/**
 * Remember the reason for the most recent decline.
 *
 * The embedded widget renders no failure screen for Apple Pay / Google Pay declines: it
 * calls the merchant's `declined` event instead, with no reason attached. Stash it here so
 * that handler can show the message our own rule produced.
 */
let lastDeclineReason = null

/**
 * The most recent field-level refusal, e.g. a card refused by the acceptance rules.
 *
 * hostedFieldsInstance.validate() only reports that a field is invalid, never why. Without
 * this the checkout-level banner falls back to "Some card details are incorrect", which
 * sends the customer to re-check an expiry date that was never the problem.
 */
let lastFieldDecline = null

export const setFieldDecline = (decline) => {
    lastFieldDecline = decline && decline.code ? decline : null
}

export const getFieldDecline = () => lastFieldDecline

export const setLastDeclineReason = (reason) => {
    lastDeclineReason = reason || null
}

export const takeLastDeclineReason = () => {
    const reason = lastDeclineReason
    lastDeclineReason = null
    return reason
}
