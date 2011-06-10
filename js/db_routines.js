/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Validate routine editor form fields.
 *
 * @param    syntaxHiglighter    an object containing the reference to the
 *                               codemirror editor. This will be used to
 *                               focus the form on the codemirror editor
 *                               if it contains invalid data.
 */
function validateRoutineEditor(syntaxHiglighter) {
    /**
     * @var    inputname    Will contain the value of the name
     *                      attribute of input fields being checked.
     */
    var inputname = '';
    /**
     * @var    $elm    a jQuery object containing the reference
     *                 to an element that is being validated.
     */
    var $elm = null;
    /**
     * @var    isError    Stores the outcome of the validation.
     */
    var isError = false;

    $elm = $('.rte_table').last().find('input[name=routine_name]');
    if ($elm.val() == '') {
        $elm.focus();
        isError = true;
    }
    if (! isError) {
        $elm = $('.rte_table').find('textarea[name=routine_definition]');
        if ($elm.val() == '') {
            syntaxHiglighter.focus();
            isError = true;
        }
    }
    if (! isError) {
        $('.routine_params_table').last().find('tr').each(function() {
            if (! isError) {
                $(this).find(':input').each(function() {
                    inputname = $(this).attr('name');
                    if (inputname.substr(0, 17) == 'routine_param_dir' ||
                        inputname.substr(0, 18) == 'routine_param_name' ||
                        inputname.substr(0, 18) == 'routine_param_type') {
                        if ($(this).val() == '') {
                            $(this).focus();
                            isError = true;
                            return false;
                        }
                    }
                });
            }
        });
    }
    if (! isError) {
        // SET, ENUM, VARCHAR and VARBINARY fields must have length/values
        $('.routine_params_table').last().find('tr').each(function() {
            var $inputtyp = $(this).find('select[name^=routine_param_type]');
            var $inputlen = $(this).find('input[name^=routine_param_length]');
            if ($inputtyp.length && $inputlen.length) {
                if (($inputtyp.val() == 'ENUM' || $inputtyp.val() == 'SET' || $inputtyp.val().substr(0,3) == 'VAR')
                   && $inputlen.val() == '') {
                    $inputlen.focus();
                    isError = true;
                    return false;
                }
            }
        });
    }
    if (! isError && $('select[name=routine_type]').find(':selected').val() == 'FUNCTION') {
        // The length/values of return variable for functions must
        // be set, if the type is SET, ENUM, VARCHAR or VARBINARY.
        var $returntyp = $('select[name=routine_returntype]');
        var $returnlen = $('input[name=routine_returnlength]');
        if (($returntyp.val() == 'ENUM' || $returntyp.val() == 'SET' || $returntyp.val().substr(0,3) == 'VAR')
           && $returnlen.val() == '') {
            $returnlen.focus();
            isError = true;
        }
    }
    if (! isError) {
        return true;
    } else {
        alert(PMA_messages['strFormEmpty']);
        return false;
    }
} // end validateRoutineEditor()

/**
 * Enable/disable the "options" dropdown for parameters and
 * the return variable in the routine editor as necessary.
 *
 * @param    $type    a jQuery object containing the reference
 *                    to the "Type" dropdown box
 * @param    $text    a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of text type
 * @param    $num     a jQuery object containing the reference
 *                    to the dropdown box with options for
 *                    parameters of numeric type
 */
function setOptionsForParameter($type, $text, $num) {
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
}

