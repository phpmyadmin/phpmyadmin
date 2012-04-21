/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the server databases list page
 * @name            Server Databases
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for server_databases.php
 *
 * Actions ajaxified here:
 * Drop Databases
 *
 */
$(document).ready(function() {
    /**
     * Attach Event Handler for 'Drop Databases'
     *
     * (see $GLOBALS['cfg']['AjaxEnable'])
     */
    $("button[name=drop_selected_dbs].ajax").live('click', function(event) {
        event.preventDefault();

        var $form = $(this.form);

        /**
         * @var selected_dbs Array containing the names of the checked databases
         */
        var selected_dbs = [];
        $form.find('input:checkbox:checked').each(function () {
            selected_dbs[selected_dbs.length] = 'DROP DATABASE `' + escapeHtml($(this).val()) + '`;';
        });
        if (! selected_dbs.length) {
            PMA_ajaxShowMessage(PMA_messages.strNoDatabasesSelected, 2000);
            return;
        }
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = 
            PMA_messages.strDropDatabaseStrongWarning + ' '
            + $.sprintf(PMA_messages.strDoYouReally, selected_dbs.join('<br />'));

        $(this).PMA_confirm(
            question,
            $form.prop('action')
                + '?' + $(this.form).serialize()
                + '&drop_selected_dbs=1&is_js_confirmed=1&ajax_request=true',
            function(url) {
                PMA_ajaxShowMessage(PMA_messages.strProcessingRequest, false);

                $.post(url, function(data) {
                    if(data.success == true) {
                        PMA_ajaxShowMessage(data.message);
                        if (window.parent && window.parent.frame_navigation) {
                            window.parent.frame_navigation.location.reload();
                        }
                        $('#tableslistcontainer').load('server_databases.php form#dbStatsForm');
                    }
                    else {
                        PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + ": " + data.error, false);
                    }
                }); // end $.post()
        }); // end $.PMA_confirm()
    }) ; //end of Drop Database action
}); // end $(document).ready()
