import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { AJAX } from './modules/ajax.ts';
import { addDatepicker, getSqlEditor, slidingMessage } from './modules/functions.ts';
import { Navigation } from './modules/navigation.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from './modules/ajax-message.ts';
import getJsConfirmCommonParam from './modules/functions/getJsConfirmCommonParam.ts';

AJAX.registerTeardown('triggers.js', function () {
    $(document).off('click', 'a.ajax.add_anchor, a.ajax.edit_anchor');
    $(document).off('click', 'a.ajax.export_anchor');
    $(document).off('click', '#bulkActionExportButton');
    $(document).off('click', 'a.ajax.drop_anchor');
    $(document).off('click', '#bulkActionDropButton');
});

const DatabaseTriggers = {
    /**
     * @var syntaxHiglighter Reference to the codemirror editor
     */
    syntaxHiglighter: null,
    /**
     * Validate editor form fields.
     *
     * @return {boolean}
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
            $elm.trigger('focus');
            alert(window.Messages.strFormEmpty);

            return false;
        }

        $elm = $('table.rte_table').find('textarea[name=item_definition]');
        if ($elm.val() === '') {
            if (this.syntaxHiglighter !== null) {
                this.syntaxHiglighter.focus();
            } else {
                $('textarea[name=item_definition]').last().trigger('focus');
            }

            alert(window.Messages.strFormEmpty);

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
     *
     * @return {boolean}
     */
    validateCustom: function () {
        return true;
    }, // end validateCustom()

    exportDialog: function ($this) {
        var $msg = ajaxShowMessage();
        if ($this.attr('id') === 'bulkActionExportButton') {
            var combined = {
                success: true,
                title: window.Messages.strExport,
                message: '',
                error: ''
            };
            // export anchors of all selected rows
            var exportAnchors = $('input.checkall:checked').parents('tr').find('.export_anchor');
            var count = exportAnchors.length;
            var returnCount = 0;
            var p: any = $.when();
            exportAnchors.each(function () {
                var h = $(this).attr('href');
                p = p.then(function () {
                    return $.get(h, { 'ajax_request': true }, function (data) {
                        returnCount++;
                        if (data.success === true) {
                            combined.message += '\n' + data.message + '\n';
                            if (returnCount === count) {
                                showExport(combined);
                            }
                        } else {
                            // complain even if one export is failing
                            combined.success = false;
                            combined.error += '\n' + data.error + '\n';
                            if (returnCount === count) {
                                showExport(combined);
                            }
                        }
                    });
                });
            });
        } else {
            $.get($this.attr('href'), { 'ajax_request': true }, showExport);
        }

        ajaxRemoveMessage($msg);

        function showExport (data) {
            if (data.success !== true) {
                ajaxShowMessage(data.error, false);

                return;
            }

            ajaxRemoveMessage($msg);

            const triggersExportTextarea = '<textarea id="triggersExportTextarea" cols="40" rows="15" class="form-control" aria-label="' + window.Messages.strTrigger + '"></textarea>';
            const triggersExportModal = document.getElementById('triggersExportModal');
            triggersExportModal.addEventListener('shown.bs.modal', function () {
                triggersExportModal.querySelector('.modal-title').textContent = data.title;
                triggersExportModal.querySelector('.modal-body').innerHTML = triggersExportTextarea;
                document.getElementById('triggersExportTextarea').textContent = data.message;
                getSqlEditor($('#triggersExportTextarea'));
            });

            triggersExportModal.addEventListener('hidden.bs.modal', function () {
                triggersExportModal.querySelector('.modal-body').innerHTML = triggersExportTextarea;
            });

            bootstrap.Modal.getOrCreateInstance(triggersExportModal).show();
        }
    },
    editorDialog: function (isNew, $this) {
        var that = this;
        /**
         * @var $edit_row jQuery object containing the reference to
         *                the row of the the item being edited
         *                from the list of items
         */
        var $editRow = null;
        if ($this.hasClass('edit_anchor')) {
            // Remember the row of the item being edited for later,
            // so that if the edit is successful, we can replace the
            // row with info about the modified item.
            $editRow = $this.parents('tr');
        }

        /**
         * @var $msg jQuery object containing the reference to
         *           the AJAX message shown to the user
         */
        var $msg = ajaxShowMessage();
        $.get($this.attr('href'), { 'ajax_request': true }, function (data) {
            if (data.success !== true) {
                ajaxShowMessage(data.error, false);

                return;
            }

            // We have successfully fetched the editor form
            ajaxRemoveMessage($msg);

            let isEditMode = false;

            function triggersEditorModalSaveEventHandler () {
                // Move the data from the codemirror editor back to the
                // textarea, where it can be used in the form submission.
                if (typeof window.CodeMirror !== 'undefined') {
                    that.syntaxHiglighter.save();
                }

                // Validate editor and submit request, if passed.
                if (! that.validate()) {
                    return;
                }

                /**
                 * @var data Form data to be sent in the AJAX request
                 */
                var data = $('form.rte_form').last().serialize();
                $msg = ajaxShowMessage(
                    window.Messages.strProcessingRequest
                );

                var url = $('form.rte_form').last().attr('action');
                $.post(url, data, function (data) {
                    if (data.success !== true) {
                        ajaxShowMessage(data.error, false);

                        return;
                    }

                    // Item created successfully
                    ajaxRemoveMessage($msg);
                    slidingMessage(data.message);
                    bootstrap.Modal.getOrCreateInstance('#triggersEditorModal').hide();
                    // If we are in 'edit' mode, we must
                    // remove the reference to the old row.
                    if (isEditMode && $editRow !== null) {
                        $editRow.remove();
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
                                .toUpperCase()
                                .trim();

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
                        $('table.data').hide('slow', function () {
                            $('#nothing2display').show('slow');
                        });
                    }

                    // Now we have inserted the row at the correct
                    // position, but surely at least some row classes
                    // are wrong now. So we will iterate through
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
                        $('#nothing2display').hide('slow', function () {
                            $('table.data').show('slow');
                        });
                    }

                    Navigation.reload();
                }); // end $.post()
            }

            const triggersEditorModal = document.getElementById('triggersEditorModal');

            triggersEditorModal.addEventListener('shown.bs.modal', function () {
                /**
                 * Issue #15810 - use button titles for modals (eg: new procedure)
                 * Respect the order: title on href tag, href content, title sent in response
                 */
                triggersEditorModal.querySelector('.modal-title').textContent = $this.attr('title') || $this.text() || $(data.title).text();
                triggersEditorModal.querySelector('.modal-body').innerHTML = data.message;

                const triggersEditorModalSaveButton = document.getElementById('triggersEditorModalSaveButton');
                triggersEditorModalSaveButton?.addEventListener('click', triggersEditorModalSaveEventHandler);

                $(this).find('input[name=item_name]').trigger('focus');
                $(this).find('input.datefield').each(function () {
                    addDatepicker($(this).css('width', '95%'), 'date');
                });

                $(this).find('input.datetimefield').each(function () {
                    addDatepicker($(this).css('width', '95%'), 'datetime');
                });

                // @ts-ignore
                $.datepicker.initialized = false;

                if ($('input[name=editor_process_edit]').length > 0) {
                    isEditMode = true;
                }

                // Attach syntax highlighted editor to the definition
                /**
                 * @var elm jQuery object containing the reference to
                 *                 the Definition textarea.
                 */
                var $elm = $('textarea[name=item_definition]').last();
                var linterOptions = {
                    editorType: 'trigger',
                };
                that.syntaxHiglighter = getSqlEditor($elm, {}, 'vertical', linterOptions);
                window.codeMirrorEditor = that.syntaxHiglighter;
            });

            triggersEditorModal.addEventListener('hidden.bs.modal', function () {
                const triggersEditorModalSaveButton = document.getElementById('triggersEditorModalSaveButton');
                triggersEditorModalSaveButton?.removeEventListener('click', triggersEditorModalSaveEventHandler);
                document.getElementById('triggersEditorModal').querySelector('.modal-body').innerHTML = '<div class="spinner-border" role="status">' +
                    '<span class="visually-hidden">' + window.Messages.strLoading + '</span></div>';
            });

            bootstrap.Modal.getOrCreateInstance(triggersEditorModal).show();
        });
    },

    dropDialog: function ($this) {
        /**
         * @var $curr_row Object containing reference to the current row
         */
        var $currRow = $this.parents('tr');
        /**
         * @var question String containing the question to be asked for confirmation
         */
        var question = $('<div></div>').text(
            $currRow.children('td').children('.drop_sql').html()
        );
        // We ask for confirmation first here, before submitting the ajax request
        $this.confirm(question, $this.attr('href'), function (url) {
            /**
             * @var msg jQuery object containing the reference to
             *          the AJAX message shown to the user
             */
            var $msg = ajaxShowMessage(window.Messages.strProcessingRequest);
            var params = getJsConfirmCommonParam(this, $this.getPostData());
            $.post(url, params, function (data) {
                if (data.success !== true) {
                    ajaxShowMessage(data.error, false);

                    return;
                }

                /**
                 * @var $table Object containing reference
                 *             to the main list of elements
                 */
                var $table = $currRow.parent();
                // Check how many rows will be left after we remove
                // the one that the user has requested us to remove
                if ($table.find('tr').length === 3) {
                    // If there are two rows left, it means that they are
                    // the header of the table and the rows that we are
                    // about to remove, so after the removal there will be
                    // nothing to show in the table, so we hide it.
                    $table.hide('slow', function () {
                        $(this).find('tr.even, tr.odd').remove();
                        $('.withSelected').remove();
                        $('#nothing2display').show('slow');
                    });
                } else {
                    $currRow.hide('slow', function () {
                        $(this).remove();
                        // Now we have removed the row from the list, but maybe
                        // some row classes are wrong now. So we will iterate
                        // through all rows and assign correct classes to them.
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
                            rowclass = (ct % 2 === 1) ? 'odd' : 'even';
                            $(this).removeClass().addClass(rowclass);
                            ct++;
                        });
                    });
                }

                // Get rid of the "Loading" message
                ajaxRemoveMessage($msg);
                // Show the query that we just executed
                slidingMessage(data.sql_query);
                Navigation.reload();
            }); // end $.post()
        });
    },

    dropMultipleDialog: function ($this) {
        // We ask for confirmation here
        $this.confirm(window.Messages.strDropRTEitems, '', function () {
            /**
             * @var msg jQuery object containing the reference to
             *          the AJAX message shown to the user
             */
            var $msg = ajaxShowMessage(window.Messages.strProcessingRequest);

            // drop anchors of all selected rows
            var dropAnchors = $('input.checkall:checked').parents('tr').find('.drop_anchor');
            var success = true;
            var count = dropAnchors.length;
            var returnCount = 0;

            dropAnchors.each(function () {
                var $anchor = $(this);
                /**
                 * @var $curr_row Object containing reference to the current row
                 */
                var $currRow = $anchor.parents('tr');
                var params = getJsConfirmCommonParam(this, $anchor.getPostData());
                $.post($anchor.attr('href'), params, function (data) {
                    returnCount++;
                    if (data.success !== true) {
                        ajaxShowMessage(data.error, false);
                        success = false;
                        if (returnCount === count) {
                            Navigation.reload();
                        }

                        return;
                    }

                    /**
                     * @var $table Object containing reference
                     *             to the main list of elements
                     */
                    var $table = $currRow.parent();
                    // Check how many rows will be left after we remove
                    // the one that the user has requested us to remove
                    if ($table.find('tr').length === 3) {
                        // If there are two rows left, it means that they are
                        // the header of the table and the rows that we are
                        // about to remove, so after the removal there will be
                        // nothing to show in the table, so we hide it.
                        $table.hide('slow', function () {
                            $(this).find('tr.even, tr.odd').remove();
                            $('.withSelected').remove();
                            $('#nothing2display').show('slow');
                        });
                    } else {
                        $currRow.hide('fast', function () {
                            // we will iterate
                            // through all rows and assign correct classes to them.
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
                                rowclass = (ct % 2 === 1) ? 'odd' : 'even';
                                $(this).removeClass().addClass(rowclass);
                                ct++;
                            });
                        });

                        $currRow.remove();
                    }

                    if (returnCount === count) {
                        if (success) {
                            // Get rid of the "Loading" message
                            ajaxRemoveMessage($msg);
                            $('#rteListForm_checkall').prop({ checked: false, indeterminate: false });
                        }

                        Navigation.reload();
                    }
                }); // end $.post()
            }); // end drop_anchors.each()
        });
    }
};

AJAX.registerOnload('triggers.js', function () {
    /**
     * Attach Ajax event handlers for the Add/Edit functionality.
     */
    $(document).on('click', 'a.ajax.add_anchor, a.ajax.edit_anchor', function (event) {
        event.preventDefault();

        if ($(this).hasClass('add_anchor')) {
            // @ts-ignore
            $.datepicker.initialized = false;
        }

        DatabaseTriggers.editorDialog($(this).hasClass('add_anchor'), $(this));
    });

    /**
     * Attach Ajax event handlers for Export
     */
    $(document).on('click', 'a.ajax.export_anchor', function (event) {
        event.preventDefault();
        DatabaseTriggers.exportDialog($(this));
    });

    $(document).on('click', '#bulkActionExportButton', function (event) {
        event.preventDefault();
        DatabaseTriggers.exportDialog($(this));
    });

    /**
     * Attach Ajax event handlers for Drop functionality
     */
    $(document).on('click', 'a.ajax.drop_anchor', function (event) {
        event.preventDefault();
        DatabaseTriggers.dropDialog($(this));
    });

    $(document).on('click', '#bulkActionDropButton', function (event) {
        event.preventDefault();
        DatabaseTriggers.dropMultipleDialog($(this));
    });
});
