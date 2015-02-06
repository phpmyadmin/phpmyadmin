/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used wherever an sql query form is used
 *
 * @requires    jQuery
 * @requires    js/functions.js
 *
 */

var $data_a;
var prevScrollX = 0, fixedTop;

/**
 * decode a string URL_encoded
 *
 * @param string str
 * @return string the URL-decoded string
 */
function PMA_urldecode(str)
{
    if (typeof str !== 'undefined') {
        return decodeURIComponent(str.replace(/\+/g, '%20'));
    }
}

/**
 * endecode a string URL_decoded
 *
 * @param string str
 * @return string the URL-encoded string
 */
function PMA_urlencode(str)
{
    if (typeof str !== 'undefined') {
        return encodeURIComponent(str).replace(/\%20/g, '+');
    }
}

/**
 * Get the field name for the current field.  Required to construct the query
 * for grid editing
 *
 * @param $this_field  jQuery object that points to the current field's tr
 */
function getFieldName($this_field)
{

    var this_field_index = $this_field.index();
    // ltr or rtl direction does not impact how the DOM was generated
    // check if the action column in the left exist
    var left_action_exist = !$('#table_results').find('th:first').hasClass('draggable');
    // number of column span for checkbox and Actions
    var left_action_skip = left_action_exist ? $('#table_results').find('th:first').attr('colspan') - 1 : 0;

    // If this column was sorted, the text of the a element contains something
    // like <small>1</small> that is useful to indicate the order in case
    // of a sort on multiple columns; however, we dont want this as part
    // of the column name so we strip it ( .clone() to .end() )
    var field_name = $('#table_results')
        .find('thead')
        .find('th:eq(' + (this_field_index - left_action_skip) + ') a')
        .clone()    // clone the element
        .children() // select all the children
        .remove()   // remove all of them
        .end()      // go back to the selected element
        .text();    // grab the text
    // happens when just one row (headings contain no a)
    if (field_name === '') {
        var $heading = $('#table_results').find('thead').find('th:eq(' + (this_field_index - left_action_skip) + ')').children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        field_name = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    field_name = $.trim(field_name);

    return field_name;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('sql.js', function () {
    $('a.delete_row.ajax').die('click');
    $('#bookmarkQueryForm').die('submit');
    $('input#bkm_label').unbind('keyup');
    $("#sqlqueryresults").die('makegrid');
    $("#sqlqueryresults").die('stickycolumns');
    $("#togglequerybox").unbind('click');
    $("#button_submit_query").die('click');
    $("input[name=bookmark_variable]").unbind("keypress");
    $("#sqlqueryform.ajax").die('submit');
    $("input[name=navig].ajax").die('click');
    $("#pageselector").die('change');
    $("#table_results.ajax").find("a[title=Sort]").die('click');
    $("#displayOptionsForm.ajax").die('submit');
    $('th.column_heading.pointer').die('hover');
    $('th.column_heading.marker').die('click');
    $(window).unbind('scroll');
    $(".filter_rows").die("keyup");
    $('body').off('click', '.navigation .showAllRows');
    $('body').off('click','a.browse_foreign');
    $('body').off('click', '#simulate_dml');
    $('body').off('keyup', '#sqlqueryform');
    $('body').off('click', '#resultsForm.ajax button[name="submit_mult"], #resultsForm.ajax input[name="submit_mult"]');
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
    // Delete row from SQL results
    $('a.delete_row.ajax').live('click', function (e) {
        e.preventDefault();
        var question =  PMA_sprintf(PMA_messages.strDoYouReally, escapeHtml($(this).closest('td').find('div').text()));
        var $link = $(this);
        $link.PMA_confirm(question, $link.attr('href'), function (url) {
            $msgbox = PMA_ajaxShowMessage();
            $.get(url, {'ajax_request': true, 'is_js_confirmed': true}, function (data) {
                if (data.success) {
                    PMA_ajaxShowMessage(data.message);
                    $link.closest('tr').remove();
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            });
        });
    });

    // Ajaxification for 'Bookmark this SQL query'
    $('#bookmarkQueryForm').live('submit', function (e) {
        e.preventDefault();
        PMA_ajaxShowMessage();
        $.post($(this).attr('action'), 'ajax_request=1&' + $(this).serialize(), function (data) {
            if (data.success) {
                PMA_ajaxShowMessage(data.message);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        });
    });

    /* Hides the bookmarkoptions checkboxes when the bookmark label is empty */
    $('input#bkm_label').keyup(function () {
        $('input#id_bkm_all_users, input#id_bkm_replace')
            .parent()
            .toggle($(this).val().length > 0);
    }).trigger('keyup');

    /**
     * Attach the {@link makegrid} function to a custom event, which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $("#sqlqueryresults").live('makegrid', function () {
        PMA_makegrid($('#table_results')[0]);
    });

    /*
     * Attach a custom event for sticky column headings which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $("#sqlqueryresults").live('stickycolumns', function () {
        if ($("#table_results").length === 0) {
            return;
        }
        //add sticky columns div
        initStickyColumns();
        rearrangeStickyColumns();
        //adjust sticky columns on scroll
        $(window).bind('scroll', function() {
            handleStickyColumns();
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
        .html(PMA_messages.strHideQueryBox)
        .appendTo("#sqlqueryform")
        // initially hidden because at this point, nothing else
        // appears under the link
        .hide();

        // Attach the toggling of the query box visibility to a click
        $("#togglequerybox").bind('click', function () {
            var $link = $(this);
            $link.siblings().slideToggle("fast");
            if ($link.text() == PMA_messages.strHideQueryBox) {
                $link.text(PMA_messages.strShowQueryBox);
                // cheap trick to add a spacer between the menu tabs
                // and "Show query box"; feel free to improve!
                $('#togglequerybox_spacer').remove();
                $link.before('<br id="togglequerybox_spacer" />');
            } else {
                $link.text(PMA_messages.strHideQueryBox);
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
    $("#button_submit_query").live('click', function (event) {
        $(".success,.error").hide();
        //hide already existing error or success message
        var $form = $(this).closest("form");
        // the Go button related to query submission was clicked,
        // instead of the one related to Bookmarks, so empty the
        // id_bookmark selector to avoid misinterpretation in
        // import.php about what needs to be done
        $form.find("select[name=id_bookmark]").val("");
        // let normal event propagation happen
    });

    /**
     * Event handler for hitting enter on sqlqueryform bookmark_variable
     * (the Variable textfield in Bookmarked SQL query section)
     *
     * @memberOf    jQuery
     */
    $("input[name=bookmark_variable]").bind("keypress", function (event) {
        // force the 'Enter Key' to implicitly click the #button_submit_bookmark
        var keycode = (event.keyCode ? event.keyCode : (event.which ? event.which : event.charCode));
        if (keycode == 13) { // keycode for enter key
            // When you press enter in the sqlqueryform, which
            // has 2 submit buttons, the default is to run the
            // #button_submit_query, because of the tabindex
            // attribute.
            // This submits #button_submit_bookmark instead,
            // because when you are in the Bookmarked SQL query
            // section and hit enter, you expect it to do the
            // same action as the Go button in that section.
            $("#button_submit_bookmark").click();
            return false;
        } else  {
            return true;
        }
    });

    /**
     * Ajax Event handler for 'SQL Query Submit'
     *
     * @see         PMA_ajaxShowMessage()
     * @memberOf    jQuery
     * @name        sqlqueryform_submit
     */
    $("#sqlqueryform.ajax").live('submit', function (event) {
        event.preventDefault();

        var $form = $(this);
        if (codemirror_editor) {
            $form[0].elements.sql_query.value = codemirror_editor.getValue();
        }
        if (! checkSqlQuery($form[0])) {
            return false;
        }

        // remove any div containing a previous error message
        $('div.error').remove();

        var $msgbox = PMA_ajaxShowMessage();
        var $sqlqueryresults = $('#sqlqueryresults');

        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize(), function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                // success happens if the query returns rows or not
                //
                // fade out previous messages, if any
                $('div.success, div.sqlquery_message').fadeOut();
                if ($('#result_query').length) {
                    $('#result_query').remove();
                }

                // show a message that stays on screen
                if (typeof data.action_bookmark != 'undefined') {
                    // view only
                    if ('1' == data.action_bookmark) {
                        $('#sqlquery').text(data.sql_query);
                        // send to codemirror if possible
                        setQuery(data.sql_query);
                    }
                    // delete
                    if ('2' == data.action_bookmark) {
                        $("#id_bookmark option[value='" + data.id_bookmark + "']").remove();
                        // if there are no bookmarked queries now (only the empty option),
                        // remove the bookmark section
                        if ($('#id_bookmark option').length == 1) {
                            $('#fieldsetBookmarkOptions').hide();
                            $('#fieldsetBookmarkOptionsFooter').hide();
                        }
                    }
                    $sqlqueryresults
                     .show()
                     .html(data.message);
                } else if (typeof data.sql_query != 'undefined') {
                    $('<div class="sqlquery_message"></div>')
                     .html(data.sql_query)
                     .insertBefore('#sqlqueryform');
                    // unnecessary div that came from data.sql_query
                    $('div.notice').remove();
                } else {
                    $sqlqueryresults
                     .show()
                     .html(data.message);
                }
                PMA_highlightSQL($('#result_query'));

                if (data._menu) {
                    AJAX.cache.menus.replace(data._menu);
                    AJAX.cache.menus.add(data._menuHash, data._menu);
                } else if (data._menuHash) {
                    AJAX.cache.menus.replace(AJAX.cache.menus.get(data._menuHash));
                }

                if (data._params) {
                    PMA_commonParams.setAll(data._params);
                }

                if (typeof data.ajax_reload != 'undefined') {
                    if (data.ajax_reload.reload) {
                        if (data.ajax_reload.table_name) {
                            PMA_commonParams.set('table', data.ajax_reload.table_name);
                            PMA_commonActions.refreshMain();
                        } else {
                            PMA_reloadNavigation();
                        }
                    }
                } else if (typeof data.reload != 'undefined') {
                    // this happens if a USE or DROP command was typed
                    PMA_commonActions.setDb(data.db);
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
                    PMA_commonActions.refreshMain(url, function () {
                        if ($('#result_query').length) {
                            $('#result_query').remove();
                        }
                        if (data.sql_query) {
                            $('<div id="result_query"></div>')
                                .html(data.sql_query)
                                .prependTo('#page_content');
                            PMA_highlightSQL($('#page_content'));
                        }
                    });
                }

                $sqlqueryresults.show().trigger('makegrid').trigger('stickycolumns');
                $('#togglequerybox').show();
                PMA_init_slider();

                if (typeof data.action_bookmark == 'undefined') {
                    if ($('#sqlqueryform input[name="retain_query_box"]').is(':checked') !== true) {
                        if ($("#togglequerybox").siblings(":visible").length > 0) {
                            $("#togglequerybox").trigger('click');
                        }
                    }
                }
            } else if (typeof data !== 'undefined' && data.success === false) {
                // show an error message that stays on screen
                $('#sqlqueryform').before(data.error);
                $sqlqueryresults.hide();
            }
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.post()
    }); // end SQL Query submit

    /**
     * Paginate results with Page Selector dropdown
     * @memberOf    jQuery
     * @name        paginate_dropdown_change
     */
    $("#pageselector").live('change', function (event) {
        var $form = $(this).parent("form");
        $form.submit();
    }); // end Paginate results with Page Selector

    /**
     * Ajax Event handler for the display options
     * @memberOf    jQuery
     * @name        displayOptionsForm_submit
     */
    $("#displayOptionsForm.ajax").live('submit', function (event) {
        event.preventDefault();

        $form = $(this);

        var $msgbox = PMA_ajaxShowMessage();
        $.post($form.attr('action'), $form.serialize() + '&ajax_request=true', function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            var $sqlqueryresults = $("#sqlqueryresults");
            $sqlqueryresults
             .html(data.message)
             .trigger('makegrid');
            PMA_init_slider();
            PMA_highlightSQL($sqlqueryresults);
        }); // end $.post()
    }); //end displayOptionsForm handler

    // Filter row handling. --STARTS--
    $(".filter_rows").live("keyup", function () {
        var $target_table = $("#table_results");
        var $header_cells = $target_table.find("th[data-column]");
        var target_columns = Array();
        // To handle colspan=4, in case of edit,copy etc options.
        var dummy_th = ($(".edit_row_anchor").length !== 0 ?
            '<th class="hide dummy_th"></th><th class="hide dummy_th"></th><th class="hide dummy_th"></th>'
            : '');
        // Selecting columns that will be considered for filtering and searching.
        $header_cells.each(function () {
            target_columns.push($.trim($(this).text()));
        });

        var phrase = $(this).val();
        // Set same value to both Filter rows fields.
        $(".filter_rows").not(this).val(phrase);
        // Handle colspan.
        $target_table.find("thead > tr").prepend(dummy_th);
        $.uiTableFilter($target_table, phrase, target_columns);
        $target_table.find("th.dummy_th").remove();
    });
    // Filter row handling. --ENDS--

    // Prompt to confirm on Show All
    $('body').on('click', '.navigation .showAllRows', function (e) {
        e.preventDefault();
        $form = $(this).parents('form');

        if (! $(this).is(':checked')) { // already showing all rows
            submitShowAllForm();
        } else {
            $form.PMA_confirm(PMA_messages.strShowAllRowsWarning, $form.attr('action'), function (url) {
                submitShowAllForm();
            });
        }

        function submitShowAllForm() {
            var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true';
            PMA_ajaxShowMessage();
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        }
    });

    $('body').on('keyup', '#sqlqueryform', function () {
        PMA_handleSimulateQueryButton();
    });

    /**
     * Ajax event handler for 'Simulate DML'.
     */
    $('body').on('click', '#simulate_dml', function () {
        var $form = $('#sqlqueryform');
        var query = '';
        var delimiter = $('#id_sql_delimiter').val();
        var db_name = $form.find('input[name="db"]').val();

        if (codemirror_editor) {
            query = codemirror_editor.getValue();
        } else {
            query = $('#sqlquery').val();
        }

        if (query.length === 0) {
            alert(PMA_messages.strFormEmpty);
            $('#sqlquery').focus();
            return false;
        }

        var $msgbox = PMA_ajaxShowMessage();
        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: {
                token: $form.find('input[name="token"]').val(),
                db: db_name,
                ajax_request: '1',
                simulate_dml: '1',
                sql_query: query,
                sql_delimiter: delimiter
            },
            success: function (response) {
                PMA_ajaxRemoveMessage($msgbox);
                if (response.success) {
                    var dialog_content = '<div class="preview_sql">';
                    if (response.sql_data) {
                        var len = response.sql_data.length;
                        for (var i=0; i<len; i++) {
                            dialog_content += '<strong>' + PMA_messages.strSQLQuery +
                                '</strong>' + response.sql_data[i].sql_query +
                                PMA_messages.strMatchedRows +
                                ' <a href="' + response.sql_data[i].matched_rows_url +
                                '">' + response.sql_data[i].matched_rows + '</a><br>';
                            if (i<len-1) {
                                dialog_content += '<hr>';
                            }
                        }
                    } else {
                        dialog_content += response.message;
                    }
                    dialog_content += '</div>';
                    $dialog_content = $(dialog_content);
                    var button_options = {};
                    button_options[PMA_messages.strClose] = function () {
                        $(this).dialog('close');
                    };
                    var $response_dialog = $('<div />').append($dialog_content).dialog({
                        minWidth: 540,
                        maxHeight: 400,
                        modal: true,
                        buttons: button_options,
                        title: PMA_messages.strSimulateDML,
                        open: function () {
                            PMA_highlightSQL($(this));
                        },
                        close: function () {
                            $(this).remove();
                        }
                    });
                } else {
                    PMA_ajaxShowMessage(response.error);
                }
            },
            error: function (response) {
                PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest);
            }
        });
    });

    /**
     * Handles multi submits of results browsing page such as edit, delete and export
     */
    $('body').on('click', '#resultsForm.ajax button[name="submit_mult"], #resultsForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.parent('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();
        PMA_ajaxShowMessage();
        $.get($form.attr('action'), submitData, AJAX.responseHandler);
    });
}); // end $()

