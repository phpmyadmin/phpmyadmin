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
     * @var    buttonOptions     Object containing options for jQueryUI dialog buttons
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
        // Common validation
        $elm = $('.rte_table').last().find('input[name=item_name]');
        if ($elm.val() == '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
        $elm = $('.rte_table').find('textarea[name=item_definition]');
        if ($elm.val() == '') {
            this.syntaxHiglighter.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
        return RTE.validateCustom();
    } // end validate()
}; // end RTE namespace

/**
 * Attach Ajax event handlers for the Routines, Triggers and Events editor.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function() {
    /**
     * Attach Ajax event handlers for the Add/Edit functionality.
     *
     * @uses    PMA_ajaxShowMessage()
     * @uses    PMA_ajaxRemoveMessage()
     *
     * @see $cfg['AjaxEnable']
     */
    $('.ajax_add_anchor, .ajax_edit_anchor').live('click', function(event) {
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
        var $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxRemoveMessage($msg);
                RTE.buttonOptions[PMA_messages['strGo']] = function() {
                    RTE.syntaxHiglighter.save();
                    // Validate editor and submit request, if passed.
                    if (RTE.validate()) {
                        /**
                         * @var    data    Form data to be sent in the AJAX request.
                         */
                        var data = $('.rte_form').last().serialize();
                        $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
                        $.post($('.rte_form').last().attr('action'), data, function (data) {
                            if(data.success == true) {
                                // Item created successfully
                                PMA_ajaxRemoveMessage($msg);
                                PMA_slidingMessage(data.message);
                                RTE.$ajaxDialog.dialog('close');
                                // If we are in 'edit' mode, we must remove the reference to the old row.
                                if (mode == 'edit') {
                                    $edit_row.remove();
                                }

                                if (data.insert) {
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
                                    // Fade-in the new row
                                    $('.ajaxInsert').show('slow').removeClass('ajaxInsert');
                                    // Now we have inserted the row at the correct position, but surely
                                    // at least some row classes are wrong now. So we will itirate
                                    // throught all rows and assign correct classes to them.
                                } else if ($('table.data').find('tr').has('td').length == 0) {
                                    $('table.data').hide("slow", function () {
                                        $('#nothing2display').show("slow");
                                    });
                                }
                                /**
                                 * @var    ct    Count of processed rows.
                                 */
                                var ct = 0;
                                $('table.data').find('tr').has('td').each(function() {
                                    rowclass = (ct % 2 == 0) ? 'even' : 'odd';
                                    $(this).removeClass().addClass(rowclass);
                                    ct++;
                                });
                                // If this is the first item being added, remove the
                                // "No items" message and show the list of items.
                                if ($('table.data').find('tr').has('td').length > 0 && $('#nothing2display').is(':visible')) {
                                    $('#nothing2display').hide("slow", function () {
                                        $('table.data').show("slow");
                                    });
                                }
                            } else {
                                PMA_ajaxShowMessage(data.error);
                            }
                        });
                    }
                } // end of function that handles the submission of the Editor
                RTE.buttonOptions[PMA_messages['strClose']] = function() {
                    $(this).dialog("close");
                }
                /**
                 * Display the dialog to the user
                 */
                RTE.$ajaxDialog = $('<div>'+data.message+'</div>').dialog({
                                width: 700,  // TODO: make a better decision about the size
                                height: 555, // of the dialog based on the size of the viewport
                                buttons: RTE.buttonOptions,
                                title: data.title,
                                modal: true,
                                close: function () {
                                    $(this).remove();
                                }
                        });
                RTE.$ajaxDialog.find('input[name=item_name]').focus();
                RTE.$ajaxDialog.find('.datefield, .datetimefield').each(function() {
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
                // Attach syntax highlited editor to routine definition
                /**
                 * @var    $elm    jQuery object containing the reference to
                 *                 the "Routine Definition" textarea.
                 */
                var $elm = $('textarea[name=item_definition]').last();
                /**
                 * @var    opts    Options to pass to the codemirror editor.
                 */
                var opts = {lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql"};
                RTE.syntaxHiglighter = CodeMirror.fromTextArea($elm[0], opts);
                // Hack to prevent the syntax highlighter from expanding beyond dialog boundries
                $('.CodeMirror-scroll').find('div').first().css('width', '1px');
                // Execute item-specific code
                RTE.postDialogShow(data);
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }) // end $.get()
    }); // end $.live()

    /**
     * Attach Ajax event handlers for input fields in the editor
     * and the routine execution dialog used to submit the Ajax
     * request when the ENTER key is pressed.
     *
     * @see $cfg['AjaxEnable']
     */
    $('.rte_table').find('input[name^=item], input[name^=params]').live('keydown', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            if (typeof RTE.buttonOptions[PMA_messages['strGo']] == 'function') {
                RTE.buttonOptions[PMA_messages['strGo']].call();
            }
        }
    });

    /**
     * Attach Ajax event handlers for Export of Routines, Triggers and Events.
     *
     * @uses    PMA_ajaxShowMessage()
     * @uses    PMA_ajaxRemoveMessage()
     *
     * @see $cfg['AjaxEnable']
     */
    $('.ajax_export_anchor').live('click', function(event) {
        event.preventDefault();
        var $msg = PMA_ajaxShowMessage(PMA_messages['strLoading']);
        $.get($(this).attr('href'), {'ajax_request': true}, function(data) {
            if(data.success == true) {
                PMA_ajaxRemoveMessage($msg);
                /**
                 * @var button_options  Object containing options for jQueryUI dialog buttons
                 */
                var button_options = {};
                button_options[PMA_messages['strClose']] = function() {$(this).dialog("close").remove();}
                /**
                 * Display the dialog to the user
                 */
                var $ajaxDialog = $('<div>'+data.message+'</div>').dialog({
                                      width: 500,
                                      buttons: button_options,
                                      title: data.title
                                  });
                // Attach syntax highlited editor to export dialog
                var elm = $ajaxDialog.find('textarea');
                CodeMirror.fromTextArea(elm[0], {lineNumbers: true, matchBrackets: true, indentUnit: 4, mode: "text/x-mysql"});
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }) // end $.get()
    }); // end $.live()

    /**
     * Attach Ajax event handlers for Drop functionality of Routines, Triggers and Events.
     *
     * @uses    $.PMA_confirm()
     * @uses    PMA_ajaxShowMessage()
     * @see     $cfg['AjaxEnable']
     */
    $('.ajax_drop_anchor').live('click', function(event) {
        event.preventDefault();
        /**
         * @var $curr_row    Object containing reference to the current row
         */
        var $curr_row = $(this).parents('tr');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = $('<div></div>').text($curr_row.children('td').children('.drop_sql').html());
        $(this).PMA_confirm(question, $(this).attr('href'), function(url) {
            /**
             * @var    $msg    jQuery object containing the reference to
             *                 the AJAX message shown to the user.
             */
            var $msg = PMA_ajaxShowMessage(PMA_messages['strProcessingRequest']);
            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function(data) {
                if(data.success == true) {
                    /**
                     * @var $table    Object containing reference to the main list of elements.
                     */
                    var $table = $curr_row.parent();
                    if ($table.find('tr').length == 2) {
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
                             * @var    ct    Count of processed rows.
                             */
                            var ct = 0;
                            $table.find('tr').has('td').each(function() {
                                rowclass = (ct % 2 == 0) ? 'even' : 'odd';
                                $(this).removeClass().addClass(rowclass);
                                ct++;
                            });
                        });
                    }
                    // Show the query that we just executed
                    PMA_ajaxRemoveMessage($msg);
                    PMA_slidingMessage(data.sql_query);
                } else {
                    PMA_ajaxShowMessage(PMA_messages['strErrorProcessingRequest'] + " : " + data.error);
                }
            }) // end $.get()
        }) // end $.PMA_confirm()
    }); // end $.live()
}); // end of $(document).ready()
