/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    function used in table data manipulation pages
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @requires    js/functions.js
 *
 */

/**
 * Modify form controls when the "NULL" checkbox is checked
 *
 * @param theType     string   the MySQL field type
 * @param urlField    string   the urlencoded field name - OBSOLETE
 * @param md5Field    string   the md5 hashed field name
 * @param multi_edit  string   the multi_edit row sequence number
 *
 * @return boolean  always true
 */
function nullify (theType, urlField, md5Field, multi_edit) {
    var rowForm = document.forms.insertForm;

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']']) !== 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "ENUM" field with more than 20 characters
    if (theType === 1) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'][1].selectedIndex = -1;
    // Other "ENUM" field
    } else if (theType === 2) {
        var elts     = rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var elts_cnt = elts.length;
            for (var i = 0; i < elts_cnt; i++) {
                elts[i].checked = false;
            } // end for
        } // end if
    // "SET" field
    } else if (theType === 3) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  '][]'].selectedIndex = -1;
    // Foreign key field (drop-down)
    } else if (theType === 4) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'].selectedIndex = -1;
    // foreign key field (with browsing icon for foreign values)
    } else if (theType === 6) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    // Other field types
    } else /* if (theType === 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
// function checks the number of days in febuary
function daysInFebruary (year) {
    return (((year % 4 === 0) && (((year % 100 !== 0)) || (year % 400 === 0))) ? 29 : 28);
}
// function to convert single digit to double digit
function fractionReplace (num) {
    num = parseInt(num, 10);
    return num >= 1 && num <= 9 ? '0' + num : '00';
}

/* function to check the validity of date
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2001-12-23
* 2) 2001-1-2
* 3) 02-12-23
* 4) And instead of using '-' the following punctuations can be used (+,.,*,^,@,/) All these are accepted by mysql as well. Therefore no issues
*/
function isDate (val, tmstmp) {
    val = val.replace(/[.|*|^|+|//|@]/g, '-');
    var arrayVal = val.split('-');
    for (var a = 0; a < arrayVal.length; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join('-');
    var pos = 2;
    var dtexp = new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30))|((00)-(00)))$/);
    if (val.length === 8) {
        pos = 0;
    }
    if (dtexp.test(val)) {
        var month = parseInt(val.substring(pos + 3, pos + 5), 10);
        var day = parseInt(val.substring(pos + 6, pos + 8), 10);
        var year = parseInt(val.substring(0, pos + 2), 10);
        if (month === 2 && day > daysInFebruary(year)) {
            return false;
        }
        if (val.substring(0, pos + 2).length === 2) {
            year = parseInt('20' + val.substring(0, pos + 2), 10);
        }
        if (tmstmp === true) {
            if (year < 1978) {
                return false;
            }
            if (year > 2038 || (year > 2037 && day > 19 && month >= 1) || (year > 2037 && month > 1)) {
                return false;
            }
        }
    } else {
        return false;
    }
    return true;
}

