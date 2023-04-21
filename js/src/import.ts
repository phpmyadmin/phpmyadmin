import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import { Functions } from './modules/functions.ts';
import { Navigation } from './modules/navigation.ts';
import { CommonParams } from './modules/common.ts';
import { ajaxShowMessage } from './modules/ajax-message.ts';

/**
 * Functions used in the import tab
 *
 */


/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 */
function changePluginOpts () {
    $('#format_specific_opts').find('div.format_specific_options').each(function () {
        $(this).hide();
    });

    var selectedPluginName = $('#plugins').find('option:selected').val();
    $('#' + selectedPluginName + '_options').fadeIn('slow');

    const importNotification = document.getElementById('import_notification');
    if (importNotification) {
        importNotification.innerText = '';
        if (selectedPluginName === 'csv') {
            importNotification.innerHTML = '<div class="alert alert-info mb-0 mt-3" role="alert">' + window.Messages.strImportCSV + '</div>';
        }
    }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 *
 * @param {string} fname
 */
function matchFile (fname) {
    var fnameArray = fname.toLowerCase().split('.');
    var len = fnameArray.length;
    if (len !== 0) {
        var extension = fnameArray[len - 1];
        if (extension === 'gz' || extension === 'bz2' || extension === 'zip') {
            len--;
        }

        // Only toggle if the format of the file can be imported
        if ($('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).length === 1) {
            $('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).prop('selected', true);
            changePluginOpts();
        }
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('import.js', function () {
    $('#plugins').off('change');
    $('#input_import_file').off('change');
    $('#select_local_import_file').off('change');
    $('#input_import_file').off('change').off('focus');
    $('#select_local_import_file').off('focus');
    $('#text_csv_enclosed').add('#text_csv_escaped').off('keyup');
    $('#importmain #buttonGo').off('click');
});

AJAX.registerOnload('import.js', function () {
    // import_file_form validation.
    $(document).on('submit', '#import_file_form', function () {
        var radioLocalImport = $('#localFileTab');
        var radioImport = $('#uploadFileTab');
        var fileMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> ' + window.Messages.strImportDialogMessage + '</div>';
        var wrongTblNameMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">' + window.Messages.strTableNameDialogMessage + '</div>';
        var wrongDBNameMsg = '<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">' + window.Messages.strDBNameDialogMessage + '</div>';

        if (radioLocalImport.length !== 0) {
            // remote upload.

            if (radioImport.hasClass('active') && $('#input_import_file').val() === '') {
                $('#input_import_file').trigger('focus');
                ajaxShowMessage(fileMsg, false);

                return false;
            }

            if (radioLocalImport.hasClass('active')) {
                if ($('#select_local_import_file').length === 0) {
                    ajaxShowMessage('<div class="alert alert-danger" role="alert"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> ' + window.Messages.strNoImportFile + ' </div>', false);

                    return false;
                }

                if ($('#select_local_import_file').val() === '') {
                    $('#select_local_import_file').trigger('focus');
                    ajaxShowMessage(fileMsg, false);

                    return false;
                }
            }
        } else {
            // local upload.
            if ($('#input_import_file').val() === '') {
                $('#input_import_file').trigger('focus');
                ajaxShowMessage(fileMsg, false);

                return false;
            }

            if ($('#text_csv_new_tbl_name').length > 0) {
                var newTblName = ($('#text_csv_new_tbl_name').val() as string);
                if (newTblName.length > 0 && newTblName.trim().length === 0) {
                    ajaxShowMessage(wrongTblNameMsg, false);

                    return false;
                }
            }

            if ($('#text_csv_new_db_name').length > 0) {
                var newDBName = ($('#text_csv_new_db_name').val() as string);
                if (newDBName.length > 0 && newDBName.trim().length === 0) {
                    ajaxShowMessage(wrongDBNameMsg, false);

                    return false;
                }
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

    /**
     * Set up the interface for Javascript-enabled browsers since the default is for
     *  Javascript-disabled browsers
     */
    $('#format_specific_opts').find('div.format_specific_options')
        .find('h3')
        .remove();
    // $("form[name=import] *").unwrap();

    /**
     * for input element text_csv_enclosed and text_csv_escaped allow just one character to enter.
     * as mysql allows just one character for these fields,
     * if first character is escape then allow two including escape character.
     */
    $('#text_csv_enclosed').add('#text_csv_escaped').on('keyup', function () {
        if (($(this).val() as string).length === 2 && ($(this).val() as string).charAt(0) !== '\\') {
            $(this).val(($(this).val() as string).substring(0, 1));

            return false;
        }

        return true;
    });

    $('#importmain #buttonGo').on('click', function () {
        const uploadProgressInfo = document.getElementById('upload_progress_info');
        const uploadId = uploadProgressInfo.dataset.uploadId;
        const handler = uploadProgressInfo.dataset.handler;

        $('#upload_form_form').css('display', 'none');

        const clockImage = '<img src="' + window.themeImagePath + 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock">';

        if (handler !== 'PhpMyAdmin\\Plugins\\Import\\Upload\\UploadNoplugin') {
            var finished = false;
            var percent = 0.0;
            var total = 0;
            var complete = 0;
            var originalTitle = parent && parent.document ? parent.document.title : false;
            var importStart;

            var performUpload = function () {
                $.getJSON(
                    'index.php?route=/import-status',
                    { 'id': uploadId, 'import_status': 1, 'server': CommonParams.get('server') },
                    function (response) {
                        finished = response.finished;
                        percent = response.percent;
                        total = response.total;
                        complete = response.complete;

                        if (total === 0 && complete === 0 && percent === 0) {
                            $('#upload_form_status_info').html(clockImage + ' ' + window.Messages.uploadProgressMaximumAllowedSize);
                            $('#upload_form_status').css('display', 'none');
                        } else {
                            var nowDate = new Date();
                            const now = Date.UTC(
                                nowDate.getFullYear(),
                                nowDate.getMonth(),
                                nowDate.getDate(),
                                nowDate.getHours(),
                                nowDate.getMinutes(),
                                nowDate.getSeconds()
                            ) + nowDate.getMilliseconds() - 1000;

                            var statusText = window.sprintf(
                                window.Messages.uploadProgressStatusText,
                                Functions.formatBytes(
                                    complete, 1, window.Messages.strDecimalSeparator
                                ),
                                Functions.formatBytes(
                                    total, 1, window.Messages.strDecimalSeparator
                                )
                            );

                            if ($('#importmain').is(':visible')) {
                                // Show progress UI
                                $('#importmain').hide();
                                const uploadProgressDiv = '<div class="upload_progress">'
                                    + '<div class="upload_progress_bar_outer">'
                                    + '<div class="percentage"></div>'
                                    + '<div id="status" class="upload_progress_bar_inner">'
                                    + '<div class="percentage"></div></div></div>'
                                    + '<div>' + clockImage + ' ' + window.Messages.uploadProgressUploading + '</div>'
                                    + '<div id="statustext"></div></div>';
                                $('#import_form_status').html(uploadProgressDiv).show();
                                importStart = now;
                            } else if (percent > 9 || complete > 2000000) {
                                // Calculate estimated time
                                var usedTime = now - importStart;
                                var seconds = parseInt((((total - complete) / complete) * usedTime / 1000).toString());
                                var speed = window.sprintf(
                                    window.Messages.uploadProgressPerSecond,
                                    Functions.formatBytes(complete / usedTime * 1000, 1, window.Messages.strDecimalSeparator)
                                );

                                var minutes = parseInt((seconds / 60).toString());
                                seconds %= 60;
                                var estimatedTime;
                                if (minutes > 0) {
                                    estimatedTime = window.Messages.uploadProgressRemainingMin
                                        .replace('%MIN', minutes.toString())
                                        .replace('%SEC', seconds.toString());
                                } else {
                                    estimatedTime = window.Messages.uploadProgressRemainingSec
                                        .replace('%SEC', seconds.toString());
                                }

                                statusText += '<br>' + speed + '<br><br>' + estimatedTime;
                            }

                            var percentString = Math.round(percent) + '%';
                            $('#status').animate({ width: percentString }, 150);
                            $('.percentage').text(percentString);

                            // Show percent in window title
                            if (originalTitle !== false) {
                                parent.document.title
                                    = percentString + ' - ' + originalTitle;
                            } else {
                                document.title
                                    = percentString + ' - ' + originalTitle;
                            }

                            $('#statustext').html(statusText);
                        }

                        if (finished) {
                            if (originalTitle !== false) {
                                // @ts-ignore
                                parent.document.title = originalTitle;
                            } else {
                                // @ts-ignore
                                document.title = originalTitle;
                            }

                            $('#importmain').hide();
                            // Loads the message, either success or mysql error
                            $('#import_form_status')
                                .html(clockImage + ' ' + window.Messages.uploadProgressBeingProcessed)
                                .show();

                            $('#import_form_status').load(
                                'index.php?route=/import-status&message=1&import_status=1&server=' + CommonParams.get('server')
                            );

                            Navigation.reload();
                        } else {
                            setTimeout(performUpload, 1000);
                        }
                    });
            };

            setTimeout(performUpload, 1000);
        } else {
            // No plugin available
            $('#upload_form_status_info').html(clockImage + ' ' + window.Messages.uploadProgressNoDetails);
            $('#upload_form_status').css('display', 'none');
        }
    });
});
