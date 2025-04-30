/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry'
import { __ } from '@wordpress/i18n'
import { getPaymentMethodData } from '@woocommerce/settings'
import { decodeEntities } from '@wordpress/html-entities'
import { RawHTML } from '@wordpress/element'

/**
 * Internal dependencies
 */
import errors from '../common/errors'
import { CONTAINER_IDS, GATEWAY_ID_APPLE_PAY, TEXT_DOMAIN } from './constants'
import { PaymentComponent } from './components/payment-component'

const settings = getPaymentMethodData(GATEWAY_ID_APPLE_PAY, {})
const defaultLabel = __('Apple Pay', TEXT_DOMAIN)
const label = decodeEntities(settings?.title || '') || defaultLabel
const icon = settings?.icon

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
 * Content component
 */
const Content = (props) => {
    return <RawHTML>{decodeEntities(settings.description || '')}</RawHTML>
}

/**
 * Apple pay button
 */
const ApplePayButton = (props) => {
    return (
        <PaymentComponent
            componentInstance={window.DNAPayments.ApplePayComponent}
            gatewayId={GATEWAY_ID_APPLE_PAY}
            containerId={CONTAINER_IDS.applepay}
            errorMessage={__(errors.APPLE_PAY_INIT_FAIL.message, TEXT_DOMAIN)}
            props={props}
        />
    )
}

/**
 */
const dnapaymentsApplePayPaymentMethod = {
    name: GATEWAY_ID_APPLE_PAY,
    label: <Label />,
    content: <ApplePayButton />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
    placeOrderButtonLabel: label,
}

registerPaymentMethod(dnapaymentsApplePayPaymentMethod)
