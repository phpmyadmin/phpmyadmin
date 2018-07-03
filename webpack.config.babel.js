import path from 'path';
import webpack from 'webpack';
import BundleAnalyzerPlugin from 'webpack-bundle-analyzer';
// let dev server port be 3307
let plugin = BundleAnalyzerPlugin.BundleAnalyzerPlugin;

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
    new plugin()
];
var isProd = false;

export default [{
    // envionment either development or production
    mode: mode,
    entry: {
        index_new: './js/src/index.js',
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist'),
        // url on which dev server will run
        publicPath: isProd ? 'js/dist/' : 'http://localhost:3307/js/dist/'
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
    // devtool: 'source-map',
    resolve: {
        extensions: ['.js']
    },
    devServer: devServer,
    plugins: plugins
}];
