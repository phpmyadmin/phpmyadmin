/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used for index manipulation pages
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * Returns the array of indexes based on the index choice
 *
 * @param index_choice index choice
 */
function PMA_getIndexArray (index_choice) {
    var source_array = null;

    switch (index_choice.toLowerCase()) {
    case 'primary':
        source_array = primary_indexes;
        break;
    case 'unique':
        source_array = unique_indexes;
        break;
    case 'index':
        source_array = indexes;
        break;
    case 'fulltext':
        source_array = fulltext_indexes;
        break;
    case 'spatial':
        source_array = spatial_indexes;
        break;
    default:
        return null;
    }
    return source_array;
}

/**
 * Hides/shows the inputs and submits appropriately depending
 * on whether the index type chosen is 'SPATIAL' or not.
 */
function checkIndexType () {
    /**
     * @var Object Dropdown to select the index choice.
     */
    var $select_index_choice = $('#select_index_choice');
    /**
     * @var Object Dropdown to select the index type.
     */
    var $select_index_type = $('#select_index_type');
    /**
     * @var Object Table header for the size column.
     */
    var $size_header = $('#index_columns').find('thead tr th:nth-child(2)');
    /**
     * @var Object Inputs to specify the columns for the index.
     */
    var $column_inputs = $('select[name="index[columns][names][]"]');
    /**
     * @var Object Inputs to specify sizes for columns of the index.
     */
    var $size_inputs = $('input[name="index[columns][sub_parts][]"]');
    /**
     * @var Object Footer containg the controllers to add more columns
     */
    var $add_more = $('#index_frm').find('.add_more');

    if ($select_index_choice.val() === 'SPATIAL') {
        // Disable and hide the size column
        $size_header.hide();
        $size_inputs.each(function () {
            $(this)
                .prop('disabled', true)
                .parent('td').hide();
        });

        // Disable and hide the columns of the index other than the first one
        var initial = true;
        $column_inputs.each(function () {
            $column_input = $(this);
            if (! initial) {
                $column_input
                    .prop('disabled', true)
                    .parent('td').hide();
            } else {
                initial = false;
            }
        });

        // Hide controllers to add more columns
        $add_more.hide();
    } else {
        // Enable and show the size column
        $size_header.show();
        $size_inputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Enable and show the columns of the index
        $column_inputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Show controllers to add more columns
        $add_more.show();
    }

    if ($select_index_choice.val() === 'SPATIAL' ||
            $select_index_choice.val() === 'FULLTEXT') {
        $select_index_type.val('').prop('disabled', true);
    } else {
        $select_index_type.prop('disabled', false);
    }
}

/**
 * Sets current index information into form parameters.
 *
 * @param array  source_array Array containing index columns
 * @param string index_choice Choice of index
 *
 * @return void
 */
function PMA_setIndexFormParameters (source_array, index_choice) {
    if (index_choice === 'index') {
        $('input[name="indexes"]').val(JSON.stringify(source_array));
    } else {
        $('input[name="' + index_choice + '_indexes"]').val(JSON.stringify(source_array));
    }
}

/**
 * Removes a column from an Index.
 *
 * @param string col_index Index of column in form
 *
 * @return void
 */
function PMA_removeColumnFromIndex (col_index) {
    // Get previous index details.
    var previous_index = $('select[name="field_key[' + col_index + ']"]')
        .attr('data-index');
    if (previous_index.length) {
        previous_index = previous_index.split(',');
        var source_array = PMA_getIndexArray(previous_index[0]);
        if (source_array === null) {
            return;
        }

        // Remove column from index array.
        var source_length = source_array[previous_index[1]].columns.length;
        for (var i = 0; i < source_length; i++) {
            if (source_array[previous_index[1]].columns[i].col_index === col_index) {
                source_array[previous_index[1]].columns.splice(i, 1);
            }
        }

        // Remove index completely if no columns left.
        if (source_array[previous_index[1]].columns.length === 0) {
            source_array.splice(previous_index[1], 1);
        }

        // Update current index details.
        $('select[name="field_key[' + col_index + ']"]').attr('data-index', '');
        // Update form index parameters.
        PMA_setIndexFormParameters(source_array, previous_index[0].toLowerCase());
    }
}

/**
 * Adds a column to an Index.
 *
 * @param array  source_array Array holding corresponding indexes
 * @param string array_index  Index of an INDEX in array
 * @param string index_choice Choice of Index
 * @param string col_index    Index of column on form
 *
 * @return void
 */
