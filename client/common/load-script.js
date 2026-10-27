const loadedScripts = {}
const timeoutMs = 15000

export const loadScript = (src, isReady = () => false) => {
    if (!src) {
        return Promise.resolve()
    }

    if (loadedScripts[src]) {
        return loadedScripts[src]
    }

    const existingScript = document.querySelector(`script[src^="${src}"]`)
    if (existingScript) {
        loadedScripts[src] = new Promise((resolve, reject) => {
            if (existingScript.dataset.dnaLoaded === '1' || existingScript.readyState === 'complete' || isReady()) {
                resolve()
                return
            }

            const timeout = setTimeout(() => reject(new Error(`Script load timeout: ${src}`)), timeoutMs)
            const onLoad = () => {
                clearTimeout(timeout)
                existingScript.dataset.dnaLoaded = '1'
                resolve()
            }
            const onError = () => {
                clearTimeout(timeout)
                reject(new Error(`Script load failed: ${src}`))
            }

            existingScript.addEventListener('load', onLoad, { once: true })
            existingScript.addEventListener('error', onError, { once: true })
        })

        return loadedScripts[src]
    }

    loadedScripts[src] = new Promise((resolve, reject) => {
        const script = document.createElement('script')
        const timeout = setTimeout(() => reject(new Error(`Script load timeout: ${src}`)), timeoutMs)
        script.src = src
        script.async = true
        script.onload = () => {
            clearTimeout(timeout)
            script.dataset.dnaLoaded = '1'
            resolve()
        }
        script.onerror = () => {
            clearTimeout(timeout)
            reject(new Error(`Script load failed: ${src}`))
        }
        document.head.appendChild(script)
    })

    return loadedScripts[src]
}
