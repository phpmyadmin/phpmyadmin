import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import { Functions } from './modules/functions.ts';
import { Navigation } from './modules/navigation.ts';
import { CommonParams } from './modules/common.ts';
import highlightSql from './modules/sql-highlight.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from './modules/ajax-message.ts';
import createProfilingChart from './modules/functions/createProfilingChart.ts';
import { escapeHtml } from './modules/functions/escape.ts';
import refreshMainContent from './modules/functions/refreshMainContent.ts';
import isStorageSupported from './modules/functions/isStorageSupported.ts';

/**
 * @fileoverview    functions used wherever an sql query form is used
 *
 * @test-module Sql
 */

/**
 * decode a string URL_encoded
 *
 * @param {string} str
 * @return {string} the URL-decoded string
 */
function urlDecode (str) {
    if (typeof str !== 'undefined') {
        return decodeURIComponent(str.replace(/\+/g, '%20'));
    }
}

/**
 * encode a string URL_decoded
 *
 * @param {string} str
 * @return {string} the URL-encoded string
 */
function urlEncode (str) {
    if (typeof str !== 'undefined') {
        return encodeURIComponent(str).replace(/%20/g, '+');
    }
}

/**
 * Saves SQL query in local storage or cookie
 *
 * @param {string} query SQL query
 */
function autoSave (query): void {
    if (query) {
        var key = Sql.getAutoSavedKey();
        if (isStorageSupported('localStorage')) {
            window.localStorage.setItem(key, query);
        } else {
            window.Cookies.set(key, query, { path: CommonParams.get('rootPath') });
        }
    }
}

/**
 * Saves SQL query in local storage or cookie
 *
 * @param {string} db database name
 * @param {string} table table name
 * @param {string} query SQL query
 */
function showThisQuery (db, table, query): void {
    var showThisQueryObject = {
        'db': db,
        'table': table,
        'query': query
    };
    if (isStorageSupported('localStorage')) {
        window.localStorage.showThisQuery = 1;
        window.localStorage.showThisQueryObject = JSON.stringify(showThisQueryObject);
    } else {
        window.Cookies.set('showThisQuery', '1', { path: CommonParams.get('rootPath') });
        window.Cookies.set('showThisQueryObject', JSON.stringify(showThisQueryObject), { path: CommonParams.get('rootPath') });
    }
}

/**
 * Set query to codemirror if show this query is
 * checked and query for the db and table pair exists
 */
function setShowThisQuery () {
    var db = $('input[name="db"]').val();
    var table = $('input[name="table"]').val();
    if (isStorageSupported('localStorage')) {
        if (window.localStorage.showThisQueryObject !== undefined) {
            var storedDb = JSON.parse(window.localStorage.showThisQueryObject).db;
            var storedTable = JSON.parse(window.localStorage.showThisQueryObject).table;
            var storedQuery = JSON.parse(window.localStorage.showThisQueryObject).query;
        }

        if (window.localStorage.showThisQuery !== undefined
            && window.localStorage.showThisQuery === '1') {
            $('input[name="show_query"]').prop('checked', true);
            if (db === storedDb && table === storedTable) {
                if (window.codeMirrorEditor) {
                    window.codeMirrorEditor.setValue(storedQuery);
                    // @ts-ignore
                } else if (document.sqlform) {
                    // @ts-ignore
                    document.sqlform.sql_query.value = storedQuery;
                }
            }
        } else {
            $('input[name="show_query"]').prop('checked', false);
        }
    }
}

/**
 * Saves SQL query with sort in local storage or cookie
 *
 * @param {string} query SQL query
 */
function autoSaveWithSort (query): void {
    if (query) {
        if (isStorageSupported('localStorage')) {
            window.localStorage.setItem('autoSavedSqlSort', query);
        } else {
            window.Cookies.set('autoSavedSqlSort', query, { path: CommonParams.get('rootPath') });
        }
    }
}

/**
 * Clear saved SQL query with sort in local storage or cookie
 */
function clearAutoSavedSort (): void {
    if (isStorageSupported('localStorage')) {
        window.localStorage.removeItem('autoSavedSqlSort');
    } else {
        window.Cookies.set('autoSavedSqlSort', '', { path: CommonParams.get('rootPath') });
    }
}

/**
 * Get the field name for the current field.  Required to construct the query
 * for grid editing
 *
 * @param $tableResults enclosing results table
 * @param $thisField    jQuery object that points to the current field's tr
 *
 * @return {string}
 */
