import path from 'path';
import webpack from 'webpack';

// environment either development or production
var MODE = 'development';

var WEBPACK_HOST = 'http://localhost';

// port number of dev server
// let dev server port be 3307
var WEBPACK_PORT = 3307;

var PUBLIC_PATH;

if (MODE === 'development') {
    PUBLIC_PATH = WEBPACK_HOST + ':' + WEBPACK_PORT + '/js/dist/';
} else {
    PUBLIC_PATH = 'js/dist/';
}

var module = {
    rules: [
        { test: /\.(js)$/, use: 'babel-loader', exclude: /node_modules/ }
    ]
};
var devServer = {
    port: WEBPACK_PORT,
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
    mode: MODE,
    entry: {
        index_new: './js/src/index.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist'),
        publicPath: PUBLIC_PATH
    },
    module: module,
    resolve: {
        extensions: ['.js']
    },
    devServer: devServer,
    plugins: plugins
}];
