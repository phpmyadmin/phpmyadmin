import $ from 'jquery';
import { AJAX } from './ajax.ts';
import { Navigation } from './navigation.ts';
import { CommonParams } from './common.ts';
import tooltip from './tooltip.ts';
import highlightSql from './sql-highlight.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from './ajax-message.ts';
import { escapeHtml } from './functions/escape.ts';
import getImageTag from './functions/getImageTag.ts';
import handleRedirectAndReload from './functions/handleRedirectAndReload.ts';
import refreshMainContent from './functions/refreshMainContent.ts';
import checkIndexType from './indexes/checkIndexType.ts';
import checkIndexName from './indexes/checkIndexName.ts';
import mainMenuResizerCallback from './functions/mainMenuResizerCallback.ts';
import isStorageSupported from './functions/isStorageSupported.ts';

/**
 * Object containing CodeMirror editor of the query editor in SQL tab.
 */
window.codeMirrorEditor = null;

/**
 * Object containing CodeMirror editor of the inline query editor.
 */
let codeMirrorInlineEditor: CodeMirror.EditorFromTextArea | null = null;

/**
 * Shows if Table/Column name autocomplete AJAX is in progress.
 * @type {boolean}
 */
let sqlAutoCompleteInProgress = false;

/**
 * Object containing list of columns in each table.
 * @type {(any[]|boolean)}
 */
let sqlAutoComplete: boolean | any[] = false;

/**
 * String containing default table to autocomplete columns.
 * @type {string}
 */
let sqlAutoCompleteDefaultTable = '';

/**
 * Array to hold the columns in central list per db.
 * @type {any[]}
 */
window.centralColumnList = [];

/**
 * Make sure that ajax requests will not be cached by appending a random variable to their parameters.
 */
function addNoCacheToAjaxRequests (options: JQuery.AjaxSettings, originalOptions: JQuery.AjaxSettings): void {
    const nocache = new Date().getTime() + '' + Math.floor(Math.random() * 1000000);
    if (typeof options.data === 'string') {
        options.data += '&_nocache=' + nocache + '&token=' + encodeURIComponent(CommonParams.get('token'));
    } else if (typeof options.data === 'object') {
        options.data = $.extend(originalOptions.data, {
            '_nocache': nocache,
            'token': CommonParams.get('token')
        });
    }
}

/**
 * Adds a date/time picker to an element
 *
 * @param {object} $thisElement a jQuery object pointing to the element
 * @param {string} type
 * @param {object} options
 */
function addDatepicker ($thisElement, type = undefined, options = undefined) {
    if (type !== 'date' && type !== 'time' && type !== 'datetime' && type !== 'timestamp') {
        return;
    }

    var showTimepicker = true;
    if (type === 'date') {
        showTimepicker = false;
    }

    // Getting the current Date and time
    var currentDateTime = new Date();

    var defaultOptions = {
        timeInput: true,
        hour: currentDateTime.getHours(),
        minute: currentDateTime.getMinutes(),
        second: currentDateTime.getSeconds(),
        showOn: 'button',
        buttonImage: window.themeImagePath + 'b_calendar.png',
        buttonImageOnly: true,
        stepMinutes: 1,
        stepHours: 1,
        showSecond: true,
        showMillisec: true,
        showMicrosec: true,
        showTimepicker: showTimepicker,
        showButtonPanel: false,
        changeYear: true,
        dateFormat: 'yy-mm-dd', // yy means year with four digits
        timeFormat: 'HH:mm:ss.lc',
        constrainInput: false,
        altFieldTimeOnly: false,
        showAnim: '',
        beforeShow: function (input, inst) {
            // Remember that we came from the datepicker; this is used
            // in table/change.js by verificationsAfterFieldChange()
            $thisElement.data('comes_from', 'datepicker');
            if ($(input).closest('.cEdit').length > 0) {
                setTimeout(function () {
                    inst.dpDiv.css({
                        top: 0,
                        left: 0,
                        position: 'relative'
                    });
                }, 0);
            }

            setTimeout(function () {
                // Fix wrong timepicker z-index, doesn't work without timeout
                $('#ui-timepicker-div').css('z-index', $('#ui-datepicker-div').css('z-index'));
                // Integrate tooltip text into dialog
                var tooltip = $thisElement.uiTooltip('instance');
                if (typeof tooltip !== 'undefined') {
                    tooltip.disable();
                    var $note = $('<p class="note"></div>');
                    $note.text(tooltip.option('content'));
                    $('div.ui-datepicker').append($note);
                }
            }, 0);
        },
        onSelect: function () {
            $thisElement.data('datepicker').inline = true;
        },
        onClose: function () {
            // The value is no more from the date picker
            $thisElement.data('comes_from', '');
            if (typeof $thisElement.data('datepicker') !== 'undefined') {
                $thisElement.data('datepicker').inline = false;
            }

            var tooltip = $thisElement.uiTooltip('instance');
            if (typeof tooltip !== 'undefined') {
                tooltip.enable();
            }
        }
    };
    if (type === 'time') {
        $thisElement.timepicker($.extend(defaultOptions, options));
        // Add a tip regarding entering MySQL allowed-values for TIME data-type
        tooltip($thisElement, 'input', window.Messages.strMysqlAllowedValuesTipTime);
    } else {
        $thisElement.datetimepicker($.extend(defaultOptions, options));
    }
}

/**
 * Add a date/time picker to each element that needs it
 * (only when jquery-ui-timepicker-addon.js is loaded)
 */
function addDateTimePicker () {
    if ($.timepicker === undefined) {
        return;
    }

    $('input.timefield, input.datefield, input.datetimefield').each(function () {
        var decimals = Number($(this).parent().attr('data-decimals'));
        var type = $(this).parent().attr('data-type');

        var showMillisec = false;
        var showMicrosec = false;
        var timeFormat = 'HH:mm:ss';
        var hourMax = 23;
        // check for decimal places of seconds
        if (decimals > 0 && type.indexOf('time') !== -1) {
            if (decimals > 3) {
                showMillisec = true;
                showMicrosec = true;
                timeFormat = 'HH:mm:ss.lc';
            } else {
                showMillisec = true;
                timeFormat = 'HH:mm:ss.l';
            }
        }

        if (type === 'time') {
            hourMax = 99;
        }

        Functions.addDatepicker($(this), type, {
            showMillisec: showMillisec,
            showMicrosec: showMicrosec,
            timeFormat: timeFormat,
            hourMax: hourMax,
            firstDay: window.firstDayOfCalendar
        });

        // Add a tip regarding entering MySQL allowed-values
        // for TIME and DATE data-type
        if ($(this).hasClass('timefield')) {
            tooltip($(this), 'input', window.Messages.strMysqlAllowedValuesTipTime);
        } else if ($(this).hasClass('datefield')) {
            tooltip($(this), 'input', window.Messages.strMysqlAllowedValuesTipDate);
        }
    });
}

/**
 * Creates an SQL editor which supports auto completing etc.
 *
 * @param $textarea   jQuery object wrapping the textarea to be made the editor
 * @param options     optional options for CodeMirror
 * @param {'vertical'|'horizontal'|'both'} resize optional resizing ('vertical', 'horizontal', 'both')
 * @param lintOptions additional options for lint
 */
function getSqlEditor ($textarea, options = undefined, resize = undefined, lintOptions = undefined): CodeMirror.EditorFromTextArea | null {
    if ($textarea.length === 0 || typeof window.CodeMirror === 'undefined') {
        return null;
    }

    var resizeType = resize;
    // merge options for CodeMirror
    var defaults = {
        lineNumbers: true,
        matchBrackets: true,
        extraKeys: { 'Ctrl-Space': 'autocomplete' },
        hintOptions: { 'completeSingle': false, 'completeOnSingleClick': true },
        indentUnit: 4,
        mode: 'text/x-mysql',
        lineWrapping: true
    };

    // @ts-ignore
    if (window.CodeMirror.sqlLint) {
        $.extend(defaults, {
            gutters: ['CodeMirror-lint-markers'],
            lint: {
                // @ts-ignore
                'getAnnotations': window.CodeMirror.sqlLint,
                'async': true,
                'lintOptions': lintOptions
            }
        });
    }

    $.extend(true, defaults, options);

    // create CodeMirror editor
    var codemirrorEditor = window.CodeMirror.fromTextArea($textarea[0], defaults);
    // allow resizing
    if (! resizeType) {
        resizeType = 'vertical';
    }

    $(codemirrorEditor.getWrapperElement())
        .css('resize', resizeType);

    // enable autocomplete
    codemirrorEditor.on('inputRead', Functions.codeMirrorAutoCompleteOnInputRead);

    // page locking
    codemirrorEditor.on('change', function (e) {
        // @ts-ignore
        e.data = {
            value: 3,
            content: codemirrorEditor.isClean(),
        };

        AJAX.lockPageHandler(e);
    });

    return codemirrorEditor;
}

/**
 * Clear text selection
 */
