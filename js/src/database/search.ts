import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { Functions } from '../modules/functions.ts';
import { CommonParams } from '../modules/common.ts';
import highlightSql from '../modules/sql-highlight.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import getImageTag from '../modules/functions/getImageTag.ts';

/**
 * JavaScript functions used on Database Search page
 *
 * @package PhpMyAdmin
 */

/**
 * AJAX script for the Database Search page.
 *
 * Actions ajaxified here:
 * Retrieve result of SQL query
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('database/search.js', function () {
    $('.browse_results').off('click');
    $('.delete_results').off('click');
    $('#buttonGo').off('click');
    $('#togglesearchresultlink').off('click');
    $('#togglequerybox').off('click');
    $('#togglesearchformlink').off('click');
    $('#select_all').off('click');
    $('#unselect_all').off('click');
    $(document).off('submit', '#db_search_form.ajax');
});

AJAX.registerOnload('database/search.js', function () {
    /** Hide the table link in the initial search result */
    var icon = getImageTag('s_tbl', '', { 'id': 'table-image' }).toString();
    $('#table-info').prepend(icon).hide();

    /** Hide the browse and deleted results in the new search criteria */
    $('#buttonGo').on('click', function () {
        $('#table-info').hide();
        $('#browse-results').hide();
        $('#sqlqueryform').hide();
        $('#togglequerybox').hide();
    });

    /**
     * Prepare a div containing a link for toggle the search results
     */
    $('#togglesearchresultsdiv')
        /** don't show it until we have results on-screen */
        .hide();

    /**
     * Changing the displayed text according to
     * the hide/show criteria in search result forms
     */
    $('#togglesearchresultlink')
        .html(window.Messages.strHideSearchResults)
        .on('click', function () {
            var $link = $(this);
            $('#searchresults').slideToggle();
            if ($link.text() === window.Messages.strHideSearchResults) {
                $link.text(window.Messages.strShowSearchResults);
            } else {
                $link.text(window.Messages.strHideSearchResults);
            }

            /** avoid default click action */
            return false;
        });

    /**
     * Prepare a div containing a link for toggle the search form,
     * otherwise it's incorrectly displayed after a couple of clicks
     */
    $('#togglesearchformdiv')
        .hide(); // don't show it until we have results on-screen

    /**
     * Changing the displayed text according to
     * the hide/show criteria in search form
     */
    $('#togglequerybox')
        .hide()
        .on('click', function () {
            var $link = $(this);
            $('#sqlqueryform').slideToggle('medium');
            if ($link.text() === window.Messages.strHideQueryBox) {
                $link.text(window.Messages.strShowQueryBox);
            } else {
                $link.text(window.Messages.strHideQueryBox);
            }

            /** avoid default click action */
            return false;
        });

    /** don't show it until we have results on-screen */

    /**
     * Changing the displayed text according to
     * the hide/show criteria in search criteria form
     */
    $('#togglesearchformlink')
        .html(window.Messages.strShowSearchCriteria)
        .on('click', function () {
            var $link = $(this);
            $('#db_search_form').slideToggle();
            if ($link.text() === window.Messages.strHideSearchCriteria) {
                $link.text(window.Messages.strShowSearchCriteria);
            } else {
                $link.text(window.Messages.strHideSearchCriteria);
            }

            /** avoid default click action */
            return false;
        });

    /*
     * Ajax Event handler for retrieving the results from a table
     */
    $(document).on('click', '.browse_results', function (e) {
        e.preventDefault();
        /**   Hides the results shown by the delete criteria */
        var $msg = ajaxShowMessage(window.Messages.strBrowsing, false);
        $('#sqlqueryform').hide();
        $('#togglequerybox').hide();
        /**  Load the browse results to the page */
        $('#table-info').show();
        var tableName = $(this).data('table-name');
        $('#table-link').attr({ 'href': $(this).data('href') }).text(tableName);

        var url = $(this).data('href') + '#searchresults';
        var browseSql = $(this).data('browse-sql');
        var params = {
            'ajax_request': true,
            'is_js_confirmed': true,
            'sql_query': browseSql
        };
        $.post(url, params, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                $('#browse-results').html(data.message);
                ajaxRemoveMessage($msg);
                $('.table_results').each(function () {
                    window.makeGrid(this, true, true, true, true);
                });

                $('#browse-results').show();
                highlightSql($('#browse-results'));
                $('html, body')
                    .animate({
                        scrollTop: $('#browse-results').offset().top
                    }, 1000);
            } else {
                ajaxShowMessage(data.error, false);
            }
        });
    });

    /*
     * Ajax Event handler for deleting the results from a table
     */
    $(document).on('click', '.delete_results', function (e) {
        e.preventDefault();
        /**  Hides the results shown by the browse criteria */
        $('#table-info').hide();
        $('#sqlqueryform').hide();
        $('#togglequerybox').hide();
        /** Conformation message for deletion */
        var msg = window.sprintf(
            window.Messages.strConfirmDeleteResults,
            $(this).data('table-name')
        );
        if (confirm(msg)) {
            var $msg = ajaxShowMessage(window.Messages.strDeleting, false);
            /** Load the deleted option to the page*/
            $('#sqlqueryform').html('');
            var params = {
                'ajax_request': true,
                'is_js_confirmed': true,
                'sql_query': $(this).data('delete-sql')
            };
            var url = $(this).data('href');

            $.post(url, params, function (data) {
                if (typeof data === 'undefined' || ! data.success) {
                    ajaxShowMessage(data.error, false);

                    return;
                }

                $('#sqlqueryform').html(data.sql_query);
                /** Refresh the search results after the deletion */
                $('#buttonGo').trigger('click');
                $('#togglequerybox').html(window.Messages.strHideQueryBox);
                /** Show the results of the deletion option */
                $('#browse-results').hide();
                $('#sqlqueryform').show();
                $('#togglequerybox').show();
                $('html, body')
                    .animate({
                        scrollTop: $('#browse-results').offset().top
                    }, 1000);

                ajaxRemoveMessage($msg);
            });
        }
    });

    /**
     * Ajax Event handler for retrieving the result of an SQL Query
     */
    $(document).on('submit', '#db_search_form.ajax', function (event) {
        event.preventDefault();
        if ($('#criteriaTables :selected').length === 0) {
            ajaxShowMessage(window.Messages.strNoTableSelected);

            return;
        }

        var $msgbox = ajaxShowMessage(window.Messages.strSearching, false);
        // jQuery object to reuse
        var $form = $(this);

        Functions.prepareForAjaxRequest($form);

        var url = $form.serialize() + CommonParams.get('arg_separator') + 'submit_search=' + $('#buttonGo').val();
        $.post($form.attr('action'), url, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                // found results
                $('#searchresults').html(data.message);

                $('#togglesearchresultlink')
                    // always start with the Show message
                    .text(window.Messages.strHideSearchResults);

                $('#togglesearchresultsdiv')
                    // now it's time to show the div containing the link
                    .show();

                $('#searchresults').show();


                $('#db_search_form')
                    // workaround for Chrome problem (bug #3168569)
                    .slideToggle()
                    .hide();

                $('#togglesearchformlink')
                    // always start with the Show message
                    .text(window.Messages.strShowSearchCriteria);

                $('#togglesearchformdiv')
                    // now it's time to show the div containing the link
                    .show();
            } else {
                // error message (zero rows)
                $('#searchresults').html(data.error).show();
            }

            ajaxRemoveMessage($msgbox);
        });
    });

    $('#select_all').on('click', function () {
        Functions.setSelectOptions('db_search', 'criteriaTables[]', true);

        return false;
    });

    $('#unselect_all').on('click', function () {
        Functions.setSelectOptions('db_search', 'criteriaTables[]', false);

        return false;
    });
}); // end $()
