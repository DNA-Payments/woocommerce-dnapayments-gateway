import { GATEWAY_ID, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_APPLE_PAY, GATEWAY_ID_PAYPAL } from '../../common/constants'

export function getGlobalVariables() {
    /* global wc_dna_params */
    const gatewayId = GATEWAY_ID
    const isTestMode = wc_dna_params.is_test_mode === '1'
    const integrationType = wc_dna_params.integration_type
    const isHostedFields = wc_dna_params.integration_type === 'seamless'
    const allowSavingCards = wc_dna_params.allow_saving_cards === '1'
    const tempToken = wc_dna_params.temp_token
    const cards = Object.values(wc_dna_params.cards || {})
    const availableGateways = wc_dna_params.available_gateways || []
    const sendCallbackEveryFailedAttempt = Number(wc_dna_params.send_callback_every_failed_attempt)
    const availableSchemes = wc_dna_params.available_schemes || []
    const iconPath = wc_dna_params.card_scheme_icon_path
    const terminalConfig = wc_dna_params.terminal_config
    const transactionType = wc_dna_params.transaction_type
    const placeOrderButtonText = (wc_dna_params.placeOrderButtonText || '').trim()

    return {
        gatewayId,
        isTestMode,
        isHostedFields,
        integrationType,
        allowSavingCards,
        cards: allowSavingCards ? cards : [],
        availableGateways,
        sendCallbackEveryFailedAttempt,
        availableSchemes,
        iconPath,
        tempToken,
        terminalConfig,
        transactionType,
        placeOrderButtonText,
    }
}

export const DNA_PAYMENTS_GATEWAYS = [GATEWAY_ID, GATEWAY_ID_GOOGLE_PAY, GATEWAY_ID_APPLE_PAY, GATEWAY_ID_PAYPAL]

export const getRequiredFields = (isShippingIncluded) => {
    const fields = [
        'terms',
        'billing_email',
        'billing_country',
        'billing_city',
        'billing_address_1',
        'billing_last_name',
        'billing_first_name',
        'billing_postcode',
    ]
    if (isShippingIncluded) {
        fields.push(
            ...[
                'shipping_country',
                'shipping_city',
                'shipping_address_1',
                'shipping_last_name',
                'shipping_first_name',
                'shipping_postcode',
            ],
        )
    }
    return fields
}
