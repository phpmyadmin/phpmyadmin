/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used wherever an sql query form is used
 *
 * @requires    jQuery
 * @requires    js/functions.js
 *
 */

/* global isStorageSupported */ // js/config.js
/* global codeMirrorEditor */ // js/functions.js
/* global MicroHistory */ // js/microhistory.js
/* global makeGrid */ // js/makegrid.js

var Sql = {};

var prevScrollX = 0;

/**
 * decode a string URL_encoded
 *
 * @param string str
 * @return string the URL-decoded string
 */
Sql.urlDecode = function (str) {
    if (typeof str !== 'undefined') {
        return decodeURIComponent(str.replace(/\+/g, '%20'));
    }
};

/**
 * endecode a string URL_decoded
 *
 * @param string str
 * @return string the URL-encoded string
 */
Sql.urlEncode = function (str) {
    if (typeof str !== 'undefined') {
        return encodeURIComponent(str).replace(/%20/g, '+');
    }
};

/**
 * Saves SQL query in local storage or cookie
 *
 * @param string SQL query
 * @return void
 */
Sql.autoSave = function (query) {
    if (isStorageSupported('localStorage')) {
        window.localStorage.autoSavedSql = query;
    } else {
        Cookies.set('autoSavedSql', query);
    }
};

/**
 * Saves SQL query in local storage or cookie
 *
 * @param string database name
 * @param string table name
 * @param string SQL query
 * @return void
 */
Sql.showThisQuery = function (db, table, query) {
    var showThisQueryObject = {
        'db': db,
        'table': table,
        'query': query
    };
    if (isStorageSupported('localStorage')) {
        window.localStorage.showThisQuery = 1;
        window.localStorage.showThisQueryObject = JSON.stringify(showThisQueryObject);
    } else {
        Cookies.set('showThisQuery', 1);
        Cookies.set('showThisQueryObject', JSON.stringify(showThisQueryObject));
    }
};

/**
 * Set query to codemirror if show this query is
 * checked and query for the db and table pair exists
 */
Sql.setShowThisQuery = function () {
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
                if (codeMirrorEditor) {
                    codeMirrorEditor.setValue(storedQuery);
                } else if (document.sqlform) {
                    document.sqlform.sql_query.value = storedQuery;
                }
            }
        } else {
            $('input[name="show_query"]').prop('checked', false);
        }
    }
};

/**
 * Saves SQL query with sort in local storage or cookie
 *
 * @param string SQL query
 * @return void
 */
Sql.autoSaveWithSort = function (query) {
    if (query) {
        if (isStorageSupported('localStorage')) {
            window.localStorage.autoSavedSqlSort = query;
        } else {
            Cookies.set('autoSavedSqlSort', query);
        }
    }
};

/**
 * Clear saved SQL query with sort in local storage or cookie
 *
 * @return void
 */
Sql.clearAutoSavedSort = function () {
    if (isStorageSupported('localStorage')) {
        window.localStorage.removeItem('auto_saved_sql_sort');
    } else {
        Cookies.set('auto_saved_sql_sort', '');
    }
};

/**
 * Get the field name for the current field.  Required to construct the query
 * for grid editing
 *
 * @param $tableResults enclosing results table
 * @param $thisField    jQuery object that points to the current field's tr
 */
