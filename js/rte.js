/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JavaScript functionality for Routines, Triggers and Events.
 *
 * @package PhpMyadmin
 */
/**
 * @var RTE Contains all the JavaScript functionality
 *          for Routines, Triggers and Events
 */
var RTE = {
    /**
     * Construct for the object that provides the
     * functionality for Routines, Triggers and Events
     */
    object: function (type) {
        $.extend(this, RTE.COMMON);
        switch (type) {
        case 'routine':
            $.extend(this, RTE.ROUTINE);
            break;
        case 'trigger':
            // nothing extra yet for triggers
            break;
        case 'event':
            $.extend(this, RTE.EVENT);
            break;
        default:
            break;
        }
    },
    /**
     * @var string param_template Template for a row in the routine editor
     */
    param_template: ''
};

/**
 * @var RTE.COMMON a JavaScript namespace containing the functionality
 *                 for Routines, Triggers and Events
 *
 *                 This namespace is extended by the functionality required
 *                 to handle a specific item (a routine, trigger or event)
 *                 in the relevant javascript files in this folder
 */
RTE.COMMON = {
    /**
     * @var $ajaxDialog Query object containing the reference to the
     *                  dialog that contains the editor
     */
    $ajaxDialog: null,
    /**
     * @var syntaxHiglighter Reference to the codemirror editor
     */
    syntaxHiglighter: null,
    /**
     * @var buttonOptions Object containing options for
     *                    the jQueryUI dialog buttons
     */
    buttonOptions: {},
    /**
     * Validate editor form fields.
     */
    validate: function () {
        /**
         * @var $elm a jQuery object containing the reference
         *           to an element that is being validated
         */
        var $elm = null;
        // Common validation. At the very least the name
        // and the definition must be provided for an item
        $elm = $('table.rte_table').last().find('input[name=item_name]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages.strFormEmpty);
            return false;
        }
        $elm = $('table.rte_table').find('textarea[name=item_definition]');
        if ($elm.val() === '') {
            if (this.syntaxHiglighter !== null) {
                this.syntaxHiglighter.focus();
            }
            else {
                $('textarea[name=item_definition]').last().focus();
            }
            alert(PMA_messages.strFormEmpty);
            return false;
        }
        // The validation has so far passed, so now
        // we can validate item-specific fields.
        return this.validateCustom();
    }, // end validate()
    /**
     * Validate custom editor form fields.
     * This function can be overridden by
     * other files in this folder
     */
    validateCustom: function () {
        return true;
    }, // end validateCustom()
    /**
     * Execute some code after the ajax
     * dialog for the editor is shown.
     * This function can be overridden by
     * other files in this folder
     */
    postDialogShow: function () {
        // Nothing by default
    }, // end postDialogShow()

    exportDialog: function ($this) {
        var $msg = PMA_ajaxShowMessage();
        // Fire the ajax request straight away
        $.get($this.attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                /**
                 * @var button_options Object containing options
                 *                     for jQueryUI dialog buttons
                 */
                var button_options = {};
                button_options[PMA_messages.strClose] = function () {
                    $(this).dialog("close").remove();
                };
                /**
                 * Display the dialog to the user
                 */
                var $ajaxDialog = $('<div>' + data.message + '</div>').dialog({
                    width: 500,
                    buttons: button_options,
                    title: data.title
                });
                // Attach syntax highlighted editor to export dialog
                /**
                 * @var $elm jQuery object containing the reference
                 *           to the Export textarea.
                 */
                var $elm = $ajaxDialog.find('textarea');
                /**
                 * @var opts Options to pass to the codemirror editor
                 */
                var opts = {
                    lineNumbers: true,
                    matchBrackets: true,
                    extraKeys: {"Ctrl-Space": "autocomplete"},
                    hintOptions: {"completeSingle": false, "completeOnSingleClick": true},
                    indentUnit: 4,
                    mode: "text/x-mysql",
                    lineWrapping: true
                };
                CodeMirror.fromTextArea($elm[0], opts)
                    .on("inputRead", codemirrorAutocompleteOnInputRead);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    },  // end exportDialog()
    editorDialog: function (is_new, $this) {
        var that = this;
        /**
         * @var $edit_row jQuery object containing the reference to
         *                the row of the the item being edited
         *                from the list of items
         */
        var $edit_row = null;
        if ($this.hasClass('edit_anchor')) {
            // Remeber the row of the item being edited for later,
            // so that if the edit is successful, we can replace the
            // row with info about the modified item.
            $edit_row = $this.parents('tr');
        }
        /**
         * @var $msg jQuery object containing the reference to
         *           the AJAX message shown to the user
         */
        var $msg = PMA_ajaxShowMessage();
        $.get($this.attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                // We have successfully fetched the editor form
                PMA_ajaxRemoveMessage($msg);
                // Now define the function that is called when
                // the user presses the "Go" button
                that.buttonOptions[PMA_messages.strGo] = function () {
                    // Move the data from the codemirror editor back to the
                    // textarea, where it can be used in the form submission.
                    if (typeof CodeMirror != 'undefined') {
                        that.syntaxHiglighter.save();
                    }
                    // Validate editor and submit request, if passed.
                    if (that.validate()) {
                        /**
                         * @var data Form data to be sent in the AJAX request
                         */
                        var data = $('form.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(
                            PMA_messages.strProcessingRequest
                        );
                        var url = $('form.rte_form').last().attr('action');
                        $.post(url, data, function (data) {
                            if (data.success === true) {
                                // Item created successfully
                                PMA_ajaxRemoveMessage($msg);
                                PMA_slidingMessage(data.message);
                                that.$ajaxDialog.dialog('close');
                                // If we are in 'edit' mode, we must
                                // remove the reference to the old row.
                                if (mode === 'edit' && $edit_row !== null ) {
                                    $edit_row.remove();
                                }
                                // Sometimes, like when moving a trigger from
                                // a table to another one, the new row should
                                // not be inserted into the list. In this case
                                // "data.insert" will be set to false.
                                if (data.insert) {
                                    // Insert the new row at the correct
                                    // location in the list of items
                                    /**
                                     * @var text Contains the name of an item from
                                     *           the list that is used in comparisons
                                     *           to find the correct location where
                                     *           to insert a new row.
                                     */
                                    var text = '';
                                    /**
                                     * @var inserted Whether a new item has been
                                     *               inserted in the list or not
                                     */
                                    var inserted = false;
                                    $('table.data').find('tr').each(function () {
                                        text = $(this)
                                                .children('td')
                                                .eq(0)
                                                .find('strong')
                                                .text()
                                                .toUpperCase();
                                        text = $.trim(text);
                                        if (text !== '' && text > data.name) {
                                            $(this).before(data.new_row);
                                            inserted = true;
                                            return false;
                                        }
                                    });
                                    if (! inserted) {
                                        // If we didn't manage to insert the row yet,
                                        // it must belong at the end of the list,
                                        // so we insert it there.
                                        $('table.data').append(data.new_row);
                                    }
                                    // Fade-in the new row
                                    $('tr.ajaxInsert')
                                        .show('slow')
                                        .removeClass('ajaxInsert');
                                } else if ($('table.data').find('tr').has('td').length === 0) {
                                    // If we are not supposed to insert the new row,
                                    // we will now check if the table is empty and
                                    // needs to be hidden. This will be the case if
                                    // we were editing the only item in the list,
                                    // which we removed and will not be inserting
                                    // something else in its place.
                                    $('table.data').hide("slow", function () {
                                        $('#nothing2display').show("slow");
                                    });
                                }
                                // Now we have inserted the row at the correct
                                // position, but surely at least some row classes
                                // are wrong now. So we will itirate throught
                                // all rows and assign correct classes to them
                                /**
                                 * @var ct Count of processed rows
                                 */
                                var ct = 0;
                                /**
                                 * @var rowclass Class to be attached to the row
                                 *               that is being processed
                                 */
                                var rowclass = '';
                                $('table.data').find('tr').has('td').each(function () {
                                    rowclass = (ct % 2 === 0) ? 'odd' : 'even';
                                    $(this).removeClass().addClass(rowclass);
                                    ct++;
                                });
                                // If this is the first item being added, remove
                                // the "No items" message and show the list.
                                if ($('table.data').find('tr').has('td').length > 0 &&
                                    $('#nothing2display').is(':visible')
                                    ) {
                                    $('#nothing2display').hide("slow", function () {
                                        $('table.data').show("slow");
                                    });
                                }
                                PMA_reloadNavigation();
                            } else {
                                PMA_ajaxShowMessage(data.error, false);
                            }
                        }); // end $.post()
                    } // end "if (that.validate())"
                }; // end of function that handles the submission of the Editor
                that.buttonOptions[PMA_messages.strClose] = function () {
                    $(this).dialog("close");
                };
                /**
                 * Display the dialog to the user
                 */
                that.$ajaxDialog = $('<div id="rteDialog">' + data.message + '</div>').dialog({
                    width: 700,
                    minWidth: 500,
                    maxHeight: $(window).height(),
                    buttons: that.buttonOptions,
                    title: data.title,
                    modal: true,
                    open: function () {
                        if ($('#rteDialog').parents('.ui-dialog').height() > $(window).height()) {
                            $('#rteDialog').dialog("option", "height", $(window).height());
                        }
                        $(this).find('input[name=item_name]').focus();
                        $(this).find('input.datefield').each(function () {
                            PMA_addDatepicker($(this).css('width', '95%'), 'date');
                        });
                        $(this).find('input.datetimefield').each(function () {
                            PMA_addDatepicker($(this).css('width', '95%'), 'datetime');
                        });
                        $.datepicker.initialized = false;
                    },
                    close: function () {
                        $(this).remove();
                    }
                });
                /**
                 * @var mode Used to remeber whether the editor is in
                 *           "Edit" or "Add" mode
                 */
                var mode = 'add';
                if ($('input[name=editor_process_edit]').length > 0) {
                    mode = 'edit';
                }
                // Attach syntax highlighted editor to the definition
                /**
                 * @var elm jQuery object containing the reference to
                 *                 the Definition textarea.
                 */
                var $elm = $('textarea[name=item_definition]').last();
                /**
                 * @var opts Options to pass to the codemirror editor
                 */
                var opts = {
                    lineNumbers: true,
                    matchBrackets: true,
                    extraKeys: {"Ctrl-Space": "autocomplete"},
                    hintOptions: {"completeSingle": false, "completeOnSingleClick": true},
                    indentUnit: 4,
                    mode: "text/x-mysql",
                    lineWrapping: true
                };
                if (typeof CodeMirror != 'undefined') {
                    that.syntaxHiglighter = CodeMirror.fromTextArea($elm[0], opts);
                    that.syntaxHiglighter.on("inputRead", codemirrorAutocompleteOnInputRead);
                }
                // Execute item-specific code
                that.postDialogShow(data);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    },

    dropDialog: function ($this) {
       /**
         * @var $curr_row Object containing reference to the current row
         */
        var $curr_row = $this.parents('tr');
        /**
         * @var question String containing the question to be asked for confirmation
         */
        var question = $('<div/>').text(
            $curr_row.children('td').children('.drop_sql').html()
        );
        // We ask for confirmation first here, before submitting the ajax request
        $this.PMA_confirm(question, $this.attr('href'), function (url) {
            /**
             * @var msg jQuery object containing the reference to
             *          the AJAX message shown to the user
             */
            var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function (data) {
                if (data.success === true) {
                    /**
                     * @var $table Object containing reference
                     *             to the main list of elements
                     */
                    var $table = $curr_row.parent();
                    // Check how many rows will be left after we remove
                    // the one that the user has requested us to remove
                    if ($table.find('tr').length === 3) {
                        // If there are two rows left, it means that they are
                        // the header of the table and the rows that we are
                        // about to remove, so after the removal there will be
                        // nothing to show in the table, so we hide it.
                        $table.hide("slow", function () {
                            $(this).find('tr.even, tr.odd').remove();
                            $('#nothing2display').show("slow");
                        });
                    } else {
                        $curr_row.hide("slow", function () {
                            $(this).remove();
                            // Now we have removed the row from the list, but maybe
                            // some row classes are wrong now. So we will itirate
                            // throught all rows and assign correct classes to them.
                            /**
                             * @var ct Count of processed rows
                             */
                            var ct = 0;
                            /**
                             * @var rowclass Class to be attached to the row
                             *               that is being processed
                             */
                            var rowclass = '';
                            $table.find('tr').has('td').each(function () {
                                rowclass = (ct % 2 === 0) ? 'odd' : 'even';
                                $(this).removeClass().addClass(rowclass);
                                ct++;
                            });
                        });
                    }
                    // Get rid of the "Loading" message
                    PMA_ajaxRemoveMessage($msg);
                    // Show the query that we just executed
                    PMA_slidingMessage(data.sql_query);
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }
}; // end RTE namespace

/**
 * @var RTE.EVENT JavaScript functionality for events
 */
RTE.EVENT = {
    validateCustom: function () {
        /**
         * @var elm a jQuery object containing the reference
         *          to an element that is being validated
         */
        var $elm = null;
        if (this.$ajaxDialog.find('select[name=item_type]').find(':selected').val() === 'RECURRING') {
            // The interval field must not be empty for recurring events
            $elm = this.$ajaxDialog.find('input[name=item_interval_value]');
            if ($elm.val() === '') {
                $elm.focus();
                alert(PMA_messages.strFormEmpty);
                return false;
            }
        } else {
            // The execute_at field must not be empty for "once off" events
            $elm = this.$ajaxDialog.find('input[name=item_execute_at]');
            if ($elm.val() === '') {
                $elm.focus();
                alert(PMA_messages.strFormEmpty);
                return false;
            }
        }
        return true;
    }
};

/**
 * @var RTE.ROUTINE JavaScript functionality for routines
 */
RTE.ROUTINE = {
    /**
     * Overriding the postDialogShow() function defined in common.js
     *
     * @param data JSON-encoded data from the ajax request
     */
    postDialogShow: function (data) {
        // Cache the template for a parameter table row
        RTE.param_template = data.param_template;
        var that = this;
        // Make adjustments in the dialog to make it AJAX compatible
        $('td.routine_param_remove').show();
        $('input[name=routine_removeparameter]').remove();
        $('input[name=routine_addparameter]').css('width', '100%');
        // Enable/disable the 'options' dropdowns for parameters as necessary
        $('table.routine_params_table').last().find('th[colspan=2]').attr('colspan', '1');
        $('table.routine_params_table').last().find('tr').has('td').each(function () {
            that.setOptionsForParameter(
                $(this).find('select[name^=item_param_type]'),
                $(this).find('input[name^=item_param_length]'),
                $(this).find('select[name^=item_param_opts_text]'),
                $(this).find('select[name^=item_param_opts_num]')
            );
        });
        // Enable/disable the 'options' dropdowns for
        // function return value as necessary
        this.setOptionsForParameter(
            $('table.rte_table').last().find('select[name=item_returntype]'),
            $('table.rte_table').last().find('input[name=item_returnlength]'),
            $('table.rte_table').last().find('select[name=item_returnopts_text]'),
            $('table.rte_table').last().find('select[name=item_returnopts_num]')
        );
    },
    /**
     * Overriding the validateCustom() function defined in common.js
     */
    validateCustom: function () {
        /**
         * @var isSuccess Stores the outcome of the validation
         */
        var isSuccess = true;
        /**
         * @var inputname The value of the "name" attribute for
         *                the field that is being processed
         */
        var inputname = '';
        this.$ajaxDialog.find('table.routine_params_table').last().find('tr').each(function () {
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
            alert(PMA_messages.strFormEmpty);
            return false;
        }
        this.$ajaxDialog.find('table.routine_params_table').last().find('tr').each(function () {
            // SET, ENUM, VARCHAR and VARBINARY fields must have length/values
            var $inputtyp = $(this).find('select[name^=item_param_type]');
            var $inputlen = $(this).find('input[name^=item_param_length]');
            if ($inputtyp.length && $inputlen.length) {
                if (($inputtyp.val() === 'ENUM' || $inputtyp.val() === 'SET' || $inputtyp.val().substr(0, 3) === 'VAR') &&
                    $inputlen.val() === ''
                   ) {
                    $inputlen.focus();
                    isSuccess = false;
                    return false;
                }
            }
        });
        if (! isSuccess) {
            alert(PMA_messages.strFormEmpty);
            return false;
        }
        if (this.$ajaxDialog.find('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
            // The length/values of return variable for functions must
            // be set, if the type is SET, ENUM, VARCHAR or VARBINARY.
            var $returntyp = this.$ajaxDialog.find('select[name=item_returntype]');
            var $returnlen = this.$ajaxDialog.find('input[name=item_returnlength]');
            if (($returntyp.val() === 'ENUM' || $returntyp.val() === 'SET' || $returntyp.val().substr(0, 3) === 'VAR') &&
                $returnlen.val() === ''
                ) {
                $returnlen.focus();
                alert(PMA_messages.strFormEmpty);
                return false;
            }
        }
        if ($('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
            // A function must contain a RETURN statement in its definition
            if (this.$ajaxDialog.find('table.rte_table').find('textarea[name=item_definition]').val().toUpperCase().indexOf('RETURN') < 0) {
                this.syntaxHiglighter.focus();
                alert(PMA_messages.MissingReturn);
                return false;
            }
        }
        return true;
    },
    /**
     * Enable/disable the "options" dropdown and "length" input for
     * parameters and the return variable in the routine editor
     * as necessary.
     *
     * @param type a jQuery object containing the reference
     *             to the "Type" dropdown box
     * @param len  a jQuery object containing the reference
     *             to the "Length" input box
     * @param text a jQuery object containing the reference
     *             to the dropdown box with options for
     *             parameters of text type
     * @param num  a jQuery object containing the reference
     *             to the dropdown box with options for
     *             parameters of numeric type
     */
    setOptionsForParameter: function ($type, $len, $text, $num) {
        /**
         * @var no_opts a jQuery object containing the reference
         *              to an element to be displayed when no
         *              options are available
         */
        var $no_opts = $text.parent().parent().find('.no_opts');
        /**
         * @var no_len a jQuery object containing the reference
         *             to an element to be displayed when no
         *             "length/values" field is available
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
    },
    executeDialog: function ($this) {
        var that = this;
        /**
         * @var msg jQuery object containing the reference to
         *          the AJAX message shown to the user
         */
        var $msg = PMA_ajaxShowMessage();
        $.get($this.attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                // If 'data.dialog' is true we show a dialog with a form
                // to get the input parameters for routine, otherwise
                // we just show the results of the query
                if (data.dialog) {
                    // Define the function that is called when
                    // the user presses the "Go" button
                    that.buttonOptions[PMA_messages.strGo] = function () {
                        /**
                         * @var data Form data to be sent in the AJAX request
                         */
                        var data = $('form.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(
                            PMA_messages.strProcessingRequest
                        );
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
                    that.buttonOptions[PMA_messages.strClose] = function () {
                        $(this).dialog("close");
                    };
                    /**
                     * Display the dialog to the user
                     */
                    $ajaxDialog = $('<div>' + data.message + '</div>').dialog({
                        width: 650,
                        buttons: that.buttonOptions,
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
                    /*
                    * Define the function if the user presses enter
                    */
                    $('form.rte_form').on('keyup', function (event) {
                        event.preventDefault();
                        if (event.keyCode === 13) {
                            /**
                            * @var data Form data to be sent in the AJAX request
                            */
                            var data = $(this).serialize();
                            $msg = PMA_ajaxShowMessage(
                                PMA_messages.strProcessingRequest
                            );
                            var url = $(this).attr('action');
                            $.post(url, data, function (data) {
                                if (data.success === true) {
                                    // Routine executed successfully
                                    PMA_ajaxRemoveMessage($msg);
                                    PMA_slidingMessage(data.message);
                                    $('form.rte_form').off('keyup');
                                    $ajaxDialog.remove();
                                } else {
                                    PMA_ajaxShowMessage(data.error, false);
                                }
                            });
                        }
                    });
                } else {
                    // Routine executed successfully
                    PMA_slidingMessage(data.message);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    }
};

/**
 * Attach Ajax event handlers for the Routines, Triggers and Events editor
 */
$(function () {
    /**
     * Attach Ajax event handlers for the Add/Edit functionality.
     */
    $(document).on('click', 'a.ajax.add_anchor, a.ajax.edit_anchor', function (event) {
        event.preventDefault();
        var type = $(this).attr('href').substr(0, $(this).attr('href').indexOf('?'));
        if (type.indexOf('routine') != -1) {
            type = 'routine';
        } else if (type.indexOf('trigger') != -1) {
            type = 'trigger';
        } else if (type.indexOf('event') != -1) {
            type = 'event';
        } else {
            type = '';
        }
        var dialog = new RTE.object(type);
        dialog.editorDialog($(this).hasClass('add_anchor'), $(this));
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the Execute routine functionality
     */
    $(document).on('click', 'a.ajax.exec_anchor', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.executeDialog($(this));
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for Export of Routines, Triggers and Events
     */
    $(document).on('click', 'a.ajax.export_anchor', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.exportDialog($(this));
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for Drop functionality
     * of Routines, Triggers and Events.
     */
    $(document).on('click', 'a.ajax.drop_anchor', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.dropDialog($(this));
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the "Change event/routine type"
     * functionality in the events editor, so that the correct
     * rows are shown in the editor when changing the event type
     */
    $(document).on('change', 'select[name=item_type]', function () {
        $(this)
        .closest('table')
        .find('tr.recurring_event_row, tr.onetime_event_row, tr.routine_return_row, .routine_direction_cell')
        .toggle();
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the "Change parameter type"
     * functionality in the routines editor, so that the correct
     * option/length fields, if any, are shown when changing
     * a parameter type
     */
    $(document).on('change', 'select[name^=item_param_type]', function () {
        /**
         * @var row jQuery object containing the reference to
         *          a row in the routine parameters table
         */
        var $row = $(this).parents('tr').first();
        var rte = new RTE.object('routine');
        rte.setOptionsForParameter(
            $row.find('select[name^=item_param_type]'),
            $row.find('input[name^=item_param_length]'),
            $row.find('select[name^=item_param_opts_text]'),
            $row.find('select[name^=item_param_opts_num]')
        );
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the "Change the type of return
     * variable of function" functionality, so that the correct fields,
     * if any, are shown when changing the function return type type
     */
    $(document).on('change', 'select[name=item_returntype]', function () {
        var rte = new RTE.object('routine');
        var $table = $(this).closest('table.rte_table');
        rte.setOptionsForParameter(
            $table.find('select[name=item_returntype]'),
            $table.find('input[name=item_returnlength]'),
            $table.find('select[name=item_returnopts_text]'),
            $table.find('select[name=item_returnopts_num]')
        );
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the "Add parameter to routine" functionality
     */
    $(document).on('click', 'input[name=routine_addparameter]', function (event) {
        event.preventDefault();
        /**
         * @var routine_params_table jQuery object containing the reference
         *                           to the routine parameters table
         */
        var $routine_params_table = $(this).closest('div.ui-dialog').find('.routine_params_table');
        /**
         * @var new_param_row A string containing the HTML code for the
         *                    new row for the routine parameters table
         */
        var new_param_row = RTE.param_template.replace(/%s/g, $routine_params_table.find('tr').length - 1);
        // Append the new row to the parameters table
        $routine_params_table.append(new_param_row);
        // Make sure that the row is correctly shown according to the type of routine
        if ($(this).closest('div.ui-dialog').find('table.rte_table select[name=item_type]').val() === 'FUNCTION') {
            $('tr.routine_return_row').show();
            $('td.routine_direction_cell').hide();
        }
        /**
         * @var newrow jQuery object containing the reference to the newly
         *             inserted row in the routine parameters table
         */
        var $newrow = $(this).closest('div.ui-dialog').find('table.routine_params_table').find('tr').has('td').last();
        // Enable/disable the 'options' dropdowns for parameters as necessary
        var rte = new RTE.object('routine');
        rte.setOptionsForParameter(
            $newrow.find('select[name^=item_param_type]'),
            $newrow.find('input[name^=item_param_length]'),
            $newrow.find('select[name^=item_param_opts_text]'),
            $newrow.find('select[name^=item_param_opts_num]')
        );
    }); // end $(document).on()

    /**
     * Attach Ajax event handlers for the
     * "Remove parameter from routine" functionality
     */
    $(document).on('click', 'a.routine_param_remove_anchor', function (event) {
        event.preventDefault();
        $(this).parent().parent().remove();
        // After removing a parameter, the indices of the name attributes in
        // the input fields lose the correct order and need to be reordered.
        /**
         * @var index Counter used for reindexing the input
         *            fields in the routine parameters table
         */
        var index = 0;
        $(this).closest('div.ui-dialog').find('table.routine_params_table').find('tr').has('td').each(function () {
            $(this).find(':input').each(function () {
                /**
                 * @var inputname The value of the name attribute of
                 *                the input field being reindexed
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
    }); // end $(document).on()
}); // end of $()
