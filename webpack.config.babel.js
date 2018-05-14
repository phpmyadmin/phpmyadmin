import path from 'path';
import webpack from 'webpack';

// let dev server port be 3307


/*'./js/es6/chart.js',
    './js/es6/common.js',
    './js/es6/config.js',
    './js/es6/console.js',
    './js/es6/cross_framing_protection.js',
    './js/es6/db_central_columns.js',
    './js/es6/db_multi_table_query',
    './js/es6/db_operations.js',
    './js/es6/db_qbe.js',
    './js/es6/db_search.js',
    './js/es6/db_structure.js',
    './js/es6/db_tracking.js',
    './js/es6/doclinks.js',
    './js/es6/error_reporting.js',
    './js/es6/export_output.js',
    './js/es6/.js',
    './js/es6/ajax.js',
    './js/es6/ajax.js',
    './js/es6/ajax.js',
*/
export default {
    entry: [
        './js/es6/sql.js',
    ],
    output: {
        filename: 'js/sql.js',
        path: path.resolve(__dirname, 'dist'),
        publicPath: 'http://localhost:3007/dist'
    },
    module: {
        rules: [
            { test: /\.(js)$/, use: 'babel-loader', exclude: /node_modules/ },
        ],
    },
    devtool: 'source-map',
    resolve: {
        extensions: ['.js'],
    },
    devServer: {
        port: 3307,
        hot: true,
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
    plugins: [
        new webpack.optimize.OccurrenceOrderPlugin(),
        new webpack.HotModuleReplacementPlugin(),
        new webpack.NamedModulesPlugin(),
        new webpack.NoEmitOnErrorsPlugin(),
    ],
}