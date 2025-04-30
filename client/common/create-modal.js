export function createModal(id) {
    let modalContainer = document.getElementById(id)

    if (modalContainer) {
        return {
            show: () => modalContainer.classList.add('open'),
            hide: () => modalContainer.classList.remove('open'),
            remove: () => modalContainer.remove(),
            body: modalContainer.querySelector('.dna-modal-body'),
        }
    }

    // Create modal elements
    modalContainer = document.createElement('div')
    modalContainer.className = 'dna-modal-container'
    modalContainer.id = id

    const modal = document.createElement('div')
    modal.className = 'dna-modal'

    const modalBody = document.createElement('div')
    modalBody.className = 'dna-modal-body'

    // Append elements
    modal.appendChild(modalBody)
    modalContainer.appendChild(modal)
    document.body.appendChild(modalContainer)

    return {
        show: () => modalContainer.classList.add('open'),
        hide: () => modalContainer.classList.remove('open'),
        remove: () => modalContainer.remove(),
        body: modalBody,
    }
}
