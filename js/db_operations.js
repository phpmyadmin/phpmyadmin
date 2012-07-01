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

$(function() {

    /**
     * Ajax event handlers for 'Rename Database'
     *
     * @see     $cfg['AjaxEnable']
     */
    $("#rename_db_form.ajax").live('submit', function(event) {
        event.preventDefault();

        var $form = $(this);

        var question = escapeHtml('CREATE DATABASE ' + $('#new_db_name').val() + ' / DROP DATABASE ' + window.parent.db);

        PMA_prepareForAjaxRequest($form);
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

        $form.PMA_confirm(question, $form.attr('action'), function(url) {
            PMA_ajaxShowMessage(PMA_messages['strRenamingDatabases'], false);

            $.get(url, $("#rename_db_form").serialize() + '&is_js_confirmed=1', function(data) {
                if(data.success == true) {

                    PMA_ajaxShowMessage(data.message);

                    window.parent.db = data.newname;

                    $("#floating_menubar")
                    .next('div')
                    .remove()
                    .end()
                    .after(data.sql_query);

                    //Remove the empty notice div generated due to a NULL query passed to CommonFunctions::getMessage()
                    var $notice_class = $("#floating_menubar").next("div").find('.notice');
                    if ($notice_class.text() == '') {
                        $notice_class.remove();
                    }

                    $("<span>" + PMA_messages['strReloadDatabase'] + "?</span>").dialog({
                        buttons: button_options
                    }) //end dialog options
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }) // end $.get()
        })
    }); // end Rename Database

    /**
     * Ajax Event Handler for 'Copy Database'
     *
     * @see     $cfg['AjaxEnable']
     */
    $("#copy_db_form.ajax").live('submit', function(event) {
        event.preventDefault();

        PMA_ajaxShowMessage(PMA_messages['strCopyingDatabase'], false);

        var $form = $(this);

        PMA_prepareForAjaxRequest($form);

        $.get($form.attr('action'), $form.serialize(), function(data) {
            // use messages that stay on screen
            $('div.success, div.error').fadeOut();
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
                if( $("#checkbox_switch").is(":checked")) {
                    window.parent.db = data.newname;
                    window.parent.refreshMain();
                    window.parent.refreshNavigation();
               } else {
                    // Here we force a refresh because the navigation
                    // frame url is not changing so this function would
                    // not refresh it
                    window.parent.refreshNavigation(true);
               }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }) // end $.get
    }) // end copy database

    /**
     * Ajax Event handler for 'Change Charset' of the database
     *
     * @see     $cfg['AjaxEnable']
     */
    $("#change_db_charset_form.ajax").live('submit', function(event) {
        event.preventDefault();

        var $form = $(this);

        PMA_prepareForAjaxRequest($form);

        PMA_ajaxShowMessage(PMA_messages['strChangingCharset']);
        $.get($form.attr('action'), $form.serialize() + "&submitcollation=" + $form.find("input[name=submitcollation]").val(), function(data) {
            if(data.success == true) {
                PMA_ajaxShowMessage(data.message);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }) // end $.get()
    }) // end change charset

}, 'top.frame_content');
