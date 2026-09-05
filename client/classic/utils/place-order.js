import { debounce } from '../../common/debounce'
import errors from '../../common/errors'
import { payHostedFields } from '../../common/pay-hosted-fields'
import { getSubscriptionPaymentMethods, hasSubscription, getSubscriptionVerificationConfig } from '../../common/subscription'
import { shouldHideOrderLines } from '../../common/validater'
import { getGlobalVariables } from './data'
import {
    getFieldDecline,
    getValidationEvents,
    publishOrderId,
    setLastDeclineReason,
    takeLastDeclineReason,
    traceGate,
} from '../../common/validators'

export const createPlaceOrder = ({
    page,
    setFormLoading,
    allowSavingCards,
    cards,
    paymentMethods,
    cardError,
    fetchPaymentData,
    onComplete,
}) =>
    debounce(async (hostedFieldsInstance) => {
        const { isTestMode, integrationType, terminalConfig, autoRedirectDelayInMs, paymentMethodsSettings } =
            getGlobalVariables()

        setFormLoading(true)

        if (integrationType === 'seamless') {
            const { isValid } = await hostedFieldsInstance.validate()
            if (!isValid) {
                // Prefer the reason the card was refused; see the blocks equivalent.
                const decline = getFieldDecline()

                cardError.show(decline ? decline.message : errors.CARD_DETAILS_INVALID.message)
                return setFormLoading(false)
            }
        }

        const { paymentData, auth, paymentMethodsSettings: freshSettings } = (await fetchPaymentData()) || {}

        if (!paymentData || !auth) {
            return setFormLoading(false)
        }

        publishOrderId(paymentData)

        // Prefer the rules recomputed alongside this payload; they reflect the final order.
        const acceptanceRules = freshSettings || paymentMethodsSettings

        if (shouldHideOrderLines(terminalConfig) && paymentData?.orderLines) {
            delete paymentData.orderLines
        }

        const validationEvents = getValidationEvents('events')

        // The embedded widget renders no failure screen for wallet declines: it calls
        // `declined` instead, with no reason attached. Keep our own reason so it can be shown.
        const wrappedValidationEvents = validationEvents && {
            onCardNumberValidate: validationEvents.onCardNumberValidate,
            onValidate: async (context) => {
                const decision = await validationEvents.onValidate(context)
                setLastDeclineReason(decision === true ? null : decision?.reason)
                return decision
            },
        }

        const events =
            integrationType === 'embedded'
                ? {
                      paid: () => {
                          setFormLoading(true)
                      },
                      declined: () => {
                          const reason = takeLastDeclineReason()
                          traceGate('DNA Checkout -> events.declined() fired', reason || '(no reason from our rule)')
                          if (reason) {
                              cardError.show(reason)
                          }
                      },
                      ...wrappedValidationEvents,
                  }
                : wrappedValidationEvents

        const config = {
            isTestMode,
            cards,
            allowSavingCards,
            events,
        }

        // Omit the key entirely when there are no rules, so the terminal config applies.
        if (acceptanceRules && Object.keys(acceptanceRules).length) {
            config.paymentMethodsSettings = acceptanceRules
            traceGate('DNA Checkout -> configure({ paymentMethodsSettings }) [' + integrationType + ']: acceptance rules applied', acceptanceRules)
        } else {
            traceGate('DNA Checkout -> configure() [' + integrationType + ']: no acceptance rules, terminal configuration applies')
        }

        if (autoRedirectDelayInMs) {
            config.autoRedirectDelayInMs = autoRedirectDelayInMs
        }

        const _paymentMethods = hasSubscription(paymentData) ? getSubscriptionPaymentMethods() : paymentMethods
        if (_paymentMethods) {
            config.paymentMethods = _paymentMethods
        }

        if (page === 'change_payment_method') {
            const verificationConfig = getSubscriptionVerificationConfig()
            Object.assign(config, verificationConfig);
        }

        window.DNAPayments.configure(config)

        switch (integrationType) {
            case 'seamless': {
                const result = await payHostedFields(hostedFieldsInstance, paymentData, auth)

                // Stop here on failure rather than falling through. onComplete() happens to
                // be harmless for an error result today, but only because payHostedFields
                // never returns `data` alongside `error` - an invariant nothing enforces.
                if (result.error) {
                    cardError.show(result.error)

                    // CLOSE_TRANSACTION is the one failure that still navigates, to the
                    // gateway's failure page; keep the mask up while the browser leaves.
                    if (result.redirect) {
                        setFormLoading(true)
                        window.location.href = result.redirect
                        break
                    }

                    setFormLoading(false)
                    break
                }

                if (onComplete) {
                    onComplete(result)
                } else {
                    setFormLoading(false)
                }
                break
            }
            case 'embedded':
                window.DNAPayments.openPaymentIframeWidget({ ...paymentData, auth })
                setFormLoading(false)
                break
            default:
                window.DNAPayments.openPaymentPage({ ...paymentData, auth })
                setFormLoading(false)
        }
    })
