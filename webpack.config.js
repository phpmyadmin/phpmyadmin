const path = require('path');

module.exports = {
    mode: 'none',
    entry: {
        'server/databases': './js/src/server/databases.js',
        'server/plugins': './js/src/server/plugins.js',
        'server/privileges': './js/src/server/privileges.js',
        'server/status/advisor': './js/src/server/status/advisor.js',
        'server/status/processes': './js/src/server/status/processes.js',
        'server/user_groups': './js/src/server/user_groups.js',
        'server/variables': './js/src/server/variables.js',
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
