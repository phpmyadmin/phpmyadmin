import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { AJAX } from '../modules/ajax.ts';
import { addDatepicker, getSqlEditor, slidingMessage } from '../modules/functions.ts';
import { Navigation } from '../modules/navigation.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import getJsConfirmCommonParam from '../modules/functions/getJsConfirmCommonParam.ts';

AJAX.registerTeardown('database/routines.js', function () {
    $(document).off('click', 'a.ajax.add_anchor');
    $(document).off('click', 'a.ajax.edit_anchor');
    $(document).off('click', 'a.ajax.exec_anchor');
    $(document).off('click', 'a.ajax.export_anchor');
    $(document).off('click', '#bulkActionExportButton');
    $(document).off('click', 'a.ajax.drop_anchor');
    $(document).off('click', '#bulkActionDropButton');
    $(document).off('change', 'select[name=item_type]');
    $(document).off('change', 'select[name^=item_param_type]');
    $(document).off('change', 'select[name=item_returntype]');
    $(document).off('click', '#addRoutineParameterButton');
    $(document).off('click', 'a.routine_param_remove_anchor');
});

const DatabaseRoutines = {
    /**
     * @var {string} paramTemplate Template for a row in the routine editor
     */
    paramTemplate: '',
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
    },

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

            // No routine is exportable (due to privilege issues)
            if (count === 0) {
                ajaxShowMessage(window.Messages.NoExportable);
            }

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

            const routinesExportTextarea = '<textarea id="routinesExportTextarea" cols="40" rows="15" class="form-control" aria-label="' + window.Messages.strRoutine + '"></textarea>';
            const routinesExportModal = document.getElementById('routinesExportModal');
            routinesExportModal.addEventListener('shown.bs.modal', function () {
                routinesExportModal.querySelector('.modal-title').textContent = data.title;
                routinesExportModal.querySelector('.modal-body').innerHTML = routinesExportTextarea;
                document.getElementById('routinesExportTextarea').textContent = data.message;
                getSqlEditor($('#routinesExportTextarea'));
            });

            routinesExportModal.addEventListener('hidden.bs.modal', function () {
                routinesExportModal.querySelector('.modal-body').innerHTML = routinesExportTextarea;
            });

            bootstrap.Modal.getOrCreateInstance(routinesExportModal).show();
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

            const routinesEditorModalSaveEventHandler = function () {
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
                    bootstrap.Modal.getOrCreateInstance('#routinesEditorModal').hide();

                    var tableId = '#' + data.tableType + 'Table';
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
                        $(tableId + '.data').find('tr').each(function () {
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
                            $(tableId + '.data').append(data.new_row);
                        }

                        // Fade-in the new row
                        $('tr.ajaxInsert')
                            .show('slow')
                            .removeClass('ajaxInsert');
                    } else if ($(tableId + '.data').find('tr').has('td').length === 0) {
                        // If we are not supposed to insert the new row,
                        // we will now check if the table is empty and
                        // needs to be hidden. This will be the case if
                        // we were editing the only item in the list,
                        // which we removed and will not be inserting
                        // something else in its place.
                        $(tableId + '.data').hide('slow', function () {
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
                    $(tableId + '.data').find('tr').has('td').each(function () {
                        rowclass = (ct % 2 === 0) ? 'odd' : 'even';
                        $(this).removeClass('odd even').addClass(rowclass);
                        ct++;
                    });

                    // If this is the first item being added, remove
                    // the "No items" message and show the list.
                    if ($(tableId + '.data').find('tr').has('td').length > 0 &&
                        $('#nothing2display').is(':visible')
                    ) {
                        $('#nothing2display').hide('slow', function () {
                            $(tableId + '.data').show('slow');
                        });
                    }

                    Navigation.reload();
                });
            };

            const routinesEditorModal = document.getElementById('routinesEditorModal');

            routinesEditorModal.addEventListener('shown.bs.modal', function () {
                /**
                 * Issue #15810 - use button titles for modals (eg: new procedure)
                 * Respect the order: title on href tag, href content, title sent in response
                 */
                routinesEditorModal.querySelector('.modal-title').textContent = $this.attr('title') || $this.text() || $(data.title).text();
                routinesEditorModal.querySelector('.modal-body').innerHTML = data.message;

                const routinesEditorModalSaveButton = document.getElementById('routinesEditorModalSaveButton');
                routinesEditorModalSaveButton?.addEventListener('click', routinesEditorModalSaveEventHandler);

                $(this).find('input[name=item_name]').trigger('focus');
                $(this).find('input.datefield').each(function () {
                    addDatepicker($(this).css('width', '95%'), 'date');
                });

                $(this).find('input.datetimefield').each(function () {
                    addDatepicker($(this).css('width', '95%'), 'datetime');
                });

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
                    editorType: 'routine',
                };
                that.syntaxHiglighter = getSqlEditor($elm, {}, 'vertical', linterOptions);
                window.codeMirrorEditor = that.syntaxHiglighter;

                // Execute item-specific code
                that.postDialogShow(data);
            });

            routinesEditorModal.addEventListener('hidden.bs.modal', function () {
                const routinesEditorModalSaveButton = document.getElementById('routinesEditorModalSaveButton');
                routinesEditorModalSaveButton?.removeEventListener('click', routinesEditorModalSaveEventHandler);
                document.getElementById('routinesEditorModal').querySelector('.modal-body').innerHTML = '<div class="spinner-border" role="status">' +
                    '<span class="visually-hidden">' + window.Messages.strLoading + '</span></div>';
            });

            bootstrap.Modal.getOrCreateInstance(routinesEditorModal).show();
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
                var $table = $currRow.parent().parent();
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
                            $(this).removeClass('odd even').addClass(rowclass);
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
                    var $table = $currRow.parent().parent();
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
                                $(this).removeClass('odd even').addClass(rowclass);
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
    },

    /**
     * Execute some code after the ajax dialog for the editor is shown.
     *
     * @param data JSON-encoded data from the ajax request
     */
    postDialogShow: function (data) {
        // Cache the template for a parameter table row
        DatabaseRoutines.paramTemplate = data.paramTemplate;
        var that = this;
        // Make adjustments in the dialog to make it AJAX compatible
        $('td.routine_param_remove').show();
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

        // Allow changing parameter order
        $('.routine_params_table tbody').sortable({
            containment: '.routine_params_table tbody',
            handle: '.dragHandle',
            stop: function () {
                that.reindexParameters();
            },
        });
    },
    /**
     * Reindexes the parameters after dropping a parameter or reordering parameters
     */
    reindexParameters: function () {
        /**
         * @var index Counter used for reindexing the input
         *            fields in the routine parameters table
         */
        var index = 0;
        $('table.routine_params_table tbody').find('tr').each(function () {
            $(this).find(':input').each(function () {
                /**
                 * @var inputname The value of the name attribute of
                 *                the input field being reindexed
                 */
                var inputname = $(this).attr('name');
                if (inputname.startsWith('item_param_dir')) {
                    $(this).attr('name', inputname.substring(0, 14) + '[' + index + ']');
                } else if (inputname.startsWith('item_param_name')) {
                    $(this).attr('name', inputname.substring(0, 15) + '[' + index + ']');
                } else if (inputname.startsWith('item_param_type')) {
                    $(this).attr('name', inputname.substring(0, 15) + '[' + index + ']');
                } else if (inputname.startsWith('item_param_length')) {
                    $(this).attr('name', inputname.substring(0, 17) + '[' + index + ']');
                    $(this).attr('id', 'item_param_length_' + index);
                } else if (inputname.startsWith('item_param_opts_text')) {
                    $(this).attr('name', inputname.substring(0, 20) + '[' + index + ']');
                } else if (inputname.startsWith('item_param_opts_num')) {
                    $(this).attr('name', inputname.substring(0, 19) + '[' + index + ']');
                }
            });

            index++;
        });
    },
    /**
     * Validate custom editor form fields.
     *
     * @return {boolean}
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

        const routinesEditorModal = $('#routinesEditorModal');
        routinesEditorModal.find('table.routine_params_table').last().find('tr').each(function () {
            // Every parameter of a routine must have
            // a non-empty direction, name and type
            if (! isSuccess) {
                return false;
            }

            $(this).find(':input').each(function () {
                inputname = $(this).attr('name');
                if (inputname.startsWith('item_param_dir') ||
                    inputname.startsWith('item_param_name') ||
                    inputname.startsWith('item_param_type')) {
                    if ($(this).val() === '') {
                        $(this).trigger('focus');
                        isSuccess = false;

                        return false;
                    }
                }
            });
        });

        if (! isSuccess) {
            alert(window.Messages.strFormEmpty);

            return false;
        }

        routinesEditorModal.find('table.routine_params_table').last().find('tr').each(function () {
            // SET, ENUM, VARCHAR and VARBINARY fields must have length/values
            var $inputtyp = $(this).find('select[name^=item_param_type]');
            var $inputlen = $(this).find('input[name^=item_param_length]');
            if ($inputtyp.length && $inputlen.length) {
                if (($inputtyp.val() === 'ENUM' || $inputtyp.val() === 'SET' || ($inputtyp.val() as string).startsWith('VAR')) &&
                    $inputlen.val() === ''
                ) {
                    $inputlen.trigger('focus');
                    isSuccess = false;

                    return false;
                }
            }
        });

        if (! isSuccess) {
            alert(window.Messages.strFormEmpty);

            return false;
        }

        if (routinesEditorModal.find('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
            // The length/values of return variable for functions must
            // be set, if the type is SET, ENUM, VARCHAR or VARBINARY.
            var $returntyp = routinesEditorModal.find('select[name=item_returntype]');
            var $returnlen = routinesEditorModal.find('input[name=item_returnlength]');
            if (($returntyp.val() === 'ENUM' || $returntyp.val() === 'SET' || ($returntyp.val() as string).startsWith('VAR')) &&
                $returnlen.val() === ''
            ) {
                $returnlen.trigger('focus');
                alert(window.Messages.strFormEmpty);

                return false;
            }
        }

        if ($('select[name=item_type]').find(':selected').val() === 'FUNCTION') {
            // A function must contain a RETURN statement in its definition
            const itemDefinitionValue = (routinesEditorModal.find('table.rte_table').find('textarea[name=item_definition]').val() as string);
            if (itemDefinitionValue.toUpperCase().indexOf('RETURN') < 0) {
                this.syntaxHiglighter.focus();
                alert(window.Messages.MissingReturn);

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
     * @param $type a jQuery object containing the reference
     *              to the "Type" dropdown box
     * @param $len  a jQuery object containing the reference
     *              to the "Length" input box
     * @param $text a jQuery object containing the reference
     *              to the dropdown box with options for
     *              parameters of text type
     * @param $num  a jQuery object containing the reference
     *              to the dropdown box with options for
     *              parameters of numeric type
     */
    setOptionsForParameter: function ($type, $len, $text, $num) {
        /**
         * @var no_opts a jQuery object containing the reference
         *              to an element to be displayed when no
         *              options are available
         */
        var $noOpts = $text.parent().parent().find('.no_opts');
        /**
         * @var no_len a jQuery object containing the reference
         *             to an element to be displayed when no
         *             "length/values" field is available
         */
        var $noLen = $len.parent().parent().find('.no_len');

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
            $noOpts.hide();
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
            $noOpts.hide();
            break;
        default:
            $text.parent().hide();
            $num.parent().hide();
            $noOpts.show();
            break;
        }

        // Process for parameter length
        switch ($type.val()) {
        case 'DATE':
        case 'TINYBLOB':
        case 'TINYTEXT':
        case 'BLOB':
        case 'TEXT':
        case 'MEDIUMBLOB':
        case 'MEDIUMTEXT':
        case 'LONGBLOB':
        case 'LONGTEXT':
            $text.closest('tr').find('a').first().hide();
            $len.parent().hide();
            $noLen.show();
            break;
        default:
            if ($type.val() === 'ENUM' || $type.val() === 'SET') {
                $text.closest('tr').find('a').first().show();
            } else {
                $text.closest('tr').find('a').first().hide();
            }

            $len.parent().show();
            $noLen.hide();
            break;
        }
    },
    executeDialog: function ($this) {
        /**
         * @var msg jQuery object containing the reference to
         *          the AJAX message shown to the user
         */
        var $msg = ajaxShowMessage();
        var params = getJsConfirmCommonParam($this[0], $this.getPostData());
        $.post($this.attr('href'), params, function (data) {
            if (data.success !== true) {
                ajaxShowMessage(data.error, false);

                return;
            }

            ajaxRemoveMessage($msg);
            // If 'data.dialog' is true we show a dialog with a form
            // to get the input parameters for routine, otherwise
            // we just show the results of the query
            if (! data.dialog) {
                // Routine executed successfully
                slidingMessage(data.message);

                return;
            }

            const routinesExecuteModal = document.getElementById('routinesExecuteModal');
            const modal = bootstrap.Modal.getOrCreateInstance(routinesExecuteModal);

            const routinesExecuteButtonEventHandler = function () {
                /**
                 * @var data Form data to be sent in the AJAX request
                 */
                var data = $('form.rte_form').last().serialize();
                $msg = ajaxShowMessage(
                    window.Messages.strProcessingRequest
                );

                $.post('index.php?route=/database/routines', data, function (data) {
                    if (data.success === true) {
                        // Routine executed successfully
                        ajaxRemoveMessage($msg);
                        slidingMessage(data.message);
                        modal.hide();
                    } else {
                        ajaxShowMessage(data.error, false);
                    }
                });
            };

            routinesExecuteModal.addEventListener('hidden.bs.modal', function () {
                routinesExecuteModal.querySelector('.modal-title').textContent = '';
                routinesExecuteModal.querySelector('.modal-body').innerHTML = '';

                const routinesExecuteModalExecuteButton = document.getElementById('routinesExecuteModalExecuteButton');
                routinesExecuteModalExecuteButton?.removeEventListener('click', routinesExecuteButtonEventHandler);
            });

            routinesExecuteModal.addEventListener('shown.bs.modal', function () {
                routinesExecuteModal.querySelector('.modal-title').textContent = data.title;
                routinesExecuteModal.querySelector('.modal-body').innerHTML = data.message;

                const routinesExecuteModalExecuteButton = document.getElementById('routinesExecuteModalExecuteButton');
                routinesExecuteModalExecuteButton?.addEventListener('click', routinesExecuteButtonEventHandler);

                const modalBody = $('#routinesExecuteModal .modal-body');
                modalBody.find('input[name^=params]').first().trigger('focus');
                /**
                 * Attach the datepickers to the relevant form fields
                 */
                modalBody.find('input.datefield, input.datetimefield').each(function () {
                    addDatepicker($(this).css('width', '95%'));
                });

                /*
                * Define the function if the user presses enter
                */
                $('form.rte_form').on('keyup', function (event) {
                    event.preventDefault();
                    if (event.keyCode !== 13) {
                        return;
                    }

                    /**
                     * @var data Form data to be sent in the AJAX request
                     */
                    var data = $(this).serialize();
                    $msg = ajaxShowMessage(
                        window.Messages.strProcessingRequest
                    );

                    var url = $(this).attr('action');
                    $.post(url, data, function (data) {
                        if (data.success !== true) {
                            ajaxShowMessage(data.error, false);

                            return;
                        }

                        // Routine executed successfully
                        ajaxRemoveMessage($msg);
                        slidingMessage(data.message);
                        $('form.rte_form').off('keyup');
                        modal.hide();
                    });
                });
            });

            modal.show();
        });
    }
};

