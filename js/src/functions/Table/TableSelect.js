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
