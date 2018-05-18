import path from 'path';
import webpack from 'webpack';

// let dev server port be 3307

var mode = 'development';
var module = {
    rules: [
        { test: /\.(js)$/, use: 'babel-loader', exclude: /node_modules/ }
    ]
};
var resolve = {
    extensions: ['.js']
};
var devServer = {
    // port number of dev server
    port: 3307,
    // hot: true,
    headers: {
        'Access-Control-Allow-Origin': '*'
    }
};
var plugins = [
    new webpack.optimize.OccurrenceOrderPlugin(),
    new webpack.HotModuleReplacementPlugin(),
    new webpack.NamedModulesPlugin(),
    new webpack.NoEmitOnErrorsPlugin(),
];

export default [{
    // envionment either development or production
    mode: mode,
    entry: {
        ajax: './js/src/ajax.js',
        chart: './js/src/chart.js',
        common: './js/src/common.js',
        config: './js/src/config.js',
        console: './js/src/console.js',
        cross_framing_protection: './js/src/cross_framing_protection.js',
        db_central_columns: './js/src/db_central_columns.js',
        db_multi_table_query: './js/src/db_multi_table_query.js',
        db_operations: './js/src/db_operations.js',
        db_qbe: './js/src/db_qbe.js',
        db_search: './js/src/db_search.js',
        db_structure: './js/src/db_structure.js',
        db_tracking: './js/src/db_tracking.js',
        doclinks: './js/src/doclinks.js',
        error_report: './js/src/error_report.js',
        export: './js/src/export.js',
        export_output: './js/src/export_output.js',
        functions: './js/src/functions.js',
        gis_data_editor: './js/src/gis_data_editor.js',
        import: './js/src/import.js',
        indexes: './js/src/indexes.js',
        keyhandler: './js/src/keyhandler.js',
        makegrid: './js/src/makegrid.js',
        menu_resizer: './js/src/menu_resizer.js',
        microhistory: './js/src/microhistory.js',
        multi_column_sort: './js/src/multi_column_sort.js',
        navigation: './js/src/navigation.js',
        normalization: './js/src/normalization.js',
        page_settings: './js/src/page_settings.js',
        replication: './js/src/replication.js',
        rte: './js/src/rte.js',
        server_databases: './js/src/server_databases.js',
        server_plugins: './js/src/server_plugins.js',
        server_privileges: './js/src/server_privileges.js',
        server_status_advisor: './js/src/server_status_advisor.js',
        server_status_monitor: './js/src/server_status_monitor.js',
        server_status_processes: './js/src/server_status_processes.js',
        server_status_queries: './js/src/server_status_queries.js',
        server_status_sorter: './js/src/server_status_sorter.js',
        server_status_variables: './js/src/server_status_variables.js',
        server_user_groups: './js/src/server_user_groups.js',
        server_variables: './js/src/server_variables.js',
        shortcuts_handler: './js/src/shortcuts_handler.js',
        sql: './js/src/sql.js',
        tbl_change: './js/src/tbl_change.js',
        tbl_chart: './js/src/tbl_chart.js',
        tbl_find_replace: './js/src/tbl_find_replace.js',
        tbl_gis_visualization: './js/src/tbl_gis_visualization.js',
        tbl_operations: './js/src/tbl_operations.js',
        tbl_relation: './js/src/tbl_relation.js',
        tbl_select: './js/src/tbl_select.js',
        tbl_structure: './js/src/tbl_structure.js',
        tbl_tracking: './js/src/tbl_tracking.js',
        tbl_zoom_plot_jqplot: './js/src/tbl_zoom_plot_jqplot.js',
        u2f: './js/src/u2f.js'
    },
    output: {
        filename: 'js/sql.js',
        path: path.resolve(__dirname, 'dist'),
        publicPath: 'http://localhost:3007/dist'
    },
    module: module,
    // devtool: 'source-map',
    resolve: resolve,
    devServer: devServer,
    plugins: plugins
},
{
    // envionment either development or production
    mode: 'development',
    entry: {
        pma_common_params: './js/src/global/pma_common_params',
        ajax_global: './js/src/global/ajax_global.js',
        jQuery: './js/src/global/jquery.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist/global'),
        // url on which dev server will run
        publicPath: 'http://localhost:3007/js/dist/global/',
        library: '[name]',
        libraryTarget: 'umd'
    },
    module: module,
    // devtool: 'source-map',
    resolve: resolve,
    devServer: devServer,
    plugins: plugins
}];
