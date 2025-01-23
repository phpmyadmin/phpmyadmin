const path = require('path');
const webpack = require('webpack');
const autoprefixer = require('autoprefixer');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RtlCssPlugin = require('rtlcss-webpack-plugin');

const rootPath = path.resolve(__dirname, '');
const publicPath = path.resolve(__dirname, 'public');

const typeScriptErrorsToIgnore = [
    5096, // TS5096: Option 'allowImportingTsExtensions' can only be used when either 'noEmit' or 'emitDeclarationOnly' is set.
];

module.exports = [
    {
        mode: 'none',
        devtool: 'source-map',
        entry: {
            'codemirror/addon/lint/sql-lint': rootPath + '/resources/js/src/codemirror/addon/lint/sql-lint.ts',
            'console': { import: rootPath + '/resources/js/src/console.ts', library: { name: 'Console', type: 'window', export: 'Console' } },
            'datetimepicker': rootPath + '/resources/js/src/datetimepicker.ts',
            'database/central_columns': rootPath + '/resources/js/src/database/central_columns.ts',
            'database/events': rootPath + '/resources/js/src/database/events.ts',
            'database/multi_table_query': rootPath + '/resources/js/src/database/multi_table_query.ts',
            'database/operations': rootPath + '/resources/js/src/database/operations.ts',
            'database/query_generator': rootPath + '/resources/js/src/database/query_generator.ts',
            'database/routines': rootPath + '/resources/js/src/database/routines.ts',
            'database/search': rootPath + '/resources/js/src/database/search.ts',
            'database/structure': rootPath + '/resources/js/src/database/structure.ts',
            'database/tracking': rootPath + '/resources/js/src/database/tracking.ts',
            'designer/init': rootPath + '/resources/js/src/designer/init.ts',
            'drag_drop_import': rootPath + '/resources/js/src/drag_drop_import.ts',
            'error_report': rootPath + '/resources/js/src/error_report.ts',
            'export': rootPath + '/resources/js/src/export.ts',
            'export_output': rootPath + '/resources/js/src/export_output.ts',
            'gis_data_editor': rootPath + '/resources/js/src/gis_data_editor.ts',
            'home': rootPath + '/resources/js/src/home.ts',
            'import': rootPath + '/resources/js/src/import.ts',
            'jquery.sortable-table': rootPath + '/resources/js/src/jquery.sortable-table.ts',
            'main': rootPath + '/resources/js/src/main.ts',
            'makegrid': rootPath + '/resources/js/src/makegrid.ts',
            'menu_resizer': rootPath + '/resources/js/src/menu_resizer.ts',
            'multi_column_sort': rootPath + '/resources/js/src/multi_column_sort.ts',
            'normalization': rootPath + '/resources/js/src/normalization.ts',
            'replication': rootPath + '/resources/js/src/replication.ts',
            'server/databases': rootPath + '/resources/js/src/server/databases.ts',
            'server/plugins': rootPath + '/resources/js/src/server/plugins.ts',
            'server/privileges': rootPath + '/resources/js/src/server/privileges.ts',
            'server/status/monitor': rootPath + '/resources/js/src/server/status/monitor.ts',
            'server/status/processes': rootPath + '/resources/js/src/server/status/processes.ts',
            'server/status/queries': rootPath + '/resources/js/src/server/status/queries.ts',
            'server/status/variables': rootPath + '/resources/js/src/server/status/variables.ts',
            'server/user_groups': rootPath + '/resources/js/src/server/user_groups.ts',
            'server/variables': rootPath + '/resources/js/src/server/variables.ts',
            'setup/scripts': rootPath + '/resources/js/src/setup/scripts.ts',
            'shortcuts_handler': rootPath + '/resources/js/src/shortcuts_handler.ts',
            'sql': rootPath + '/resources/js/src/sql.ts',
            'table/change': rootPath + '/resources/js/src/table/change.ts',
            'table/chart': rootPath + '/resources/js/src/table/chart.ts',
            'table/find_replace': rootPath + '/resources/js/src/table/find_replace.ts',
            'table/gis_visualization': rootPath + '/resources/js/src/table/gis_visualization.ts',
            'table/operations': rootPath + '/resources/js/src/table/operations.ts',
            'table/relation': rootPath + '/resources/js/src/table/relation.ts',
            'table/select': rootPath + '/resources/js/src/table/select.ts',
            'table/structure': rootPath + '/resources/js/src/table/structure.ts',
            'table/tracking': rootPath + '/resources/js/src/table/tracking.ts',
            'table/zoom_search': rootPath + '/resources/js/src/table/zoom_search.ts',
            'transformations/image_upload': rootPath + '/resources/js/src/transformations/image_upload.ts',
            'transformations/json': rootPath + '/resources/js/src/transformations/json.ts',
            'transformations/json_editor': rootPath + '/resources/js/src/transformations/json_editor.ts',
            'transformations/sql_editor': rootPath + '/resources/js/src/transformations/sql_editor.ts',
            'transformations/xml': rootPath + '/resources/js/src/transformations/xml.ts',
            'transformations/xml_editor': rootPath + '/resources/js/src/transformations/xml_editor.ts',
            'triggers': rootPath + '/resources/js/src/triggers.ts',
            'u2f': rootPath + '/resources/js/src/u2f.ts',
            'validator-messages': rootPath + '/resources/js/src/validator-messages.ts',
            'webauthn': rootPath + '/resources/js/src/webauthn.ts',
        },
        output: {
            filename: '[name].js',
            path: publicPath + '/js/dist',
        },
        optimization: {
            runtimeChunk: 'single',
            splitChunks: {
                chunks: 'all',
                name: 'shared',
                minSize: 1,
            },
        },
        externals: {
            jquery: 'jQuery',
            codemirror: 'CodeMirror',
        },
        module: {
            rules: [
                {
                    test: /\.ts$/,
                    exclude: /node_modules/,
                    use: [
                        { loader: 'babel-loader', options: { presets:  ['@babel/preset-env'] } },
                        { loader: 'ts-loader', options: { ignoreDiagnostics: typeScriptErrorsToIgnore } },
                    ],
                },
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: { loader: 'babel-loader', options: { presets:  ['@babel/preset-env'] } },
                },
            ],
        },
        plugins: [
            new CopyPlugin({
                patterns: [
                    { from: rootPath + '/node_modules/codemirror/addon/hint/sql-hint.js', to: publicPath + '/js/vendor/codemirror/addon/hint/sql-hint.js' },
                    { from: rootPath + '/node_modules/codemirror/addon/hint/show-hint.css', to: publicPath + '/js/vendor/codemirror/addon/hint/show-hint.css' },
                    { from: rootPath + '/node_modules/codemirror/addon/hint/show-hint.js', to: publicPath + '/js/vendor/codemirror/addon/hint/show-hint.js' },
                    { from: rootPath + '/node_modules/codemirror/addon/runmode/runmode.js', to: publicPath + '/js/vendor/codemirror/addon/runmode/runmode.js' },
                    { from: rootPath + '/node_modules/codemirror/addon/lint/lint.css', to: publicPath + '/js/vendor/codemirror/addon/lint/lint.css' },
                    { from: rootPath + '/node_modules/codemirror/addon/lint/lint.js', to: publicPath + '/js/vendor/codemirror/addon/lint/lint.js' },
                    { from: rootPath + '/node_modules/codemirror/lib/codemirror.js', to: publicPath + '/js/vendor/codemirror/lib/codemirror.js' },
                    { from: rootPath + '/node_modules/codemirror/lib/codemirror.css', to: publicPath + '/js/vendor/codemirror/lib/codemirror.css' },
                    { from: rootPath + '/node_modules/codemirror/mode/sql/sql.js', to: publicPath + '/js/vendor/codemirror/mode/sql/sql.js' },
                    { from: rootPath + '/node_modules/codemirror/mode/javascript/javascript.js', to: publicPath + '/js/vendor/codemirror/mode/javascript/javascript.js' },
                    { from: rootPath + '/node_modules/codemirror/mode/xml/xml.js', to: publicPath + '/js/vendor/codemirror/mode/xml/xml.js' },
                    { from: rootPath + '/node_modules/codemirror/LICENSE', to: publicPath + '/js/vendor/codemirror/LICENSE', toType: 'file' },
                    { from: rootPath + '/node_modules/jquery/dist/jquery.min.js', to: publicPath + '/js/vendor/jquery/jquery.min.js' },
                    { from: rootPath + '/node_modules/jquery/dist/jquery.min.map', to: publicPath + '/js/vendor/jquery/jquery.min.map' },
                    { from: rootPath + '/node_modules/jquery/LICENSE.txt', to: publicPath + '/js/vendor/jquery/MIT-LICENSE.txt' },
                    { from: rootPath + '/node_modules/jquery-migrate/dist/jquery-migrate.min.js', to: publicPath + '/js/vendor/jquery/jquery-migrate.min.js' },
                    { from: rootPath + '/node_modules/jquery-migrate/dist/jquery-migrate.min.map', to: publicPath + '/js/vendor/jquery/jquery-migrate.min.map' },
                    { from: rootPath + '/node_modules/jquery-ui-dist/jquery-ui.min.js', to: publicPath + '/js/vendor/jquery/jquery-ui.min.js' },
                    { from: rootPath + '/node_modules/jquery-validation/dist/jquery.validate.min.js', to: publicPath + '/js/vendor/jquery/jquery.validate.min.js' },
                    { from: rootPath + '/node_modules/jquery-validation/dist/additional-methods.js', to: publicPath + '/js/vendor/jquery/additional-methods.js' },
                    { from: rootPath + '/node_modules/js-cookie/dist/js.cookie.min.js', to: publicPath + '/js/vendor/js.cookie.min.js' },
                    { from: rootPath + '/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js', to: publicPath + '/js/vendor/bootstrap/bootstrap.bundle.min.js' },
                    { from: rootPath + '/node_modules/bootstrap/dist/js/bootstrap.bundle.min.js.map', to: publicPath + '/js/vendor/bootstrap/bootstrap.bundle.min.js.map' },
                    { from: rootPath + '/node_modules/@zxcvbn-ts/core/dist/zxcvbn-ts.js', to: publicPath + '/js/vendor/zxcvbn-ts.js' },
                    { from: rootPath + '/node_modules/@zxcvbn-ts/core/dist/zxcvbn-ts.js.map', to: publicPath + '/js/vendor/zxcvbn-ts.js.map' },
                    { from: rootPath + '/node_modules/tracekit/tracekit.js', to: publicPath + '/js/vendor/tracekit.js' },
                    { from: rootPath + '/node_modules/u2f-api-polyfill/u2f-api-polyfill.js', to: publicPath + '/js/vendor/u2f-api-polyfill.js' },
                    { from: rootPath + '/node_modules/jquery-uitablefilter/jquery.uitablefilter.js', to: publicPath + '/js/vendor/jquery/jquery.uitablefilter.js' },
                    { from: rootPath + '/node_modules/tablesorter/dist/js/jquery.tablesorter.js', to: publicPath + '/js/vendor/jquery/jquery.tablesorter.js' },
                    { from: rootPath + '/node_modules/jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon.js', to: publicPath + '/js/vendor/jquery/jquery-ui-timepicker-addon.js' },
                    { from: rootPath + '/node_modules/ol/ol.css', to: publicPath + '/js/vendor/openlayers/theme/ol.css' },
                    { from: rootPath + '/node_modules/locutus.sprintf/src/php/strings/sprintf.browser.js', to: publicPath + '/js/vendor/sprintf.js' },
                    { from: rootPath + '/node_modules/chart.js/dist/chart.umd.js', to: publicPath + '/js/vendor/chart.umd.js' },
                    { from: rootPath + '/node_modules/chart.js/dist/chart.umd.js.map', to: publicPath + '/js/vendor/chart.umd.js.map' },
                    { from: rootPath + '/node_modules/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.js', to: publicPath + '/js/vendor/chartjs-adapter-date-fns.bundle.js' },
                    { from: rootPath + '/node_modules/chartjs-plugin-zoom/dist/chartjs-plugin-zoom.js', to: publicPath + '/js/vendor/chartjs-plugin-zoom.js' },
                    { from: rootPath + '/node_modules/hammerjs/hammer.js', to: publicPath + '/js/vendor/hammer.js' },
                ],
            }),
        ],
    },
    {
        name: 'OpenLayers',
        entry: rootPath + '/resources/js/src/ol.mjs',
        devtool: 'source-map',
        mode: 'production',
        performance: {
            hints: false,
            maxEntrypointSize: 512000,
            maxAssetSize: 512000,
        },
        output: {
            path: publicPath + '/js/vendor/openlayers',
            filename: 'OpenLayers.js',
            library: 'ol',
            libraryTarget: 'umd',
            libraryExport: 'default',
        },
        plugins: [
            new webpack.BannerPlugin({
                banner: 'OpenLayers (https://openlayers.org/)\nCopyright 2005-present, OpenLayers Contributors All rights reserved.\nLicensed under BSD 2-Clause License (https://github.com/openlayers/openlayers/blob/main/LICENSE.md)',
            }),
        ],
        optimization: {
            minimize: false,
        }
    },
    {
        name: 'CSS',
        mode: 'none',
        devtool: 'source-map',
        entry: {
            'public/themes/bootstrap/css/theme': publicPath + '/themes/bootstrap/scss/theme.scss',
            'public/themes/metro/css/theme': publicPath + '/themes/metro/scss/theme.scss',
            'public/themes/original/css/theme': publicPath + '/themes/original/scss/theme.scss',
            'public/themes/pmahomme/css/theme': publicPath + '/themes/pmahomme/scss/theme.scss',
            'public/setup/styles': publicPath + '/setup/scss/styles.scss',
        },
        output: {
            filename: 'build/css/[name].js',
            path: rootPath,
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                            options: {
                                url: false,
                            },
                        },
                        {
                            loader: 'postcss-loader',
                            options: {
                                postcssOptions: {
                                    plugins: [ autoprefixer() ],
                                },
                            },
                        },
                        'sass-loader',
                    ],
                },
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: '[name].css',
                chunkFilename: '[id].css',
            }),
            new RtlCssPlugin({
                filename: '[name].rtl.css',
            }),
        ],
    },
];
