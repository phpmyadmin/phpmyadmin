const path = require('path');

module.exports = {
    mode: 'none',
    entry: {
        'server/databases': './js/src/server/databases.js',
        'server/plugins': './js/src/server/plugins.js',
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist'),
    },
    externals: {
        jquery: 'jQuery',
        ajax: 'AJAX',
        commonActions: 'CommonActions',
        commonParams: 'CommonParams',
        functions: 'Functions',
        messages: 'Messages',
        microHistory: 'MicroHistory',
        navigation: 'Navigation',
    }
};
