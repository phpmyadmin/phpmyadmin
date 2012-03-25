/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used for index manipulation pages
 *
 */

/**
 * Hides/shows the inputs and submits appropriately depending
 * on whether the index type chosen is 'SPATIAL' or not.
 */
function checkIndexType()
{
    /**
     * @var Object Dropdown to select the index type.
     */
    $select_index_type = $('#select_index_type');
    /**
     * @var Object Table header for the size column.
     */
    $size_header = $('#index_columns thead tr th:nth-child(2)');
    /**
     * @var Object Inputs to specify the columns for the index.
     */
    $column_inputs = $('select[name="index[columns][names][]"]');
    /**
     * @var Object Inputs to specify sizes for columns of the index.
     */
    $size_inputs = $('input[name="index[columns][sub_parts][]"]');
    /**
     * @var Object Footer containg the controllers to add more columns
     */
    $add_more = $('#index_frm .tblFooters');

    if ($select_index_type.val() == 'SPATIAL') {
        // Disable and hide the size column
        $size_header.hide();
        $size_inputs.each(function(){
            $(this)
                .attr('disabled', true)
                .parent('td').hide();
        });

        // Disable and hide the columns of the index other than the first one
        var initial = true;
        $column_inputs.each(function() {
            $column_input = $(this);
            if (! initial) {
                $column_input
                    .attr('disabled', true)
                    .parent('td').hide();
            } else {
                initial = false;
            }
        });

        // Hide controllers to add more columns
        $add_more.hide();
    } else {
        // Enable and show the size column
        $size_header.show();
        $size_inputs.each(function() {
            $(this)
                .attr('disabled', false)
                .parent('td').show();
        });

        // Enable and show the columns of the index
        $column_inputs.each(function() {
            $(this)
                .attr('disabled', false)
                .parent('td').show();
        });

        // Show controllers to add more columns
        $add_more.show();
    }
}

/**#@+
 * @namespace   jQuery
 */

/**
 * @description <p>Ajax scripts for table index page</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Showing/hiding inputs depending on the index type chosen</li>
 * </ul>
 *
 * @name        document.ready
 * @memberOf    jQuery
 */
$(document).ready(function() {
    checkIndexType();
    checkIndexName("index_frm");
    $('#select_index_type').live('change', function(event){
        event.preventDefault();
        checkIndexType();
        checkIndexName("index_frm");
    });
});

/**#@- */
