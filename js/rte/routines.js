/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * @var    param_template    This variable contains the template for one row
 *                           of the parameters table that is attached to the
 *                           dialog when a new parameter is added.
 */
RTE.param_template = '';

/**
 * Overriding the postDialogShow() function defined in common.js
 *
 * @param data   JSON-encoded data from the ajax request
 */
RTE.postDialogShow = function (data) {
    // Cache the template for a parameter table row
    RTE.param_template = data.param_template;
    // Make adjustments in the dialog to make it AJAX compatible
    $('td.routine_param_remove').show();
    $('input[name=routine_removeparameter]').remove();
    $('input[name=routine_addparameter]').css('width', '100%');
    // Enable/disable the 'options' dropdowns for parameters as necessary
    $('table.routine_params_table').last().find('th[colspan=2]').attr('colspan', '1');
    $('table.routine_params_table').last().find('tr').has('td').each(function () {
        RTE.setOptionsForParameter(
            $(this).find('select[name^=item_param_type]'),
            $(this).find('input[name^=item_param_length]'),
            $(this).find('select[name^=item_param_opts_text]'),
            $(this).find('select[name^=item_param_opts_num]')
        );
    });
    // Enable/disable the 'options' dropdowns for
    // function return value as necessary
    RTE.setOptionsForParameter(
        $('table.rte_table').last().find('select[name=item_returntype]'),
        $('table.rte_table').last().find('input[name=item_returnlength]'),
        $('table.rte_table').last().find('select[name=item_returnopts_text]'),
        $('table.rte_table').last().find('select[name=item_returnopts_num]')
    );
}; // end RTE.postDialogShow()

/**
 * Overriding the validateCustom() function defined in common.js
 */
