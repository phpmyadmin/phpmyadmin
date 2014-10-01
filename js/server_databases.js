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
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('server_databases.js', function () {
    $("#dbStatsForm").die('submit');
    $('#create_database_form.ajax').die('submit');
});

/**
 * AJAX scripts for server_databases.php
 *
 * Actions ajaxified here:
 * Drop Databases
 *
 */
AJAX.registerOnload('server_databases.js', function () {
    /**
     * Attach Event Handler for 'Drop Databases'
     */
    $("#dbStatsForm").live('submit', function (event) {
        event.preventDefault();

        var $form = $(this);

        /**
         * @var selected_dbs Array containing the names of the checked databases
         */
        var selected_dbs = [];
        // loop over all checked checkboxes, except the .checkall_box checkbox
        $form.find('input:checkbox:checked:not(.checkall_box)').each(function () {
            $(this).closest('tr').addClass('removeMe');
            selected_dbs[selected_dbs.length] = 'DROP DATABASE `' + escapeHtml($(this).val()) + '`;';
        });
        if (! selected_dbs.length) {
            PMA_ajaxShowMessage(
                $('<div class="notice" />').text(
                    PMA_messages.strNoDatabasesSelected
                ),
                2000
            );
            return;
        }
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages.strDropDatabaseStrongWarning + ' ' +
            $.sprintf(PMA_messages.strDoYouReally, selected_dbs.join('<br />'));

        $(this).PMA_confirm(
            question,
            $form.prop('action') + '?' + $(this).serialize() +
                '&drop_selected_dbs=1&is_js_confirmed=1&ajax_request=true',
            function (url) {
                PMA_ajaxShowMessage(PMA_messages.strProcessingRequest, false);

                $.post(url, function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        PMA_ajaxShowMessage(data.message);

                        var $rowsToRemove = $form.find('tr.removeMe');
                        var $databasesCount = $('#databases_count');
                        var newCount = parseInt($databasesCount.text(), 10) - $rowsToRemove.length;
                        $databasesCount.text(newCount);

                        $rowsToRemove.remove();
                        $form.find('tbody').PMA_sort_table('.name');
                        if ($form.find('tbody').find('tr').length == 0) {
                            // user just dropped the last db on this page
                            PMA_commonActions.refreshMain();
                        }
                        PMA_reloadNavigation();
                    } else {
                        $form.find('tr.removeMe').removeClass('removeMe');
                        PMA_ajaxShowMessage(data.error, false);
                    }
                }); // end $.post()
            }
        ); // end $.PMA_confirm()
    }); //end of Drop Database action

    /**
     * Attach Ajax event handlers for 'Create Database'.
     */
    $('#create_database_form.ajax').live('submit', function (event) {
        event.preventDefault();

        var $form = $(this);

        // TODO Remove this section when all browsers support HTML5 "required" property
        var newDbNameInput = $form.find('input[name=new_db]');
        if (newDbNameInput.val() === '') {
            newDbNameInput.focus();
            alert(PMA_messages.strFormEmpty);
            return;
        }
        // end remove

        PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxShowMessage(data.message);

                //Append database's row to table
                $("#tabledatabases")
                .find('tbody')
                .append(data.new_db_string)
                .PMA_sort_table('.name');

                var $databases_count_object = $('#databases_count');
                var databases_count = parseInt($databases_count_object.text(), 10) + 1;
                $databases_count_object.text(databases_count);
                PMA_reloadNavigation();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.post()
    }); // end $().live()
}); // end $()
