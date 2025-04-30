export function setLoading($elem, isLoading) {
    if (isLoading) {
        $elem.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6,
            },
        })
    } else {
        $elem.unblock()
    }
}

export const createSetLoading = ($elem) => (isLoading) => setLoading($elem, isLoading)

export function createCardError() {
    return {
        hide: () => jQuery('.dna-source-errors').empty(),
        show: (message) => jQuery('.dna-source-errors').html(wrapMessage(message)),
    }
}

export function wrapMessage(msg, isSuccess = false) {
    const className = isSuccess ? 'woocommerce-message' : 'woocommerce-error'

    if (!msg) {
        return ''
    }

    if (Array.isArray(msg)) {
        if (!msg.length) return ''
        return '<ul class="' + className + '">' + msg.map((msg) => '<li>' + msg + '</li>').join('') + '</ul>'
    }

    if (msg instanceof Error) {
        return wrapMessage(msg.message)
    }

    return msg.includes('class="' + className + '"') ? msg : '<div class="' + className + '">' + msg + '</div>'
}

export function getSelectedPaymentGateway() {
    return jQuery('input[name="payment_method"]:checked').val() || null
}

export function scrollToNotices($form) {
    let scrollElement = jQuery('.woocommerce-NoticeGroup-updateOrderReview, .woocommerce-NoticeGroup-checkout')

    if (!scrollElement.length) {
        scrollElement = $form
    }

    jQuery.scroll_to_notices(scrollElement)
}