function clearSelection () {
    // @ts-ignore
    if (document.selection && document.selection.empty) {
        // @ts-ignore
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
 * Hides/shows the default value input field, depending on the default type
 * Ticks the NULL checkbox if NULL is chosen as default value.
 *
 * @param {JQuery<HTMLElement>} $defaultType
 */
function hideShowDefaultValue ($defaultType) {
    if ($defaultType.val() === 'USER_DEFINED') {
        $defaultType.siblings('.default_value').show().trigger('focus');
    } else {
        $defaultType.siblings('.default_value').hide();
        if ($defaultType.val() === 'NULL') {
            var $nullCheckbox = $defaultType.closest('tr').find('.allow_null');
            $nullCheckbox.prop('checked', true);
        }
    }
}

/**
 * Hides/shows the input field for column expression based on whether
 * VIRTUAL/PERSISTENT is selected
 *
 * @param $virtuality virtuality dropdown
 */
function hideShowExpression ($virtuality) {
    if ($virtuality.val() === '') {
        $virtuality.siblings('.expression').hide();
    } else {
        $virtuality.siblings('.expression').show();
    }
}

/**
 * Show notices for ENUM columns; add/hide the default value
 *
 */
function verifyColumnsProperties () {
    $('select.column_type').each(function () {
        Functions.showNoticeForEnum($(this));
        Functions.showWarningForIntTypes();
    });

    $('select.default_type').each(function () {
        Functions.hideShowDefaultValue($(this));
    });

    $('select.virtuality').each(function () {
        Functions.hideShowExpression($(this));
    });
}

/**
 * Add a hidden field to the form to indicate that this will be an
 * Ajax request (only if this hidden field does not exist)
 *
 * @param {object} $form the form
 */
function prepareForAjaxRequest ($form) {
    if (! $form.find('input:hidden').is('#ajax_request_hidden')) {
        $form.append('<input type="hidden" id="ajax_request_hidden" name="ajax_request" value="true">');
    }
}

function checkPasswordStrength (value, meterObject, meterObjectLabel, username) {
    // List of words we don't want to appear in the password
    var customDict = [
        'phpmyadmin',
        'mariadb',
        'mysql',
        'php',
        'my',
        'admin',
    ];
    if (username) {
        customDict.push(username);
    }

    window.zxcvbnts.core.zxcvbnOptions.setOptions({ dictionary: { userInputs: customDict } });
    var zxcvbnObject = window.zxcvbnts.core.zxcvbn(value);
    var strength = zxcvbnObject.score;
    strength = parseInt(strength);
    meterObject.val(strength);
    switch (strength) {
    case 0:
        meterObjectLabel.html(window.Messages.strExtrWeak);
        break;
    case 1:
        meterObjectLabel.html(window.Messages.strVeryWeak);
        break;
    case 2:
        meterObjectLabel.html(window.Messages.strWeak);
        break;
    case 3:
        meterObjectLabel.html(window.Messages.strGood);
        break;
    case 4:
        meterObjectLabel.html(window.Messages.strStrong);
    }
}

/**
 * Generate a new password and copy it to the password input areas
 *
 * @param {object} passwordForm the form that holds the password fields
 *
 * @return {boolean} always true
 */
function suggestPassword (passwordForm) {
    // restrict the password to just letters and numbers to avoid problems:
    // "editors and viewers regard the password as multiple words and
    // things like double click no longer work"
    var pwchars = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWYXZ@!_.*/()[]-';
    var passwordlength = 16;    // do we want that to be dynamic?  no, keep it simple :)
    var passwd = passwordForm.generated_pw;
    // eslint-disable-next-line compat/compat
    var randomWords = new Int32Array(passwordlength);

    passwd.value = '';

    var i;

    // First we're going to try to use a built-in CSPRNG
    // eslint-disable-next-line compat/compat
    if (window.crypto && window.crypto.getRandomValues) {
        // eslint-disable-next-line compat/compat
        window.crypto.getRandomValues(randomWords);
    } else if (window.msCrypto && window.msCrypto.getRandomValues) {
        // Because of course IE calls it msCrypto instead of being standard
        window.msCrypto.getRandomValues(randomWords);
    } else {
        // Fallback to Math.random
        for (i = 0; i < passwordlength; i++) {
            randomWords[i] = Math.floor(Math.random() * pwchars.length);
        }
    }

    for (i = 0; i < passwordlength; i++) {
        passwd.value += pwchars.charAt(Math.abs(randomWords[i]) % pwchars.length);
    }

    var $jQueryPasswordForm = $(passwordForm);

    passwordForm.elements.pma_pw.value = passwd.value;
    passwordForm.elements.pma_pw2.value = passwd.value;
    var meterObj = $jQueryPasswordForm.find('meter[name="pw_meter"]').first();
    var meterObjLabel = $jQueryPasswordForm.find('span[name="pw_strength"]').first();
    var username = '';
    if (passwordForm.elements.username) {
        username = passwordForm.elements.username.value;
    }

    Functions.checkPasswordStrength(passwd.value, meterObj, meterObjLabel, username);

    return true;
}

/**
 * for PhpMyAdmin\Display\ChangePassword and /user-password
 */
function displayPasswordGenerateButton () {
    var generatePwdRow = $('<tr></tr>').addClass('align-middle');
    $('<td></td>').html(window.Messages.strGeneratePassword).appendTo(generatePwdRow);
    var pwdCell = $('<td colspan="2"></td>').addClass('row').appendTo(generatePwdRow);

    pwdCell.append('<div class="d-flex align-items-center col-4"></div>');

    var pwdButton = ($('<input>') as JQuery<HTMLInputElement>)
        .attr({ type: 'button', id: 'button_generate_password', value: window.Messages.strGenerate })
        .addClass('btn btn-secondary button')
        .on('click', function () {
            Functions.suggestPassword(this.form);
        });
    var pwdTextbox = $('<input>')
        .attr({ type: 'text', name: 'generated_pw', id: 'generated_pw' })
        .addClass('col-6');

    pwdCell.find('div').eq(0).append(pwdButton);
    pwdCell.append(pwdTextbox);

    if (document.getElementById('button_generate_password') === null) {
        $('#tr_element_before_generate_password').parent().append(generatePwdRow);
    }

    var generatePwdDiv = $('<div></div>').addClass('item');
    $('<label></label>').attr({ for: 'button_generate_password' })
        .html(window.Messages.strGeneratePassword + ':')
        .appendTo(generatePwdDiv);

    var optionsSpan = $('<span></span>').addClass('options')
        .appendTo(generatePwdDiv);
    pwdButton.clone(true).appendTo(optionsSpan);
    pwdTextbox.clone(true).appendTo(generatePwdDiv);

    if (document.getElementById('button_generate_password') === null) {
        $('#div_element_before_generate_password').parent().append(generatePwdDiv);
    }
}

/**
 * Displays a confirmation box before submitting a "DROP/DELETE/ALTER" query.
 * This function is called while clicking links
 *
 * @param {object} theLink     the link
 * @param {object} theSqlQuery the sql query to submit
 *
 * @return {boolean} whether to run the query or not
 */
function confirmLink (theLink, theSqlQuery) {
    // Confirmation is not required in the configuration file
    // or browser is Opera (crappy js implementation)
    if (window.Messages.strDoYouReally === '' || typeof (window.opera) !== 'undefined') {
        return true;
    }

    var isConfirmed = window.confirm(window.sprintf(window.Messages.strDoYouReally, theSqlQuery));
    if (isConfirmed) {
        if (typeof (theLink.href) !== 'undefined') {
            theLink.href += CommonParams.get('arg_separator') + 'is_js_confirmed=1';
        } else if (typeof (theLink.form) !== 'undefined') {
            theLink.form.action += '?is_js_confirmed=1';
        }
    }

    return isConfirmed;
}

/**
 * Confirms a "DROP/DELETE/ALTER" query before
 * submitting it if required.
 * This function is called by the 'Functions.checkSqlQuery()' js function.
 *
 * @param {object} theForm1  the form
 * @param {string} sqlQuery1 the sql query string
 *
 * @return {boolean} whether to run the query or not
 *
 * @see Functions.checkSqlQuery()
 */
function confirmQuery (theForm1, sqlQuery1) {
    // Confirmation is not required in the configuration file
    if (window.Messages.strDoYouReally === '') {
        return true;
    }

    // Confirms a "DROP/DELETE/ALTER/TRUNCATE" statement
    //
    // TODO: find a way (if possible) to use the parser-analyser
    // for this kind of verification
    // For now, I just added a ^ to check for the statement at
    // beginning of expression

    var doConfirmRegExp0 = new RegExp('^\\s*DROP\\s+(IF EXISTS\\s+)?(TABLE|PROCEDURE)\\s', 'i');
    var doConfirmRegExp1 = new RegExp('^\\s*ALTER\\s+TABLE\\s+((`[^`]+`)|([A-Za-z0-9_$]+))\\s+DROP\\s', 'i');
    var doConfirmRegExp2 = new RegExp('^\\s*DELETE\\s+FROM\\s', 'i');
    var doConfirmRegExp3 = new RegExp('^\\s*TRUNCATE\\s', 'i');
    var doConfirmRegExp4 = new RegExp('^(?=.*UPDATE\\b)^((?!WHERE).)*$', 'i');

    if (doConfirmRegExp0.test(sqlQuery1) ||
        doConfirmRegExp1.test(sqlQuery1) ||
        doConfirmRegExp2.test(sqlQuery1) ||
        doConfirmRegExp3.test(sqlQuery1) ||
        doConfirmRegExp4.test(sqlQuery1)) {
        var message;
        if (sqlQuery1.length > 100) {
            message = sqlQuery1.substring(0, 100) + '\n    ...';
        } else {
            message = sqlQuery1;
        }

        var isConfirmed = window.confirm(window.sprintf(window.Messages.strDoYouReally, message));
        // statement is confirmed -> update the
        // "is_js_confirmed" form field so the confirm test won't be
        // run on the server side and allows to submit the form
        if (isConfirmed) {
            theForm1.elements.is_js_confirmed.value = 1;

            return true;
        } else {
            // statement is rejected -> do not submit the form
            window.focus();

            return false;
        } // end if (handle confirm box result)
    } // end if (display confirm box)

    return true;
}

/**
 * Displays an error message if the user submitted the sql query form with no
 * sql query, else checks for "DROP/DELETE/ALTER" statements
 *
 * @param {object} theForm the form
 *
 * @return {boolean} always false
 *
 * @see Functions.confirmQuery()
 */
function checkSqlQuery (theForm) {
    // get the textarea element containing the query
    var sqlQuery;
    if (window.codeMirrorEditor) {
        window.codeMirrorEditor.save();
        sqlQuery = window.codeMirrorEditor.getValue();
    } else {
        sqlQuery = theForm.elements.sql_query.value;
    }

    var spaceRegExp = new RegExp('\\s+');
    if (typeof (theForm.elements.sql_file) !== 'undefined' &&
        theForm.elements.sql_file.value.replace(spaceRegExp, '') !== '') {
        return true;
    }

    if (typeof (theForm.elements.id_bookmark) !== 'undefined' &&
        (theForm.elements.id_bookmark.value !== null || theForm.elements.id_bookmark.value !== '') &&
        theForm.elements.id_bookmark.selectedIndex !== 0) {
        return true;
    }

    var result = false;
    // Checks for "DROP/DELETE/ALTER" statements
    if (sqlQuery.replace(spaceRegExp, '') !== '') {
        result = Functions.confirmQuery(theForm, sqlQuery);
    } else {
        alert(window.Messages.strFormEmpty);
    }

    if (window.codeMirrorEditor) {
        window.codeMirrorEditor.focus();
    } else if (codeMirrorInlineEditor) {
        codeMirrorInlineEditor.focus();
    }

    return result;
}

/**
 * Check if a form's element is empty.
 * An element containing only spaces is also considered empty
 *
 * @param {object} theForm      the form
 * @param {string} theFieldName the name of the form field to put the focus on
 *
 * @return {boolean} whether the form field is empty or not
 */
function emptyCheckTheField (theForm, theFieldName) {
    var theField = theForm.elements[theFieldName];
    var spaceRegExp = new RegExp('\\s+');

    return theField.value.replace(spaceRegExp, '') === '';
}

/**
 * Ensures a value submitted in a form is numeric and is in a range
 *
 * @param {object} theForm the form
 * @param {string} theFieldName the name of the form field to check
 * @param {any} message
 * @param {number} minimum the minimum authorized value
 * @param {number} maximum the maximum authorized value
 *
 * @return {boolean}  whether a valid number has been submitted or not
 */
function checkFormElementInRange (theForm, theFieldName, message, minimum = undefined, maximum = undefined) {
    var theField = theForm.elements[theFieldName];
    var val = parseInt(theField.value, 10);
    var min = 0;
    var max = Number.MAX_VALUE;

    if (typeof (minimum) !== 'undefined') {
        min = minimum;
    }

    if (typeof (maximum) !== 'undefined' && maximum !== null) {
        max = maximum;
    }

    if (isNaN(val)) {
        theField.select();
        alert(window.Messages.strEnterValidNumber);
        theField.focus();

        return false;
    } else if (val < min || val > max) {
        theField.select();
        alert(window.sprintf(message, val));
        theField.focus();

        return false;
    } else {
        theField.value = val;
    }

    return true;
}

function checkTableEditForm (theForm, fieldsCnt) {
    // TODO: avoid sending a message if user just wants to add a line
    // on the form but has not completed at least one field name

    var atLeastOneField = 0;
    var i;
    var elm;
    var elm2;
    var elm3;
    var val;
    var id;

    for (i = 0; i < fieldsCnt; i++) {
        id = '#field_' + i + '_2';
        elm = $(id);
        val = elm.val();
        if (val === 'VARCHAR' || val === 'CHAR' || val === 'BIT' || val === 'VARBINARY' || val === 'BINARY') {
            elm2 = $('#field_' + i + '_3');
            val = parseInt(elm2.val(), 10);
            elm3 = $('#field_' + i + '_1');
            if (isNaN(val) && elm3.val() !== '') {
                elm2.select();
                alert(window.Messages.strEnterValidLength);
                elm2.focus();

                return false;
            }
        }

        if (atLeastOneField === 0) {
            id = 'field_' + i + '_1';
            if (! Functions.emptyCheckTheField(theForm, id)) {
                atLeastOneField = 1;
            }
        }
    }

    if (atLeastOneField === 0) {
        var theField = theForm.elements.field_0_1;
        alert(window.Messages.strFormEmpty);
        theField.focus();

        return false;
    }

    // at least this section is under jQuery
    var $input = $('input.textfield[name=\'table\']');
    if ($input.val() === '') {
        alert(window.Messages.strFormEmpty);
        $input.trigger('focus');

        return false;
    }

    return true;
}

/**
 * True if last click is to check a row.
 * @type {boolean}
 */
let lastClickChecked = false;

/**
 * Zero-based index of last clicked row. Used to handle the shift + click event in the code above.
 * @type {number}
 */
let lastClickedRow = -1;

/**
 * Zero-based index of last shift clicked row.
 * @type {number}
 */
let lastShiftClickedRow = -1;

/** @type {number} */
let idleSecondsCounter = 0;
/** @type {number} */
let incInterval;
/** @type {number} */
let updateTimeout;

function teardownIdleEvent () {
    clearTimeout(updateTimeout);
    clearInterval(incInterval);
    $(document).off('mousemove');
}

function onloadIdleEvent () {
    document.onclick = function () {
        idleSecondsCounter = 0;
    };

    $(document).on('mousemove', function () {
        idleSecondsCounter = 0;
    });

    document.onkeypress = function () {
        idleSecondsCounter = 0;
    };

    function guid () {
        function s4 () {
            return Math.floor((1 + Math.random()) * 0x10000)
                .toString(16)
                .substring(1);
        }

        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    }

    function SetIdleTime () {
        idleSecondsCounter++;
    }

    function UpdateIdleTime () {
        var href = 'index.php?route=/';
        var guid = 'default';
        if (isStorageSupported('sessionStorage')) {
            guid = window.sessionStorage.guid;
        }

        var params = {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'db': CommonParams.get('db'),
            'guid': guid,
            'access_time': idleSecondsCounter,
            'check_timeout': 1
        };
        $.ajax({
            type: 'POST',
            url: href,
            data: params,
            success: function (data) {
                if (data.success) {
                    if (CommonParams.get('LoginCookieValidity') - idleSecondsCounter < 0) {
                        /* There is other active window, let's reset counter */
                        idleSecondsCounter = 0;
                    }

                    var remaining = Math.min(
                        /* Remaining login validity */
                        CommonParams.get('LoginCookieValidity') - idleSecondsCounter,
                        /* Remaining time till session GC */
                        CommonParams.get('session_gc_maxlifetime')
                    );
                    var interval = 1000;
                    if (remaining > 5) {
                        // max value for setInterval() function
                        interval = Math.min((remaining - 1) * 1000, Math.pow(2, 31) - 1);
                    }

                    updateTimeout = window.setTimeout(UpdateIdleTime, interval);
                } else { // timeout occurred
                    clearInterval(incInterval);
                    if (isStorageSupported('sessionStorage')) {
                        window.sessionStorage.clear();
                    }

                    // append the login form on the page, disable all the forms which were not disabled already, close all the open jqueryui modal boxes
                    if (! $('#modalOverlay').length) {
                        $('fieldset').not(':disabled').attr('disabled', 'disabled').addClass('disabled_for_expiration');
                        $('body').append(data.error);
                        $('.ui-dialog').each(function () {
                            $('#' + $(this).attr('aria-describedby')).dialog('close');
                        });

                        $('#input_username').trigger('focus');
                    } else {
                        Navigation.update(CommonParams.set('token', data.new_token));
                        $('input[name=token]').val(data.new_token);
                    }

                    idleSecondsCounter = 0;
                    handleRedirectAndReload(data);
                }
            }
        });
    }

    if (CommonParams.get('logged_in')) {
        incInterval = window.setInterval(SetIdleTime, 1000);
        var sessionTimeout = Math.min(
            CommonParams.get('LoginCookieValidity'),
            CommonParams.get('session_gc_maxlifetime')
        );
        if (isStorageSupported('sessionStorage')) {
            window.sessionStorage.setItem('guid', guid());
        }

        var interval = (sessionTimeout - 5) * 1000;
        if (interval > Math.pow(2, 31) - 1) { // max value for setInterval() function
            interval = Math.pow(2, 31) - 1;
        }

        updateTimeout = window.setTimeout(UpdateIdleTime, interval);
    }
}

/**
 * @return {function}
 */
function getCheckAllCheckboxEventHandler () {
    return function (e) {
        var $this = $(this);
        var $tr = $this.closest('tr');
        var $table = $this.closest('table');

        if (! e.shiftKey || lastClickedRow === -1) {
            // usual click

            var $checkbox = $tr.find(':checkbox.checkall');
            var checked = $this.prop('checked');
            $checkbox.prop('checked', checked).trigger('change');
            if (checked) {
                $tr.addClass('marked table-active');
            } else {
                $tr.removeClass('marked table-active');
            }

            lastClickChecked = checked;

            // remember the last clicked row
            lastClickedRow = lastClickChecked ? $table.find('tbody tr:not(.noclick)').index($tr) : -1;
            lastShiftClickedRow = -1;
        } else {
            // handle the shift click
            Functions.clearSelection();
            var start;
            var end;

            // clear last shift click result
            if (lastShiftClickedRow >= 0) {
                if (lastShiftClickedRow >= lastClickedRow) {
                    start = lastClickedRow;
                    end = lastShiftClickedRow;
                } else {
                    start = lastShiftClickedRow;
                    end = lastClickedRow;
                }

                $tr.parent().find('tr:not(.noclick)')
                    .slice(start, end + 1)
                    .removeClass('marked table-active')
                    .find(':checkbox')
                    .prop('checked', false)
                    .trigger('change');
            }

            // handle new shift click
            var currRow = $table.find('tbody tr:not(.noclick)').index($tr);
            if (currRow >= lastClickedRow) {
                start = lastClickedRow;
                end = currRow;
            } else {
                start = currRow;
                end = lastClickedRow;
            }

            $tr.parent().find('tr:not(.noclick)')
                .slice(start, end + 1)
                .addClass('marked table-active')
                .find(':checkbox')
                .prop('checked', true)
                .trigger('change');

            // remember the last shift clicked row
            lastShiftClickedRow = currRow;
        }
    };
}

/**
 * Checks/unchecks all options of a <select> element
 *
 * @param {string} theForm   the form name
 * @param {string} theSelect the element name
 * @param {boolean} doCheck  whether to check or to uncheck options
 *
 * @return {boolean} always true
 */
function setSelectOptions (theForm, theSelect, doCheck) {
    $('form[name=\'' + theForm + '\'] select[name=\'' + theSelect + '\']').find('option').prop('selected', doCheck);

    return true;
}


/**
 * Updates the input fields for the parameters based on the query
 */
function updateQueryParameters () {
    if (! $('#parameterized').is(':checked')) {
        $('#parametersDiv').empty();

        return;
    }

    var query = window.codeMirrorEditor ? window.codeMirrorEditor.getValue() : ($('#sqlquery').val() as string);

    var allParameters = query.match(/:[a-zA-Z0-9_]+/g);
    var parameters = [];
    // get unique parameters
    if (allParameters) {
        $.each(allParameters, function (i, parameter) {
            if ($.inArray(parameter, parameters) === -1) {
                parameters.push(parameter);
            }
        });
    } else {
        $('#parametersDiv').text(window.Messages.strNoParam);

        return;
    }

    var $temp = $('<div></div>');
    $temp.append($('#parametersDiv').children());
    $('#parametersDiv').empty();

    $.each(parameters, function (i, parameter) {
        var paramName = parameter.substring(1);
        var $param = $temp.find('#paramSpan_' + paramName);
        if (! $param.length) {
            $param = $('<span class="parameter" id="paramSpan_' + paramName + '"></span>');
            $('<label for="param_' + paramName + '"></label>').text(parameter).appendTo($param);
            $('<input type="text" name="parameters[' + parameter + ']" id="param_' + paramName + '">').appendTo($param);
        }

        $('#parametersDiv').append($param);
    });
}

/**
 * Get checkbox for foreign key checks
 *
 * @return {string}
 */
function getForeignKeyCheckboxLoader () {
    var html = '';
    html += '<div class="mt-1 mb-2">';
    html += '<div class="load-default-fk-check-value">';
    html += getImageTag('ajax_clock_small');
    html += '</div>';
    html += '</div>';

    return html;
}

function loadForeignKeyCheckbox () {
    // Load default foreign key check value
    var params = {
        'ajax_request': true,
        'server': CommonParams.get('server'),
    };
    $.get('index.php?route=/sql/get-default-fk-check-value', params, function (data) {
        var html = '<input type="hidden" name="fk_checks" value="0">' +
            '<input type="checkbox" name="fk_checks" id="fk_checks"' +
            (data.default_fk_check_value ? ' checked="checked"' : '') + '>' +
            '<label for="fk_checks">' + window.Messages.strForeignKeyCheck + '</label>';
        $('.load-default-fk-check-value').replaceWith(html);
    });
}

function teardownSqlQueryEditEvents () {
    $(document).off('click', 'a.inline_edit_sql');
    $(document).off('click', 'input#sql_query_edit_save');
    $(document).off('click', 'input#sql_query_edit_discard');
    if (window.codeMirrorEditor) {
        // @ts-ignore
        window.codeMirrorEditor.off('blur');
    } else {
        $(document).off('blur', '#sqlquery');
    }

    $(document).off('change', '#parameterized');
    $('#sqlquery').off('keydown');
    $('#sql_query_edit').off('keydown');

    if (codeMirrorInlineEditor) {
        // Copy the sql query to the text area to preserve it.
        $('#sql_query_edit').text(codeMirrorInlineEditor.getValue());
        $(codeMirrorInlineEditor.getWrapperElement()).off('keydown');
        codeMirrorInlineEditor.toTextArea();
        codeMirrorInlineEditor = null;
    }

    if (window.codeMirrorEditor) {
        $(window.codeMirrorEditor.getWrapperElement()).off('keydown');
    }
}

function onloadSqlQueryEditEvents () {
    // If we are coming back to the page by clicking forward button
    // of the browser, bind the code mirror to inline query editor.
    Functions.bindCodeMirrorToInlineEditor();
    $(document).on('click', 'a.inline_edit_sql', function () {
        if ($('#sql_query_edit').length) {
            // An inline query editor is already open,
            // we don't want another copy of it
            return false;
        }

        var $form = $('.result_query form');
        var sqlQuery = ($form.find('input[name=\'sql_query\']').val() as string).trim();
        var $innerSql = $('.result_query').find('code.sql');

        var newContent = '<textarea name="sql_query_edit" id="sql_query_edit">' + escapeHtml(sqlQuery) + '</textarea>\n';
        newContent += Functions.getForeignKeyCheckboxLoader();
        newContent += '<input type="submit" id="sql_query_edit_save" class="btn btn-secondary button btnSave" value="' + window.Messages.strGo + '">\n';
        newContent += '<input type="button" id="sql_query_edit_discard" class="btn btn-secondary button btnDiscard" value="' + window.Messages.strCancel + '">\n';
        var $editorArea = $('div#inline_editor');
        if ($editorArea.length === 0) {
            $editorArea = $('<div id="inline_editor_outer"></div>');
            $editorArea.insertBefore($innerSql);
        }

        $editorArea.html(newContent);
        Functions.loadForeignKeyCheckbox();
        $innerSql.hide();

        Functions.bindCodeMirrorToInlineEditor();

        return false;
    });

    $(document).on('click', 'input#sql_query_edit_save', function () {
        // hide already existing success message
        var sqlQuery;
        if (codeMirrorInlineEditor) {
            codeMirrorInlineEditor.save();
            sqlQuery = codeMirrorInlineEditor.getValue();
        } else {
            sqlQuery = $(this).parent().find('#sql_query_edit').val();
        }

        var fkCheck = $(this).parent().find('#fk_checks').is(':checked');

        var $form = $('a.inline_edit_sql').prev('form');
        var $fakeForm = $('<form>', { action: 'index.php?route=/import', method: 'post' })
            .append($form.find('input[name=server], input[name=db], input[name=table], input[name=token]').clone())
            .append($('<input>', { type: 'hidden', name: 'show_query', value: 1 }))
            .append($('<input>', { type: 'hidden', name: 'is_js_confirmed', value: 0 }))
            .append($('<input>', { type: 'hidden', name: 'sql_query', value: sqlQuery }))
            .append($('<input>', { type: 'hidden', name: 'fk_checks', value: fkCheck ? 1 : 0 }));
        if (! Functions.checkSqlQuery($fakeForm[0])) {
            return false;
        }

        $('.alert-success').hide();
        $fakeForm.appendTo($('body')).trigger('submit');
    });

    $(document).on('click', 'input#sql_query_edit_discard', function () {
        var $divEditor = $('div#inline_editor_outer');
        $divEditor.siblings('code.sql').show();
        $divEditor.remove();
    });

    $(document).on('change', '#parameterized', Functions.updateQueryParameters);

    var $inputUsername = $('#input_username');
    if ($inputUsername) {
        if ($inputUsername.val() === '') {
            $inputUsername.trigger('focus');
        } else {
            $('#input_password').trigger('focus');
        }
    }
}

/**
 * "inputRead" event handler for CodeMirror SQL query editors for autocompletion
 * @param instance
 */
function codeMirrorAutoCompleteOnInputRead (instance) {
    if (! sqlAutoCompleteInProgress
        && (! instance.options.hintOptions.tables || ! sqlAutoComplete)) {
        if (! sqlAutoComplete) {
            // Reset after teardown
            instance.options.hintOptions.tables = false;
            instance.options.hintOptions.defaultTable = '';

            sqlAutoCompleteInProgress = true;

            var params = {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'db': CommonParams.get('db'),
                'no_debug': true
            };

            var columnHintRender = function (elem, self, data) {
                $('<div class="autocomplete-column-name">')
                    .text(data.columnName)
                    .appendTo(elem);

                $('<div class="autocomplete-column-hint">')
                    .text(data.columnHint)
                    .appendTo(elem);
            };

            $.ajax({
                type: 'POST',
                url: 'index.php?route=/database/sql/autocomplete',
                data: params,
                success: function (data) {
                    if (data.success) {
                        var tables = data.tables;
                        sqlAutoCompleteDefaultTable = CommonParams.get('table');
                        sqlAutoComplete = [];
                        for (var table in tables) {
                            if (tables.hasOwnProperty(table)) {
                                var columns = tables[table];
                                // @ts-ignore
                                table = {
                                    text: table,
                                    columns: []
                                };

                                for (var column in columns) {
                                    if (columns.hasOwnProperty(column)) {
                                        var displayText = columns[column].Type;
                                        if (columns[column].Key === 'PRI') {
                                            displayText += ' | Primary';
                                        } else if (columns[column].Key === 'UNI') {
                                            displayText += ' | Unique';
                                        }

                                        // @ts-ignore
                                        table.columns.push({
                                            text: column,
                                            displayText: column + ' | ' + displayText,
                                            columnName: column,
                                            columnHint: displayText,
                                            render: columnHintRender
                                        });
                                    }
                                }
                            }

                            sqlAutoComplete.push(table);
                        }

                        instance.options.hintOptions.tables = sqlAutoComplete;
                        instance.options.hintOptions.defaultTable = sqlAutoCompleteDefaultTable;
                    }
                },
                complete: function () {
                    sqlAutoCompleteInProgress = false;
                }
            });
        } else {
            instance.options.hintOptions.tables = sqlAutoComplete;
            instance.options.hintOptions.defaultTable = sqlAutoCompleteDefaultTable;
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
        // @ts-ignore
        window.CodeMirror.commands.autocomplete(instance);
    }
}

function removeAutocompleteInfo () {
    sqlAutoComplete = false;
    sqlAutoCompleteDefaultTable = '';
}

/**
 * Binds the CodeMirror to the text area used to inline edit a query.
 */
function bindCodeMirrorToInlineEditor () {
    var $inlineEditor = $('#sql_query_edit');
    if ($inlineEditor.length === 0) {
        return;
    }

    if (typeof window.CodeMirror !== 'undefined') {
        var height = $inlineEditor.css('height');
        codeMirrorInlineEditor = Functions.getSqlEditor($inlineEditor);
        codeMirrorInlineEditor.getWrapperElement().style.height = height;
        codeMirrorInlineEditor.refresh();
        codeMirrorInlineEditor.focus();
        $(codeMirrorInlineEditor.getWrapperElement())
            .on('keydown', Functions.catchKeypressesFromSqlInlineEdit);
    } else {
        $inlineEditor
            .trigger('focus')
            .on('keydown', Functions.catchKeypressesFromSqlInlineEdit);
    }
}

function catchKeypressesFromSqlInlineEdit (event) {
    // ctrl-enter is 10 in chrome and ie, but 13 in ff
    if ((event.ctrlKey || event.metaKey) && (event.keyCode === 13 || event.keyCode === 10)) {
        $('#sql_query_edit_save').trigger('click');
    }
}

/**
 * Updates an element containing code.
 *
 * @param {JQuery} $base     base element which contains the raw and the
 *                           highlighted code.
 *
 * @param {string} htmlValue code in HTML format, displayed if code cannot be
 *                           highlighted
 *
 * @param {string} rawValue  raw code, used as a parameter for highlighter
 *
 * @return {boolean}        whether content was updated or not
 */
function updateCode ($base, htmlValue, rawValue) {
    var $code = $base.find('code');
    if ($code.length === 0) {
        return false;
    }

    // Determines the type of the content and appropriate CodeMirror mode.
    var type = '';
    var mode = '';
    if ($code.hasClass('json')) {
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
    if (typeof window.CodeMirror !== 'undefined') {
        var $highlighted = $('<div class="' + type + '-highlight cm-s-default"></div>');
        // @ts-ignore
        window.CodeMirror.runMode(rawValue, mode, $highlighted[0]);
        $notHighlighted.hide();
        $code.html('').append($notHighlighted, $highlighted[0]);
    } else {
        $code.html('').append($notHighlighted);
    }

    return true;
}

/**
 * Requests SQL for previewing before executing.
 *
 * @param {JQuery<HTMLElement>} $form Form containing query data
 */
function previewSql ($form): void {
    var formUrl = $form.attr('action');
    var sep = CommonParams.get('arg_separator');
    var formData = $form.serialize() +
        sep + 'do_save_data=1' +
        sep + 'preview_sql=1' +
        sep + 'ajax_request=1';
    var $messageBox = ajaxShowMessage();
    $.ajax({
        type: 'POST',
        url: formUrl,
        data: formData,
        success: function (response) {
            ajaxRemoveMessage($messageBox);
            if (response.success) {
                $('#previewSqlModal').modal('show');
                $('#previewSqlModal').find('.modal-body').first().html(response.sql_data);
                $('#previewSqlModalLabel').first().html(window.Messages.strPreviewSQL);
                $('#previewSqlModal').on('shown.bs.modal', function () {
                    highlightSql($('#previewSqlModal'));
                });
            } else {
                ajaxShowMessage(response.message);
            }
        },
        error: function () {
            ajaxShowMessage(window.Messages.strErrorProcessingRequest);
        }
    });
}

/**
 * Callback called when submit/"OK" is clicked on sql preview/confirm modal
 *
 * @callback onSubmitCallback
 * @param {string} url The url
 */

/**
 *
 * @param {string}           sqlData  Sql query to preview
 * @param {string}           url      Url to be sent to callback
 * @param {onSubmitCallback} callback On submit callback function
 */
function confirmPreviewSql (sqlData, url, callback): void {
    $('#previewSqlConfirmModal').modal('show');
    $('#previewSqlConfirmModalLabel').first().html(window.Messages.strPreviewSQL);
    $('#previewSqlConfirmCode').first().text(sqlData);
    $('#previewSqlConfirmModal').on('shown.bs.modal', function () {
        highlightSql($('#previewSqlConfirmModal'));
    });

    $('#previewSQLConfirmOkButton').on('click', function () {
        callback(url);
        $('#previewSqlConfirmModal').modal('hide');
    });
}

/**
 * check for reserved keyword column name
 *
 * @param {JQuery} $form Form
 *
 * @return {boolean}
 */
function checkReservedWordColumns ($form) {
    var isConfirmed = true;
    $.ajax({
        type: 'POST',
        url: 'index.php?route=/table/structure/reserved-word-check',
        data: $form.serialize(),
        success: function (data) {
            if (typeof data.success !== 'undefined' && data.success === true) {
                isConfirmed = window.confirm(data.message);
            }
        },
        async: false
    });

    return isConfirmed;
}

/**
 * Copy text to clipboard
 *
 * @param {string | number | string[]} text to copy to clipboard
 *
 * @return {boolean}
 */
function copyToClipboard (text) {
    var $temp = $('<input>');
    $temp.css({
        'position': 'fixed',
        'width': '2em',
        'border': 0,
        'top': 0,
        'left': 0,
        'padding': 0,
        'background': 'transparent'
    });

    $('body').append($temp);
    $temp.val(text).trigger('select');
    try {
        var res = document.execCommand('copy');
        $temp.remove();

        return res;
    } catch (e) {
        $temp.remove();

        return false;
    }
}

/**
 * @return {function}
 */
function dismissNotifications () {
    return function () {
        /**
         * Allows the user to dismiss a notification
         * created with ajaxShowMessage()
         */
        var holdStarter = null;
        $(document).on('mousedown', 'span.ajax_notification.dismissable', function () {
            holdStarter = setTimeout(function () {
                holdStarter = null;
            }, 250);
        });

        $(document).on('mouseup', 'span.ajax_notification.dismissable', function (event) {
            if (holdStarter && event.which === 1) {
                clearTimeout(holdStarter);
                ajaxRemoveMessage($(this));
            }
        });

        /**
         * The below two functions hide the "Dismiss notification" tooltip when a user
         * is hovering a link or button that is inside an ajax message
         */
        $(document).on('mouseover', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
            if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
                $(this).parents('span.ajax_notification').uiTooltip('disable');
            }
        });

        $(document).on('mouseout', 'span.ajax_notification a, span.ajax_notification button, span.ajax_notification input', function () {
            if ($(this).parents('span.ajax_notification').is(':data(tooltip)')) {
                $(this).parents('span.ajax_notification').uiTooltip('enable');
            }
        });

        $(document).on('click', 'a.copyQueryBtn', function (event) {
            event.preventDefault();
            var res = Functions.copyToClipboard($(this).attr('data-text'));
            if (res) {
                $(this).after('<span id=\'copyStatus\'> (' + window.Messages.strCopyQueryButtonSuccess + ')</span>');
            } else {
                $(this).after('<span id=\'copyStatus\'> (' + window.Messages.strCopyQueryButtonFailure + ')</span>');
            }

            setTimeout(function () {
                $('#copyStatus').remove();
            }, 2000);
        });
    };
}

/**
 * Hides/shows the "Open in ENUM/SET editor" message, depending on the data type of the column currently selected
 *
 * @param selectElement
 */
function showNoticeForEnum (selectElement) {
    var enumNoticeId = selectElement.attr('id').split('_')[1];
    enumNoticeId += '_' + (parseInt(selectElement.attr('id').split('_')[2], 10) + 1);
    var selectedType = selectElement.val();
    if (selectedType === 'ENUM' || selectedType === 'SET') {
        $('p#enum_notice_' + enumNoticeId).show();
    } else {
        $('p#enum_notice_' + enumNoticeId).hide();
    }
}

/**
 * Hides/shows a warning message when LENGTH is used with inappropriate integer type
 */
function showWarningForIntTypes () {
    if (! $('div#length_not_allowed').length) {
        return;
    }

    var lengthRestrictions = $('select.column_type option').map(function () {
        return $(this).filter(':selected').attr('data-length-restricted');
    }).get();

    var restricationFound = lengthRestrictions.some(restriction => Number(restriction) === 1);

    if (restricationFound) {
        $('div#length_not_allowed').show();
    } else {
        $('div#length_not_allowed').hide();
    }
}

/**
 * Formats a profiling duration nicely (in us and ms time).
 * Used in server/status/monitor.js
 *
 * @param {number} number   Number to be formatted, should be in the range of microsecond to second
 * @param {number} accuracy Accuracy, how many numbers right to the comma should be
 * @return {string}        The formatted number
 */
function prettyProfilingNum (number, accuracy) {
    var num = number;
    var acc = accuracy;
    if (! acc) {
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
 * @param {string} string Query to be formatted
 * @return {string}      The formatted query
 */
function sqlPrettyPrint (string) {
    if (typeof window.CodeMirror === 'undefined') {
        return string;
    }

    var mode = window.CodeMirror.getMode({}, 'text/x-mysql');
    var stream = new window.CodeMirror.StringStream(string);
    var state = mode.startState();
    var token;
    var tokens = [];
    var output = '';
    var tabs = function (cnt) {
        var ret = '';
        for (var i = 0; i < 4 * cnt; i++) {
            ret += ' ';
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
    var spaceExceptionsBefore = { ';': true, ',': true, '.': true, '(': true };
    // don't put spaces after these tokens
    var spaceExceptionsAfter = { '.': true };

    // Populate tokens array
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
    // If a new code block is found, newBlock contains its type for one iteration and vice versa for endBlock
    var newBlock;
    var endBlock;
    // How much to indent in the current line
    var indentLevel = 0;
    // Holds the "root-level" statements
    var statementPart;
    var lastStatementPart = statements[currentStatement][0];

    blockStack.unshift('statement');

    // Iterate through every token and format accordingly
    for (var i = 0; i < tokens.length; i++) {
        // New block => push to stack
        if (tokens[i][1] === '(') {
            if (i < tokens.length - 1 && tokens[i + 1][0] === 'statement-verb') {
                blockStack.unshift(newBlock = 'statement');
            } else if (i > 0 && tokens[i - 1][0] === 'builtin') {
                blockStack.unshift(newBlock = 'function');
            } else {
                blockStack.unshift(newBlock = 'generic');
            }
        } else {
            newBlock = null;
        }

        // Block end => pop from stack
        if (tokens[i][1] === ')') {
            endBlock = blockStack[0];
            blockStack.shift();
        } else {
            endBlock = null;
        }

        // A subquery is starting
        if (i > 0 && newBlock === 'statement') {
            indentLevel++;
            output += '\n' + tabs(indentLevel) + tokens[i][1] + ' ' + tokens[i + 1][1].toUpperCase() + '\n' + tabs(indentLevel + 1);
            currentStatement = tokens[i + 1][1];
            i++;
            continue;
        }

        // A subquery is ending
        if (endBlock === 'statement' && indentLevel > 0) {
            output += '\n' + tabs(indentLevel);
            indentLevel--;
        }

        // One less indentation for statement parts (from, where, order by, etc.) and a newline
        statementPart = statements[currentStatement].indexOf(tokens[i][1]);
        if (statementPart !== -1) {
            if (i > 0) {
                output += '\n';
            }

            output += tabs(indentLevel) + tokens[i][1].toUpperCase();
            output += '\n' + tabs(indentLevel + 1);
            lastStatementPart = tokens[i][1];
            // Normal indentation and spaces for everything else
        } else {
            if (! spaceExceptionsBefore[tokens[i][1]] &&
                ! (i > 0 && spaceExceptionsAfter[tokens[i - 1][1]]) &&
                output.charAt(output.length - 1) !== ' ') {
                output += ' ';
            }

            if (tokens[i][0] === 'keyword') {
                output += tokens[i][1].toUpperCase();
            } else {
                output += tokens[i][1];
            }
        }

        // split columns in select and 'update set' clauses, but only inside statements blocks
        if ((lastStatementPart === 'select' || lastStatementPart === 'where' || lastStatementPart === 'set') &&
            tokens[i][1] === ',' && blockStack[0] === 'statement') {
            output += '\n' + tabs(indentLevel + 1);
        }

        // split conditions in where clauses, but only inside statements blocks
        if (lastStatementPart === 'where' &&
            (tokens[i][1] === 'and' || tokens[i][1] === 'or' || tokens[i][1] === 'xor')) {
            if (blockStack[0] === 'statement') {
                output += '\n' + tabs(indentLevel + 1);
            }
            // Todo: Also split and or blocks in newlines & indentation++
            // if (blockStack[0] === 'generic')
            //   output += ...
        }
    }

    return output;
}

/**
 * jQuery function that uses jQueryUI's dialogs to confirm with user. Does not
 * return a jQuery object yet and hence cannot be chained
 *
 * @param {string}   question
 * @param {string}   url          URL to be passed to the callbackFn to make
 *                                an Ajax call to
 * @param {Function} callbackFn   callback to execute after user clicks on OK
 * @param {Function} openCallback optional callback to run when dialog is shown
 *
 * @return {boolean}
 */
function confirmDialog (question, url = undefined, callbackFn = undefined, openCallback = undefined) {
    var confirmState = CommonParams.get('confirm');
    if (! confirmState) {
        // user does not want to confirm
        if (typeof callbackFn === 'function') {
            callbackFn.call(this, url);

            return true;
        }
    }

    if (window.Messages.strDoYouReally === '') {
        return true;
    }

    const functionConfirmModal = $('#functionConfirmModal') as JQuery<HTMLDivElement>;
    functionConfirmModal.modal('show');
    functionConfirmModal.find('.modal-body').first().html(question);

    const functionConfirmOkButton = $('#functionConfirmOkButton') as JQuery<HTMLButtonElement>;
    functionConfirmOkButton.off('click');// Un-register previous modals
    functionConfirmOkButton.on('click', function () {
        functionConfirmModal.modal('hide');
        if (typeof callbackFn === 'function') {
            callbackFn.call(this, url);
        }
    });

    if (typeof openCallback === 'function') {
        openCallback();
    }
}

/**
 * jQuery function to sort a table's body after a new row has been appended to it.
 *
 * @param {string} textSelector string to select the sortKey's text
 *
 * @return {JQuery<HTMLElement>} for chaining purposes
 */
function sortTable (textSelector) {
    return this.each(function () {
        /**
         * @var table_body  Object referring to the table's <tbody> element
         */
        var tableBody = $(this);
        /**
         * @var rows    Object referring to the collection of rows in {@link tableBody}
         */
        var rows = $(this).find('tr').get();

        // get the text of the field that we will sort by
        $.each(rows, function (index, row) {
            // @ts-ignore
            row.sortKey = $(row).find(textSelector).text().toLowerCase().trim();
        });

        // get the sorted order
        rows.sort(function (a, b) {
            // @ts-ignore
            if (a.sortKey < b.sortKey) {
                return -1;
            }

            // @ts-ignore
            if (a.sortKey > b.sortKey) {
                return 1;
            }

            return 0;
        });

        // pull out each row from the table and then append it according to it's order
        $.each(rows, function (index, row) {
            $(tableBody).append(row);
            // @ts-ignore
            row.sortKey = null;
        });
    });
}

function teardownCreateTableEvents (): void {
    $(document).off('submit', 'form.create_table_form.ajax');
    $(document).off('click', 'form.create_table_form.ajax input[name=submit_num_fields]');
    $(document).off('keyup', 'form.create_table_form.ajax input');
    $(document).off('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]');
}

/**
 * Used on /database/operations, /database/structure and /database/tracking
 */
function onloadCreateTableEvents (): void {
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
         * Functions.checkTableEditForm() needs a pure element and not a jQuery object,
         * this is why we pass $form[0] as a parameter (the jQuery object
         * is actually an array of DOM elements)
         */

        if (Functions.checkTableEditForm($form[0], $form.find('input[name=orig_num_fields]').val())) {
            Functions.prepareForAjaxRequest($form);
            if (! Functions.checkReservedWordColumns($form)) {
                return;
            }

            ajaxShowMessage(window.Messages.strProcessingRequest);
            // User wants to submit the form
            $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
                if (typeof data === 'undefined' || data.success !== true) {
                    ajaxShowMessage(
                        '<div class="alert alert-danger" role="alert">' + data.error + '</div>',
                        false
                    );

                    return;
                }

                $('#properties_message')
                    .removeClass('alert-danger')
                    .html('');

                ajaxShowMessage(data.message);
                // Only if the create table dialog (distinct panel) exists
                var $createTableDialog = $('#create_table_dialog');
                if ($createTableDialog.length > 0) {
                    $createTableDialog.dialog('close').remove();
                }

                $('#tableslistcontainer').before(data.formatted_sql);

                /**
                 * @var tables_table    Object referring to the <tbody> element that holds the list of tables
                 */
                var tablesTable = $('#tablesForm').find('tbody').not('#tbl_summary_row');
                // this is the first table created in this db
                if (tablesTable.length === 0) {
                    refreshMainContent(CommonParams.get('opendb_url'));
                } else {
                    /**
                     * @var curr_last_row   Object referring to the last <tr> element in {@link tablesTable}
                     */
                    var currLastRow = $(tablesTable).find('tr').last();
                    /**
                     * @var curr_last_row_index_string   String containing the index of {@link currLastRow}
                     */
                    var currLastRowIndexString = $(currLastRow).find('input:checkbox').attr('id').match(/\d+/)[0];
                    /**
                     * @var curr_last_row_index Index of {@link currLastRow}
                     */
                    var currLastRowIndex = parseFloat(currLastRowIndexString);
                    /**
                     * @var new_last_row_index   Index of the new row to be appended to {@link tablesTable}
                     */
                    var newLastRowIndex = currLastRowIndex + 1;
                    /**
                     * @var new_last_row_id String containing the id of the row to be appended to {@link tablesTable}
                     */
                    var newLastRowId = 'checkbox_tbl_' + newLastRowIndex;

                    data.newTableString = data.newTableString.replace(/checkbox_tbl_/, newLastRowId);
                    // append to table
                    $(data.newTableString)
                        .appendTo(tablesTable);

                    // Sort the table
                    $(tablesTable).sortTable('th');

                    // Adjust summary row
                    window.DatabaseStructure.adjustTotals();
                }

                // Refresh navigation as a new table has been added
                Navigation.reload();
                // Redirect to table structure page on creation of new table
                var argsep = CommonParams.get('arg_separator');
                var params12 = 'ajax_request=true' + argsep + 'ajax_page_request=true';
                var tableStructureUrl = 'index.php?route=/table/structure' + argsep + 'server=' + data.params.server +
                    argsep + 'db=' + data.params.db + argsep + 'token=' + data.params.token +
                    argsep + 'goto=' + encodeURIComponent('index.php?route=/database/structure') + argsep + 'table=' + data.params.table + '';
                $.get(tableStructureUrl, params12, AJAX.responseHandler);
            }); // end $.post()
        }
    }); // end create table form (save)

    /**
     * Submits the intermediate changes in the table creation form
     * to refresh the UI accordingly
     *
     * @param actionParam
     */
    function submitChangesInCreateTableForm (actionParam) {
        /**
         * @var    the_form    object referring to the create table form
         */
        var $form = $('form.create_table_form.ajax');

        var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);

        // User wants to add more fields to the table
        $.post($form.attr('action'), $form.serialize() + '&' + actionParam, function (data) {
            if (typeof data === 'undefined' || ! data.success) {
                ajaxShowMessage(data.error);

                return;
            }

            var $pageContent = $('#page_content');
            $pageContent.html(data.message);
            highlightSql($pageContent);
            Functions.verifyColumnsProperties();
            Functions.hideShowConnection($('.create_table_form select[name=tbl_storage_engine]'));
            ajaxRemoveMessage($msgbox);
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
        if (event.keyCode !== 13) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        $(this)
            .closest('form')
            .find('input[name=submit_num_fields]')
            .trigger('click');
    });

    /**
     * Attach event handler to manage changes in number of partitions and subpartitions
     */
    $(document).on('change', 'input[name=partition_count],input[name=subpartition_count],select[name=partition_by]', function () {
        var $this = $(this);
        var $form = $this.parents('form');
        if ($form.is('.create_table_form.ajax')) {
            submitChangesInCreateTableForm('submit_partition_change=1');
        } else {
            $form.trigger('submit');
        }
    });

    $(document).on('change', 'input[value=AUTO_INCREMENT]', function () {
        if (! this.checked) {
            return;
        }

        var colRegEx = /\d/.exec($(this).attr('name'));
        const col = colRegEx[0];
        var $selectFieldKey = $('select[name="field_key[' + col + ']"]');
        if ($selectFieldKey.val() === 'none_' + col) {
            $selectFieldKey.val('primary_' + col).trigger('change', [false]);
        }
    });

    $('body')
        .off('click', 'input.preview_sql')
        .on('click', 'input.preview_sql', function () {
            var $form = $(this).closest('form');
            Functions.previewSql($form);
        });
}

/**
 * Validates the password field in a form
 *
 * @see    window.Messages.strPasswordEmpty
 * @see    window.Messages.strPasswordNotSame
 * @param {object} $theForm The form to be validated
 * @return {boolean}
 */
function checkPassword ($theForm) {
    // Did the user select 'no password'?
    if ($theForm.find('#nopass_1').is(':checked')) {
        return true;
    } else {
        var $pred = $theForm.find('#select_pred_password');
        if ($pred.length && ($pred.val() === 'none' || $pred.val() === 'keep')) {
            return true;
        }
    }

    var $password = $theForm.find('input[name=pma_pw]');
    var $passwordRepeat = $theForm.find('input[name=pma_pw2]');
    var alertMessage: string | boolean = false;

    if ($password.val() === '') {
        alertMessage = window.Messages.strPasswordEmpty;
    } else if ($password.val() !== $passwordRepeat.val()) {
        alertMessage = window.Messages.strPasswordNotSame;
    }

    if (alertMessage) {
        alert(alertMessage);
        $password.val('');
        $passwordRepeat.val('');
        $password.trigger('focus');

        return false;
    }

    return true;
}

function onloadChangePasswordEvents (): void {
    /* Handler for hostname type */
    $(document).on('change', '#select_pred_hostname', function () {
        var hostname = $('#pma_hostname');
        if (this.value === 'any') {
            hostname.val('%');
        } else if (this.value === 'localhost') {
            hostname.val('localhost');
        } else if (this.value === 'thishost' && $(this).data('thishost')) {
            hostname.val($(this).data('thishost'));
        } else if (this.value === 'hosttable') {
            hostname.val('').prop('required', false);
        } else if (this.value === 'userdefined') {
            hostname.trigger('focus').select().prop('required', true);
        }
    });

    /* Handler for editing hostname */
    $(document).on('change', '#pma_hostname', function () {
        $('#select_pred_hostname').val('userdefined');
        $('#pma_hostname').prop('required', true);
    });

    /* Handler for username type */
    $(document).on('change', '#select_pred_username', function () {
        if (this.value === 'any') {
            $('#pma_username').val('').prop('required', false);
            $('#user_exists_warning').css('display', 'none');
        } else if (this.value === 'userdefined') {
            $('#pma_username').trigger('focus').trigger('select').prop('required', true);
        }
    });

    /* Handler for editing username */
    $(document).on('change', '#pma_username', function () {
        $('#select_pred_username').val('userdefined');
        $('#pma_username').prop('required', true);
    });

    /* Handler for password type */
    $(document).on('change', '#select_pred_password', function () {
        if (this.value === 'none') {
            $('#text_pma_pw2').prop('required', false).val('');
            $('#text_pma_pw').prop('required', false).val('');
        } else if (this.value === 'userdefined') {
            $('#text_pma_pw2').prop('required', true);
            $('#text_pma_pw').prop('required', true).trigger('focus').trigger('select');
        } else {
            $('#text_pma_pw2').prop('required', false);
            $('#text_pma_pw').prop('required', false);
        }
    });

    /* Handler for editing password */
    $(document).on('change', '#text_pma_pw,#text_pma_pw2', function () {
        $('#select_pred_password').val('userdefined');
        $('#text_pma_pw2').prop('required', true);
        $('#text_pma_pw').prop('required', true);
    });

    /**
     * Unbind all event handlers before tearing down a page
     */
    $(document).off('click', '#change_password_anchor.ajax');

    /**
     * Attach Ajax event handler on the change password anchor
     */

    $(document).on('click', '#change_password_anchor.ajax', function (event) {
        event.preventDefault();

        var $msgbox = ajaxShowMessage();

        $('#changePasswordGoButton').on('click', function () {
            event.preventDefault();

            /**
             * @var $the_form    Object referring to the change password form
             */
            var $theForm = $('#change_password_form');

            if (! Functions.checkPassword($theForm)) {
                return false;
            }

            /**
             * @var {string} thisValue String containing the value of the submit button.
             * Need to append this for the change password form on Server Privileges
             * page to work
             */
            var thisValue = $(this).val();

            var $msgbox = ajaxShowMessage(window.Messages.strProcessingRequest);
            $theForm.append('<input type="hidden" name="ajax_request" value="true">');

            $.post($theForm.attr('action'), $theForm.serialize() + CommonParams.get('arg_separator') + 'change_pw=' + thisValue, function (data) {
                if (typeof data === 'undefined' || data.success !== true) {
                    ajaxShowMessage(data.error, false);

                    return;
                }

                var $pageContent = $('#page_content');
                $pageContent.prepend(data.message);
                highlightSql($pageContent);
                $('#change_password_dialog').hide().remove();
                $('#edit_user_dialog').dialog('close').remove();
                ajaxRemoveMessage($msgbox);
            }); // end $.post()

            $('#changePasswordModal').modal('hide');
        });

        $.get($(this).attr('href'), { 'ajax_request': true }, function (data) {
            if (typeof data === 'undefined' || ! data.success) {
                ajaxShowMessage(data.error, false);

                return;
            }

            if (data.scripts) {
                AJAX.scriptHandler.load(data.scripts);
            }

            // for this dialog, we remove the fieldset wrapping due to double headings
            $('#changePasswordModal').modal('show');
            $('#changePasswordModal').find('.modal-body').first().html(data.message);
            $('fieldset#fieldset_change_password')
                .find('legend').remove().end()
                .find('table.table').unwrap().addClass('m-3')
                .find('input#text_pma_pw').trigger('focus');

            $('#fieldset_change_password_footer').hide();
            ajaxRemoveMessage($msgbox);
            Functions.displayPasswordGenerateButton();
            $('#change_password_form').on('submit', function (e) {
                e.preventDefault();
                $(this)
                    .closest('.ui-dialog')
                    .find('.ui-dialog-buttonpane .ui-button')
                    .first()
                    .trigger('click');
            });
        }); // end $.get()
    });
}

function teardownEnumSetEditorMessage (): void {
    $(document).off('change', 'select.column_type');
    $(document).off('change', 'select.default_type');
    $(document).off('change', 'select.virtuality');
    $(document).off('change', 'input.allow_null');
    $(document).off('change', '.create_table_form select[name=tbl_storage_engine]');
}

/**
 * Toggle the hiding/showing of the "Open in ENUM/SET editor" message when
 * the page loads and when the selected data type changes
 */
function onloadEnumSetEditorMessage (): void {
    // is called here for normal page loads and also when opening
    // the Create table dialog
    Functions.verifyColumnsProperties();
    //
    // needs on() to work also in the Create Table dialog
    $(document).on('change', 'select.column_type', function () {
        Functions.showNoticeForEnum($(this));
        Functions.showWarningForIntTypes();
    });

    $(document).on('change', 'select.default_type', function () {
        Functions.hideShowDefaultValue($(this));
    });

    $(document).on('change', 'select.virtuality', function () {
        Functions.hideShowExpression($(this));
    });

    $(document).on('change', 'input.allow_null', function () {
        Functions.validateDefaultValue($(this));
    });

    $(document).on('change', '.create_table_form select[name=tbl_storage_engine]', function () {
        Functions.hideShowConnection($(this));
    });
}

/**
 * If the chosen storage engine is FEDERATED show connection field. Hide otherwise
 *
 * @param $engineSelector storage engine selector
 */
function hideShowConnection ($engineSelector) {
    var $connection = $('.create_table_form input[name=connection]');
    var $labelTh = $('.create_table_form #storage-engine-connection');
    if ($engineSelector.val() !== 'FEDERATED') {
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
 *
 * @param $nullCheckbox
 */
function validateDefaultValue ($nullCheckbox) {
    if (! $nullCheckbox.prop('checked')) {
        var $default = $nullCheckbox.closest('tr').find('.default_type');
        if ($default.val() === 'NULL') {
            $default.val('NONE');
        }
    }
}

/**
 * function to populate the input fields on picking a column from central list
 *
 * @param {string} inputId input id of the name field for the column to be populated
 * @param {number} offset of the selected column in central list of columns
 */
function autoPopulate (inputId, offset) {
    var db = CommonParams.get('db');
    var table = CommonParams.get('table');
    var newInputId = inputId.substring(0, inputId.length - 1);
    $('#' + newInputId + '1').val(window.centralColumnList[db + '_' + table][offset].col_name);
    var colType = window.centralColumnList[db + '_' + table][offset].col_type.toUpperCase();
    $('#' + newInputId + '2').val(colType);
    var $input3 = $('#' + newInputId + '3');
    $input3.val(window.centralColumnList[db + '_' + table][offset].col_length);
    if (colType === 'ENUM' || colType === 'SET') {
        $input3.next().show();
    } else {
        $input3.next().hide();
    }

    var colDefault = window.centralColumnList[db + '_' + table][offset].col_default.toUpperCase();
    var $input4 = $('#' + newInputId + '4');
    if (colDefault === 'NULL' || colDefault === 'CURRENT_TIMESTAMP' || colDefault === 'CURRENT_TIMESTAMP()') {
        if (colDefault === 'CURRENT_TIMESTAMP()') {
            colDefault = 'CURRENT_TIMESTAMP';
        }

        $input4.val(colDefault);
        $input4.siblings('.default_value').hide();
    }

    if (colDefault === '') {
        $input4.val('NONE');
        $input4.siblings('.default_value').hide();
    } else {
        $input4.val('USER_DEFINED');
        $input4.siblings('.default_value').show();
        $input4.siblings('.default_value').val(window.centralColumnList[db + '_' + table][offset].col_default);
    }

    $('#' + newInputId + '5').val(window.centralColumnList[db + '_' + table][offset].col_collation);
    var $input6 = $('#' + newInputId + '6');
    $input6.val(window.centralColumnList[db + '_' + table][offset].col_attribute);
    if (window.centralColumnList[db + '_' + table][offset].col_extra === 'on update CURRENT_TIMESTAMP') {
        $input6.val(window.centralColumnList[db + '_' + table][offset].col_extra);
    }

    if (window.centralColumnList[db + '_' + table][offset].col_extra.toUpperCase() === 'AUTO_INCREMENT') {
        $('#' + newInputId + '9').prop('checked', true).trigger('change');
    } else {
        $('#' + newInputId + '9').prop('checked', false);
    }

    if (window.centralColumnList[db + '_' + table][offset].col_isNull !== '0') {
        $('#' + newInputId + '7').prop('checked', true);
    } else {
        $('#' + newInputId + '7').prop('checked', false);
    }
}

function teardownEnumSetEditor (): void {
    $(document).off('click', 'a.open_enum_editor');
    $(document).off('click', 'input.add_value');
    $(document).off('click', '#enum_editor td.drop');
    $(document).off('click', 'a.central_columns_dialog');
}

/**
 * Opens the ENUM/SET editor and controls its functions
 */
function onloadEnumSetEditor (): void {
    $(document).on('click', 'a.open_enum_editor', function () {
        // Get the name of the column that is being edited
        var colname = ($(this).closest('tr').find('input').first().val() as string);
        var title;
        var i;
        // And use it to make up a title for the page
        if (colname.length < 1) {
            title = window.Messages.enum_newColumnVals;
        } else {
            title = window.Messages.enum_columnVals.replace(
                /%s/,
                '"' + escapeHtml(decodeURIComponent(colname)) + '"'
            );
        }

        // Get the values as a string
        var inputstring = ($(this)
            .closest('td')
            .find('input')
            .val() as string);
        // Escape html entities
        inputstring = $('<div></div>')
            .text(inputstring)
            .html();

        // Parse the values, escaping quotes and
        // slashes on the fly, into an array
        var values = [];
        var inString = false;
        var curr;
        var next;
        var buffer = '';
        for (i = 0; i < inputstring.length; i++) {
            curr = inputstring.charAt(i);
            next = i === inputstring.length ? '' : inputstring.charAt(i + 1);
            if (! inString && curr === '\'') {
                inString = true;
            } else if (inString && curr === '\\' && next === '\\') {
                buffer += '&#92;';
                i++;
            } else if (inString && next === '\'' && (curr === '\'' || curr === '\\')) {
                buffer += '&#39;';
                i++;
            } else if (inString && curr === '\'') {
                inString = false;
                values.push(buffer);
                buffer = '';
            } else if (inString) {
                buffer += curr;
            }
        }

        if (buffer.length > 0) {
            // The leftovers in the buffer are the last value (if any)
            values.push(buffer);
        }

        var fields = '';
        // If there are no values, maybe the user is about to make a
        // new list so we add a few for them to get started with.
        if (values.length === 0) {
            values.push('', '', '', '');
        }

        // Add the parsed values to the editor
        var dropIcon = getImageTag('b_drop');
        for (i = 0; i < values.length; i++) {
            fields += '<tr><td>' +
                '<input type=\'text\' value=\'' + values[i] + '\'>' +
                '</td><td class=\'drop\'>' +
                dropIcon +
                '</td></tr>';
        }

        /**
         * @var dialog HTML code for the ENUM/SET dialog
         */
        var dialog = '<div id=\'enum_editor\'>' +
            '<fieldset class="pma-fieldset">' +
            '<legend>' + title + '</legend>' +
            '<p>' + getImageTag('s_notice') +
            window.Messages.enum_hint + '</p>' +
            '<table class="table table-borderless values">' + fields + '</table>' +
            '</fieldset><fieldset class="pma-fieldset tblFooters">' +
            '<table class="table table-borderless add"><tr><td>' +
            '<div class=\'slider\'></div>' +
            '</td><td>' +
            '<form><div><input type=\'submit\' class=\'add_value btn btn-primary\' value=\'' +
            window.sprintf(window.Messages.enum_addValue, 1) +
            '\'></div></form>' +
            '</td></tr></table>' +
            '<input type=\'hidden\' value=\'' + // So we know which column's data is being edited
            $(this).closest('td').find('input').attr('id') +
            '\'>' +
            '</fieldset>' +
            '</div>';
        $('#enumEditorGoButton').on('click', function () {
            // When the submit button is clicked,
            // put the data back into the original form
            var valueArray = [];
            ($('#enumEditorModal').find('.values input') as JQuery<HTMLInputElement>).each(function (index, elm) {
                var val = elm.value.replace(/\\/g, '\\\\').replace(/'/g, '\'\'');
                valueArray.push('\'' + val + '\'');
            });

            // get the Length/Values text field where this value belongs
            var valuesId = $('#enumEditorModal').find('input[type=\'hidden\']').val();
            $('input#' + valuesId).val(valueArray.join(','));
        });

        // Show the dialog
        var width = parseInt(
            ((parseInt($('html').css('font-size'), 10) / 13) * 340).toString(),
            10
        );
        if (! width) {
            width = 340;
        }

        $('#enumEditorModal').modal('show');
        $('#enumEditorModal').find('.modal-body').first().html(dialog);
        // slider for choosing how many fields to add
        $('#enumEditorModal').find('.slider').slider({
            animate: true,
            range: 'min',
            value: 1,
            min: 1,
            max: 9,
            slide: function (event, ui) {
                $(this).closest('table').find('input[type=submit]').val(
                    window.sprintf(window.Messages.enum_addValue, ui.value)
                );
            }
        });

        // Focus the slider, otherwise it looks nearly transparent
        $('a.ui-slider-handle').addClass('ui-state-focus');

        return false;
    });

    $(document).on('click', 'a.central_columns_dialog', function () {
        var href = 'index.php?route=/database/central-columns';
        var db = CommonParams.get('db');
        var table = CommonParams.get('table');
        var maxRows = $(this).data('maxrows');
        var pick = $(this).data('pick');
        if (pick !== false) {
            pick = true;
        }

        var params = {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'db': CommonParams.get('db'),
            'cur_table': CommonParams.get('table'),
            'getColumnList': true
        };
        var colid = $(this).closest('td').find('input').attr('id');
        var fields = '';
        if (! (db + '_' + table in window.centralColumnList)) {
            window.centralColumnList.push(db + '_' + table);
            $.ajax({
                type: 'POST',
                url: href,
                data: params,
                success: function (data) {
                    window.centralColumnList[db + '_' + table] = data.message;
                },
                async: false
            });
        }

        var i = 0;
        var listSize = window.centralColumnList[db + '_' + table].length;
        var min = (listSize <= maxRows) ? listSize : maxRows;
        for (i = 0; i < min; i++) {
            fields += '<tr><td><div><span class="fw-bold">' +
                escapeHtml(window.centralColumnList[db + '_' + table][i].col_name) +
                '</span><br><span class="color_gray">' + window.centralColumnList[db + '_' + table][i].col_type;

            if (window.centralColumnList[db + '_' + table][i].col_attribute !== '') {
                fields += '(' + escapeHtml(window.centralColumnList[db + '_' + table][i].col_attribute) + ') ';
            }

            if (window.centralColumnList[db + '_' + table][i].col_length !== '') {
                fields += '(' + escapeHtml(window.centralColumnList[db + '_' + table][i].col_length) + ') ';
            }

            fields += escapeHtml(window.centralColumnList[db + '_' + table][i].col_extra) + '</span>' +
                '</div></td>';

            if (pick) {
                fields += '<td><input class="btn btn-secondary pick w-100" type="submit" value="' +
                    window.Messages.pickColumn + '" onclick="Functions.autoPopulate(\'' + colid + '\',' + i + ')"></td>';
            }

            fields += '</tr>';
        }

        var resultPointer = i;
        var searchIn = '<input type="text" class="filter_rows" placeholder="' + window.Messages.searchList + '">';
        if (fields === '') {
            fields = window.sprintf(window.Messages.strEmptyCentralList, '\'' + escapeHtml(db) + '\'');
            searchIn = '';
        }

        var seeMore = '';
        if (listSize > maxRows) {
            seeMore = '<fieldset class="pma-fieldset tblFooters text-center fw-bold">' +
                '<a href=\'#\' id=\'seeMore\'>' + window.Messages.seeMore + '</a></fieldset>';
        }

        var centralColumnsDialog = '<div class=\'max_height_400\'>' +
            '<fieldset class="pma-fieldset">' +
            searchIn +
            '<table id="col_list" class="table table-borderless values">' + fields + '</table>' +
            '</fieldset>' +
            seeMore +
            '</div>';

        var width = parseInt(
            ((parseInt($('html').css('font-size'), 10) / 13) * 500).toString(),
            10
        );
        if (! width) {
            width = 500;
        }

        var buttonOptions = {};
        var $centralColumnsDialog = $(centralColumnsDialog).dialog({
            classes: {
                'ui-dialog-titlebar-close': 'btn-close'
            },
            minWidth: width,
            maxHeight: 450,
            modal: true,
            title: window.Messages.pickColumnTitle,
            buttons: buttonOptions,
            open: function () {
                $('#col_list').on('click', '.pick', function () {
                    $centralColumnsDialog.remove();
                });

                $('.filter_rows').on('keyup', function () {
                    $.uiTableFilter($('#col_list'), $(this).val());
                });

                $('#seeMore').on('click', function () {
                    fields = '';
                    min = (listSize <= maxRows + resultPointer) ? listSize : maxRows + resultPointer;
                    for (i = resultPointer; i < min; i++) {
                        fields += '<tr><td><div><span class="fw-bold">' +
                            window.centralColumnList[db + '_' + table][i].col_name +
                            '</span><br><span class="color_gray">' +
                            window.centralColumnList[db + '_' + table][i].col_type;

                        if (window.centralColumnList[db + '_' + table][i].col_attribute !== '') {
                            fields += '(' + window.centralColumnList[db + '_' + table][i].col_attribute + ') ';
                        }

                        if (window.centralColumnList[db + '_' + table][i].col_length !== '') {
                            fields += '(' + window.centralColumnList[db + '_' + table][i].col_length + ') ';
                        }

                        fields += window.centralColumnList[db + '_' + table][i].col_extra + '</span>' +
                            '</div></td>';

                        if (pick) {
                            fields += '<td><input class="btn btn-secondary pick w-100" type="submit" value="' +
                                window.Messages.pickColumn + '" onclick="Functions.autoPopulate(\'' + colid + '\',' + i + ')"></td>';
                        }

                        fields += '</tr>';
                    }

                    $('#col_list').append(fields);
                    resultPointer = i;
                    if (resultPointer === listSize) {
                        $('#seeMore').hide();
                    }

                    return false;
                });

                $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button').first().trigger('focus');
            },
            close: function () {
                $('#col_list').off('click', '.pick');
                $('.filter_rows').off('keyup');
                $(this).remove();
            }
        });

        return false;
    });

    // When "add a new value" is clicked, append an empty text field
    $(document).on('click', 'input.add_value', function (e) {
        e.preventDefault();
        var numNewRows = $('#enumEditorModal').find('div.slider').slider('value');
        while (numNewRows--) {
            $('#enumEditorModal').find('.values')
                .append(
                    '<tr class=\'hide\'><td>' +
                    '<input type=\'text\'>' +
                    '</td><td class=\'drop\'>' +
                    getImageTag('b_drop') +
                    '</td></tr>'
                )
                .find('tr').last()
                .show('fast');
        }
    });

    // Removes the specified row from the enum editor
    $(document).on('click', '#enum_editor td.drop', function () {
        $(this).closest('tr').hide('fast', function () {
            $(this).remove();
        });
    });
}

/**
 * Handler for adding more columns to an index in the editor
 * @return {function}
 */
function getAddIndexEventHandler () {
    return function (event) {
        event.preventDefault();
        var hadAddButtonHidden = $(this).closest('fieldset').find('.add_fields').hasClass('hide');
        if (hadAddButtonHidden === false) {
            var rowsToAdd = $(this)
                .closest('fieldset')
                .find('.slider')
                .slider('value');

            var tempEmptyVal = function () {
                $(this).val('');
            };

            var tempSetFocus = function () {
                if ($(this).find('option:selected').val() === '') {
                    return true;
                }

                $(this).closest('tr').find('input').trigger('focus');
            };

            while (rowsToAdd--) {
                var $indexColumns = $('#index_columns');
                var $newrow = $indexColumns
                    .find('tbody > tr').first()
                    .clone()
                    .appendTo(
                        $indexColumns.find('tbody')
                    );
                $newrow.find(':input').each(tempEmptyVal);
                // focus index size input on column picked
                $newrow.find('select').on('change', tempSetFocus);
            }
        }
    };
}

function indexDialogModal (routeUrl, url, title, callbackSuccess, callbackFailure = undefined) {
    /* Remove the hidden dialogs if there are*/
    var modal = $('#indexDialogModal');

    const indexDialogPreviewModal = document.getElementById('indexDialogPreviewModal');
    indexDialogPreviewModal.addEventListener('shown.bs.modal', () => {
        const modalBody = indexDialogPreviewModal.querySelector('.modal-body');
        const $form = $('#index_frm');
        const formUrl = $form.attr('action');
        const sep = CommonParams.get('arg_separator');
        const formData = $form.serialize() +
            sep + 'do_save_data=1' +
            sep + 'preview_sql=1' +
            sep + 'ajax_request=1';
        $.post({
            url: formUrl,
            data: formData,
            success: response => {
                if (! response.success) {
                    modalBody.innerHTML = '<div class="alert alert-danger" role="alert">' + window.Messages.strErrorProcessingRequest + '</div>';

                    return;
                }

                modalBody.innerHTML = response.sql_data;
                highlightSql($('#indexDialogPreviewModal'));
            },
            error: () => {
                modalBody.innerHTML = '<div class="alert alert-danger" role="alert">' + window.Messages.strErrorProcessingRequest + '</div>';
            }
        });
    });

    indexDialogPreviewModal.addEventListener('hidden.bs.modal', () => {
        indexDialogPreviewModal.querySelector('.modal-body').innerHTML = '<div class="spinner-border" role="status">' +
            '<span class="visually-hidden">' + window.Messages.strLoading + '</span></div>';
    });

    // Remove previous click listeners from other modal openings (issue: #17892)
    $('#indexDialogModalGoButton').off('click');
    $('#indexDialogModalGoButton').on('click', function () {
        /**
         * @var the_form object referring to the export form
         */
        var $form = $('#index_frm');
        ajaxShowMessage(window.Messages.strProcessingRequest);
        Functions.prepareForAjaxRequest($form);
        // User wants to submit the form
        $.post($form.attr('action'), $form.serialize() + CommonParams.get('arg_separator') + 'do_save_data=1', function (data) {
            var $sqlqueryresults = $('.sqlqueryresults');
            if ($sqlqueryresults.length !== 0) {
                $sqlqueryresults.remove();
            }

            if (typeof data !== 'undefined' && data.success === true) {
                ajaxShowMessage(data.message);
                highlightSql($('.result_query'));
                $('.result_query .alert').remove();
                /* Reload the field form*/
                $('#table_index').remove();
                $('<div id=\'temp_div\'><div>')
                    .append(data.index_table)
                    .find('#table_index')
                    .insertAfter('#index_header');

                var $editIndexDialog = $('#indexDialogModal');
                if ($editIndexDialog.length > 0) {
                    $editIndexDialog.modal('hide');
                }

                $('div.no_indexes_defined').hide();
                if (callbackSuccess) {
                    callbackSuccess(data);
                }

                Navigation.reload();
            } else {
                var $tempDiv = $('<div id=\'temp_div\'><div>').append(data.error);
                var $error;
                if ($tempDiv.find('.error code').length !== 0) {
                    $error = $tempDiv.find('.error code').addClass('error');
                } else {
                    $error = $tempDiv;
                }

                if (callbackFailure) {
                    callbackFailure();
                }

                ajaxShowMessage($error, false);
            }
        }); // end $.post()
    });

    var $msgbox = ajaxShowMessage();
    $.post(routeUrl, url, function (data) {
        if (typeof data !== 'undefined' && data.success === false) {
            // in the case of an error, show the error message returned.
            ajaxShowMessage(data.error, false);

            return;
        }

        ajaxRemoveMessage($msgbox);
        // Show dialog if the request was successful
        modal.modal('show');
        // FIXME data may be undefiend
        modal.find('.modal-body').first().html(data.message);
        $('#indexDialogModalLabel').first().text(title);
        Functions.verifyColumnsProperties();
        modal.find('.tblFooters').remove();
        Functions.showIndexEditDialog(modal);
    }); // end $.get()
}

function indexEditorDialog (url, title, callbackSuccess, callbackFailure = undefined) {
    Functions.indexDialogModal('index.php?route=/table/indexes', url, title, callbackSuccess, callbackFailure);
}

function indexRenameDialog (url, title, callbackSuccess, callbackFailure = undefined) {
    Functions.indexDialogModal('index.php?route=/table/indexes/rename', url, title, callbackSuccess, callbackFailure);
}

function showIndexEditDialog ($outer) {
    checkIndexType();
    checkIndexName('index_frm');
    var $indexColumns = $('#index_columns');
    $indexColumns.find('td').each(function () {
        $(this).css('width', $(this).width() + 'px');
    });

    $indexColumns.find('tbody').sortable({
        axis: 'y',
        containment: $indexColumns.find('tbody'),
        tolerance: 'pointer'
    });

    Functions.showHints($outer);
    // Add a slider for selecting how many columns to add to the index
    $outer.find('.slider').slider({
        animate: true,
        value: 1,
        min: 1,
        max: 16,
        slide: function (event, ui) {
            $(this).closest('fieldset').find('input[type=submit]').val(
                window.sprintf(window.Messages.strAddToIndex, ui.value)
            );
        }
    });

    $('div.add_fields').removeClass('hide');
    // focus index size input on column picked
    $outer.find('table#index_columns select').on('change', function () {
        if ($(this).find('option:selected').val() === '') {
            return true;
        }

        $(this).closest('tr').find('input').trigger('focus');
    });

    // Focus the slider, otherwise it looks nearly transparent
    $('a.ui-slider-handle').addClass('ui-state-focus');
    // set focus on index name input, if empty
    var input = $outer.find('input#input_index_name');
    if (! input.val()) {
        input.trigger('focus');
    }
}

/**
 * Function to display tooltips that were
 * generated on the PHP side by PhpMyAdmin\Util::showHint()
 *
 * @param {object} $div a div jquery object which specifies the
 *                    domain for searching for tooltips. If we
 *                    omit this parameter the function searches
 *                    in the whole body
 **/
function showHints ($div: JQuery<HTMLElement> | undefined = undefined) {
    var $newDiv = $div;
    if ($newDiv === undefined || ! ($newDiv instanceof $) || $newDiv.length === 0) {
        $newDiv = $('body');
    }

    $newDiv.find('.pma_hint').each(function () {
        tooltip($(this).children('img'), 'img', $(this).children('span').html());
    });
}

/**
 * @return {function}
 */
function initializeMenuResizer () {
    return function () {
        // Initialise the menu resize plugin
        $('#topmenu').menuResizer(mainMenuResizerCallback);
        // register resize event
        $(window).on('resize', function () {
            $('#topmenu').menuResizer('resize');
        });
    };
}

/**
 * var  toggleButton  This is a function that creates a toggle
 *                    sliding button given a jQuery reference
 *                    to the correct DOM element
 *
 * @param $obj
 */
function toggleButton ($obj) {
    // In rtl mode the toggle switch is flipped horizontally
    // so we need to take that into account
    var right;
    if ($('span.text_direction', $obj).text() === 'ltr') {
        right = 'right';
    } else {
        right = 'left';
    }

    /**
     * @var  h  Height of the button, used to scale the
     *          background image and position the layers
     */
    var h = $obj.height();
    $('img', $obj).height(h);
    $('table', $obj).css('bottom', h - 1);
    /**
     * @var  on   Width of the "ON" part of the toggle switch
     * @var  off  Width of the "OFF" part of the toggle switch
     */
    var on = $('td.toggleOn', $obj).width();
    var off = $('td.toggleOff', $obj).width();
    // Make the "ON" and "OFF" parts of the switch the same size
    // + 2 pixels to avoid overflowed
    $('td.toggleOn > div', $obj).width(Math.max(on, off) + 2);
    $('td.toggleOff > div', $obj).width(Math.max(on, off) + 2);
    /**
     *  @var  w  Width of the central part of the switch
     */
    var w = parseInt((($('img', $obj).height() / 16) * 22).toString(), 10);
    // Resize the central part of the switch on the top
    // layer to match the background
    $($obj).find('table td').eq(1).children('div').width(w);
    /**
     * @var  imgw    Width of the background image
     * @var  tblw    Width of the foreground layer
     * @var  offset  By how many pixels to move the background
     *               image, so that it matches the top layer
     */
    var imgw = $('img', $obj).width();
    var tblw = $('table', $obj).width();
    var offset = parseInt((((imgw - tblw) / 2).toString()), 10);
    // Move the background to match the layout of the top layer
    $obj.find('img').css(right, offset);
    /**
     * @var  offw    Outer width of the "ON" part of the toggle switch
     * @var  btnw    Outer width of the central part of the switch
     */
    var offw = $('td.toggleOff', $obj).outerWidth();
    var btnw = $($obj).find('table td').eq(1).outerWidth();
    // Resize the main div so that exactly one side of
    // the switch plus the central part fit into it.
    $obj.width(offw + btnw + 2);
    /**
     * @var  move  How many pixels to move the
     *             switch by when toggling
     */
    var move = $('td.toggleOff', $obj).outerWidth();
    // If the switch is initialized to the
    // OFF state we need to move it now.
    if ($('div.toggle-container', $obj).hasClass('off')) {
        if (right === 'right') {
            $('div.toggle-container', $obj).animate({ 'left': '-=' + move + 'px' }, 0);
        } else {
            $('div.toggle-container', $obj).animate({ 'left': '+=' + move + 'px' }, 0);
        }
    }

    // Attach an 'onclick' event to the switch
    $('div.toggle-container', $obj).on('click', function () {
        if ($(this).hasClass('isActive')) {
            return false;
        } else {
            $(this).addClass('isActive');
        }

        var $msg = ajaxShowMessage();
        var $container = $(this);
        var callback = $('span.callback', this).text();
        var operator;
        var url;
        var removeClass;
        var addClass;
        // Perform the actual toggle
        if ($(this).hasClass('on')) {
            if (right === 'right') {
                operator = '-=';
            } else {
                operator = '+=';
            }

            url = $(this).find('td.toggleOff > span').text();
            removeClass = 'on';
            addClass = 'off';
        } else {
            if (right === 'right') {
                operator = '+=';
            } else {
                operator = '-=';
            }

            url = $(this).find('td.toggleOn > span').text();
            removeClass = 'off';
            addClass = 'on';
        }

        var parts = url.split('?');
        $.post(parts[0], parts[1] + '&ajax_request=true', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                ajaxRemoveMessage($msg);
                $container
                    .removeClass(removeClass)
                    .addClass(addClass)
                    .animate({ 'left': operator + move + 'px' }, function () {
                        $container.removeClass('isActive');
                    });

                // eslint-disable-next-line no-eval
                eval(callback);
            } else {
                ajaxShowMessage(data.error, false);
                $container.removeClass('isActive');
            }
        });
    });
}

function initializeToggleButtons (): void {
    $('div.toggleAjax').each(function () {
        var $button = $(this).show();
        $button.find('img').each(function () {
            if (this.complete) {
                Functions.toggleButton($button);
            } else {
                $(this).on('load', function () {
                    Functions.toggleButton($button);
                });
            }
        });
    });
}

/**
 * Auto submit page selector
 * @return {function}
 */
function getPageSelectorEventHandler () {
    return function (event) {
        event.stopPropagation();
        // Check where to load the new content
        if ($(this).closest('#pma_navigation').length === 0) {
            // For the main page we don't need to do anything,
            $(this).closest('form').trigger('submit');
        } else {
            // but for the navigation we need to manually replace the content
            Navigation.treePagination($(this));
        }
    };
}

function teardownRecentFavoriteTables (): void {
    $('#update_recent_tables').off('ready');
    $('#sync_favorite_tables').off('ready');
}

function onloadRecentFavoriteTables (): void {
    var $updateRecentTables = $('#update_recent_tables');
    if ($updateRecentTables.length) {
        $.get(
            $updateRecentTables.attr('href'),
            { 'no_debug': true },
            function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    $('#pma_recent_list').html(data.list);
                }
            }
        );
    }

    // Sync favorite tables from localStorage to pmadb.
    if (! $('#sync_favorite_tables').length) {
        return;
    }

    $.ajax({
        url: $('#sync_favorite_tables').attr('href'),
        cache: false,
        type: 'POST',
        data: {
            'favoriteTables': (isStorageSupported('localStorage') && typeof window.localStorage.favoriteTables !== 'undefined')
                ? window.localStorage.favoriteTables
                : '',
            'server': CommonParams.get('server'),
            'no_debug': true
        },
        success: function (data) {
            // Update localStorage.
            if (isStorageSupported('localStorage')) {
                window.localStorage.favoriteTables = data.favoriteTables;
            }

            $('#pma_favorite_list').html(data.list);
        }
    });
}

