import $ from 'jquery';
import { AJAX } from './ajax.ts';
import { Functions } from './functions.ts';
import { Navigation } from './navigation.ts';
import { CommonParams } from './common.ts';
import highlightSql from './sql-highlight.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from './ajax-message.ts';
import getJsConfirmCommonParam from './functions/getJsConfirmCommonParam.ts';
import refreshMainContent from './functions/refreshMainContent.ts';
import checkIndexType from './indexes/checkIndexType.ts';
import checkIndexName from './indexes/checkIndexName.ts';

/**
 * Array to hold 'Primary' index columns.
 * @type {any[]}
 */
let primaryColumns = [];

/**
 * Array to hold 'Unique' index columns.
 * @type {any[]}
 */
let uniqueColumns = [];

/**
 * Array to hold 'Index' columns.
 * @type {any[]}
 */
let indexColumns = [];

/**
 * Array to hold 'Fulltext' columns.
 * @type {any[]}
 */
let fulltextColumns = [];

/**
 * Array to hold 'Spatial' columns.
 * @type {any[]}
 */
let spatialColumns = [];

function resetColumnLists (): void {
    primaryColumns = [];
    uniqueColumns = [];
    indexColumns = [];
    fulltextColumns = [];
    spatialColumns = [];
}

/**
 * Returns the array of indexes based on the index choice
 *
 * @param {string} indexChoice index choice
 *
 * @return {null|object}
 */
function getIndexArray (indexChoice) {
    let sourceArray = null;

    switch (indexChoice.toLowerCase()) {
    case 'primary':
        sourceArray = primaryColumns;
        break;
    case 'unique':
        sourceArray = uniqueColumns;
        break;
    case 'index':
        sourceArray = indexColumns;
        break;
    case 'fulltext':
        sourceArray = fulltextColumns;
        break;
    case 'spatial':
        sourceArray = spatialColumns;
        break;
    default:
        return null;
    }

    return sourceArray;
}

/**
 * Sets current index information into form parameters.
 *
 * @param {any[]}  sourceArray Array containing index columns
 * @param {string} indexChoice Choice of index
 */
function setIndexFormParameters (sourceArray, indexChoice): void {
    if (indexChoice === 'index') {
        $('input[name="indexes"]').val(JSON.stringify(sourceArray));
    } else {
        $('input[name="' + indexChoice + '_indexes"]').val(JSON.stringify(sourceArray));
    }
}

/**
 * Removes a column from an Index.
 *
 * @param {string} colIndex Index of column in form
 */
function removeColumnFromIndex (colIndex): void {
    // Get previous index details.
    var previousIndex = $('select[name="field_key[' + colIndex + ']"]')
        .attr('data-index');
    if (previousIndex.length) {
        const previousIndexes = previousIndex.split(',');
        var sourceArray = Indexes.getIndexArray(previousIndexes[0]);
        if (sourceArray === null) {
            return;
        }

        // Remove column from index array.
        var sourceLength = sourceArray[previousIndexes[1]].columns.length;
        for (var i = 0; i < sourceLength; i++) {
            if (sourceArray[previousIndexes[1]].columns[i].col_index === colIndex) {
                sourceArray[previousIndexes[1]].columns.splice(i, 1);
            }
        }

        // Remove index completely if no columns left.
        if (sourceArray[previousIndexes[1]].columns.length === 0) {
            sourceArray.splice(previousIndexes[1], 1);
        }

        // Update current index details.
        $('select[name="field_key[' + colIndex + ']"]').attr('data-index', '');
        // Update form index parameters.
        Indexes.setIndexFormParameters(sourceArray, previousIndexes[0].toLowerCase());
    }
}

/**
 * Adds a column to an Index.
 *
 * @param {any[]}  sourceArray Array holding corresponding indexes
 * @param {string} arrayIndex  Index of an INDEX in array
 * @param {string} indexChoice Choice of Index
 * @param {string} colIndex    Index of column on form
 */