function getFieldName ($tableResults, $thisField) {
    var thisFieldIndex = $thisField.index();
    // ltr or rtl direction does not impact how the DOM was generated
    // check if the action column in the left exist
    var leftActionExist = ! $tableResults.find('th').first().hasClass('draggable');
    // number of column span for checkbox and Actions
    var leftActionSkip = leftActionExist ? $tableResults.find('th').first().attr('colspan') - 1 : 0;

    // If this column was sorted, the text of the a element contains something
    // like <small>1</small> that is useful to indicate the order in case
    // of a sort on multiple columns; however, we dont want this as part
    // of the column name so we strip it ( .clone() to .end() )
    var fieldName = $tableResults
        .find('thead')
        .find('th')
        .eq(thisFieldIndex - leftActionSkip)
        .find('a')
        .clone()    // clone the element
        .children() // select all the children
        .remove()   // remove all of them
        .end()      // go back to the selected element
        .text();    // grab the text
    // happens when just one row (headings contain no a)
    if (fieldName === '') {
        var $heading = $tableResults.find('thead').find('th').eq(thisFieldIndex - leftActionSkip).children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        fieldName = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    fieldName = fieldName.trim();

    return fieldName;
}

/**
 * @type {boolean} lock for the sqlbox textarea in the querybox
 */
let sqlBoxLocked = false;

/**
 * @type {boolean[]} holds elements which content should only selected once
 */
const onlyOnceElements = [];

/**
 * Handles 'Simulate query' button on SQL query box.
 */
const handleSimulateQueryButton = function (): void {
    const updateRegExp = new RegExp('^\\s*UPDATE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+SET\\s', 'i');
    const deleteRegExp = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    let query = '';

    if (window.codeMirrorEditor) {
        query = window.codeMirrorEditor.getValue();
    } else {
        query = ($('#sqlquery').val() as string);
    }

    const $simulateDml = $('#simulate_dml');
    if (updateRegExp.test(query) || deleteRegExp.test(query)) {
        if (! $simulateDml.length) {
            $('#button_submit_query').before(
                '<input type="button" id="simulate_dml"' +
                'tabindex="199" class="btn btn-primary" value="' + window.Messages.strSimulateDML + '">'
            );
        }
    } else {
        if ($simulateDml.length) {
            $simulateDml.remove();
        }
    }
};

/**
 * selects the content of a given object, f.e. a textarea
 *
 * @param {HTMLTextAreaElement} element Element of which the content will be selected
 */
const selectContent = function (element) {
    if (onlyOnceElements[element.name]) {
        return;
    }

    onlyOnceElements[element.name] = true;

    if (sqlBoxLocked) {
        return;
    }

    element.select();
};

/**
 * Sets current value for query box.
 * @param {string} query
 */
const setQuery = function (query): void {
    if (window.codeMirrorEditor) {
        window.codeMirrorEditor.setValue(query);
        window.codeMirrorEditor.focus();
        // @ts-ignore
    } else if (document.sqlform) {
        // @ts-ignore
        document.sqlform.sql_query.value = query;
        // @ts-ignore
        document.sqlform.sql_query.focus();
    }
};

/**
 * Create quick sql statements.
 *
 * @param {'clear'|'format'|'saved'|'selectall'|'select'|'insert'|'update'|'delete'} queryType
 *
 */
const insertQuery = function (queryType) {
    var table;
    if (queryType === 'clear') {
        setQuery('');

        return;
    } else if (queryType === 'format') {
        if (window.codeMirrorEditor) {
            $('#querymessage').html(window.Messages.strFormatting +
                '&nbsp;<img class="ajaxIcon" src="' +
                window.themeImagePath + 'ajax_clock_small.gif" alt="">');

            var params = {
                'ajax_request': true,
                'sql': window.codeMirrorEditor.getValue(),
                'server': CommonParams.get('server')
            };
            $.ajax({
                type: 'POST',
                url: 'index.php?route=/database/sql/format',
                data: params,
                success: function (data) {
                    if (data.success) {
                        window.codeMirrorEditor.setValue(data.sql);
                    }

                    $('#querymessage').html('');
                },
                error: function () {
                    $('#querymessage').html('');
                }
            });
        }

        return;
    } else if (queryType === 'saved') {
        var db = $('input[name="db"]').val();
        table = $('input[name="table"]').val();
        var key = db;
        if (table !== undefined) {
            key += '.' + table;
        }

        key = 'autoSavedSql_' + key;
        if (isStorageSupported('localStorage') &&
            typeof window.localStorage.getItem(key) === 'string') {
            setQuery(window.localStorage.getItem(key));
            // @ts-ignore
        } else if (window.Cookies.get(key, { path: CommonParams.get('rootPath') })) {
            // @ts-ignore
            setQuery(window.Cookies.get(key, { path: CommonParams.get('rootPath') }));
        } else {
            ajaxShowMessage(window.Messages.strNoAutoSavedQuery);
        }

        return;
    }

    var query = '';
    // @ts-ignore
    var myListBox = document.sqlform.dummy;
    // @ts-ignore
    table = document.sqlform.table.value;

    if (myListBox.options.length > 0) {
        sqlBoxLocked = true;
        var columnsList = '';
        var valDis = '';
        var editDis = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            NbSelect++;
            if (NbSelect > 1) {
                columnsList += ', ';
                valDis += ',';
                editDis += ',';
            }

            columnsList += myListBox.options[i].value;
            valDis += '\'[value-' + NbSelect + ']\'';
            editDis += myListBox.options[i].value + '=\'[value-' + NbSelect + ']\'';
        }

        if (queryType === 'selectall') {
            query = 'SELECT * FROM `' + table + '` WHERE 1';
        } else if (queryType === 'select') {
            query = 'SELECT ' + columnsList + ' FROM `' + table + '` WHERE 1';
        } else if (queryType === 'insert') {
            query = 'INSERT INTO `' + table + '`(' + columnsList + ') VALUES (' + valDis + ')';
        } else if (queryType === 'update') {
            query = 'UPDATE `' + table + '` SET ' + editDis + ' WHERE 1';
        } else if (queryType === 'delete') {
            query = 'DELETE FROM `' + table + '` WHERE 0';
        }

        setQuery(query);
        sqlBoxLocked = false;
    }
};

