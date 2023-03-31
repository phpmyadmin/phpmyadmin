import $ from 'jquery';

/**
 * Hides/shows the inputs and submits appropriately depending
 * on whether the index type chosen is 'SPATIAL' or not.
 */
export default function checkIndexType () {
    /**
     * @var {JQuery<HTMLElement}, Dropdown to select the index choice.
     */
    var $selectIndexChoice = $('#select_index_choice');
    /**
     * @var {JQuery<HTMLElement}, Dropdown to select the index type.
     */
    var $selectIndexType = $('#select_index_type');
    /**
     * @var {JQuery<HTMLElement}, Table header for the size column.
     */
    var $sizeHeader = $('#index_columns').find('thead tr').children('th').eq(1);
    /**
     * @var {JQuery<HTMLElement}, Inputs to specify the columns for the index.
     */
    var $columnInputs = $('select[name="index[columns][names][]"]');
    /**
     * @var {JQuery<HTMLElement}, Inputs to specify sizes for columns of the index.
     */
    var $sizeInputs = $('input[name="index[columns][sub_parts][]"]');
    /**
     * @var {JQuery<HTMLElement}, Footer containing the controllers to add more columns
     */
    var $addMore = $('#index_frm').find('.add_more');

    if ($selectIndexChoice.val() === 'SPATIAL') {
        // Disable and hide the size column
        $sizeHeader.hide();
        $sizeInputs.each(function () {
            $(this)
                .prop('disabled', true)
                .parent('td').hide();
        });

        // Disable and hide the columns of the index other than the first one
        var initial = true;
        $columnInputs.each(function () {
            var $columnInput = $(this);
            if (! initial) {
                $columnInput
                    .prop('disabled', true)
                    .parent('td').hide();
            } else {
                initial = false;
            }
        });

        // Hide controllers to add more columns
        $addMore.hide();
    } else {
        // Enable and show the size column
        $sizeHeader.show();
        $sizeInputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Enable and show the columns of the index
        $columnInputs.each(function () {
            $(this)
                .prop('disabled', false)
                .parent('td').show();
        });

        // Show controllers to add more columns
        $addMore.show();
    }

    if ($selectIndexChoice.val() === 'SPATIAL' ||
        $selectIndexChoice.val() === 'FULLTEXT') {
        $selectIndexType.val('').prop('disabled', true);
    } else {
        $selectIndexType.prop('disabled', false);
    }
}
