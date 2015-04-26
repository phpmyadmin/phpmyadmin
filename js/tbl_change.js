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
function nullify(theType, urlField, md5Field, multi_edit)
{
    var rowForm = document.forms.insertForm;

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']']) != 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + md5Field + ']'].selectedIndex = -1;
    }

    // "ENUM" field with more than 20 characters
    if (theType == 1) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'][1].selectedIndex = -1;
    }
    // Other "ENUM" field
    else if (theType == 2) {
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
    }
    // "SET" field
    else if (theType == 3) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  '][]'].selectedIndex = -1;
    }
    // Foreign key field (drop-down)
    else if (theType == 4) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field +  ']'].selectedIndex = -1;
    }
    // foreign key field (with browsing icon for foreign values)
    else if (theType == 6) {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    }
    // Other field types
    else /*if (theType == 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + md5Field + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * javascript DateTime format validation.
 * its used to prevent adding default (0000-00-00 00:00:00) to database when user enter wrong values
 * Start of validation part
 */
//function checks the number of days in febuary
function daysInFebruary(year)
{
    return (((year % 4 === 0) && (((year % 100 !== 0)) || (year % 400 === 0))) ? 29 : 28);
}
//function to convert single digit to double digit
function fractionReplace(num)
{
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
function isDate(val, tmstmp)
{
    val = val.replace(/[.|*|^|+|//|@]/g, '-');
    var arrayVal = val.split("-");
    for (var a = 0; a < arrayVal.length; a++) {
        if (arrayVal[a].length == 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join("-");
    var pos = 2;
    var dtexp = new RegExp(/^([0-9]{4})-(((01|03|05|07|08|10|12)-((0[0-9])|([1-2][0-9])|(3[0-1])))|((02|04|06|09|11)-((0[0-9])|([1-2][0-9])|30)))$/);
    if (val.length == 8) {
        pos = 0;
    }
    if (dtexp.test(val)) {
        var month = parseInt(val.substring(pos + 3, pos + 5), 10);
        var day = parseInt(val.substring(pos + 6, pos + 8), 10);
        var year = parseInt(val.substring(0, pos + 2), 10);
        if (month == 2 && day > daysInFebruary(year)) {
            return false;
        }
        if (val.substring(0, pos + 2).length == 2) {
            year = parseInt("20" + val.substring(0, pos + 2), 10);
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
function isTime(val)
{
    var arrayVal = val.split(":");
    for (var a = 0, l = arrayVal.length; a < l; a++) {
        if (arrayVal[a].length == 1) {
            arrayVal[a] = fractionReplace(arrayVal[a]);
        }
    }
    val = arrayVal.join(":");
    var tmexp = new RegExp(/^(([0-1][0-9])|(2[0-3])):((0[0-9])|([1-5][0-9])):((0[0-9])|([1-5][0-9]))(\.[0-9]{1,6}){0,1}$/);
    return tmexp.test(val);
}

function verificationsAfterFieldChange(urlField, multi_edit, theType)
{
    var evt = window.event || arguments.callee.caller.arguments[0];
    var target = evt.target || evt.srcElement;
    var $this_input = $("input[name='fields[multi_edit][" + multi_edit + "][" +
        urlField + "]']");
    // check if it is textarea rather than input
    if ($this_input.length === 0) {
        $this_input = $("textarea[name='fields[multi_edit][" + multi_edit + "][" +
            urlField + "]']");
    }

    //To generate the textbox that can take the salt
    var new_salt_box = "<br><input type=text name=salt[multi_edit][" + multi_edit + "][" + urlField + "]" +
        " id=salt_" + target.id + " placeholder='" + PMA_messages.strEncryptionKey + "'>";

    //If encrypting or decrypting functions that take salt as input is selected append the new textbox for salt
    if (target.value === 'AES_ENCRYPT' ||
            target.value === 'AES_DECRYPT' ||
            target.value === 'DES_ENCRYPT' ||
            target.value === 'DES_DECRYPT' ||
            target.value === 'ENCRYPT') {
        if (!($("#salt_" + target.id).length)) {
            $this_input.after(new_salt_box);
        }
    } else {
        //Remove the textbox for salt
        $('#salt_' + target.id).prev('br').remove();
        $("#salt_" + target.id).remove();
    }

    if (target.value === 'AES_DECRYPT' || target.value === 'AES_ENCRYPT') {
        if ($this_input.data('type') !== 'HEX') {
            $('#' + target.id).addClass('invalid_value');
            return false;
        }
    } else if(target.value === 'MD5' &&
        typeof $this_input.data('maxlength') !== 'undefined' &&
        $this_input.data('maxlength') < 32
    ) {
        $('#' + target.id).addClass('invalid_value');
        return false;
    } else {
        $('#' + target.id).removeClass('invalid_value');
    }

    // Unchecks the corresponding "NULL" control
    $("input[name='fields_null[multi_edit][" + multi_edit + "][" + urlField + "]']").prop('checked', false);

    // Unchecks the Ignore checkbox for the current row
    $("input[name='insert_ignore_" + multi_edit + "']").prop('checked', false);

    // Does this field come from datepicker?
    if ($this_input.data('comes_from') == 'datepicker') {
        // Yes, so do not validate because the final value is not yet in
        // the field and hopefully the datepicker returns a valid date+time
        $this_input.removeClass("invalid_value");
        return true;
    }

    if (target.name.substring(0, 6) == "fields") {
        // validate for date time
        if (theType == "datetime" || theType == "time" || theType == "date" || theType == "timestamp") {
            $this_input.removeClass("invalid_value");
            var dt_value = $this_input.val();
            if (theType == "date") {
                if (! isDate(dt_value)) {
                    $this_input.addClass("invalid_value");
                    return false;
                }
            } else if (theType == "time") {
                if (! isTime(dt_value)) {
                    $this_input.addClass("invalid_value");
                    return false;
                }
            } else if (theType == "datetime" || theType == "timestamp") {
                var tmstmp = false;
                if (dt_value == "CURRENT_TIMESTAMP") {
                    return true;
                }
                if (theType == "timestamp") {
                    tmstmp = true;
                }
                if (dt_value == "0000-00-00 00:00:00") {
                    return true;
                }
                var dv = dt_value.indexOf(" ");
                if (dv == -1) {
                    $this_input.addClass("invalid_value");
                    return false;
                } else {
                    if (! (isDate(dt_value.substring(0, dv), tmstmp) && isTime(dt_value.substring(dv + 1)))) {
                        $this_input.addClass("invalid_value");
                        return false;
                    }
                }
            }
        }
        //validation for integer type
        if ($this_input.data('type') === 'INT') {
            var min = $this_input.attr('min');
            var max = $this_input.attr('max');
            var value = $this_input.val();
            $this_input.removeClass("invalid_value");
            if (isNaN(value) || BigInts.compare(value, min) < 0 ||
                BigInts.compare(value, max) > 0
            ) {
                $this_input.addClass("invalid_value");
                return false;
            }
            //validation for CHAR types
        } else if ($this_input.data('type') === 'CHAR') {
            var len = $this_input.val().length;
            var maxlen = $this_input.data('maxlength');
            $this_input.removeClass("invalid_value");
            if (typeof maxlen !== 'undefined' && len > maxlen) {
                $this_input.addClass("invalid_value");
                return false;
            }
            // validate binary & blob types
        } else if ($this_input.data('type') === 'HEX') {
            $this_input.removeClass("invalid_value");
            if ($this_input.val().match(/^[a-f0-9]*$/i) === null) {
                $this_input.addClass("invalid_value");
                return false;
            }
        }
    }
}
 /* End of fields validation*/

/**
 * Applies the selected function to all rows to be inserted.
 *
 * @param  string currId       Current ID of the row
 * @param  string functionName Name of the function
 * @param  bool   copySalt     Whether to copy salt or not
 * @param  string salt         Salt value
 * @param  object targetRows   Target rows
 *
 * @return void
 */
function applyFunctionToAllRows(currId, functionName, copySalt, salt, targetRows)
{
    targetRows.each(function () {
        var currentRowNum = /\d/.exec($(this).find("input[name*='fields_name']").attr("name"));

        // Append the function select list.
        var targetSelectList = $(this).find("select[name*='funcs[multi_edit]']");

        if (targetSelectList.attr("id") === currId) {
            return;
        }
        targetSelectList.find("option").filter(function () {
            return $(this).text() === functionName;
        }).attr("selected","selected");

        // Handle salt field.
        if (functionName === 'AES_ENCRYPT' ||
                functionName === 'AES_DECRYPT' ||
                functionName === 'DES_ENCRYPT' ||
                functionName === 'DES_DECRYPT' ||
                functionName === 'ENCRYPT') {
            if ($("#salt_" + targetSelectList.attr("id")).length === 0) {
                // Get hash value.
                var hashed_value = targetSelectList.attr("name").match(/\[multi\_edit\]\[\d\]\[(.*)\]/);
                //To generate the textbox that can take the salt
                var new_salt_box = "<br><input type=text name=salt[multi_edit][" + currentRowNum + "][" + hashed_value[1] + "]" +
                    " id=salt_" + targetSelectList.attr("id") + " placeholder='" + PMA_messages.strEncryptionKey + "'>";
                targetSelectList.parent().next("td").next("td").find("input[name*='fields']").after(new_salt_box);
            }

            if (copySalt) {
                $("#salt_" + targetSelectList.attr("id")).val(salt);
            }
        } else {
            var id = targetSelectList.attr("id");
            if ($("#salt_" + id).length) {
                $("#salt_" + id).remove();
            }
        }
    });
}


/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_change.js', function () {
    $(document).off('click', 'span.open_gis_editor');
    $(document).off('click', "input[name='gis_data[save]']");
    $(document).off('click', 'input.checkbox_null');
    $('select[name="submit_type"]').unbind('change');
    $(document).off('change', "#insert_rows");
    $(document).off('click', "select[name*='funcs']");
});

/**
 * Ajax handlers for Change Table page
 *
 * Actions Ajaxified here:
 * Submit Data to be inserted into the table.
 * Restart insertion with 'N' rows.
 */
AJAX.registerOnload('tbl_change.js', function () {
    $.datepicker.initialized = false;

    $(document).on('click', 'span.open_gis_editor', function (event) {
        event.preventDefault();

        var $span = $(this);
        // Current value
        var value = $span.parent('td').children("input[type='text']").val();
        // Field name
        var field = $span.parents('tr').children('td:first').find("input[type='hidden']").val();
        // Column type
        var type = $span.parents('tr').find('span.column_type').text();
        // Names of input field and null checkbox
        var input_name = $span.parent('td').children("input[type='text']").attr('name');
        //Token
        var token = $("input[name='token']").val();

        openGISEditor();
        if (!gisEditorLoaded) {
            loadJSAndGISEditor(value, field, type, input_name, token);
        } else {
            loadGISEditor(value, field, type, input_name, token);
        }
    });

    /**
     * Uncheck the null checkbox as geometry data is placed on the input field
     */
    $(document).on('click', "input[name='gis_data[save]']", function (event) {
        var input_name = $('form#gis_data_editor_form').find("input[name='input_name']").val();
        var $null_checkbox = $("input[name='" + input_name + "']").parents('tr').find('.checkbox_null');
        $null_checkbox.prop('checked', false);
    });

    /**
     * Handles all current checkboxes for Null; this only takes care of the
     * checkboxes on currently displayed rows as the rows generated by
     * "Continue insertion" are handled in the "Continue insertion" code
     *
     */
    $(document).on('click', 'input.checkbox_null', function (e) {
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
    $('select[name="submit_type"]').bind('change', function (e) {
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
                if (thisElemSubmitTypeVal == 'insert'
                    || thisElemSubmitTypeVal == 'insertignore'
                    || thisElemSubmitTypeVal == 'showinsert'
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
    $(document).on('change', "#insert_rows", function (event) {
        event.preventDefault();
        /**
         * @var columnCount   Number of number of columns table has.
         */
        var columnCount = $("table.insertRowTable:first").find("tr").has("input[name*='fields_name']").length;
        /**
         * @var curr_rows   Number of current insert rows already on page
         */
        var curr_rows = $("table.insertRowTable").length;
        /**
         * @var target_rows Number of rows the user wants
         */
        var target_rows = $("#insert_rows").val();

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
                if ($this_element.is("select[name*='funcs']")) {
                    var this_id = $this_element.attr("id");
                    var id_parts = this_id.split(/\_/);
                    var old_id_index = id_parts[1];
                    var prevSelectedValue = $("#field_" + old_id_index + "_1").val();
                    var new_id_index = parseInt(old_id_index) + columnCount;
                    var new_id = 'field_' + new_id_index + '_1';
                    $this_element.attr('id', new_id);
                    $this_element.find("option").filter(function () {
                        return $(this).text() === prevSelectedValue;
                    }).attr("selected","selected");

                    // If salt field is there then update its id.
                    var nextSaltInput = $this_element.parent().next("td").next("td").find("input[name*='salt']");
                    if (nextSaltInput.length !== 0) {
                        nextSaltInput.attr("id", "salt_" + new_id);
                    }
                }

                // handle input text fields and textareas
                if ($this_element.is('.textfield') || $this_element.is('.char')) {
                    // do not remove the 'value' attribute for ENUM columns
                    if ($this_element.closest('tr').find('span.column_type').html() != 'enum') {
                        $this_element.val($this_element.closest('tr').find('span.default_value').html());
                    }
                    $this_element
                        .unbind('change')
                        // Remove onchange attribute that was placed
                        // by tbl_change.php; it refers to the wrong row index
                        .attr('onchange', null)
                        // Keep these values to be used when the element
                        // will change
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .bind('change', function (e) {
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
                        .unbind('click')
                        // Keep these values to be used when the element
                        // will be clicked
                        .data('hashed_field', hashed_field)
                        .data('new_row_index', new_row_index)
                        .bind('click', function (e) {
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
                var $last_row = $("#insertForm").find(".insertRowTable:last");

                // need to access this at more than one level
                // (also needs improvement because it should be calculated
                //  just once per cloned row, not once per column)
                var new_row_index = 0;

                //Clone the insert tables
                $last_row
                .clone(true, true)
                .insertBefore("#actions_panel")
                .find('input[name*=multi_edit],select[name*=multi_edit],textarea[name*=multi_edit]')
                .each(tempIncrementIndex)
                .end()
                .find('.foreign_values_anchor')
                .each(tempReplaceAnchor);

                //Insert/Clone the ignore checkboxes
                if (curr_rows == 1) {
                    $('<input id="insert_ignore_1" type="checkbox" name="insert_ignore_1" checked="checked" />')
                    .insertBefore("table.insertRowTable:last")
                    .after('<label for="insert_ignore_1">' + PMA_messages.strIgnore + '</label>');
                } else {

                    /**
                     * @var $last_checkbox   Object reference to the last checkbox in #insertForm
                     */
                    var $last_checkbox = $("#insertForm").children('input:checkbox:last');

                    /** name of {@link $last_checkbox} */
                    var last_checkbox_name = $last_checkbox.attr('name');
                    /** index of {@link $last_checkbox} */
                    var last_checkbox_index = parseInt(last_checkbox_name.match(/\d+/), 10);
                    /** name of new {@link $last_checkbox} */
                    var new_name = last_checkbox_name.replace(/\d+/, last_checkbox_index + 1);

                    $('<br/><div class="clearfloat"></div>')
                    .insertBefore("table.insertRowTable:last");

                    $last_checkbox
                    .clone()
                    .attr({'id': new_name, 'name': new_name})
                    .prop('checked', true)
                    .insertBefore("table.insertRowTable:last");

                    $('label[for^=insert_ignore]:last')
                    .clone()
                    .attr('for', new_name)
                    .insertBefore("table.insertRowTable:last");

                    $('<br/>')
                    .insertBefore("table.insertRowTable:last");
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
                $(this).attr('id', "field_" + tabindex + "_3");
            });
            $('.control_at_footer')
            .each(function () {
                tabindex++;
                $(this).attr('tabindex', tabindex);
            });
        } else if (curr_rows > target_rows) {
            while (curr_rows > target_rows) {
                $("input[id^=insert_ignore]:last")
                .nextUntil("fieldset")
                .addBack()
                .remove();
                curr_rows--;
            }
        }
        // Add all the required datepickers back
        addDateTimePicker();
    });

    /**
     * @var $function_option_dialog object holds dialog for selected function options.
     */
    var $function_option_dialog = null;

    PMA_tooltip(
        $("select[name*='funcs']"),
        'select',
        PMA_messages.strFunctionHint
    );

    $(document).on('click', "select[name*='funcs']", function (event) {
        if (! event.shiftKey) {
            return false;
        }

        // Name of selected function.
        var functionName = $(this).find("option:selected").html();
        var currId = $(this).attr("id");
        // Name of column.
        var columnName = $(this).closest("tr").find("input[name*='fields_name']").val();
        var targetRows = $("tr").has("input[value='" + columnName + "']");
        var salt;
        var copySalt = false;

        if (functionName === 'AES_ENCRYPT' ||
                functionName === 'AES_DECRYPT' ||
                functionName === 'DES_ENCRYPT' ||
                functionName === 'DES_DECRYPT' ||
                functionName === 'ENCRYPT') {
            // Dialog title.
            var title = functionName;
            // Dialog buttons functions.
            var buttonOptions = {};
            buttonOptions[PMA_messages.strYes] = function () {
                // Go function.
                copySalt = true;
                salt = $("#salt_" + currId).val();
                applyFunctionToAllRows(currId, functionName, copySalt, salt, targetRows);
                $(this).dialog("close");
            };
            buttonOptions[PMA_messages.strNo] = function () {
                copySalt = false;
                salt = "";
                applyFunctionToAllRows(currId, functionName, copySalt, salt, targetRows);
                $(this).dialog("close");
            };

            // Contents of dialog.
            var dialog = "<div>" +
                        "<fieldset>" +
                        "<span style='font-weight: bold;'>" +
                        PMA_messages.strCopyEncryptionKey +
                        "</fieldset>" +
                        "</div>";

            // Show the dialog
            var width = parseInt(
                (parseInt($('html').css('font-size'), 10) / 13) * 340,
                10
            );
            if (! width) {
                width = 340;
            }

            $function_option_dialog = $(dialog).dialog({
                minWidth: width,
                modal: true,
                title: title,
                buttons: buttonOptions,
                resizable: false,
                open: function () {
                    // Focus the "Go" button after opening the dialog
                    $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button:first').focus();
                },
                close: function () {
                    $(this).remove();
                }
            });
        }

        applyFunctionToAllRows(currId, functionName, copySalt, "", targetRows);
    });
});

function changeValueFieldType(elem, searchIndex)
{
    var fieldsValue = $("select#fieldID_" + searchIndex);
    if (0 === fieldsValue.size()) {
        return;
    }

    var type = $(elem).val();
    if ('IN (...)' == type ||
        'NOT IN (...)' == type ||
        'BETWEEN' == type ||
        'NOT BETWEEN' == type
    ) {
        $("#fieldID_" + searchIndex).attr('multiple', '');
    } else {
        $("#fieldID_" + searchIndex).removeAttr('multiple');
    }
}