/* function to check the validity of time
* The following patterns are accepted in this validation (accepted in mysql as well)
* 1) 2:3:4
* 2) 2:23:43
* 3) 2:23:43.123456
*/
function isTime (val) {
    var arrayVal = val.split(':');
    for (var a = 0, l = arrayVal.length; a < l; a++) {
        if (arrayVal[a].length === 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join(':');
    var tmexp = new RegExp(/^(-)?(([0-7]?[0-9][0-9])|(8[0-2][0-9])|(83[0-8])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$/);
    return tmexp.test(val);
}

/**
 * To check whether insert section is ignored or not
 */
function checkForCheckbox (multi_edit) {
    if ($('#insert_ignore_' + multi_edit).length) {
        return $('#insert_ignore_' + multi_edit).is(':unchecked');
    }
    return true;
}

function verificationsAfterFieldChange (urlField, multi_edit, theType) {
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    var $this_input = $(':input[name^=\'fields[multi_edit][' + multi_edit + '][' +
        urlField + ']\']');
    // the function drop-down that corresponds to this input field
    var $this_function = $('select[name=\'funcs[multi_edit][' + multi_edit + '][' +
        urlField + ']\']');
    var function_selected = false;
    if (typeof $this_function.val() !== 'undefined' &&
        $this_function.val() !== null &&
        $this_function.val().length > 0
    ) {
        function_selected = true;
    }

    // To generate the textbox that can take the salt
    var new_salt_box = '<br><input type=text name=salt[multi_edit][' + multi_edit + '][' + urlField + ']' +
        ' id=salt_' + target.id + ' placeholder=\'' + PMA_messages.strEncryptionKey + '\'>';

    // If encrypting or decrypting functions that take salt as input is selected append the new textbox for salt
    if (target.value === 'AES_ENCRYPT' ||
            target.value === 'AES_DECRYPT' ||
            target.value === 'DES_ENCRYPT' ||
            target.value === 'DES_DECRYPT' ||
            target.value === 'ENCRYPT') {
        if (!($('#salt_' + target.id).length)) {
            $this_input.after(new_salt_box);
        }
    } else {
        // Remove the textbox for salt
        $('#salt_' + target.id).prev('br').remove();
        $('#salt_' + target.id).remove();
    }

    if (target.value === 'AES_DECRYPT'
            || target.value === 'AES_ENCRYPT'
            || target.value === 'MD5') {
        $('#' + target.id).rules('add', {
            validationFunctionForFuns: {
                param: $this_input,
                depends: function () {
                    return checkForCheckbox(multi_edit);
                }
            }
        });
    }

    // Unchecks the corresponding "NULL" control
    $('input[name=\'fields_null[multi_edit][' + multi_edit + '][' + urlField + ']\']').prop('checked', false);

    // Unchecks the Ignore checkbox for the current row
    $('input[name=\'insert_ignore_' + multi_edit + '\']').prop('checked', false);

    var charExceptionHandling;
    if (theType.substring(0,4) === 'char') {
        charExceptionHandling = theType.substring(5,6);
    } else if (theType.substring(0,7) === 'varchar') {
        charExceptionHandling = theType.substring(8,9);
    }
    if (function_selected) {
        $this_input.removeAttr('min');
        $this_input.removeAttr('max');
        // @todo: put back attributes if corresponding function is deselected
    }

    if ($this_input.data('rulesadded') === null && ! function_selected) {
        // call validate before adding rules
        $($this_input[0].form).validate();
        // validate for date time
        if (theType === 'datetime' || theType === 'time' || theType === 'date' || theType === 'timestamp') {
            $this_input.rules('add', {
                validationFunctionForDateTime: {
                    param: theType,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        // validation for integer type
        if ($this_input.data('type') === 'INT') {
            var mini = parseInt($this_input.attr('min'));
            var maxi = parseInt($this_input.attr('max'));
            $this_input.rules('add', {
                number: {
                    param : true,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                },
                min: {
                    param: mini,
                    depends: function () {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                },
                max: {
                    param: maxi,
                    depends: function () {
                        if (isNaN($this_input.val())) {
                            return false;
                        } else {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                }
            });
            // validation for CHAR types
        } else if ($this_input.data('type') === 'CHAR') {
            var maxlen = $this_input.data('maxlength');
            if (typeof maxlen !== 'undefined') {
                if (maxlen <= 4) {
                    maxlen = charExceptionHandling;
                }
                $this_input.rules('add', {
                    maxlength: {
                        param: maxlen,
                        depends: function () {
                            return checkForCheckbox(multi_edit);
                        }
                    }
                });
            }
            // validate binary & blob types
        } else if ($this_input.data('type') === 'HEX') {
            $this_input.rules('add', {
                validationFunctionForHex: {
                    param: true,
                    depends: function () {
                        return checkForCheckbox(multi_edit);
                    }
                }
            });
        }
        $this_input.data('rulesadded', true);
    } else if ($this_input.data('rulesadded') === true && function_selected) {
        // remove any rules added
        $this_input.rules('remove');
        // remove any error messages
        $this_input
            .removeClass('error')
            .removeAttr('aria-invalid')
            .siblings('.error')
            .remove();
        $this_input.data('rulesadded', null);
    }
}
/* End of fields validation*/

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_change.js', function () {
    $(document).off('click', 'span.open_gis_editor');
    $(document).off('click', 'input[name^=\'insert_ignore_\']');
    $(document).off('click', 'input[name=\'gis_data[save]\']');
    $(document).off('click', 'input.checkbox_null');
    $('select[name="submit_type"]').off('change');
    $(document).off('change', '#insert_rows');
});

/**
 * Ajax handlers for Change Table page
 *
 * Actions Ajaxified here:
 * Submit Data to be inserted into the table.
 * Restart insertion with 'N' rows.
 */
AJAX.registerOnload('tbl_change.js', function () {
    if ($('#insertForm').length) {
        // validate the comment form when it is submitted
        $('#insertForm').validate();
        jQuery.validator.addMethod('validationFunctionForHex', function (value, element) {
            return value.match(/^[a-f0-9]*$/i) !== null;
        });

        jQuery.validator.addMethod('validationFunctionForFuns', function (value, element, options) {
            if (value.substring(0, 3) === 'AES' && options.data('type') !== 'HEX') {
                return false;
            }

            return !(value.substring(0, 3) === 'MD5' &&
                typeof options.data('maxlength') !== 'undefined' &&
                options.data('maxlength') < 32);
        });

        jQuery.validator.addMethod('validationFunctionForDateTime', function (value, element, options) {
            var dt_value = value;
            var theType = options;
            if (theType === 'date') {
                return isDate(dt_value);
            } else if (theType === 'time') {
                return isTime(dt_value);
            } else if (theType === 'datetime' || theType === 'timestamp') {
                var tmstmp = false;
                dt_value = dt_value.trim();
                if (dt_value === 'CURRENT_TIMESTAMP' || dt_value === 'current_timestamp()') {
                    return true;
                }
                if (theType === 'timestamp') {
                    tmstmp = true;
                }
                if (dt_value === '0000-00-00 00:00:00') {
                    return true;
                }
                var dv = dt_value.indexOf(' ');
                if (dv === -1) { // Only the date component, which is valid
                    return isDate(dt_value, tmstmp);
                }

                return isDate(dt_value.substring(0, dv), tmstmp) &&
                    isTime(dt_value.substring(dv + 1));
            }
        });
        /*
         * message extending script must be run
         * after initiation of functions
         */
        extendingValidatorMessages();
    }

    $.datepicker.initialized = false;

    $(document).on('click', 'span.open_gis_editor', function (event) {
        event.preventDefault();

        var $span = $(this);
        // Current value
        var value = $span.parent('td').children('input[type=\'text\']').val();
        // Field name
        var field = $span.parents('tr').children('td:first').find('input[type=\'hidden\']').val();
        // Column type
        var type = $span.parents('tr').find('span.column_type').text();
        // Names of input field and null checkbox
        var input_name = $span.parent('td').children('input[type=\'text\']').attr('name');

        openGISEditor();
        if (!gisEditorLoaded) {
            loadJSAndGISEditor(value, field, type, input_name);
        } else {
            loadGISEditor(value, field, type, input_name);
        }
    });

    /**
     * Forced validation check of fields
     */
    $(document).on('click','input[name^=\'insert_ignore_\']', function (event) {
        $('#insertForm').valid();
    });

    /**
     * Uncheck the null checkbox as geometry data is placed on the input field
     */
    $(document).on('click', 'input[name=\'gis_data[save]\']', function (event) {
        var input_name = $('form#gis_data_editor_form').find('input[name=\'input_name\']').val();
        var $null_checkbox = $('input[name=\'' + input_name + '\']').parents('tr').find('.checkbox_null');
        $null_checkbox.prop('checked', false);
    });

    /**
     * Handles all current checkboxes for Null; this only takes care of the
     * checkboxes on currently displayed rows as the rows generated by
     * "Continue insertion" are handled in the "Continue insertion" code
     *
     */
    $(document).on('click', 'input.checkbox_null', function () {
        nullify(
            // use hidden fields populated by tbl_change.php
            $(this).siblings('.nullify_code').val(),
            $(this).closest('tr').find('input:hidden').first().val(),
            $(this).siblings('.hashed_field').val(),
            $(this).siblings('.multi_edit').val()
        );
    });

    /**
     * Reset the auto_increment column to 0 when selecting any of the
     * insert options in submit_type-dropdown. Only perform the reset
     * when we are in edit-mode, and not in insert-mode(no previous value
     * available).
     */
    $('select[name="submit_type"]').on('change', function () {
        var thisElemSubmitTypeVal = $(this).val();
        var $table = $('table.insertRowTable');
        var auto_increment_column = $table.find('input[name^="auto_increment"]');
        auto_increment_column.each(function () {
            var $thisElemAIField = $(this);
            var thisElemName = $thisElemAIField.attr('name');

            var prev_value_field = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields_prev') + '"]');
            var value_field = $table.find('input[name="' + thisElemName.replace('auto_increment', 'fields') + '"]');
            var previous_value = $(prev_value_field).val();
            if (previous_value !== undefined) {
                if (thisElemSubmitTypeVal === 'insert'
                    || thisElemSubmitTypeVal === 'insertignore'
                    || thisElemSubmitTypeVal === 'showinsert'
                ) {
                    $(value_field).val(0);
                } else {
                    $(value_field).val(previous_value);
                }
            }
        });
    });

    /**
     * Continue Insertion form
     */
    $(document).on('change', '#insert_rows', function (event) {
        event.preventDefault();
        /**
         * @var columnCount   Number of number of columns table has.
         */
        var columnCount = $('table.insertRowTable:first').find('tr').has('input[name*=\'fields_name\']').length;
        /**
         * @var curr_rows   Number of current insert rows already on page
         */
        var curr_rows = $('table.insertRowTable').length;
        /**
         * @var target_rows Number of rows the user wants
         */
        var target_rows = $('#insert_rows').val();

        // remove all datepickers
        $('input.datefield, input.datetimefield').each(function () {
            $(this).datepicker('destroy');
        });

        if (curr_rows < target_rows) {
            var tempIncrementIndex = function () {
                var $this_element = $(this);
                /**
                 * Extract the index from the name attribute for all input/select fields and increment it
                 * name is of format funcs[multi_edit][10][<long random string of alphanum chars>]
                 */

                /**
                 * @var this_name   String containing name of the input/select elements
                 */
                var this_name = $this_element.attr('name');
                /** split {@link this_name} at [10], so we have the parts that can be concatenated later */
                var name_parts = this_name.split(/\[\d+\]/);
                /** extract the [10] from  {@link name_parts} */
                var old_row_index_string = this_name.match(/\[\d+\]/)[0];
                /** extract 10 - had to split into two steps to accomodate double digits */
                var old_row_index = parseInt(old_row_index_string.match(/\d+/)[0], 10);

                /** calculate next index i.e. 11 */
                new_row_index = old_row_index + 1;
                /** generate the new name i.e. funcs[multi_edit][11][foobarbaz] */
                var new_name = name_parts[0] + '[' + new_row_index + ']' + name_parts[1];

                var hashed_field = name_parts[1].match(/\[(.+)\]/)[1];
                $this_element.attr('name', new_name);

                /** If element is select[name*='funcs'], update id */
                if ($this_element.is('select[name*=\'funcs\']')) {
                    var this_id = $this_element.attr('id');
                    var id_parts = this_id.split(/\_/);
                    var old_id_index = id_parts[1];
                    var prevSelectedValue = $('#field_' + old_id_index + '_1').val();
                    var new_id_index = parseInt(old_id_index) + columnCount;
                    var new_id = 'field_' + new_id_index + '_1';
                    $this_element.attr('id', new_id);
                    $this_element.find('option').filter(function () {
                        return $(this).text() === prevSelectedValue;
                    }).attr('selected','selected');

                    // If salt field is there then update its id.
                    var nextSaltInput = $this_element.parent().next('td').next('td').find('input[name*=\'salt\']');
                    if (nextSaltInput.length !== 0) {
                        nextSaltInput.attr('id', 'salt_' + new_id);
                    }
                }

                // handle input text fields and textareas
                if ($this_element.is('.textfield') || $this_element.is('.char') || $this_element.is('textarea')) {
                    // do not remove the 'value' attribute for ENUM columns
                    // special handling for radio fields after updating ids to unique - see below
                    if ($this_element.closest('tr').find('span.column_type').html() !== 'enum') {
                        $this_element.val($this_element.closest('tr').find('span.default_value').html());
                    }
                    $this_element
                        .off('change')
                        // Remove onchange attribute that was placed
                        // by tbl_change.php; it refers to the wrong row index
                        .attr('onchange', null)
                        // Keep these values to be used when the element
                        // will change
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .on('change', function () {
                            var $changed_element = $(this);
                            verificationsAfterFieldChange(
                                $changed_element.data('hashed_field'),
                                $changed_element.data('new_row_index'),
                                $changed_element.closest('tr').find('span.column_type').html()
                            );
                        });
                }

                if ($this_element.is('.checkbox_null')) {
                    $this_element
                        // this event was bound earlier by jQuery but
                        // to the original row, not the cloned one, so unbind()
                        .off('click')
                        // Keep these values to be used when the element
                        // will be clicked
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .on('click', function () {
                            var $changed_element = $(this);
                            nullify(
                                $changed_element.siblings('.nullify_code').val(),
                                $this_element.closest('tr').find('input:hidden').first().val(),
                                $changed_element.data('hashed_field'),
                                '[multi_edit][' + $changed_element.data('new_row_index') + ']'
                            );
                        });
                }
            };

            var tempReplaceAnchor = function () {
                var $anchor = $(this);
                var new_value = 'rownumber=' + new_row_index;
                // needs improvement in case something else inside
                // the href contains this pattern
                var new_href = $anchor.attr('href').replace(/rownumber=\d+/, new_value);
                $anchor.attr('href', new_href);
            };

            while (curr_rows < target_rows) {
                /**
                 * @var $last_row    Object referring to the last row
                 */
                var $last_row = $('#insertForm').find('.insertRowTable:last');

                // need to access this at more than one level
                // (also needs improvement because it should be calculated
                //  just once per cloned row, not once per column)
                var new_row_index = 0;

                // Clone the insert tables
                $last_row
                    .clone(true, true)
                    .insertBefore('#actions_panel')
                    .find('input[name*=multi_edit],select[name*=multi_edit],textarea[name*=multi_edit]')
                    .each(tempIncrementIndex)
                    .end()
                    .find('.foreign_values_anchor')
                    .each(tempReplaceAnchor);

                // Insert/Clone the ignore checkboxes
                if (curr_rows === 1) {
                    $('<input id="insert_ignore_1" type="checkbox" name="insert_ignore_1" checked="checked" />')
                        .insertBefore('table.insertRowTable:last')
                        .after('<label for="insert_ignore_1">' + PMA_messages.strIgnore + '</label>');
                } else {
                    /**
                     * @var $last_checkbox   Object reference to the last checkbox in #insertForm
                     */
                    var $last_checkbox = $('#insertForm').children('input:checkbox:last');

                    /** name of {@link $last_checkbox} */
                    var last_checkbox_name = $last_checkbox.attr('name');
                    /** index of {@link $last_checkbox} */
                    var last_checkbox_index = parseInt(last_checkbox_name.match(/\d+/), 10);
                    /** name of new {@link $last_checkbox} */
                    var new_name = last_checkbox_name.replace(/\d+/, last_checkbox_index + 1);

                    $('<br/><div class="clearfloat"></div>')
                        .insertBefore('table.insertRowTable:last');

                    $last_checkbox
                        .clone()
                        .attr({ 'id': new_name, 'name': new_name })
                        .prop('checked', true)
                        .insertBefore('table.insertRowTable:last');

                    $('label[for^=insert_ignore]:last')
                        .clone()
                        .attr('for', new_name)
                        .insertBefore('table.insertRowTable:last');

                    $('<br/>')
                        .insertBefore('table.insertRowTable:last');
                }
                curr_rows++;
            }
            // recompute tabindex for text fields and other controls at footer;
            // IMO it's not really important to handle the tabindex for
            // function and Null
            var tabindex = 0;
            $('.textfield, .char, textarea')
                .each(function () {
                    tabindex++;
                    $(this).attr('tabindex', tabindex);
                    // update the IDs of textfields to ensure that they are unique
                    $(this).attr('id', 'field_' + tabindex + '_3');

                    // special handling for radio fields after updating ids to unique
                    if ($(this).closest('tr').find('span.column_type').html() === 'enum') {
                        if ($(this).val() === $(this).closest('tr').find('span.default_value').html()) {
                            $(this).prop('checked', true);
                        } else {
                            $(this).prop('checked', false);
                        }
                    }
                });
            $('.control_at_footer')
                .each(function () {
                    tabindex++;
                    $(this).attr('tabindex', tabindex);
                });
        } else if (curr_rows > target_rows) {
            /**
             * Displays alert if data loss possible on decrease
             * of rows.
             */
            var checkLock = jQuery.isEmptyObject(AJAX.lockedTargets);
            if (checkLock || confirm(PMA_messages.strConfirmRowChange) === true) {
                while (curr_rows > target_rows) {
                    $('input[id^=insert_ignore]:last')
                        .nextUntil('fieldset')
                        .addBack()
                        .remove();
                    curr_rows--;
                }
            } else {
                document.getElementById('insert_rows').value = curr_rows;
            }
        }
        // Add all the required datepickers back
        addDateTimePicker();
    });
});

function changeValueFieldType (elem, searchIndex) {
    var fieldsValue = $('select#fieldID_' + searchIndex);
    if (0 === fieldsValue.size()) {
        return;
    }

    var type = $(elem).val();
    if ('IN (...)' === type ||
        'NOT IN (...)' === type ||
        'BETWEEN' === type ||
        'NOT BETWEEN' === type
    ) {
        $('#fieldID_' + searchIndex).attr('multiple', '');
    } else {
        $('#fieldID_' + searchIndex).removeAttr('multiple');
    }
}
