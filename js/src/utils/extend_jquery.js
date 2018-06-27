import $ from 'jquery';
import 'jquery-migrate';
import 'jquery-ui-bundle';
import 'jquery-ui-timepicker-addon';
import 'jquery-mousewheel';
import 'jquery.event.drag';
import 'jquery-validation';
import 'tablesorter';
import { methods } from './menu_resizer';
import { GlobalVariables, timePicker, validations } from '../variables/export_variables';

/**
 * Make sure that ajax requests will not be cached
 * by appending a random variable to their parameters
 */
$.ajaxPrefilter(function (options, originalOptions, jqXHR) {
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
 * Return POST data as stored by Util::linkOrButton
 */
$.fn.getPostData = function () {
    var dataPost = this.attr('data-post');
    // Strip possible leading ?
    if (dataPost !== undefined && dataPost.substring(0,1) == '?') {
        dataPost = dataPost.substr(1);
    }
    return dataPost;
};

for (var key in timePicker) {
    $.datepicker.regional[''][key] = timePicker[key];
}


window.jQ = $;

export const jQuery = $;
