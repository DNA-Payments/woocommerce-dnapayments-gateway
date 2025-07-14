export function normalizeCardSchemeName(cardScheme) {
    if (!cardScheme) return null

    const normalized = cardScheme.toLowerCase().trim()

    switch (normalized) {
        case 'amex':
        case 'amexcard':
        case 'americanexpress':
        case 'american express':
        case 'american-express':
            return 'amex'

        case 'dci':
        case 'diners':
        case 'dinersclub':
        case 'diners club':
        case 'diners-club':
            return 'diners'

        case 'mc':
        case 'mastercard':
        case 'master card':
        case 'master-card':
            return 'mastercard'

        case 'upi':
        case 'unionpay':
        case 'union pay':
            return 'unionpay'

        case 'visa':
        case 'visacard':
        case 'visa card':
        case 'visa-card':
            return 'visa'

        case 'maestro':
        case 'maestrocard':
        case 'maestro card':
        case 'maestro-card':
            return 'maestro'

        case 'discover':
        case 'discovercard':
        case 'discover card':
        case 'discover-card':
            return 'discover'

        default:
            return normalized
    }
}
