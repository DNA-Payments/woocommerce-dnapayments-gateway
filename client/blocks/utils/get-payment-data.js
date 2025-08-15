import { select } from '@wordpress/data'

import { removeNonLatin1 } from '../../common/utils'
import { dnaPaymentsSettingsData } from './get-settings'

export function getPaymentData(props) {
    const { billing, shippingData } = props
    const { terminalId } = dnaPaymentsSettingsData

    const orderLines = props.cartData.cartItems.map((product) => {
        const total = getAmount(parseFloat(product.totals.line_subtotal), props)
        const quantity = product.quantity

        return {
            reference: String(product.id),
            name: removeNonLatin1(product.name),
            imageUrl: product.images?.[0]?.src ?? '',
            productUrl: product.permalink,
            quantity,
            unitPrice: Math.round(total / quantity),
            totalAmount: total,
        }
    })

    const getValueByKey = (key) => getAmount(billing.cartTotalItems.find((item) => item.key === key)?.value ?? 0, props)

    return {
        amount: getAmount(billing.cartTotal.value, props),
        currency: billing.currency.code,
        customerDetails: {
            email: billing.billingAddress.email,
            accountDetails: {
                accountId: billing.customerId ? String(billing.customerId) : undefined,
            },
            billingAddress: getAddress(billing.billingAddress),
            deliveryDetails: {
                deliveryAddress: getAddress(shippingData.shippingAddress),
            },
        },
        amountBreakdown: {
            itemTotal: { totalAmount: getValueByKey('total_items') },
            shipping: { totalAmount: getValueByKey('total_shipping') },
            taxTotal: { totalAmount: getValueByKey('total_tax') },
            discount: { totalAmount: getValueByKey('total_discount') },
        },
        orderLines,
        paymentSettings: {
            terminalId,
        },
    }
}

export function getAmount(amount, { billing }) {
    return amount / 10 ** billing.currency.minorUnit
}

export function getAddress(address) {
    return {
        firstName: address.first_name,
        lastName: address.last_name,
        addressLine1: address.address_1,
        addressLine2: address.address_2,
        city: address.city,
        postalCode: address.postcode,
        phone: address.phone,
        country: address.country,
    }
}

export function getOrderId() {
    const { CHECKOUT_STORE_KEY } = window.wc.wcBlocksData
    const store = select(CHECKOUT_STORE_KEY)

    return store.getOrderId()
}
