/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Overriding the validateCustom() function defined in common.js
 */
RTE.validateCustom = function () {
    /**
     * @var    $elm    a jQuery object containing the reference
     *                 to an element that is being validated.
     */
    var $elm = null;
    if ($('select[name=item_type]').find(':selected').val() === 'RECURRING') {
        // The interval field must not be empty for recurring events
        $elm = $('input[name=item_interval_value]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    } else {
        // The execute_at field must not be empty for "once off" events
        $elm = $('input[name=item_execute_at]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    }
    return true;
}; // end RTE.validateCustom()

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('rte/events.js', function () {
    $('select[name=item_type]').die('change');
});

/**
 * Attach Ajax event handlers for the "Change event type"
 * functionality in the events editor, so that the correct
 * rows are shown in the editor when changing the event type
 *
 * @see $cfg['AjaxEnable']
 */
AJAX.registerOnload('rte/events.js', function () {
    $('select[name=item_type]').live('change', function () {
        $('tr.recurring_event_row, tr.onetime_event_row').toggle();
    }); // end $.live()
}); // end of $()