/**
 * Creates a message inside an object with a sliding effect
 *
 * @param {string} msg    A string containing the text to display
 * @param {JQuery} $object   a jQuery object containing the reference
 *                 to the element where to put the message
 *                 This is optional, if no element is
 *                 provided, one will be created below the
 *                 navigation links at the top of the page
 *
 * @return {boolean} True on success, false on failure
 */
function slidingMessage (msg, $object = undefined) {
    var $obj = $object;
    if (msg === undefined || msg.length === 0) {
        // Don't show an empty message
        return false;
    }

    // @ts-ignore
    if ($obj === undefined || ! ($obj instanceof $) || $obj.length === 0) {
        // If the second argument was not supplied,
        // we might have to create a new DOM node.
        if ($('#PMA_slidingMessage').length === 0) {
            $('#page_content').prepend(
                '<span id="PMA_slidingMessage" ' +
                'class="d-inline-block"></span>'
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
                highlightSql($obj);
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
        highlightSql($obj);
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
}

/**
 * Attach CodeMirror editor to SQL edit area.
 */
function onloadCodeMirrorEditor (): void {
    var $elm = $('#sqlquery');
    if ($elm.siblings().filter('.CodeMirror').length > 0) {
        return;
    }

    if ($elm.length > 0) {
        if (typeof window.CodeMirror !== 'undefined') {
            window.codeMirrorEditor = Functions.getSqlEditor($elm);
            window.codeMirrorEditor.focus();
            window.codeMirrorEditor.on('blur', Functions.updateQueryParameters);
        } else {
            // without codemirror
            $elm.trigger('focus').on('blur', Functions.updateQueryParameters);
        }
    }

    highlightSql($('body'));
}

function teardownCodeMirrorEditor (): void {
    if (! window.codeMirrorEditor) {
        return;
    }

    $('#sqlquery').text(window.codeMirrorEditor.getValue());
    window.codeMirrorEditor.toTextArea();
    window.codeMirrorEditor = null;
}

function onloadLockPage (): void {
    // initializes all lock-page elements lock-id and
    // val-hash data property
    $('#page_content form.lock-page textarea, ' +
        '#page_content form.lock-page input[type="text"], ' +
        '#page_content form.lock-page input[type="number"], ' +
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
        $(this).data('val-hash', AJAX.hash($(this).is(':checked')));
    });
}

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
}($));

