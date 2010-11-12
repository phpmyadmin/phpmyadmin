/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in server privilege pages
 * @name            Database Operations
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Ajax event handlers here for db_operations.php
 *
 * Actions Ajaxified here:
 * Rename Database
 * Copy Database
 * Change charset
 */

$(document).ready(function() {

    /**
     * Ajax event handlers for 'Rename Database'
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowUser()
     */
    $("#rename_db_form").live('submit', function(event) {
        event.preventDefault();

        var question = 'CREATE DATABASE ... and then DROP DATABASE ' + window.parent.db;
        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        /**
         * @var button_options  Object containing options for jQueryUI dialog buttons
         */
        var button_options = {};
        button_options[PMA_messages['strYes']] = function() {
                                                    $(this).dialog("close").remove();
                                                    window.parent.refreshMain();
                                                    window.parent.refreshNavigation();
                                                };
        button_options[PMA_messages['strNo']] = function() { $(this).dialog("close").remove(); }

        $(this).PMA_confirm(question, $(this).attr('action'), function(url) {
            PMA_ajaxShowMessage(PMA_messages['strRenamingDatabases']);

            $.get(url, $("#rename_db_form").serialize() + '&is_js_confirmed=1', function(data) {
                if(data.success == true) {
                    
                    PMA_ajaxShowMessage(data.message);

                    window.parent.db = data.newname;

                    $("#topmenucontainer")
                    .next('div')
                    .remove()
                    .end()
                    .after(data.sql_query);

                    //Remove the empty notice div generated due to a NULL query passed to PMA_showMessage()
                    var notice_class = $("#topmenucontainer").next("div").find('.notice');
                    if($(notice_class).text() == '') {
                        $(notice_class).remove();
                    }

                    $("<span>" + PMA_messages['strReloadDatabase'] + "?</span>").dialog({
                        buttons: button_options
                    }) //end dialog options
                }
                else {
                    PMA_ajaxShowMessage(data.error);
                }
            }) // end $.get()
        })
    }); // end Rename Database

    /**
     * Ajax Event Handler for 'Copy Database'
     *
     * @uses    PMA_ajaxShowMessage()
     */
    $("#copy_db_form").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strCopyingDatabase']);
        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        $.get($(this).attr('action'), $(this).serialize(), function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
                if( $("#checkbox_switch").is(":checked")) {
                    window.parent.db = data.newname;
                    window.parent.refreshMain();
                    window.parent.refreshNavigation();
               }
            }
            else {
                PMA_ajaxShowMessage(data.error);
            }
        }) // end $.get
    }) // end copy database

    /**
     * Ajax Event handler for 'Change Charset' of the database
     *
     * @uses    PMA_ajaxShowMessage()
     */
    $("#change_db_charset_form").live('submit', function(event) {
        event.preventDefault();

        $(this).append('<input type="hidden" name="ajax_request" value="true" />');

        PMA_ajaxShowMessage(PMA_messages['strChangingCharset']);

        $.get($(this).attr('action'), $(this).serialize() + "&submitcollation=" + $(this).find("input[name=submitcollation]").attr('value'), function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
            }
            else {
                PMA_ajaxShowMessage(data.error);
            }
        }) // end $.get()
    }) // end change charset
    
}, 'top.frame_content');