Sql.getFieldName = function ($tableResults, $thisField) {
    var thisFieldIndex = $thisField.index();
    // ltr or rtl direction does not impact how the DOM was generated
    // check if the action column in the left exist
    var leftActionExist = !$tableResults.find('th:first').hasClass('draggable');
    // number of column span for checkbox and Actions
    var leftActionSkip = leftActionExist ? $tableResults.find('th:first').attr('colspan') - 1 : 0;

    // If this column was sorted, the text of the a element contains something
    // like <small>1</small> that is useful to indicate the order in case
    // of a sort on multiple columns; however, we dont want this as part
    // of the column name so we strip it ( .clone() to .end() )
    var fieldName = $tableResults
        .find('thead')
        .find('th:eq(' + (thisFieldIndex - leftActionSkip) + ') a')
        .clone()    // clone the element
        .children() // select all the children
        .remove()   // remove all of them
        .end()      // go back to the selected element
        .text();    // grab the text
    // happens when just one row (headings contain no a)
    if (fieldName === '') {
        var $heading = $tableResults.find('thead').find('th:eq(' + (thisFieldIndex - leftActionSkip) + ')').children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        fieldName = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    fieldName = $.trim(fieldName);

    return fieldName;
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('sql.js', function () {
    $(document).off('click', 'a.delete_row.ajax');
    $(document).off('submit', '.bookmarkQueryForm');
    $('input#bkm_label').off('input');
    $(document).off('makegrid', '.sqlqueryresults');
    $(document).off('stickycolumns', '.sqlqueryresults');
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
    $(document).off('scroll', window);
    $(document).off('keyup', '.filter_rows');
    $(document).off('click', '#printView');
    if (codeMirrorEditor) {
        codeMirrorEditor.off('change');
    } else {
        $('#sqlquery').off('input propertychange');
    }
    $('body').off('click', '.navigation .showAllRows');
    $('body').off('click', 'a.browse_foreign');
    $('body').off('click', '#simulate_dml');
    $('body').off('keyup', '#sqlqueryform');
    $('body').off('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]');
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
    if (codeMirrorEditor || document.sqlform) {
        Sql.setShowThisQuery();
    }
    $(function () {
        if (codeMirrorEditor) {
            codeMirrorEditor.on('change', function () {
                Sql.autoSave(codeMirrorEditor.getValue());
            });
        } else {
            $('#sqlquery').on('input propertychange', function () {
                Sql.autoSave($('#sqlquery').val());
            });
            var useLocalStorageValue = isStorageSupported('localStorage') && typeof window.localStorage.auto_saved_sql_sort !== 'undefined';
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
            var sortStoredQuery = useLocalStorageValue ? window.localStorage.auto_saved_sql_sort : Cookies.get('auto_saved_sql_sort');
            if (typeof sortStoredQuery !== 'undefined' && sortStoredQuery !== $('select[name="sql_query"]').val() && $('select[name="sql_query"] option[value="' + sortStoredQuery + '"]').length !== 0) {
                $('select[name="sql_query"]').val(sortStoredQuery).trigger('change');
            }
        }
    });

    // Delete row from SQL results
    $(document).on('click', 'a.delete_row.ajax', function (e) {
        e.preventDefault();
        var question =  Functions.sprintf(Messages.strDoYouReally, Functions.escapeHtml($(this).closest('td').find('div').text()));
        var $link = $(this);
        $link.confirm(question, $link.attr('href'), function (url) {
            Functions.ajaxShowMessage();
            var argsep = CommonParams.get('arg_separator');
            var params = 'ajax_request=1' + argsep + 'is_js_confirmed=1';
            var postData = $link.getPostData();
            if (postData) {
                params += argsep + postData;
            }
            $.post(url, params, function (data) {
                if (data.success) {
                    Functions.ajaxShowMessage(data.message);
                    $link.closest('tr').remove();
                } else {
                    Functions.ajaxShowMessage(data.error, false);
                }
            });
        });
    });

    // Ajaxification for 'Bookmark this SQL query'
    $(document).on('submit', '.bookmarkQueryForm', function (e) {
        e.preventDefault();
        Functions.ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        $.post($(this).attr('action'), 'ajax_request=1' + argsep + $(this).serialize(), function (data) {
            if (data.success) {
                Functions.ajaxShowMessage(data.message);
            } else {
                Functions.ajaxShowMessage(data.error, false);
            }
        });
    });

    /* Hides the bookmarkoptions checkboxes when the bookmark label is empty */
    $('input#bkm_label').on('input', function () {
        $('input#id_bkm_all_users, input#id_bkm_replace')
            .parent()
            .toggle($(this).val().length > 0);
    }).trigger('input');

    /**
     * Attach Event Handler for 'Copy to clipbpard
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
        textArea.style.top = 0;
        textArea.style.left = 0;

        // Ensure it has a small width and height. Setting to 1px / 1em
        // doesn't work as this gives a negative w/h on some browsers.
        textArea.style.width = '2em';
        textArea.style.height = '2em';

        // We don't need padding, reducing the size if it does flash render.
        textArea.style.padding = 0;

        // Clean up any borders.
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';

        // Avoid flash of white box if rendered for any reason.
        textArea.style.background = 'transparent';

        textArea.value = '';

        $('#serverinfo a').each(function () {
            textArea.value += $(this).text().split(':')[1].trim() + '/';
        });
        textArea.value += '\t\t' + window.location.href;
        textArea.value += '\n';
        $('.success').each(function () {
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
                textArea.value += $(this).text() + '\t';
            });
            textArea.value += '\n';
        });

        document.body.appendChild(textArea);

        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            alert('Sorry! Unable to copy');
        }

        document.body.removeChild(textArea);
    }); // end of Copy to Clipboard action

    /**
     * Attach Event Handler for 'Print' link
     */
    $(document).on('click', '#printView', function (event) {
        event.preventDefault();

        // Take to preview mode
        Functions.printPreview();
    }); // end of 'Print' action

    /**
     * Attach the {@link makegrid} function to a custom event, which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $(document).on('makegrid', '.sqlqueryresults', function () {
        $('.table_results').each(function () {
            makeGrid(this);
        });
    });

    /*
     * Attach a custom event for sticky column headings which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $(document).on('stickycolumns', '.sqlqueryresults', function () {
        $('.sticky_columns').remove();
        $('.table_results').each(function () {
            var $tableResults = $(this);
            // add sticky columns div
            var $stickColumns = Sql.initStickyColumns($tableResults);
            Sql.rearrangeStickyColumns($stickColumns, $tableResults);
            // adjust sticky columns on scroll
            $(document).on('scroll', window, function () {
                Sql.handleStickyColumns($stickColumns, $tableResults);
            });
        });
    });

    /**
     * Append the "Show/Hide query box" message to the query input form
     *
     * @memberOf jQuery
     * @name    appendToggleSpan
     */
    // do not add this link more than once
    if (! $('#sqlqueryform').find('a').is('#togglequerybox')) {
        $('<a id="togglequerybox"></a>')
            .html(Messages.strHideQueryBox)
            .appendTo('#sqlqueryform')
        // initially hidden because at this point, nothing else
        // appears under the link
            .hide();

        // Attach the toggling of the query box visibility to a click
        $('#togglequerybox').on('click', function () {
            var $link = $(this);
            $link.siblings().slideToggle('fast');
            if ($link.text() === Messages.strHideQueryBox) {
                $link.text(Messages.strShowQueryBox);
                // cheap trick to add a spacer between the menu tabs
                // and "Show query box"; feel free to improve!
                $('#togglequerybox_spacer').remove();
                $link.before('<br id="togglequerybox_spacer">');
            } else {
                $link.text(Messages.strHideQueryBox);
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
        $('.success,.error').hide();
        // hide already existing error or success message
        var $form = $(this).closest('form');
        // the Go button related to query submission was clicked,
        // instead of the one related to Bookmarks, so empty the
        // id_bookmark selector to avoid misinterpretation in
        // import.php about what needs to be done
        $form.find('select[name=id_bookmark]').val('');
        // let normal event propagation happen
        if (isStorageSupported('localStorage')) {
            window.localStorage.removeItem('autoSavedSql');
        } else {
            Cookies.set('autoSavedSql', '');
        }
        var isShowQuery =  $('input[name="show_query"]').is(':checked');
        if (isShowQuery) {
            window.localStorage.showThisQuery = '1';
            var db = $('input[name="db"]').val();
            var table = $('input[name="table"]').val();
            var query;
            if (codeMirrorEditor) {
                query = codeMirrorEditor.getValue();
            } else {
                query = $('#sqlquery').val();
            }
            Sql.showThisQuery(db, table, query);
        } else {
            window.localStorage.showThisQuery = '0';
        }
    });

    /**
     * Event handler to show appropiate number of variable boxes
     * based on the bookmarked query
     */
    $(document).on('change', '#id_bookmark', function () {
        var varCount = $(this).find('option:selected').data('varcount');
        if (typeof varCount === 'undefined') {
            varCount = 0;
        }

        var $varDiv = $('#bookmark_variables');
        $varDiv.empty();
        for (var i = 1; i <= varCount; i++) {
            $varDiv.append($('<label for="bookmark_variable_' + i + '">' + Functions.sprintf(Messages.strBookmarkVariable, i) + '</label>'));
            $varDiv.append($('<input type="text" size="10" name="bookmark_variable[' + i + ']" id="bookmark_variable_' + i + '">'));
        }

        if (varCount === 0) {
            $varDiv.parent('.formelement').hide();
        } else {
            $varDiv.parent('.formelement').show();
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
        } else  {
            return true;
        }
    });

    /**
     * Ajax Event handler for 'SQL Query Submit'
     *
     * @see         Functions.ajaxShowMessage()
     * @memberOf    jQuery
     * @name        sqlqueryform_submit
     */
    $(document).on('submit', '#sqlqueryform.ajax', function (event) {
        event.preventDefault();

        var $form = $(this);
        if (codeMirrorEditor) {
            $form[0].elements.sql_query.value = codeMirrorEditor.getValue();
        }
        if (! Functions.checkSqlQuery($form[0])) {
            return false;
        }

        // remove any div containing a previous error message
        $('div.error').remove();

        var $msgbox = Functions.ajaxShowMessage();
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
                        Functions.setQuery(data.sql_query);
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
                Functions.highlightSql($sqlqueryresultsouter);

                if (data.menu) {
                    if (history && history.pushState) {
                        history.replaceState({
                            menu : data.menu
                        },
                        null
                        );
                        AJAX.handleMenu.replace(data.menu);
                    } else {
                        MicroHistory.menus.replace(data.menu);
                        MicroHistory.menus.add(data.menuHash, data.menu);
                    }
                } else if (data.menuHash) {
                    if (! (history && history.pushState)) {
                        MicroHistory.menus.replace(MicroHistory.menus.get(data.menuHash));
                    }
                }

                if (data.params) {
                    CommonParams.setAll(data.params);
                }

                if (typeof data.ajax_reload !== 'undefined') {
                    if (data.ajax_reload.reload) {
                        if (data.ajax_reload.table_name) {
                            CommonParams.set('table', data.ajax_reload.table_name);
                            CommonActions.refreshMain();
                        } else {
                            Navigation.reload();
                        }
                    }
                } else if (typeof data.reload !== 'undefined') {
                    // this happens if a USE or DROP command was typed
                    CommonActions.setDb(data.db);
                    var url;
                    if (data.db) {
                        if (data.table) {
                            url = 'table_sql.php';
                        } else {
                            url = 'db_sql.php';
                        }
                    } else {
                        url = 'server_sql.php';
                    }
                    CommonActions.refreshMain(url, function () {
                        $('#sqlqueryresultsouter')
                            .show()
                            .html(data.message);
                        Functions.highlightSql($('#sqlqueryresultsouter'));
                    });
                }

                $('.sqlqueryresults').trigger('makegrid').trigger('stickycolumns');
                $('#togglequerybox').show();
                Functions.initSlider();

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
            Functions.ajaxRemoveMessage($msgbox);
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

        var $msgbox = Functions.ajaxShowMessage();
        var argsep = CommonParams.get('arg_separator');
        $.post($form.attr('action'), $form.serialize() + argsep + 'ajax_request=true', function (data) {
            Functions.ajaxRemoveMessage($msgbox);
            var $sqlqueryresults = $form.parents('.sqlqueryresults');
            $sqlqueryresults
                .html(data.message)
                .trigger('makegrid')
                .trigger('stickycolumns');
            Functions.initSlider();
            Functions.highlightSql($sqlqueryresults);
        }); // end $.post()
    }); // end displayOptionsForm handler

    // Filter row handling. --STARTS--
    $(document).on('keyup', '.filter_rows', function () {
        var uniqueId = $(this).data('for');
        var $targetTable = $('.table_results[data-uniqueId=\'' + uniqueId + '\']');
        var $headerCells = $targetTable.find('th[data-column]');
        var targetColumns = [];
        // To handle colspan=4, in case of edit,copy etc options.
        var dummyTh = ($('.edit_row_anchor').length !== 0 ?
            '<th class="hide dummy_th"></th><th class="hide dummy_th"></th><th class="hide dummy_th"></th>'
            : '');
        // Selecting columns that will be considered for filtering and searching.
        $headerCells.each(function () {
            targetColumns.push($.trim($(this).text()));
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

        if (! $(this).is(':checked')) { // already showing all rows
            Sql.submitShowAllForm();
        } else {
            $form.confirm(Messages.strShowAllRowsWarning, $form.attr('action'), function () {
                Sql.submitShowAllForm();
            });
        }

        Sql.submitShowAllForm = function () {
            var argsep = CommonParams.get('arg_separator');
            var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            Functions.ajaxShowMessage();
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        };
    });

    $('body').on('keyup', '#sqlqueryform', function () {
        Functions.handleSimulateQueryButton();
    });

    /**
     * Ajax event handler for 'Simulate DML'.
     */
    $('body').on('click', '#simulate_dml', function () {
        var $form = $('#sqlqueryform');
        var query = '';
        var delimiter = $('#id_sql_delimiter').val();
        var dbName = $form.find('input[name="db"]').val();

        if (codeMirrorEditor) {
            query = codeMirrorEditor.getValue();
        } else {
            query = $('#sqlquery').val();
        }

        if (query.length === 0) {
            alert(Messages.strFormEmpty);
            $('#sqlquery').trigger('focus');
            return false;
        }

        var $msgbox = Functions.ajaxShowMessage();
        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: {
                'server': CommonParams.get('server'),
                'db': dbName,
                'ajax_request': '1',
                'simulate_dml': '1',
                'sql_query': query,
                'sql_delimiter': delimiter
            },
            success: function (response) {
                Functions.ajaxRemoveMessage($msgbox);
                if (response.success) {
                    var dialogContent = '<div class="preview_sql">';
                    if (response.sql_data) {
                        var len = response.sql_data.length;
                        for (var i = 0; i < len; i++) {
                            dialogContent += '<strong>' + Messages.strSQLQuery +
                                '</strong>' + response.sql_data[i].sql_query +
                                Messages.strMatchedRows +
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
                    var buttonOptions = {};
                    buttonOptions[Messages.strClose] = function () {
                        $(this).dialog('close');
                    };
                    $('<div></div>').append($dialogContent).dialog({
                        minWidth: 540,
                        maxHeight: 400,
                        modal: true,
                        buttons: buttonOptions,
                        title: Messages.strSimulateDML,
                        open: function () {
                            Functions.highlightSql($(this));
                        },
                        close: function () {
                            $(this).remove();
                        }
                    });
                } else {
                    Functions.ajaxShowMessage(response.error);
                }
            },
            error: function () {
                Functions.ajaxShowMessage(Messages.strErrorProcessingRequest);
            }
        });
    });

    /**
     * Handles multi submits of results browsing page such as edit, delete and export
     */
    $('body').on('click', 'form[name="resultsForm"].ajax button[name="submit_mult"], form[name="resultsForm"].ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.closest('form');
        var argsep = CommonParams.get('arg_separator');
        var submitData = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true' + argsep + 'submit_mult=' + $button.val();
        Functions.ajaxShowMessage();
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });
}); // end $()

/**
 * Starting from some th, change the class of all td under it.
 * If isAddClass is specified, it will be used to determine whether to add or remove the class.
 */
Sql.changeClassForColumn = function ($thisTh, newClass, isAddClass) {
    // index 0 is the th containing the big T
    var thIndex = $thisTh.index();
    var hasBigT = $thisTh.closest('tr').children(':first').hasClass('column_action');
    // .eq() is zero-based
    if (hasBigT) {
        thIndex--;
    }
    var $table = $thisTh.parents('.table_results');
    if (! $table.length) {
        $table = $thisTh.parents('table').siblings('.table_results');
    }
    var $tds = $table.find('tbody tr').find('td.data:eq(' + thIndex + ')');
    if (isAddClass === undefined) {
        $tds.toggleClass(newClass);
    } else {
        $tds.toggleClass(newClass, isAddClass);
    }
};

/**
 * Handles browse foreign values modal dialog
 *
 * @param object $this_a reference to the browse foreign value link
 */
Sql.browseForeignDialog = function ($thisA) {
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
            title: Messages.strBrowseForeignValues,
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
                    value: $(showAllId).val()
                });
            }
            // updates values in dialog
            $.post($(this).attr('action') + '?ajax_request=1', postParams, function (data) {
                var $obj = $('<div>').html(data.message);
                $(formId).html($obj.find(formId).html());
                $(tableId).html($obj.find(tableId).html());
            });
            showAll = false;
        });
    });
};

