export function getQueryParam(name, shouldDelete) {
    const url = new URL(window.location.href)
    const value = url.searchParams.get(name)

    if (shouldDelete && value !== null) {
        url.searchParams.delete(name)
        history.replaceState(null, '', url.toString())
    }

    return value
}
