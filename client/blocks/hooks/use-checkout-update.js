import { useEffect } from '@wordpress/element'

export function useCheckoutUpdate(onUpdate) {
    useEffect(() => {
        const target = document.querySelector('.wc-block-checkout__form')

        if (!target) return

        const observer = new MutationObserver((mutationsList) => {
            onUpdate && onUpdate(mutationsList)
        })

        observer.observe(target, {
            childList: true, // Watch for added/removed child elements
            // attributes: true, // Watch for attribute changes
            subtree: true, // Watch all descendants
        })

        return () => observer.disconnect() // Clean up when component unmounts
    }, [])
}
