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
import { COMPONENT_SCRIPT_URLS, CONTAINER_IDS, GATEWAY_ID_ALIPAY, TEXT_DOMAIN } from '../common/constants'
import { PaymentComponent } from './components/payment-component'

const settings = getPaymentMethodData(GATEWAY_ID_ALIPAY, {})
const defaultLabel = __('Alipay', TEXT_DOMAIN)
const label = decodeEntities(settings?.title || '') || defaultLabel
const icon = settings?.icon
const componentScriptUrl = COMPONENT_SCRIPT_URLS.alipayWechatPay
const componentInstance = () => window.DNAPayments?.AlipayComponent

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
const Content = () => <RawHTML>{decodeEntities(settings.description || '')}</RawHTML>

/**
 * Alipay button
 */
const AlipayButton = (props) => {
    return (
        <PaymentComponent
            componentInstance={componentInstance}
            componentScriptUrl={componentScriptUrl}
            gatewayId={GATEWAY_ID_ALIPAY}
            containerId={CONTAINER_IDS.alipay}
            errorMessage={__(errors.ALIPAY_INIT_FAIL.message, TEXT_DOMAIN)}
            props={props}
        />
    )
}

registerPaymentMethod({
    name: GATEWAY_ID_ALIPAY,
    label: <Label />,
    content: <AlipayButton />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
    placeOrderButtonLabel: label,
})
