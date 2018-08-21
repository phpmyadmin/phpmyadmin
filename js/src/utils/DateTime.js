import { $ } from './JqueryExtended';
import { PMA_Messages as PMA_messages } from '../variables/export_variables';
import { PMA_tooltip } from './show_ajax_messages';
/*
 * Adds a date/time picker to an element
 *
 * @param object  $this_element   a jQuery object pointing to the element
 */
export function PMA_addDatepicker ($this_element, type, options) {
    var showTimepicker = true;
    if (type === 'date') {
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
            setTimeout(function () {
                // Fix wrong timepicker z-index, doesn't work without timeout
                $('#ui-timepicker-div').css('z-index', $('#ui-datepicker-div').css('z-index'));
                // Integrate tooltip text into dialog
                var tooltip = $this_element.tooltip('instance');
                if (typeof tooltip !== 'undefined') {
                    tooltip.disable();
                    var $note = $('<p class="note"></div>');
                    $note.text(tooltip.option('content'));
                    $('div.ui-datepicker').append($note);
                }
            }, 0);
        },
        onSelect: function () {
            $this_element.data('datepicker').inline = true;
        },
        onClose: function (dateText, dp_inst) {
            // The value is no more from the date picker
            $this_element.data('comes_from', '');
            if (typeof $this_element.data('datepicker') !== 'undefined') {
                $this_element.data('datepicker').inline = false;
            }
            var tooltip = $this_element.tooltip('instance');
            if (typeof tooltip !== 'undefined') {
                tooltip.enable();
            }
        }
    };
    if (type === 'time') {
        $this_element.timepicker($.extend(defaultOptions, options));
        // Add a tip regarding entering MySQL allowed-values for TIME data-type
        PMA_tooltip($this_element, 'input', PMA_messages.strMysqlAllowedValuesTipTime);
    } else {
        $this_element.datetimepicker($.extend(defaultOptions, options));
    }
}

/**
 * Add a date/time picker to each element that needs it
 * (only when jquery-ui-timepicker-addon.js is loaded)
 */
export function addDateTimePicker () {
    if ($.timepicker !== undefined) {
        $('input.timefield, input.datefield, input.datetimefield').each(function () {
            var decimals = $(this).parent().attr('data-decimals');
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
            PMA_addDatepicker($(this), type, {
                showMillisec: showMillisec,
                showMicrosec: showMicrosec,
                timeFormat: timeFormat,
                hourMax: hourMax
            });
            // Add a tip regarding entering MySQL allowed-values
            // for TIME and DATE data-type
            if ($(this).hasClass('timefield')) {
                PMA_tooltip($(this), 'input', PMA_messages.strMysqlAllowedValuesTipTime);
            } else if ($(this).hasClass('datefield')) {
                PMA_tooltip($(this), 'input', PMA_messages.strMysqlAllowedValuesTipDate);
            }
        });
    }
}

/**
 * Toggle the Datetimepicker UI if the date value entered
 * by the user in the 'text box' is not going to be accepted
 * by the Datetimepicker plugin (but is accepted by MySQL)
 */
export function toggleDatepickerIfInvalid ($td, $input_field) {
    // Regex allowed by the Datetimepicker UI
    var dtexpDate = new RegExp(['^([0-9]{4})',
        '-(((01|03|05|07|08|10|12)-((0[1-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)',
        '-((0[1-9])|([1-2][0-9])|30)))$'].join(''));
    var dtexpTime = new RegExp(['^(([0-1][0-9])|(2[0-3]))',
        ':((0[0-9])|([1-5][0-9]))',
        ':((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$'].join(''));

    // If key-ed in Time or Date values are unsupported by the UI, close it
    if ($td.attr('data-type') === 'date' && ! dtexpDate.test($input_field.val())) {
        $input_field.datepicker('hide');
    } else if ($td.attr('data-type') === 'time' && ! dtexpTime.test($input_field.val())) {
        $input_field.datepicker('hide');
    } else {
        $input_field.datepicker('show');
    }
}
