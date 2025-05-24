function formatByte (value, index) {
    let val = value;
    let i = index;
    const units = [
        window.Messages.strB,
        window.Messages.strKiB,
        window.Messages.strMiB,
        window.Messages.strGiB,
        window.Messages.strTiB,
        window.Messages.strPiB,
        window.Messages.strEiB,
    ];
    while (val >= 1024 && i <= 6) {
        val /= 1024;
        i++;
    }

    let format = '%.1f';
    if (Math.floor(val) === val) {
        format = '%.0f';
    }

    return window.sprintf(
        format + ' ' + units[i], val
    );
}

/**
 * The index indicates what unit the incoming data will be in.
 * 0 for bytes, 1 for kilobytes and so on...
 *
 * @param index
 *
 * @return {string}
 */
export default function chartByteFormatter (index) {
    const i = index || 0;

    return function (format, value) {
        let val = value;
        if (typeof val === 'number') {
            val = parseFloat(val.toString()) || 0;

            return formatByte(val, i);
        } else {
            return String(val);
        }
    };
}
