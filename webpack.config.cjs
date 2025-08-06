const path = require('path');
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
            'codemirror/addon/lint/sql-lint': rootPath + '/resources/js/codemirror/addon/lint/sql-lint.ts',
            'console': { import: rootPath + '/resources/js/console.ts', library: { name: 'Console', type: 'window', export: 'Console' } },
            'datetimepicker': rootPath + '/resources/js/datetimepicker.ts',
            'database/central_columns': rootPath + '/resources/js/database/central_columns.ts',
            'database/events': rootPath + '/resources/js/database/events.ts',
            'database/multi_table_query': rootPath + '/resources/js/database/multi_table_query.ts',
            'database/operations': rootPath + '/resources/js/database/operations.ts',
            'database/query_generator': rootPath + '/resources/js/database/query_generator.ts',
            'database/routines': rootPath + '/resources/js/database/routines.ts',
            'database/search': rootPath + '/resources/js/database/search.ts',
            'database/structure': rootPath + '/resources/js/database/structure.ts',
            'database/tracking': rootPath + '/resources/js/database/tracking.ts',
            'designer/init': rootPath + '/resources/js/designer/init.ts',
            'drag_drop_import': rootPath + '/resources/js/drag_drop_import.ts',
            'error_report': rootPath + '/resources/js/error_report.ts',
            'export': rootPath + '/resources/js/export.ts',
            'export_output': rootPath + '/resources/js/export_output.ts',
            'gis_data_editor': rootPath + '/resources/js/gis_data_editor.ts',
            'home': rootPath + '/resources/js/home.ts',
            'import': rootPath + '/resources/js/import.ts',
            'jquery.sortable-table': rootPath + '/resources/js/jquery.sortable-table.ts',
            'main': rootPath + '/resources/js/main.ts',
            'makegrid': rootPath + '/resources/js/makegrid.ts',
            'menu_resizer': rootPath + '/resources/js/menu_resizer.ts',
            'multi_column_sort': rootPath + '/resources/js/multi_column_sort.ts',
            'normalization': rootPath + '/resources/js/normalization.ts',
            'replication': rootPath + '/resources/js/replication.ts',
            'server/databases': rootPath + '/resources/js/server/databases.ts',
            'server/plugins': rootPath + '/resources/js/server/plugins.ts',
            'server/privileges': rootPath + '/resources/js/server/privileges.ts',
            'server/status/monitor': rootPath + '/resources/js/server/status/monitor.ts',
            'server/status/processes': rootPath + '/resources/js/server/status/processes.ts',
            'server/status/queries': rootPath + '/resources/js/server/status/queries.ts',
            'server/status/variables': rootPath + '/resources/js/server/status/variables.ts',
            'server/user_groups': rootPath + '/resources/js/server/user_groups.ts',
            'server/variables': rootPath + '/resources/js/server/variables.ts',
            'setup/scripts': rootPath + '/resources/js/setup/scripts.ts',
            'shortcuts_handler': rootPath + '/resources/js/shortcuts_handler.ts',
            'sql': rootPath + '/resources/js/sql.ts',
            'table/change': rootPath + '/resources/js/table/change.ts',
            'table/chart': rootPath + '/resources/js/table/chart.ts',
            'table/find_replace': rootPath + '/resources/js/table/find_replace.ts',
            'table/gis_visualization': rootPath + '/resources/js/table/gis_visualization.ts',
            'table/operations': rootPath + '/resources/js/table/operations.ts',
            'table/relation': rootPath + '/resources/js/table/relation.ts',
            'table/select': rootPath + '/resources/js/table/select.ts',
            'table/structure': rootPath + '/resources/js/table/structure.ts',
            'table/tracking': rootPath + '/resources/js/table/tracking.ts',
            'table/zoom_search': rootPath + '/resources/js/table/zoom_search.ts',
            'transformations/image_upload': rootPath + '/resources/js/transformations/image_upload.ts',
            'transformations/json': rootPath + '/resources/js/transformations/json.ts',
            'transformations/json_editor': rootPath + '/resources/js/transformations/json_editor.ts',
            'transformations/sql_editor': rootPath + '/resources/js/transformations/sql_editor.ts',
            'transformations/xml': rootPath + '/resources/js/transformations/xml.ts',
            'transformations/xml_editor': rootPath + '/resources/js/transformations/xml_editor.ts',
            'triggers': rootPath + '/resources/js/triggers.ts',
            'u2f': rootPath + '/resources/js/u2f.ts',
            'validator-messages': rootPath + '/resources/js/validator-messages.ts',
            'webauthn': rootPath + '/resources/js/webauthn.ts',
        },
        output: {
            filename: '[name].js',
            path: publicPath + '/js',
        },
        optimization: {
            chunkIds: 'named',
            moduleIds: 'named',
            runtimeChunk: 'single',
            splitChunks: {
                cacheGroups: {
                    shared: { name: 'shared', chunks: 'all', minChunks: 2, minSize: 1 },
                    bootstrap: {
                        priority: 10,
                        test: /[\\/]node_modules[\\/](bootstrap|@popperjs)[\\/]/,
                        name: 'bootstrap',
                        filename: 'vendor/[name]/[name].js',
                        chunks: 'all',
                        enforce: true,
                    },
                    openLayers: {
                        priority: 10,
                        test: /[\\/]node_modules[\\/](ol|rbush)[\\/]/,
                        name: 'openlayers',
                        filename: 'vendor/[name]/[name].js',
                        chunks: 'all',
                        enforce: true,
                    },
                },
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
                    { from: rootPath + '/node_modules/jquery-validation/dist/additional-methods.min.js', to: publicPath + '/js/vendor/jquery/additional-methods.min.js' },
                    { from: rootPath + '/node_modules/js-cookie/dist/js.cookie.min.js', to: publicPath + '/js/vendor/js.cookie.min.js' },
                    { from: rootPath + '/node_modules/@zxcvbn-ts/core/dist/zxcvbn-ts.js', to: publicPath + '/js/vendor/zxcvbn-ts.js' },
                    { from: rootPath + '/node_modules/@zxcvbn-ts/core/dist/zxcvbn-ts.js.map', to: publicPath + '/js/vendor/zxcvbn-ts.js.map' },
                    { from: rootPath + '/node_modules/tracekit/tracekit.js', to: publicPath + '/js/vendor/tracekit.js' },
                    { from: rootPath + '/node_modules/u2f-api-polyfill/u2f-api-polyfill.js', to: publicPath + '/js/vendor/u2f-api-polyfill.js' },
                    { from: rootPath + '/node_modules/jquery-uitablefilter/jquery.uitablefilter.js', to: publicPath + '/js/vendor/jquery/jquery.uitablefilter.js' },
                    { from: rootPath + '/node_modules/tablesorter/dist/js/jquery.tablesorter.js', to: publicPath + '/js/vendor/jquery/jquery.tablesorter.js' },
                    { from: rootPath + '/node_modules/jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon.min.js', to: publicPath + '/js/vendor/jquery/jquery-ui-timepicker-addon.min.js' },
                    { from: rootPath + '/node_modules/ol/ol.css', to: publicPath + '/js/vendor/openlayers/openlayers.css' },
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
