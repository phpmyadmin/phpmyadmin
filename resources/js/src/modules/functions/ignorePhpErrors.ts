import $ from 'jquery';

/**
 * Ignore the displayed php errors.
 * Simply removes the displayed errors.
 *
 * @param {boolean} clearPrevErrors whether to clear errors stored
 *             in $_SESSION['prev_errors'] at server
 *
 */
function ignorePhpErrors (clearPrevErrors = undefined) {
    var clearPrevious = clearPrevErrors;
    if (typeof (clearPrevious) === 'undefined' ||
        clearPrevious === null
    ) {
        clearPrevious = false;
    }

    // send AJAX request to /error-report with send_error_report=0, exception_type=php & token.
    // It clears the prev_errors stored in session.
    if (clearPrevious) {
        var $pmaReportErrorsForm = $('#pma_report_errors_form');
        $pmaReportErrorsForm.find('input[name="send_error_report"]').val(0); // change send_error_report to '0'
        $pmaReportErrorsForm.trigger('submit');
    }

    // remove displayed errors
    var $pmaErrors = $('#pma_errors');
    $pmaErrors.fadeOut('slow');
    $pmaErrors.remove();
}

declare global {
    interface Window {
        ignorePhpErrors: typeof ignorePhpErrors;
    }
}

window.ignorePhpErrors = ignorePhpErrors;

export { ignorePhpErrors };