function PMA_addColumnToIndex (source_array, array_index, index_choice, col_index) {
    if (col_index >= 0) {
        // Remove column from other indexes (if any).
        PMA_removeColumnFromIndex(col_index);
    }
    var index_name = $('input[name="index[Key_name]"]').val();
    var index_comment = $('input[name="index[Index_comment]"]').val();
    var key_block_size = $('input[name="index[Key_block_size]"]').val();
    var parser = $('input[name="index[Parser]"]').val();
    var index_type = $('select[name="index[Index_type]"]').val();
    var columns = [];
    $('#index_columns').find('tbody').find('tr').each(function () {
        // Get columns in particular order.
        var col_index = $(this).find('select[name="index[columns][names][]"]').val();
        var size = $(this).find('input[name="index[columns][sub_parts][]"]').val();
        columns.push({
            'col_index': col_index,
            'size': size
        });
    });

    // Update or create an index.
    source_array[array_index] = {
        'Key_name': index_name,
        'Index_comment': index_comment,
        'Index_choice': index_choice.toUpperCase(),
        'Key_block_size': key_block_size,
        'Parser': parser,
        'Index_type': index_type,
        'columns': columns
    };

    // Display index name (or column list)
    var displayName = index_name;
    if (displayName === '') {
        var columnNames = [];
        $.each(columns, function () {
            columnNames.push($('input[name="field_name[' +  this.col_index + ']"]').val());
        });
        displayName = '[' + columnNames.join(', ') + ']';
    }
    $.each(columns, function () {
        var id = 'index_name_' + this.col_index + '_8';
        var $name = $('#' + id);
        if ($name.length === 0) {
            $name = $('<a id="' + id + '" href="#" class="ajax show_index_dialog"></a>');
            $name.insertAfter($('select[name="field_key[' + this.col_index + ']"]'));
        }
        var $text = $('<small>').text(displayName);
        $name.html($text);
    });

    if (col_index >= 0) {
        // Update index details on form.
        $('select[name="field_key[' + col_index + ']"]')
            .attr('data-index', index_choice + ',' + array_index);
    }
    PMA_setIndexFormParameters(source_array, index_choice.toLowerCase());
}

/**
 * Get choices list for a column to create a composite index with.
 *
 * @param string index_choice Choice of index
 * @param array  source_array Array hodling columns for particular index
 *
 * @return jQuery Object
 */
function PMA_getCompositeIndexList (source_array, col_index) {
    // Remove any previous list.
    if ($('#composite_index_list').length) {
        $('#composite_index_list').remove();
    }

    // Html list.
    var $composite_index_list = $(
        '<ul id="composite_index_list">' +
        '<div>' + PMA_messages.strCompositeWith + '</div>' +
        '</ul>'
    );

    // Add each column to list available for composite index.
    var source_length = source_array.length;
    var already_present = false;
    for (var i = 0; i < source_length; i++) {
        var sub_array_len = source_array[i].columns.length;
        var column_names = [];
        for (var j = 0; j < sub_array_len; j++) {
            column_names.push(
                $('input[name="field_name[' + source_array[i].columns[j].col_index + ']"]').val()
            );

            if (col_index === source_array[i].columns[j].col_index) {
                already_present = true;
            }
        }

        $composite_index_list.append(
            '<li>' +
            '<input type="radio" name="composite_with" ' +
            (already_present ? 'checked="checked"' : '') +
            ' id="composite_index_' + i + '" value="' + i + '">' +
            '<label for="composite_index_' + i + '">' + column_names.join(', ') +
            '</lablel>' +
            '</li>'
        );
    }

    return $composite_index_list;
}

/**
 * Shows 'Add Index' dialog.
 *
 * @param array  source_array   Array holding particluar index
 * @param string array_index    Index of an INDEX in array
 * @param array  target_columns Columns for an INDEX
 * @param string col_index      Index of column on form
 * @param object index          Index detail object
 *
 * @return void
 */
