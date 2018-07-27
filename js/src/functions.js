import { AJAX } from './ajax';
import { $ } from './utils/JqueryExtended';
import CommonParams from './variables/common_params';
import { PMA_Messages as PMA_messages } from './variables/export_variables';
import { PMA_prepareForAjaxRequest } from './functions/AjaxRequest';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from './utils/show_ajax_messages';
import PMA_MicroHistory from './classes/MicroHistory';
import { PMA_commonActions } from './classes/CommonActions';

// Sql based imports
import { PMA_getSQLEditor, bindCodeMirrorToInlineEditor } from './functions/Sql/SqlEditor';
import { sqlQueryOptions, updateQueryParameters, PMA_highlightSQL } from './utils/sql';
import { PMA_handleSimulateQueryButton, insertQuery, checkSqlQuery } from './functions/Sql/SqlQuery';
import { escapeHtml } from './utils/Sanitise';
import { getForeignKeyCheckboxLoader, loadForeignKeyCheckbox } from './functions/Sql/ForeignKey';
import { PMA_previewSQL } from './functions/Sql/PreviewSql';

// Create Table based imports
import { checkTableEditForm, PMA_checkReservedWordColumns } from './functions/Table/CreateTable';
import { PMA_adjustTotals } from './functions/Database/Structure';
import { PMA_verifyColumnsProperties, PMA_hideShowConnection } from './functions/Table/TableColumns';

/**
 * Here we register a function that will remove the onsubmit event from all
 * forms that will be handled by the generic page loader. We then save this
 * event handler in the "jQuery data", so that we can fire it up later in
 * AJAX.requestHandler().
 *
 * See bug #3583316
 */
export function onload () {
    // Registering the onload event for functions.js
    // ensures that it will be fired for all pages
    $('form').not('.ajax').not('.disableAjax').each(function () {
        if ($(this).attr('onsubmit')) {
            $(this).data('onsubmit', this.onsubmit).attr('onsubmit', '');
        }
    });

    var $page_content = $('#page_content');
    /**
     * Workaround for passing submit button name,value on ajax form submit
     * by appending hidden element with submit button name and value.
     */
    $page_content.on('click', 'form input[type=submit]', function () {
        var buttonName = $(this).attr('name');
        if (typeof buttonName === 'undefined') {
            return;
        }
        $(this).closest('form').append($('<input/>', {
            'type' : 'hidden',
            'name' : buttonName,
            'value': $(this).val()
        }));
    });

    /**
     * Attach event listener to events when user modify visible
     * Input,Textarea and select fields to make changes in forms
     */
    $page_content.on(
        'keyup change',
        'form.lock-page textarea, ' +
        'form.lock-page input[type="text"], ' +
        'form.lock-page input[type="number"], ' +
        'form.lock-page select',
        { value:1 },
        AJAX.lockPageHandler
    );
    $page_content.on(
        'change',
        'form.lock-page input[type="checkbox"], ' +
        'form.lock-page input[type="radio"]',
        { value:2 },
        AJAX.lockPageHandler
    );
    /**
     * Reset lock when lock-page form reset event is fired
     * Note: reset does not bubble in all browser so attach to
     * form directly.
     */
    $('form.lock-page').on('reset', function (event) {
        AJAX.resetLock();
    });
}
/**
 * Unbind all event handlers before tearing down a page
 */
export function teardown1 () {
    $(document).off('click', 'a.themeselect');
    $(document).off('change', '.autosubmit');
    $('a.take_theme').off('click');
}

export function onload1 () {
    /**
     * Theme selector.
     */
    $(document).on('click', 'a.themeselect', function (e) {
        window.open(
            e.target,
            'themes',
            'left=10,top=20,width=510,height=350,scrollbars=yes,status=yes,resizable=yes'
        );
        return false;
    });

    /**
     * Automatic form submission on change.
     */
    $(document).on('change', '.autosubmit', function (e) {
        $(this).closest('form').submit();
    });

    /**
     * Theme changer.
     */
    $('a.take_theme').on('click', function (e) {
        var what = this.name;
        if (window.opener && window.opener.document.forms.setTheme.elements.set_theme) {
            window.opener.document.forms.setTheme.elements.set_theme.value = what;
            window.opener.document.forms.setTheme.submit();
            window.close();
            return false;
        }
        return true;
    });
}

