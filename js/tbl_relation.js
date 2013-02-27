/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for tbl_relation.php
 *
 */
function show_hide_clauses($thisDropdown)
{
    // here, one span contains the label and the clause dropdown
    // and we have one span for ON DELETE and one for ON UPDATE
    //
    if ($thisDropdown.val() != '') {
        $thisDropdown.parent().nextAll('span').show();
    } else {
        $thisDropdown.parent().nextAll('span').hide();
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_relation.js', function() {
    $('select.referenced_column_dropdown').unbind('change');
});

AJAX.registerOnload('tbl_relation.js', function() {
    // initial display
    $('select.referenced_column_dropdown').each(function(index, one_dropdown) {
        show_hide_clauses($(one_dropdown));
    });
    // change
    $('select.referenced_column_dropdown').change(function() {
        show_hide_clauses($(this));
    });
});
