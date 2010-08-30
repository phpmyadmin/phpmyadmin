/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for tbl_relation.php 
 *
 */
function show_hide_clauses(thisDropdown) {
    // here, one span contains the label and the clause dropdown
    // and we have one span for ON DELETE and one for ON UPDATE
    //
    if (thisDropdown.val() != '') {
        thisDropdown.parent().next('span').show().next('span').show();
    } else {
        thisDropdown.parent().next('span').hide().next('span').hide();
    }
}

$(document).ready(function() {
    // initial display
    $('.referenced_column_dropdown').each(function(index, one_dropdown) {
        show_hide_clauses($(one_dropdown));
    });
    // change
    $('.referenced_column_dropdown').change(function() {
        show_hide_clauses($(this));
    });
});