/**
 * Inserts multiple fields.
 *
 */
const insertValueQuery = function () {
    // @ts-ignore
    var myQuery = document.sqlform.sql_query;
    // @ts-ignore
    var myListBox = document.sqlform.dummy;

    if (myListBox.options.length > 0) {
        sqlBoxLocked = true;
        var columnsList = '';
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            if (myListBox.options[i].selected) {
                NbSelect++;
                if (NbSelect > 1) {
                    columnsList += ', ';
                }

                columnsList += myListBox.options[i].value;
            }
        }

        /* CodeMirror support */
        if (window.codeMirrorEditor) {
            window.codeMirrorEditor.replaceSelection(columnsList);
            window.codeMirrorEditor.focus();
            // IE support
            // @ts-ignore
        } else if (document.selection) {
            myQuery.focus();
            // @ts-ignore
            var sel = document.selection.createRange();
            sel.text = columnsList;
            // MOZILLA/NETSCAPE support
            // @ts-ignore
        } else if (document.sqlform.sql_query.selectionStart || document.sqlform.sql_query.selectionStart === '0') {
            // @ts-ignore
            var startPos = document.sqlform.sql_query.selectionStart;
            // @ts-ignore
            var endPos = document.sqlform.sql_query.selectionEnd;
            // @ts-ignore
            var SqlString = document.sqlform.sql_query.value;

            myQuery.value = SqlString.substring(0, startPos) + columnsList + SqlString.substring(endPos, SqlString.length);
            myQuery.focus();
        } else {
            myQuery.value += columnsList;
        }

        // eslint-disable-next-line no-unused-vars
        sqlBoxLocked = false;
    }
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('sql.js', function () {
    $(document).off('click', 'a.delete_row.ajax');
    $(document).off('submit', '.bookmarkQueryForm');
    $('input#bkm_label').off('input');
    $(document).off('makegrid', '.sqlqueryresults');
    $('#togglequerybox').off('click');
    $(document).off('click', '#button_submit_query');
    $(document).off('change', '#id_bookmark');
    $('input[name=\'bookmark_variable\']').off('keypress');
    $(document).off('submit', '#sqlqueryform.ajax');
    $(document).off('click', 'input[name=navig].ajax');
    $(document).off('submit', 'form[name=\'displayOptionsForm\'].ajax');
    $(document).off('mouseenter', 'th.column_heading.pointer');
    $(document).off('mouseleave', 'th.column_heading.pointer');
    $(document).off('click', 'th.column_heading.marker');
    $(document).off('keyup', '.filter_rows');
    if (window.codeMirrorEditor) {
        // @ts-ignore
        window.codeMirrorEditor.off('change');
    } else {
        $('#sqlquery').off('input propertychange');
    }

    $('body').off('click', '.navigation .showAllRows');
    $('body').off('click', 'a.browse_foreign');
    $('body').off('click', '#simulate_dml');
    $('body').off('keyup', '#sqlqueryform');
    $('body').off('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]');
    $(document).off('submit', '.maxRowsForm');
    $(document).off('click', '#view_as');
    $(document).off('click', '#sqlquery');
    $(document).off('click', 'input.sqlbutton');
    $('#fieldsSelect').off('dblclick');
});

/**
 * @description <p>Ajax scripts for sql and browse pages</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Retrieve results of an SQL query</li>
 * <li>Paginate the results table</li>
 * <li>Sort the results table</li>
 * <li>Change table according to display options</li>
 * <li>Grid editing of data</li>
 * <li>Saving a bookmark</li>
 * </ul>
 *
 * @name        document.ready
 * @memberOf    jQuery
 */
