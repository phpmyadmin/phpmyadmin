/* vim: set expandtab sw=4 ts=4 sts=4: */
$(document).ready(function() {
    /**
     * Ajax Event handler for 'Drop Routine'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * @see     $cfg['AjaxEnable']
     */
    $('.drop_routine_anchor').live('click', function(event) {
        event.preventDefault();

        /**
         * @var curr_proc_row   Object containing reference to the current procedure's row
         */
        var $curr_proc_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $curr_proc_row.children('td').children('.drop_sql').text();

        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {

            PMA_ajaxShowMessage(PMA_messages['strDroppingProcedure']);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    $("#result_query").remove();
                    if ($('#routine_list tr').length == 2) {
                        $('#routine_list').remove();
                        $('#no_routines').show();
                    } else {
                       $curr_proc_row.hide("medium").remove();
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }); // end Drop Procedure

    /**
     * Ajax Event handler for 'Export Routine'
     *
     * @see     $cfg['AjaxEnable']
     */
    $('.export_routine_anchor').live('click', function(event) {
        event.preventDefault();
        var $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxRemoveMessage($msg);
                /**
                 * @var button_options  Object containing options for jQueryUI dialog buttons
                 */
                var button_options = {};
                button_options[PMA_messages['strClose']] = function() {$(this).dialog("close").remove();}
                /**
                 * Display the dialog to the user
                 */
                $('<div>'+data.message+'</div>').dialog({
                            width: 450,
                            buttons: button_options,
                            title: data.title
                        }).find('textarea').width('100%');
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        })
    }); // end Export Procedure
}, 'top.frame_content'); //end $(document).ready() for Drop Procedure
