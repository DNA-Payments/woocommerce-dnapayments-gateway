import { getPaymentMethodData } from '@woocommerce/settings'
import { GATEWAY_ID } from '../../common/constants'
import { setNonces } from '../../common/utils'

const settings = getPaymentMethodData(GATEWAY_ID, {})
setNonces(settings.nonces || {})
const parsedAutoRedirectDelay = Number(settings.auto_redirect_delay_in_ms)
const autoRedirectDelayInMs =
    settings.integration_type !== 'seamless' && Number.isFinite(parsedAutoRedirectDelay) && parsedAutoRedirectDelay >= 1000
        ? parsedAutoRedirectDelay
        : undefined

export const dnaPaymentsSettingsData = {
    isTestMode: settings.is_test_mode,
    integrationType: settings.integration_type,
    terminalId: settings.terminal_id,
    allowSavingCards: settings.allow_saving_cards,
    sendCallbackEveryFailedAttempt: Number(settings.send_callback_every_failed_attempt),
    cards: settings.allow_saving_cards ? settings.cards : [],
    cardSchemeIconPath: settings.card_scheme_icon_path,
    terminalConfig: settings.terminal_config,
    transactionType: settings.transaction_type,
    availableSchemes: settings.available_schemes || [],
    nonces: settings.nonces || {},
    autoRedirectDelayInMs,
    paymentMethodsSettings: settings.payment_methods_settings || null,
}