/**
 * Attach Ajax event handlers for the Routines Editor.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    /**
     * @var    $ajaxDialog    jQuery object containing the reference to the
     *                        dialog that contains the routine editor.
     */
    var $ajaxDialog = null;
    /**
     * @var    param_template    This variable contains the template for one row
     *                           of the parameters table that is attached to the
     *                           dialog when a new parameter is added.
     */
    var param_template = '';
    /**
     * @var    syntaxHiglighter    Reference to the codemirror editor.
     */
    var syntaxHiglighter = null;

    /**
     * Attach Ajax event handlers for the Add/Edit routine functionality.
     *
     * @uses    PMA_ajaxShowMessage()
     * @uses    PMA_ajaxRemoveMessage()
     *
     * @see $cfg['AjaxEnable']
     */
    $('.add_routine_anchor, .edit_routine_anchor').live('click', function(event) {
        event.preventDefault();
        /**
         * @var    $edit_row    jQuery object containing the reference to
         *                      the row of the the routine being edited
         *                      from the list of routines .
         */
        var $edit_row = null;
        if ($(this).hasClass('edit_routine_anchor')) {
            // Remeber the row of the routine being edited for later,
            // so that if the edit is successful, we can replace the
            // row with info about the modified routine.
            $edit_row = $(this).parents('tr');
        }
        /**
         * @var    $msg    jQuery object containing the reference to
         *                 the AJAX message shown to the user.
         */
        var $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxRemoveMessage($msg);
                /**
                 * @var button_options  Object containing options for jQueryUI dialog buttons
                 */
                var button_options = {};
                button_options[PMA_messages['strGo']] = function() {
                    syntaxHiglighter.save();
                    // Validate editor and submit request, if passed.
                    if (validateRoutineEditor(syntaxHiglighter)) {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
                        $.post('db_routines.php', data, function (data) {
                            if(data.success == true) {
                                // Routine created successfully
                                PMA_ajaxShowMessage(data.message);
                                $('#js_query_display').html('<div style="margin-top: 1em;">' + data.sql_query + '</div>');
                                $ajaxDialog.dialog('close');
                                // If we are in 'edit' mode, we must remove the reference to the old row.
                                if (mode == 'edit') {
                                    $edit_row.remove();
                                }
                                // Insert the new row at the correct location in the list of routines
                                /**
                                 * @var    text    Contains the name of a routine from the list
                                 *                 that is used in comparisons to find the correct
                                 *                 location where to insert a new row.
                                 */
                                var text = '';
                                /**
                                 * @var    inserted    Whether a new has been inserted
                                 *                     in the list of routines or not.
                                 */
                                var inserted = false;
                                $('table.data').find('tr').each(function() {
                                    text = $(this).children('td').eq(0).find('strong').text().toUpperCase();
                                    if (text != '' && text > data.name) {
                                        $(this).before(data.new_row);
                                        inserted = true;
                                        return false;
                                    }
                                });
                                if (! inserted) {
                                    $('table.data').append(data.new_row);
                                }
                                // Now we have inserted the row at the correct position, but surely
                                // at least some row classes are wrong now. So we will itirate
                                // throught all rows and assign correct classes to them.
                                /**
                                 * @var    ct    Count of processed rows.
                                 */
                                var ct = 0;
                                $('table.data').find('tr').each(function() {
                                    if ($(this).has('th').length) {
                                        return true;
                                    }
                                    rowclass = (ct % 2 == 0) ? 'even' : 'odd';
                                    $(this).removeClass().addClass(rowclass);
                                    ct++;
                                });
                            } else {
                                PMA_ajaxShowMessage(data.error);
                            }
                        });
                    }
                } // end of function that handles the submission of the Editor
                button_options[PMA_messages['strClose']] = function() {
                    $(this).dialog("close");
                }
                /**
                 * Display the dialog to the user
                 */
                $ajaxDialog = $('<div style="font-size: 0.9em;">'+data.message+'</div>').dialog({
                                width: 700,  // TODO: make a better decision about the size
                                height: 550, // of the dialog based on the size of the viewport
                                buttons: button_options,
                                title: data.title,
                                modal: true,
                                close: function () {
                                    $(this).remove();
                                }
                        });
                /**
                 * @var    mode    Used to remeber whether the editor is in
                 *                 "Edit Routine" or "Add Routine" mode.
                 */
                var mode = 'add';
                if ($('input[name=routine_process_editroutine]').length > 0) {
                    mode = 'edit';
                }
                // Cache the template for a parameter table row
                param_template = data.param_template;
                // Make adjustments in the dialog to make it AJAX compatible
                /**
                 * @var    is_procedure    Used to make the PROCEDURE dropdown option selected
                 *                         if a procedure is being edited or created.
                 */
                var is_procedure = '';
                /**
                 * @var    is_function     Used to make the FUNCTION dropdown option selected
                 *                         if a function is being edited or created.
                 */
                var is_function = '';
                if (data.type == 'PROCEDURE') {
                    is_procedure = ' selected="selected"';
                } else if (data.type == 'FUNCTION') {
                    is_function = ' selected="selected"';
                }
                /**
                 * @var    new_type_cell    Contains HTML code that replaces the non-JS functionality
                 *                          used to switch the routine editor from procedure to function
                 *                          editing modes and back with a JS-aware dropdown.
                 */
                var new_type_cell = '<select name="routine_type">'
                                  + '<option value="PROCEDURE"' + is_procedure + '>PROCEDURE</option>'
                                  + '<option value="FUNCTION"' + is_function + '>FUNCTION</option>'
                                  + '</select>';
                $('.routine_changetype_cell').html(new_type_cell);
                $('.routine_param_remove').show();
                $('input[name=routine_removeparameter]').remove();
                $('input[name=routine_addparameter]').css('width', '100%');
                // Enable/disable the 'options' dropdowns for parameters as necessary
                $('.routine_params_table').last().find('th[colspan=2]').attr('colspan', '1');
                $('.routine_params_table').last().find('tr').has('td').each(function() {
                    setOptionsForParameter(
                        $(this).find('select[name^=routine_param_type]'),
                        $(this).find('select[name^=routine_param_opts_text]'),
                        $(this).find('select[name^=routine_param_opts_num]')
                    );
                });
                // Enable/disable the 'options' dropdowns for function return value as necessary
                setOptionsForParameter(
                    $('.rte_table').last().find('select[name=routine_returntype]'),
                    $('.rte_table').last().find('select[name=routine_returnopts_text]'),
                    $('.rte_table').last().find('select[name=routine_returnopts_num]')
                );
                // Attach syntax highlited editor to routine definition
                /**
                 * @var    $elm    jQuery object containing the reference to
                 *                 the "Routine Definition" textarea.
                 */
                var $elm = $('textarea[name=routine_definition]').last();
                /**
                 * @var    opts    Options to pass to the codemirror editor.
                 */
                var opts = {lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql"};
                syntaxHiglighter = CodeMirror.fromTextArea($elm[0], opts);
                // Hack to prevent the syntax highlighter from expanding beyond dialog boundries
                $('.CodeMirror-scroll').find('div').first().css('width', '1px');
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }) // end $.get()
    }); // end $.live()

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
        var new_param_row = param_template.replace(/%s/g, $routine_params_table.find('tr').length-1);
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
        setOptionsForParameter(
            $newrow.find('select[name^=routine_param_type]'),
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
        setOptionsForParameter(
            $row.find('select[name^=routine_param_type]'),
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
        setOptionsForParameter(
            $('.rte_table').find('select[name=routine_returntype]'),
            $('.rte_table').find('select[name=routine_returnopts_text]'),
            $('.rte_table').find('select[name=routine_returnopts_num]')
        );
    });
}); // end of $(document).ready() for the Routine Editor

