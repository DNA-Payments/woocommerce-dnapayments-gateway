export function debounce(func, delay = 300) {
    let timeoutId = null

    return (...args) => {
        clearTimeout(timeoutId)
        timeoutId = setTimeout(() => func(...args), delay)
    }
}

export function debounceAsync(func, delay = 300) {
    let timeoutId = null
    let lastPromiseReject = null

    return (...args) => {
        // Reject the previous promise if a new call is made
        if (lastPromiseReject) {
            lastPromiseReject(new Error('Debounced function call cancelled.'))
        }

        return new Promise((resolve, reject) => {
            lastPromiseReject = reject // Store the reject function to handle cancellation
            clearTimeout(timeoutId)

            timeoutId = setTimeout(async () => {
                try {
                    const result = await Promise.resolve(func(...args))
                    resolve(result)
                } catch (error) {
                    reject(error)
                }
            }, delay)
        })
    }
}
