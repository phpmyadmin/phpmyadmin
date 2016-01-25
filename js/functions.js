/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * general function, usually for data manipulation pages
 *
 */

/**
 * @var sql_box_locked lock for the sqlbox textarea in the querybox
 */
var sql_box_locked = false;

/**
 * @var array holds elements which content should only selected once
 */
var only_once_elements = [];

/**
 * @var   int   ajax_message_count   Number of AJAX messages shown since page load
 */
var ajax_message_count = 0;

/**
 * @var codemirror_editor object containing CodeMirror editor of the query editor in SQL tab
 */
var codemirror_editor = false;

/**
 * @var codemirror_editor object containing CodeMirror editor of the inline query editor
 */
var codemirror_inline_editor = false;

/**
 * @var sql_autocomplete_in_progress bool shows if Table/Column name autocomplete AJAX is in progress
 */
var sql_autocomplete_in_progress = false;

/**
 * @var sql_autocomplete object containing list of columns in each table
 */
var sql_autocomplete = false;

/**
 * @var sql_autocomplete_default_table string containing default table to autocomplete columns
 */
var sql_autocomplete_default_table = '';

/**
 * @var chart_activeTimeouts object active timeouts that refresh the charts. When disabling a realtime chart, this can be used to stop the continuous ajax requests
 */
var chart_activeTimeouts = {};

/**
 * @var central_column_list array to hold the columns in central list per db.
 */
var central_column_list = [];

/**
 * @var primary_indexes array to hold 'Primary' index columns.
 */
var primary_indexes = [];

/**
 * @var unique_indexes array to hold 'Unique' index columns.
 */
var unique_indexes = [];

/**
 * @var indexes array to hold 'Index' columns.
 */
var indexes = [];

/**
 * @var fulltext_indexes array to hold 'Fulltext' columns.
 */
var fulltext_indexes = [];

/**
 * @var spatial_indexes array to hold 'Spatial' columns.
 */
var spatial_indexes = [];

/**
 * Make sure that ajax requests will not be cached
 * by appending a random variable to their parameters
 */
$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
    var nocache = new Date().getTime() + "" + Math.floor(Math.random() * 1000000);
    if (typeof options.data == "string") {
        options.data += "&_nocache=" + nocache;
    } else if (typeof options.data == "object") {
        options.data = $.extend(originalOptions.data, {'_nocache' : nocache});
    }
});

/**
 * Hanle redirect and reload flags send as part of AJAX requests
 *
 * @param data ajax response data
 */
function PMA_handleRedirectAndReload(data) {
    if (parseInt(data.redirect_flag) == 1) {
        // add one more GET param to display session expiry msg
        if (window.location.href.indexOf('?') === -1) {
            window.location.href += '?session_expired=1';
        } else {
            window.location.href += '&session_expired=1';
        }
        window.location.reload();
    } else if (parseInt(data.reload_flag) == 1) {
        // remove the token param and reload
        window.location.href = window.location.href.replace(/&?token=[^&#]*/g, "");
        window.location.reload();
    }
}

/**
 * Creates an SQL editor which supports auto completing etc.
 *
 * @param $textarea jQuery object wrapping the textarea to be made the editor
 * @param options   optional options for CodeMirror
 * @param resize    optional resizing ('vertical', 'horizontal', 'both')
 */
function PMA_getSQLEditor($textarea, options, resize) {
    if ($textarea.length > 0 && typeof CodeMirror !== 'undefined') {

        // merge options for CodeMirror
        var defaults = {
            lineNumbers: true,
            matchBrackets: true,
            extraKeys: {"Ctrl-Space": "autocomplete"},
            hintOptions: {"completeSingle": false, "completeOnSingleClick": true},
            indentUnit: 4,
            mode: "text/x-mysql",
            lineWrapping: true
        };

        if (CodeMirror.sqlLint) {
            $.extend(defaults, {
                gutters: ["CodeMirror-lint-markers"],
                lint: {
                    "getAnnotations": CodeMirror.sqlLint,
                    "async": true,
                }
            });
        }

        $.extend(true, defaults, options);

        // create CodeMirror editor
        var codemirrorEditor = CodeMirror.fromTextArea($textarea[0], defaults);
        // allow resizing
        if (! resize) {
            resize = 'vertical';
        }
        var handles = '';
        if (resize == 'vertical') {
            handles = 'n, s';
        }
        if (resize == 'both') {
            handles = 'all';
        }
        if (resize == 'horizontal') {
            handles = 'e, w';
        }
        $(codemirrorEditor.getWrapperElement())
            .css('resize', resize)
            .resizable({
                handles: handles,
                resize: function() {
                    codemirrorEditor.setSize($(this).width(), $(this).height());
                }
            });
        // enable autocomplete
        codemirrorEditor.on("inputRead", codemirrorAutocompleteOnInputRead);

        return codemirrorEditor;
    }
    return null;
}

/**
 * Clear text selection
 */
function PMA_clearSelection() {
    if (document.selection && document.selection.empty) {
        document.selection.empty();
    } else if (window.getSelection) {
        var sel = window.getSelection();
        if (sel.empty) {
            sel.empty();
        }
        if (sel.removeAllRanges) {
            sel.removeAllRanges();
        }
    }
}

/**
 * Create a jQuery UI tooltip
 *
 * @param $elements     jQuery object representing the elements
 * @param item          the item
 *                      (see http://api.jqueryui.com/tooltip/#option-items)
 * @param myContent     content of the tooltip
 * @param additionalOptions to override the default options
 *
 */
function PMA_tooltip($elements, item, myContent, additionalOptions)
{
    if ($('#no_hint').length > 0) {
        return;
    }

    var defaultOptions = {
        content: myContent,
        items:  item,
        tooltipClass: "tooltip",
        track: true,
        show: false,
        hide: false
    };

    $elements.tooltip($.extend(true, defaultOptions, additionalOptions));
}

/**
 * HTML escaping
 */

function escapeHtml(unsafe) {
    if (typeof(unsafe) != 'undefined') {
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    } else {
        return false;
    }
}

function PMA_sprintf() {
    return sprintf.apply(this, arguments);
}

/**
 * Hides/shows the default value input field, depending on the default type
 * Ticks the NULL checkbox if NULL is chosen as default value.
 */
function PMA_hideShowDefaultValue($default_type)
{
    if ($default_type.val() == 'USER_DEFINED') {
        $default_type.siblings('.default_value').show().focus();
    } else {
        $default_type.siblings('.default_value').hide();
        if ($default_type.val() == 'NULL') {
            var $null_checkbox = $default_type.closest('tr').find('.allow_null');
            $null_checkbox.prop('checked', true);
        }
    }
}

/**
 * Hides/shows the input field for column expression based on whether
 * VIRTUAL/PERSISTENT is selected
 *
 * @param $virtuality virtuality dropdown
 */
function PMA_hideShowExpression($virtuality)
{
    if ($virtuality.val() == '') {
        $virtuality.siblings('.expression').hide();
    } else {
        $virtuality.siblings('.expression').show();
    }
}

/**
 * Show notices for ENUM columns; add/hide the default value
 *
 */
function PMA_verifyColumnsProperties()
{
    $("select.column_type").each(function () {
        PMA_showNoticeForEnum($(this));
    });
    $("select.default_type").each(function () {
        PMA_hideShowDefaultValue($(this));
    });
    $('select.virtuality').each(function () {
        PMA_hideShowExpression($(this));
    });
}

/**
 * Add a hidden field to the form to indicate that this will be an
 * Ajax request (only if this hidden field does not exist)
 *
 * @param $form object   the form
 */
function PMA_prepareForAjaxRequest($form)
{
    if (! $form.find('input:hidden').is('#ajax_request_hidden')) {
        $form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true" />');
    }
}

/**
 * Generate a new password and copy it to the password input areas
 *
 * @param passwd_form object   the form that holds the password fields
 *
 * @return boolean  always true
 */
function suggestPassword(passwd_form)
{
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ";
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwd_form.generated_pw;
    var randomWords = new Int32Array(passwordlength);

    passwd.value = '';

    // First we're going to try to use a built-in CSPRNG
    if (window.crypto && window.crypto.getRandomValues) {
        window.crypto.getRandomValues(randomWords);
    }
    // Because of course IE calls it msCrypto instead of being standard
    else if (window.msCrypto && window.msCrypto.getRandomValues) {
        window.msCrypto.getRandomValues(randomWords);
    } else {
        // Fallback to Math.random
        for (var i = 0; i < passwordlength; i++) {
            randomWords[i] = Math.floor(Math.random() * pwchars.length);
        }
    }

    for (var i = 0; i < passwordlength; i++) {
        passwd.value += pwchars.charAt(Math.abs(randomWords[i]) % pwchars.length);
    }

    passwd_form.text_pma_pw.value = passwd.value;
    passwd_form.text_pma_pw2.value = passwd.value;
    return true;
}

/**
 * Version string to integer conversion.
 */
function parseVersionString(str)
{
    if (typeof(str) != 'string') { return false; }
    var add = 0;
    // Parse possible alpha/beta/rc/
    var state = str.split('-');
    if (state.length >= 2) {
        if (state[1].substr(0, 2) == 'rc') {
            add = - 20 - parseInt(state[1].substr(2), 10);
        } else if (state[1].substr(0, 4) == 'beta') {
            add =  - 40 - parseInt(state[1].substr(4), 10);
        } else if (state[1].substr(0, 5) == 'alpha') {
            add =  - 60 - parseInt(state[1].substr(5), 10);
        } else if (state[1].substr(0, 3) == 'dev') {
            /* We don't handle dev, it's git snapshot */
            add = 0;
        }
    }
    // Parse version
    var x = str.split('.');
    // Use 0 for non existing parts
    var maj = parseInt(x[0], 10) || 0;
    var min = parseInt(x[1], 10) || 0;
    var pat = parseInt(x[2], 10) || 0;
    var hotfix = parseInt(x[3], 10) || 0;
    return  maj * 100000000 + min * 1000000 + pat * 10000 + hotfix * 100 + add;
}

/**
 * Indicates current available version on main page.
 */
function PMA_current_version(data)
{
    if (data && data.version && data.date) {
        var current = parseVersionString($('span.version').text());
        var latest = parseVersionString(data.version);
        var version_information_message = '<span class="latest">' +
            PMA_messages.strLatestAvailable +
            ' ' + escapeHtml(data.version) +
            '</span>';
        if (latest > current) {
            var message = PMA_sprintf(
                PMA_messages.strNewerVersion,
                escapeHtml(data.version),
                escapeHtml(data.date)
            );
            var htmlClass = 'notice';
            if (Math.floor(latest / 10000) === Math.floor(current / 10000)) {
                /* Security update */
                htmlClass = 'error';
            }
            $('#newer_version_notice').remove();
            $('#maincontainer').after('<div id="newer_version_notice" class="' + htmlClass + '">' + message + '</div>');
        }
        if (latest === current) {
            version_information_message = ' (' + PMA_messages.strUpToDate + ')';
        }
        var $liPmaVersion = $('#li_pma_version');
        $liPmaVersion.find('span.latest').remove();
        $liPmaVersion.append(version_information_message);
    }
}

/**
 * Loads Git revision data from ajax for index.php
 */
function PMA_display_git_revision()
{
    $('#is_git_revision').remove();
    $('#li_pma_version_git').remove();
    $.get(
        "index.php",
        {
            "server": PMA_commonParams.get('server'),
            "token": PMA_commonParams.get('token'),
            "git_revision": true,
            "ajax_request": true,
            "no_debug": true
        },
        function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                $(data.message).insertAfter('#li_pma_version');
            }
        }
    );
}

/**
 * for libraries/display_change_password.lib.php
 *     libraries/user_password.php
 *
 */

function displayPasswordGenerateButton()
{
    $('#tr_element_before_generate_password').parent().append('<tr class="vmiddle"><td>' + PMA_messages.strGeneratePassword + '</td><td><input type="button" class="button" id="button_generate_password" value="' + PMA_messages.strGenerate + '" onclick="suggestPassword(this.form)" /><input type="text" name="generated_pw" id="generated_pw" /></td></tr>');
    $('#div_element_before_generate_password').parent().append('<div class="item"><label for="button_generate_password">' + PMA_messages.strGeneratePassword + ':</label><span class="options"><input type="button" class="button" id="button_generate_password" value="' + PMA_messages.strGenerate + '" onclick="suggestPassword(this.form)" /></span><input type="text" name="generated_pw" id="generated_pw" /></div>');
}

/*
 * Adds a date/time picker to an element
 *
 * @param object  $this_element   a jQuery object pointing to the element
 */
function PMA_addDatepicker($this_element, type, options)
{
    var showTimepicker = true;
    if (type=="date") {
        showTimepicker = false;
    }

    var defaultOptions = {
        showOn: 'button',
        buttonImage: themeCalendarImage, // defined in js/messages.php
        buttonImageOnly: true,
        stepMinutes: 1,
        stepHours: 1,
        showSecond: true,
        showMillisec: true,
        showMicrosec: true,
        showTimepicker: showTimepicker,
        showButtonPanel: false,
        dateFormat: 'yy-mm-dd', // yy means year with four digits
        timeFormat: 'HH:mm:ss.lc',
        constrainInput: false,
        altFieldTimeOnly: false,
        showAnim: '',
        beforeShow: function (input, inst) {
            // Remember that we came from the datepicker; this is used
            // in tbl_change.js by verificationsAfterFieldChange()
            $this_element.data('comes_from', 'datepicker');
            if ($(input).closest('.cEdit').length > 0) {
                setTimeout(function () {
                    inst.dpDiv.css({
                        top: 0,
                        left: 0,
                        position: 'relative'
                    });
                }, 0);
            }
            // Fix wrong timepicker z-index, doesn't work without timeout
            setTimeout(function () {
                $('#ui-timepicker-div').css('z-index', $('#ui-datepicker-div').css('z-index'));
            }, 0);
        },
        onSelect: function() {
            $this_element.data('datepicker').inline = true;
        },
        onClose: function (dateText, dp_inst) {
            // The value is no more from the date picker
            $this_element.data('comes_from', '');
            if (typeof $this_element.data('datepicker') !== 'undefined') {
                $this_element.data('datepicker').inline = false;
            }
        }
    };
    if (type == "datetime" || type == "timestamp") {
        $this_element.datetimepicker($.extend(defaultOptions, options));
    }
    else if (type == "date") {
        $this_element.datetimepicker($.extend(defaultOptions, options));
    }
    else if (type == "time") {
        $this_element.timepicker($.extend(defaultOptions, options));
    }
}

/**
 * selects the content of a given object, f.e. a textarea
 *
 * @param element     object  element of which the content will be selected
 * @param lock        var     variable which holds the lock for this element
 *                              or true, if no lock exists
 * @param only_once   boolean if true this is only done once
 *                              f.e. only on first focus
 */
function selectContent(element, lock, only_once)
{
    if (only_once && only_once_elements[element.name]) {
        return;
    }

    only_once_elements[element.name] = true;

    if (lock) {
        return;
    }

    element.select();
}

/**
 * Displays a confirmation box before submitting a "DROP/DELETE/ALTER" query.
 * This function is called while clicking links
 *
 * @param theLink     object the link
 * @param theSqlQuery object the sql query to submit
 *
 * @return boolean  whether to run the query or not
 */
function confirmLink(theLink, theSqlQuery)
{
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (PMA_messages.strDoYouReally === '' || typeof(window.opera) != 'undefined') {
        return true;
    }

    var is_confirmed = confirm(PMA_sprintf(PMA_messages.strDoYouReally, theSqlQuery));
    if (is_confirmed) {
        if ($(theLink).hasClass('formLinkSubmit')) {
            var name = 'is_js_confirmed';
            if ($(theLink).attr('href').indexOf('usesubform') != -1) {
                name = 'subform[' + $(theLink).attr('href').substr('#').match(/usesubform\[(\d+)\]/i)[1] + '][is_js_confirmed]';
            }

            $(theLink).parents('form').append('<input type="hidden" name="' + name + '" value="1" />');
        } else if (typeof(theLink.href) != 'undefined') {
            theLink.href += '&is_js_confirmed=1';
        } else if (typeof(theLink.form) != 'undefined') {
            theLink.form.action += '?is_js_confirmed=1';
        }
    }

    return is_confirmed;
} // end of the 'confirmLink()' function

/**
 * Displays an error message if a "DROP DATABASE" statement is submitted
 * while it isn't allowed, else confirms a "DROP/DELETE/ALTER" query before
 * submitting it if required.
 * This function is called by the 'checkSqlQuery()' js function.
 *
 * @param theForm1 object   the form
 * @param sqlQuery1 object  the sql query textarea
 *
 * @return boolean  whether to run the query or not
 *
 * @see     checkSqlQuery()
 */
