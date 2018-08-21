/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from './variables/export_variables';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from './utils/show_ajax_messages';
import { escapeHtml } from './utils/Sanitise';
import { PMA_commonActions } from './classes/CommonActions';
import { PMA_sprintf } from './utils/sprintf';
import { getJSConfirmCommonParam } from './functions/Common';
import { getDropdownValues } from './functions/Table/Relation';

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownTblRelation () {
    $('body').off('change',
        'select[name^="destination_db"], ' +
        'select[name^="destination_table"], ' +
        'select[name^="destination_foreign_db"], ' +
        'select[name^="destination_foreign_table"]'
    );
    $('body').off('click', 'a.add_foreign_key_field');
    $('body').off('click', 'a.add_foreign_key');
    $('a.drop_foreign_key_anchor.ajax').off('click');
}

export function onloadTblRelation () {
    /**
     * Ajax event handler to fetch table/column dropdown values.
     */
    $('body').on('change',
        'select[name^="destination_db"], ' +
        'select[name^="destination_table"], ' +
        'select[name^="destination_foreign_db"], ' +
        'select[name^="destination_foreign_table"]',
        function () {
            getDropdownValues($(this));
        }
    );

    /**
     * Ajax event handler to add a column to a foreign key constraint.
     */
    $('body').on('click', 'a.add_foreign_key_field', function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Add field.
        $(this)
            .prev('span')
            .clone(true, true)
            .insertBefore($(this))
            .find('select')
            .val('');

        // Add foreign field.
        var $source_elem = $('select[name^="destination_foreign_column[' +
            $(this).attr('data-index') + ']"]:last').parent();
        $source_elem
            .clone(true, true)
            .insertAfter($source_elem)
            .find('select')
            .val('');
    });

    /**
     * Ajax event handler to add a foreign key constraint.
     */
    $('body').on('click', 'a.add_foreign_key', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var $prev_row = $(this).closest('tr').prev('tr');
        var $newRow = $prev_row.clone(true, true);

        // Update serial number.
        var curr_index = $newRow
            .find('a.add_foreign_key_field')
            .attr('data-index');
        var new_index = parseInt(curr_index) + 1;
        $newRow.find('a.add_foreign_key_field').attr('data-index', new_index);

        // Update form parameter names.
        $newRow.find('select[name^="foreign_key_fields_name"]:not(:first), ' +
            'select[name^="destination_foreign_column"]:not(:first)'
        ).each(function () {
            $(this).parent().remove();
        });
        $newRow.find('input, select').each(function () {
            $(this).attr('name',
                $(this).attr('name').replace(/\d/, new_index)
            );
        });
        $newRow.find('input[type="text"]').each(function () {
            $(this).val('');
        });
        // Finally add the row.
        $newRow.insertAfter($prev_row);
    });

    /**
     * Ajax Event handler for 'Drop Foreign key'
     */
    $('a.drop_foreign_key_anchor.ajax').on('click', function (event) {
        event.preventDefault();
        var $anchor = $(this);

        // Object containing reference to the current field's row
        var $curr_row = $anchor.parents('tr');

        var drop_query = escapeHtml(
            $curr_row.children('td')
                .children('.drop_foreign_key_msg')
                .val()
        );

        var question = PMA_sprintf(messages.strDoYouReally, drop_query);

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(messages.strDroppingForeignKey, false);
            var params = getJSConfirmCommonParam(this, $anchor.getPostData());
            $.post(url, params, function (data) {
                if (data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    PMA_commonActions.refreshMain(false, function () {
                        // Do nothing
                    });
                } else {
                    PMA_ajaxShowMessage(messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }); // end $.PMA_confirm()
    }); // end Drop Foreign key

    var windowwidth = $(window).width();
    $('.jsresponsive').css('max-width', (windowwidth - 35) + 'px');
}