function PMA_showAddIndexDialog (source_array, array_index, target_columns, col_index, index) {
    // Prepare post-data.
    var $table = $('input[name="table"]');
    var table = $table.length > 0 ? $table.val() : '';
    var post_data = {
        server: PMA_commonParams.get('server'),
        db: $('input[name="db"]').val(),
        table: table,
        ajax_request: 1,
        create_edit_table: 1,
        index: index
    };

    var columns = {};
    for (var i = 0; i < target_columns.length; i++) {
        var column_name = $('input[name="field_name[' + target_columns[i] + ']"]').val();
        var column_type = $('select[name="field_type[' + target_columns[i] + ']"]').val().toLowerCase();
        columns[column_name] = [column_type, target_columns[i]];
    }
    post_data.columns = JSON.stringify(columns);

    var button_options = {};
    button_options[PMA_messages.strGo] = function () {
        var is_missing_value = false;
        $('select[name="index[columns][names][]"]').each(function () {
            if ($(this).val() === '') {
                is_missing_value = true;
            }
        });

        if (! is_missing_value) {
            PMA_addColumnToIndex(
                source_array,
                array_index,
                index.Index_choice,
                col_index
            );
        } else {
            PMA_ajaxShowMessage(
                '<div class="error"><img src="themes/dot.gif" title="" alt=""' +
                ' class="icon ic_s_error" /> ' + PMA_messages.strMissingColumn +
                ' </div>', false
            );

            return false;
        }

        $(this).dialog('close');
    };
    button_options[PMA_messages.strCancel] = function () {
        if (col_index >= 0) {
            // Handle state on 'Cancel'.
            var $select_list = $('select[name="field_key[' + col_index + ']"]');
            if (! $select_list.attr('data-index').length) {
                $select_list.find('option[value*="none"]').attr('selected', 'selected');
            } else {
                var previous_index = $select_list.attr('data-index').split(',');
                $select_list.find('option[value*="' + previous_index[0].toLowerCase() + '"]')
                    .attr('selected', 'selected');
            }
        }
        $(this).dialog('close');
    };
    var $msgbox = PMA_ajaxShowMessage();
    $.post('tbl_indexes.php', post_data, function (data) {
        if (data.success === false) {
            // in the case of an error, show the error message returned.
            PMA_ajaxShowMessage(data.error, false);
        } else {
            PMA_ajaxRemoveMessage($msgbox);
            // Show dialog if the request was successful
            var $div = $('<div/>');
            $div
                .append(data.message)
                .dialog({
                    title: PMA_messages.strAddIndex,
                    width: 450,
                    minHeight: 250,
                    open: function () {
                        checkIndexName('index_frm');
                        PMA_showHints($div);
                        PMA_init_slider();
                        $('#index_columns').find('td').each(function () {
                            $(this).css('width', $(this).width() + 'px');
                        });
                        $('#index_columns').find('tbody').sortable({
                            axis: 'y',
                            containment: $('#index_columns').find('tbody'),
                            tolerance: 'pointer'
                        });
                        // We dont need the slider at this moment.
                        $(this).find('fieldset.tblFooters').remove();
                    },
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
        }
    });
}

/**
 * Creates a advanced index type selection dialog.
 *
 * @param array  source_array Array holding a particular type of indexes
 * @param string index_choice Choice of index
 * @param string col_index    Index of new column on form
 *
 * @return void
 */
function PMA_indexTypeSelectionDialog (source_array, index_choice, col_index) {
    var $single_column_radio = $('<input type="radio" id="single_column" name="index_choice"' +
        ' checked="checked">' +
        '<label for="single_column">' + PMA_messages.strCreateSingleColumnIndex + '</label>');
    var $composite_index_radio = $('<input type="radio" id="composite_index"' +
        ' name="index_choice">' +
        '<label for="composite_index">' + PMA_messages.strCreateCompositeIndex + '</label>');
    var $dialog_content = $('<fieldset id="advance_index_creator"></fieldset>');
    $dialog_content.append('<legend>' + index_choice.toUpperCase() + '</legend>');


    // For UNIQUE/INDEX type, show choice for single-column and composite index.
    $dialog_content.append($single_column_radio);
    $dialog_content.append($composite_index_radio);

    var button_options = {};
    // 'OK' operation.
    button_options[PMA_messages.strGo] = function () {
        if ($('#single_column').is(':checked')) {
            var index = {
                'Key_name': (index_choice === 'primary' ? 'PRIMARY' : ''),
                'Index_choice': index_choice.toUpperCase()
            };
            PMA_showAddIndexDialog(source_array, (source_array.length), [col_index], col_index, index);
        }

        if ($('#composite_index').is(':checked')) {
            if ($('input[name="composite_with"]').length !== 0 && $('input[name="composite_with"]:checked').length === 0
            ) {
                PMA_ajaxShowMessage(
                    '<div class="error"><img src="themes/dot.gif" title=""' +
                    ' alt="" class="icon ic_s_error" /> ' +
                    PMA_messages.strFormEmpty +
                    ' </div>',
                    false
                );
                return false;
            }

            var array_index = $('input[name="composite_with"]:checked').val();
            var source_length = source_array[array_index].columns.length;
            var target_columns = [];
            for (var i = 0; i < source_length; i++) {
                target_columns.push(source_array[array_index].columns[i].col_index);
            }
            target_columns.push(col_index);

            PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index,
                source_array[array_index]);
        }

        $(this).remove();
    };
    button_options[PMA_messages.strCancel] = function () {
        // Handle state on 'Cancel'.
        var $select_list = $('select[name="field_key[' + col_index + ']"]');
        if (! $select_list.attr('data-index').length) {
            $select_list.find('option[value*="none"]').attr('selected', 'selected');
        } else {
            var previous_index = $select_list.attr('data-index').split(',');
            $select_list.find('option[value*="' + previous_index[0].toLowerCase() + '"]')
                .attr('selected', 'selected');
        }
        $(this).remove();
    };
    var $dialog = $('<div/>').append($dialog_content).dialog({
        minWidth: 525,
        minHeight: 200,
        modal: true,
        title: PMA_messages.strAddIndex,
        resizable: false,
        buttons: button_options,
        open: function () {
            $('#composite_index').on('change', function () {
                if ($(this).is(':checked')) {
                    $dialog_content.append(PMA_getCompositeIndexList(source_array, col_index));
                }
            });
            $('#single_column').on('change', function () {
                if ($(this).is(':checked')) {
                    if ($('#composite_index_list').length) {
                        $('#composite_index_list').remove();
                    }
                }
            });
        },
        close: function () {
            $('#composite_index').off('change');
            $('#single_column').off('change');
            $(this).remove();
        }
    });
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('indexes.js', function () {
    $(document).off('click', '#save_index_frm');
    $(document).off('click', '#preview_index_frm');
    $(document).off('change', '#select_index_choice');
    $(document).off('click', 'a.drop_primary_key_index_anchor.ajax');
    $(document).off('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax');
    $(document).off('click', '#index_frm input[type=submit]');
    $('body').off('change', 'select[name*="field_key"]');
    $(document).off('click', '.show_index_dialog');
});

/**
 * @description <p>Ajax scripts for table index page</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Showing/hiding inputs depending on the index type chosen</li>
 * <li>create/edit/drop indexes</li>
 * </ul>
 */
AJAX.registerOnload('indexes.js', function () {
    // Re-initialize variables.
    primary_indexes = [];
    unique_indexes = [];
    indexes = [];
    fulltext_indexes = [];
    spatial_indexes = [];

    // for table creation form
    var $engine_selector = $('.create_table_form select[name=tbl_storage_engine]');
    if ($engine_selector.length) {
        PMA_hideShowConnection($engine_selector);
    }

    var $form = $('#index_frm');
    if ($form.length > 0) {
        showIndexEditDialog($form);
    }

    $(document).on('click', '#save_index_frm', function (event) {
        event.preventDefault();
        var $form = $('#index_frm');
        var argsep = PMA_commonParams.get('arg_separator');
        var submitData = $form.serialize() + argsep + 'do_save_data=1' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    $(document).on('click', '#preview_index_frm', function (event) {
        event.preventDefault();
        PMA_previewSQL($('#index_frm'));
    });

    $(document).on('change', '#select_index_choice', function (event) {
        event.preventDefault();
        checkIndexType();
        checkIndexName('index_frm');
    });

    /**
     * Ajax Event handler for 'Drop Index'
     */
    $(document).on('click', 'a.drop_primary_key_index_anchor.ajax', function (event) {
        event.preventDefault();
        var $anchor = $(this);
        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = escapeHtml(
            $curr_row.children('td')
                .children('.drop_primary_key_index_msg')
                .val()
        );

        PMA_confirmPreviewSQL(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingPrimaryKeyIndex, false);
            var params = getJSConfirmCommonParam(this, $anchor.getPostData());
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var $table_ref = $rows_to_hide.closest('table');
                    if ($rows_to_hide.length === $table_ref.find('tbody > tr').length) {
                        // We are about to remove all rows from the table
                        $table_ref.hide('medium', function () {
                            $('div.no_indexes_defined').show('medium');
                            $rows_to_hide.remove();
                        });
                        $table_ref.siblings('div.notice').hide('medium');
                    } else {
                        // We are removing some of the rows only
                        $rows_to_hide.hide('medium', function () {
                            $(this).remove();
                        });
                    }
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        PMA_highlightSQL($('#page_content'));
                    }
                    PMA_commonActions.refreshMain(false, function () {
                        $('a.ajax[href^=#indexes]').click();
                    });
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }); // end $.PMA_confirm()
    }); // end Drop Primary Key/Index

    /**
     *Ajax event handler for index edit
    **/
    $(document).on('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax', function (event) {
        event.preventDefault();
        var url;
        var title;
        if ($(this).find('a').length === 0) {
            // Add index
            var valid = checkFormElementInRange(
                $(this).closest('form')[0],
                'added_fields',
                'Column count has to be larger than zero.'
            );
            if (! valid) {
                return;
            }
            url = $(this).closest('form').serialize();
            title = PMA_messages.strAddIndex;
        } else {
            // Edit index
            url = $(this).find('a').getPostData();
            title = PMA_messages.strEditIndex;
        }
        url += PMA_commonParams.get('arg_separator') + 'ajax_request=true';
        indexEditorDialog(url, title, function () {
            // refresh the page using ajax
            PMA_commonActions.refreshMain(false, function () {
                $('a.ajax[href^=#indexes]').click();
            });
        });
    });

    /**
     * Ajax event handler for advanced index creation during table creation
     * and column addition.
     */
    $('body').on('change', 'select[name*="field_key"]', function () {
        // Index of column on Table edit and create page.
        var col_index = /\d+/.exec($(this).attr('name'));
        col_index = col_index[0];
        // Choice of selected index.
        var index_choice = /[a-z]+/.exec($(this).val());
        index_choice = index_choice[0];
        // Array containing corresponding indexes.
        var source_array = null;

        if (index_choice === 'none') {
            PMA_removeColumnFromIndex(col_index);
            var id = 'index_name_' + '0' + '_8';
            var $name = $('#' + id);
            if ($name.length === 0) {
                $name = $('<a id="' + id + '" href="#" class="ajax show_index_dialog"></a>');
                $name.insertAfter($('select[name="field_key[' + '0' + ']"]'));
            }
            $name.html('');
            return false;
        }

        // Select a source array.
        source_array = PMA_getIndexArray(index_choice);
        if (source_array === null) {
            return;
        }

        if (source_array.length === 0) {
            var index = {
                'Key_name': (index_choice === 'primary' ? 'PRIMARY' : ''),
                'Index_choice': index_choice.toUpperCase()
            };
            PMA_showAddIndexDialog(source_array, 0, [col_index], col_index, index);
        } else {
            if (index_choice === 'primary') {
                var array_index = 0;
                var source_length = source_array[array_index].columns.length;
                var target_columns = [];
                for (var i = 0; i < source_length; i++) {
                    target_columns.push(source_array[array_index].columns[i].col_index);
                }
                target_columns.push(col_index);

                PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index,
                    source_array[array_index]);
            } else {
                // If there are multiple columns selected for an index, show advanced dialog.
                PMA_indexTypeSelectionDialog(source_array, index_choice, col_index);
            }
        }
    });

    $(document).on('click', '.show_index_dialog', function (e) {
        e.preventDefault();

        // Get index details.
        var previous_index = $(this).prev('select')
            .attr('data-index')
            .split(',');

        var index_choice = previous_index[0];
        var array_index  = previous_index[1];

        var source_array = PMA_getIndexArray(index_choice);
        if (source_array !== null) {
            var source_length = source_array[array_index].columns.length;

            var target_columns = [];
            for (var i = 0; i < source_length; i++) {
                target_columns.push(source_array[array_index].columns[i].col_index);
            }

            PMA_showAddIndexDialog(source_array, array_index, target_columns, -1, source_array[array_index]);
        }
    });

    $('#index_frm').on('submit', function () {
        if (typeof(this.elements['index[Key_name]'].disabled) !== 'undefined') {
            this.elements['index[Key_name]'].disabled = false;
        }
    });
});