AJAX.registerOnload('sql.js', function () {
    // @ts-ignore
    if (window.codeMirrorEditor || document.sqlform) {
        Sql.setShowThisQuery();
    }

    $(function () {
        if (window.codeMirrorEditor) {
            window.codeMirrorEditor.on('change', function () {
                Sql.autoSave(window.codeMirrorEditor.getValue());
            });
        } else {
            $('#sqlquery').on('input propertychange', function () {
                Sql.autoSave($('#sqlquery').val());
            });

            var useLocalStorageValue = isStorageSupported('localStorage') && typeof window.localStorage.autoSavedSqlSort !== 'undefined';
            // Save sql query with sort
            if ($('#RememberSorting') !== undefined && $('#RememberSorting').is(':checked')) {
                $('select[name="sql_query"]').on('change', function () {
                    Sql.autoSaveWithSort($(this).val());
                });

                $('.sortlink').on('click', function () {
                    Sql.clearAutoSavedSort();
                });
            } else {
                Sql.clearAutoSavedSort();
            }

            // If sql query with sort for current table is stored, change sort by key select value
            var sortStoredQuery = useLocalStorageValue
                ? window.localStorage.autoSavedSqlSort
                // @ts-ignore
                : window.Cookies.get('autoSavedSqlSort', { path: CommonParams.get('rootPath') });

            if (typeof sortStoredQuery !== 'undefined' && sortStoredQuery !== $('select[name="sql_query"]').val() && $('select[name="sql_query"] option[value="' + sortStoredQuery + '"]').length !== 0) {
                $('select[name="sql_query"]').val(sortStoredQuery).trigger('change');
            }
        }
    });

    // Delete row from SQL results
    $(document).on('click', 'a.delete_row.ajax', function (e) {
        e.preventDefault();
        var question = window.sprintf(window.Messages.strDoYouReally, escapeHtml($(this).closest('td').find('div').text()));
        var $link = $(this);
        $link.confirm(question, $link.attr('href'), function (url) {
            ajaxShowMessage();
            var argsep = CommonParams.get('arg_separator');
            var params = 'ajax_request=1' + argsep + 'is_js_confirmed=1';
            var postData = $link.getPostData();
            if (postData) {
                params += argsep + postData;
            }

            $.post(url, params, function (data) {
                if (data.success) {
                    ajaxShowMessage(data.message);
                    $link.closest('tr').remove();
                } else {
                    ajaxShowMessage(data.error, false);
                }
            });
        });
    });

    // Ajaxification for 'Bookmark this SQL query'
    $(document).on('submit', '.bookmarkQueryForm', function (e) {
        e.preventDefault();
        ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        $.post($(this).attr('action'), 'ajax_request=1' + argsep + $(this).serialize(), function (data) {
            if (data.success) {
                ajaxShowMessage(data.message);
            } else {
                ajaxShowMessage(data.error, false);
            }
        });
    });

    /* Hides the bookmarkoptions checkboxes when the bookmark label is empty */
    $('input#bkm_label').on('input', function () {
        $('input#id_bkm_all_users, input#id_bkm_replace')
            .parent()
            .toggle(($(this).val() as string).length > 0);
    }).trigger('input');

    /**
     * Attach Event Handler for 'Copy to clipboard'
     */
    $(document).on('click', '#copyToClipBoard', function (event) {
        event.preventDefault();

        var textArea = document.createElement('textarea');

        //
        // *** This styling is an extra step which is likely not required. ***
        //
        // Why is it here? To ensure:
        // 1. the element is able to have focus and selection.
        // 2. if element was to flash render it has minimal visual impact.
        // 3. less flakyness with selection and copying which **might** occur if
        //    the textarea element is not visible.
        //
        // The likelihood is the element won't even render, not even a flash,
        // so some of these are just precautions. However in IE the element
        // is visible whilst the popup box asking the user for permission for
        // the web page to copy to the clipboard.
        //

        // Place in top-left corner of screen regardless of scroll position.
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';

        // Ensure it has a small width and height. Setting to 1px / 1em
        // doesn't work as this gives a negative w/h on some browsers.
        textArea.style.width = '2em';
        textArea.style.height = '2em';

        // We don't need padding, reducing the size if it does flash render.
        textArea.style.padding = '0';

        // Clean up any borders.
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';

        // Avoid flash of white box if rendered for any reason.
        textArea.style.background = 'transparent';

        textArea.value = '';

        $('#server-breadcrumb a').each(function () {
            textArea.value += $(this).data('raw-text') + '/';
        });

        textArea.value += '\t\t' + window.location.href;
        textArea.value += '\n';
        $('.alert-success').each(function () {
            textArea.value += $(this).text() + '\n\n';
        });

        $('.sql pre').each(function () {
            textArea.value += $(this).text() + '\n\n';
        });

        $('.table_results .column_heading a').each(function () {
            // Don't copy ordering number text within <small> tag
            textArea.value += $(this).clone().find('small').remove().end().text() + '\t';
        });

        textArea.value += '\n';
        $('.table_results tbody tr').each(function () {
            $(this).find('.data span').each(function () {
                // Extract <em> tag for NULL values before converting to string to not mess up formatting
                var data = $(this).find('em').length !== 0 ? $(this).find('em')[0] : this;
                textArea.value += $(data).text() + '\t';
            });

            textArea.value += '\n';
        });

        // eslint-disable-next-line compat/compat
        document.body.appendChild(textArea);

        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            alert('Sorry! Unable to copy');
        }

        // eslint-disable-next-line compat/compat
        document.body.removeChild(textArea);
    }); // end of Copy to Clipboard action

    /**
     * Attach the {@link makegrid} function to a custom event, which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $(document).on('makegrid', '.sqlqueryresults', function () {
        $('.table_results').each(function () {
            window.makeGrid(this);
        });
    });

    /**
     * Append the "Show/Hide query box" message to the query input form
     *
     * @memberOf jQuery
     * @name    appendToggleSpan
     */
    // do not add this link more than once
    if (! $('#sqlqueryform').find('button').is('#togglequerybox')) {
        $('<button class="btn btn-secondary" id="togglequerybox"></button>')
            .html(window.Messages.strHideQueryBox)
            .appendTo('#sqlqueryform')
            // initially hidden because at this point, nothing else
            // appears under the link
            .hide();

        // Attach the toggling of the query box visibility to a click
        $('#togglequerybox').on('click', function () {
            var $link = $(this);
            $link.siblings().slideToggle('fast');
            if ($link.text() === window.Messages.strHideQueryBox) {
                $link.text(window.Messages.strShowQueryBox);
                // cheap trick to add a spacer between the menu tabs
                // and "Show query box"; feel free to improve!
                $('#togglequerybox_spacer').remove();
                $link.before('<br id="togglequerybox_spacer">');
            } else {
                $link.text(window.Messages.strHideQueryBox);
            }

            // avoid default click action
            return false;
        });
    }


    /**
     * Event handler for sqlqueryform.ajax button_submit_query
     *
     * @memberOf    jQuery
     */
    $(document).on('click', '#button_submit_query', function () {
        $('.alert-success,.alert-danger').hide();
        // hide already existing error or success message
        var $form = $(this).closest('form');
        // the Go button related to query submission was clicked,
        // instead of the one related to Bookmarks, so empty the
        // id_bookmark selector to avoid misinterpretation in
        // /import about what needs to be done
        $form.find('select[name=id_bookmark]').val('');
        var isShowQuery = $('input[name="show_query"]').is(':checked');
        if (isShowQuery) {
            window.localStorage.showThisQuery = '1';
            var db = $('input[name="db"]').val();
            var table = $('input[name="table"]').val();
            var query;
            if (window.codeMirrorEditor) {
                query = window.codeMirrorEditor.getValue();
            } else {
                query = $('#sqlquery').val();
            }

            Sql.showThisQuery(db, table, query);
        } else {
            window.localStorage.showThisQuery = '0';
        }
    });

    /**
     * Event handler to show appropriate number of variable boxes
     * based on the bookmarked query
     */
    $(document).on('change', '#id_bookmark', function () {
        var varCount = $(this).find('option:selected').data('varcount');
        if (typeof varCount === 'undefined') {
            varCount = 0;
        }

        var $varDiv = $('#bookmarkVariables');
        $varDiv.empty();
        for (var i = 1; i <= varCount; i++) {
            $varDiv.append($('<div class="mb-3">'));
            $varDiv.append($('<label for="bookmarkVariable' + i + '">' + window.sprintf(window.Messages.strBookmarkVariable, i) + '</label>'));
            $varDiv.append($('<input class="form-control" type="text" size="10" name="bookmark_variable[' + i + ']" id="bookmarkVariable' + i + '">'));
            $varDiv.append($('</div>'));
        }

        if (varCount === 0) {
            $varDiv.parent().hide();
        } else {
            $varDiv.parent().show();
        }
    });

    /**
     * Event handler for hitting enter on sqlqueryform bookmark_variable
     * (the Variable textfield in Bookmarked SQL query section)
     *
     * @memberOf    jQuery
     */
    $('input[name=bookmark_variable]').on('keypress', function (event) {
        // force the 'Enter Key' to implicitly click the #button_submit_bookmark
        var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
        if (keycode === 13) { // keycode for enter key
            // When you press enter in the sqlqueryform, which
            // has 2 submit buttons, the default is to run the
            // #button_submit_query, because of the tabindex
            // attribute.
            // This submits #button_submit_bookmark instead,
            // because when you are in the Bookmarked SQL query
            // section and hit enter, you expect it to do the
            // same action as the Go button in that section.
            $('#button_submit_bookmark').trigger('click');

            return false;
        } else {
            return true;
        }
    });

    /**
     * Ajax Event handler for 'SQL Query Submit'
     *
     * @see         ajaxShowMessage()
     * @memberOf    jQuery
     * @name        sqlqueryform_submit
     */
    $(document).on('submit', '#sqlqueryform.ajax', function (event) {
        event.preventDefault();

        var $form = $(this);
        if (window.codeMirrorEditor) {
            $form[0].elements.sql_query.value = window.codeMirrorEditor.getValue();
        }

        if (! Functions.checkSqlQuery($form[0])) {
            return false;
        }

        // remove any div containing a previous error message
        $('.alert-danger').remove();

        var $msgbox = ajaxShowMessage();
        var $sqlqueryresultsouter = $('#sqlqueryresultsouter');

        Functions.prepareForAjaxRequest($form);

        var argsep = CommonParams.get('arg_separator');
        $.post($form.attr('action'), $form.serialize() + argsep + 'ajax_page_request=true', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                // success happens if the query returns rows or not

                // show a message that stays on screen
                if (typeof data.action_bookmark !== 'undefined') {
                    // view only
                    if ('1' === data.action_bookmark) {
                        $('#sqlquery').text(data.sql_query);
                        // send to codemirror if possible
                        setQuery(data.sql_query);
                    }

                    // delete
                    if ('2' === data.action_bookmark) {
                        $('#id_bookmark option[value=\'' + data.id_bookmark + '\']').remove();
                        // if there are no bookmarked queries now (only the empty option),
                        // remove the bookmark section
                        if ($('#id_bookmark option').length === 1) {
                            $('#fieldsetBookmarkOptions').hide();
                            $('#fieldsetBookmarkOptionsFooter').hide();
                        }
                    }
                }

                $sqlqueryresultsouter
                    .show()
                    .html(data.message);

                highlightSql($sqlqueryresultsouter);

                if (data.menu) {
                    history.replaceState({
                        menu: data.menu
                    },
                    null
                    );

                    AJAX.handleMenu.replace(data.menu);
                }

                if (data.params) {
                    Navigation.update(CommonParams.setAll(data.params));
                }

                if (typeof data.ajax_reload !== 'undefined') {
                    if (data.ajax_reload.reload) {
                        if (data.ajax_reload.table_name) {
                            Navigation.update(CommonParams.set('table', data.ajax_reload.table_name));
                            refreshMainContent();
                        } else {
                            Navigation.reload();
                        }
                    }
                } else if (typeof data.reload !== 'undefined') {
                    // this happens if a USE or DROP command was typed
                    if (data.db !== CommonParams.get('db')) {
                        Navigation.update(CommonParams.setAll({ 'db': data.db, 'table': '' }));
                    }

                    var url;
                    if (data.db) {
                        if (data.table) {
                            url = 'index.php?route=/table/sql';
                        } else {
                            url = 'index.php?route=/database/sql';
                        }
                    } else {
                        url = 'index.php?route=/server/sql';
                    }

                    refreshMainContent(url);
                    AJAX.callback = () => {
                        $('#sqlqueryresultsouter')
                            .show()
                            .html(data.message);

                        highlightSql($('#sqlqueryresultsouter'));
                    };
                }

                $('.sqlqueryresults').trigger('makegrid');
                $('#togglequerybox').show();

                if (typeof data.action_bookmark === 'undefined') {
                    if ($('#sqlqueryform input[name="retain_query_box"]').is(':checked') !== true) {
                        if ($('#togglequerybox').siblings(':visible').length > 0) {
                            $('#togglequerybox').trigger('click');
                        }
                    }
                }
            } else if (typeof data !== 'undefined' && data.success === false) {
                // show an error message that stays on screen
                $sqlqueryresultsouter
                    .show()
                    .html(data.error);

                $('html, body').animate({ scrollTop: $(document).height() }, 200);
            }

            ajaxRemoveMessage($msgbox);
        }); // end $.post()
    }); // end SQL Query submit

    /**
     * Ajax Event handler for the display options
     * @memberOf    jQuery
     * @name        displayOptionsForm_submit
     */
    $(document).on('submit', 'form[name=\'displayOptionsForm\'].ajax', function (event) {
        event.preventDefault();

        var $form = $(this);

        var $msgbox = ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        $.post($form.attr('action'), $form.serialize() + argsep + 'ajax_request=true', function (data) {
            ajaxRemoveMessage($msgbox);
            var $sqlqueryresults = $form.parents('.sqlqueryresults');
            $sqlqueryresults
                .html(data.message)
                .trigger('makegrid');

            highlightSql($sqlqueryresults);
        }); // end $.post()
    }); // end displayOptionsForm handler

    // Filter row handling. --STARTS--
    $(document).on('keyup', '.filter_rows', function () {
        var uniqueId = $(this).data('for');
        var $targetTable = $('.table_results[data-uniqueId=\'' + uniqueId + '\']');
        var $headerCells = $targetTable.find('th[data-column]');
        var targetColumns = [];

        // To handle colspan=4, in case of edit, copy, etc options (Table row links). Add 3 dummy <TH> elements - only when the Table row links are NOT on the "Right"
        var rowLinksLocation = ($targetTable.find('thead > tr > th')).first();
        var dummyTh = (rowLinksLocation[0].getAttribute('colspan') !== null) ? '<th class="hide dummy_th"></th><th class="hide dummy_th"></th><th class="hide dummy_th"></th>' : ''; // Selecting columns that will be considered for filtering and searching.

        // Selecting columns that will be considered for filtering and searching.
        $headerCells.each(function () {
            targetColumns.push($(this).text().trim());
        });

        var phrase = $(this).val();
        // Set same value to both Filter rows fields.
        $('.filter_rows[data-for=\'' + uniqueId + '\']').not(this).val(phrase);
        // Handle colspan.
        $targetTable.find('thead > tr').prepend(dummyTh);
        $.uiTableFilter($targetTable, phrase, targetColumns);
        $targetTable.find('th.dummy_th').remove();
    });
    // Filter row handling. --ENDS--

    // Prompt to confirm on Show All
    $('body').on('click', '.navigation .showAllRows', function (e) {
        e.preventDefault();
        var $form = $(this).parents('form');

        const submitShowAllForm = function () {
            var argsep = CommonParams.get('arg_separator');
            var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            ajaxShowMessage();
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        };

        if (! $(this).is(':checked')) { // already showing all rows
            submitShowAllForm();
        } else {
            $form.confirm(window.Messages.strShowAllRowsWarning, $form.attr('action'), function () {
                submitShowAllForm();
            });
        }
    });

    $('body').on('keyup', '#sqlqueryform', function () {
        handleSimulateQueryButton();
    });

    /**
     * Ajax event handler for 'Simulate DML'.
     */
    $('body').on('click', '#simulate_dml', function () {
        var $form = $('#sqlqueryform');
        var query = '';
        var delimiter = $('#id_sql_delimiter').val();
        var dbName = $form.find('input[name="db"]').val();

        if (window.codeMirrorEditor) {
            query = window.codeMirrorEditor.getValue();
        } else {
            query = ($('#sqlquery').val() as string);
        }

        if (query.length === 0) {
            alert(window.Messages.strFormEmpty);
            $('#sqlquery').trigger('focus');

            return false;
        }

        var $msgbox = ajaxShowMessage();
        $.ajax({
            type: 'POST',
            url: 'index.php?route=/import/simulate-dml',
            data: {
                'server': CommonParams.get('server'),
                'db': dbName,
                'ajax_request': '1',
                'sql_query': query,
                'sql_delimiter': delimiter
            },
            success: function (response) {
                ajaxRemoveMessage($msgbox);
                if (response.success) {
                    var dialogContent = '<div class="preview_sql">';
                    if (response.sql_data) {
                        var len = response.sql_data.length;
                        for (var i = 0; i < len; i++) {
                            dialogContent += '<strong>' + window.Messages.strSQLQuery +
                                '</strong>' + response.sql_data[i].sql_query +
                                window.Messages.strMatchedRows +
                                ' <a href="' + response.sql_data[i].matched_rows_url +
                                '">' + response.sql_data[i].matched_rows + '</a><br>';

                            if (i < len - 1) {
                                dialogContent += '<hr>';
                            }
                        }
                    } else {
                        dialogContent += response.message;
                    }

                    dialogContent += '</div>';
                    var $dialogContent = $(dialogContent);
                    var modal = $('#simulateDmlModal');
                    modal.modal('show');
                    modal.find('.modal-body').first()
                        // @ts-ignore
                        .html($dialogContent);

                    modal.on('shown.bs.modal', function () {
                        highlightSql(modal);
                    });
                } else {
                    ajaxShowMessage(response.error);
                }
            },
            error: function () {
                ajaxShowMessage(window.Messages.strErrorProcessingRequest);
            }
        });
    });

    /**
     * Handles multi submits of results browsing page such as edit, delete and export
     */
    $('body').on('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var action = $button.val();
        var $form = $button.closest('form');
        var argsep = CommonParams.get('arg_separator');
        var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep;
        ajaxShowMessage();
        AJAX.source = $form;

        var url;
        if (action === 'edit') {
            submitData = submitData + argsep + 'default_action=update';
            url = 'index.php?route=/table/change/rows';
        } else if (action === 'copy') {
            submitData = submitData + argsep + 'default_action=insert';
            url = 'index.php?route=/table/change/rows';
        } else if (action === 'export') {
            url = 'index.php?route=/table/export/rows';
        } else if (action === 'delete') {
            url = 'index.php?route=/table/delete/confirm';
        } else {
            return;
        }

        $.post(url, submitData, AJAX.responseHandler);
    });

    $(document).on('submit', '.maxRowsForm', function () {
        var unlimNumRows = Number($(this).find('input[name="unlim_num_rows"]').val());

        var maxRowsCheck = Functions.checkFormElementInRange(
            this,
            'session_max_rows',
            window.Messages.strNotValidRowNumber,
            1
        );
        var posCheck = Functions.checkFormElementInRange(
            this,
            'pos',
            window.Messages.strNotValidRowNumber,
            0,
            unlimNumRows > 0 ? unlimNumRows - 1 : null
        );

        return maxRowsCheck && posCheck;
    });

    $('#insertBtn').on('click', function () {
        insertValueQuery();
    });

    $('#view_as').on('click', function () {
        selectContent(this);
    });

    $('#sqlquery').on('click', function () {
        if ($(this).data('textarea-auto-select') === true) {
            selectContent(this);
        }
    });

    $(document).on('click', 'input.sqlbutton', function (evt) {
        insertQuery(evt.target.id);
        handleSimulateQueryButton();

        return false;
    });

    $('#fieldsSelect').on('dblclick', () => {
        insertValueQuery();
    });
});

