import { tryParse } from './try-parse'

export function removeNonLatin1(str) {
    // Keep only Latin-1 (ISO-8859-1) characters: 0x00–0xFF
    // This removes emojis, smart quotes, special Unicode symbols, etc.
    return str?.replace(/[^\x00-\xFF]/g, '').trim() || ''
}

export function addGatewayId(merchantCustomData, gatewayId) {
    const customData = tryParse(merchantCustomData) || {}
    customData.gatewayId = gatewayId
    return JSON.stringify(customData)
}
