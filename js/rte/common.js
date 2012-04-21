/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * @var    RTE    a JavaScript namespace containing the functionality
 *                for Routines, Triggers and Events.
 *
 *                This namespace is extended by the functionality required
 *                to handle a specific item (a routine, trigger or event)
 *                in the relevant javascript files in this folder.
 */
var RTE = {
    /**
     * @var    $ajaxDialog        jQuery object containing the reference to the
     *                            dialog that contains the editor.
     */
    $ajaxDialog: null,
    /**
     * @var    syntaxHiglighter   Reference to the codemirror editor.
     */
    syntaxHiglighter: null,
    /**
     * @var    buttonOptions      Object containing options for
     *                            the jQueryUI dialog buttons
     */
    buttonOptions: {},
    /**
     * Validate editor form fields.
     */
    validate: function () {
        /**
         * @var    $elm    a jQuery object containing the reference
         *                 to an element that is being validated.
         */
        var $elm = null;
        // Common validation. At the very least the name
        // and the definition must be provided for an item
        $elm = $('table.rte_table').last().find('input[name=item_name]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
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
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
        // The validation has so far passed, so now
        // we can validate item-specific fields.
        return RTE.validateCustom();
    }, // end validate()
    /**
     * Validate custom editor form fields.
     * This function can be overridden by
     * other files in this folder.
     */
    validateCustom: function () {
        return true;
    }, // end validateCustom()
    /**
     * Execute some code after the ajax
     * dialog for the ditor is shown.
     * This function can be overridden by
     * other files in this folder.
     */
    postDialogShow: function () {
        // Nothing by default
    } // end postDialogShow()
}; // end RTE namespace

/**
 * Attach Ajax event handlers for the Routines, Triggers and Events editor.
 *
 * @see $cfg['AjaxEnable']
 */
$(function () {
    /**
     * Attach Ajax event handlers for the Add/Edit functionality.
     */
    $('a.ajax_add_anchor, a.ajax_edit_anchor').live('click', function (event) {
        event.preventDefault();
        /**
         * @var    $edit_row    jQuery object containing the reference to
         *                      the row of the the item being edited
         *                      from the list of items .
         */
        var $edit_row = null;
        if ($(this).hasClass('ajax_edit_anchor')) {
            // Remeber the row of the item being edited for later,
            // so that if the edit is successful, we can replace the
            // row with info about the modified item.
            $edit_row = $(this).parents('tr');
        }
        /**
         * @var    $msg    jQuery object containing the reference to
         *                 the AJAX message shown to the user.
         */
        var $msg = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                // We have successfully fetched the editor form
                PMA_ajaxRemoveMessage($msg);
                // Now define the function that is called when
                // the user presses the "Go" button
                RTE.buttonOptions[PMA_messages['strGo']] = function () {
                    // Move the data from the codemirror editor back to the
                    // textarea, where it can be used in the form submission.
                    if (typeof CodeMirror != 'undefined') {
                        RTE.syntaxHiglighter.save();
                    }
                    // Validate editor and submit request, if passed.
                    if (RTE.validate()) {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('form.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
                        $.post($('form.rte_form').last().attr('action'), data, function (data) {
                            if (data.success === true) {
                                // Item created successfully
                                PMA_ajaxRemoveMessage($msg);
                                PMA_slidingMessage(data.message);
                                RTE.$ajaxDialog.dialog('close');
                                // If we are in 'edit' mode, we must remove the reference to the old row.
                                if (mode === 'edit') {
                                    $edit_row.remove();
                                }
                                // Sometimes, like when moving a trigger from a table to
                                // another one, the new row should not be inserted into the
                                // list. In this case "data.insert" will be set to false.
                                if (data.insert) {
                                    // Insert the new row at the correct location in the list of items
                                    /**
                                     * @var    text    Contains the name of an item from the list
                                     *                 that is used in comparisons to find the correct
                                     *                 location where to insert a new row.
                                     */
                                    var text = '';
                                    /**
                                     * @var    inserted    Whether a new item has been inserted
                                     *                     in the list or not.
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
                                    $('tr.ajaxInsert').show('slow').removeClass('ajaxInsert');
                                } else if ($('table.data').find('tr').has('td').length === 0) {
                                    // If we are not supposed to insert the new row, we will now
                                    // check if the table is empty and needs to be hidden. This
                                    // will be the case if we were editing the only item in the
                                    // list, which we removed and will not be inserting something
                                    // else in its place.
                                    $('table.data').hide("slow", function () {
                                        $('#nothing2display').show("slow");
                                    });
                                }
                                // Now we have inserted the row at the correct position, but surely
                                // at least some row classes are wrong now. So we will itirate
                                // throught all rows and assign correct classes to them.
                                /**
                                 * @var    ct          Count of processed rows.
                                 */
                                var ct = 0;
                                /**
                                 * @var    rowclass    Class to be attached to the row
                                 *                     that is being processed
                                 */
                                var rowclass = '';
                                $('table.data').find('tr').has('td').each(function () {
                                    rowclass = (ct % 2 === 0) ? 'odd' : 'even';
                                    $(this).removeClass().addClass(rowclass);
                                    ct++;
                                });
                                // If this is the first item being added, remove
                                // the "No items" message and show the list.
                                if ($('table.data').find('tr').has('td').length > 0
                                    && $('#nothing2display').is(':visible')) {
                                    $('#nothing2display').hide("slow", function () {
                                        $('table.data').show("slow");
                                    });
                                }
                            } else {
                                PMA_ajaxShowMessage(data.error, false);
                            }
                        }); // end $.post()
                    } // end "if (RTE.validate())"
                }; // end of function that handles the submission of the Editor
                RTE.buttonOptions[PMA_messages['strClose']] = function () {
                    $(this).dialog("close");
                };
                /**
                 * Display the dialog to the user
                 */
                RTE.$ajaxDialog = $('<div>' + data.message + '</div>').dialog({
                                width: 700,
                                minWidth: 500,
                                buttons: RTE.buttonOptions,
                                title: data.title,
                                modal: true,
                                close: function () {
                                    $(this).remove();
                                }
                        });
                RTE.$ajaxDialog.find('input[name=item_name]').focus();
                RTE.$ajaxDialog.find('input.datefield, input.datetimefield').each(function () {
                    PMA_addDatepicker($(this).css('width', '95%'));
                });
                /**
                 * @var    mode    Used to remeber whether the editor is in
                 *                 "Edit" or "Add" mode.
                 */
                var mode = 'add';
                if ($('input[name=editor_process_edit]').length > 0) {
                    mode = 'edit';
                }
                // Attach syntax highlited editor to the definition
                /**
                 * @var    $elm    jQuery object containing the reference to
                 *                 the Definition textarea.
                 */
                var $elm = $('textarea[name=item_definition]').last();
                /**
                 * @var    opts    Options to pass to the codemirror editor.
                 */
                var opts = {lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql"};
                if (typeof CodeMirror != 'undefined') {
                    RTE.syntaxHiglighter = CodeMirror.fromTextArea($elm[0], opts);
                }
                // Execute item-specific code
                RTE.postDialogShow(data);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    }); // end $.live()

    /**
     * Attach Ajax event handlers for input fields in the editor
     * and the routine execution dialog used to submit the Ajax
     * request when the ENTER key is pressed.
     */
    $('table.rte_table').find('input[name^=item], input[name^=params]').live('keydown', function (e) {
        if (e.which === 13) { // 13 is the ENTER key
            e.preventDefault();
            if (typeof RTE.buttonOptions[PMA_messages['strGo']] === 'function') {
                RTE.buttonOptions[PMA_messages['strGo']].call();
            }
        }
    }); // end $.live()

    /**
     * Attach Ajax event handlers for Export of Routines, Triggers and Events.
     */
    $('a.ajax_export_anchor').live('click', function (event) {
        event.preventDefault();
        var $msg = PMA_ajaxShowMessage();
        // Fire the ajax request straight away
        $.get($(this).attr('href'), {'ajax_request': true}, function (data) {
            if (data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                /**
                 * @var button_options  Object containing options for jQueryUI dialog buttons
                 */
                var button_options = {};
                button_options[PMA_messages['strClose']] = function () {
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
                // Attach syntax highlited editor to export dialog
                /**
                 * @var    $elm    jQuery object containing the reference
                 *                 to the Export textarea.
                 */
                var $elm = $ajaxDialog.find('textarea');
                /**
                 * @var    opts    Options to pass to the codemirror editor.
                 */
                var opts = {lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql"};
                CodeMirror.fromTextArea($elm[0], opts);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }); // end $.get()
    }); // end $.live()

    /**
     * Attach Ajax event handlers for Drop functionality of Routines, Triggers and Events.
     */
    $('a.ajax_drop_anchor').live('click', function (event) {
        event.preventDefault();
        /**
         * @var $curr_row    Object containing reference to the current row
         */
        var $curr_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $('<div/>').text($curr_row.children('td').children('.drop_sql').html());
        // We ask for confirmation first here, before submitting the ajax request
        $(this).PMA_confirm(question, $(this).attr('href'), function (url) {
            /**
             * @var    $msg    jQuery object containing the reference to
             *                 the AJAX message shown to the user.
             */
            var $msg = PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function (data) {
                if (data.success === true) {
                    /**
                     * @var $table    Object containing reference to the main list of elements.
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
                             * @var    ct          Count of processed rows.
                             */
                            var ct = 0;
                            /**
                             * @var    rowclass    Class to be attached to the row
                             *                     that is being processed
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
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); // end $.live()
}); // end of $()
