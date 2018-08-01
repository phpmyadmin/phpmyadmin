/* vim: set expandtab sw=4 ts=4 sts=4: */
import { changePluginOpts, matchFile } from './functions/import';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import { PMA_Messages as PMA_messages } from './variables/export_variables';
/**
 * Unbind all event handlers before tearing down a page
 */
export function teardown1 () {
    $('#plugins').off('change');
    $('#input_import_file').off('change');
    $('#select_local_import_file').off('change');
    $('#input_import_file').off('change').off('focus');
    $('#select_local_import_file').off('focus');
    $('#text_csv_enclosed').add('#text_csv_escaped').off('keyup');
}

export function onload1 () {
    // import_file_form validation.
    $(document).on('submit', '#import_file_form', function () {
        var radioLocalImport = $('#radio_local_import_file');
        var radioImport = $('#radio_import_file');
        var fileMsg = '<div class="error"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error" /> ' + PMA_messages.strImportDialogMessage + '</div>';

        if (radioLocalImport.length !== 0) {
            // remote upload.

            if (radioImport.is(':checked') && $('#input_import_file').val() === '') {
                $('#input_import_file').trigger('focus');
                PMA_ajaxShowMessage(fileMsg, false);
                return false;
            }

            if (radioLocalImport.is(':checked')) {
                if ($('#select_local_import_file').length === 0) {
                    PMA_ajaxShowMessage('<div class="error"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error" /> ' + PMA_messages.strNoImportFile + ' </div>', false);
                    return false;
                }

                if ($('#select_local_import_file').val() === '') {
                    $('#select_local_import_file').trigger('focus');
                    PMA_ajaxShowMessage(fileMsg, false);
                    return false;
                }
            }
        } else {
            // local upload.
            if ($('#input_import_file').val() === '') {
                $('#input_import_file').trigger('focus');
                PMA_ajaxShowMessage(fileMsg, false);
                return false;
            }
        }

        // show progress bar.
        $('#upload_form_status').css('display', 'inline');
        $('#upload_form_status_info').css('display', 'inline');
    });

    // Initially display the options for the selected plugin
    changePluginOpts();

    // Whenever the selected plugin changes, change the options displayed
    $('#plugins').on('change', function () {
        changePluginOpts();
    });

    $('#input_import_file').on('change', function () {
        matchFile($(this).val());
    });

    $('#select_local_import_file').on('change', function () {
        matchFile($(this).val());
    });

    /*
     * When the "Browse the server" form is clicked or the "Select from the web server upload directory"
     * form is clicked, the radio button beside it becomes selected and the other form becomes disabled.
     */
    $('#input_import_file').on('focus change', function () {
        $('#radio_import_file').prop('checked', true);
        $('#radio_local_import_file').prop('checked', false);
    });
    $('#select_local_import_file').on('focus', function () {
        $('#radio_local_import_file').prop('checked', true);
        $('#radio_import_file').prop('checked', false);
    });

    /**
     * Set up the interface for Javascript-enabled browsers since the default is for
     *  Javascript-disabled browsers
     */
    $('#scroll_to_options_msg').hide();
    $('#format_specific_opts').find('div.format_specific_options')
        .css({
            'border': 0,
            'margin': 0,
            'padding': 0
        })
        .find('h3')
        .remove();
    // $("form[name=import] *").unwrap();

    /**
     * for input element text_csv_enclosed and text_csv_escaped allow just one character to enter.
     * as mysql allows just one character for these fields,
     * if first character is escape then allow two including escape character.
     */
    $('#text_csv_enclosed').add('#text_csv_escaped').on('keyup', function () {
        if ($(this).val().length === 2 && $(this).val().charAt(0) !== '\\') {
            $(this).val($(this).val().substring(0, 1));
            return false;
        }
        return true;
    });
}
