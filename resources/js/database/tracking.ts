import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxShowMessage } from '../modules/ajax-message.ts';
import getJsConfirmCommonParam from '../modules/functions/getJsConfirmCommonParam.ts';

/**
 * Unbind all event handlers before tearing down the page
 */
AJAX.registerTeardown('database/tracking.js', function () {
    $('body').off('click', '#trackedForm.ajax button[name="submit_mult"], #trackedForm.ajax input[name="submit_mult"]');
    $('body').off('click', '#untrackedForm.ajax button[name="submit_mult"], #untrackedForm.ajax input[name="submit_mult"]');
    $('body').off('click', 'a.delete_tracking_anchor.ajax');
});

/**
 * Bind event handlers
 */
AJAX.registerOnload('database/tracking.js', function () {
    const $versions = $('#versions');
    $versions.find('tr').first().find('th').append($('<div class="sorticon"></div>'));
    $versions.tablesorter({
        sortList: [[1, 0]],
        headers: {
            0: { sorter: false },
            2: { sorter: 'integer' },
            5: { sorter: false },
            6: { sorter: false },
            7: { sorter: false }
        }
    });

    const $noVersions = $('#noversions');
    $noVersions.find('tr').first().find('th').append($('<div class="sorticon"></div>'));
    $noVersions.tablesorter({
        sortList: [[1, 0]],
        headers: {
            0: { sorter: false },
            2: { sorter: false }
        }
    });

    const $body = $('body');

    /**
     * Handles multi submit for tracked tables
     */
    $body.on('click', '#trackedForm.ajax button[name="submit_mult"], #trackedForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        const $button = $(this);
        const $form = $button.parent('form');
        const argsep = CommonParams.get('arg_separator');
        const submitData = $form.serialize() + argsep + 'ajax_page_request=true' + argsep + 'submit_mult=' + $button.val();

        if ($button.val() === 'delete_tracking') {
            const question = window.Messages.strDeleteTrackingDataMultiple;
            $button.confirm(question, $form.attr('action'), function (url) {
                ajaxShowMessage(window.Messages.strDeletingTrackingData);
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
     * Handles multi submit for untracked tables
     */
    $body.on('click', '#untrackedForm.ajax button[name="submit_mult"], #untrackedForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        const $button = $(this);
        const $form = $button.parent('form');
        const argsep = CommonParams.get('arg_separator');
        const submitData = $form.serialize() + argsep + 'ajax_page_request=true' + argsep + 'submit_mult=' + $button.val();
        ajaxShowMessage();
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    /**
     * Ajax Event handler for 'Delete tracking'
     */
    $body.on('click', 'a.delete_tracking_anchor.ajax', function (e) {
        e.preventDefault();
        const $anchor = $(this);
        const question = window.Messages.strDeleteTrackingData;
        $anchor.confirm(question, $anchor.attr('href'), function (url) {
            ajaxShowMessage(window.Messages.strDeletingTrackingData);
            AJAX.source = $anchor;
            const argSep = CommonParams.get('arg_separator');
            let params = getJsConfirmCommonParam(this, $anchor.getPostData());
            params += argSep + 'ajax_page_request=1';
            $.post(url, params, AJAX.responseHandler);
        });
    });
});
