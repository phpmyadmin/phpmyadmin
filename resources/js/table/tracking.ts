import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxShowMessage } from '../modules/ajax-message.ts';
import getJsConfirmCommonParam from '../modules/functions/getJsConfirmCommonParam.ts';

/**
 * Unbind all event handlers before tearing down the page
 */
AJAX.registerTeardown('table/tracking.js', function () {
    $('body').off('click', '#versionsForm.ajax button[name="submit_mult"], #versionsForm.ajax input[name="submit_mult"]');
    $('body').off('click', 'a.delete_version_anchor.ajax');
    $('body').off('click', 'a.delete_entry_anchor.ajax');
});

/**
 * Bind event handlers
 */
AJAX.registerOnload('table/tracking.js', function () {
    $('#versions tr').first().find('th').append($('<div class="sorticon"></div>'));
    $('#versions').tablesorter({
        sortList: [[1, 0]],
        headers: {
            0: { sorter: false },
            1: { sorter: 'integer' },
            5: { sorter: false },
            6: { sorter: false }
        }
    });

    if ($('#ddl_versions tbody tr').length > 0) {
        $('#ddl_versions tr').first().find('th').append($('<div class="sorticon"></div>'));
        $('#ddl_versions').tablesorter({
            sortList: [[0, 0]],
            headers: {
                0: { sorter: 'integer' },
                3: { sorter: false },
                4: { sorter: false }
            }
        });
    }

    if ($('#dml_versions tbody tr').length > 0) {
        $('#dml_versions tr').first().find('th').append($('<div class="sorticon"></div>'));
        $('#dml_versions').tablesorter({
            sortList: [[0, 0]],
            headers: {
                0: { sorter: 'integer' },
                3: { sorter: false },
                4: { sorter: false }
            }
        });
    }

    /**
     * Handles multi submit for tracking versions
     */
    $('body').on('click', '#versionsForm.ajax button[name="submit_mult"], #versionsForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        const $button = $(this);
        const $form = $button.parent('form');
        const argsep = CommonParams.get('arg_separator');
        const submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep + 'submit_mult=' + $button.val();

        if ($button.val() === 'delete_version') {
            const question = window.Messages.strDeleteTrackingVersionMultiple;
            $button.confirm(question, $form.attr('action'), function (url) {
                ajaxShowMessage();
                AJAX.source = $form;
                $.post(url, submitData, AJAX.responseHandler);
            });
        } else {
            ajaxShowMessage();
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        }
    });

    /**
     * Ajax Event handler for 'Delete version'
     */
    $('body').on('click', 'a.delete_version_anchor.ajax', function (e) {
        e.preventDefault();
        const $anchor = $(this);
        const question = window.Messages.strDeleteTrackingVersion;
        $anchor.confirm(question, $anchor.attr('href'), function (url) {
            ajaxShowMessage();
            AJAX.source = $anchor;
            const argSep = CommonParams.get('arg_separator');
            let params = getJsConfirmCommonParam(this, $anchor.getPostData());
            params += argSep + 'ajax_page_request=1';
            $.post(url, params, AJAX.responseHandler);
        });
    });

    /**
     * Ajax Event handler for 'Delete tracking report entry'
     */
    $('body').on('click', 'a.delete_entry_anchor.ajax', function (e) {
        e.preventDefault();
        const $anchor = $(this);
        const question = window.Messages.strDeletingTrackingEntry;
        $anchor.confirm(question, $anchor.attr('href'), function (url) {
            ajaxShowMessage();
            AJAX.source = $anchor;
            const argSep = CommonParams.get('arg_separator');
            let params = getJsConfirmCommonParam(this, $anchor.getPostData());
            params += argSep + 'ajax_page_request=1';
            $.post(url, params, AJAX.responseHandler);
        });
    });
});
