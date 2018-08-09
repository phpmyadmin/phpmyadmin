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
 * Moduele import
 */
import { PMA_sprintf } from './utils/sprintf';
import './variables/import_variables';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import { escapeHtml } from './utils/Sanitise';
import { PMA_Messages as messages } from './variables/export_variables';
import { $ } from './utils/JqueryExtended';
import { AJAX } from './ajax';
import CommonParams from './variables/common_params';
import { PMA_reloadNavigation } from './functions/navigation';
import { getJSConfirmCommonParam } from './functions/Common';

/**
 * @package PhpMyAdmin
 *
 * Server Databases
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownServerDatabases () {
    $(document).off('submit', '#dbStatsForm');
    $(document).off('submit', '#create_database_form.ajax');
}

/**
 * Binding event handlers on page load
 */
function onloadServerDatabases () {
    /**
     * Attach Event Handler for 'Drop Databases'
     */
    $(document).on('submit', '#dbStatsForm', function (event) {
        // debugger;
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
                    messages.strNoDatabasesSelected
                ),
                2000
            );
            return;
        }
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = messages.strDropDatabaseStrongWarning + ' ' +
            PMA_sprintf(messages.strDoYouReally, selected_dbs.join('<br />'));

        var argsep = CommonParams.get('arg_separator');
        $(this).PMA_confirm(
            question,
            $form.prop('action') + '?' + $(this).serialize() +
                argsep + 'drop_selected_dbs=1' + argsep + 'is_js_confirmed=1' + argsep + 'ajax_request=true',
            function (url) {
                PMA_ajaxShowMessage(messages.strProcessingRequest, false);

                var params = getJSConfirmCommonParam(this);

                $.post(url, params, function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        PMA_ajaxShowMessage(data.message);

                        var $rowsToRemove = $form.find('tr.removeMe');
                        var $databasesCount = $('#filter-rows-count');
                        var newCount = parseInt($databasesCount.text(), 10) - $rowsToRemove.length;
                        $databasesCount.text(newCount);

                        $rowsToRemove.remove();
                        $form.find('tbody').PMA_sort_table('.name');
                        if ($form.find('tbody').find('tr').length === 0) {
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
    }); // end of Drop Database action

    /**
     * Attach Ajax event handlers for 'Create Database'.
     */
    $(document).on('submit', '#create_database_form.ajax', function (event) {
        event.preventDefault();

        var $form = $(this);

        // TODO Remove this section when all browsers support HTML5 "required" property
        var newDbNameInput = $form.find('input[name=new_db]');
        if (newDbNameInput.val() === '') {
            newDbNameInput.focus();
            alert(messages.strFormEmpty);
            return;
        }
        // end remove

        PMA_ajaxShowMessage(messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxShowMessage(data.message);

                var $databases_count_object = $('#filter-rows-count');
                var databases_count = parseInt($databases_count_object.text(), 10) + 1;
                $databases_count_object.text(databases_count);
                PMA_reloadNavigation();

                // make ajax request to load db structure page - taken from ajax.js
                var dbStruct_url = data.url_query;
                dbStruct_url = dbStruct_url.replace(/amp;/ig, '');
                var params = 'ajax_request=true' + CommonParams.get('arg_separator') + 'ajax_page_request=true';
                if (! (history && history.pushState)) {
                    params += PMA_MicroHistory.menus.getRequestParam();
                }
                $.get(dbStruct_url, params, AJAX.responseHandler);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.post()
    }); // end $(document).on()

    /* Don't show filter if number of databases are very few */
    var databasesCount = $('#filter-rows-count').html();
    if (databasesCount <= 10) {
        $('#tableFilter').hide();
    }

    var tableRows = $('.server_databases');
    $.each(tableRows, function (index) {
        $(this).click(function () {
            PMA_commonActions.setDb($(this).attr('data'));
        });
    });
}

export {
    teardownServerDatabases,
    onloadServerDatabases
};
