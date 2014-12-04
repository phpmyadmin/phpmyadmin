/**
 * Unbind all event handlers before tearing down the page
 */
AJAX.registerTeardown('tbl_tracking.js', function () {
    $('body').off('click', '#versionsForm.ajax button[name="submit_mult"], #versionsForm.ajax input[name="submit_mult"]');
    $('body').off('click', 'a.delete_version_anchor.ajax');
});

/**
 * Bind event handlers
 */
AJAX.registerOnload('tbl_tracking.js', function () {

    /**
     * Handles multi submit for tracking versions
     */
    $('body').on('click', '#versionsForm.ajax button[name="submit_mult"], #versionsForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.parent('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();

        if ($button.val() == 'delete_version') {
            var question = PMA_messages.strDeleteTrackingVersionMultiple;
            $button.PMA_confirm(question, $form.attr('action'), function (url) {
                PMA_ajaxShowMessage();
                $.get(url, submitData, AJAX.responseHandler);
            });
        } else {
            PMA_ajaxShowMessage();
            $.get($form.attr('action'), submitData, AJAX.responseHandler);
        }
    });

    /**
     * Ajax Event handler for 'Delete version'
     */
    $('body').on('click', 'a.delete_version_anchor.ajax', function (e) {
        e.preventDefault();
        var $anchor = $(this);
        var question = PMA_messages.strDeleteTrackingVersion;
        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            PMA_ajaxShowMessage();
            $.get(url, {'ajax_page_request': true, 'ajax_request': true}, AJAX.responseHandler);
        });
    });
});