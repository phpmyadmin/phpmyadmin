/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the database structure page
 * @name            Database Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for db_structure.php
 * 
 * Actions ajaxified here:
 * Drop Database
 * Truncate Table
 * Drop Table
 *
 */
$(document).ready(function() {

    /**
     * Ajax Event handler for 'Truncate Table'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     */
    $(".truncate_table_anchor").live('click', function(event) {
        event.preventDefault();

        /**
         * @var $this_anchor Object  referring to the anchor clicked
         */
        var $this_anchor = $(this);

        //extract current table name and build the question string
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var curr_table_name = $this_anchor.parents('tr').children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = 'TRUNCATE ' + curr_table_name;

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if (data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    //Fetch inner span of this anchor
                    //and replace the icon with its disabled version
                    var span = $this_anchor.html().replace(/b_empty.png/, 'bd_empty.png');
                    var $parent_tr = $this_anchor.closest('tr');
                    var $rows_td = $parent_tr.find('.tbl_rows');
                    var $size_td = $parent_tr.find('.tbl_size');
                    var num_rows = parseInt($rows_td.text());
                    // set number of rows to 0
                    $rows_td.text('0');
                    // set size to unknown (not sure how to get the exact
                    // value here, as an empty InnoDB table would have a size) 
                    $size_td.text('-');

                    // try to compute a new total row number
                    if (! isNaN(num_rows)) {
                        $total_rows_td = $('#tbl_summary_row').find('.tbl_rows');
                        var total_rows = parseInt($total_rows_td.text());
                        if (! isNaN(total_rows)) {
                            $total_rows_td.text(total_rows - num_rows);
                        }
                    }

                    // prefix total size with "~"
                    var $total_size_td = $('#tbl_summary_row').find('.tbl_size');
                    $total_size_td.text($total_size_td.text().replace(/^/,'~'));

                    //To disable further attempts to truncate the table,
                    //replace the a element with its inner span (modified)
                    $this_anchor
                        .replaceWith(span)
                        .removeClass('truncate_table_anchor');
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) //end $.PMA_confirm()
    }); //end of Truncate Table Ajax action

    /**
     * Ajax Event handler for 'Drop Table'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     */
    $(".drop_table_anchor").live('click', function(event) {
        event.preventDefault();

        //extract current table name and build the question string
        /**
         * @var curr_row    Object containing reference to the current row
         */
        var curr_row = $(this).parents('tr');
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var curr_table_name = $(curr_row).children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = 'DROP TABLE ' + curr_table_name;

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    //need to find a better solution here.  The icon should be replaced
                    $(curr_row).hide("medium").remove();

                    if (window.parent && window.parent.frame_navigation) {
                        window.parent.frame_navigation.location.reload();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end of Drop Table Ajax action

    /**
     * Ajax Event handler for 'Drop Event'
     * 
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     */
    $('.drop_event_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_event_row  Object reference to current event's row
         */
        var curr_event_row = $(this).parents('tr');
        /**
         * @var curr_event_name String containing the name of {@link curr_event_row}
         */
        var curr_event_name = $(curr_event_row).children('td:first').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = 'DROP EVENT ' + curr_event_name;

        $(this).PMA_confirm(question, $(this).attr('href') , function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingEvent']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_event_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) //end Drop Event

    /**
     * Ajax Event handler for 'Drop Procedure'
     * 
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     */
    $('.drop_procedure_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_proc_row   Object containing reference to the current procedure's row
         */
        var curr_proc_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $(curr_proc_row).children('.drop_procedure_sql').val();

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingProcedure']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_event_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) //end Drop Procedure
    
    $('.drop_tracking_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_tracking_row   Object containing reference to the current tracked table's row
         */
        var curr_tracking_row = $(this).parents('tr');
         /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strDeleteTrackingData'];

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDeletingTrackingData']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $(curr_tracking_row).hide("medium").remove();
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }) //end Drop Tracking

    //Calculate Real End for InnoDB
    /**
     * Ajax Event handler for calculatig the real end for a InnoDB table
     * 
     * @uses    $.PMA_confirm
     */
    $('#real_end_input').live('click', function(event) {
        event.preventDefault();

        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages['strOperationTakesLongTime'];

        $(this).PMA_confirm(question, '', function() {
            return true;
        })
        return false;
    }) //end Calculate Real End for InnoDB

}, 'top.frame_content'); // end $(document).ready()
