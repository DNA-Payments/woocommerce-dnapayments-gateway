const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/blocks-checkout': ['wc', 'blocksCheckout'],
	'@woocommerce/settings'       : ['wc', 'wcSettings'],
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
	'@woocommerce/settings'       : 'wc-settings',
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

module.exports = {
	...defaultConfig,
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		}),
	],
	entry: {
		'dnapayments': path.resolve(process.cwd(), 'client', 'blocks', 'index.js'),
		'dnapayments_googlepay': path.resolve(process.cwd(), 'client', 'blocks', 'googlepay.js'),
		'dnapayments_applepay': path.resolve(process.cwd(), 'client', 'blocks', 'applepay.js'),
		'dnapayments_paypal': path.resolve(process.cwd(), 'client', 'blocks', 'paypal.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(process.cwd(), 'assets/js/blocks'),
	},
};
