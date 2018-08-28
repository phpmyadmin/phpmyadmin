/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Get the field name for the current field.  Required to construct the query
 * for grid editing
 *
 * @param $table_results enclosing results table
 * @param $this_field    jQuery object that points to the current field's tr
 */
export function getFieldName ($table_results, $this_field) {
    var this_field_index = $this_field.index();
    // ltr or rtl direction does not impact how the DOM was generated
    // check if the action column in the left exist
    var left_action_exist = !$table_results.find('th:first').hasClass('draggable');
    // number of column span for checkbox and Actions
    var left_action_skip = left_action_exist ? $table_results.find('th:first').attr('colspan') - 1 : 0;

    // If this column was sorted, the text of the a element contains something
    // like <small>1</small> that is useful to indicate the order in case
    // of a sort on multiple columns; however, we dont want this as part
    // of the column name so we strip it ( .clone() to .end() )
    var field_name = $table_results
        .find('thead')
        .find('th:eq(' + (this_field_index - left_action_skip) + ') a')
        .clone()    // clone the element
        .children() // select all the children
        .remove()   // remove all of them
        .end()      // go back to the selected element
        .text();    // grab the text
    // happens when just one row (headings contain no a)
    if (field_name === '') {
        var $heading = $table_results.find('thead').find('th:eq(' + (this_field_index - left_action_skip) + ')').children('span');
        // may contain column comment enclosed in a span - detach it temporarily to read the column name
        var $tempColComment = $heading.children().detach();
        field_name = $heading.text();
        // re-attach the column comment
        $heading.append($tempColComment);
    }

    field_name = $.trim(field_name);

    return field_name;
}
