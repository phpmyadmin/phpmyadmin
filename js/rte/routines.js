/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * @var    param_template    This variable contains the template for one row
 *                           of the parameters table that is attached to the
 *                           dialog when a new parameter is added.
 */
RTE.param_template = '';

RTE.postDialogShow = function (data) {
    // Cache the template for a parameter table row
    RTE.param_template = data.param_template;
    // Make adjustments in the dialog to make it AJAX compatible
    $('.routine_param_remove').show();
    $('input[name=routine_removeparameter]').remove();
    $('input[name=routine_addparameter]').css('width', '100%');
    // Enable/disable the 'options' dropdowns for parameters as necessary
    $('.routine_params_table').last().find('th[colspan=2]').attr('colspan', '1');
    $('.routine_params_table').last().find('tr').has('td').each(function() {
        RTE.setOptionsForParameter(
            $(this).find('select[name^=routine_param_type]'),
            $(this).find('input[name^=routine_param_length]'),
            $(this).find('select[name^=routine_param_opts_text]'),
            $(this).find('select[name^=routine_param_opts_num]')
        );
    });
    // Enable/disable the 'options' dropdowns for function return value as necessary
    RTE.setOptionsForParameter(
        $('.rte_table').last().find('select[name=routine_returntype]'),
        $('.rte_table').last().find('input[name=routine_returnlength]'),
        $('.rte_table').last().find('select[name=routine_returnopts_text]'),
        $('.rte_table').last().find('select[name=routine_returnopts_num]')
    );
};

