import { normalizeCardSchemeName } from '../../common/card-scheme'
import { createHostedFields } from '../../common/create-hosted-fields'
import { createModal } from '../../common/create-modal'
import { getGlobalVariables } from './data'

export const renderHostedFields = async ({ setFormLoading, onSuccess, onError, force = false }) => {
    const {
        gatewayId,
        cards,
        isTestMode,
        iconPath,
        sendCallbackEveryFailedAttempt,
        tempToken,
        availableSchemes,
        paymentMethodsSettings,
        terminalId,
    } = getGlobalVariables()

    let hasFieldDecline = false

    const $payment_form = jQuery('#wc-' + gatewayId + '-form')
    const $card_form = $payment_form.find('.wc-credit-card-form')
    const $payment_token = $payment_form.find('input[name="wc-' + gatewayId + '-payment-token"]')
    const $tokenized_cvc = $payment_form.find('#dna-card-cvc-token-container')

    // If the element exists and contains an iframe, return.
    // `force` rebuilds anyway: DNA caches declined cards for the lifetime of an instance,
    // so a new instance is the only way to clear a decline once the basket changes.
    if (!force && $payment_form.find('#dna-card-number').has('iframe').length) {
        return null
    }

    $card_form.hide()

    setFormLoading(true)

    try {
        const hostedFieldsInstance = await createHostedFields({
            isTestMode,
            accessToken: tempToken,
            terminalId,
            domElements: {
                name: $payment_form.find('#dna-card-name')[0],
                number: $payment_form.find('#dna-card-number')[0],
                expDate: $payment_form.find('#dna-card-exp')[0],
                cvv: $payment_form.find('#dna-card-cvc')[0],
                cvvToken: $payment_form.find('#dna-card-cvc-token')[0],
            },
            threeDSModal: createModal('three-d-secure'),
            sendCallbackEveryFailedAttempt,
            showPlaceholderOnlyOnFocus: false,
            paymentMethodsSettings,
            onFieldError: ({ field, message, code }) => {
                // Only a refusal gets promoted to the visible error area. Ordinary
                // "not filled in yet" invalidity carries no code and would otherwise shout
                // at the customer while they are still typing.
                if ('number' !== field && 'cardNumber' !== field) {
                    return
                }

                if (code && message) {
                    hasFieldDecline = true
                    onError && onError(message)
                } else if (hasFieldDecline) {
                    hasFieldDecline = false
                    onError && onError('')
                }
            },
        })

        let prevScheme = null

        hostedFieldsInstance.on('change', () => {
            const state = hostedFieldsInstance.getState()
            const img = document.getElementById('dna-card-selected')
            let scheme = normalizeCardSchemeName(state.cardInfo?.type)

            if (!scheme || !availableSchemes.includes(scheme)) {
                scheme = 'none'
            }

            if (img && scheme !== prevScheme) {
                img.setAttribute('src', iconPath + '/' + scheme + '.svg')
            }
            prevScheme = scheme
        })

        hostedFieldsInstance.on('dna-payments-three-d-secure-show', () => {
            setFormLoading(false)
        })

        hostedFieldsInstance.on('dna-payments-three-d-secure-hide', () => {
            setFormLoading(true)
        })

        const onPaymentTokenChange = async (selected) => {
            if (!selected || selected === 'new') {
                $tokenized_cvc.hide()
                $card_form.show()
                await hostedFieldsInstance.selectCard(null)
                return
            }

            const card = cards.find((c) => String(c.id) === String(selected))
            const cvvState = hostedFieldsInstance.getTokenizedCardCvvState(card)

            if (cvvState === 'required') {
                $tokenized_cvc.show()
            } else {
                $tokenized_cvc.hide()
            }

            // selectCard runs the card acceptance rules and the cardNumberValidate handler,
            // and rejects when either declines the saved card.
            try {
                await hostedFieldsInstance.selectCard(card)
                $card_form.hide()
            } catch (err) {
                $tokenized_cvc.hide()
                onError && onError(err.message)
            }
        }

        await onPaymentTokenChange($payment_token.filter(':checked').val())
        $payment_token.change(function () {
            onPaymentTokenChange(jQuery(this).val())
        })

        onSuccess && onSuccess(hostedFieldsInstance)
    } catch (err) {
        onError(err.message)
    }

    setFormLoading(false)
}