/**
 * Starting from some th, change the class of all td under it.
 * If isAddClass is specified, it will be used to determine whether to add or remove the class.
 *
 * @param $thisTh
 * @param {string} newClass
 * @param isAddClass
 */
function changeClassForColumn ($thisTh, newClass, isAddClass = undefined) {
    // index 0 is the th containing the big T
    var thIndex = $thisTh.index();
    var hasBigT = $thisTh.closest('tr').children().first().hasClass('column_action');
    // .eq() is zero-based
    if (hasBigT) {
        thIndex--;
    }

    var $table = $thisTh.parents('.table_results');
    if (! $table.length) {
        $table = $thisTh.parents('table').siblings('.table_results');
    }

    var $tds = $table.find('tbody tr').find('td.data').eq(thIndex);
    if (isAddClass === undefined) {
        $tds.toggleClass(newClass);
    } else {
        $tds.toggleClass(newClass, isAddClass);
    }
}

/**
 * Handles browse foreign values modal dialog
 *
 * @param {object} $thisA reference to the browse foreign value link
 */
function browseForeignDialog ($thisA) {
    var formId = '#browse_foreign_form';
    var showAllId = '#foreign_showAll';
    var tableId = '#browse_foreign_table';
    var filterId = '#input_foreign_filter';
    var $dialog = null;
    var argSep = CommonParams.get('arg_separator');
    var params = $thisA.getPostData();
    params += argSep + 'ajax_request=true';
    $.post($thisA.attr('href'), params, function (data) {
        // Creates browse foreign value dialog
        $dialog = $('<div>').append(data.message).dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            title: window.Messages.strBrowseForeignValues,
            width: Math.min($(window).width() - 100, 700),
            maxHeight: $(window).height() - 100,
            dialogClass: 'browse_foreign_modal',
            close: function () {
                // remove event handlers attached to elements related to dialog
                $(tableId).off('click', 'td a.foreign_value');
                $(formId).off('click', showAllId);
                $(formId).off('submit');
                // remove dialog itself
                $(this).remove();
            },
            modal: true
        });
    }).done(function () {
        var showAll = false;
        $(tableId).on('click', 'td a.foreign_value', function (e) {
            e.preventDefault();
            var $input = $thisA.prev('input[type=text]');
            // Check if input exists or get CEdit edit_box
            if ($input.length === 0) {
                $input = $thisA.closest('.edit_area').prev('.edit_box');
            }

            // Set selected value as input value
            $input.val($(this).data('key'));
            // Unchecks the Ignore checkbox for the current row
            $input.trigger('change');

            $dialog.dialog('close');
        });

        $(formId).on('click', showAllId, function () {
            showAll = true;
        });

        $(formId).on('submit', function (e) {
            e.preventDefault();
            // if filter value is not equal to old value
            // then reset page number to 1
            if ($(filterId).val() !== $(filterId).data('old')) {
                $(formId).find('select[name=pos]').val('0');
            }

            var postParams = $(this).serializeArray();
            // if showAll button was clicked to submit form then
            // add showAll button parameter to form
            if (showAll) {
                postParams.push({
                    name: $(showAllId).attr('name'),
                    value: ($(showAllId).val() as string)
                });
            }

            // updates values in dialog
            $.post($(this).attr('action') + '&ajax_request=1', postParams, function (data) {
                var $obj = $('<div>').html(data.message);
                $(formId).html($obj.find(formId).html());
                $(tableId).html($obj.find(tableId).html());
            });

            showAll = false;
        });
    });
}