RTE.validateCustom = function () {
    var isSuccess = true;
    $('.routine_params_table').last().find('tr').each(function() {
        if (isSuccess) {
            $(this).find(':input').each(function() {
                inputname = $(this).attr('name');
                if (inputname.substr(0, 17) == 'routine_param_dir' ||
                    inputname.substr(0, 18) == 'routine_param_name' ||
                    inputname.substr(0, 18) == 'routine_param_type') {
                    if ($(this).val() == '') {
                        $(this).focus();
                        isSuccess = false;
                        return false;
                    }
                }
            });
        }
    });
    if (! isSuccess) {
        alert(PMA_messages['strFormEmpty']);
        return false;
    }
    // SET, ENUM, VARCHAR and VARBINARY fields must have length/values
    $('.routine_params_table').last().find('tr').each(function() {
        var $inputtyp = $(this).find('select[name^=routine_param_type]');
        var $inputlen = $(this).find('input[name^=routine_param_length]');
        if ($inputtyp.length && $inputlen.length) {
            if (($inputtyp.val() == 'ENUM' || $inputtyp.val() == 'SET' || $inputtyp.val().substr(0,3) == 'VAR')
               && $inputlen.val() == '') {
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
    if ($('select[name=routine_type]').find(':selected').val() == 'FUNCTION') {
        // The length/values of return variable for functions must
        // be set, if the type is SET, ENUM, VARCHAR or VARBINARY.
        var $returntyp = $('select[name=routine_returntype]');
        var $returnlen = $('input[name=routine_returnlength]');
        if (($returntyp.val() == 'ENUM' || $returntyp.val() == 'SET' || $returntyp.val().substr(0,3) == 'VAR')
           && $returnlen.val() == '') {
            $returnlen.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    }
    if ($('select[name=routine_type]').find(':selected').val() == 'FUNCTION') {
        if ($('.rte_table').find('textarea[name=item_definition]').val().toUpperCase().indexOf('RETURN') < 0) {
            RTE.syntaxHiglighter.focus();
            alert(PMA_messages['MissingReturn']);
            return false;
        }
    }
    return isSuccess;
};

/**
 * Enable/disable the "options" dropdown and "length" input for
 * parameters and the return variable in the routine editor
 * as necessary.
 *
 * @param    $type    a jQuery object containing the reference
 *                    to the "Type" dropdown box
 * @param    $len     a jQuery object containing the reference
 *                    to the "Length" input box
 * @param    $text    a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of text type
 * @param    $num     a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of numeric type
 */
RTE.setOptionsForParameter = function($type, $len, $text, $num) {
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
        $text.show();
        $num.parent().hide();
        break;
    default:
        $text.parent().show();
        $text.hide();
        $num.parent().hide();
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
        $len.hide();
        break;
    default:
        $len.show();
        break;
    }
};

/**
 * Attach Ajax event handlers for the Routines functionalities.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    /**
     * Attach Ajax event handlers for the "Add parameter to routine" functionality.
     *
     * @see $cfg['AjaxEnable']
     */
    $('input[name=routine_addparameter]').live('click', function(event) {
        event.preventDefault();
        /**
         * @var    $routine_params_table    jQuery object containing the reference
         *                                  to the routine parameters table.
         */
        var $routine_params_table = $('.routine_params_table').last();
        /**
         * @var    $new_param_row    A string containing the HTML code for the
         *                           new row for the routine paramaters table.
         */
        var new_param_row = RTE.param_template.replace(/%s/g, $routine_params_table.find('tr').length-1);
        $routine_params_table.append(new_param_row);
        if ($('.rte_table').find('select[name=routine_type]').val() == 'FUNCTION') {
            $('.routine_return_row').show();
            $('.routine_direction_cell').hide();
        }
        /**
         * @var    $newrow    jQuery object containing the reference to the newly
         *                    inserted row in the routine parameters table.
         */
        var $newrow = $('.routine_params_table').last().find('tr').has('td').last();
        RTE.setOptionsForParameter(
            $newrow.find('select[name^=routine_param_type]'),
            $newrow.find('input[name^=routine_param_length]'),
            $newrow.find('select[name^=routine_param_opts_text]'),
            $newrow.find('select[name^=routine_param_opts_num]')
        );
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Remove parameter from routine" functionality.
     *
     * @see $cfg['AjaxEnable']
     */
    $('.routine_param_remove_anchor').live('click', function (event) {
        event.preventDefault();
        $(this).parent().parent().remove();
        // After removing a parameter, the indices of the name attributes in
        // the input fields lose the correct order and need to be reordered.
        /**
         * @var    index    Counter used for reindexing the input
         *                  fields in the routine parameters table.
         */
        var index = 0;
        $('.routine_params_table').last().find('tr').has('td').each(function() {
            $(this).find(':input').each(function() {
                /**
                 * @var    inputname    The value of the name attribute of
                 *                      the input field being reindexed.
                 */
                var inputname = $(this).attr('name');
                if (inputname.substr(0, 17) == 'routine_param_dir') {
                    $(this).attr('name', inputname.substr(0, 17) + '[' + index + ']');
                } else if (inputname.substr(0, 18) == 'routine_param_name') {
                    $(this).attr('name', inputname.substr(0, 18) + '[' + index + ']');
                } else if (inputname.substr(0, 18) == 'routine_param_type') {
                    $(this).attr('name', inputname.substr(0, 18) + '[' + index + ']');
                } else if (inputname.substr(0, 20) == 'routine_param_length') {
                    $(this).attr('name', inputname.substr(0, 20) + '[' + index + ']');
                } else if (inputname.substr(0, 23) == 'routine_param_opts_text') {
                    $(this).attr('name', inputname.substr(0, 23) + '[' + index + ']');
                } else if (inputname.substr(0, 22) == 'routine_param_opts_num') {
                    $(this).attr('name', inputname.substr(0, 22) + '[' + index + ']');
                }
            });
            index++;
        });
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Change routine type" functionality.
     *
     * @see $cfg['AjaxEnable']
     */
    $('select[name=routine_type]').live('change', function() {
        $('.routine_return_row, .routine_direction_cell').toggle();
    }); // end $.live()

    /**
     * Attach Ajax event handlers for the "Change parameter type" functionality.
     *
     * @see $cfg['AjaxEnable']
     */
    $('select[name^=routine_param_type]').live('change', function() {
        /**
         * @var    $row    jQuery object containing the reference to
         *                 a row in the routine parameters table.
         */
        var $row = $(this).parents('tr').first();
        RTE.setOptionsForParameter(
            $row.find('select[name^=routine_param_type]'),
            $row.find('input[name^=routine_param_length]'),
            $row.find('select[name^=routine_param_opts_text]'),
            $row.find('select[name^=routine_param_opts_num]')
        );
    });

    /**
     * Attach Ajax event handlers for the "Change the type of return
     * variable of function" functionality.
     *
     * @see $cfg['AjaxEnable']
     */
    $('select[name=routine_returntype]').live('change', function() {
        RTE.setOptionsForParameter(
            $('.rte_table').find('select[name=routine_returntype]'),
            $('.rte_table').find('input[name=routine_returnlength]'),
            $('.rte_table').find('select[name=routine_returnopts_text]'),
            $('.rte_table').find('select[name=routine_returnopts_num]')
        );
    });

    /**
     * Attach Ajax event handlers for the Execute routine functionality.
     *
     * @uses    PMA_ajaxShowMessage()
     * @uses    PMA_ajaxRemoveMessage()
     *
     * @see $cfg['AjaxEnable']
     */
    $('.ajax_exec_anchor').live('click', function(event) {
        event.preventDefault();
        /**
         * @var    $msg    jQuery object containing the reference to
         *                 the AJAX message shown to the user.
         */
        var $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxRemoveMessage($msg);
                if (data.dialog) {
                    RTE.buttonOptions[PMA_messages['strGo']] = function() {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
                        $.post('db_routines.php', data, function (data) {
                           if(data.success == true) {
                                // Routine executed successfully
                                PMA_ajaxRemoveMessage($msg);
                                PMA_slidingMessage(data.message);
                                $ajaxDialog.dialog('close');
                            } else {
                                PMA_ajaxShowMessage(data.error);
                            }
                        });
                    }
                    RTE.buttonOptions[PMA_messages['strClose']] = function() {
                        $(this).dialog("close");
                    }
                    /**
                     * Display the dialog to the user
                     */
                    $ajaxDialog = $('<div>'+data.message+'</div>').dialog({
                                    width: 650,  // TODO: make a better decision about the size
                                                 // of the dialog based on the size of the viewport
                                    buttons: RTE.buttonOptions,
                                    title: data.title,
                                    modal: true,
                                    close: function () {
                                        $(this).remove();
                                    }
                            });
                    $ajaxDialog.find('input[name^=params]').first().focus();
                    $ajaxDialog.find('.datefield, .datetimefield').each(function() {
                        PMA_addDatepicker($(this).css('width', '95%'));
                    });
                } else {
                    // Routine executed successfully
                    PMA_slidingMessage(data.message);
                }
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    });
}); // end of $(document).ready() for the Routine Functionalities
