import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { prepareForAjaxRequest } from '../modules/functions.ts';
import { Navigation } from '../modules/navigation.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxShowMessage } from '../modules/ajax-message.ts';
import getJsConfirmCommonParam from '../modules/functions/getJsConfirmCommonParam.ts';
import { escapeHtml } from '../modules/functions/escape.ts';
import refreshMainContent from '../modules/functions/refreshMainContent.ts';

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const DropDatabases = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        event.preventDefault();

        const $form = $(this);

        /**
         * @var selected_dbs Array containing the names of the checked databases
         */
        const selectedDbs = [];
        // loop over all checked checkboxes, except the .checkall_box checkbox
        $form.find('input:checkbox:checked:not(.checkall_box)').each(function () {
            $(this).closest('tr').addClass('removeMe');
            selectedDbs[selectedDbs.length] = 'DROP DATABASE `' + escapeHtml(($(this).val() as string)) + '`;';
        });

        if (! selectedDbs.length) {
            ajaxShowMessage(
                $('<div class="alert alert-warning" role="alert"></div>').text(
                    window.Messages.strNoDatabasesSelected
                ),
                2000
            );

            return;
        }

        /**
         * @var question    String containing the question to be asked for confirmation
         */
        const question = window.Messages.strDropDatabaseStrongWarning + ' ' +
            window.sprintf(window.Messages.strDoYouReally, selectedDbs.join('<br>'));

        const modal = $('#dropDatabaseModal');
        modal.find('.modal-body').html(question);
        modal.modal('show');

        const url = 'index.php?route=/server/databases/destroy&' + $(this).serialize();

        $('#dropDatabaseModalDropButton').on('click', function () {
            ajaxShowMessage(window.Messages.strProcessingRequest, false);

            const parts = url.split('?');
            const params = getJsConfirmCommonParam(this, parts[1]);

            $.post(parts[0], params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    ajaxShowMessage(data.message);

                    const $rowsToRemove = $form.find('tr.removeMe');
                    const $databasesCount = $('#filter-rows-count');
                    const newCount = parseInt($databasesCount.text(), 10) - $rowsToRemove.length;
                    $databasesCount.text(newCount);

                    $rowsToRemove.remove();
                    $form.find('tbody').sortTable('.name');
                    if ($form.find('tbody').find('tr').length === 0) {
                        // user just dropped the last db on this page
                        refreshMainContent();
                    }

                    Navigation.reload();
                } else {
                    $form.find('tr.removeMe').removeClass('removeMe');
                    ajaxShowMessage(data.error, false);
                }
            });

            modal.modal('hide');
            $('#dropDatabaseModalDropButton').off('click');
        });
    }
};

/**
 * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 */
const CreateDatabase = {
    /**
     * @param {Event} event
     */
    handleEvent: function (event) {
        event.preventDefault();

        const $form = $(this);

        // TODO Remove this section when all browsers support HTML5 "required" property
        const newDbNameInput = $form.find('input[name=new_db]');
        if (newDbNameInput.val() === '') {
            newDbNameInput.trigger('focus');
            alert(window.Messages.strFormEmpty);

            return;
        }
        // end remove

        ajaxShowMessage(window.Messages.strProcessingRequest);
        prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                ajaxShowMessage(data.message);

                const $databasesCountObject = $('#filter-rows-count');
                const databasesCount = parseInt($databasesCountObject.text(), 10) + 1;
                $databasesCountObject.text(databasesCount);
                Navigation.reload();

                // make ajax request to load db structure page - taken from ajax.js
                let dbStructUrl = data.url;
                dbStructUrl = dbStructUrl.replace(/amp;/ig, '');
                const params = 'ajax_request=true' + CommonParams.get('arg_separator') + 'ajax_page_request=true';
                $.get(dbStructUrl, params, AJAX.responseHandler);
            } else {
                ajaxShowMessage(data.error, false);
            }
        });
    }
};

AJAX.registerTeardown('server/databases.js', function () {
    $(document).off('submit', '#dbStatsForm');
    $(document).off('submit', '#create_database_form.ajax');
});

AJAX.registerOnload('server/databases.js', function () {
    $(document).on('submit', '#dbStatsForm', DropDatabases.handleEvent);
    $(document).on('submit', '#create_database_form.ajax', CreateDatabase.handleEvent);
});
