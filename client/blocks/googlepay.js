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
import { COMPONENT_SCRIPT_URLS, CONTAINER_IDS, GATEWAY_ID_GOOGLE_PAY, TEXT_DOMAIN } from '../common/constants'
import { PaymentComponent } from './components/payment-component'

const settings = getPaymentMethodData(GATEWAY_ID_GOOGLE_PAY, {})
const defaultLabel = __('Google Pay', TEXT_DOMAIN)
const label = decodeEntities(settings?.title || '') || defaultLabel
const icon = settings?.icon
const componentScriptUrl = COMPONENT_SCRIPT_URLS.googlePay
const componentInstance = () => window.DNAPayments?.GooglePayComponent

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
 * Google pay button
 */
const GooglePayButton = (props) => {
    return (
        <PaymentComponent
            componentInstance={componentInstance}
            componentScriptUrl={componentScriptUrl}
            gatewayId={GATEWAY_ID_GOOGLE_PAY}
            containerId={CONTAINER_IDS.googlepay}
            errorMessage={__(errors.GOOGLE_PAY_INIT_FAIL.message, TEXT_DOMAIN)}
            props={props}
        />
    )
}

/**
 */
const dnapaymentsGooglePayPaymentMethod = {
    name: GATEWAY_ID_GOOGLE_PAY,
    label: <Label />,
    content: <GooglePayButton />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
    placeOrderButtonLabel: label,
}

registerPaymentMethod(dnapaymentsGooglePayPaymentMethod)