function confirmQuery(theForm1, sqlQuery1)
{
    // Confirmation is not required in the configuration file
    if (PMA_messages.strDoYouReally === '') {
        return true;
    }

    // "DROP DATABASE" statement isn't allowed
    if (PMA_messages.strNoDropDatabases !== '') {
        var drop_re = new RegExp('(^|;)\\s*DROP\\s+(IF EXISTS\\s+)?DATABASE\\s', 'i');
        if (drop_re.test(sqlQuery1.value)) {
            alert(PMA_messages.strNoDropDatabases);
            theForm1.reset();
            sqlQuery1.focus();
            return false;
        } // end if
    } // end if

    // Confirms a "DROP/DELETE/ALTER/TRUNCATE" statement
    //
    // TODO: find a way (if possible) to use the parser-analyser
    // for this kind of verification
    // For now, I just added a ^ to check for the statement at
    // beginning of expression

    var do_confirm_re_0 = new RegExp('^\\s*DROP\\s+(IF EXISTS\\s+)?(TABLE|DATABASE|PROCEDURE)\\s', 'i');
    var do_confirm_re_1 = new RegExp('^\\s*ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
    var do_confirm_re_2 = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var do_confirm_re_3 = new RegExp('^\\s*TRUNCATE\\s', 'i');

    if (do_confirm_re_0.test(sqlQuery1.value) ||
        do_confirm_re_1.test(sqlQuery1.value) ||
        do_confirm_re_2.test(sqlQuery1.value) ||
        do_confirm_re_3.test(sqlQuery1.value)) {
        var message;
        if (sqlQuery1.value.length > 100) {
            message = sqlQuery1.value.substr(0, 100) + '\n    ...';
        } else {
            message = sqlQuery1.value;
        }
        var is_confirmed = confirm(PMA_sprintf(PMA_messages.strDoYouReally, message));
        // statement is confirmed -> update the
        // "is_js_confirmed" form field so the confirm test won't be
        // run on the server side and allows to submit the form
        if (is_confirmed) {
            theForm1.elements.is_js_confirmed.value = 1;
            return true;
        }
        // statement is rejected -> do not submit the form
        else {
            window.focus();
            sqlQuery1.focus();
            return false;
        } // end if (handle confirm box result)
    } // end if (display confirm box)

    return true;
} // end of the 'confirmQuery()' function

/**
 * Displays an error message if the user submitted the sql query form with no
 * sql query, else checks for "DROP/DELETE/ALTER" statements
 *
 * @param theForm object the form
 *
 * @return boolean  always false
 *
 * @see     confirmQuery()
 */
function checkSqlQuery(theForm)
{
    // get the textarea element containing the query
    var sqlQuery;
    if (codemirror_editor) {
        codemirror_editor.save();
        sqlQuery = codemirror_editor.getValue();
    } else {
        sqlQuery = theForm.elements.sql_query.value;
    }
    var isEmpty  = 1;
    var space_re = new RegExp('\\s+');
    if (typeof(theForm.elements.sql_file) != 'undefined' &&
            theForm.elements.sql_file.value.replace(space_re, '') !== '') {
        return true;
    }
    if (isEmpty && typeof(theForm.elements.id_bookmark) != 'undefined' &&
            (theForm.elements.id_bookmark.value !== null || theForm.elements.id_bookmark.value !== '') &&
            theForm.elements.id_bookmark.selectedIndex !== 0) {
        return true;
    }
    // Checks for "DROP/DELETE/ALTER" statements
    if (sqlQuery.replace(space_re, '') !== '') {
        return confirmQuery(theForm, sqlQuery);
    }
    theForm.reset();
    isEmpty = 1;

    if (isEmpty) {
        alert(PMA_messages.strFormEmpty);
        codemirror_editor.focus();
        return false;
    }

    return true;
} // end of the 'checkSqlQuery()' function

/**
 * Check if a form's element is empty.
 * An element containing only spaces is also considered empty
 *
 * @param object   the form
 * @param string   the name of the form field to put the focus on
 *
 * @return boolean  whether the form field is empty or not
 */
function emptyCheckTheField(theForm, theFieldName)
{
    var theField = theForm.elements[theFieldName];
    var space_re = new RegExp('\\s+');
    return theField.value.replace(space_re, '') === '';
} // end of the 'emptyCheckTheField()' function

/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param object   the form
 * @param string   the name of the form field to check
 * @param integer  the minimum authorized value
 * @param integer  the maximum authorized value
 *
 * @return boolean  whether a valid number has been submitted or not
 */
function checkFormElementInRange(theForm, theFieldName, message, min, max)
{
    var theField         = theForm.elements[theFieldName];
    var val              = parseInt(theField.value, 10);

    if (typeof(min) == 'undefined') {
        min = 0;
    }
    if (typeof(max) == 'undefined') {
        max = Number.MAX_VALUE;
    }

    // It's not a number
    if (isNaN(val)) {
        theField.select();
        alert(PMA_messages.strEnterValidNumber);
        theField.focus();
        return false;
    }
    // It's a number but it is not between min and max
    else if (val < min || val > max) {
        theField.select();
        alert(PMA_sprintf(message, val));
        theField.focus();
        return false;
    }
    // It's a valid number
    else {
        theField.value = val;
    }
    return true;

} // end of the 'checkFormElementInRange()' function


function checkTableEditForm(theForm, fieldsCnt)
{
    // TODO: avoid sending a message if user just wants to add a line
    // on the form but has not completed at least one field name

    var atLeastOneField = 0;
    var i, elm, elm2, elm3, val, id;

    for (i = 0; i < fieldsCnt; i++) {
        id = "#field_" + i + "_2";
        elm = $(id);
        val = elm.val();
        if (val == 'VARCHAR' || val == 'CHAR' || val == 'BIT' || val == 'VARBINARY' || val == 'BINARY') {
            elm2 = $("#field_" + i + "_3");
            val = parseInt(elm2.val(), 10);
            elm3 = $("#field_" + i + "_1");
            if (isNaN(val) && elm3.val() !== "") {
                elm2.select();
                alert(PMA_messages.strEnterValidLength);
                elm2.focus();
                return false;
            }
        }

        if (atLeastOneField === 0) {
            id = "field_" + i + "_1";
            if (!emptyCheckTheField(theForm, id)) {
                atLeastOneField = 1;
            }
        }
    }
    if (atLeastOneField === 0) {
        var theField = theForm.elements.field_0_1;
        alert(PMA_messages.strFormEmpty);
        theField.focus();
        return false;
    }

    // at least this section is under jQuery
    var $input = $("input.textfield[name='table']");
    if ($input.val() === "") {
        alert(PMA_messages.strFormEmpty);
        $input.focus();
        return false;
    }

    return true;
} // enf of the 'checkTableEditForm()' function

/**
 * True if last click is to check a row.
 */
var last_click_checked = false;

/**
 * Zero-based index of last clicked row.
 * Used to handle the shift + click event in the code above.
 */
var last_clicked_row = -1;

/**
 * Zero-based index of last shift clicked row.
 */
var last_shift_clicked_row = -1;

var _idleSecondsCounter = 0;
var IncInterval;
var updateTimeout;
AJAX.registerTeardown('functions.js', function () {
    clearTimeout(updateTimeout);
    clearInterval(IncInterval);
    $(document).off('mousemove');
});

AJAX.registerOnload('functions.js', function () {
    document.onclick = function() {
        _idleSecondsCounter = 0;
    };
    $(document).on('mousemove',function() {
        _idleSecondsCounter = 0;
    });
    document.onkeypress = function() {
        _idleSecondsCounter = 0;
    };

    function SetIdleTime() {
        _idleSecondsCounter++;
    }
    function UpdateIdleTime() {
        var href = 'index.php';
        var params = {
                'ajax_request' : true,
                'token' : PMA_commonParams.get('token'),
                'server' : PMA_commonParams.get('server'),
                'db' : PMA_commonParams.get('db'),
                'access_time':_idleSecondsCounter
            };
        $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    if (data.success) {
                        if (PMA_commonParams.get('LoginCookieValidity')-_idleSecondsCounter > 5) {
                            var interval = (PMA_commonParams.get('LoginCookieValidity') - _idleSecondsCounter - 5) * 1000;
                            if (interval > Math.pow(2, 31) - 1) { // max value for setInterval() function
                                interval = Math.pow(2, 31) - 1;
                            }
                            updateTimeout = window.setTimeout(UpdateIdleTime, interval);
                        } else {
                            updateTimeout = window.setTimeout(UpdateIdleTime, 2000);
                        }
                    } else { //timeout occurred
                        window.location.reload(true);
                        clearInterval(IncInterval);
                    }
                }
            });
    }
    if (PMA_commonParams.get('logged_in') && PMA_commonParams.get('auth_type') == 'cookie') {
        IncInterval = window.setInterval(SetIdleTime, 1000);
        var interval = (PMA_commonParams.get('LoginCookieValidity') - 5) * 1000;
        if (interval > Math.pow(2, 31) - 1) { // max value for setInterval() function
            interval = Math.pow(2, 31) - 1;
        }
        updateTimeout = window.setTimeout(UpdateIdleTime, interval);
    }
});
/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', 'input:checkbox.checkall');
});
AJAX.registerOnload('functions.js', function () {
    /**
     * Row marking in horizontal mode (use "on" so that it works also for
     * next pages reached via AJAX); a tr may have the class noclick to remove
     * this behavior.
     */

    $(document).on('click', 'input:checkbox.checkall', function (e) {
        $this = $(this);
        var $tr = $this.closest('tr');
        var $table = $this.closest('table');

        if (!e.shiftKey || last_clicked_row == -1) {
            // usual click

            var $checkbox = $tr.find(':checkbox.checkall');
            var checked = $this.prop('checked');
            $checkbox.prop('checked', checked).trigger('change');
            if (checked) {
                $tr.addClass('marked');
            } else {
                $tr.removeClass('marked');
            }
            last_click_checked = checked;

            // remember the last clicked row
            last_clicked_row = last_click_checked ? $table.find('tr.odd:not(.noclick), tr.even:not(.noclick)').index($tr) : -1;
            last_shift_clicked_row = -1;
        } else {
            // handle the shift click
            PMA_clearSelection();
            var start, end;

            // clear last shift click result
            if (last_shift_clicked_row >= 0) {
                if (last_shift_clicked_row >= last_clicked_row) {
                    start = last_clicked_row;
                    end = last_shift_clicked_row;
                } else {
                    start = last_shift_clicked_row;
                    end = last_clicked_row;
                }
                $tr.parent().find('tr.odd:not(.noclick), tr.even:not(.noclick)')
                    .slice(start, end + 1)
                    .removeClass('marked')
                    .find(':checkbox')
                    .prop('checked', false)
                    .trigger('change');
            }

            // handle new shift click
            var curr_row = $table.find('tr.odd:not(.noclick), tr.even:not(.noclick)').index($tr);
            if (curr_row >= last_clicked_row) {
                start = last_clicked_row;
                end = curr_row;
            } else {
                start = curr_row;
                end = last_clicked_row;
            }
            $tr.parent().find('tr.odd:not(.noclick), tr.even:not(.noclick)')
                .slice(start, end + 1)
                .addClass('marked')
                .find(':checkbox')
                .prop('checked', true)
                .trigger('change');

            // remember the last shift clicked row
            last_shift_clicked_row = curr_row;
        }
    });

    addDateTimePicker();

    /**
     * Add attribute to text boxes for iOS devices (based on bugID: 3508912)
     */
    if (navigator.userAgent.match(/(iphone|ipod|ipad)/i)) {
        $('input[type=text]').attr('autocapitalize', 'off').attr('autocorrect', 'off');
    }
});

/**
 * Row highlighting in horizontal mode (use "on"
 * so that it works also for pages reached via AJAX)
 */
/*AJAX.registerOnload('functions.js', function () {
    $(document).on('hover', 'tr.odd, tr.even',function (event) {
        var $tr = $(this);
        $tr.toggleClass('hover',event.type=='mouseover');
        $tr.children().toggleClass('hover',event.type=='mouseover');
    });
})*/

/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = [];

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usually a table or a div containing the table or tables
 *
 * @param container    DOM element
 */
function markAllRows(container_id)
{

    $("#" + container_id).find("input:checkbox:enabled").prop('checked', true)
    .trigger("change")
    .parents("tr").addClass("marked");
    return true;
}

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usually a table or a div containing the table or tables
 *
 * @param container    DOM element
 */
function unMarkAllRows(container_id)
{

    $("#" + container_id).find("input:checkbox:enabled").prop('checked', false)
    .trigger("change")
    .parents("tr").removeClass("marked");
    return true;
}

/**
 * Checks/unchecks all checkbox in given container (f.e. a form, fieldset or div)
 *
 * @param string   container_id  the container id
 * @param boolean  state         new value for checkbox (true or false)
 * @return boolean  always true
 */
function setCheckboxes(container_id, state)
{

    $("#" + container_id).find("input:checkbox").prop('checked', state);
    return true;
} // end of the 'setCheckboxes()' function

/**
  * Checks/unchecks all options of a <select> element
  *
  * @param string   the form name
  * @param string   the element name
  * @param boolean  whether to check or to uncheck options
  *
  * @return boolean  always true
  */
function setSelectOptions(the_form, the_select, do_check)
{
    $("form[name='" + the_form + "'] select[name='" + the_select + "']").find("option").prop('selected', do_check);
    return true;
} // end of the 'setSelectOptions()' function

/**
 * Sets current value for query box.
 */
function setQuery(query)
{
    if (codemirror_editor) {
        codemirror_editor.setValue(query);
        codemirror_editor.focus();
    } else {
        document.sqlform.sql_query.value = query;
        document.sqlform.sql_query.focus();
    }
}

/**
 * Handles 'Simulate query' button on SQL query box.
 *
 * @return void
 */
function PMA_handleSimulateQueryButton()
{
    var update_re = new RegExp('^\\s*UPDATE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+SET\\s', 'i');
    var delete_re = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var query = '';

    if (codemirror_editor) {
        query = codemirror_editor.getValue();
    } else {
        query = $('#sqlquery').val();
    }

    var $simulateDml = $('#simulate_dml');
    if (update_re.test(query) || delete_re.test(query)) {
        if (! $simulateDml.length) {
            $('#button_submit_query')
            .before('<input type="button" id="simulate_dml"' +
                'tabindex="199" value="' +
                PMA_messages.strSimulateDML +
                '" />');
        }
    } else {
        if ($simulateDml.length) {
            $simulateDml.remove();
        }
    }
}

/**
  * Create quick sql statements.
  *
  */
function insertQuery(queryType)
{
    if (queryType == "clear") {
        setQuery('');
        return;
    } else if (queryType == "format") {
        if (codemirror_editor) {
            $('#querymessage').html(PMA_messages.strFormatting +
                '&nbsp;<img class="ajaxIcon" src="' +
                pmaThemeImage + 'ajax_clock_small.gif" alt="">');
            var href = 'db_sql_format.php';
            var params = {
                'ajax_request': true,
                'token': PMA_commonParams.get('token'),
                'sql': codemirror_editor.getValue()
            };
            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    if (data.success) {
                        codemirror_editor.setValue(data.sql);
                    }
                    $('#querymessage').html('');
                }
            });
        }
        return;
    } else if (queryType == "saved") {
        if ($.cookie('auto_saved_sql')) {
            setQuery($.cookie('auto_saved_sql'));
        } else {
            PMA_ajaxShowMessage(PMA_messages.strNoAutoSavedQuery);
        }
        return;
    }

    var query = "";
    var myListBox = document.sqlform.dummy;
    var table = document.sqlform.table.value;

    if (myListBox.options.length > 0) {
        sql_box_locked = true;
        var columnsList = "";
        var valDis = "";
        var editDis = "";
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            NbSelect++;
            if (NbSelect > 1) {
                columnsList += ", ";
                valDis += ",";
                editDis += ",";
            }
            columnsList += myListBox.options[i].value;
            valDis += "[value-" + NbSelect + "]";
            editDis += myListBox.options[i].value + "=[value-" + NbSelect + "]";
        }
        if (queryType == "selectall") {
            query = "SELECT * FROM `" + table + "` WHERE 1";
        } else if (queryType == "select") {
            query = "SELECT " + columnsList + " FROM `" + table + "` WHERE 1";
        } else if (queryType == "insert") {
            query = "INSERT INTO `" + table + "`(" + columnsList + ") VALUES (" + valDis + ")";
        } else if (queryType == "update") {
            query = "UPDATE `" + table + "` SET " + editDis + " WHERE 1";
        } else if (queryType == "delete") {
            query = "DELETE FROM `" + table + "` WHERE 1";
        }
        setQuery(query);
        sql_box_locked = false;
    }
}


/**
  * Inserts multiple fields.
  *
  */
