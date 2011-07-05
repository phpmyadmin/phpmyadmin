/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * What type of item the editor is for.
 */
RTE.editor = 'event';

/**
 * Attach Ajax event handlers for the "Change event type"
 * functionality in the events editor.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function () {
    $('select[name=item_type]').live('change', function() {
        $('.recurring_event_row, .onetime_event_row').toggle();
    }); // end $.live()
}); // end of $(document).ready()
