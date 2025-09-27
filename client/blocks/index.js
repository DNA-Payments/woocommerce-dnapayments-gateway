/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry'
import { __ } from '@wordpress/i18n'
import { select } from '@wordpress/data'
import { useState, useEffect, RawHTML } from '@wordpress/element'
import { getPaymentMethodData } from '@woocommerce/settings'
import { decodeEntities } from '@wordpress/html-entities'

/**
 * Internal dependencies
 */
import { GATEWAY_ID, TEXT_DOMAIN } from './constants'
import { DnapaymentsCreditCardFields } from './components/credit-card-fields'
import { usePaymentForm } from './hooks/use-payment-form'
import { setPlaceOrderButtonDisabled } from './utils/place-order-button'

const settings = getPaymentMethodData(GATEWAY_ID, {})
const allowSavingCards = settings.allow_saving_cards
const isHostedFields = settings.integration_type === 'seamless'
const customPlaceOrder = (settings?.placeOrderButtonText || '').trim()

const defaultLabel = __('DNA Payments', TEXT_DOMAIN)
const label = decodeEntities(settings?.title || '') || defaultLabel
const icon = settings?.icon
const icons = settings?.icons

const globalError = __(
    `Authentication failed. Please check that your credentials are correct. If you are using the Hosted Fields integration, make sure it is enabled for your account by your payment provider.`,
    TEXT_DOMAIN,
)

/**
 * Content component
 */
const RawContent = (props) => {
    return <RawHTML>{decodeEntities(settings.description || '')}</RawHTML>
}

/**
 * Content component
 */
const Content = (props) => {
    const [isLoaded, setLoaded] = useState(false)
    const [hostedFieldsInstance, setHostedFieldsInstance] = useState(null)

    usePaymentForm({ props, hostedFieldsInstance })

    useEffect(() => {
        if (!settings.temp_token) {
            props.setExpressPaymentError(globalError)
        }
    }, [])

    useEffect(() => {
        if (!isHostedFields) {
            setPlaceOrderButtonDisabled(false)
        }
    }, [])

    const isEditor = !!select('core/editor')
    // Don't render anything if we're in the editor.
    if (isEditor) {
        return null
    }

    if (isHostedFields) {
        return (
            <DnapaymentsCreditCardFields
                props={props}
                isLoaded={isLoaded}
                hostedFieldsInstance={hostedFieldsInstance}
                onLoad={(instance) => {
                    setHostedFieldsInstance(instance)
                    setLoaded(true)
                }}
            />
        )
    }

    return <RawHTML>{decodeEntities(settings.description || '')}</RawHTML>
}

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
    const { PaymentMethodLabel } = props.components
    return (
        <span style={{ width: '100%' }}>
            <PaymentMethodLabel text={label} />
            <span style={{ float: 'right', marginRight: 20, display: 'inline-flex' }}>
                {icons
                    ? icons.map((iconUrl, index) => (
                          <img key={index} src={iconUrl} style={{ marginRight: index === icons.length - 1 ? 0 : 2 }} />
                      ))
                    : icon && <img src={icon} />}
            </span>
        </span>
    )
}

/**
 * DNA Payments payment method config object.
 */
const dnapaymentsPaymentMethod = {
    name: GATEWAY_ID,
    label: <Label />,
    content: <Content />,
    edit: <RawContent />,
    canMakePayment: () => true,
    savedTokenComponent: <Content />,
    ariaLabel: label,
    supports: {
        showSavedCards: allowSavingCards && isHostedFields,
        showSaveOption: allowSavingCards && isHostedFields,
        features: settings?.supports ?? [],
    },
    placeOrderButtonLabel: customPlaceOrder || undefined,
}

registerPaymentMethod(dnapaymentsPaymentMethod)
