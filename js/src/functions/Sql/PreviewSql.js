import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
import PMA_commonParams from '../../variables/common_params';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from '../../utils/show_ajax_messages';
/**
 * Requests SQL for previewing before executing.
 *
 * @param jQuery Object $form Form containing query data
 *
 * @return void
 */
export function PMA_previewSQL ($form) {
    var form_url = $form.attr('action');
    var sep = PMA_commonParams.get('arg_separator');
    var form_data = $form.serialize() +
        sep + 'do_save_data=1' +
        sep + 'preview_sql=1' +
        sep + 'ajax_request=1';
    var $msgbox = PMA_ajaxShowMessage();
    $.ajax({
        type: 'POST',
        url: form_url,
        data: form_data,
        success: function (response) {
            PMA_ajaxRemoveMessage($msgbox);
            if (response.success) {
                var $dialog_content = $('<div/>')
                    .append(response.sql_data);
                var button_options = {};
                button_options[PMA_messages.strClose] = function () {
                    $(this).dialog('close');
                };
                var $response_dialog = $dialog_content.dialog({
                    minWidth: 550,
                    maxHeight: 400,
                    modal: true,
                    buttons: button_options,
                    title: PMA_messages.strPreviewSQL,
                    close: function () {
                        $(this).remove();
                    },
                    open: function () {
                        // Pretty SQL printing.
                        PMA_highlightSQL($(this));
                    }
                });
            } else {
                PMA_ajaxShowMessage(response.message);
            }
        },
        error: function () {
            PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest);
        }
    });
}
