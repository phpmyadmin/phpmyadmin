import $ from 'jquery';
import 'jquery-migrate';
import 'jquery-ui-bundle';
import 'jquery-ui-timepicker-addon';
import 'jquery-mousewheel';
import 'jquery.event.drag';
import 'jquery-validation';
import { methods } from './menu_resizer';
// TODO: To use this import for replacing variables used in this file for
// extending various strings for localization.
// import { GlobalVariables, timePicker, validations } from '../variables/export_variables';
import { PMA_Messages as PMA_messages } from '../variables/export_variables';

/**
 * Make sure that ajax requests will not be cached
 * by appending a random variable to their parameters
 */
$.ajaxPrefilter(function (options, originalOptions) {
    var nocache = new Date().getTime() + '' + Math.floor(Math.random() * 1000000);
    if (typeof options.data === 'string') {
        options.data += '&_nocache=' + nocache + '&token=' + encodeURIComponent(PMA_commonParams.get('token'));
    } else if (typeof options.data === 'object') {
        options.data = $.extend(originalOptions.data, { '_nocache' : nocache, 'token': PMA_commonParams.get('token') });
    }
});

/**
 * Comes from menu_resizer.js
 */
$.fn.menuResizer = function (method) {
    if (methods[method]) {
        return methods[method].call(this);
    } else if (typeof method === 'function') {
        return methods.init.apply(this, [method]);
    } else {
        $.error('Method ' +  method + ' does not exist on jQuery.menuResizer');
    }
};

/**
 * comes from makegrid.js
 */
$.fn.noSelect = function (p) { // no select plugin by Paulo P.Marinas
    var prevent = (p === null) ? true : p;
    var is_msie = navigator.userAgent.indexOf('MSIE') > -1 || !!window.navigator.userAgent.match(/Trident.*rv\:11\./);
    var is_firefox = navigator.userAgent.indexOf('Firefox') > -1;
    var is_safari = navigator.userAgent.indexOf('Safari') > -1;
    var is_opera = navigator.userAgent.indexOf('Presto') > -1;
    if (prevent) {
        return this.each(function () {
            if (is_msie || is_safari) {
                $(this).on('selectstart', false);
            } else if (is_firefox) {
                $(this).css('MozUserSelect', 'none');
                $('body').trigger('focus');
            } else if (is_opera) {
                $(this).on('mousedown', false);
            } else {
                $(this).attr('unselectable', 'on');
            }
        });
    } else {
        return this.each(function () {
            if (is_msie || is_safari) {
                $(this).off('selectstart');
            } else if (is_firefox) {
                $(this).css('MozUserSelect', 'inherit');
            } else if (is_opera) {
                $(this).off('mousedown');
            } else {
                $(this).removeAttr('unselectable');
            }
        });
    }
};

/**
 * comes from functions.js
 */
/**
 * jQuery plugin to correctly filter input fields by value, needed
 * because some nasty values may break selector syntax
 */
$.fn.filterByValue = function (value) {
    return this.filter(function () {
        return $(this).val() === value;
    });
};

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

$.fn.PMA_confirm = function (question, url, callbackFn, openCallback) {
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
                $(this).dialog('close');
                if ($.isFunction(callbackFn)) {
                    callbackFn.call(this, url);
                }
            }
        },
        {
            text: PMA_messages.strCancel,
            'class': 'submitCancel',
            click: function () {
                $(this).dialog('close');
            }
        }
    ];

    $('<div/>', { 'id': 'confirm_dialog', 'title': PMA_messages.strConfirm })
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
 *
 * @param string      text_selector   string to select the sortKey's text
 *
 * @return jQuery Object for chaining purposes
 */
$.fn.PMA_sort_table = function (text_selector) {
    return this.each(function () {
        /**
         * @var table_body  Object referring to the table's <tbody> element
         */
        var table_body = $(this);
        /**
         * @var rows    Object referring to the collection of rows in {@link table_body}
         */
        var rows = $(this).find('tr').get();

        // get the text of the field that we will sort by
        $.each(rows, function (index, row) {
            row.sortKey = $.trim($(row).find(text_selector).text().toLowerCase());
        });

        // get the sorted order
        rows.sort(function (a, b) {
            if (a.sortKey < b.sortKey) {
                return -1;
            }
            if (a.sortKey > b.sortKey) {
                return 1;
            }
            return 0;
        });

        // pull out each row from the table and then append it according to it's order
        $.each(rows, function (index, row) {
            $(table_body).append(row);
            row.sortKey = null;
        });
    });
};

/**
 * Return POST data as stored by Util::linkOrButton
 */
$.fn.getPostData = function () {
    var dataPost = this.attr('data-post');
    // Strip possible leading ?
    if (dataPost !== undefined && dataPost.substring(0,1) === '?') {
        dataPost = dataPost.substr(1);
    }
    return dataPost;
};

/**
 * Replacing default datepicker strings for localization
 */
if ($.datepicker) {
    // Creating copy of datepicker strings object
    var datePicker = Object.assign(window.datePicker);
    // Deleting datepicker variable from window as it is of no use now
    delete window.datePicker;

    for (let key in datePicker) {
        $.datepicker.regional[''][key] = datePicker[key];
    }
}

/**
 * Replacing default timepicker strings for localozation
 */
if ($.timePicker) {
    // Creating copy of timepicker strings object
    var timePicker = Object.assign(window.timePicker);
    // Deleting timepicker variable from window as it is of no use now
    delete window.timePicker;

    for (let key in timePicker) {
        $.timepicker.regional[''][key] = timePicker[key];
    }
}

export function extendingValidatorMessages () {
    // Creating copy of validationMessage strings object
    var validateMessage = Object.assign(window.validationMessage);
    // Deleting validationMessage variable from window as it is of no use now
    delete window.validationMessage;
    // Replacing default validation messages forr localization
    $.extend($.validator.messages, validateMessage);

    // Creating copy of validationFormat strings object
    var validateFormat = Object.assign(window.validationFormat);
    // Deleting validationFormat variable from window as it is of no use now
    delete window.validationFormat;
    for (let i in validateFormat) {
        validateFormat[i] = $.validator.format(validateFormat[i]);
    }
    // Replacing default validation messages forr localization
    $.extend($.validator.messages, validateFormat);
}

window.jQ = $;

export {
    $
};
