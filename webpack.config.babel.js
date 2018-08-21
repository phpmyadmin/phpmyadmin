import path from 'path';
import webpack from 'webpack';
import BundleAnalyzerPlugin from 'webpack-bundle-analyzer';

function WebpackConfig (env) {
    let BundleAnalyzer = BundleAnalyzerPlugin.BundleAnalyzerPlugin;

    // environment either development or production
    var MODE;
    if (typeof env !== 'undefined'
        && env.production === true
    ) {
        MODE = 'production';
    } else {
        MODE = 'development';
    }

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
        },

    };
    var plugins = [
        new webpack.optimize.OccurrenceOrderPlugin(),
        new webpack.HotModuleReplacementPlugin(),
        new webpack.NamedModulesPlugin(),
        new webpack.NoEmitOnErrorsPlugin(),
        new webpack.ProvidePlugin({
            jQuery: 'jquery'
        }),
    ];
    if (MODE === 'development') {
        plugins.push(new BundleAnalyzer());
    }

    return {
        mode: MODE,
        entry: {
            index_new: './js/src/index.js'
        },
        output: {
            filename: '[name].js',
            path: path.resolve(__dirname, 'js/dist'),
            publicPath: PUBLIC_PATH
        },
        optimization: {
            splitChunks: {
                chunks: 'all',
                minSize: 30000,
                minChunks: 1,
                maxAsyncRequests: 5,
                maxInitialRequests: 3,
                automaticNameDelimiter: '~',
                name: true,
                cacheGroups: {
                    vendors: {
                        test: /[\\/]node_modules[\\/]/,
                        priority: -10
                    },
                    default: {
                        minChunks: 2,
                        priority: -20,
                        reuseExistingChunk: true
                    }
                }
            }
        },
        module: module,
        resolve: {
            extensions: ['.js']
        },
        devServer: devServer,
        plugins: plugins
    };
};

export default WebpackConfig;