/**
 * Starting from some th, change the class of all td under it.
 * If isAddClass is specified, it will be used to determine whether to add or remove the class.
 */
function PMA_changeClassForColumn($this_th, newclass, isAddClass)
{
    // index 0 is the th containing the big T
    var th_index = $this_th.index();
    var has_big_t = !$this_th.closest('tr').children(':first').hasClass('column_heading');
    // .eq() is zero-based
    if (has_big_t) {
        th_index--;
    }
    var $tds = $("#table_results").find('tbody tr').find('td.data:eq(' + th_index + ')');
    if (isAddClass === undefined) {
        $tds.toggleClass(newclass);
    } else {
        $tds.toggleClass(newclass, isAddClass);
    }
}

/**
 * Handles browse foreign values modal dialog
 *
 * @param object $this_a reference to the browse foreign value link
 */
function browseForeignDialog($this_a)
{
    var formId = '#browse_foreign_form';
    var showAllId = '#foreign_showAll';
    var tableId = '#browse_foreign_table';
    var filterId = '#input_foreign_filter';
    var $dialog = null;
    $.get($this_a.attr('href'), {'ajax_request': true}, function (data) {
        // Creates browse foreign value dialog
        $dialog = $('<div>').append(data.message).dialog({
            title: PMA_messages.strBrowseForeignValues,
            width: Math.min($(window).width() - 100, 700),
            dialogClass: 'browse_foreign_modal',
            close: function (ev, ui) {
                // remove event handlers attached to elements related to dialog
                $(tableId).off('click', 'td a.foreign_value');
                $(formId).off('click', showAllId);
                $(formId).off('submit');
                // remove dialog itself
                $(this).remove();
            },
            create: function () {
                $(this).css('maxHeight', $(window).height() - 100);
            },
            modal: true
        });
    }).done(function () {
        var showAll = false;
        $(tableId).on('click', 'td a.foreign_value', function () {
            var $input = $this_a.prev('input[type=text]');
            // Check if input exists or get CEdit edit_box
            if ($input.length === 0 ) {
                $input = $this_a.closest('.edit_area').prev('.edit_box');
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
            if ($(filterId).val() != $(filterId).data('old')) {
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
}

AJAX.registerOnload('sql.js', function () {
    $('body').on('click', 'a.browse_foreign', function (e) {
        e.preventDefault();
        browseForeignDialog($(this));
    });

    /**
     * vertical column highlighting in horizontal mode when hovering over the column header
     */
    $('th.column_heading.pointer').live('hover', function (e) {
        PMA_changeClassForColumn($(this), 'hover', e.type == 'mouseenter');
    });

    /**
     * vertical column marking in horizontal mode when clicking the column header
     */
    $('th.column_heading.marker').live('click', function () {
        PMA_changeClassForColumn($(this), 'marked');
    });

    /**
     * create resizable table
     */
    $("#sqlqueryresults").trigger('makegrid').trigger('stickycolumns');
});

/*
 * Profiling Chart
 */
function makeProfilingChart()
{
    if ($('#profilingchart').length === 0 ||
        $('#profilingchart').html().length !== 0 ||
        !$.jqplot || !$.jqplot.Highlighter || !$.jqplot.PieRenderer
    ) {
        return;
    }

    var data = [];
    $.each(jQuery.parseJSON($('#profilingChartData').html()), function (key, value) {
        data.push([key, parseFloat(value)]);
    });

    // Remove chart and data divs contents
    $('#profilingchart').html('').show();
    $('#profilingChartData').html('');

    PMA_createProfilingChartJqplot('profilingchart', data);
}

/*
 * initialize profiling data tables
 */
function initProfilingTables()
{
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
}

/*
 * Set position, left, top, width of sticky_columns div
 */
function setStickyColumnsPosition(position, top, left) {
    if ($("#sticky_columns").length !== 0) {
        $("#sticky_columns")
            .css("position", position)
            .css("top", top)
            .css("left", left ? left : "auto")
            .css("width", $("#table_results").width());
    }
}

/*
 * Initialize sticky columns
 */
function initStickyColumns() {
    fixedTop = $('#floating_menubar').height();
    if ($("#sticky_columns").length === 0) {
        $('<table id="sticky_columns"></table>')
            .insertBefore('#page_content')
            .css("position", "fixed")
            .css("z-index", "99")
            .css("width", $("#table_results").width())
            .css("margin-left", $('#page_content').css("margin-left"))
            .css("top", fixedTop)
            .css("display", "none");
    }
}

/*
 * Arrange/Rearrange columns in sticky header
 */
function rearrangeStickyColumns() {
    var $sticky_columns = $("#sticky_columns");
    var $originalHeader = $("#table_results > thead");
    var $originalColumns = $originalHeader.find("tr:first").children();
    var $clonedHeader = $originalHeader.clone();
    // clone width per cell
    $clonedHeader.find("tr:first").children().width(function(i,val) {
        var width = $originalColumns.eq(i).width();
        if (! $.browser.mozilla) {
            width += 1;
        }
        return width;
    });
    $sticky_columns.empty().append($clonedHeader);
}

/*
 * Adjust sticky columns on horizontal/vertical scroll
 */
function handleStickyColumns() {
    if ($("#table_results").length === 0) {
        return;
    }
    var currentScrollX = $(window).scrollLeft();
    var windowOffset = $(window).scrollTop();
    var tableStartOffset = $("#table_results").offset().top;
    var tableEndOffset = tableStartOffset + $("#table_results").height();
    var $sticky_columns = $("#sticky_columns");
    if (windowOffset >= tableStartOffset && windowOffset <= tableEndOffset) {
        //for horizontal scrolling
        if(prevScrollX != currentScrollX) {
            prevScrollX = currentScrollX;
            setStickyColumnsPosition("absolute", fixedTop + windowOffset);
        //for vertical scrolling
        } else {
            setStickyColumnsPosition("fixed", fixedTop, $("#pma_navigation").width() - currentScrollX);
        }
        $sticky_columns.show();
    } else {
        $sticky_columns.hide();
    }
}

AJAX.registerOnload('sql.js', function () {
    makeProfilingChart();
    initProfilingTables();
});