Sql.checkSavedQuery = function () {
    if (isStorageSupported('localStorage') && window.localStorage.autoSavedSql !== undefined) {
        Functions.ajaxShowMessage(Messages.strPreviousSaveQuery);
    }
};

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
    $('.sqlqueryresults').trigger('makegrid').trigger('stickycolumns');

    /**
     * Check if there is any saved query
     */
    if (codeMirrorEditor || document.sqlform) {
        Sql.checkSavedQuery();
    }
});

/**
 * Profiling Chart
 */
Sql.makeProfilingChart = function () {
    if ($('#profilingchart').length === 0 ||
        $('#profilingchart').html().length !== 0 ||
        !$.jqplot || !$.jqplot.Highlighter || !$.jqplot.PieRenderer
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

    Functions.createProfilingChart('profilingchart', data);
};

/**
 * initialize profiling data tables
 */
Sql.initProfilingTables = function () {
    if (!$.tablesorter) {
        return;
    }

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
};

/**
 * Set position, left, top, width of sticky_columns div
 */
Sql.setStickyColumnsPosition = function ($stickyColumns, $tableResults, position, top, left, marginLeft) {
    $stickyColumns
        .css('position', position)
        .css('top', top)
        .css('left', left ? left : 'auto')
        .css('margin-left', marginLeft ? marginLeft : '0px')
        .css('width', $tableResults.width());
};

/**
 * Initialize sticky columns
 */
Sql.initStickyColumns = function ($tableResults) {
    return $('<table class="sticky_columns"></table>')
        .insertBefore($tableResults)
        .css('position', 'fixed')
        .css('z-index', '98')
        .css('width', $tableResults.width())
        .css('margin-left', $('#page_content').css('margin-left'))
        .css('top', $('#floating_menubar').height())
        .css('display', 'none');
};

/**
 * Arrange/Rearrange columns in sticky header
 */
Sql.rearrangeStickyColumns = function ($stickyColumns, $tableResults) {
    var $originalHeader = $tableResults.find('thead');
    var $originalColumns = $originalHeader.find('tr:first').children();
    var $clonedHeader = $originalHeader.clone();
    var isFirefox = navigator.userAgent.indexOf('Firefox') > -1;
    var isSafari = navigator.userAgent.indexOf('Safari') > -1;
    // clone width per cell
    $clonedHeader.find('tr:first').children().each(function (i) {
        var width = $originalColumns.eq(i).width();
        if (! isFirefox && ! isSafari) {
            width += 1;
        }
        $(this).width(width);
        if (isSafari) {
            $(this).css('min-width', width).css('max-width', width);
        }
    });
    $stickyColumns.empty().append($clonedHeader);
};

/**
 * Adjust sticky columns on horizontal/vertical scroll for all tables
 */
Sql.handleAllStickyColumns = function () {
    $('.sticky_columns').each(function () {
        Sql.handleStickyColumns($(this), $(this).next('.table_results'));
    });
};

/**
 * Adjust sticky columns on horizontal/vertical scroll
 */
Sql.handleStickyColumns = function ($stickyColumns, $tableResults) {
    var currentScrollX = $(window).scrollLeft();
    var windowOffset = $(window).scrollTop();
    var tableStartOffset = $tableResults.offset().top;
    var tableEndOffset = tableStartOffset + $tableResults.height();
    if (windowOffset >= tableStartOffset && windowOffset <= tableEndOffset) {
        // for horizontal scrolling
        if (prevScrollX !== currentScrollX) {
            prevScrollX = currentScrollX;
            Sql.setStickyColumnsPosition($stickyColumns, $tableResults, 'absolute', $('#floating_menubar').height() + windowOffset - tableStartOffset);
        // for vertical scrolling
        } else {
            Sql.setStickyColumnsPosition($stickyColumns, $tableResults, 'fixed', $('#floating_menubar').height(), $('#pma_navigation').width() - currentScrollX, $('#page_content').css('margin-left'));
        }
        $stickyColumns.show();
    } else {
        $stickyColumns.hide();
    }
};

AJAX.registerOnload('sql.js', function () {
    Sql.makeProfilingChart();
    Sql.initProfilingTables();
});