RTE.validateCustom = function () {
    /**
     * @var   isSuccess   Stores the outcome of the validation
     */
    var isSuccess = true;
    /**
     * @var   inputname   The value of the "name" attribute for
     *                    the field that is being processed
     */
    var inputname = '';
    $('table.routine_params_table').last().find('tr').each(function () {
        // Every parameter of a routine must have
        // a non-empty direction, name and type
        if (isSuccess) {
            $(this).find(':input').each(function () {
                inputname = $(this).attr('name');
                if (inputname.substr(0, 14) === 'item_param_dir' ||
                    inputname.substr(0, 15) === 'item_param_name' ||
                    inputname.substr(0, 15) === 'item_param_type') {
                    if ($(this).val() === '') {
                        $(this).focus();
                        isSuccess = false;
                        return false;
                    }
                }
            });
        } else {
            return false;
        }
    });
    if (! isSuccess) {
        alert(PMA_messages['strFormEmpty']);
        return false;
    }
    $('table.routine_params_table').last().find('tr').each(function () {
        // SET, ENUM, VARCHAR and VARBINARY fields must have length/values
        var $inputtyp = $(this).find('select[name^=item_param_type]');
        var $inputlen = $(this).find('input[name^=item_param_length]');
        if ($inputtyp.length && $inputlen.length) {
            if (($inputtyp.val() === 'ENUM' || $inputtyp.val() === 'SET' || $inputtyp.val().substr(0, 3) === 'VAR')
               && $inputlen.val() === '') {
                $inputlen.focus();
                isSuccess = false;
                return false;
            }
        }
    });
    if (! isSuccess) {
        alert(PMA_messages['strFormEmpty']);
        return false;
    }
    if ($('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
        // The length/values of return variable for functions must
        // be set, if the type is SET, ENUM, VARCHAR or VARBINARY.
        var $returntyp = $('select[name=item_returntype]');
        var $returnlen = $('input[name=item_returnlength]');
        if (($returntyp.val() === 'ENUM' || $returntyp.val() === 'SET' || $returntyp.val().substr(0, 3) === 'VAR')
           && $returnlen.val() === '') {
            $returnlen.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    }
    if ($('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
        // A function must contain a RETURN statement in its definition
        if ($('table.rte_table').find('textarea[name=item_definition]').val().toUpperCase().indexOf('RETURN') < 0) {
            RTE.syntaxHiglighter.focus();
            alert(PMA_messages['MissingReturn']);
            return false;
        }
    }
    return true;
}; // end RTE.validateCustom()

/**
 * Enable/disable the "options" dropdown and "length" input for
 * parameters and the return variable in the routine editor
 * as necessary.
 *
 * @param $type    a jQuery object containing the reference
 *                    to the "Type" dropdown box
 * @param $len     a jQuery object containing the reference
 *                    to the "Length" input box
 * @param $text    a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of text type
 * @param $num     a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of numeric type
 */
RTE.setOptionsForParameter = function ($type, $len, $text, $num) {
    /**
     * @var   $no_opts   a jQuery object containing the reference
     *                   to an element to be displayed when no
     *                   options are available
     */
    var $no_opts = $text.parent().parent().find('.no_opts');
    /**
     * @var   $no_len    a jQuery object containing the reference
     *                   to an element to be displayed when no
     *                  "length/values" field is available
     */
    var $no_len  = $len.parent().parent().find('.no_len');

    // Process for parameter options
    switch ($type.val()) {
    case 'TINYINT':
    case 'SMALLINT':
    case 'MEDIUMINT':
    case 'INT':
    case 'BIGINT':
    case 'DECIMAL':
    case 'FLOAT':
    case 'DOUBLE':
    case 'REAL':
        $text.parent().hide();
        $num.parent().show();
        $no_opts.hide();
        break;
    case 'TINYTEXT':
    case 'TEXT':
    case 'MEDIUMTEXT':
    case 'LONGTEXT':
    case 'CHAR':
    case 'VARCHAR':
    case 'SET':
    case 'ENUM':
        $text.parent().show();
        $num.parent().hide();
        $no_opts.hide();
        break;
    default:
        $text.parent().hide();
        $num.parent().hide();
        $no_opts.show();
        break;
    }
    // Process for parameter length
    switch ($type.val()) {
    case 'DATE':
    case 'DATETIME':
    case 'TIME':
    case 'TINYBLOB':
    case 'TINYTEXT':
    case 'BLOB':
    case 'TEXT':
    case 'MEDIUMBLOB':
    case 'MEDIUMTEXT':
    case 'LONGBLOB':
    case 'LONGTEXT':
        $text.closest('tr').find('a:first').hide();
        $len.parent().hide();
        $no_len.show();
        break;
    default:
        if ($type.val() == 'ENUM' || $type.val() == 'SET') {
            $text.closest('tr').find('a:first').show();
        } else {
            $text.closest('tr').find('a:first').hide();
        }
        $len.parent().show();
        $no_len.hide();
        break;
    }
}; // end RTE.setOptionsForParameter()

/**
 * Attach Ajax event handlers for the Routines functionalities.
 *
 * @see $cfg['AjaxEnable']
 */
$(function () {
    /**
     * Attach Ajax event handlers for the "Add parameter to routine" functionality.
     */
    $('input[name=routine_addparameter]').live('click', function (event) {
        event.preventDefault();
        /**
         * @var    $routine_params_table    jQuery object containing the reference
         *                                  to the routine parameters table.
         */
        var $routine_params_table = $('table.routine_params_table').last();
        /**
         * @var    $new_param_row    A string containing the HTML code for the
         *                           new row for the routine paramaters table.
         */
        var new_param_row = RTE.param_template.replace(/%s/g, $routine_params_table.find('tr').length - 1);
        // Append the new row to the parameters table
        $routine_params_table.append(new_param_row);
        // Make sure that the row is correctly shown according to the type of routine
        if ($('table.rte_table').find('select[name=item_type]').val() === 'FUNCTION') {
            $('tr.routine_return_row').show();
            $('td.routine_direction_cell').hide();
        }
        /**
         * @var    $newrow    jQuery object containing the reference to the newly
         *                    inserted row in the routine parameters table.
         */
        var $newrow = $('table.routine_params_table').last().find('tr').has('td').last();
        // Enable/disable the 'options' dropdowns for parameters as necessary
        RTE.setOptionsForParameter(
            $newrow.find('select[name^=item_param_type]'),
            $newrow.find('input[name^=item_param_length]'),
            $newrow.find('select[name^=item_param_opts_text]'),
            $newrow.find('select[name^=item_param_opts_num]')
        );
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Remove parameter from routine" functionality.
     */
    $('a.routine_param_remove_anchor').live('click', function (event) {
        event.preventDefault();
        $(this).parent().parent().remove();
        // After removing a parameter, the indices of the name attributes in
        // the input fields lose the correct order and need to be reordered.
        /**
         * @var    index    Counter used for reindexing the input
         *                  fields in the routine parameters table.
         */
        var index = 0;
        $('table.routine_params_table').last().find('tr').has('td').each(function () {
            $(this).find(':input').each(function () {
                /**
                 * @var    inputname    The value of the name attribute of
                 *                      the input field being reindexed.
                 */
                var inputname = $(this).attr('name');
                if (inputname.substr(0, 14) === 'item_param_dir') {
                    $(this).attr('name', inputname.substr(0, 14) + '[' + index + ']');
                } else if (inputname.substr(0, 15) === 'item_param_name') {
                    $(this).attr('name', inputname.substr(0, 15) + '[' + index + ']');
                } else if (inputname.substr(0, 15) === 'item_param_type') {
                    $(this).attr('name', inputname.substr(0, 15) + '[' + index + ']');
                } else if (inputname.substr(0, 17) === 'item_param_length') {
                    $(this).attr('name', inputname.substr(0, 17) + '[' + index + ']');
                    $(this).attr('id', 'item_param_length_' + index);
                } else if (inputname.substr(0, 20) === 'item_param_opts_text') {
                    $(this).attr('name', inputname.substr(0, 20) + '[' + index + ']');
                } else if (inputname.substr(0, 19) === 'item_param_opts_num') {
                    $(this).attr('name', inputname.substr(0, 19) + '[' + index + ']');
                }
            });
            index++;
        });
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Change routine type"
     * functionality in the routines editor, so that the correct
     * fields are shown in the editor when changing the routine type
     */
    $('select[name=item_type]').live('change', function () {
        $('tr.routine_return_row, td.routine_direction_cell').toggle();
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Change parameter type"
     * functionality in the routines editor, so that the correct
     * option/length fields, if any, are shown when changing
     * a parameter type
     */
    $('select[name^=item_param_type]').live('change', function () {
        /**
         * @var    $row    jQuery object containing the reference to
         *                 a row in the routine parameters table
         */
        var $row = $(this).parents('tr').first();
        RTE.setOptionsForParameter(
            $row.find('select[name^=item_param_type]'),
            $row.find('input[name^=item_param_length]'),
            $row.find('select[name^=item_param_opts_text]'),
            $row.find('select[name^=item_param_opts_num]')
        );
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Change the type of return
     * variable of function" functionality, so that the correct fields,
     * if any, are shown when changing the function return type type
     */
    $('select[name=item_returntype]').live('change', function () {
        RTE.setOptionsForParameter(
            $('table.rte_table').find('select[name=item_returntype]'),
            $('table.rte_table').find('input[name=item_returnlength]'),
            $('table.rte_table').find('select[name=item_returnopts_text]'),
            $('table.rte_table').find('select[name=item_returnopts_num]')
        );
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the Execute routine functionality.
     */
    $('a.ajax_exec_anchor').live('click', function (event) {
        event.preventDefault();
        /**
         * @var    $msg    jQuery object containing the reference to
         *                 the AJAX message shown to the user
         */
        var $msg = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                // If 'data.dialog' is true we show a dialog with a form
                // to get the input parameters for routine, otherwise
                // we just show the results of the query
                if (data.dialog) {
                    // Define the function that is called when
                    // the user presses the "Go" button
                    RTE.buttonOptions[PMA_messages['strGo']] = function () {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('form.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
                        $.post('db_routines.php', data, function (data) {
                            if (data.success === true) {
                                // Routine executed successfully
                                PMA_ajaxRemoveMessage($msg);
                                PMA_slidingMessage(data.message);
                                $ajaxDialog.dialog('close');
                            } else {
                                PMA_ajaxShowMessage(data.error, false);
                            }
                        });
                    };
                    RTE.buttonOptions[PMA_messages['strClose']] = function () {
                        $(this).dialog("close");
                    };
                    /**
                     * Display the dialog to the user
                     */
                    $ajaxDialog = $('<div>' + data.message + '</div>').dialog({
                                    width: 650,
                                    buttons: RTE.buttonOptions,
                                    title: data.title,
                                    modal: true,
                                    close: function () {
                                        $(this).remove();
                                    }
                            });
                    $ajaxDialog.find('input[name^=params]').first().focus();
                    /**
                     * Attach the datepickers to the relevant form fields
                     */
                    $ajaxDialog.find('input.datefield, input.datetimefield').each(function () {
                        PMA_addDatepicker($(this).css('width', '95%'));
                    });
                } else {
                    // Routine executed successfully
                    PMA_slidingMessage(data.message);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    }); // end $.live()
}); // end of $() for the Routine Functionalities