function addColumnToIndex (sourceArray, arrayIndex, indexChoice, colIndex): void {
    if (colIndex >= 0) {
        // Remove column from other indexes (if any).
        Indexes.removeColumnFromIndex(colIndex);
    }

    var indexName = ($('input[name="index[Key_name]"]').val() as string);
    var indexComment = $('input[name="index[Index_comment]"]').val();
    var keyBlockSize = $('input[name="index[Key_block_size]"]').val();
    var parser = $('input[name="index[Parser]"]').val();
    var indexType = $('select[name="index[Index_type]"]').val();
    var columns = [];
    $('#index_columns').find('tbody').find('tr').each(function () {
        // Get columns in particular order.
        var colIndex = $(this).find('select[name="index[columns][names][]"]').val();
        var size = $(this).find('input[name="index[columns][sub_parts][]"]').val();
        columns.push({
            'col_index': colIndex,
            'size': size
        });
    });

    // Update or create an index.
    sourceArray[arrayIndex] = {
        'Key_name': indexName,
        'Index_comment': indexComment,
        'Index_choice': indexChoice.toUpperCase(),
        'Key_block_size': keyBlockSize,
        'Parser': parser,
        'Index_type': indexType,
        'columns': columns
    };

    // Display index name (or column list)
    var displayName = indexName;
    if (displayName === '') {
        var columnNames = [];
        $.each(columns, function () {
            columnNames.push($('input[name="field_name[' + this.col_index + ']"]').val());
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
        // @ts-ignore
        $name.html($text);
    });

    if (colIndex >= 0) {
        // Update index details on form.
        $('select[name="field_key[' + colIndex + ']"]')
            .attr('data-index', indexChoice + ',' + arrayIndex);
    }

    Indexes.setIndexFormParameters(sourceArray, indexChoice.toLowerCase());
}

/**
 * Get choices list for a column to create a composite index with.
 *
 * @param {any[]} sourceArray Array hodling columns for particular index
 * @param {string} colIndex Choice of index
 *
 * @return {JQuery} jQuery Object
 */
function getCompositeIndexList (sourceArray, colIndex) {
    // Remove any previous list.
    if ($('#composite_index_list').length) {
        $('#composite_index_list').remove();
    }

    // Html list.
    var $compositeIndexList = $(
        '<ul id="composite_index_list">' +
        '<div>' + window.Messages.strCompositeWith + '</div>' +
        '</ul>'
    );

    // Add each column to list available for composite index.
    var sourceLength = sourceArray.length;
    var alreadyPresent = false;
    for (var i = 0; i < sourceLength; i++) {
        var subArrayLen = sourceArray[i].columns.length;
        var columnNames = [];
        for (var j = 0; j < subArrayLen; j++) {
            columnNames.push(
                $('input[name="field_name[' + sourceArray[i].columns[j].col_index + ']"]').val()
            );

            if (colIndex === sourceArray[i].columns[j].col_index) {
                alreadyPresent = true;
            }
        }

        $compositeIndexList.append(
            '<li>' +
            '<input type="radio" name="composite_with" ' +
            (alreadyPresent ? 'checked="checked"' : '') +
            ' id="composite_index_' + i + '" value="' + i + '">' +
            '<label for="composite_index_' + i + '">' + columnNames.join(', ') +
            '</label>' +
            '</li>'
        );
    }

    return $compositeIndexList;
}

var addIndexGo = function (sourceArray, arrayIndex, index, colIndex) {
    var isMissingValue = false;
    $('select[name="index[columns][names][]"]').each(function () {
        if ($(this).val() === '') {
            isMissingValue = true;
        }
    });

    if (! isMissingValue) {
        Indexes.addColumnToIndex(
            sourceArray,
            arrayIndex,
            index.Index_choice,
            colIndex
        );
    } else {
        ajaxShowMessage(
            '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt=""' +
            ' class="icon ic_s_error"> ' + window.Messages.strMissingColumn +
            ' </div>', false
        );

        return false;
    }

    $('#addIndexModal').modal('hide');
};

/**
 * Shows 'Add Index' dialog.
 *
 * @param {any[]}  sourceArray   Array holding particular index
 * @param {string} arrayIndex    Index of an INDEX in array
 * @param {any[]}  targetColumns Columns for an INDEX
 * @param {string} colIndex      Index of column on form
 * @param {object} index         Index detail object
 * @param {boolean} showDialog   Whether to show index creation dialog or not
 */
function showAddIndexDialog (sourceArray, arrayIndex, targetColumns, colIndex, index, showDialog = undefined): void {
    var showDialogLocal = typeof showDialog !== 'undefined' ? showDialog : true;
    // Prepare post-data.
    var $table = $('input[name="table"]');
    var table = $table.length > 0 ? $table.val() : '';
    var postData = {
        'server': CommonParams.get('server'),
        'db': $('input[name="db"]').val(),
        'table': table,
        'ajax_request': 1,
        'create_edit_table': 1,
        'index': index,
        'columns': '',
    };

    var columns = {};
    for (var i = 0; i < targetColumns.length; i++) {
        var columnName = ($('input[name="field_name[' + targetColumns[i] + ']"]').val() as string);
        var columnType = ($('select[name="field_type[' + targetColumns[i] + ']"]').val() as string).toLowerCase();
        columns[columnName] = [columnType, targetColumns[i]];
    }

    postData.columns = JSON.stringify(columns);

    $('#addIndexModalGoButton').on('click', function () {
        addIndexGo(sourceArray, arrayIndex, index, colIndex);
    });

    $('#addIndexModalCancelButton').on('click', function () {
        if (colIndex >= 0) {
            // Handle state on 'Cancel'.
            var $selectList = $('select[name="field_key[' + colIndex + ']"]');
            if (! $selectList.attr('data-index').length) {
                $selectList.find('option[value*="none"]').attr('selected', 'selected');
            } else {
                var previousIndex = $selectList.attr('data-index').split(',');
                $selectList.find('option[value*="' + previousIndex[0].toLowerCase() + '"]')
                    .attr('selected', 'selected');
            }
        }

        $('#addIndexModal').modal('hide');
    });

    $('#addIndexModalCloseButton').on('click', function () {
        $('#addIndexModal').modal('hide');
    });

    var $msgbox = ajaxShowMessage();
    $.post('index.php?route=/table/indexes', postData, function (data) {
        if (data.success === false) {
            // in the case of an error, show the error message returned.
            ajaxShowMessage(data.error, false);
        } else {
            ajaxRemoveMessage($msgbox);
            var $div = $('<div></div>');
            if (showDialogLocal) {
                // Show dialog if the request was successful
                if ($('#addIndex').length > 0) {
                    $('#addIndex').remove();
                }

                $('#addIndexModal').on('keypress', function (e) {
                    if (e.which === 13 || e.keyCode === 13 || (window.event as KeyboardEvent).keyCode === 13) {
                        e.preventDefault();
                        console.log('BOOM');
                        addIndexGo(sourceArray, arrayIndex, index, colIndex);
                        $('#addIndexModal').modal('hide');
                    }
                });

                $('#addIndexModal').modal('show');
                $('#addIndexModalLabel').first().text(window.Messages.strAddIndex);
                $('#addIndexModal').find('.modal-body').first().html(data.message);
                checkIndexName('index_frm');
                Functions.showHints($div);
                $('#index_columns').find('td').each(function () {
                    $(this).css('width', $(this).width() + 'px');
                });

                $('#index_columns').find('tbody').sortable({
                    axis: 'y',
                    containment: $('#index_columns').find('tbody'),
                    tolerance: 'pointer'
                });
            } else {
                $div
                    .append(data.message);

                $div.css({ 'display': 'none' });
                $div.appendTo($('body'));
                $div.attr({ 'id': 'addIndex' });
                var isMissingValue = false;
                $('select[name="index[columns][names][]"]').each(function () {
                    if ($(this).val() === '') {
                        isMissingValue = true;
                    }
                });

                if (! isMissingValue) {
                    Indexes.addColumnToIndex(
                        sourceArray,
                        arrayIndex,
                        index.Index_choice,
                        colIndex
                    );
                } else {
                    ajaxShowMessage(
                        '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt=""' +
                        ' class="icon ic_s_error"> ' + window.Messages.strMissingColumn +
                        ' </div>', false
                    );

                    return false;
                }
            }
        }
    });
}

var removeIndexOnChangeEvent = function () {
    $('#composite_index').off('change');
    $('#single_column').off('change');
    $('#addIndexModal').modal('hide');
};

/**
 * Creates a advanced index type selection dialog.
 *
 * @param {any[]}  sourceArray Array holding a particular type of indexes
 * @param {string} indexChoice Choice of index
 * @param {string} colIndex    Index of new column on form
 */
function indexTypeSelectionDialog (sourceArray, indexChoice, colIndex): void {
    var $singleColumnRadio = $('<input type="radio" id="single_column" name="index_choice"' +
        ' checked="checked">' +
        '<label for="single_column">' + window.Messages.strCreateSingleColumnIndex + '</label>');
    var $compositeIndexRadio = $('<input type="radio" id="composite_index"' +
        ' name="index_choice">' +
        '<label for="composite_index">' + window.Messages.strCreateCompositeIndex + '</label>');
    var $dialogContent = $('<fieldset class="pma-fieldset" id="advance_index_creator"></fieldset>');
    $dialogContent.append('<legend>' + indexChoice.toUpperCase() + '</legend>');


    // For UNIQUE/INDEX type, show choice for single-column and composite index.
    $dialogContent.append($singleColumnRadio);
    $dialogContent.append($compositeIndexRadio);

    // 'OK' operation.
    $('#addIndexModalGoButton').on('click', function () {
        if ($('#single_column').is(':checked')) {
            var index = {
                'Key_name': (indexChoice === 'primary' ? 'PRIMARY' : ''),
                'Index_choice': indexChoice.toUpperCase()
            };
            Indexes.showAddIndexDialog(sourceArray, (sourceArray.length), [colIndex], colIndex, index);
        }

        if ($('#composite_index').is(':checked')) {
            if ($('input[name="composite_with"]').length !== 0 && $('input[name="composite_with"]:checked').length === 0
            ) {
                ajaxShowMessage(
                    '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title=""' +
                    ' alt="" class="icon ic_s_error"> ' +
                    window.Messages.strFormEmpty +
                    ' </div>',
                    false
                );

                return false;
            }

            var arrayIndex = Number($('input[name="composite_with"]:checked').val());
            var sourceLength = sourceArray[arrayIndex].columns.length;
            var targetColumns = [];
            for (var i = 0; i < sourceLength; i++) {
                targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
            }

            targetColumns.push(colIndex);

            Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, colIndex,
                sourceArray[arrayIndex]);
        }

        $('#addIndexModal').modal('hide');
    });

    $('#addIndexModalCancelButton').on('click', function () {
        // Handle state on 'Cancel'.
        var $selectList = $('select[name="field_key[' + colIndex + ']"]');
        if (! $selectList.attr('data-index').length) {
            $selectList.find('option[value*="none"]').attr('selected', 'selected');
        } else {
            var previousIndex = $selectList.attr('data-index').split(',');
            $selectList.find('option[value*="' + previousIndex[0].toLowerCase() + '"]')
                .attr('selected', 'selected');
        }

        removeIndexOnChangeEvent();
    });

    $('#addIndexModalCloseButton').on('click', function () {
        removeIndexOnChangeEvent();
    });

    $('#addIndexModal').modal('show');
    $('#addIndexModalLabel').first().text(window.Messages.strAddIndex);
    $('#addIndexModal').find('.modal-body').first()
        // @ts-ignore
        .html($dialogContent);

    $('#composite_index').on('change', function () {
        if ($(this).is(':checked')) {
            $dialogContent.append(Indexes.getCompositeIndexList(sourceArray, colIndex));
        }
    });

    $('#single_column').on('change', function () {
        if ($(this).is(':checked')) {
            if ($('#composite_index_list').length) {
                $('#composite_index_list').remove();
            }
        }
    });
}

