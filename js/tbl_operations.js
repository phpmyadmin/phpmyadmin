/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in server privilege pages
 *
 */

/**
 * Add all AJAX scripts for tbl_operations.php page here.
 *
 * Alter table order - #div_table_order form
 * Move Table - #div_table_rename form
 * Table Options - #div_table_options form
 * Copy Table - #div_table_copy form
 * Table Maintenance - #div_table_maintenance (need to id each anchor)
 *  Check
 *  Repair
 *  Analyze
 *  Flush
 *  Optimize
 */
 
  /**
 * Attach Ajax event handlers for Drop Table.
 *
 * @uses    $.PMA_confirm()
 * @uses    PMA_ajaxShowMessage()
 * @uses    window.parent.refreshNavigation()
 * @uses    window.parent.refreshMain()
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    $("#drop_tbl_anchor").live('click', function(event) {
        event.preventDefault();

        //context is top.frame_content, so we need to use window.parent.db to access the db var
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDropTableStrongWarning'] + '\n' + PMA_messages['strDoYouReally'] + ' :\n' + 'DROP TABLE ' + window.parent.table;

        $(this).PMA_confirm(question, $(this).attr('href') ,function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
            $.get(url, {'is_js_confirmed': '1', 'ajax_request': true}, function(data) {
                //Database deleted successfully, refresh both the frames
                window.parent.refreshNavigation();
                window.parent.refreshMain();
            }) // end $.get()
        }); // end $.PMA_confirm()
    }); //end of Drop Table Ajax action
}) // end of $(document).ready() for Drop Table

 /**
 * Attach Ajax event handlers for Truncate Table.
 *
 * @uses    $.PMA_confirm()
 * @uses    PMA_ajaxShowMessage()
 * @uses    window.parent.refreshNavigation()
 * @uses    window.parent.refreshMain()
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    $("#truncate_tbl_anchor").live('click', function(event) {
        event.preventDefault();

        //context is top.frame_content, so we need to use window.parent.db to access the db var
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strTruncateTableStrongWarning'] + '\n' + PMA_messages['strDoYouReally'] + ' :\n' + 'TRUNCATE TABLE ' + window.parent.table;

        $(this).PMA_confirm(question, $(this).attr('href') ,function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
            $.get(url, {'is_js_confirmed': '1', 'ajax_request': true}, function(data) {
                //Database deleted successfully, refresh both the frames
                window.parent.refreshNavigation();
                window.parent.refreshMain();
            }) // end $.get()
        }); // end $.PMA_confirm()
    }); //end of Drop Table Ajax action
}) // end of $(document).ready() for Drop Table