/**
 * Attach Ajax event handlers for the Routine Execution dialog.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    /**
     * Attach Ajax event handlers for the Execute routine functionality.
     *
     * @uses    PMA_ajaxShowMessage()
     * @uses    PMA_ajaxRemoveMessage()
     *
     * @see $cfg['AjaxEnable']
     */
    $('.exec_routine_anchor').live('click', function(event) {
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
                    /**
                     * @var button_options  Object containing options for jQueryUI dialog buttons
                     */
                    var button_options = {};
                    button_options[PMA_messages['strGo']] = function() {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
                        $.post('db_routines.php', data, function (data) {
                           if(data.success == true) {
                                // Routine executed successfully
                                PMA_ajaxShowMessage(data.message);
                                $('#js_query_display').html('<div style="margin-top: 1em;">' + data.results + '</div>');
                                $ajaxDialog.dialog('close');
                            } else {
                                PMA_ajaxShowMessage(data.error);
                            }
                        });
                    }
                    button_options[PMA_messages['strClose']] = function() {
                        $(this).dialog("close");
                    }
                    /**
                     * Display the dialog to the user
                     */
                    $ajaxDialog = $('<div style="font-size: 0.9em;">'+data.message+'</div>').dialog({
                                    width: 650,  // TODO: make a better decision about the size
                                                 // of the dialog based on the size of the viewport
                                    buttons: button_options,
                                    title: data.title,
                                    modal: true,
                                    close: function () {
                                        $(this).remove();
                                    }
                            });
                } else {
                    // Routine executed successfully
                    PMA_ajaxShowMessage(data.message);
                    $('#js_query_display').html('<div style="margin-top: 1em;">' + data.results + '</div>');
                }
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    });
}); // end of $(document).ready() for the Routine Execution dialog
