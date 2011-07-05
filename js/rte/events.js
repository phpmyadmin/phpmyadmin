/* vim: set expandtab sw=4 ts=4 sts=4: */

RTE.validateCustom = function () {
    /**
     * @var    $elm    a jQuery object containing the reference
     *                 to an element that is being validated.
     */
    var $elm = null;
    if ($('select[name=item_type]').find(':selected').val() === 'RECURRING') {
        $elm = $('input[name=item_interval_value]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    } else {
        $elm = $('input[name=item_execute_at]');
        if ($elm.val() === '') {
            $elm.focus();
            alert(PMA_messages['strFormEmpty']);
            return false;
        }
    }
    return true;
};
RTE.postDialogShow = function () {};

/**
 * Attach Ajax event handlers for the "Change event type"
 * functionality in the events editor.
 *
 * @see $cfg['AjaxEnable']
 */
$(document).ready(function () {
    $('select[name=item_type]').live('change', function () {
        $('.recurring_event_row, .onetime_event_row').toggle();
    }); // end $.live()
}); // end of $(document).ready()