function insertValueQuery()
{
    var myQuery = document.sqlform.sql_query;
    var myListBox = document.sqlform.dummy;

    if (myListBox.options.length > 0) {
        sql_box_locked = true;
        var columnsList = "";
        var NbSelect = 0;
        for (var i = 0; i < myListBox.options.length; i++) {
            if (myListBox.options[i].selected) {
                NbSelect++;
                if (NbSelect > 1) {
                    columnsList += ", ";
                }
                columnsList += myListBox.options[i].value;
            }
        }

        /* CodeMirror support */
        if (codemirror_editor) {
            codemirror_editor.replaceSelection(columnsList);
        //IE support
        } else if (document.selection) {
            myQuery.focus();
            var sel = document.selection.createRange();
            sel.text = columnsList;
            document.sqlform.insert.focus();
        }
        //MOZILLA/NETSCAPE support
        else if (document.sqlform.sql_query.selectionStart || document.sqlform.sql_query.selectionStart == "0") {
            var startPos = document.sqlform.sql_query.selectionStart;
            var endPos = document.sqlform.sql_query.selectionEnd;
            var SqlString = document.sqlform.sql_query.value;

            myQuery.value = SqlString.substring(0, startPos) + columnsList + SqlString.substring(endPos, SqlString.length);
        } else {
            myQuery.value += columnsList;
        }
        sql_box_locked = false;
    }
}

/**
 * Updates the input fields for the parameters based on the query
 */
function updateQueryParameters() {

    if ($('#parameterized').is(':checked')) {
        var query = codemirror_editor ? codemirror_editor.getValue() : $('#sqlquery').val();

        var allParameters = query.match(/:[a-zA-Z0-9_]+/g);
         var parameters = [];
         // get unique parameters
         if (allParameters) {
             $.each(allParameters, function(i, parameter){
                 if ($.inArray(parameter, parameters) === -1) {
                     parameters.push(parameter);
                 }
             });
         }

         var $temp = $('<div />');
         $temp.append($('#parametersDiv').children());
         $('#parametersDiv').empty();

         $.each(parameters, function (i, parameter) {
             var paramName = parameter.substring(1);
             var $param = $temp.find('#paramSpan_' + paramName );
             if (! $param.length) {
                 $param = $('<span class="parameter" id="paramSpan_' + paramName + '" />');
                 $('<label for="param_' + paramName + '" />').text(parameter).appendTo($param);
                 $('<input type="text" name="parameters[' + parameter + ']" id="param_' + paramName + '" />').appendTo($param);
             }
             $('#parametersDiv').append($param);
         });
    } else {
        $('#parametersDiv').empty();
    }
}

/**
 * Add a date/time picker to each element that needs it
 * (only when jquery-ui-timepicker-addon.js is loaded)
 */
function addDateTimePicker() {
    if ($.timepicker !== undefined) {
        $('input.timefield, input.datefield, input.datetimefield').each(function () {

            var decimals = $(this).parent().attr('data-decimals');
            var type = $(this).parent().attr('data-type');

            var showMillisec = false;
            var showMicrosec = false;
            var timeFormat = 'HH:mm:ss';
            // check for decimal places of seconds
            if (decimals > 0 && type.indexOf('time') != -1){
                if (decimals > 3) {
                    showMillisec = true;
                    showMicrosec = true;
                    timeFormat = 'HH:mm:ss.lc';
                } else {
                    showMillisec = true;
                    timeFormat = 'HH:mm:ss.l';
                }
            }
            PMA_addDatepicker($(this), type, {
                showMillisec: showMillisec,
                showMicrosec: showMicrosec,
                timeFormat: timeFormat
            });
        });
    }
}

/**
  * Refresh/resize the WYSIWYG scratchboard
  */
function refreshLayout()
{
    var $elm = $('#pdflayout');
    var orientation = $('#orientation_opt').val();
    var paper = 'A4';
    var $paperOpt = $('#paper_opt');
    if ($paperOpt.length == 1) {
        paper = $paperOpt.val();
    }
    var posa = 'y';
    var posb = 'x';
    if (orientation == 'P') {
        posa = 'x';
        posb = 'y';
    }
    $elm.css('width', pdfPaperSize(paper, posa) + 'px');
    $elm.css('height', pdfPaperSize(paper, posb) + 'px');
}

/**
 * Initializes positions of elements.
 */
function TableDragInit() {
    $('.pdflayout_table').each(function () {
        var $this = $(this);
        var number = $this.data('number');
        var x = $('#c_table_' + number + '_x').val();
        var y = $('#c_table_' + number + '_y').val();
        $this.css('left', x + 'px');
        $this.css('top', y + 'px');
        /* Make elements draggable */
        $this.draggable({
            containment: "parent",
            drag: function (evt, ui) {
                var number = $this.data('number');
                $('#c_table_' + number + '_x').val(parseInt(ui.position.left, 10));
                $('#c_table_' + number + '_y').val(parseInt(ui.position.top, 10));
            }
        });
    });
}

/**
 * Resets drag and drop positions.
 */
function resetDrag() {
    $('.pdflayout_table').each(function () {
        var $this = $(this);
        var x = $this.data('x');
        var y = $this.data('y');
        $this.css('left', x + 'px');
        $this.css('top', y + 'px');
    });
}

/**
 * User schema handlers.
 */
$(function () {
    /* Move in scratchboard on manual change */
    $(document).on('change', '.position-change', function () {
        var $this = $(this);
        var $elm = $('#table_' + $this.data('number'));
        $elm.css($this.data('axis'), $this.val() + 'px');
    });
    /* Refresh on paper size/orientation change */
    $(document).on('change', '.paper-change', function () {
        var $elm = $('#pdflayout');
        if ($elm.css('visibility') == 'visible') {
            refreshLayout();
            TableDragInit();
        }
    });
    /* Show/hide the WYSIWYG scratchboard */
    $(document).on('click', '#toggle-dragdrop', function () {
        var $elm = $('#pdflayout');
        if ($elm.css('visibility') == 'hidden') {
            refreshLayout();
            TableDragInit();
            $elm.css('visibility', 'visible');
            $elm.css('display', 'block');
            $('#showwysiwyg').val('1');
        } else {
            $elm.css('visibility', 'hidden');
            $elm.css('display', 'none');
            $('#showwysiwyg').val('0');
        }
    });
    /* Reset scratchboard */
    $(document).on('click', '#reset-dragdrop', function () {
        resetDrag();
    });
});

/**
 * Returns paper sizes for a given format
 */
function pdfPaperSize(format, axis)
{
    switch (format.toUpperCase()) {
    case '4A0':
        if (axis == 'x') {
            return 4767.87;
        } else {
            return 6740.79;
        }
        break;
    case '2A0':
        if (axis == 'x') {
            return 3370.39;
        } else {
            return 4767.87;
        }
        break;
    case 'A0':
        if (axis == 'x') {
            return 2383.94;
        } else {
            return 3370.39;
        }
        break;
    case 'A1':
        if (axis == 'x') {
            return 1683.78;
        } else {
            return 2383.94;
        }
        break;
    case 'A2':
        if (axis == 'x') {
            return 1190.55;
        } else {
            return 1683.78;
        }
        break;
    case 'A3':
        if (axis == 'x') {
            return 841.89;
        } else {
            return 1190.55;
        }
        break;
    case 'A4':
        if (axis == 'x') {
            return 595.28;
        } else {
            return 841.89;
        }
        break;
    case 'A5':
        if (axis == 'x') {
            return 419.53;
        } else {
            return 595.28;
        }
        break;
    case 'A6':
        if (axis == 'x') {
            return 297.64;
        } else {
            return 419.53;
        }
        break;
    case 'A7':
        if (axis == 'x') {
            return 209.76;
        } else {
            return 297.64;
        }
        break;
    case 'A8':
        if (axis == 'x') {
            return 147.40;
        } else {
            return 209.76;
        }
        break;
    case 'A9':
        if (axis == 'x') {
            return 104.88;
        } else {
            return 147.40;
        }
        break;
    case 'A10':
        if (axis == 'x') {
            return 73.70;
        } else {
            return 104.88;
        }
        break;
    case 'B0':
        if (axis == 'x') {
            return 2834.65;
        } else {
            return 4008.19;
        }
        break;
    case 'B1':
        if (axis == 'x') {
            return 2004.09;
        } else {
            return 2834.65;
        }
        break;
    case 'B2':
        if (axis == 'x') {
            return 1417.32;
        } else {
            return 2004.09;
        }
        break;
    case 'B3':
        if (axis == 'x') {
            return 1000.63;
        } else {
            return 1417.32;
        }
        break;
    case 'B4':
        if (axis == 'x') {
            return 708.66;
        } else {
            return 1000.63;
        }
        break;
    case 'B5':
        if (axis == 'x') {
            return 498.90;
        } else {
            return 708.66;
        }
        break;
    case 'B6':
        if (axis == 'x') {
            return 354.33;
        } else {
            return 498.90;
        }
        break;
    case 'B7':
        if (axis == 'x') {
            return 249.45;
        } else {
            return 354.33;
        }
        break;
    case 'B8':
        if (axis == 'x') {
            return 175.75;
        } else {
            return 249.45;
        }
        break;
    case 'B9':
        if (axis == 'x') {
            return 124.72;
        } else {
            return 175.75;
        }
        break;
    case 'B10':
        if (axis == 'x') {
            return 87.87;
        } else {
            return 124.72;
        }
        break;
    case 'C0':
        if (axis == 'x') {
            return 2599.37;
        } else {
            return 3676.54;
        }
        break;
    case 'C1':
        if (axis == 'x') {
            return 1836.85;
        } else {
            return 2599.37;
        }
        break;
    case 'C2':
        if (axis == 'x') {
            return 1298.27;
        } else {
            return 1836.85;
        }
        break;
    case 'C3':
        if (axis == 'x') {
            return 918.43;
        } else {
            return 1298.27;
        }
        break;
    case 'C4':
        if (axis == 'x') {
            return 649.13;
        } else {
            return 918.43;
        }
        break;
    case 'C5':
        if (axis == 'x') {
            return 459.21;
        } else {
            return 649.13;
        }
        break;
    case 'C6':
        if (axis == 'x') {
            return 323.15;
        } else {
            return 459.21;
        }
        break;
    case 'C7':
        if (axis == 'x') {
            return 229.61;
        } else {
            return 323.15;
        }
        break;
    case 'C8':
        if (axis == 'x') {
            return 161.57;
        } else {
            return 229.61;
        }
        break;
    case 'C9':
        if (axis == 'x') {
            return 113.39;
        } else {
            return 161.57;
        }
        break;
    case 'C10':
        if (axis == 'x') {
            return 79.37;
        } else {
            return 113.39;
        }
        break;
    case 'RA0':
        if (axis == 'x') {
            return 2437.80;
        } else {
            return 3458.27;
        }
        break;
    case 'RA1':
        if (axis == 'x') {
            return 1729.13;
        } else {
            return 2437.80;
        }
        break;
    case 'RA2':
        if (axis == 'x') {
            return 1218.90;
        } else {
            return 1729.13;
        }
        break;
    case 'RA3':
        if (axis == 'x') {
            return 864.57;
        } else {
            return 1218.90;
        }
        break;
    case 'RA4':
        if (axis == 'x') {
            return 609.45;
        } else {
            return 864.57;
        }
        break;
    case 'SRA0':
        if (axis == 'x') {
            return 2551.18;
        } else {
            return 3628.35;
        }
        break;
    case 'SRA1':
        if (axis == 'x') {
            return 1814.17;
        } else {
            return 2551.18;
        }
        break;
    case 'SRA2':
        if (axis == 'x') {
            return 1275.59;
        } else {
            return 1814.17;
        }
        break;
    case 'SRA3':
        if (axis == 'x') {
            return 907.09;
        } else {
            return 1275.59;
        }
        break;
    case 'SRA4':
        if (axis == 'x') {
            return 637.80;
        } else {
            return 907.09;
        }
        break;
    case 'LETTER':
        if (axis == 'x') {
            return 612.00;
        } else {
            return 792.00;
        }
        break;
    case 'LEGAL':
        if (axis == 'x') {
            return 612.00;
        } else {
            return 1008.00;
        }
        break;
    case 'EXECUTIVE':
        if (axis == 'x') {
            return 521.86;
        } else {
            return 756.00;
        }
        break;
    case 'FOLIO':
        if (axis == 'x') {
            return 612.00;
        } else {
            return 936.00;
        }
        break;
    } // end switch

    return 0;
}

/**
 * Get checkbox for foreign key checks
 *
 * @return string
 */
function getForeignKeyCheckboxLoader() {
    var html = '';
    html    += '<div>';
    html    += '<div class="load-default-fk-check-value">';
    html    += PMA_getImage('ajax_clock_small.gif');
    html    += '</div>';
    html    += '</div>';
    return html;
}

function loadForeignKeyCheckbox() {
    // Load default foreign key check value
    var params = {
        'ajax_request': true,
        'token': PMA_commonParams.get('token'),
        'server': PMA_commonParams.get('server'),
        'get_default_fk_check_value': true
    };
    $.get('sql.php', params, function (data) {
        var html = '<input type="hidden" name="fk_checks" value="0" />' +
            '<input type="checkbox" name="fk_checks" id="fk_checks"' +
            (data.default_fk_check_value ? ' checked="checked"' : '') + ' />' +
            '<label for="fk_checks">' + PMA_messages.strForeignKeyCheck + '</label>';
        $('.load-default-fk-check-value').replaceWith(html);
    });
}

function getJSConfirmCommonParam(elem) {
    return {
        'is_js_confirmed' : 1,
        'ajax_request' : true,
        'fk_checks': $(elem).find('#fk_checks').is(':checked') ? 1 : 0
    };
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', "a.inline_edit_sql");
    $(document).off('click', "input#sql_query_edit_save");
    $(document).off('click', "input#sql_query_edit_discard");
    $('input.sqlbutton').unbind('click');
    if (codemirror_editor) {
        codemirror_editor.off('blur');
    } else {
        $(document).off('blur', '#sqlquery');
    }
    $(document).off('change', '#parameterized');
    $('#sqlquery').unbind('keydown');
    $('#sql_query_edit').unbind('keydown');

    if (codemirror_inline_editor) {
        // Copy the sql query to the text area to preserve it.
        $('#sql_query_edit').text(codemirror_inline_editor.getValue());
        $(codemirror_inline_editor.getWrapperElement()).unbind('keydown');
        codemirror_inline_editor.toTextArea();
        codemirror_inline_editor = false;
    }
    if (codemirror_editor) {
        $(codemirror_editor.getWrapperElement()).unbind('keydown');
    }
});

/**
 * Jquery Coding for inline editing SQL_QUERY
 */
