/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used wherever an sql query form is used
 *
 * @requires    jQuery
 * @requires    js/functions.js
 *
 */

var $data_a;

/**
 * decode a string URL_encoded
 *
 * @param string str
 * @return string the URL-decoded string
 */
function PMA_urldecode(str)
{
    return decodeURIComponent(str.replace(/\+/g, '%20'));
}

function PMA_urlencode(str)
{
    return encodeURIComponent(str).replace(/\%20/g, '+');
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
    var field_name = $('#table_results').find('thead').find('th:eq('+ (this_field_index - left_action_skip) + ') a').text();
    // happens when just one row (headings contain no a)
    if ("" == field_name) {
        var $heading = $('#table_results').find('thead').find('th:eq('+ (this_field_index - left_action_skip) + ')').children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        field_name = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    field_name = $.trim(field_name);

    return field_name;
}


/**#@+
 * @namespace   jQuery
 */

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
 * </ul>
 *
 * @name        document.ready
 * @memberOf    jQuery
 */
$(function() {
    /* Hides the bookmarkoptions checkboxes when the bookmark label is empty */
    $('input#bkm_label').keyup(function() {
        $('input#id_bkm_all_users, input#id_bkm_replace')
            .parent()
            .toggle($(this).val().length > 0);
    }).trigger('keyup');

    /**
     * Attach the {@link makegrid} function to a custom event, which will be
     * triggered manually everytime the table of results is reloaded
     * @memberOf    jQuery
     */
    $("#sqlqueryresults").live('makegrid', function() {
        PMA_makegrid($('#table_results')[0]);
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
        .html(PMA_messages['strHideQueryBox'])
        .appendTo("#sqlqueryform")
        // initially hidden because at this point, nothing else
        // appears under the link
        .hide();

        // Attach the toggling of the query box visibility to a click
        $("#togglequerybox").bind('click', function() {
            var $link = $(this);
            $link.siblings().slideToggle("fast");
            if ($link.text() == PMA_messages['strHideQueryBox']) {
                $link.text(PMA_messages['strShowQueryBox']);
                // cheap trick to add a spacer between the menu tabs
                // and "Show query box"; feel free to improve!
                $('#togglequerybox_spacer').remove();
                $link.before('<br id="togglequerybox_spacer" />');
            } else {
                $link.text(PMA_messages['strHideQueryBox']);
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
    $("#button_submit_query").live('click', function(event) {
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
    $("input[name=bookmark_variable]").bind("keypress", function(event) {
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
     * @see         $cfg['AjaxEnable']
     * @memberOf    jQuery
     * @name        sqlqueryform_submit
     */
    $("#sqlqueryform.ajax").live('submit', function(event) {
        event.preventDefault();

        var $form = $(this);
        if (! checkSqlQuery($form[0])) {
            return false;
        }

        // remove any div containing a previous error message
        $('div.error').remove();

        var $msgbox = PMA_ajaxShowMessage();
        var $sqlqueryresults = $('#sqlqueryresults');

        PMA_prepareForAjaxRequest($form);

        $.post($form.attr('action'), $form.serialize() , function(data) {
            if (data.success == true) {
                // success happens if the query returns rows or not
                //
                // fade out previous messages, if any
                $('div.success, div.sqlquery_message').fadeOut();

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
                $sqlqueryresults.show().trigger('makegrid');
                $('#togglequerybox').show();
                PMA_init_slider();

                if (typeof data.action_bookmark == 'undefined') {
                    if( $('#sqlqueryform input[name="retain_query_box"]').is(':checked') != true ) {
                        if ($("#togglequerybox").siblings(":visible").length > 0) {
                            $("#togglequerybox").trigger('click');
                        }
                    }
                }

                // this happens if a USE command was typed
                if (typeof data.reload != 'undefined') {
                    // Unbind the submit event before reloading. See bug #3295529
                    $("#sqlqueryform.ajax").die('submit');
                    $form.find('input[name=db]').val(data.db);
                    // need to regenerate the whole upper part
                    $form.find('input[name=ajax_request]').remove();
                    $form.append('<input type="hidden" name="reload" value="true" />');
                    $.post('db_sql.php', $form.serialize(), function(data) {
                        $('body').html(data);
                    }); // end inner post
                }
            } else if (data.success == false ) {
                // show an error message that stays on screen
                $('#sqlqueryform').before(data.error);
                $sqlqueryresults.hide();
            }
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.post()
    }); // end SQL Query submit

    /**
     * Ajax Event handlers for Paginating the results table
     */

    /**
     * Paginate when we click any of the navigation buttons
     * (only if the element has the ajax class, see $cfg['AjaxEnable'])
     * @memberOf    jQuery
     * @name        paginate_nav_button_click
     * @see         $cfg['AjaxEnable']
     */
    $("input[name=navig].ajax").live('click', function(event) {
        /** @lends jQuery */
        event.preventDefault();

        /**
         * @var $form    Object referring to the form element that paginates the results table
         */
        var $form = $(this).parent("form");

        if (($form[0].elements['session_max_rows'] != undefined
            ? checkFormElementInRange(
                $form[0],
                'session_max_rows',
                PMA_messages['strNotValidRowNumber'], 1)
            : true
            )
            &&
            checkFormElementInRange(
                $form[0],
                'pos',
                PMA_messages['strNotValidRowNumber'], 0)) {

            var $msgbox = PMA_ajaxShowMessage();
            PMA_prepareForAjaxRequest($form);

            $.post($form.attr('action'), $form.serialize(), function(data) {
                $("#sqlqueryresults")
                 .html(data.message)
                 .trigger('makegrid');
                PMA_init_slider();
                PMA_ajaxRemoveMessage($msgbox);
            }); // end $.post()
        }
    }); // end Paginate results table

    /**
     * Paginate results with Page Selector dropdown
     * @memberOf    jQuery
     * @name        paginate_dropdown_change
     * @see         $cfg['AjaxEnable']
     */
    $("#pageselector").live('change', function(event) {
        var $form = $(this).parent("form");

        if ($(this).hasClass('ajax')) {
            event.preventDefault();

            var $msgbox = PMA_ajaxShowMessage();

            $.post($form.attr('action'), $form.serialize() + '&ajax_request=true', function(data) {
                $("#sqlqueryresults")
                 .html(data.message)
                 .trigger('makegrid');
                PMA_init_slider();
                PMA_ajaxRemoveMessage($msgbox);
            }); // end $.post()
        } else {
            $form.submit();
        }

    }); // end Paginate results with Page Selector

    /**
     * Ajax Event handler for sorting the results table
     * @memberOf    jQuery
     * @name        table_results_sort_click
     * @see         $cfg['AjaxEnable']
     */
    $("#table_results.ajax").find("a[title=Sort]").live('click', function(event) {
        event.preventDefault();

        var $msgbox = PMA_ajaxShowMessage();

        $anchor = $(this);

        $.get($anchor.attr('href'), $anchor.serialize() + '&ajax_request=true', function(data) {
            $("#sqlqueryresults")
             .html(data.message)
             .trigger('makegrid');
            PMA_ajaxRemoveMessage($msgbox);
        }); // end $.get()
    }); //end Sort results table

    /**
     * Ajax Event handler for the display options
     * @memberOf    jQuery
     * @name        displayOptionsForm_submit
     * @see         $cfg['AjaxEnable']
     */
    $("#displayOptionsForm.ajax").live('submit', function(event) {
        event.preventDefault();

        $form = $(this);

        $.post($form.attr('action'), $form.serialize() + '&ajax_request=true' , function(data) {
            $("#sqlqueryresults")
             .html(data.message)
             .trigger('makegrid');
            PMA_init_slider();
        }); // end $.post()
    }); //end displayOptionsForm handler

/**
 * Ajax Event for table row change
 * */
    $("#resultsForm.ajax .mult_submit[value=edit]").live('click', function(event){
        event.preventDefault();

        /*Check whether atleast one row is selected for change*/
        if ($("#table_results tbody tr, #table_results tbody tr td").hasClass("marked")) {
            var $div = $('<div id="change_row_dialog"></div>');

            /**
             * @var    button_options  Object that stores the options passed to jQueryUI
             *                          dialog
             */
            var button_options = {};
            // in the following function we need to use $(this)
            button_options[PMA_messages['strCancel']] = function() {
                $(this).dialog('close');
            };

            var button_options_error = {};
            button_options_error[PMA_messages['strOK']] = function() {
                $(this).dialog('close');
            };
            var $form = $("#resultsForm");
            var $msgbox = PMA_ajaxShowMessage();

            $.get($form.attr('action'), $form.serialize()+"&ajax_request=true&submit_mult=row_edit", function(data) {
                //in the case of an error, show the error message returned.
                if (data.success != undefined && data.success == false) {
                    $div
                    .append(data.error)
                    .dialog({
                        title: PMA_messages['strChangeTbl'],
                        height: 230,
                        width: 900,
                        open: PMA_verifyColumnsProperties,
                        close: function(event, ui) {
                            $(this).remove();
                        },
                        buttons : button_options_error
                    }); // end dialog options
                } else {
                    $div
                    .append(data.message)
                    .dialog({
                        title: PMA_messages['strChangeTbl'],
                        height: 600,
                        width: 900,
                        open: PMA_verifyColumnsProperties,
                        close: function(event, ui) {
                            $(this).remove();
                        },
                        buttons : button_options
                    })
                    //Remove the top menu container from the dialog
                    .find("#topmenucontainer").hide()
                    ; // end dialog options
                    $("table.insertRowTable").addClass("ajax");
                    $("#buttonYes").addClass("ajax");
                }
                PMA_ajaxRemoveMessage($msgbox);
            }); // end $.get()
        } else {
            PMA_ajaxShowMessage(PMA_messages['strNoRowSelected']);
        }
    });

/**
 * Click action for "Go" button in ajax dialog insertForm -> insertRowTable
 */
    $("#insertForm .insertRowTable.ajax input[type=submit]").live('click', function(event) {
        event.preventDefault();
        /**
         * @var    the_form    object referring to the insert form
         */
        var $form = $("#insertForm");
        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize(), function(data) {
            if (data.success == true) {
                PMA_ajaxShowMessage(data.message);
                if ($("#pageselector").length != 0) {
                    $("#pageselector").trigger('change');
                } else {
                    $("input[name=navig].ajax").trigger('click');
                }

            } else {
                PMA_ajaxShowMessage(data.error, false);
                $("#table_results tbody tr.marked .multi_checkbox " +
                        ", #table_results tbody tr td.marked .multi_checkbox").prop("checked", false);
                $("#table_results tbody tr.marked .multi_checkbox " +
                        ", #table_results tbody tr td.marked .multi_checkbox").removeClass("last_clicked");
                $("#table_results tbody tr" +
                        ", #table_results tbody tr td").removeClass("marked");
            }
            if ($("#change_row_dialog").length > 0) {
                $("#change_row_dialog").dialog("close").remove();
            }
            /**Update the row count at the tableForm*/
            $("#result_query").remove();
            $("#sqlqueryresults").prepend(data.sql_query);
            $("#result_query .notice").remove();
            $("#result_query").prepend((data.message));
        }); // end $.post()
    }); // end insert table button "Go"

/**$("#buttonYes.ajax").live('click'
 * Click action for #buttonYes button in ajax dialog insertForm
 */
    $("#buttonYes.ajax").live('click', function(event){
        event.preventDefault();
        /**
         * @var    the_form    object referring to the insert form
         */
        var $form = $("#insertForm");
        /**Get the submit type in the form*/
        var selected_submit_type = $("#insertForm").find("#actions_panel .control_at_footer option:selected").val();
        $("#result_query").remove();
        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize() , function(data) {
            if (data.success == true) {
                PMA_ajaxShowMessage(data.message);
                if (selected_submit_type == "showinsert") {
                    $("#sqlqueryresults").prepend(data.sql_query);
                    $("#result_query .notice").remove();
                    $("#result_query").prepend(data.message);
                    $("#table_results tbody tr.marked .multi_checkbox " +
                        ", #table_results tbody tr td.marked .multi_checkbox").prop("checked", false);
                    $("#table_results tbody tr.marked .multi_checkbox " +
                        ", #table_results tbody tr td.marked .multi_checkbox").removeClass("last_clicked");
                    $("#table_results tbody tr" +
                        ", #table_results tbody tr td").removeClass("marked");
                } else {
                    if ($("#pageselector").length != 0) {
                        $("#pageselector").trigger('change');
                    } else {
                        $("input[name=navig].ajax").trigger('click');
                    }
                    $("#result_query").remove();
                    $("#sqlqueryresults").prepend(data.sql_query);
                    $("#result_query .notice").remove();
                    $("#result_query").prepend((data.message));
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
                $("#table_results tbody tr.marked .multi_checkbox " +
                    ", #table_results tbody tr td.marked .multi_checkbox").prop("checked", false);
                $("#table_results tbody tr.marked .multi_checkbox " +
                    ", #table_results tbody tr td.marked .multi_checkbox").removeClass("last_clicked");
                $("#table_results tbody tr" +
                    ", #table_results tbody tr td").removeClass("marked");
            }
            if ($("#change_row_dialog").length > 0) {
                $("#change_row_dialog").dialog("close").remove();
            }
        }); // end $.post()
    });

}, 'top.frame_content'); // end $()


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
    var $tds = $this_th.closest('table').find('tbody tr').find('td.data:eq('+th_index+')');
    if (isAddClass == undefined) {
        $tds.toggleClass(newclass);
    } else {
        $tds.toggleClass(newclass, isAddClass);
    }
}

$(function() {

    $('a.browse_foreign').live('click', function(e) {
        e.preventDefault();
        window.open(this.href, 'foreigners', 'width=640,height=240,scrollbars=yes,resizable=yes');
        $anchor = $(this);
        $anchor.addClass('browse_foreign_clicked');
    });

    /**
     * vertical column highlighting in horizontal mode when hovering over the column header
     */
    $('th.column_heading.pointer').live('hover', function(e) {
        PMA_changeClassForColumn($(this), 'hover', e.type == 'mouseenter');
        });

    /**
     * vertical column marking in horizontal mode when clicking the column header
     */
    $('th.column_heading.marker').live('click', function() {
        PMA_changeClassForColumn($(this), 'marked');
        });

    /**
     * create resizable table
     */
    $("#sqlqueryresults").trigger('makegrid');
});

/*
 * Profiling Chart
 */
function makeProfilingChart()
{
    if ($('#profilingchart').length == 0) {
        return;
    }

    var data = new Array();
    $.each(jQuery.parseJSON($('#profilingchart').html()),function(key,value) {
        data.push([key,parseFloat(value)]);
    });

    // Prevent the user from seeing the JSON code
    $('div#profilingchart').html('').show();

    PMA_createProfilingChartJqplot('profilingchart', data);
}


/**#@- */
