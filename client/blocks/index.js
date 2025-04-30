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

const defaultLabel = __('DNA Payments', TEXT_DOMAIN)
const label = decodeEntities(settings?.title || '') || defaultLabel
const icon = settings?.icon

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
            {icon && <img src={icon} style={{ float: 'right', marginRight: 20 }} />}
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
    placeOrderButtonLabel: __('Pay with DNA Payments', TEXT_DOMAIN),
}

registerPaymentMethod(dnapaymentsPaymentMethod)
