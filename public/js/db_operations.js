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
 * Change Charset
 * Drop Database
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('db_operations.js', function () {
    $(document).off('submit', '#rename_db_form.ajax');
    $(document).off('submit', '#copy_db_form.ajax');
    $(document).off('submit', '#change_db_charset_form.ajax');
    $(document).off('click', '#drop_db_anchor.ajax');
});

AJAX.registerOnload('db_operations.js', function () {
    /**
     * Ajax event handlers for 'Rename Database'
     */
    $(document).on('submit', '#rename_db_form.ajax', function (event) {
        event.preventDefault();

        var old_db_name = PMA_commonParams.get('db');
        var new_db_name = $('#new_db_name').val();

        if (new_db_name === old_db_name) {
            PMA_ajaxShowMessage(PMA_messages.strDatabaseRenameToSameName, false, 'error');
            return false;
        }

        var $form = $(this);

        var question = escapeHtml('CREATE DATABASE ' + new_db_name + ' / DROP DATABASE ' + old_db_name);

        PMA_prepareForAjaxRequest($form);

        $form.PMA_confirm(question, $form.attr('action'), function (url) {
            PMA_ajaxShowMessage(PMA_messages.strRenamingDatabases, false);
            $.post(url, $('#rename_db_form').serialize() + PMA_commonParams.get('arg_separator') + 'is_js_confirmed=1', function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    PMA_commonParams.set('db', data.newname);

                    PMA_reloadNavigation(function () {
                        $('#pma_navigation_tree')
                            .find('a:not(\'.expander\')')
                            .each(function (index) {
                                var $thisAnchor = $(this);
                                if ($thisAnchor.text() === data.newname) {
                                    // simulate a click on the new db name
                                    // in navigation
                                    $thisAnchor.trigger('click');
                                }
                            });
                    });
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }); // end $.post()
        });
    }); // end Rename Database

    /**
     * Ajax Event Handler for 'Copy Database'
     */
    $(document).on('submit', '#copy_db_form.ajax', function (event) {
        event.preventDefault();
        PMA_ajaxShowMessage(PMA_messages.strCopyingDatabase, false);
        var $form = $(this);
        PMA_prepareForAjaxRequest($form);
        $.post($form.attr('action'), $form.serialize(), function (data) {
            // use messages that stay on screen
            $('div.success, div.error').fadeOut();
            if (typeof data !== 'undefined' && data.success === true) {
                if ($('#checkbox_switch').is(':checked')) {
                    PMA_commonParams.set('db', data.newname);
                    PMA_commonActions.refreshMain(false, function () {
                        PMA_ajaxShowMessage(data.message);
                    });
                } else {
                    PMA_commonParams.set('db', data.db);
                    PMA_ajaxShowMessage(data.message);
                }
                PMA_reloadNavigation();
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.post()
    }); // end copy database

    /**
     * Change tables columns visible only if change tables is checked
     */
    $('#span_change_all_tables_columns_collations').hide();
    $('#checkbox_change_all_tables_collations').on('click', function () {
        $('#span_change_all_tables_columns_collations').toggle();
    });

    /**
     * Ajax Event handler for 'Change Charset' of the database
     */
    $(document).on('submit', '#change_db_charset_form.ajax', function (event) {
        event.preventDefault();
        var $form = $(this);
        PMA_prepareForAjaxRequest($form);
        PMA_ajaxShowMessage(PMA_messages.strChangingCharset);
        $.post($form.attr('action'), $form.serialize() + PMA_commonParams.get('arg_separator') + 'submitcollation=1', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxShowMessage(data.message);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.post()
    }); // end change charset

    /**
     * Ajax event handlers for Drop Database
     */
    $(document).on('click', '#drop_db_anchor.ajax', function (event) {
        event.preventDefault();
        var $link = $(this);
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages.strDropDatabaseStrongWarning + ' ';
        question += PMA_sprintf(
            PMA_messages.strDoYouReally,
            'DROP DATABASE `' + escapeHtml(PMA_commonParams.get('db') + '`')
        );
        var params = getJSConfirmCommonParam(this, $link.getPostData());

        $(this).PMA_confirm(question, $(this).attr('href'), function (url) {
            PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success) {
                    // Database deleted successfully, refresh both the frames
                    PMA_reloadNavigation();
                    PMA_commonParams.set('db', '');
                    PMA_commonActions.refreshMain(
                        'server_databases.php',
                        function () {
                            PMA_ajaxShowMessage(data.message);
                        }
                    );
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            });
        });
    });
});
