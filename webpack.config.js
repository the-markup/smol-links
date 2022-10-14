// See also: https://dev.to/alexstandiford/make-webpack-configuration-easy-with-wordpress-scripts-26kk
const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');
const path = require('path');

module.exports = {
	...defaultConfig,
	...{
		entry: {
			editor: path.resolve(process.cwd(), 'src', 'js', 'editor.js'),
			manager: path.resolve(process.cwd(), 'src', 'js', 'manager.js'),
			settings: path.resolve(process.cwd(), 'src', 'js', 'settings.js'),
		},
		performance: {
			hints: false,
			maxEntrypointSize: 512000,
			maxAssetSize: 512000,
		},
	},
};
