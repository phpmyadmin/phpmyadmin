/**
 * Unbind all event handlers before tearing down the page
 */
AJAX.registerTeardown('db_tracking.js', function () {
    $('body').off('click', '#trackedForm.ajax button[name="submit_mult"], #trackedForm.ajax input[name="submit_mult"]');
    $('body').off('click', '#untrackedForm.ajax button[name="submit_mult"], #untrackedForm.ajax input[name="submit_mult"]');
    $('body').off('click', 'a.delete_tracking_anchor.ajax');
});

/**
 * Bind event handlers
 */
AJAX.registerOnload('db_tracking.js', function () {

    var $versions = $('#versions');
    $versions.find('tr:first th').append($('<div class="sorticon"></div>'));
    $versions.tablesorter({
        sortList: [[1, 0]],
        headers: {
            0: {sorter: false},
            2: {sorter: "integer"},
            5: {sorter: false},
            6: {sorter: false},
            7: {sorter: false}
        }
    });

    var $noVersions = $('#noversions');
    $noVersions.find('tr:first th').append($('<div class="sorticon"></div>'));
    $noVersions.tablesorter({
        sortList: [[1, 0]],
        headers: {
            0: {sorter: false},
            2: {sorter: false}
        }
    });

    var $body = $('body');

    /**
     * Handles multi submit for tracked tables
     */
    $body.on('click', '#trackedForm.ajax button[name="submit_mult"], #trackedForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.parent('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();

        if ($button.val() == 'delete_tracking') {
            var question = PMA_messages.strDeleteTrackingDataMultiple;
            $button.PMA_confirm(question, $form.attr('action'), function (url) {
                PMA_ajaxShowMessage(PMA_messages.strDeletingTrackingData);
                AJAX.source = $form;
                $.post(url, submitData, AJAX.responseHandler);
            });
        } else {
            PMA_ajaxShowMessage();
            AJAX.source = $form;
            $.post($form.attr('action'), submitData, AJAX.responseHandler);
        }
    });

    /**
     * Handles multi submit for untracked tables
     */
    $body.on('click', '#untrackedForm.ajax button[name="submit_mult"], #untrackedForm.ajax input[name="submit_mult"]', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.parent('form');
        var submitData = $form.serialize() + '&ajax_request=true&ajax_page_request=true&submit_mult=' + $button.val();
        PMA_ajaxShowMessage();
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    /**
     * Ajax Event handler for 'Delete tracking'
     */
    $body.on('click', 'a.delete_tracking_anchor.ajax', function (e) {
        e.preventDefault();
        var $anchor = $(this);
        var question = PMA_messages.strDeleteTrackingData;
        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            PMA_ajaxShowMessage(PMA_messages.strDeletingTrackingData);
            AJAX.source = $anchor;
            $.get(url, {'ajax_page_request': true, 'ajax_request': true}, AJAX.responseHandler);
        });
    });
});