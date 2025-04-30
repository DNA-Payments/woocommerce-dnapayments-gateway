export class DnaPaymentsError extends Error {
    constructor(options) {
        super(options.message)

        Object.setPrototypeOf(this, DnaPaymentsError.prototype)

        this.name = 'DnaPaymentsError'
        this.code = options.code
        this.data = options.data
        this.stack = Error().stack
    }

    toJSON() {
        return {
            ...this,
            message: this.message,
        }
    }
}