AJAX.registerOnload('functions.js', function () {
    // If we are coming back to the page by clicking forward button
    // of the browser, bind the code mirror to inline query editor.
    bindCodeMirrorToInlineEditor();
    $(document).on('click', "a.inline_edit_sql", function () {
        if ($('#sql_query_edit').length) {
            // An inline query editor is already open,
            // we don't want another copy of it
            return false;
        }

        var $form = $(this).prev('form');
        var sql_query  = $form.find("input[name='sql_query']").val().trim();
        var $inner_sql = $(this).parent().prev().find('code.sql');
        var old_text   = $inner_sql.html();

        var new_content = "<textarea name=\"sql_query_edit\" id=\"sql_query_edit\">" + sql_query + "</textarea>\n";
        new_content    += getForeignKeyCheckboxLoader();
        new_content    += "<input type=\"submit\" id=\"sql_query_edit_save\" class=\"button btnSave\" value=\"" + PMA_messages.strGo + "\"/>\n";
        new_content    += "<input type=\"button\" id=\"sql_query_edit_discard\" class=\"button btnDiscard\" value=\"" + PMA_messages.strCancel + "\"/>\n";
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

    $(document).on('click', "input#sql_query_edit_save", function () {
        $(".success").hide();
        //hide already existing success message
        var sql_query;
        if (codemirror_inline_editor) {
            codemirror_inline_editor.save();
            sql_query = codemirror_inline_editor.getValue();
        } else {
            sql_query = $(this).parent().find('#sql_query_edit').val();
        }
        var fk_check = $(this).parent().find('#fk_checks').is(':checked');

        var $form = $("a.inline_edit_sql").prev('form');
        var $fake_form = $('<form>', {action: 'import.php', method: 'post'})
                .append($form.find("input[name=server], input[name=db], input[name=table], input[name=token]").clone())
                .append($('<input/>', {type: 'hidden', name: 'show_query', value: 1}))
                .append($('<input/>', {type: 'hidden', name: 'is_js_confirmed', value: 0}))
                .append($('<input/>', {type: 'hidden', name: 'sql_query', value: sql_query}))
                .append($('<input/>', {type: 'hidden', name: 'fk_checks', value: fk_check ? 1 : 0}));
        if (! checkSqlQuery($fake_form[0])) {
            return false;
        }
        $fake_form.appendTo($('body')).submit();
    });

    $(document).on('click', "input#sql_query_edit_discard", function () {
        var $divEditor = $('div#inline_editor_outer');
        $divEditor.siblings('code.sql').show();
        $divEditor.remove();
    });

    $('input.sqlbutton').click(function (evt) {
        insertQuery(evt.target.id);
        PMA_handleSimulateQueryButton();
        return false;
    });

    $(document).on('change', '#parameterized', updateQueryParameters);

    var $inputUsername = $('#input_username');
    if ($inputUsername) {
        if ($inputUsername.val() === '') {
            $inputUsername.focus();
        } else {
            $('#input_password').focus();
        }
    }
});

/**
 * "inputRead" event handler for CodeMirror SQL query editors for autocompletion
 */
function codemirrorAutocompleteOnInputRead(instance) {
    if (!sql_autocomplete_in_progress
        && (!instance.options.hintOptions.tables || !sql_autocomplete)) {

        if (!sql_autocomplete) {
            // Reset after teardown
            instance.options.hintOptions.tables = false;
            instance.options.hintOptions.defaultTable = '';

            sql_autocomplete_in_progress = true;

            var href = 'db_sql_autocomplete.php';
            var params = {
                'ajax_request': true,
                'token': PMA_commonParams.get('token'),
                'server': PMA_commonParams.get('server'),
                'db': PMA_commonParams.get('db'),
                'no_debug': true
            };

            var columnHintRender = function(elem, self, data) {
                $('<div class="autocomplete-column-name">')
                    .text(data.columnName)
                    .appendTo(elem);
                $('<div class="autocomplete-column-hint">')
                    .text(data.columnHint)
                    .appendTo(elem);
            };

            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    if (data.success) {
                        var tables = $.parseJSON(data.tables);
                        sql_autocomplete_default_table = PMA_commonParams.get('table');
                        sql_autocomplete = [];
                        for (var table in tables) {
                            if (tables.hasOwnProperty(table)) {
                                var columns = tables[table];
                                table = {
                                    text: table,
                                    columns: []
                                };
                                for (var column in columns) {
                                    if (columns.hasOwnProperty(column)) {
                                        var displayText = columns[column].Type;
                                        if (columns[column].Key == 'PRI') {
                                            displayText += ' | Primary';
                                        } else if (columns[column].Key == 'UNI') {
                                            displayText += ' | Unique';
                                        }
                                        table.columns.push({
                                            text: column,
                                            displayText: column + " | " +  displayText,
                                            columnName: column,
                                            columnHint: displayText,
                                            render: columnHintRender
                                        });
                                    }
                                }
                            }
                            sql_autocomplete.push(table);
                        }
                        instance.options.hintOptions.tables = sql_autocomplete;
                        instance.options.hintOptions.defaultTable = sql_autocomplete_default_table;
                    }
                },
                complete: function () {
                    sql_autocomplete_in_progress = false;
                }
            });
        }
        else {
            instance.options.hintOptions.tables = sql_autocomplete;
            instance.options.hintOptions.defaultTable = sql_autocomplete_default_table;
        }
    }
    if (instance.state.completionActive) {
        return;
    }
    var cur = instance.getCursor();
    var token = instance.getTokenAt(cur);
    var string = '';
    if (token.string.match(/^[.`\w@]\w*$/)) {
        string = token.string;
    }
    if (string.length > 0) {
        CodeMirror.commands.autocomplete(instance);
    }
}

/**
 * Remove autocomplete information before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    sql_autocomplete = false;
    sql_autocomplete_default_table = '';
});

/**
 * Binds the CodeMirror to the text area used to inline edit a query.
 */
function bindCodeMirrorToInlineEditor() {
    var $inline_editor = $('#sql_query_edit');
    if ($inline_editor.length > 0) {
        if (typeof CodeMirror !== 'undefined') {
            var height = $inline_editor.css('height');
            codemirror_inline_editor = PMA_getSQLEditor($inline_editor);
            codemirror_inline_editor.getWrapperElement().style.height = height;
            codemirror_inline_editor.refresh();
            codemirror_inline_editor.focus();
            $(codemirror_inline_editor.getWrapperElement())
                .bind('keydown', catchKeypressesFromSqlInlineEdit);
        } else {
            $inline_editor
                .focus()
                .bind('keydown', catchKeypressesFromSqlInlineEdit);
        }
    }
}

function catchKeypressesFromSqlInlineEdit(event) {
    // ctrl-enter is 10 in chrome and ie, but 13 in ff
    if (event.ctrlKey && (event.keyCode == 13 || event.keyCode == 10)) {
        $("#sql_query_edit_save").trigger('click');
    }
}

/**
 * Adds doc link to single highlighted SQL element
 */
function PMA_doc_add($elm, params)
{
    if (typeof mysql_doc_template == 'undefined') {
        return;
    }

    var url = PMA_sprintf(
        decodeURIComponent(mysql_doc_template),
        params[0]
    );
    if (params.length > 1) {
        url += '#' + params[1];
    }
    var content = $elm.text();
    $elm.text('');
    $elm.append('<a target="mysql_doc" class="cm-sql-doc" href="' + url + '">' + content + '</a>');
}

/**
 * Generates doc links for keywords inside highlighted SQL
 */
function PMA_doc_keyword(idx, elm)
{
    var $elm = $(elm);
    /* Skip already processed ones */
    if ($elm.find('a').length > 0) {
        return;
    }
    var keyword = $elm.text().toUpperCase();
    var $next = $elm.next('.cm-keyword');
    if ($next) {
        var next_keyword = $next.text().toUpperCase();
        var full = keyword + ' ' + next_keyword;

        var $next2 = $next.next('.cm-keyword');
        if ($next2) {
            var next2_keyword = $next2.text().toUpperCase();
            var full2 = full + ' ' + next2_keyword;
            if (full2 in mysql_doc_keyword) {
                PMA_doc_add($elm, mysql_doc_keyword[full2]);
                PMA_doc_add($next, mysql_doc_keyword[full2]);
                PMA_doc_add($next2, mysql_doc_keyword[full2]);
                return;
            }
        }
        if (full in mysql_doc_keyword) {
            PMA_doc_add($elm, mysql_doc_keyword[full]);
            PMA_doc_add($next, mysql_doc_keyword[full]);
            return;
        }
    }
    if (keyword in mysql_doc_keyword) {
        PMA_doc_add($elm, mysql_doc_keyword[keyword]);
    }
}

/**
 * Generates doc links for builtins inside highlighted SQL
 */
function PMA_doc_builtin(idx, elm)
{
    var $elm = $(elm);
    var builtin = $elm.text().toUpperCase();
    if (builtin in mysql_doc_builtin) {
        PMA_doc_add($elm, mysql_doc_builtin[builtin]);
    }
}

/**
 * Higlights SQL using CodeMirror.
 */
function PMA_highlightSQL($base)
{
    var $elm = $base.find('code.sql');
    $elm.each(function () {
        var $sql = $(this);
        var $pre = $sql.find('pre');
        /* We only care about visible elements to avoid double processing */
        if ($pre.is(":visible")) {
            var $highlight = $('<div class="sql-highlight cm-s-default"></div>');
            $sql.append($highlight);
            if (typeof CodeMirror != 'undefined') {
                CodeMirror.runMode($sql.text(), 'text/x-mysql', $highlight[0]);
                $pre.hide();
                $highlight.find('.cm-keyword').each(PMA_doc_keyword);
                $highlight.find('.cm-builtin').each(PMA_doc_builtin);
            }
        }
    });
}

/**
 * Updates an element containing code.
 *
 * @param jQuery Object $base base element which contains the raw and the
 *                            highlighted code.
 *
 * @param string htmlValue    code in HTML format, displayed if code cannot be
 *                            highlighted
 *
 * @param string rawValue     raw code, used as a parameter for highlighter
 *
 * @return bool               whether content was updated or not
 */
function PMA_updateCode($base, htmlValue, rawValue)
{
    var $code = $base.find('code');
    if ($code.length == 0) {
        return false;
    }

    // Determines the type of the content and appropriate CodeMirror mode.
    var type = '', mode = '';
    if  ($code.hasClass('json')) {
        type = 'json';
        mode = 'application/json';
    } else if ($code.hasClass('sql')) {
        type = 'sql';
        mode = 'text/x-mysql';
    } else if ($code.hasClass('xml')) {
        type = 'xml';
        mode = 'application/xml';
    } else {
        return false;
    }

    // Element used to display unhighlighted code.
    var $notHighlighted = $('<pre>' + htmlValue + '</pre>');

    // Tries to highlight code using CodeMirror.
    if (typeof CodeMirror != 'undefined') {
        var $highlighted = $('<div class="' + type + '-highlight cm-s-default"></div>');
        CodeMirror.runMode(rawValue, mode, $highlighted[0]);
        $notHighlighted.hide();
        $code.html('').append($notHighlighted, $highlighted[0]);
    } else {
        $code.html('').append($notHighlighted);
    }

    return true;
}

/**
 * Show a message on the top of the page for an Ajax request
 *
 * Sample usage:
 *
 * 1) var $msg = PMA_ajaxShowMessage();
 * This will show a message that reads "Loading...". Such a message will not
 * disappear automatically and cannot be dismissed by the user. To remove this
 * message either the PMA_ajaxRemoveMessage($msg) function must be called or
 * another message must be show with PMA_ajaxShowMessage() function.
 *
 * 2) var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
 * This is a special case. The behaviour is same as above,
 * just with a different message
 *
 * 3) var $msg = PMA_ajaxShowMessage('The operation was successful');
 * This will show a message that will disappear automatically and it can also
 * be dismissed by the user.
 *
 * 4) var $msg = PMA_ajaxShowMessage('Some error', false);
 * This will show a message that will not disappear automatically, but it
 * can be dismissed by the user after he has finished reading it.
 *
 * @param string  message     string containing the message to be shown.
 *                              optional, defaults to 'Loading...'
 * @param mixed   timeout     number of milliseconds for the message to be visible
 *                              optional, defaults to 5000. If set to 'false', the
 *                              notification will never disappear
 * @return jQuery object       jQuery Element that holds the message div
 *                              this object can be passed to PMA_ajaxRemoveMessage()
 *                              to remove the notification
 */
function PMA_ajaxShowMessage(message, timeout)
{
    /**
     * @var self_closing Whether the notification will automatically disappear
     */
    var self_closing = true;
    /**
     * @var dismissable Whether the user will be able to remove
     *                  the notification by clicking on it
     */
    var dismissable = true;
    // Handle the case when a empty data.message is passed.
    // We don't want the empty message
    if (message === '') {
        return true;
    } else if (! message) {
        // If the message is undefined, show the default
        message = PMA_messages.strLoading;
        dismissable = false;
        self_closing = false;
    } else if (message == PMA_messages.strProcessingRequest) {
        // This is another case where the message should not disappear
        dismissable = false;
        self_closing = false;
    }
    // Figure out whether (or after how long) to remove the notification
    if (timeout === undefined) {
        timeout = 5000;
    } else if (timeout === false) {
        self_closing = false;
    }
    // Create a parent element for the AJAX messages, if necessary
    if ($('#loading_parent').length === 0) {
        $('<div id="loading_parent"></div>')
        .prependTo("#page_content");
    }
    // Update message count to create distinct message elements every time
    ajax_message_count++;
    // Remove all old messages, if any
    $("span.ajax_notification[id^=ajax_message_num]").remove();
    /**
     * @var    $retval    a jQuery object containing the reference
     *                    to the created AJAX message
     */
    var $retval = $(
            '<span class="ajax_notification" id="ajax_message_num_' +
            ajax_message_count +
            '"></span>'
    )
    .hide()
    .appendTo("#loading_parent")
    .html(message)
    .show();
    // If the notification is self-closing we should create a callback to remove it
    if (self_closing) {
        $retval
        .delay(timeout)
        .fadeOut('medium', function () {
            if ($(this).is(':data(tooltip)')) {
                $(this).tooltip('destroy');
            }
            // Remove the notification
            $(this).remove();
        });
    }
    // If the notification is dismissable we need to add the relevant class to it
    // and add a tooltip so that the users know that it can be removed
    if (dismissable) {
        $retval.addClass('dismissable').css('cursor', 'pointer');
        /**
         * Add a tooltip to the notification to let the user know that (s)he
         * can dismiss the ajax notification by clicking on it.
         */
        PMA_tooltip(
            $retval,
            'span',
            PMA_messages.strDismiss
        );
    }
    PMA_highlightSQL($retval);

    return $retval;
}

/**
 * Removes the message shown for an Ajax operation when it's completed
 *
 * @param jQuery object   jQuery Element that holds the notification
 *
 * @return nothing
 */
function PMA_ajaxRemoveMessage($this_msgbox)
{
    if ($this_msgbox !== undefined && $this_msgbox instanceof jQuery) {
        $this_msgbox
        .stop(true, true)
        .fadeOut('medium');
        if ($this_msgbox.is(':data(tooltip)')) {
            $this_msgbox.tooltip('destroy');
        } else {
            $this_msgbox.remove();
        }
    }
}

/**
 * Requests SQL for previewing before executing.
 *
 * @param jQuery Object $form Form containing query data
 *
 * @return void
 */
function PMA_previewSQL($form)
{
    var form_url = $form.attr('action');
    var form_data = $form.serialize() +
        '&do_save_data=1' +
        '&preview_sql=1' +
        '&ajax_request=1';
    var $msgbox = PMA_ajaxShowMessage();
    $.ajax({
        type: 'POST',
        url: form_url,
        data: form_data,
        success: function (response) {
            PMA_ajaxRemoveMessage($msgbox);
            if (response.success) {
                var $dialog_content = $('<div/>')
                    .append(response.sql_data);
                var button_options = {};
                button_options[PMA_messages.strClose] = function () {
                    $(this).dialog('close');
                };
                var $response_dialog = $dialog_content.dialog({
                    minWidth: 550,
                    maxHeight: 400,
                    modal: true,
                    buttons: button_options,
                    title: PMA_messages.strPreviewSQL,
                    close: function () {
                        $(this).remove();
                    },
                    open: function () {
                        // Pretty SQL printing.
                        PMA_highlightSQL($(this));
                    }
                });
            } else {
                PMA_ajaxShowMessage(response.message);
            }
        },
        error: function () {
            PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest);
        }
    });
}

/**
 * check for reserved keyword column name
 *
 * @param jQuery Object $form Form
 *
 * @returns true|false
 */

function PMA_checkReservedWordColumns($form) {
    var is_confirmed = true;
    $.ajax({
        type: 'POST',
        url: "tbl_structure.php",
        data: $form.serialize() + '&reserved_word_check=1',
        success: function (data) {
            if (typeof data.success != 'undefined' && data.success === true) {
                is_confirmed = confirm(data.message);
            }
        },
        async:false
    });
    return is_confirmed;
}

// This event only need to be fired once after the initial page load
$(function () {
    /**
     * Allows the user to dismiss a notification
     * created with PMA_ajaxShowMessage()
     */
    $(document).on('click', 'span.ajax_notification.dismissable', function () {
        PMA_ajaxRemoveMessage($(this));
    });
    /**
     * The below two functions hide the "Dismiss notification" tooltip when a user
     * is hovering a link or button that is inside an ajax message
     */
    $(document).on('mouseover', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
        if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
            $(this).parents('span.ajax_notification').tooltip('disable');
        }
    });
    $(document).on('mouseout', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
        if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
            $(this).parents('span.ajax_notification').tooltip('enable');
        }
    });
});

/**
 * Hides/shows the "Open in ENUM/SET editor" message, depending on the data type of the column currently selected
 */
function PMA_showNoticeForEnum(selectElement)
{
    var enum_notice_id = selectElement.attr("id").split("_")[1];
    enum_notice_id += "_" + (parseInt(selectElement.attr("id").split("_")[2], 10) + 1);
    var selectedType = selectElement.val();
    if (selectedType == "ENUM" || selectedType == "SET") {
        $("p#enum_notice_" + enum_notice_id).show();
    } else {
        $("p#enum_notice_" + enum_notice_id).hide();
    }
}

/*
 * Creates a Profiling Chart with jqplot. Used in sql.js
 * and in server_status_monitor.js
 */
function PMA_createProfilingChartJqplot(target, data)
{
    return $.jqplot(target, [data],
        {
            seriesDefaults: {
                renderer: $.jqplot.PieRenderer,
                rendererOptions: {
                    showDataLabels:  true
                }
            },
            highlighter: {
                show: true,
                tooltipLocation: 'se',
                sizeAdjust: 0,
                tooltipAxes: 'pieref',
                useAxesFormatters: false,
                formatString: '%s, %.9Ps'
            },
            legend: {
                show: true,
                location: 'e',
                rendererOptions: {numberColumns: 2}
            },
            // from http://tango.freedesktop.org/Tango_Icon_Theme_Guidelines#Color_Palette
            seriesColors: [
                '#fce94f',
                '#fcaf3e',
                '#e9b96e',
                '#8ae234',
                '#729fcf',
                '#ad7fa8',
                '#ef2929',
                '#eeeeec',
                '#888a85',
                '#c4a000',
                '#ce5c00',
                '#8f5902',
                '#4e9a06',
                '#204a87',
                '#5c3566',
                '#a40000',
                '#babdb6',
                '#2e3436'
            ]
        }
    );
}

/**
 * Formats a profiling duration nicely (in us and ms time).
 * Used in server_status_monitor.js
 *
 * @param  integer    Number to be formatted, should be in the range of microsecond to second
 * @param  integer    Accuracy, how many numbers right to the comma should be
 * @return string     The formatted number
 */
function PMA_prettyProfilingNum(num, acc)
{
    if (!acc) {
        acc = 2;
    }
    acc = Math.pow(10, acc);
    if (num * 1000 < 0.1) {
        num = Math.round(acc * (num * 1000 * 1000)) / acc + 'µ';
    } else if (num < 0.1) {
        num = Math.round(acc * (num * 1000)) / acc + 'm';
    } else {
        num = Math.round(acc * num) / acc;
    }

    return num + 's';
}


/**
 * Formats a SQL Query nicely with newlines and indentation. Depends on Codemirror and MySQL Mode!
 *
 * @param string      Query to be formatted
 * @return string      The formatted query
 */
function PMA_SQLPrettyPrint(string)
{
    if (typeof CodeMirror == 'undefined') {
        return string;
    }

    var mode = CodeMirror.getMode({}, "text/x-mysql");
    var stream = new CodeMirror.StringStream(string);
    var state = mode.startState();
    var token, tokens = [];
    var output = '';
    var tabs = function (cnt) {
        var ret = '';
        for (var i = 0; i < 4 * cnt; i++) {
            ret += " ";
        }
        return ret;
    };

    // "root-level" statements
    var statements = {
        'select': ['select', 'from', 'on', 'where', 'having', 'limit', 'order by', 'group by'],
        'update': ['update', 'set', 'where'],
        'insert into': ['insert into', 'values']
    };
    // don't put spaces before these tokens
    var spaceExceptionsBefore = {';': true, ',': true, '.': true, '(': true};
    // don't put spaces after these tokens
    var spaceExceptionsAfter = {'.': true};

    // Populate tokens array
    var str = '';
    while (! stream.eol()) {
        stream.start = stream.pos;
        token = mode.token(stream, state);
        if (token !== null) {
            tokens.push([token, stream.current().toLowerCase()]);
        }
    }

    var currentStatement = tokens[0][1];

    if (! statements[currentStatement]) {
        return string;
    }
    // Holds all currently opened code blocks (statement, function or generic)
    var blockStack = [];
    // Holds the type of block from last iteration (the current is in blockStack[0])
    var previousBlock;
    // If a new code block is found, newBlock contains its type for one iteration and vice versa for endBlock
    var newBlock, endBlock;
    // How much to indent in the current line
    var indentLevel = 0;
    // Holds the "root-level" statements
    var statementPart, lastStatementPart = statements[currentStatement][0];

    blockStack.unshift('statement');

    // Iterate through every token and format accordingly
    for (var i = 0; i < tokens.length; i++) {
        previousBlock = blockStack[0];

        // New block => push to stack
        if (tokens[i][1] == '(') {
            if (i < tokens.length - 1 && tokens[i + 1][0] == 'statement-verb') {
                blockStack.unshift(newBlock = 'statement');
            } else if (i > 0 && tokens[i - 1][0] == 'builtin') {
                blockStack.unshift(newBlock = 'function');
            } else {
                blockStack.unshift(newBlock = 'generic');
            }
        } else {
            newBlock = null;
        }

        // Block end => pop from stack
        if (tokens[i][1] == ')') {
            endBlock = blockStack[0];
            blockStack.shift();
        } else {
            endBlock = null;
        }

        // A subquery is starting
        if (i > 0 && newBlock == 'statement') {
            indentLevel++;
            output += "\n" + tabs(indentLevel) + tokens[i][1] + ' ' + tokens[i + 1][1].toUpperCase() + "\n" + tabs(indentLevel + 1);
            currentStatement = tokens[i + 1][1];
            i++;
            continue;
        }

        // A subquery is ending
        if (endBlock == 'statement' && indentLevel > 0) {
            output += "\n" + tabs(indentLevel);
            indentLevel--;
        }

        // One less indentation for statement parts (from, where, order by, etc.) and a newline
        statementPart = statements[currentStatement].indexOf(tokens[i][1]);
        if (statementPart != -1) {
            if (i > 0) {
                output += "\n";
            }
            output += tabs(indentLevel) + tokens[i][1].toUpperCase();
            output += "\n" + tabs(indentLevel + 1);
            lastStatementPart = tokens[i][1];
        }
        // Normal indentation and spaces for everything else
        else {
            if (! spaceExceptionsBefore[tokens[i][1]] &&
               ! (i > 0 && spaceExceptionsAfter[tokens[i - 1][1]]) &&
               output.charAt(output.length - 1) != ' ') {
                output += " ";
            }
            if (tokens[i][0] == 'keyword') {
                output += tokens[i][1].toUpperCase();
            } else {
                output += tokens[i][1];
            }
        }

        // split columns in select and 'update set' clauses, but only inside statements blocks
        if ((lastStatementPart == 'select' || lastStatementPart == 'where'  || lastStatementPart == 'set') &&
            tokens[i][1] == ',' && blockStack[0] == 'statement') {

            output += "\n" + tabs(indentLevel + 1);
        }

        // split conditions in where clauses, but only inside statements blocks
        if (lastStatementPart == 'where' &&
            (tokens[i][1] == 'and' || tokens[i][1] == 'or' || tokens[i][1] == 'xor')) {

            if (blockStack[0] == 'statement') {
                output += "\n" + tabs(indentLevel + 1);
            }
            // Todo: Also split and or blocks in newlines & indentation++
            //if (blockStack[0] == 'generic')
             //   output += ...
        }
    }
    return output;
}

/**
 * jQuery function that uses jQueryUI's dialogs to confirm with user. Does not
 *  return a jQuery object yet and hence cannot be chained
 *
 * @param string      question
 * @param string      url           URL to be passed to the callbackFn to make
 *                                  an Ajax call to
 * @param function    callbackFn    callback to execute after user clicks on OK
 * @param function    openCallback  optional callback to run when dialog is shown
 */

jQuery.fn.PMA_confirm = function (question, url, callbackFn, openCallback) {
    var confirmState = PMA_commonParams.get('confirm');
    if (! confirmState) {
        // user does not want to confirm
        if ($.isFunction(callbackFn)) {
            callbackFn.call(this, url);
            return true;
        }
    }
    if (PMA_messages.strDoYouReally === '') {
        return true;
    }

    /**
     * @var    button_options  Object that stores the options passed to jQueryUI
     *                          dialog
     */
    var button_options = [
        {
            text: PMA_messages.strOK,
            'class': 'submitOK',
            click: function () {
                $(this).dialog("close");
                if ($.isFunction(callbackFn)) {
                    callbackFn.call(this, url);
                }
            }
        },
        {
            text: PMA_messages.strCancel,
            'class': 'submitCancel',
            click: function () {
                $(this).dialog("close");
            }
        }
    ];

    $('<div/>', {'id': 'confirm_dialog', 'title': PMA_messages.strConfirm})
    .prepend(question)
    .dialog({
        buttons: button_options,
        close: function () {
            $(this).remove();
        },
        open: openCallback,
        modal: true
    });
};

/**
 * jQuery function to sort a table's body after a new row has been appended to it.
 * Also fixes the even/odd classes of the table rows at the end.
 *
 * @param string      text_selector   string to select the sortKey's text
 *
 * @return jQuery Object for chaining purposes
 */
jQuery.fn.PMA_sort_table = function (text_selector) {
    return this.each(function () {

        /**
         * @var table_body  Object referring to the table's <tbody> element
         */
        var table_body = $(this);
        /**
         * @var rows    Object referring to the collection of rows in {@link table_body}
         */
        var rows = $(this).find('tr').get();

        //get the text of the field that we will sort by
        $.each(rows, function (index, row) {
            row.sortKey = $.trim($(row).find(text_selector).text().toLowerCase());
        });

        //get the sorted order
        rows.sort(function (a, b) {
            if (a.sortKey < b.sortKey) {
                return -1;
            }
            if (a.sortKey > b.sortKey) {
                return 1;
            }
            return 0;
        });

        //pull out each row from the table and then append it according to it's order
        $.each(rows, function (index, row) {
            $(table_body).append(row);
            row.sortKey = null;
        });

        //Re-check the classes of each row
        $(this).find('tr:odd')
        .removeClass('even').addClass('odd')
        .end()
        .find('tr:even')
        .removeClass('odd').addClass('even');
    });
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('submit', "#create_table_form_minimal.ajax");
    $(document).off('submit', "form.create_table_form.ajax");
    $(document).off('click', "form.create_table_form.ajax input[name=submit_num_fields]");
    $(document).off('keyup', "form.create_table_form.ajax input");
});

/**
 * jQuery coding for 'Create Table'.  Used on db_operations.php,
 * db_structure.php and db_tracking.php (i.e., wherever
 * libraries/display_create_table.lib.php is used)
 *
 * Attach Ajax Event handlers for Create Table
 */
AJAX.registerOnload('functions.js', function () {
    /**
     * Attach event handler for submission of create table form (save)
     */
    $(document).on('submit', "form.create_table_form.ajax", function (event) {
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
                //User wants to submit the form
                $.post($form.attr('action'), $form.serialize() + "&do_save_data=1", function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#properties_message')
                         .removeClass('error')
                         .html('');
                        PMA_ajaxShowMessage(data.message);
                        // Only if the create table dialog (distinct panel) exists
                        var $createTableDialog = $("#create_table_dialog");
                        if ($createTableDialog.length > 0) {
                            $createTableDialog.dialog("close").remove();
                        }
                        $('#tableslistcontainer').before(data.formatted_sql);

                        /**
                         * @var tables_table    Object referring to the <tbody> element that holds the list of tables
                         */
                        var tables_table = $("#tablesForm").find("tbody").not("#tbl_summary_row");
                        // this is the first table created in this db
                        if (tables_table.length === 0) {
                            PMA_commonActions.refreshMain(
                                PMA_commonParams.get('opendb_url')
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
                            //append to table
                            $(data.new_table_string)
                             .appendTo(tables_table);

                            //Sort the table
                            $(tables_table).PMA_sort_table('th');

                            // Adjust summary row
                            PMA_adjustTotals();
                        }

                        //Refresh navigation as a new table has been added
                        PMA_reloadNavigation();
                        // Redirect to table structure page on creation of new table
                        var params_12 = 'ajax_request=true&ajax_page_request=true';
                        if (! (history && history.pushState)) {
                            params_12 += PMA_MicroHistory.menus.getRequestParam();
                        }
                        tblStruct_url = 'tbl_structure.php?server=' + data._params.server +
                            '&db='+ data._params.db + '&token=' + data._params.token +
                            '&goto=db_structure.php&table=' + data._params.table + '';
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
     * Attach event handler for create table form (add fields)
     */
    $(document).on('click', "form.create_table_form.ajax input[name=submit_num_fields]", function (event) {
        event.preventDefault();
        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $(this).closest('form');

        if (!checkFormElementInRange(this.form, 'added_fields', PMA_messages.strLeastColumnError, 1)) {
            return;
        }

        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);

        //User wants to add more fields to the table
        $.post($form.attr('action'), $form.serialize() + "&submit_num_fields=1", function (data) {
            if (typeof data !== 'undefined' && data.success) {
                var $pageContent = $("#page_content");
                $pageContent.html(data.message);
                PMA_highlightSQL($pageContent);
                PMA_verifyColumnsProperties();
                PMA_hideShowConnection($('.create_table_form select[name=tbl_storage_engine]'));
                PMA_ajaxRemoveMessage($msgbox);
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }); //end $.post()
    }); // end create table form (add fields)

    $(document).on('keydown', "form.create_table_form.ajax input[name=added_fields]", function (event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            event.stopImmediatePropagation();
            $(this)
                .closest('form')
                .find('input[name=submit_num_fields]')
                .click();
        }
    });
    $("input[value=AUTO_INCREMENT]").change(function(){
        if (this.checked) {
            var col = /\d/.exec($(this).attr('name'));
            col = col[0];
            var $selectFieldKey = $('select[name="field_key[' + col + ']"]');
            if ($selectFieldKey.val() === 'none_'+col) {
                $selectFieldKey.val('primary_'+col).change();
            }
        }
    });
    $('body')
    .off('click', 'input.preview_sql')
    .on('click', 'input.preview_sql', function () {
        var $form = $(this).closest('form');
        PMA_previewSQL($form);
    });
});


/**
 * Validates the password field in a form
 *
 * @see    PMA_messages.strPasswordEmpty
 * @see    PMA_messages.strPasswordNotSame
 * @param  object $the_form The form to be validated
 * @return bool
 */
function PMA_checkPassword($the_form)
{
    // Did the user select 'no password'?
    if ($the_form.find('#nopass_1').is(':checked')) {
        return true;
    } else {
        var $pred = $the_form.find('#select_pred_password');
        if ($pred.length && ($pred.val() == 'none' || $pred.val() == 'keep')) {
            return true;
        }
    }

    var $password = $the_form.find('input[name=pma_pw]');
    var $password_repeat = $the_form.find('input[name=pma_pw2]');
    var alert_msg = false;

    if ($password.val() === '') {
        alert_msg = PMA_messages.strPasswordEmpty;
    } else if ($password.val() != $password_repeat.val()) {
        alert_msg = PMA_messages.strPasswordNotSame;
    }

    if (alert_msg) {
        alert(alert_msg);
        $password.val('');
        $password_repeat.val('');
        $password.focus();
        return false;
    }
    return true;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', '#change_password_anchor.ajax');
});
/**
 * Attach Ajax event handlers for 'Change Password' on index.php
 */
AJAX.registerOnload('functions.js', function () {

    /**
     * Attach Ajax event handler on the change password anchor
     */
    $(document).on('click', '#change_password_anchor.ajax', function (event) {
        event.preventDefault();

        var $msgbox = PMA_ajaxShowMessage();

        /**
         * @var button_options  Object containing options to be passed to jQueryUI's dialog
         */
        var button_options = {};
        button_options[PMA_messages.strGo] = function () {

            event.preventDefault();

            /**
             * @var $the_form    Object referring to the change password form
             */
            var $the_form = $("#change_password_form");

            if (! PMA_checkPassword($the_form)) {
                return false;
            }

            /**
             * @var this_value  String containing the value of the submit button.
             * Need to append this for the change password form on Server Privileges
             * page to work
             */
            var this_value = $(this).val();

            var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $the_form.append('<input type="hidden" name="ajax_request" value="true" />');

            $.post($the_form.attr('action'), $the_form.serialize() + '&change_pw=' + this_value, function (data) {
                if (typeof data === 'undefined' || data.success !== true) {
                    PMA_ajaxShowMessage(data.error, false);
                    return;
                }

                var $pageContent = $("#page_content");
                $pageContent.prepend(data.message);
                PMA_highlightSQL($pageContent);
                $("#change_password_dialog").hide().remove();
                $("#edit_user_dialog").dialog("close").remove();
                PMA_ajaxRemoveMessage($msgbox);
            }); // end $.post()
        };

        button_options[PMA_messages.strCancel] = function () {
            $(this).dialog('close');
        };
        $.get($(this).attr('href'), {'ajax_request': true}, function (data) {
            if (typeof data === 'undefined' || !data.success) {
                PMA_ajaxShowMessage(data.error, false);
                return;
            }

            $('<div id="change_password_dialog"></div>')
                .dialog({
                    title: PMA_messages.strChangePassword,
                    width: 600,
                    close: function (ev, ui) {
                        $(this).remove();
                    },
                    buttons: button_options,
                    modal: true
                })
                .append(data.message);
            // for this dialog, we remove the fieldset wrapping due to double headings
            $("fieldset#fieldset_change_password")
                .find("legend").remove().end()
                .find("table.noclick").unwrap().addClass("some-margin")
                .find("input#text_pma_pw").focus();
            displayPasswordGenerateButton();
            $('#fieldset_change_password_footer').hide();
            PMA_ajaxRemoveMessage($msgbox);
            $('#change_password_form').bind('submit', function (e) {
                e.preventDefault();
                $(this)
                    .closest('.ui-dialog')
                    .find('.ui-dialog-buttonpane .ui-button')
                    .first()
                    .click();
            });
        }); // end $.get()
    }); // end handler for change password anchor
}); // end $() for Change Password

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', "select.column_type");
    $(document).off('change', "select.default_type");
    $(document).off('change', "select.virtuality");
    $(document).off('change', 'input.allow_null');
    $(document).off('change', '.create_table_form select[name=tbl_storage_engine]');
});
/**
 * Toggle the hiding/showing of the "Open in ENUM/SET editor" message when
 * the page loads and when the selected data type changes
 */
AJAX.registerOnload('functions.js', function () {
    // is called here for normal page loads and also when opening
    // the Create table dialog
    PMA_verifyColumnsProperties();
    //
    // needs on() to work also in the Create Table dialog
    $(document).on('change', "select.column_type", function () {
        PMA_showNoticeForEnum($(this));
    });
    $(document).on('change', "select.default_type", function () {
        PMA_hideShowDefaultValue($(this));
    });
    $(document).on('change', "select.virtuality", function () {
        PMA_hideShowExpression($(this));
    });
    $(document).on('change', 'input.allow_null', function () {
        PMA_validateDefaultValue($(this));
    });
    $(document).on('change', '.create_table_form select[name=tbl_storage_engine]', function () {
        PMA_hideShowConnection($(this));
    });
});

/**
 * If the chosen storage engine is FEDERATED show connection field. Hide otherwise
 *
 * @param $engine_selector storage engine selector
 */
function PMA_hideShowConnection($engine_selector)
{
    var $connection = $('.create_table_form input[name=connection]');
    var index = $connection.parent('td').index() + 1;
    var $labelTh = $connection.parents('tr').prev('tr').children('th:nth-child(' + index + ')');
    if ($engine_selector.val() != 'FEDERATED') {
        $connection
            .prop('disabled', true)
            .parent('td').hide();
        $labelTh.hide();
    } else {
        $connection
            .prop('disabled', false)
            .parent('td').show();
        $labelTh.show();
    }
}

/**
 * If the column does not allow NULL values, makes sure that default is not NULL
 */
function PMA_validateDefaultValue($null_checkbox)
{
    if (! $null_checkbox.prop('checked')) {
        var $default = $null_checkbox.closest('tr').find('.default_type');
        if ($default.val() == 'NULL') {
            $default.val('NONE');
        }
    }
}

/**
 * function to populate the input fields on picking a column from central list
 *
 * @param string  input_id input id of the name field for the column to be populated
 * @param integer offset of the selected column in central list of columns
 */
function autoPopulate(input_id, offset)
{
    var db = PMA_commonParams.get('db');
    var table = PMA_commonParams.get('table');
    input_id = input_id.substring(0, input_id.length - 1);
    $('#' + input_id + '1').val(central_column_list[db + '_' + table][offset].col_name);
    var col_type = central_column_list[db + '_' + table][offset].col_type.toUpperCase();
    $('#' + input_id + '2').val(col_type);
    var $input3 = $('#' + input_id + '3');
    $input3.val(central_column_list[db + '_' + table][offset].col_length);
    if(col_type === 'ENUM' || col_type === 'SET') {
        $input3.next().show();
    } else {
        $input3.next().hide();
    }
    var col_default = central_column_list[db + '_' + table][offset].col_default.toUpperCase();
    var $input4 = $('#' + input_id + '4');
    if (col_default !== '' && col_default !== 'NULL' && col_default !== 'CURRENT_TIMESTAMP') {
        $input4.val("USER_DEFINED");
        $input4.next().next().show();
        $input4.next().next().val(central_column_list[db + '_' + table][offset].col_default);
    } else {
        $input4.val(central_column_list[db + '_' + table][offset].col_default);
        $input4.next().next().hide();
    }
    $('#' + input_id + '5').val(central_column_list[db + '_' + table][offset].col_collation);
    var $input6 = $('#' + input_id + '6');
    $input6.val(central_column_list[db + '_' + table][offset].col_attribute);
    if(central_column_list[db + '_' + table][offset].col_extra === 'on update CURRENT_TIMESTAMP') {
        $input6.val(central_column_list[db + '_' + table][offset].col_extra);
    }
    if(central_column_list[db + '_' + table][offset].col_extra.toUpperCase() === 'AUTO_INCREMENT') {
        $('#' + input_id + '9').prop("checked",true).change();
    } else {
        $('#' + input_id + '9').prop("checked",false);
    }
    if(central_column_list[db + '_' + table][offset].col_isNull !== '0') {
        $('#' + input_id + '7').prop("checked",true);
    } else {
        $('#' + input_id + '7').prop("checked",false);
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', "a.open_enum_editor");
    $(document).off('click', "input.add_value");
    $(document).off('click', "#enum_editor td.drop");
    $(document).off('click', 'a.central_columns_dialog');
});
/**
 * @var $enum_editor_dialog An object that points to the jQuery
 *                          dialog of the ENUM/SET editor
 */
var $enum_editor_dialog = null;
/**
 * Opens the ENUM/SET editor and controls its functions
 */
AJAX.registerOnload('functions.js', function () {
    $(document).on('click', "a.open_enum_editor", function () {
        // Get the name of the column that is being edited
        var colname = $(this).closest('tr').find('input:first').val();
        var title;
        var i;
        // And use it to make up a title for the page
        if (colname.length < 1) {
            title = PMA_messages.enum_newColumnVals;
        } else {
            title = PMA_messages.enum_columnVals.replace(
                /%s/,
                '"' + escapeHtml(decodeURIComponent(colname)) + '"'
            );
        }
        // Get the values as a string
        var inputstring = $(this)
            .closest('td')
            .find("input")
            .val();
        // Escape html entities
        inputstring = $('<div/>')
            .text(inputstring)
            .html();
        // Parse the values, escaping quotes and
        // slashes on the fly, into an array
        var values = [];
        var in_string = false;
        var curr, next, buffer = '';
        for (i = 0; i < inputstring.length; i++) {
            curr = inputstring.charAt(i);
            next = i == inputstring.length ? '' : inputstring.charAt(i + 1);
            if (! in_string && curr == "'") {
                in_string = true;
            } else if (in_string && curr == "\\" && next == "\\") {
                buffer += "&#92;";
                i++;
            } else if (in_string && next == "'" && (curr == "'" || curr == "\\")) {
                buffer += "&#39;";
                i++;
            } else if (in_string && curr == "'") {
                in_string = false;
                values.push(buffer);
                buffer = '';
            } else if (in_string) {
                buffer += curr;
            }
        }
        if (buffer.length > 0) {
            // The leftovers in the buffer are the last value (if any)
            values.push(buffer);
        }
        var fields = '';
        // If there are no values, maybe the user is about to make a
        // new list so we add a few for him/her to get started with.
        if (values.length === 0) {
            values.push('', '', '', '');
        }
        // Add the parsed values to the editor
        var drop_icon = PMA_getImage('b_drop.png');
        for (i = 0; i < values.length; i++) {
            fields += "<tr><td>" +
                   "<input type='text' value='" + values[i] + "'/>" +
                   "</td><td class='drop'>" +
                   drop_icon +
                   "</td></tr>";
        }
        /**
         * @var dialog HTML code for the ENUM/SET dialog
         */
        var dialog = "<div id='enum_editor'>" +
                   "<fieldset>" +
                    "<legend>" + title + "</legend>" +
                    "<p>" + PMA_getImage('s_notice.png') +
                    PMA_messages.enum_hint + "</p>" +
                    "<table class='values'>" + fields + "</table>" +
                    "</fieldset><fieldset class='tblFooters'>" +
                    "<table class='add'><tr><td>" +
                    "<div class='slider'></div>" +
                    "</td><td>" +
                    "<form><div><input type='submit' class='add_value' value='" +
                    PMA_sprintf(PMA_messages.enum_addValue, 1) +
                    "'/></div></form>" +
                    "</td></tr></table>" +
                    "<input type='hidden' value='" + // So we know which column's data is being edited
                    $(this).closest('td').find("input").attr("id") +
                    "' />" +
                    "</fieldset>" +
                    "</div>";
        /**
         * @var  Defines functions to be called when the buttons in
         * the buttonOptions jQuery dialog bar are pressed
         */
        var buttonOptions = {};
        buttonOptions[PMA_messages.strGo] = function () {
            // When the submit button is clicked,
            // put the data back into the original form
            var value_array = [];
            $(this).find(".values input").each(function (index, elm) {
                var val = elm.value.replace(/\\/g, '\\\\').replace(/'/g, "''");
                value_array.push("'" + val + "'");
            });
            // get the Length/Values text field where this value belongs
            var values_id = $(this).find("input[type='hidden']").val();
            $("input#" + values_id).val(value_array.join(","));
            $(this).dialog("close");
        };
        buttonOptions[PMA_messages.strClose] = function () {
            $(this).dialog("close");
        };
        // Show the dialog
        var width = parseInt(
            (parseInt($('html').css('font-size'), 10) / 13) * 340,
            10
        );
        if (! width) {
            width = 340;
        }
        $enum_editor_dialog = $(dialog).dialog({
            minWidth: width,
            maxHeight: 450,
            modal: true,
            title: PMA_messages.enum_editor,
            buttons: buttonOptions,
            open: function () {
                // Focus the "Go" button after opening the dialog
                $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button:first').focus();
            },
            close: function () {
                $(this).remove();
            }
        });
        // slider for choosing how many fields to add
        $enum_editor_dialog.find(".slider").slider({
            animate: true,
            range: "min",
            value: 1,
            min: 1,
            max: 9,
            slide: function (event, ui) {
                $(this).closest('table').find('input[type=submit]').val(
                    PMA_sprintf(PMA_messages.enum_addValue, ui.value)
                );
            }
        });
        // Focus the slider, otherwise it looks nearly transparent
        $('a.ui-slider-handle').addClass('ui-state-focus');
        return false;
    });

    $(document).on('click', 'a.central_columns_dialog', function (e) {
        var href = "db_central_columns.php";
        var db = PMA_commonParams.get('db');
        var table = PMA_commonParams.get('table');
        var maxRows = $(this).data('maxrows');
        var pick = $(this).data('pick');
        if (pick !== false) {
            pick = true;
        }
        var params = {
            'ajax_request' : true,
            'token' : PMA_commonParams.get('token'),
            'server' : PMA_commonParams.get('server'),
            'db' : PMA_commonParams.get('db'),
            'cur_table' : PMA_commonParams.get('table'),
            'getColumnList':true
        };
        var colid = $(this).closest('td').find("input").attr("id");
        var fields = '';
        if (! (db + '_' + table in central_column_list)) {
            central_column_list.push(db + '_' + table);
            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    central_column_list[db + '_' + table] = $.parseJSON(data.message);
                },
                async:false
            });
        }
        var i = 0;
        var list_size = central_column_list[db + '_' + table].length;
        var min = (list_size <= maxRows) ? list_size : maxRows;
        for (i = 0; i < min; i++) {

            fields += '<tr><td><div><span style="font-weight:bold">' +
                escapeHtml(central_column_list[db + '_' + table][i].col_name) +
                '</span><br><span style="color:gray">' + central_column_list[db + '_' + table][i].col_type;

            if (central_column_list[db + '_' + table][i].col_attribute !== '') {
                fields += '(' + escapeHtml(central_column_list[db + '_' + table][i].col_attribute) + ') ';
            }
            if (central_column_list[db + '_' + table][i].col_length !== '') {
                fields += '(' + escapeHtml(central_column_list[db + '_' + table][i].col_length) +') ';
            }
            fields += escapeHtml(central_column_list[db + '_' + table][i].col_extra) + '</span>' +
                '</div></td>';
            if (pick) {
                fields += '<td><input class="pick" style="width:100%" type="submit" value="' +
                    PMA_messages.pickColumn + '" onclick="autoPopulate(\'' + colid + '\',' + i + ')"/></td>';
            }
            fields += '</tr>';
        }
        var result_pointer = i;
        var search_in = '<input type="text" class="filter_rows" placeholder="' + PMA_messages.searchList + '">';
        if (fields === '') {
            fields = PMA_sprintf(PMA_messages.strEmptyCentralList, "'" + db + "'");
            search_in = '';
        }
        var seeMore = '';
        if (list_size > maxRows) {
            seeMore = "<fieldset class='tblFooters' style='text-align:center;font-weight:bold'>" +
                "<a href='#' id='seeMore'>" + PMA_messages.seeMore + "</a></fieldset>";
        }
        var central_columns_dialog = "<div style='max-height:400px'>" +
            "<fieldset>" +
            search_in +
            "<table id='col_list' style='width:100%' class='values'>" + fields + "</table>" +
            "</fieldset>" +
            seeMore +
            "</div>";

        var width = parseInt(
            (parseInt($('html').css('font-size'), 10) / 13) * 500,
            10
        );
        if (! width) {
            width = 500;
        }
        var buttonOptions = {};
        var $central_columns_dialog = $(central_columns_dialog).dialog({
            minWidth: width,
            maxHeight: 450,
            modal: true,
            title: PMA_messages.pickColumnTitle,
            buttons: buttonOptions,
            open: function () {
                $('#col_list').on("click", ".pick", function (){
                    $central_columns_dialog.remove();
                });
                $(".filter_rows").on("keyup", function () {
                    $.uiTableFilter($("#col_list"), $(this).val());
                });
                $("#seeMore").click(function() {
                    fields = '';
                    min = (list_size <= maxRows + result_pointer) ? list_size : maxRows + result_pointer;
                    for (i = result_pointer; i < min; i++) {

                        fields += '<tr><td><div><span style="font-weight:bold">' +
                            central_column_list[db + '_' + table][i].col_name +
                            '</span><br><span style="color:gray">' +
                            central_column_list[db + '_' + table][i].col_type;

                        if (central_column_list[db + '_' + table][i].col_attribute !== '') {
                            fields += '(' + central_column_list[db + '_' + table][i].col_attribute + ') ';
                        }
                        if (central_column_list[db + '_' + table][i].col_length !== '') {
                            fields += '(' + central_column_list[db + '_' + table][i].col_length + ') ';
                        }
                        fields += central_column_list[db + '_' + table][i].col_extra + '</span>' +
                            '</div></td>';
                        if (pick) {
                            fields += '<td><input class="pick" style="width:100%" type="submit" value="' +
                                PMA_messages.pickColumn + '" onclick="autoPopulate(\'' + colid + '\',' + i + ')"/></td>';
                        }
                        fields += '</tr>';
                    }
                    $("#col_list").append(fields);
                    result_pointer = i;
                    if (result_pointer === list_size) {
                        $('.tblFooters').hide();
                    }
                    return false;
                });
                $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button:first').focus();
            },
            close: function () {
                $('#col_list').off("click", ".pick");
                $(".filter_rows").off("keyup");
                $(this).remove();
            }
        });
        return false;
    });

   // $(document).on('click', 'a.show_central_list',function(e) {

   // });
    // When "add a new value" is clicked, append an empty text field
    $(document).on('click', "input.add_value", function (e) {
        e.preventDefault();
        var num_new_rows = $enum_editor_dialog.find("div.slider").slider('value');
        while (num_new_rows--) {
            $enum_editor_dialog.find('.values')
                .append(
                    "<tr style='display: none;'><td>" +
                    "<input type='text' />" +
                    "</td><td class='drop'>" +
                    PMA_getImage('b_drop.png') +
                    "</td></tr>"
                )
                .find('tr:last')
                .show('fast');
        }
    });

    // Removes the specified row from the enum editor
    $(document).on('click', "#enum_editor td.drop", function () {
        $(this).closest('tr').hide('fast', function () {
            $(this).remove();
        });
    });
});

/**
 * Ensures indexes names are valid according to their type and, for a primary
 * key, lock index name to 'PRIMARY'
 * @param string   form_id  Variable which parses the form name as
 *                            the input
 * @return boolean  false    if there is no index form, true else
 */
function checkIndexName(form_id)
{
    if ($("#" + form_id).length === 0) {
        return false;
    }

    // Gets the elements pointers
    var $the_idx_name = $("#input_index_name");
    var $the_idx_choice = $("#select_index_choice");

    // Index is a primary key
    if ($the_idx_choice.find("option:selected").val() == 'PRIMARY') {
        $the_idx_name.val('PRIMARY');
        $the_idx_name.prop("disabled", true);
    }

    // Other cases
    else {
        if ($the_idx_name.val() == 'PRIMARY') {
            $the_idx_name.val("");
        }
        $the_idx_name.prop("disabled", false);
    }

    return true;
} // end of the 'checkIndexName()' function

AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', '#index_frm input[type=submit]');
});
AJAX.registerOnload('functions.js', function () {
    /**
     * Handler for adding more columns to an index in the editor
     */
    $(document).on('click', '#index_frm input[type=submit]', function (event) {
        event.preventDefault();
        var rows_to_add = $(this)
            .closest('fieldset')
            .find('.slider')
            .slider('value');

        var tempEmptyVal = function () {
            $(this).val('');
        };

        var tempSetFocus = function () {
            if ($(this).find("option:selected").val() === '') {
                return true;
            }
            $(this).closest("tr").find("input").focus();
        };

        while (rows_to_add--) {
            var $indexColumns = $('#index_columns');
            var $newrow = $indexColumns
                .find('tbody > tr:first')
                .clone()
                .appendTo(
                    $indexColumns.find('tbody')
                );
            $newrow.find(':input').each(tempEmptyVal);
            // focus index size input on column picked
            $newrow.find('select').change(tempSetFocus);
        }
    });
});

function indexEditorDialog(url, title, callback_success, callback_failure)
{
    /*Remove the hidden dialogs if there are*/
    var $editIndexDialog = $('#edit_index_dialog');
    if ($editIndexDialog.length !== 0) {
        $editIndexDialog.remove();
    }
    var $div = $('<div id="edit_index_dialog"></div>');

    /**
     * @var button_options Object that stores the options
     *                     passed to jQueryUI dialog
     */
    var button_options = {};
    button_options[PMA_messages.strGo] = function () {
        /**
         * @var    the_form    object referring to the export form
         */
        var $form = $("#index_frm");
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize() + "&do_save_data=1", function (data) {
            var $sqlqueryresults = $(".sqlqueryresults");
            if ($sqlqueryresults.length !== 0) {
                $sqlqueryresults.remove();
            }
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxShowMessage(data.message);
                var $resultQuery = $('.result_query');
                if ($resultQuery.length) {
                    $resultQuery.remove();
                }
                if (data.sql_query) {
                    $('<div class="result_query"></div>')
                        .html(data.sql_query)
                        .prependTo('#page_content');
                    PMA_highlightSQL($('#page_content'));
                }
                $(".result_query .notice").remove();
                $resultQuery.prepend(data.message);
                /*Reload the field form*/
                $("#table_index").remove();
                $("<div id='temp_div'><div>")
                    .append(data.index_table)
                    .find("#table_index")
                    .insertAfter("#index_header");
                var $editIndexDialog = $("#edit_index_dialog");
                if ($editIndexDialog.length > 0) {
                    $editIndexDialog.dialog("close");
                }
                $('div.no_indexes_defined').hide();
                if (callback_success) {
                    callback_success();
                }
                PMA_reloadNavigation();
            } else {
                var $temp_div = $("<div id='temp_div'><div>").append(data.error);
                var $error;
                if ($temp_div.find(".error code").length !== 0) {
                    $error = $temp_div.find(".error code").addClass("error");
                } else {
                    $error = $temp_div;
                }
                if (callback_failure) {
                    callback_failure();
                }
                PMA_ajaxShowMessage($error, false);
            }
        }); // end $.post()
    };
    button_options[PMA_messages.strPreviewSQL] = function () {
        // Function for Previewing SQL
        var $form = $('#index_frm');
        PMA_previewSQL($form);
    };
    button_options[PMA_messages.strCancel] = function () {
        $(this).dialog('close');
    };
    var $msgbox = PMA_ajaxShowMessage();
    $.get("tbl_indexes.php", url, function (data) {
        if (typeof data !== 'undefined' && data.success === false) {
            //in the case of an error, show the error message returned.
            PMA_ajaxShowMessage(data.error, false);
        } else {
            PMA_ajaxRemoveMessage($msgbox);
            // Show dialog if the request was successful
            $div
            .append(data.message)
            .dialog({
                title: title,
                width: 450,
                height: 350,
                open: PMA_verifyColumnsProperties,
                modal: true,
                buttons: button_options,
                close: function () {
                    $(this).remove();
                }
            });
            $div.find('.tblFooters').remove();
            showIndexEditDialog($div);
        }
    }); // end $.get()
}

function showIndexEditDialog($outer)
{
    checkIndexType();
    checkIndexName("index_frm");
    var $indexColumns = $('#index_columns');
    $indexColumns.find('td').each(function () {
        $(this).css("width", $(this).width() + 'px');
    });
    $indexColumns.find('tbody').sortable({
        axis: 'y',
        containment: $indexColumns.find("tbody"),
        tolerance: 'pointer'
    });
    PMA_showHints($outer);
    PMA_init_slider();
    // Add a slider for selecting how many columns to add to the index
    $outer.find('.slider').slider({
        animate: true,
        value: 1,
        min: 1,
        max: 16,
        slide: function (event, ui) {
            $(this).closest('fieldset').find('input[type=submit]').val(
                PMA_sprintf(PMA_messages.strAddToIndex, ui.value)
            );
        }
    });
    $('div.add_fields').removeClass('hide');
    // focus index size input on column picked
    $outer.find('table#index_columns select').change(function () {
        if ($(this).find("option:selected").val() === '') {
            return true;
        }
        $(this).closest("tr").find("input").focus();
    });
    // Focus the slider, otherwise it looks nearly transparent
    $('a.ui-slider-handle').addClass('ui-state-focus');
    // set focus on index name input, if empty
    var input = $outer.find('input#input_index_name');
    input.val() || input.focus();
}

/**
 * Function to display tooltips that were
 * generated on the PHP side by PMA_Util::showHint()
 *
 * @param object $div a div jquery object which specifies the
 *                    domain for searching for tooltips. If we
 *                    omit this parameter the function searches
 *                    in the whole body
 **/
function PMA_showHints($div)
{
    if ($div === undefined || ! $div instanceof jQuery || $div.length === 0) {
        $div = $("body");
    }
    $div.find('.pma_hint').each(function () {
        PMA_tooltip(
            $(this).children('img'),
            'img',
            $(this).children('span').html()
        );
    });
}

AJAX.registerOnload('functions.js', function () {
    PMA_showHints();
});

function PMA_mainMenuResizerCallback() {
    // 5 px margin for jumping menu in Chrome
    return $(document.body).width() - 5;
}
// This must be fired only once after the initial page load
$(function () {
    // Initialise the menu resize plugin
    $('#topmenu').menuResizer(PMA_mainMenuResizerCallback);
    // register resize event
    $(window).resize(function () {
        $('#topmenu').menuResizer('resize');
    });
});

/**
 * Get the row number from the classlist (for example, row_1)
 */
function PMA_getRowNumber(classlist)
{
    return parseInt(classlist.split(/\s+row_/)[1], 10);
}

/**
 * Changes status of slider
 */
function PMA_set_status_label($element)
{
    var text;
    if ($element.css('display') == 'none') {
        text = '+ ';
    } else {
        text = '- ';
    }
    $element.closest('.slide-wrapper').prev().find('span').text(text);
}

/**
 * var  toggleButton  This is a function that creates a toggle
 *                    sliding button given a jQuery reference
 *                    to the correct DOM element
 */
var toggleButton = function ($obj) {
    // In rtl mode the toggle switch is flipped horizontally
    // so we need to take that into account
    var right;
    if ($('span.text_direction', $obj).text() == 'ltr') {
        right = 'right';
    } else {
        right = 'left';
    }
    /**
     *  var  h  Height of the button, used to scale the
     *          background image and position the layers
     */
    var h = $obj.height();
    $('img', $obj).height(h);
    $('table', $obj).css('bottom', h - 1);
    /**
     *  var  on   Width of the "ON" part of the toggle switch
     *  var  off  Width of the "OFF" part of the toggle switch
     */
    var on  = $('td.toggleOn', $obj).width();
    var off = $('td.toggleOff', $obj).width();
    // Make the "ON" and "OFF" parts of the switch the same size
    // + 2 pixels to avoid overflowed
    $('td.toggleOn > div', $obj).width(Math.max(on, off) + 2);
    $('td.toggleOff > div', $obj).width(Math.max(on, off) + 2);
    /**
     *  var  w  Width of the central part of the switch
     */
    var w = parseInt(($('img', $obj).height() / 16) * 22, 10);
    // Resize the central part of the switch on the top
    // layer to match the background
    $('table td:nth-child(2) > div', $obj).width(w);
    /**
     *  var  imgw    Width of the background image
     *  var  tblw    Width of the foreground layer
     *  var  offset  By how many pixels to move the background
     *               image, so that it matches the top layer
     */
    var imgw = $('img', $obj).width();
    var tblw = $('table', $obj).width();
    var offset = parseInt(((imgw - tblw) / 2), 10);
    // Move the background to match the layout of the top layer
    $obj.find('img').css(right, offset);
    /**
     *  var  offw    Outer width of the "ON" part of the toggle switch
     *  var  btnw    Outer width of the central part of the switch
     */
    var offw = $('td.toggleOff', $obj).outerWidth();
    var btnw = $('table td:nth-child(2)', $obj).outerWidth();
    // Resize the main div so that exactly one side of
    // the switch plus the central part fit into it.
    $obj.width(offw + btnw + 2);
    /**
     *  var  move  How many pixels to move the
     *             switch by when toggling
     */
    var move = $('td.toggleOff', $obj).outerWidth();
    // If the switch is initialized to the
    // OFF state we need to move it now.
    if ($('div.container', $obj).hasClass('off')) {
        if (right == 'right') {
            $('div.container', $obj).animate({'left': '-=' + move + 'px'}, 0);
        } else {
            $('div.container', $obj).animate({'left': '+=' + move + 'px'}, 0);
        }
    }
    // Attach an 'onclick' event to the switch
    $('div.container', $obj).click(function () {
        if ($(this).hasClass('isActive')) {
            return false;
        } else {
            $(this).addClass('isActive');
        }
        var $msg = PMA_ajaxShowMessage();
        var $container = $(this);
        var callback = $('span.callback', this).text();
        var operator, url, removeClass, addClass;
        // Perform the actual toggle
        if ($(this).hasClass('on')) {
            if (right == 'right') {
                operator = '-=';
            } else {
                operator = '+=';
            }
            url = $(this).find('td.toggleOff > span').text();
            removeClass = 'on';
            addClass = 'off';
        } else {
            if (right == 'right') {
                operator = '+=';
            } else {
                operator = '-=';
            }
            url = $(this).find('td.toggleOn > span').text();
            removeClass = 'off';
            addClass = 'on';
        }
        $.post(url, {'ajax_request': true}, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                $container
                .removeClass(removeClass)
                .addClass(addClass)
                .animate({'left': operator + move + 'px'}, function () {
                    $container.removeClass('isActive');
                });
                eval(callback);
            } else {
                PMA_ajaxShowMessage(data.error, false);
                $container.removeClass('isActive');
            }
        });
    });
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $('div.container').unbind('click');
});
/**
 * Initialise all toggle buttons
 */
AJAX.registerOnload('functions.js', function () {
    $('div.toggleAjax').each(function () {
        var $button = $(this).show();
        $button.find('img').each(function () {
            if (this.complete) {
                toggleButton($button);
            } else {
                $(this).load(function () {
                    toggleButton($button);
                });
            }
        });
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('change', 'select.pageselector');
    $(document).off('click', 'a.formLinkSubmit');
    $('#update_recent_tables').unbind('ready');
    $('#sync_favorite_tables').unbind('ready');
});

AJAX.registerOnload('functions.js', function () {

    /**
     * Autosubmit page selector
     */
    $(document).on('change', 'select.pageselector', function (event) {
        event.stopPropagation();
        // Check where to load the new content
        if ($(this).closest("#pma_navigation").length === 0) {
            // For the main page we don't need to do anything,
            $(this).closest("form").submit();
        } else {
            // but for the navigation we need to manually replace the content
            PMA_navigationTreePagination($(this));
        }
    });

    /**
     * Load version information asynchronously.
     */
    if ($('li.jsversioncheck').length > 0) {
        $.getJSON('version_check.php', {'server' : PMA_commonParams.get('server')}, PMA_current_version);
    }

    if ($('#is_git_revision').length > 0) {
        setTimeout(PMA_display_git_revision, 10);
    }

    /**
     * Slider effect.
     */
    PMA_init_slider();

    /**
     * Enables the text generated by PMA_Util::linkOrButton() to be clickable
     */
    $(document).on('click', 'a.formLinkSubmit', function (e) {
        if (! $(this).hasClass('requireConfirm')) {
            submitFormLink($(this));
            return false;
        }
    });

    var $updateRecentTables = $('#update_recent_tables');
    if ($updateRecentTables.length) {
        $.get(
            $updateRecentTables.attr('href'),
            {no_debug: true},
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    $('#pma_recent_list').html(data.list);
                }
            }
        );
    }

    // Sync favorite tables from localStorage to pmadb.
    if ($('#sync_favorite_tables').length) {
        $.ajax({
            url: $('#sync_favorite_tables').attr("href"),
            cache: false,
            type: 'POST',
            data: {
                favorite_tables: (isStorageSupported('localStorage') && typeof window.localStorage.favorite_tables !== 'undefined')
                    ? window.localStorage.favorite_tables
                    : '',
                no_debug: true
            },
            success: function (data) {
                // Update localStorage.
                if (isStorageSupported('localStorage')) {
                    window.localStorage.favorite_tables = data.favorite_tables;
                }
                $('#pma_favorite_list').html(data.list);
            }
        });
    }
}); // end of $()

/**
 * Submits the form placed in place of a link due to the excessive url length
 *
 * @param $link anchor
 * @returns {Boolean}
 */
function submitFormLink($link)
{
    if ($link.attr('href').indexOf('=') != -1) {
        var data = $link.attr('href').substr($link.attr('href').indexOf('#') + 1).split('=', 2);
        $link.parents('form').append('<input type="hidden" name="' + data[0] + '" value="' + data[1] + '"/>');
    }
    $link.parents('form').submit();
}

/**
 * Initializes slider effect.
 */
function PMA_init_slider()
{
    $('div.pma_auto_slider').each(function () {
        var $this = $(this);
        if ($this.data('slider_init_done')) {
            return;
        }
        var $wrapper = $('<div>', {'class': 'slide-wrapper'});
        $wrapper.toggle($this.is(':visible'));
        $('<a>', {href: '#' + this.id, "class": 'ajax'})
            .text($this.attr('title'))
            .prepend($('<span>'))
            .insertBefore($this)
            .click(function () {
                var $wrapper = $this.closest('.slide-wrapper');
                var visible = $this.is(':visible');
                if (!visible) {
                    $wrapper.show();
                }
                $this[visible ? 'hide' : 'show']('blind', function () {
                    $wrapper.toggle(!visible);
                    $wrapper.parent().toggleClass("print_ignore", visible);
                    PMA_set_status_label($this);
                });
                return false;
            });
        $this.wrap($wrapper);
        $this.removeAttr('title');
        PMA_set_status_label($this);
        $this.data('slider_init_done', 1);
    });
}

/**
 * Initializes slider effect.
 */
AJAX.registerOnload('functions.js', function () {
    PMA_init_slider();
});

/**
 * Restores sliders to the state they were in before initialisation.
 */
AJAX.registerTeardown('functions.js', function () {
    $('div.pma_auto_slider').each(function () {
        var $this = $(this);
        $this.removeData();
        $this.parent().replaceWith($this);
        $this.parent().children('a').remove();
    });
});

/**
 * Creates a message inside an object with a sliding effect
 *
 * @param msg    A string containing the text to display
 * @param $obj   a jQuery object containing the reference
 *                 to the element where to put the message
 *                 This is optional, if no element is
 *                 provided, one will be created below the
 *                 navigation links at the top of the page
 *
 * @return bool   True on success, false on failure
 */
function PMA_slidingMessage(msg, $obj)
{
    if (msg === undefined || msg.length === 0) {
        // Don't show an empty message
        return false;
    }
    if ($obj === undefined || ! $obj instanceof jQuery || $obj.length === 0) {
        // If the second argument was not supplied,
        // we might have to create a new DOM node.
        if ($('#PMA_slidingMessage').length === 0) {
            $('#page_content').prepend(
                '<span id="PMA_slidingMessage" ' +
                'style="display: inline-block;"></span>'
            );
        }
        $obj = $('#PMA_slidingMessage');
    }
    if ($obj.has('div').length > 0) {
        // If there already is a message inside the
        // target object, we must get rid of it
        $obj
        .find('div')
        .first()
        .fadeOut(function () {
            $obj
            .children()
            .remove();
            $obj
            .append('<div>' + msg + '</div>');
            // highlight any sql before taking height;
            PMA_highlightSQL($obj);
            $obj.find('div')
                .first()
                .hide();
            $obj
            .animate({
                height: $obj.find('div').first().height()
            })
            .find('div')
            .first()
            .fadeIn();
        });
    } else {
        // Object does not already have a message
        // inside it, so we simply slide it down
        $obj.width('100%')
            .html('<div>' + msg + '</div>');
        // highlight any sql before taking height;
        PMA_highlightSQL($obj);
        var h = $obj
            .find('div')
            .first()
            .hide()
            .height();
        $obj
        .find('div')
        .first()
        .css('height', 0)
        .show()
        .animate({
                height: h
            }, function () {
            // Set the height of the parent
            // to the height of the child
                $obj
                .height(
                    $obj
                    .find('div')
                    .first()
                    .height()
                );
            });
    }
    return true;
} // end PMA_slidingMessage()

/**
 * Attach CodeMirror2 editor to SQL edit area.
 */
AJAX.registerOnload('functions.js', function () {
    var $elm = $('#sqlquery');
    if ($elm.length > 0) {
        if (typeof CodeMirror != 'undefined') {
            codemirror_editor = PMA_getSQLEditor($elm);
            codemirror_editor.focus();
            codemirror_editor.on("blur", updateQueryParameters);
        } else {
            // without codemirror
            $elm.focus()
                .bind('blur', updateQueryParameters);
        }
    }
    PMA_highlightSQL($('body'));
});
AJAX.registerTeardown('functions.js', function () {
    if (codemirror_editor) {
        $('#sqlquery').text(codemirror_editor.getValue());
        codemirror_editor.toTextArea();
        codemirror_editor = false;
    }
});
AJAX.registerOnload('functions.js', function () {
    // initializes all lock-page elements lock-id and
    // val-hash data property
    $('#page_content form.lock-page textarea, ' +
            '#page_content form.lock-page input[type="text"], '+
            '#page_content form.lock-page input[type="number"], '+
            '#page_content form.lock-page select').each(function (i) {
        $(this).data('lock-id', i);
        // val-hash is the hash of default value of the field
        // so that it can be compared with new value hash
        // to check whether field was modified or not.
        $(this).data('val-hash', AJAX.hash($(this).val()));
    });

    // initializes lock-page elements (input types checkbox and radio buttons)
    // lock-id and val-hash data property
    $('#page_content form.lock-page input[type="checkbox"], ' +
            '#page_content form.lock-page input[type="radio"]').each(function (i) {
        $(this).data('lock-id', i);
        $(this).data('val-hash', AJAX.hash($(this).is(":checked")));
    });
});
/**
 * jQuery plugin to cancel selection in HTML code.
 */
(function ($) {
    $.fn.noSelect = function (p) { //no select plugin by Paulo P.Marinas
        var prevent = (p === null) ? true : p;
        var is_msie = navigator.userAgent.indexOf('MSIE') > -1 || !!window.navigator.userAgent.match(/Trident.*rv\:11\./);
        var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
        var is_safari = navigator.userAgent.indexOf("Safari") > -1;
        var is_opera = navigator.userAgent.indexOf("Presto") > -1;
        if (prevent) {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).bind('selectstart', function () {
                        return false;
                    });
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'none');
                    $('body').trigger('focus');
                } else if (is_opera) {
                    $(this).bind('mousedown', function () {
                        return false;
                    });
                } else {
                    $(this).attr('unselectable', 'on');
                }
            });
        } else {
            return this.each(function () {
                if (is_msie || is_safari) {
                    $(this).unbind('selectstart');
                } else if (is_firefox) {
                    $(this).css('MozUserSelect', 'inherit');
                } else if (is_opera) {
                    $(this).unbind('mousedown');
                } else {
                    $(this).removeAttr('unselectable');
                }
            });
        }
    }; //end noSelect
})(jQuery);

/**
 * jQuery plugin to correctly filter input fields by value, needed
 * because some nasty values may break selector syntax
 */
(function ($) {
    $.fn.filterByValue = function (value) {
        return this.filter(function () {
            return $(this).val() === value;
        });
    };
})(jQuery);

/**
 * Return value of a cell in a table.
 */
function PMA_getCellValue(td) {
    var $td = $(td);
    if ($td.is('.null')) {
        return '';
    } else if ((! $td.is('.to_be_saved')
        || $td.is('.set'))
        && $td.data('original_data')
    ) {
        return $td.data('original_data');
    } else {
        return $td.text();
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $(document).off('click', 'a.themeselect');
    $(document).off('change', '.autosubmit');
    $('a.take_theme').unbind('click');
});

AJAX.registerOnload('functions.js', function () {
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
    $('a.take_theme').click(function (e) {
        var what = this.name;
        if (window.opener && window.opener.document.forms.setTheme.elements.set_theme) {
            window.opener.document.forms.setTheme.elements.set_theme.value = what;
            window.opener.document.forms.setTheme.submit();
            window.close();
            return false;
        }
        return true;
    });
});

/**
 * Print button
 */
function printPage()
{
    // Do print the page
    if (typeof(window.print) != 'undefined') {
        window.print();
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function () {
    $('input#print').unbind('click');
    $(document).off('click', 'a.create_view.ajax');
    $(document).off('keydown', '#createViewDialog input, #createViewDialog select');
    $(document).off('change', '#fkc_checkbox');
});

AJAX.registerOnload('functions.js', function () {
    $('input#print').click(printPage);
    /**
     * Ajaxification for the "Create View" action
     */
    $(document).on('click', 'a.create_view.ajax', function (e) {
        e.preventDefault();
        PMA_createViewDialog($(this));
    });
    /**
     * Attach Ajax event handlers for input fields in the editor
     * and used to submit the Ajax request when the ENTER key is pressed.
     */
    if ($('#createViewDialog').length !== 0) {
        $(document).on('keydown', '#createViewDialog input, #createViewDialog select', function (e) {
            if (e.which === 13) { // 13 is the ENTER key
                e.preventDefault();

                // with preventing default, selection by <select> tag
                // was also prevented in IE
                $(this).blur();

                $(this).closest('.ui-dialog').find('.ui-button:first').click();
            }
        }); // end $(document).on()
    }

    syntaxHighlighter = PMA_getSQLEditor($('textarea[name="view[as]"]'));

});

function PMA_createViewDialog($this)
{
    var $msg = PMA_ajaxShowMessage();
    var syntaxHighlighter = null;
    $.get($this.attr('href') + '&ajax_request=1&ajax_dialog=1', function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            PMA_ajaxRemoveMessage($msg);
            var buttonOptions = {};
            buttonOptions[PMA_messages.strGo] = function () {
                if (typeof CodeMirror !== 'undefined') {
                    syntaxHighlighter.save();
                }
                $msg = PMA_ajaxShowMessage();
                $.get('view_create.php', $('#createViewDialog').find('form').serialize(), function (data) {
                    PMA_ajaxRemoveMessage($msg);
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#createViewDialog').dialog("close");
                        $('.result_query').html(data.message);
                        PMA_reloadNavigation();
                    } else {
                        PMA_ajaxShowMessage(data.error, false);
                    }
                });
            };
            buttonOptions[PMA_messages.strClose] = function () {
                $(this).dialog("close");
            };
            var $dialog = $('<div/>').attr('id', 'createViewDialog').append(data.message).dialog({
                width: 600,
                minWidth: 400,
                modal: true,
                buttons: buttonOptions,
                title: PMA_messages.strCreateView,
                close: function () {
                    $(this).remove();
                }
            });
            // Attach syntax highlighted editor
            syntaxHighlighter = PMA_getSQLEditor($dialog.find('textarea'));
            $('input:visible[type=text]', $dialog).first().focus();
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    });
}

/**
 * Makes the breadcrumbs and the menu bar float at the top of the viewport
 */
$(function () {
    if ($("#floating_menubar").length && $('#PMA_disable_floating_menubar').length === 0) {
        var left = $('html').attr('dir') == 'ltr' ? 'left' : 'right';
        $("#floating_menubar")
            .css('margin-' + left, $('#pma_navigation').width() + $('#pma_navigation_resizer').width())
            .css(left, 0)
            .css({
                'position': 'fixed',
                'top': 0,
                'width': '100%',
                'z-index': 99
            })
            .append($('#serverinfo'))
            .append($('#topmenucontainer'));
        // Allow the DOM to render, then adjust the padding on the body
        setTimeout(function () {
            $('body').css(
                'padding-top',
                $('#floating_menubar').outerHeight(true)
            );
            $('#topmenu').menuResizer('resize');
        }, 4);
    }
});

/**
 * Scrolls the page to the top if clicking the serverinfo bar
 */
$(function () {
    $(document).delegate("#serverinfo, #goto_pagetop", "click", function (event) {
        event.preventDefault();
        $('html, body').animate({scrollTop: 0}, 'fast');
    });
});

var checkboxes_sel = "input.checkall:checkbox:enabled";
/**
 * Watches checkboxes in a form to set the checkall box accordingly
 */
var checkboxes_changed = function () {
    var $form = $(this.form);
    // total number of checkboxes in current form
    var total_boxes = $form.find(checkboxes_sel).length;
    // number of checkboxes checked in current form
    var checked_boxes = $form.find(checkboxes_sel + ":checked").length;
    var $checkall = $form.find("input.checkall_box");
    if (total_boxes == checked_boxes) {
        $checkall.prop({checked: true, indeterminate: false});
    }
    else if (checked_boxes > 0) {
        $checkall.prop({checked: true, indeterminate: true});
    }
    else {
        $checkall.prop({checked: false, indeterminate: false});
    }
};
$(document).on("change", checkboxes_sel, checkboxes_changed);

$(document).on("change", "input.checkall_box", function () {
    var is_checked = $(this).is(":checked");
    $(this.form).find(checkboxes_sel).prop("checked", is_checked)
    .parents("tr").toggleClass("marked", is_checked);
});

/**
 * Watches checkboxes in a sub form to set the sub checkall box accordingly
 */
var sub_checkboxes_changed = function () {
    var $form = $(this).parent().parent();
    // total number of checkboxes in current sub form
    var total_boxes = $form.find(checkboxes_sel).length;
    // number of checkboxes checked in current sub form
    var checked_boxes = $form.find(checkboxes_sel + ":checked").length;
    var $checkall = $form.find("input.sub_checkall_box");
    if (total_boxes == checked_boxes) {
        $checkall.prop({checked: true, indeterminate: false});
    }
    else if (checked_boxes > 0) {
        $checkall.prop({checked: true, indeterminate: true});
    }
    else {
        $checkall.prop({checked: false, indeterminate: false});
    }
};
$(document).on("change", checkboxes_sel + ", input.checkall_box:checkbox:enabled", sub_checkboxes_changed);

$(document).on("change", "input.sub_checkall_box", function () {
    var is_checked = $(this).is(":checked");
    var $form = $(this).parent().parent();
    $form.find(checkboxes_sel).prop("checked", is_checked)
    .parents("tr").toggleClass("marked", is_checked);
});

/**
 * Toggles row colors of a set of 'tr' elements starting from a given element
 *
 * @param $start Starting element
 */
function toggleRowColors($start)
{
    for (var $curr_row = $start; $curr_row.length > 0; $curr_row = $curr_row.next()) {
        if ($curr_row.hasClass('odd')) {
            $curr_row.removeClass('odd').addClass('even');
        } else if ($curr_row.hasClass('even')) {
            $curr_row.removeClass('even').addClass('odd');
        }
    }
}

/**
 * Formats a byte number to human-readable form
 *
 * @param bytes the bytes to format
 * @param optional subdecimals the number of digits after the point
 * @param optional pointchar the char to use as decimal point
 */
function formatBytes(bytes, subdecimals, pointchar) {
    if (!subdecimals) {
        subdecimals = 0;
    }
    if (!pointchar) {
        pointchar = '.';
    }
    var units = ['B', 'KiB', 'MiB', 'GiB'];
    for (var i = 0; bytes > 1024 && i < units.length; i++) {
        bytes /= 1024;
    }
    var factor = Math.pow(10, subdecimals);
    bytes = Math.round(bytes * factor) / factor;
    bytes = bytes.toString().split('.').join(pointchar);
    return bytes + ' ' + units[i];
}

AJAX.registerOnload('functions.js', function () {
    /**
     * Opens pma more themes link in themes browser, in new window instead of popup
     * This way, we don't break HTML validity
     */
    $("a._blank").prop("target", "_blank");
    /**
     * Reveal the login form to users with JS enabled
     * and focus the appropriate input field
     */
    var $loginform = $('#loginform');
    if ($loginform.length) {
        $loginform.find('.js-show').show();
        if ($('#input_username').val()) {
            $('#input_password').focus();
        } else {
            $('#input_username').focus();
        }
    }
});

/**
 * Dynamically adjust the width of the boxes
 * on the table and db operations pages
 */
(function () {
    function DynamicBoxes() {
        var $boxContainer = $('#boxContainer');
        if ($boxContainer.length) {
            var minWidth = $boxContainer.data('box-width');
            var viewport = $(window).width() - $('#pma_navigation').width();
            var slots = Math.floor(viewport / minWidth);
            $boxContainer.children()
            .each(function () {
                if (viewport < minWidth) {
                    $(this).width(minWidth);
                } else {
                    $(this).css('width', ((1 /  slots) * 100) + "%");
                }
            })
            .removeClass('clearfloat')
            .filter(':nth-child(' + slots + 'n+1)')
            .addClass('clearfloat');
        }
    }
    AJAX.registerOnload('functions.js', function () {
        DynamicBoxes();
    });
    $(function () {
        $(window).resize(DynamicBoxes);
    });
})();

/**
 * Formats timestamp for display
 */
function PMA_formatDateTime(date, seconds) {
    var result = $.datepicker.formatDate('yy-mm-dd', date);
    var timefmt = 'HH:mm';
    if (seconds) {
        timefmt = 'HH:mm:ss';
    }
    return result + ' ' + $.datepicker.formatTime(
        timefmt, {
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds()
        }
    );
}

/**
 * Check than forms have less fields than max allowed by PHP.
 */
function checkNumberOfFields() {
    if (typeof maxInputVars === 'undefined') {
        return false;
    }
    if (false === maxInputVars) {
        return false;
    }
    $('form').each(function() {
        var nbInputs = $(this).find(':input').length;
        if (nbInputs > maxInputVars) {
            var warning = PMA_sprintf(PMA_messages.strTooManyInputs, maxInputVars);
            PMA_ajaxShowMessage(warning);
            return false;
        }
        return true;
    });

    return true;
}

/**
 * Ignore the displayed php errors.
 * Simply removes the displayed errors.
 *
 * @param  clearPrevErrors whether to clear errors stored
 *             in $_SESSION['prev_errors'] at server
 *
 */
function PMA_ignorePhpErrors(clearPrevErrors){
    if (typeof(clearPrevErrors) === "undefined" ||
        clearPrevErrors === null
    ) {
        str = false;
    }
    // send AJAX request to error_report.php with send_error_report=0, exception_type=php & token.
    // It clears the prev_errors stored in session.
    if(clearPrevErrors){
        var $pmaReportErrorsForm = $('#pma_report_errors_form');
        $pmaReportErrorsForm.find('input[name="send_error_report"]').val(0); // change send_error_report to '0'
        $pmaReportErrorsForm.submit();
    }

    // remove displayed errors
    var $pmaErrors = $('#pma_errors');
    $pmaErrors.fadeOut( "slow");
    $pmaErrors.remove();
}

/**
 * checks whether browser supports web storage
 *
 * @param type the type of storage i.e. localStorage or sessionStorage
 *
 * @returns bool
 */
function isStorageSupported(type)
{
    try {
        window[type].setItem('PMATest', 'test');
        // Check whether key-value pair was set successfully
        if (window[type].getItem('PMATest') === 'test') {
            // Supported, remove test variable from storage
            window[type].removeItem('PMATest');
            return true;
        }
    } catch(error) {
        // Not supported
        PMA_ajaxShowMessage(PMA_messages.strNoLocalStorage, false);
    }
    return false;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function(){
    $(document).off('keydown', 'form input, form textarea, form select');
});

AJAX.registerOnload('functions.js', function () {
    /**
     * Handle 'Ctrl/Alt + Enter' form submits
     */
    $('form input, form textarea, form select').on('keydown', function(e){
        if((e.ctrlKey && e.which == 13) || (e.altKey && e.which == 13)) {
            $form = $(this).closest('form');
            if (! $form.find('input[type="submit"]') ||
                ! $form.find('input[type="submit"]').click()
            ) {
                $form.submit();
            }
        }
    });
});

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('functions.js', function(){
    $(document).off('change', 'input[type=radio][name="pw_hash"]');
});

AJAX.registerOnload('functions.js', function(){
    /*
     * Display warning regarding SSL when sha256_password
     * method is selected
     * Used in user_password.php (Change Password link on index.php)
     */
    $(document).on("change", 'select#select_authentication_plugin_cp', function() {
        if (this.value === 'sha256_password') {
            $('#ssl_reqd_warning_cp').show();
        } else {
            $('#ssl_reqd_warning_cp').hide();
        }
    });
});
