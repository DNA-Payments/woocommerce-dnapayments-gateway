export function logError(err, title = '') {
    console.error('CODE', err.code, 'MESSAGE', err.message)
    console.error(title, err)
}

export function logData(...args) {
    console.log(...args)
}