AJAX.registerOnload('database/routines.js', function () {
    $(document).on('click', 'a.ajax.add_anchor', function (event) {
        event.preventDefault();
        DatabaseRoutines.editorDialog(true, $(this));
    });

    $(document).on('click', 'a.ajax.edit_anchor', function (event) {
        event.preventDefault();
        DatabaseRoutines.editorDialog(false, $(this));
    });

    $(document).on('click', 'a.ajax.exec_anchor', function (event) {
        event.preventDefault();
        DatabaseRoutines.executeDialog($(this));
    });

    $(document).on('click', 'a.ajax.export_anchor', function (event) {
        event.preventDefault();
        DatabaseRoutines.exportDialog($(this));
    });

    $(document).on('click', '#bulkActionExportButton', function (event) {
        event.preventDefault();
        DatabaseRoutines.exportDialog($(this));
    });

    $(document).on('click', 'a.ajax.drop_anchor', function (event) {
        event.preventDefault();
        DatabaseRoutines.dropDialog($(this));
    });

    $(document).on('click', '#bulkActionDropButton', function (event) {
        event.preventDefault();
        DatabaseRoutines.dropMultipleDialog($(this));
    });

    $(document).on('change', 'select[name=item_type]', function () {
        $(this).closest('table').find('tr.routine_return_row, .routine_direction_cell').toggle();
    });

    $(document).on('change', 'select[name^=item_param_type]', function () {
        const $row = $(this).parents('tr').first();
        DatabaseRoutines.setOptionsForParameter(
            $row.find('select[name^=item_param_type]'),
            $row.find('input[name^=item_param_length]'),
            $row.find('select[name^=item_param_opts_text]'),
            $row.find('select[name^=item_param_opts_num]')
        );
    });

    $(document).on('change', 'select[name=item_returntype]', function () {
        const $table = $(this).closest('table.rte_table');
        DatabaseRoutines.setOptionsForParameter(
            $table.find('select[name=item_returntype]'),
            $table.find('input[name=item_returnlength]'),
            $table.find('select[name=item_returnopts_text]'),
            $table.find('select[name=item_returnopts_num]')
        );
    });

    $(document).on('click', '#addRoutineParameterButton', function (event) {
        event.preventDefault();
        /**
         * @var routine_params_table jQuery object containing the reference
         *                           to the routine parameters table
         */
        const $routineParamsTable = $(this).closest('div.modal').find('.routine_params_table');
        /**
         * @var new_param_row A string containing the HTML code for the
         *                    new row for the routine parameters table
         */
        const newParamRow = DatabaseRoutines.paramTemplate.replace(/%s/g, ($routineParamsTable.find('tr').length - 1).toString());
        // Append the new row to the parameters table
        $routineParamsTable.append(newParamRow);
        // Make sure that the row is correctly shown according to the type of routine
        if ($(this).closest('div.modal').find('table.rte_table select[name=item_type]').val() === 'FUNCTION') {
            $('tr.routine_return_row').show();
            $('td.routine_direction_cell').hide();
        }

        /**
         * @var newrow jQuery object containing the reference to the newly
         *             inserted row in the routine parameters table
         */
        const $newrow = $(this).closest('div.modal').find('table.routine_params_table').find('tr').has('td').last();
        // Enable/disable the 'options' dropdowns for parameters as necessary
        DatabaseRoutines.setOptionsForParameter(
            $newrow.find('select[name^=item_param_type]'),
            $newrow.find('input[name^=item_param_length]'),
            $newrow.find('select[name^=item_param_opts_text]'),
            $newrow.find('select[name^=item_param_opts_num]')
        );
    });

    $(document).on('click', 'a.routine_param_remove_anchor', function (event) {
        event.preventDefault();
        $(this).parent().parent().remove();
        // After removing a parameter, the indices of the name attributes in
        // the input fields lose the correct order and need to be reordered.
        DatabaseRoutines.reindexParameters();
    });
});