/**
 * Get the auto saved query key
 * @return {string}
 */
function getAutoSavedKey () {
    var db = $('input[name="db"]').val();
    var table = $('input[name="table"]').val();
    var key = db;
    if (table !== undefined) {
        key += '.' + table;
    }

    return 'autoSavedSql_' + key;
}

function checkSavedQuery () {
    var key = Sql.getAutoSavedKey();

    if (isStorageSupported('localStorage') &&
        typeof window.localStorage.getItem(key) === 'string') {
        ajaxShowMessage(window.Messages.strPreviousSaveQuery);
        // @ts-ignore
    } else if (window.Cookies.get(key, { path: CommonParams.get('rootPath') })) {
        ajaxShowMessage(window.Messages.strPreviousSaveQuery);
    }
}

AJAX.registerOnload('sql.js', function () {
    $('body').on('click', 'a.browse_foreign', function (e) {
        e.preventDefault();
        Sql.browseForeignDialog($(this));
    });

    /**
     * vertical column highlighting in horizontal mode when hovering over the column header
     */
    $(document).on('mouseenter', 'th.column_heading.pointer', function () {
        Sql.changeClassForColumn($(this), 'hover', true);
    });

    $(document).on('mouseleave', 'th.column_heading.pointer', function () {
        Sql.changeClassForColumn($(this), 'hover', false);
    });

    /**
     * vertical column marking in horizontal mode when clicking the column header
     */
    $(document).on('click', 'th.column_heading.marker', function () {
        Sql.changeClassForColumn($(this), 'marked');
    });

    /**
     * create resizable table
     */
    $('.sqlqueryresults').trigger('makegrid');

    /**
     * Check if there is any saved query
     */
    // @ts-ignore
    if (window.codeMirrorEditor || document.sqlform) {
        Sql.checkSavedQuery();
    }
});