/* *************************************** CODEMIRROR EDITOR START *************************************** */
/**
 * Attach CodeMirror2 editor to SQL edit area.
 */
export function onloadSqlEditor () {
    var $elm = $('#sqlquery');
    if ($elm.length > 0) {
        if (CommonParams.get('CodemirrorEnable') === true) {
            sqlQueryOptions.codemirror_editor = PMA_getSQLEditor($elm);
            sqlQueryOptions.codemirror_editor.focus();
            sqlQueryOptions.codemirror_editor.on('blur', updateQueryParameters);
        } else {
            // without codemirror
            $elm.focus().on('blur', updateQueryParameters);
        }
    }
    PMA_highlightSQL($('body'));
}
export function teardownSqlEditor () {
    if (sqlQueryOptions.codemirror_editor) {
        $('#sqlquery').text(sqlQueryOptions.codemirror_editor.getValue());
        sqlQueryOptions.codemirror_editor.toTextArea();
        sqlQueryOptions.codemirror_editor = false;
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownSqlInlineEditor () {
    $(document).off('click', 'a.inline_edit_sql');
    $(document).off('click', 'input#sql_query_edit_save');
    $(document).off('click', 'input#sql_query_edit_discard');
    $('input.sqlbutton').off('click');
    if (sqlQueryOptions.codemirror_editor) {
        sqlQueryOptions.codemirror_editor.off('blur');
    } else {
        $(document).off('blur', '#sqlquery');
    }
    $(document).off('change', '#parameterized');
    $(document).off('click', 'input.sqlbutton');
    $('#sqlquery').off('keydown');
    $('#sql_query_edit').off('keydown');

    if (sqlQueryOptions.codemirror_inline_editor) {
        // Copy the sql query to the text area to preserve it.
        $('#sql_query_edit').text(sqlQueryOptions.codemirror_inline_editor.getValue());
        $(sqlQueryOptions.codemirror_inline_editor.getWrapperElement()).off('keydown');
        sqlQueryOptions.codemirror_inline_editor.toTextArea();
        sqlQueryOptions.codemirror_inline_editor = false;
    }
    if (sqlQueryOptions.codemirror_editor) {
        $(sqlQueryOptions.codemirror_editor.getWrapperElement()).off('keydown');
    }
}

/**
 * Jquery Coding for inline editing SQL_QUERY
 */
export function onloadSqlInlineEditor () {
    // If we are coming back to the page by clicking forward button
    // of the browser, bind the code mirror to inline query editor.
    bindCodeMirrorToInlineEditor();
    $(document).on('click', 'a.inline_edit_sql', function () {
        if ($('#sql_query_edit').length) {
            // An inline query editor is already open,
            // we don't want another copy of it
            return false;
        }

        var $form = $(this).prev('form');
        var sql_query  = $form.find('input[name=\'sql_query\']').val().trim();
        var $inner_sql = $(this).parent().prev().find('code.sql');
        var old_text   = $inner_sql.html();

        var new_content = '<textarea name="sql_query_edit" id="sql_query_edit">' + escapeHtml(sql_query) + '</textarea>\n';
        new_content    += getForeignKeyCheckboxLoader();
        new_content    += '<input type="submit" id="sql_query_edit_save" class="button btnSave" value="' + PMA_messages.strGo + '"/>\n';
        new_content    += '<input type="button" id="sql_query_edit_discard" class="button btnDiscard" value="' + PMA_messages.strCancel + '"/>\n';
        var $editor_area = $('div#inline_editor');
        if ($editor_area.length === 0) {
            $editor_area = $('<div id="inline_editor_outer"></div>');
            $editor_area.insertBefore($inner_sql);
        }
        $editor_area.html(new_content);
        loadForeignKeyCheckbox();
        $inner_sql.hide();

        bindCodeMirrorToInlineEditor();
        return false;
    });

    $(document).on('click', 'input#sql_query_edit_save', function (e) {
        // hide already existing success message
        var sql_query;
        if (sqlQueryOptions.codemirror_inline_editor) {
            sqlQueryOptions.codemirror_inline_editor.save();
            sql_query = sqlQueryOptions.codemirror_inline_editor.getValue();
        } else {
            sql_query = $(this).parent().find('#sql_query_edit').val();
        }
        var fk_check = $(this).parent().find('#fk_checks').is(':checked');

        var $form = $('a.inline_edit_sql').prev('form');
        var $fake_form = $('<form>', { action: 'import.php', method: 'post' })
            .append($form.find('input[name=server], input[name=db], input[name=table], input[name=token]').clone())
            .append($('<input/>', { type: 'hidden', name: 'show_query', value: 1 }))
            .append($('<input/>', { type: 'hidden', name: 'is_js_confirmed', value: 0 }))
            .append($('<input/>', { type: 'hidden', name: 'sql_query', value: sql_query }))
            .append($('<input/>', { type: 'hidden', name: 'fk_checks', value: fk_check ? 1 : 0 }));
        if (! checkSqlQuery($fake_form[0])) {
            return false;
        }
        $('.success').hide();
        $fake_form.appendTo($('body')).submit();
    });

    $(document).on('click', 'input#sql_query_edit_discard', function () {
        var $divEditor = $('div#inline_editor_outer');
        $divEditor.siblings('code.sql').show();
        $divEditor.remove();
    });

    $(document).on('click', 'input.sqlbutton', function (evt) {
        insertQuery(evt.target.id);
        PMA_handleSimulateQueryButton();
        return false;
    });

    $(document).on('change', '#parameterized', updateQueryParameters);

    var $inputUsername = $('#input_username');
    if ($inputUsername) {
        if ($inputUsername.val() === '') {
            $inputUsername.trigger('focus');
        } else {
            $('#input_password').trigger('focus');
        }
    }
}
/* *************************************** CODEMIRROR EDITOR ENDS *************************************** */

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownCtrlEnterFormSubmit () {
    $(document).off('keydown', 'form input, form textarea, form select');
}

export function onloadCtrlEnterFormSubmit () {
    /**
     * Handle 'Ctrl/Alt + Enter' form submits
     */
    $('form input, form textarea, form select').on('keydown', function (e) {
        if ((e.ctrlKey && e.which === 13) || (e.altKey && e.which === 13)) {
            var $form = $(this).closest('form');
            if (! $form.find('input[type="submit"]') ||
                ! $form.find('input[type="submit"]').trigger('click')
            ) {
                $form.submit();
            }
        }
    });
}

/* *************************************** CREATE TABLE STARTS *************************************** */
/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownCreateTable () {
    $(document).off('submit', '#create_table_form_minimal.ajax');
    $(document).off('submit', 'form.create_table_form.ajax');
    $(document).off('click', 'form.create_table_form.ajax input[name=submit_num_fields]');
    $(document).off('keyup', 'form.create_table_form.ajax input');
    $(document).off('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]');
}

/**
 * jQuery coding for 'Create Table'.  Used on db_operations.php,
 * db_structure.php and db_tracking.php (i.e., wherever
 * PhpMyAdmin\Display\CreateTable is used)
 *
 * Attach Ajax Event handlers for Create Table
 */
export function onloadCreateTable () {
    /**
     * Attach event handler for submission of create table form (save)
     */
    $(document).on('submit', 'form.create_table_form.ajax', function (event) {
        event.preventDefault();

        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $(this);

        /*
         * First validate the form; if there is a problem, avoid submitting it
         *
         * checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */

        if (checkTableEditForm($form[0], $form.find('input[name=orig_num_fields]').val())) {
            PMA_prepareForAjaxRequest($form);
            if (PMA_checkReservedWordColumns($form)) {
                PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
                // User wants to submit the form
                $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#properties_message')
                            .removeClass('error')
                            .html('');
                        PMA_ajaxShowMessage(data.message);
                        // Only if the create table dialog (distinct panel) exists
                        var $createTableDialog = $('#create_table_dialog');
                        if ($createTableDialog.length > 0) {
                            $createTableDialog.dialog('close').remove();
                        }
                        $('#tableslistcontainer').before(data.formatted_sql);

                        /**
                         * @var tables_table    Object referring to the <tbody> element that holds the list of tables
                         */
                        var tables_table = $('#tablesForm').find('tbody').not('#tbl_summary_row');
                        // this is the first table created in this db
                        if (tables_table.length === 0) {
                            PMA_commonActions.refreshMain(
                                CommonParams.get('opendb_url')
                            );
                        } else {
                            /**
                             * @var curr_last_row   Object referring to the last <tr> element in {@link tables_table}
                             */
                            var curr_last_row = $(tables_table).find('tr:last');
                            /**
                             * @var curr_last_row_index_string   String containing the index of {@link curr_last_row}
                             */
                            var curr_last_row_index_string = $(curr_last_row).find('input:checkbox').attr('id').match(/\d+/)[0];
                            /**
                             * @var curr_last_row_index Index of {@link curr_last_row}
                             */
                            var curr_last_row_index = parseFloat(curr_last_row_index_string);
                            /**
                             * @var new_last_row_index   Index of the new row to be appended to {@link tables_table}
                             */
                            var new_last_row_index = curr_last_row_index + 1;
                            /**
                             * @var new_last_row_id String containing the id of the row to be appended to {@link tables_table}
                             */
                            var new_last_row_id = 'checkbox_tbl_' + new_last_row_index;

                            data.new_table_string = data.new_table_string.replace(/checkbox_tbl_/, new_last_row_id);
                            // append to table
                            $(data.new_table_string)
                                .appendTo(tables_table);

                            // Sort the table
                            $(tables_table).PMA_sort_table('th');

                            // Adjust summary row
                            PMA_adjustTotals();
                        }

                        // Refresh navigation as a new table has been added
                        PMA_reloadNavigation();
                        // Redirect to table structure page on creation of new table
                        var argsep = CommonParams.get('arg_separator');
                        var params_12 = 'ajax_request=true' + argsep + 'ajax_page_request=true';
                        if (! (history && history.pushState)) {
                            params_12 += PMA_MicroHistory.menus.getRequestParam();
                        }
                        var tblStruct_url = 'tbl_structure.php?server=' + data._params.server +
                            argsep + 'db=' + data._params.db + argsep + 'token=' + data._params.token +
                            argsep + 'goto=db_structure.php' + argsep + 'table=' + data._params.table + '';
                        $.get(tblStruct_url, params_12, AJAX.responseHandler);
                    } else {
                        PMA_ajaxShowMessage(
                            '<div class="error">' + data.error + '</div>',
                            false
                        );
                    }
                }); // end $.post()
            }
        } // end if (checkTableEditForm() )
    }); // end create table form (save)

    /**
     * Submits the intermediate changes in the table creation form
     * to refresh the UI accordingly
     */
    function submitChangesInCreateTableForm (actionParam) {
        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $('form.create_table_form.ajax');

        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        // User wants to add more fields to the table
        $.post($form.attr('action'), $form.serialize() + '&' + actionParam, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                var $pageContent = $('#page_content');
                $pageContent.html(data.message);
                PMA_highlightSQL($pageContent);
                PMA_verifyColumnsProperties();
                PMA_hideShowConnection($('.create_table_form select[name=tbl_storage_engine]'));
                PMA_ajaxRemoveMessage($msgbox);
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }); // end $.post()
    }

    /**
     * Attach event handler for create table form (add fields)
     */
    $(document).on('click', 'form.create_table_form.ajax input[name=submit_num_fields]', function (event) {
        event.preventDefault();
        submitChangesInCreateTableForm('submit_num_fields=1');
    }); // end create table form (add fields)

    $(document).on('keydown', 'form.create_table_form.ajax input[name=added_fields]', function (event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            event.stopImmediatePropagation();
            $(this)
                .closest('form')
                .find('input[name=submit_num_fields]')
                .trigger('click');
        }
    });

    /**
     * Attach event handler to manage changes in number of partitions and subpartitions
     */
    $(document).on('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]', function (event) {
        var $this = $(this);
        var $form = $this.parents('form');
        if ($form.is('.create_table_form.ajax')) {
            submitChangesInCreateTableForm('submit_partition_change=1');
        } else {
            $form.submit();
        }
    });

    $(document).on('change', 'input[value=AUTO_INCREMENT]', function () {
        if (this.checked) {
            var col = /\d/.exec($(this).attr('name'));
            col = col[0];
            var $selectFieldKey = $('select[name="field_key[' + col + ']"]');
            if ($selectFieldKey.val() === 'none_' + col) {
                $selectFieldKey.val('primary_' + col).trigger('change');
            }
        }
    });
    $('body')
        .off('click', 'input.preview_sql')
        .on('click', 'input.preview_sql', function () {
            var $form = $(this).closest('form');
            PMA_previewSQL($form);
        });
}
/* *************************************** CREATE TABLE STARTS *************************************** */
