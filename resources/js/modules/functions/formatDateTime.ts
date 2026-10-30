import $ from 'jquery';

/**
 * Formats timestamp for display
 *
 * @param {Date} date
 * @param {boolean} seconds
 * @return {string}
 */
export default function formatDateTime (date, seconds = false) {
    const result = $.datepicker.formatDate('yy-mm-dd', date);
    let timefmt = 'HH:mm';
    if (seconds) {
        timefmt = 'HH:mm:ss';
    }

    // @ts-ignore
    return result + ' ' + $.datepicker.formatTime(
        timefmt, {
            hour: date.getHours(),
            minute: date.getMinutes(),
            second: date.getSeconds()
        }
    );
}
