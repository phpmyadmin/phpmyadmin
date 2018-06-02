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
        // ajax_new: './js/src/ajax.js',
        // chart_new: './js/src/chart.js',
        // common_new: './js/src/common.js',
        // config_new: './js/src/config.js',
        // console_new: './js/src/console.js',
        // cross_framing_protection_new: './js/src/cross_framing_protection.js',
        // db_central_columns_new: './js/src/db_central_columns.js',
        // db_multi_table_query_new: './js/src/db_multi_table_query.js',
        // db_operations_new: './js/src/db_operations.js',
        // db_qbe_new: './js/src/db_qbe.js',
        db_search_new: './js/src/db_search.js',
        // db_structure_new: './js/src/db_structure.js',
        // db_tracking_new: './js/src/db_tracking.js',
        // doclinks_new: './js/src/doclinks.js',
        index_new: './js/src/index.js',
        // error_report_new: './js/src/error_report.js',
        // export_new: './js/src/export.js',
        // export_output_new: './js/src/export_output.js',
        // functions_new: './js/src/functions.js',
        // gis_data_editor_new: './js/src/gis_data_editor.js',
        // import_new: './js/src/import.js',
        // indexes_new: './js/src/indexes.js',
        // keyhandler_new: './js/src/keyhandler.js',
        // makegrid_new: './js/src/makegrid.js',
        // menu_resizer_new: './js/src/menu_resizer.js',
        // microhistory_new: './js/src/microhistory.js',
        // multi_column_sort_new: './js/src/multi_column_sort.js',
        // navigation_new: './js/src/navigation.js',
        // normalization_new: './js/src/normalization.js',
        // page_settings_new: './js/src/page_settings.js',
        // replication_new: './js/src/replication.js',
        // rte_new: './js/src/rte.js',
        // server_databases_new: './js/src/server_databases.js',
        server_plugins_new: './js/src/server_plugins.js',
        server_privileges_new: './js/src/server_privileges.js',
        server_status_advisor_new: './js/src/server_status_advisor.js',
        // server_status_monitor_new: './js/src/server_status_monitor.js',
        // server_status_processes_new: './js/src/server_status_processes.js',
        // server_status_queries_new: './js/src/server_status_queries.js',
        // server_status_sorter_new: './js/src/server_status_sorter.js',
        // server_status_variables_new: './js/src/server_status_variables.js',
        // server_user_groups_new: './js/src/server_user_groups.js',
        // server_variables_new: './js/src/server_variables.js',
        // shortcuts_handler_new: './js/src/shortcuts_handler.js',
        // sql_new: './js/src/sql.js',
        // tbl_change_new: './js/src/tbl_change.js',
        // tbl_chart_new: './js/src/tbl_chart.js',
        // tbl_find_replace_new: './js/src/tbl_find_replace.js',
        // tbl_gis_visualization_new: './js/src/tbl_gis_visualization.js',
        // tbl_operations_new: './js/src/tbl_operations.js',
        // tbl_relation_new: './js/src/tbl_relation.js',
        // tbl_select_new: './js/src/tbl_select.js',
        tbl_structure_new: './js/src/tbl_structure.js',
        // tbl_tracking_new: './js/src/tbl_tracking.js',
        // tbl_zoom_plot_jqplot_new: './js/src/tbl_zoom_plot_jqplot.js',
        // u2f_new: './js/src/u2f.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist'),
        // url on which dev server will run
        publicPath: 'http://localhost:3307/js/dist/'
    },
    module: module,
    // devtool: 'source-map',
    resolve: {
        extensions: ['.js']
    },
    devServer: devServer,
    plugins: plugins
}/*,
{
    // envionment either development or production
    mode: 'development',
    entry: {
        pma_messages: './js/src/global/pma_messages.js',
        ajax_global: './js/src/global/ajax_global.js',
        jQuery: './js/src/global/jquery.js'
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'js/dist/global'),
        // url on which dev server will run
        publicPath: 'http://localhost:3007/js/dist/global/',
        library: '[name]',
        libraryTarget: 'var',
        umdNamedDefine: true
    },
    module: module,
    // devtool: 'source-map',
    resolve: {
        extensions: ['.js']
    },
    devServer: devServer,
    plugins: plugins
}*/];
