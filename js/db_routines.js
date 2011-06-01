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
                        $('#routine_list').hide("slow", function () {
                            $(this).remove();
                        });
                        $('#no_routines').show("slow");
                    } else {
                        $curr_proc_row.hide("slow", function () {
                            $(this).remove();
                        });
                    }
                }
                else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }); // end Drop Procedure

}, 'top.frame_content'); //end $(document).ready() for Drop Procedure
