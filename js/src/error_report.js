import ErrorReport from './classes/ErrorReport';
import TraceKit from 'tracekit';

export function onload1 () {
    TraceKit.report.subscribe(ErrorReport.error_handler);
    ErrorReport.set_up_error_reporting();
    ErrorReport.wrap_global_functions();
}