/**
 * Profiling Chart
 */
function makeProfilingChart () {
    if ($('#profilingchart').length === 0 ||
        $('#profilingchart').html().length !== 0 ||
        ! $.jqplot || ! $.jqplot.Highlighter || ! $.jqplot.PieRenderer
    ) {
        return;
    }

    var data = [];
    $.each(JSON.parse($('#profilingChartData').html()), function (key, value) {
        data.push([key, parseFloat(value)]);
    });

    // Remove chart and data divs contents
    $('#profilingchart').html('').show();
    $('#profilingChartData').html('');

    createProfilingChart('profilingchart', data);
}

/**
 * initialize profiling data tables
 */
function initProfilingTables () {
    if (! $.tablesorter) {
        return;
    }

    // Added to allow two direction sorting
    $('#profiletable')
        .find('thead th')
        .off('click mousedown');

    $('#profiletable').tablesorter({
        widgets: ['zebra'],
        sortList: [[0, 0]],
        textExtraction: function (node) {
            if (node.children.length > 0) {
                return node.children[0].innerHTML;
            } else {
                return node.innerHTML;
            }
        }
    });

    // Added to allow two direction sorting
    $('#profilesummarytable')
        .find('thead th')
        .off('click mousedown');

    $('#profilesummarytable').tablesorter({
        widgets: ['zebra'],
        sortList: [[1, 1]],
        textExtraction: function (node) {
            if (node.children.length > 0) {
                return node.children[0].innerHTML;
            } else {
                return node.innerHTML;
            }
        }
    });
}

AJAX.registerOnload('sql.js', function () {
    Sql.makeProfilingChart();
    Sql.initProfilingTables();
});

const Sql = {
    urlDecode: urlDecode,
    urlEncode: urlEncode,
    autoSave: autoSave,
    showThisQuery: showThisQuery,
    setShowThisQuery: setShowThisQuery,
    autoSaveWithSort: autoSaveWithSort,
    clearAutoSavedSort: clearAutoSavedSort,
    getFieldName: getFieldName,
    changeClassForColumn: changeClassForColumn,
    browseForeignDialog: browseForeignDialog,
    getAutoSavedKey: getAutoSavedKey,
    checkSavedQuery: checkSavedQuery,
    makeProfilingChart: makeProfilingChart,
    initProfilingTables: initProfilingTables,
};

declare global {
    interface Window {
        Sql: typeof Sql;
    }
}

window.Sql = Sql;
