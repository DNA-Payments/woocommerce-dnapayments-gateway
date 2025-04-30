const path = require('path')

module.exports = (env, argv) => {
    return {
        mode: argv.watch ? 'development' : 'production',
        entry: {
            'dna-payments': path.resolve(__dirname, 'dna-payments.js'),
            'dna-payments-add-card': path.resolve(__dirname, 'dna-payments-add-card.js'),
        },
        output: {
            path: path.resolve(__dirname, '..', '..', 'assets', 'js', 'classic'),
            filename: '[name].js',
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            presets: [
                                [
                                    '@babel/preset-env',
                                    {
                                        useBuiltIns: 'usage',
                                        corejs: 3,
                                    },
                                ],
                            ],
                        },
                    },
                },
            ],
        },
        resolve: {
            extensions: ['.js'],
        },
        watchOptions: {
            ignored: /node_modules/, // Ignore unnecessary files
            aggregateTimeout: 300, // Delay rebuild after the first change
            poll: 1000, // Check for changes every second (useful in some environments)
        },
        watch: argv.watch || false,
    }
}
