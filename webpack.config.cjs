const path = require('path');
const webpack = require('webpack');
const autoprefixer = require('autoprefixer');
const CopyPlugin = require('copy-webpack-plugin');
const WebpackConcatPlugin = require('webpack-concat-files-plugin');
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
            'chart': rootPath + '/resources/js/src/chart.ts',
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
            'jqplot/plugins/jqplot.byteFormatter': rootPath + '/resources/js/src/jqplot/plugins/jqplot.byteFormatter.ts',
            'jquery.sortable-table': rootPath + '/resources/js/src/jquery.sortable-table.ts',
            'main': rootPath + '/resources/js/src/main.ts',
            'makegrid': rootPath + '/resources/js/src/makegrid.ts',
            'menu_resizer': rootPath + '/resources/js/src/menu_resizer.ts',
            'multi_column_sort': rootPath + '/resources/js/src/multi_column_sort.ts',
            'name-conflict-fixes': rootPath + '/resources/js/src/name-conflict-fixes.ts',
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
            'table/zoom_plot_jqplot': rootPath + '/resources/js/src/table/zoom_plot_jqplot.ts',
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
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.pieRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.pieRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.barRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.barRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.pointLabels.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.pointLabels.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.enhancedPieLegendRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.enhancedPieLegendRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.dateAxisRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.dateAxisRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.categoryAxisRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.categoryAxisRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.canvasTextRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.canvasTextRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.canvasAxisLabelRenderer.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.cursor.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.cursor.js' },
                    { from: rootPath + '/node_modules/updated-jqplot/build/plugins/jqplot.highlighter.js', to: publicPath + '/js/vendor/jqplot/plugins/jqplot.highlighter.js' },
                    { from: rootPath + '/node_modules/chart.js/dist/chart.umd.js', to: publicPath + '/js/vendor/chart.umd.js' },
                    { from: rootPath + '/node_modules/chart.js/dist/chart.umd.js.map', to: publicPath + '/js/vendor/chart.umd.js.map' },
                ],
            }),
            new WebpackConcatPlugin({
                bundles: [
                    {
                        dest: publicPath + '/js/vendor/jqplot/jquery.jqplot.js',
                        src: [
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.core.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.axisLabelRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.axisTickRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.canvasGridRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.divTitleRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.linePattern.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.lineRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.linearAxisRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.linearTickGenerator.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.markerRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.shadowRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.shapeRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.tableLegendRenderer.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.themeEngine.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.toImage.js',
                            rootPath + '/node_modules/updated-jqplot/build/jsdate.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.sprintf.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.effects.core.js',
                            rootPath + '/node_modules/updated-jqplot/build/jqplot.effects.blind.js',
                        ],
                    },
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
            'themes/bootstrap/css/theme': publicPath + '/themes/bootstrap/scss/theme.scss',
            'themes/metro/css/theme': publicPath + '/themes/metro/scss/theme.scss',
            'themes/original/css/theme': publicPath + '/themes/original/scss/theme.scss',
            'themes/pmahomme/css/theme': publicPath + '/themes/pmahomme/scss/theme.scss',
            'setup/styles': publicPath + '/setup/scss/styles.scss',
        },
        output: {
            filename: 'build/css/[name].js',
            path: publicPath,
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
