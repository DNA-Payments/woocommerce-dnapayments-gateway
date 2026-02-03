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
import { CONTAINER_IDS, GATEWAY_ID_ALIPAY_PLUS, TEXT_DOMAIN } from '../common/constants'
import { PaymentComponent } from './components/payment-component'

const settings = getPaymentMethodData(GATEWAY_ID_ALIPAY_PLUS, {})
const defaultLabel = __('Alipay+', TEXT_DOMAIN)
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
const Content = () => <RawHTML>{decodeEntities(settings.description || '')}</RawHTML>

/**
 * Alipay Plus button
 */
const AlipayPlusButton = (props) => {
    return (
        <PaymentComponent
            componentInstance={window.DNAPayments.AlipayPlusComponent}
            gatewayId={GATEWAY_ID_ALIPAY_PLUS}
            containerId={CONTAINER_IDS.alipayplus}
            errorMessage={__(errors.ALIPAY_PLUS_INIT_FAIL.message, TEXT_DOMAIN)}
            props={props}
        />
    )
}

registerPaymentMethod({
    name: GATEWAY_ID_ALIPAY_PLUS,
    label: <Label />,
    content: <AlipayPlusButton />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
    placeOrderButtonLabel: label,
})
