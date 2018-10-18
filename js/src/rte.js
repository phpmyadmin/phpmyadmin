/* vim: set expandtab sw=4 ts=4 sts=4: */

/* Module import */
import RTE from './classes/RTE';

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
        if (type.indexOf('routine') !== -1) {
            type = 'routine';
        } else if (type.indexOf('trigger') !== -1) {
            type = 'trigger';
        } else if (type.indexOf('event') !== -1) {
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

    $(document).on('click', '#rteListForm.ajax .mult_submit[value="export"]', function (event) {
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

    $(document).on('click', '#rteListForm.ajax .mult_submit[value="drop"]', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.dropMultipleDialog($(this));
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
        RTE.ROUTINE.reindexParameters();
    }); // end $(document).on()
}); // end of $()
