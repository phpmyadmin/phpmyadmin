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

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('db_operations.js', function() {
    $("#rename_db_form.ajax").die('submit');
    $("#copy_db_form.ajax").die('submit');
    $("#change_db_charset_form.ajax").die('submit');
});

AJAX.registerOnload('db_operations.js', function() {

    /**
     * Ajax event handlers for 'Rename Database'
     *
     * @see     $cfg['AjaxEnable']
     */
    $("#rename_db_form.ajax").live('submit', function(event) {
        event.preventDefault();

        var $form = $(this);

        var question = escapeHtml('CREATE DATABASE ' + $('#new_db_name').val() + ' / DROP DATABASE ' + PMA_commonParams.get('db'));

        PMA_prepareForAjaxRequest($form);
        /**
         * @var button_options  Object containing options for jQueryUI dialog buttons
         */
        var button_options = {};
        button_options[PMA_messages['strYes']] = function() {
            $(this).dialog("close").remove();
            PMA_commonActions.refreshMain();
            PMA_reloadNavigation();
        };
        button_options[PMA_messages['strNo']] = function() {
            $(this).dialog("close").remove();
        }

        $form.PMA_confirm(question, $form.attr('action'), function(url) {
            PMA_ajaxShowMessage(PMA_messages['strRenamingDatabases'], false);
            $.get(url, $("#rename_db_form").serialize() + '&is_js_confirmed=1', function(data) {
                if(data.success == true) {
                    PMA_ajaxShowMessage(data.message);
                    PMA_commonParams.set('db', data.newname);

                    $("#page_content")
                    .find('#result_query')
                    .remove();
                    $("#page_content")
                    .prepend(data.sql_query);

                    //Remove the empty notice div generated due to a NULL query passed to PMA_Util::getMessage()
                    var $notice_class = $('#result_query').find('.notice');
                    if ($notice_class.text() == '') {
                        $notice_class.remove();
                    }

                    $("<span>" + PMA_messages['strReloadDatabase'] + "?</span>").dialog({
                        buttons: button_options,
                        modal: true
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
                PMA_commonParams.set('db', data.newname);
                if( $("#checkbox_switch").is(":checked")) {
                    PMA_commonParams.set('db', data.newname);
                    PMA_commonActions.refreshMain(false, function () {
                        PMA_ajaxShowMessage(data.message);
                    });
                } else {
                    PMA_ajaxShowMessage(data.message);
                }
                PMA_reloadNavigation();
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
});
