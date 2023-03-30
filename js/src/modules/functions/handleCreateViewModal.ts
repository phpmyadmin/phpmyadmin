import $ from 'jquery';
import { ajaxRemoveMessage, ajaxShowMessage } from '../ajax-message.js';
import { CommonParams } from '../common.js';
import { Functions } from '../functions.js';
import { Navigation } from '../navigation.js';
import getJsConfirmCommonParam from './getJsConfirmCommonParam.js';

/**
 * @param {JQuery<HTMLElement>} $this
 *
 * @return {void}
 */
export default function handleCreateViewModal ($this) {
    var $msg = ajaxShowMessage();
    var sep = CommonParams.get('arg_separator');
    var params = getJsConfirmCommonParam(this, $this.getPostData());
    params += sep + 'ajax_dialog=1';
    $.post($this.attr('href'), params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            ajaxRemoveMessage($msg);
            $('#createViewModalGoButton').on('click', function () {
                if (typeof window.CodeMirror !== 'undefined') {
                    window.codeMirrorEditor.save();
                }
                $msg = ajaxShowMessage();
                $.post('index.php?route=/view/create', $('#createViewModal').find('form').serialize(), function (data) {
                    ajaxRemoveMessage($msg);
                    if (typeof data !== 'undefined' && data.success === true) {
                        $('#createViewModal').modal('hide');
                        $('.result_query').html(data.message);
                        Navigation.reload();
                    } else {
                        ajaxShowMessage(data.error);
                    }
                });
            });
            $('#createViewModal').find('.modal-body').first().html(data.message);
            // Attach syntax highlighted editor
            $('#createViewModal').on('shown.bs.modal', function () {
                window.codeMirrorEditor = Functions.getSqlEditor($('#createViewModal').find('textarea'));
                $('input:visible[type=text]', $('#createViewModal')).first().trigger('focus');
                $('#createViewModal').off('shown.bs.modal');
            });
            $('#createViewModal').modal('show');
        } else {
            ajaxShowMessage(data.error);
        }
    });
}
