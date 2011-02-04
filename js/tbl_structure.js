/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the table structure page
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for tbl_structure.php
 *
 * Actions ajaxified here:
 * Drop Column
 * Add Primary Key
 * Drop Primary Key/Index
 *
 */
$(document).ready(function() {
    
    /**
     * Attach Event Handler for 'Drop Column'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".drop_column_anchor").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_row    Object reference to the currently selected row (i.e. field in the table)
         */
        var curr_row = $(this).parents('tr');
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $(curr_row).children('th').children('label').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + ' :\n ALTER TABLE `' + curr_table_name + '` DROP `' + curr_column_name + '`';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingColumn']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }); // end $.PMA_confirm()
    }) ; //end of Drop Column Anchor action

    /**
     * Ajax Event handler for 'Add Primary Key'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $(".action_primary a").live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_table_name String containing the name of the current table
         */
        var curr_table_name = window.parent.table;
        /**
         * @var curr_column_name    String containing name of the field referred to by {@link curr_row}
         */
        var curr_column_name = $(this).parents('tr').children('th').children('label').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDoYouReally'] + ' :\n ALTER TABLE `' + curr_table_name + '` ADD PRIMARY KEY(`' + curr_column_name + '`)';

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strAddingPrimaryKey']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(this).remove();
                    if (typeof data.reload != 'undefined') {
                        window.parent.frame_content.location.reload();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    })//end Add Primary Key

    /**
     * Ajax Event handler for 'Drop Primary Key/Index'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $('.drop_primary_key_index_anchor').live('click', function(event) {
        event.preventDefault();

        $anchor = $(this);

        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = $curr_row.children('td').children('.drop_primary_key_index_msg').val();

        $anchor.PMA_confirm(question, $anchor.attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingPrimaryKeyIndex']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $rows_to_hide.hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) //end Drop Primary Key/Index
    
}) // end $(document).ready()
