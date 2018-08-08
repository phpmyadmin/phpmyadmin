export function changeValueFieldType (elem, searchIndex) {
    var fieldsValue = $('select#fieldID_' + searchIndex);
    if (0 === fieldsValue.size()) {
        return;
    }

    var type = $(elem).val();
    if ('IN (...)' === type ||
        'NOT IN (...)' === type ||
        'BETWEEN' === type ||
        'NOT BETWEEN' === type
    ) {
        $('#fieldID_' + searchIndex).attr('multiple', '');
    } else {
        $('#fieldID_' + searchIndex).removeAttr('multiple');
    }
}

/**
 * Checks if given data-type is numeric or date.
 *
 * @param string data_type Column data-type
 *
 * @return bool|string
 */
export function PMA_checkIfDataTypeNumericOrDate (data_type) {
    // To test for numeric data-types.
    var numeric_re = new RegExp(
        'TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|REAL',
        'i'
    );

    // To test for date data-types.
    var date_re = new RegExp(
        'DATETIME|DATE|TIMESTAMP|TIME|YEAR',
        'i'
    );

    // Return matched data-type
    if (numeric_re.test(data_type)) {
        return numeric_re.exec(data_type)[0];
    }

    if (date_re.test(data_type)) {
        return date_re.exec(data_type)[0];
    }

    return false;
}