/**
 * Return value of a cell in a table.
 *
 * @param {string} td
 * @return {string}
 */
function getCellValue (td) {
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
 * Validate and return stringified JSON inputs, or plain if invalid.
 *
 * @param json the json input to be validated and stringified
 * @param replacer An array of strings and numbers that acts as an approved list for selecting the object properties that will be stringified.
 * @param space Adds indentation, white space, and line break characters to the return-value JSON text to make it easier to read.
 * @return {string}
 */
function stringifyJSON (json, replacer = null, space = 0) {
    try {
        return JSON.stringify(JSON.parse(json), replacer, space);
    } catch (e) {
        return json;
    }
}

/**
 * Automatic form submission on change.
 * @return {function}
 */
function getAutoSubmitEventHandler () {
    return function () {
        $(this).closest('form').trigger('submit');
    };
}

function teardownCreateView () {
    $(document).off('keydown', '#createViewModal input, #createViewModal select');
    $(document).off('change', '#fkc_checkbox');
}

function onloadCreateView () {
    $('.logout').on('click', function () {
        var form = $(
            '<form method="POST" action="' + $(this).attr('href') + '" class="disableAjax">' +
            '<input type="hidden" name="token" value="' + escapeHtml(CommonParams.get('token')) + '">' +
            '</form>'
        );
        $('body').append(form);
        form.submit();
        sessionStorage.clear();

        return false;
    });

    /**
     * Attach Ajax event handlers for input fields in the editor
     * and used to submit the Ajax request when the ENTER key is pressed.
     */
    if ($('#createViewModal').length !== 0) {
        $(document).on('keydown', '#createViewModal input, #createViewModal select', function (e) {
            if (e.which === 13) { // 13 is the ENTER key
                e.preventDefault();

                // with preventing default, selection by <select> tag
                // was also prevented in IE
                $(this).trigger('blur');

                $(this).closest('.ui-dialog').find('.ui-button').first().trigger('click');
            }
        }); // end $(document).on()
    }

    if ($('textarea[name="view[as]"]').length !== 0) {
        window.codeMirrorEditor = Functions.getSqlEditor($('textarea[name="view[as]"]'));
    }
}

/**
 * Makes the breadcrumbs and the menu bar float at the top of the viewport.
 * @return {function}
 */
function floatingMenuBar () {
    return function () {
        if (! $('#floating_menubar').length || $('#PMA_disable_floating_menubar').length !== 0) {
            return;
        }

        var left = $('html').attr('dir') === 'ltr' ? 'left' : 'right';
        $('#floating_menubar')
            .css('margin-' + left, $('#pma_navigation').width() + $('#pma_navigation_resizer').width())
            .css(left, 0)
            .css({
                'position': 'fixed',
                'top': 0,
                'width': '100%',
                'z-index': 99
            })
            .append($('#server-breadcrumb'))
            .append($('#topmenucontainer'));

        // Allow the DOM to render, then adjust the padding on the body
        setTimeout(function () {
            $('body').css(
                'padding-top',
                $('#floating_menubar').outerHeight(true)
            );

            $('#topmenu').menuResizer('resize');
        }, 4);
    };
}

/**
 * Scrolls the page to the top if clicking the server-breadcrumb bar
 * @return {function}
 */
function breadcrumbScrollToTop () {
    return function () {
        $(document).on('click', '#server-breadcrumb, #goto_pagetop', function (event) {
            event.preventDefault();
            $('html, body').animate({ scrollTop: 0 }, 'fast');
        });
    };
}

const checkboxesSel = 'input.checkall:checkbox:enabled';

/**
 * Watches checkboxes in a form to set the checkall box accordingly
 */
function checkboxesChanged () {
    var $form = $(this.form);
    // total number of checkboxes in current form
    var totalBoxes = $form.find(checkboxesSel).length;
    // number of checkboxes checked in current form
    var checkedBoxes = $form.find(checkboxesSel + ':checked').length;
    var $checkall = $form.find('input.checkall_box');
    if (totalBoxes === checkedBoxes) {
        $checkall.prop({ checked: true, indeterminate: false });
    } else if (checkedBoxes > 0) {
        $checkall.prop({ checked: true, indeterminate: true });
    } else {
        $checkall.prop({ checked: false, indeterminate: false });
    }
}

/**
 * @return {function}
 */
function getCheckAllBoxEventHandler () {
    return function () {
        var isChecked = $(this).is(':checked');
        $(this.form).find(checkboxesSel).not('.row-hidden').prop('checked', isChecked)
            .parents('tr').toggleClass('marked table-active', isChecked);
    };
}

/**
 * @return {function}
 */
function getCheckAllFilterEventHandler () {
    return function () {
        var $this = $(this);
        var selector = $this.data('checkall-selector');
        $('input.checkall_box').prop('checked', false);
        $this.parents('form').find(checkboxesSel).filter(selector).prop('checked', true).trigger('change')
            .parents('tr').toggleClass('marked', true);

        return false;
    };
}

/**
 * Watches checkboxes in a sub form to set the sub checkall box accordingly
 */
function subCheckboxesChanged () {
    var $form = $(this).parent().parent();
    // total number of checkboxes in current sub form
    var totalBoxes = $form.find(checkboxesSel).length;
    // number of checkboxes checked in current sub form
    var checkedBoxes = $form.find(checkboxesSel + ':checked').length;
    var $checkall = $form.find('input.sub_checkall_box');
    if (totalBoxes === checkedBoxes) {
        $checkall.prop({ checked: true, indeterminate: false });
    } else if (checkedBoxes > 0) {
        $checkall.prop({ checked: true, indeterminate: true });
    } else {
        $checkall.prop({ checked: false, indeterminate: false });
    }
}

/**
 * @return {function}
 */
function getSubCheckAllBoxEventHandler () {
    return function () {
        var isChecked = $(this).is(':checked');
        var $form = $(this).parent().parent();
        $form.find(checkboxesSel).prop('checked', isChecked)
            .parents('tr').toggleClass('marked', isChecked);
    };
}

/**
 * Rows filtering
 *
 * - rows to filter are identified by data-filter-row attribute
 *   which contains uppercase string to filter
 * - it is simple substring case insensitive search
 * - optionally number of matching rows is written to element with
 *   id filter-rows-count
 * @return {function}
 */
function getFilterTextEventHandler () {
    return function () {
        var filterInput = ($(this).val() as string).toUpperCase().replace(/ /g, '_');
        var count = 0;
        $('[data-filter-row]').each(function () {
            var $row = $(this);
            /* Can not use data() here as it does magic conversion to int for numeric values */
            if ($row.attr('data-filter-row').indexOf(filterInput) > -1) {
                count += 1;
                $row.show();
                $row.find('input.checkall').removeClass('row-hidden');
            } else {
                $row.hide();
                $row.find('input.checkall').addClass('row-hidden').prop('checked', false);
                $row.removeClass('marked');
            }
        });

        setTimeout(function () {
            $(checkboxesSel).trigger('change');
        }, 300);

        $('#filter-rows-count').html(count.toString());
    };
}

function onloadFilterText () {
    /* Trigger filtering of the list based on incoming database name */
    var $filter = $('#filterText');
    if ($filter.val()) {
        $filter.trigger('keyup').trigger('select');
    }
}

/**
 * Formats a byte number to human-readable form
 *
 * @param bytesToFormat the bytes to format
 * @param subDecimals optional subdecimals the number of digits after the point
 * @param pointChar optional pointchar the char to use as decimal point
 *
 * @return {string}
 */
function formatBytes (bytesToFormat, subDecimals, pointChar) {
    var bytes = bytesToFormat;
    var decimals = subDecimals;
    var point = pointChar;
    if (! decimals) {
        decimals = 0;
    }

    if (! point) {
        point = '.';
    }

    var units = ['B', 'KiB', 'MiB', 'GiB'];
    for (var i = 0; bytes > 1024 && i < units.length; i++) {
        bytes /= 1024;
    }

    var factor = Math.pow(10, decimals);
    bytes = Math.round(bytes * factor) / factor;
    bytes = bytes.toString().split('.').join(point);

    return bytes + ' ' + units[i];
}

function onloadLoginForm () {
    /**
     * Reveal the login form to users with JS enabled
     * and focus the appropriate input field
     */
    var $loginform = $('#loginform');
    if ($loginform.length) {
        $loginform.find('.js-show').show();
        if ($('#input_username').val()) {
            $('#input_password').trigger('focus');
        } else {
            $('#input_username').trigger('focus');
        }
    }

    var $httpsWarning = $('#js-https-mismatch');
    if ($httpsWarning.length) {
        if ((window.location.protocol === 'https:') !== CommonParams.get('is_https')) {
            $httpsWarning.show();
        }
    }
}

/**
 * Toggle the Datetimepicker UI if the date value entered
 * by the user in the 'text box' is not going to be accepted
 * by the Datetimepicker plugin (but is accepted by MySQL)
 *
 * @param $td
 * @param $inputField
 */
function toggleDatepickerIfInvalid ($td, $inputField) {
    // If the Datetimepicker UI is not present, return
    if ($inputField.hasClass('hasDatepicker')) {
        // Regex allowed by the Datetimepicker UI
        var dtexpDate = new RegExp([
            '^([0-9]{4})',
            '-(((01|03|05|07|08|10|12)-((0[1-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)',
            '-((0[1-9])|([1-2][0-9])|30)))$'
        ].join(''));
        var dtexpTime = new RegExp([
            '^(([0-1][0-9])|(2[0-3]))',
            ':((0[0-9])|([1-5][0-9]))',
            ':((0[0-9])|([1-5][0-9]))(.[0-9]{1,6}){0,1}$'
        ].join(''));

        // If key-ed in Time or Date values are unsupported by the UI, close it
        if ($td.attr('data-type') === 'date' && ! dtexpDate.test($inputField.val())) {
            $inputField.datepicker('hide');
        } else if ($td.attr('data-type') === 'time' && ! dtexpTime.test($inputField.val())) {
            $inputField.datepicker('hide');
        } else {
            $inputField.datepicker('show');
        }
    }
}

/**
 * Function to submit the login form after validation is done.
 * NOTE: do NOT use a module or it will break the callback, issue #15435
 */
window.recaptchaCallback = function () {
    $('#login_form').trigger('submit');
};

/**
 * Handle 'Ctrl/Alt + Enter' form submits
 * @return {function}
 */
function getKeyboardFormSubmitEventHandler () {
    return function (e) {
        if (e.which !== 13 || ! (e.ctrlKey || e.altKey)) {
            return;
        }

        var $form = $(this).closest('form');

        // There could be multiple submit buttons on the same form,
        // we assume all of them behave identical and just click one.
        if (! $form.find('input[type="submit"]').first() ||
            ! $form.find('input[type="submit"]').first().trigger('click')
        ) {
            $form.trigger('submit');
        }
    };
}

/**
 * Display warning regarding SSL when sha256_password method is selected
 * Used in /user-password (Change Password link on index.php)
 * @return {function}
 */
function getSslPasswordEventHandler () {
    return function () {
        if (this.value === 'sha256_password') {
            $('#ssl_reqd_warning_cp').show();
        } else {
            $('#ssl_reqd_warning_cp').hide();
        }
    };
}

function teardownSortLinkMouseEvent () {
    $(document).off('mouseover', '.sortlink');
    $(document).off('mouseout', '.sortlink');
}

function onloadSortLinkMouseEvent () {
    // Bind event handlers for toggling sort icons
    $(document).on('mouseover', '.sortlink', function () {
        $(this).find('.soimg').toggle();
    });

    $(document).on('mouseout', '.sortlink', function () {
        $(this).find('.soimg').toggle();
    });
}

/**
 * Return POST data as stored by Generator::linkOrButton
 *
 * @return {string}
 */
function getPostData () {
    var dataPost = this.attr('data-post');
    // Strip possible leading ?
    if (dataPost !== undefined && dataPost.startsWith('?')) {
        dataPost = dataPost.substring(1);
    }

    return dataPost;
}

/**
 * General functions, usually for data manipulation pages.
 * @test-module Functions
 */
const Functions = {
    addNoCacheToAjaxRequests: addNoCacheToAjaxRequests,
    addDatepicker: addDatepicker,
    addDateTimePicker: addDateTimePicker,
    getSqlEditor: getSqlEditor,
    clearSelection: clearSelection,
    hideShowDefaultValue: hideShowDefaultValue,
    hideShowExpression: hideShowExpression,
    verifyColumnsProperties: verifyColumnsProperties,
    prepareForAjaxRequest: prepareForAjaxRequest,
    checkPasswordStrength: checkPasswordStrength,
    suggestPassword: suggestPassword,
    displayPasswordGenerateButton: displayPasswordGenerateButton,
    confirmLink: confirmLink,
    confirmQuery: confirmQuery,
    checkSqlQuery: checkSqlQuery,
    emptyCheckTheField: emptyCheckTheField,
    checkFormElementInRange: checkFormElementInRange,
    checkTableEditForm: checkTableEditForm,
    teardownIdleEvent: teardownIdleEvent,
    onloadIdleEvent: onloadIdleEvent,
    getCheckAllCheckboxEventHandler: getCheckAllCheckboxEventHandler,
    setSelectOptions: setSelectOptions,
    updateQueryParameters: updateQueryParameters,
    getForeignKeyCheckboxLoader: getForeignKeyCheckboxLoader,
    loadForeignKeyCheckbox: loadForeignKeyCheckbox,
    teardownSqlQueryEditEvents: teardownSqlQueryEditEvents,
    onloadSqlQueryEditEvents: onloadSqlQueryEditEvents,
    codeMirrorAutoCompleteOnInputRead: codeMirrorAutoCompleteOnInputRead,
    removeAutocompleteInfo: removeAutocompleteInfo,
    bindCodeMirrorToInlineEditor: bindCodeMirrorToInlineEditor,
    catchKeypressesFromSqlInlineEdit: catchKeypressesFromSqlInlineEdit,
    updateCode: updateCode,
    previewSql: previewSql,
    confirmPreviewSql: confirmPreviewSql,
    checkReservedWordColumns: checkReservedWordColumns,
    copyToClipboard: copyToClipboard,
    dismissNotifications: dismissNotifications,
    showNoticeForEnum: showNoticeForEnum,
    showWarningForIntTypes: showWarningForIntTypes,
    prettyProfilingNum: prettyProfilingNum,
    sqlPrettyPrint: sqlPrettyPrint,
    confirm: confirmDialog,
    sortTable: sortTable,
    teardownCreateTableEvents: teardownCreateTableEvents,
    onloadCreateTableEvents: onloadCreateTableEvents,
    checkPassword: checkPassword,
    onloadChangePasswordEvents: onloadChangePasswordEvents,
    teardownEnumSetEditorMessage: teardownEnumSetEditorMessage,
    onloadEnumSetEditorMessage: onloadEnumSetEditorMessage,
    hideShowConnection: hideShowConnection,
    validateDefaultValue: validateDefaultValue,
    autoPopulate: autoPopulate,
    teardownEnumSetEditor: teardownEnumSetEditor,
    onloadEnumSetEditor: onloadEnumSetEditor,
    getAddIndexEventHandler: getAddIndexEventHandler,
    indexDialogModal: indexDialogModal,
    indexEditorDialog: indexEditorDialog,
    indexRenameDialog: indexRenameDialog,
    showIndexEditDialog: showIndexEditDialog,
    showHints: showHints,
    initializeMenuResizer: initializeMenuResizer,
    toggleButton: toggleButton,
    initializeToggleButtons: initializeToggleButtons,
    getPageSelectorEventHandler: getPageSelectorEventHandler,
    teardownRecentFavoriteTables: teardownRecentFavoriteTables,
    onloadRecentFavoriteTables: onloadRecentFavoriteTables,
    slidingMessage: slidingMessage,
    onloadCodeMirrorEditor: onloadCodeMirrorEditor,
    teardownCodeMirrorEditor: teardownCodeMirrorEditor,
    onloadLockPage: onloadLockPage,
    getCellValue: getCellValue,
    stringifyJSON: stringifyJSON,
    getAutoSubmitEventHandler: getAutoSubmitEventHandler,
    teardownCreateView: teardownCreateView,
    onloadCreateView: onloadCreateView,
    floatingMenuBar: floatingMenuBar,
    breadcrumbScrollToTop: breadcrumbScrollToTop,
    checkboxesSel: checkboxesSel,
    checkboxesChanged: checkboxesChanged,
    getCheckAllBoxEventHandler: getCheckAllBoxEventHandler,
    getCheckAllFilterEventHandler: getCheckAllFilterEventHandler,
    subCheckboxesChanged: subCheckboxesChanged,
    getSubCheckAllBoxEventHandler: getSubCheckAllBoxEventHandler,
    getFilterTextEventHandler: getFilterTextEventHandler,
    onloadFilterText: onloadFilterText,
    formatBytes: formatBytes,
    onloadLoginForm: onloadLoginForm,
    toggleDatepickerIfInvalid: toggleDatepickerIfInvalid,
    getKeyboardFormSubmitEventHandler: getKeyboardFormSubmitEventHandler,
    getSslPasswordEventHandler: getSslPasswordEventHandler,
    teardownSortLinkMouseEvent: teardownSortLinkMouseEvent,
    onloadSortLinkMouseEvent: onloadSortLinkMouseEvent,
    getPostData: getPostData,
};

$.fn.confirm = Functions.confirm;
$.fn.sortTable = Functions.sortTable;
$.fn.getPostData = Functions.getPostData;

declare global {
    interface Window {
        codeMirrorEditor: CodeMirror.EditorFromTextArea | null;
        recaptchaCallback: () => void;
        centralColumnList: any[];
        Functions: typeof Functions;
    }
}

window.Functions = Functions;

export { Functions };