/**
 * @return {function}
 */
function off () {
    return function () {
        $(document).off('click', '#save_index_frm');
        $(document).off('click', '#preview_index_frm');
        $(document).off('change', '#select_index_choice');
        $(document).off('click', 'a.drop_primary_key_index_anchor.ajax');
        $(document).off('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax');
        $(document).off('click', '#table_index tbody tr td.rename_index.ajax');
        $(document).off('click', '#index_frm input[type=submit]');
        $('body').off('change', 'select[name*="field_key"]');
        $(document).off('click', '.show_index_dialog');
    };
}

/**
 * @return {function}
 */
function on () {
    return function () {
        Indexes.resetColumnLists();

        // for table creation form
        var $engineSelector = $('.create_table_form select[name=tbl_storage_engine]');
        if ($engineSelector.length) {
            Functions.hideShowConnection($engineSelector);
        }

        var $form = $('#index_frm');
        if ($form.length > 0) {
            Functions.showIndexEditDialog($form);
        }

        $(document).on('click', '#save_index_frm', function (event) {
            event.preventDefault();
            var $form = $('#index_frm');
            var argsep = CommonParams.get('arg_separator');
            var submitData = $form.serialize() + argsep + 'do_save_data=1' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            ajaxShowMessage(window.Messages.strProcessingRequest);
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        });

        $(document).on('click', '#preview_index_frm', function (event) {
            event.preventDefault();
            Functions.previewSql($('#index_frm'));
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
             * @var $currRow Object containing reference to the current field's row
             */
            var $currRow = $anchor.parents('tr');
            /** @var {number} rows Number of columns in the key */
            var rows = Number($anchor.parents('td').attr('rowspan')) || 1;
            /** @var {number} $rowsToHide Rows that should be hidden */
            var $rowsToHide = $currRow;
            for (var i = 1, $lastRow = $currRow.next(); i < rows; i++, $lastRow = $lastRow.next()) {
                $rowsToHide = $rowsToHide.add($lastRow);
            }

            var question = $currRow.children('td')
                .children('.drop_primary_key_index_msg')
                .val();

            Functions.confirmPreviewSql(question, $anchor.attr('href'), function (url) {
                var $msg = ajaxShowMessage(window.Messages.strDroppingPrimaryKeyIndex, false);
                var params = getJsConfirmCommonParam(this, $anchor.getPostData());
                $.post(url, params, function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        ajaxRemoveMessage($msg);
                        var $tableRef = $rowsToHide.closest('table');
                        if ($rowsToHide.length === $tableRef.find('tbody > tr').length) {
                            // We are about to remove all rows from the table
                            $tableRef.hide('medium', function () {
                                $('div.no_indexes_defined').show('medium');
                                $rowsToHide.remove();
                            });

                            $tableRef.siblings('.alert-primary').hide('medium');
                        } else {
                            // We are removing some of the rows only
                            $rowsToHide.hide('medium', function () {
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

                            highlightSql($('#page_content'));
                        }

                        Navigation.reload();
                        refreshMainContent('index.php?route=/table/structure');
                    } else {
                        ajaxShowMessage(window.Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                    }
                }); // end $.post()
            });
        }); // end Drop Primary Key/Index

        /**
         * Ajax event handler for index edit
         **/
        $(document).on('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax', function (event) {
            event.preventDefault();
            var url;
            var title;
            if ($(this).find('a').length === 0) {
                // Add index
                var valid = Functions.checkFormElementInRange(
                    $(this).closest('form')[0],
                    'added_fields',
                    'Column count has to be larger than zero.'
                );
                if (! valid) {
                    return;
                }

                url = $(this).closest('form').serialize();
                title = window.Messages.strAddIndex;
            } else {
                // Edit index
                url = $(this).find('a').getPostData();
                title = window.Messages.strEditIndex;
            }

            url += CommonParams.get('arg_separator') + 'ajax_request=true';
            Functions.indexEditorDialog(url, title, function (data) {
                Navigation.update(CommonParams.set('db', data.params.db));
                Navigation.update(CommonParams.set('table', data.params.table));
                refreshMainContent('index.php?route=/table/structure');
            });
        });

        /**
         * Ajax event handler for index rename
         **/
        $(document).on('click', '#table_index tbody tr td.rename_index.ajax', function (event) {
            event.preventDefault();
            var url = $(this).find('a').getPostData();
            var title = window.Messages.strRenameIndex;
            url += CommonParams.get('arg_separator') + 'ajax_request=true';
            Functions.indexRenameDialog(url, title, function (data) {
                Navigation.update(CommonParams.set('db', data.params.db));
                Navigation.update(CommonParams.set('table', data.params.table));
                refreshMainContent('index.php?route=/table/structure');
            });
        });

        /**
         * Ajax event handler for advanced index creation during table creation
         * and column addition.
         */
        $('body').on('change', 'select[name*="field_key"]', function (e, showDialog) {
            var showDialogLocal = typeof showDialog !== 'undefined' ? showDialog : true;
            // Index of column on Table edit and create page.
            var colIndexRegEx = /\d+/.exec($(this).attr('name'));
            const colIndex = colIndexRegEx[0];
            // Choice of selected index.
            var indexChoiceRegEx = /[a-z]+/.exec(($(this).val() as string));
            const indexChoice = indexChoiceRegEx[0];
            // Array containing corresponding indexes.
            var sourceArray = null;

            if (indexChoice === 'none') {
                Indexes.removeColumnFromIndex(colIndex);
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
            sourceArray = Indexes.getIndexArray(indexChoice);
            if (sourceArray === null) {
                return;
            }

            if (sourceArray.length === 0) {
                var index = {
                    'Key_name': (indexChoice === 'primary' ? 'PRIMARY' : ''),
                    'Index_choice': indexChoice.toUpperCase()
                };
                Indexes.showAddIndexDialog(sourceArray, 0, [colIndex], colIndex, index, showDialogLocal);
            } else {
                if (indexChoice === 'primary') {
                    var arrayIndex = 0;
                    var sourceLength = sourceArray[arrayIndex].columns.length;
                    var targetColumns = [];
                    for (var i = 0; i < sourceLength; i++) {
                        targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
                    }

                    targetColumns.push(colIndex);
                    Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, colIndex,
                        sourceArray[arrayIndex], showDialogLocal);
                } else {
                    // If there are multiple columns selected for an index, show advanced dialog.
                    Indexes.indexTypeSelectionDialog(sourceArray, indexChoice, colIndex);
                }
            }
        });

        $(document).on('click', '.show_index_dialog', function (e) {
            e.preventDefault();

            // Get index details.
            var previousIndex = $(this).prev('select')
                .attr('data-index')
                .split(',');

            var indexChoice = previousIndex[0];
            var arrayIndex = previousIndex[1];

            var sourceArray = Indexes.getIndexArray(indexChoice);
            if (sourceArray !== null) {
                var sourceLength = sourceArray[arrayIndex].columns.length;

                var targetColumns = [];
                for (var i = 0; i < sourceLength; i++) {
                    targetColumns.push(sourceArray[arrayIndex].columns[i].col_index);
                }

                Indexes.showAddIndexDialog(sourceArray, arrayIndex, targetColumns, -1, sourceArray[arrayIndex]);
            }
        });

        ($('#index_frm') as JQuery<HTMLFormElement>).on('submit', function () {
            if (typeof (this.elements['index[Key_name]'].disabled) !== 'undefined') {
                this.elements['index[Key_name]'].disabled = false;
            }
        });
    };
}

/**
 * Index manipulation pages
 */
const Indexes = {
    resetColumnLists: resetColumnLists,
    getIndexArray: getIndexArray,
    setIndexFormParameters: setIndexFormParameters,
    removeColumnFromIndex: removeColumnFromIndex,
    addColumnToIndex: addColumnToIndex,
    getCompositeIndexList: getCompositeIndexList,
    showAddIndexDialog: showAddIndexDialog,
    indexTypeSelectionDialog: indexTypeSelectionDialog,
    off: off,
    on: on,
};

export { Indexes };
