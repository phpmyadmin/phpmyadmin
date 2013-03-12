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
    $(".add_index.ajax").unbind('click');
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
    
    /**
     *  Ajax event handler for add index
    **/
    $(".add_index.ajax").click(function(event) {
        event.preventDefault();
        if ($(this).find("a").length == 0) {
            // Add index
            var valid = checkFormElementInRange(
                $(this).closest('form')[0],
                'added_fields',
                'Column count has to be larger than zero.'
            );
            if (! valid) {
                return;
            }
            var url = $(this).closest('form').serialize();
            var title = PMA_messages['strAddIndex'];
        } else {
            // Edit index
            var url = $(this).find("a").attr("href");
            if (url.substring(0, 16) == "tbl_indexes.php?") {
                url = url.substring(16, url.length);
            }
            var title = PMA_messages['strEditIndex'];
        }
        url += "&ajax_request=true";
        addIndexDialog(url, title);
    });

});

function addIndexDialog(url, title)
{
    /*Remove the hidden dialogs if there are*/
    if ($('#edit_index_dialog').length != 0) {
        $('#edit_index_dialog').remove();
    }
    var $div = $('<div id="edit_index_dialog"></div>');

    /**
     * @var button_options Object that stores the options
     *                     passed to jQueryUI dialog
     */
    var button_options = {};
    button_options[PMA_messages['strGo']] = function() {
        /**
         * @var    the_form    object referring to the export form
         */
        var $form = $("#index_frm");
        PMA_prepareForAjaxRequest($form);
        //User wants to submit the form
        $.post($form.attr('action'), $form.serialize()+"&do_save_data=1", function(data) {
            if ($("#sqlqueryresults").length != 0) {
                $("#sqlqueryresults").remove();
            }
            if (data.success == true) {
                // refresh the data in the page when index is created
                PMA_commonActions.refreshMain(false, function () {
                    PMA_ajaxShowMessage(data.message);
                    if ($('#result_query').length) {
                        $('#result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div id="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#page_content');
                    }
                    $("#result_query .notice").remove();
                    $("#result_query").prepend(data.message);
                    /*Reload the field form*/
                    $("#table_index").remove();
                    $('div.no_indexes_defined').hide();                    
                });                
            } else {
                var $temp_div = $("<div id='temp_div'><div>").append(data.error);
                if ($temp_div.find(".error code").length != 0) {
                    var $error = $temp_div.find(".error code").addClass("error");
                } else {
                    var $error = $temp_div;
                }
                PMA_ajaxShowMessage($error, false);
            }
        }); // end $.post()
    };
    button_options[PMA_messages['strCancel']] = function() {
        $(this).dialog('close');
    };
    var $msgbox = PMA_ajaxShowMessage();
    $.get("tbl_indexes.php", url, function(data) {
        if (data.success == false) {
            //in the case of an error, show the error message returned.
            PMA_ajaxShowMessage(data.error, false);
        } else {
            PMA_ajaxRemoveMessage($msgbox);
            // Show dialog if the request was successful
            $div
            .append(data.message)
            .dialog({
                title: title,
                width: 450,
                open: PMA_verifyColumnsProperties,
                modal: true,
                buttons: button_options,
                close: function () {
                    $(this).remove();
                }
            });
            checkIndexType();
            checkIndexName("index_frm");
            PMA_showHints($div);
            // Add a slider for selecting how many columns to add to the index
            $div.find('.slider').slider({
                animate: true,
                value: 1,
                min: 1,
                max: 16,
                slide: function( event, ui ) {
                    $(this).closest('fieldset').find('input[type=submit]').val(
                        $.sprintf(PMA_messages['strAddToIndex'], ui.value)
                    );
                }
            });
            // focus index size input on column picked
            $div.find('table#index_columns select').change(function() {
                if ($(this).find("option:selected").val() == '') {
                    return true;
                }
                $(this).closest("tr").find("input").focus();
            });
            // Focus the slider, otherwise it looks nearly transparent
            $('a.ui-slider-handle').addClass('ui-state-focus');
            // set focus on index name input, if empty
            var input = $div.find('input#input_index_name');
            input.val() || input.focus();
        }
    }); // end $.get()
}
