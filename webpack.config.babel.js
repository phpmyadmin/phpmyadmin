import path from 'path';
import webpack from 'webpack';

// let dev server port be 3307

var mode = 'development';
var module = {
    rules: [
        { test: /\.(js)$/, use: 'babel-loader', exclude: /node_modules/ }
    ]
};
var devServer = {
    // port number of dev server
    port: 3307,
    hot: false,
    headers: {
        'Access-Control-Allow-Origin': '*'
    }
};
var plugins = [
    new webpack.optimize.OccurrenceOrderPlugin(),
    new webpack.HotModuleReplacementPlugin(),
    new webpack.NamedModulesPlugin(),
    new webpack.NoEmitOnErrorsPlugin()
];

export default [{
    // envionment either development or production
    mode: mode,
    entry: {
        db_search_new: './js/src/db_search.js'
    },
    output: {
        filename: 'js/sql.js',
        path: path.resolve(__dirname, 'dist'),
        publicPath: 'http://localhost:3007/dist'
    },
    module: module,
    // devtool: 'source-map',
    resolve: {
        extensions: ['.js']
    },
    devServer: devServer,
    plugins: plugins
}];
