const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    entry: {
        'ss-gutenberg': './js/gutenberg/ss-gutenberg.js',
    },
}

module.exports = config;
