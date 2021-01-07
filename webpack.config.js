const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const config = {
    ...defaultConfig,
    entry: {
        'ss-gutenberg-test': './js/gutenberg/ss-gutenberg-test.js',
    },
}

module.exports = config;
