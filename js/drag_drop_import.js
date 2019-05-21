/* vim: set expandtab sw=4 ts=4 sts=4: */

/* This script handles PMA Drag Drop Import, loaded only when configuration is enabled.*/

/**
 * Class to handle PMA Drag and Drop Import
 *      feature
 */
PMA_DROP_IMPORT = {
    /**
     * @var int, count of total uploads in this view
     */
    uploadCount: 0,
    /**
     * @var int, count of live uploads
     */
    liveUploadCount: 0,
    /**
     * @var  string array, allowed extensions
     */
    allowedExtensions: ['sql', 'xml', 'ldi', 'mediawiki', 'shp'],
    /**
     * @var  string array, allowed extensions for compressed files
     */
    allowedCompressedExtensions: ['gz', 'bz2', 'zip'],
    /**
     * @var obj array to store message returned by import_status.php
     */
    importStatus: [],
    /**
     * Checks if any dropped file has valid extension or not
     *
     * @param file filename
     *
     * @return string, extension for valid extension, '' otherwise
     */
    _getExtension: function (file) {
        var arr = file.split('.');
        ext = arr[arr.length - 1];

        // check if compressed
        if (jQuery.inArray(ext.toLowerCase(),
            PMA_DROP_IMPORT.allowedCompressedExtensions) !== -1) {
            ext = arr[arr.length - 2];
        }

        // Now check for extension
        if (jQuery.inArray(ext.toLowerCase(),
            PMA_DROP_IMPORT.allowedExtensions) !== -1) {
            return ext;
        }
        return '';
    },
    /**
     * Shows upload progress for different sql uploads
     *
     * @param: hash (string), hash for specific file upload
     * @param: percent (float), file upload percentage
     *
     * @return void
     */
    _setProgress: function (hash, percent) {
        $('.pma_sql_import_status div li[data-hash="' + hash + '"]')
            .children('progress').val(percent);
    },
    /**
     * Function to upload the file asynchronously
     *
     * @param formData FormData object for a specific file
     * @param hash hash of the current file upload
     *
     * @return void
     */
    _sendFileToServer: function (formData, hash) {
        var uploadURL = './import.php'; // Upload URL
        var extraData = {};
        var jqXHR = $.ajax({
            xhr: function () {
                var xhrobj = $.ajaxSettings.xhr();
                if (xhrobj.upload) {
                    xhrobj.upload.addEventListener('progress', function (event) {
                        var percent = 0;
                        var position = event.loaded || event.position;
                        var total = event.total;
                        if (event.lengthComputable) {
                            percent = Math.ceil(position / total * 100);
                        }
                        // Set progress
                        PMA_DROP_IMPORT._setProgress(hash, percent);
                    }, false);
                }
                return xhrobj;
            },
            url: uploadURL,
            type: 'POST',
            contentType:false,
            processData: false,
            cache: false,
            data: formData,
            success: function (data) {
                PMA_DROP_IMPORT._importFinished(hash, false, data.success);
                if (!data.success) {
                    PMA_DROP_IMPORT.importStatus[PMA_DROP_IMPORT.importStatus.length] = {
                        hash: hash,
                        message: data.error
                    };
                }
            }
        });

        // -- provide link to cancel the upload
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize').html('<span hash="' +
            hash + '" class="pma_drop_file_status" task="cancel">' +
            Messages.dropImportMessageCancel + '</span>');

        // -- add event listener to this link to abort upload operation
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize span.pma_drop_file_status')
            .on('click', function () {
                if ($(this).attr('task') === 'cancel') {
                    jqXHR.abort();
                    $(this).html('<span>' + Messages.dropImportMessageAborted + '</span>');
                    PMA_DROP_IMPORT._importFinished(hash, true, false);
                } else if ($(this).children('span').html() ===
                    Messages.dropImportMessageFailed) {
                    // -- view information
                    var $this = $(this);
                    $.each(PMA_DROP_IMPORT.importStatus,
                        function (key, value) {
                            if (value.hash === hash) {
                                $('.pma_drop_result:visible').remove();
                                var filename = $this.parent('span').attr('data-filename');
                                $('body').append('<div class="pma_drop_result"><h2>' +
                                Messages.dropImportImportResultHeader + ' - ' +
                                filename + '<span class="close">x</span></h2>' + value.message + '</div>');
                                $('.pma_drop_result').draggable();  // to make this dialog draggable
                            }
                        });
                }
            });
    },
    /**
     * Triggered when an object is dragged into the PMA UI
     *
     * @param event obj
     *
     * @return void
     */
    _dragenter : function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($('.noDragDrop').length !== 0) {
            return;
        }

        event.stopPropagation();
        event.preventDefault();
        if (!PMA_DROP_IMPORT._hasFiles(event)) {
            return;
        }
        if (CommonParams.get('db') === '') {
            $('.pma_drop_handler').html(Messages.dropImportSelectDB);
        } else {
            $('.pma_drop_handler').html(Messages.dropImportDropFiles);
        }
        $('.pma_drop_handler').fadeIn();
    },
    /**
     * Check if dragged element contains Files
     *
     * @param event the event object
     *
     * @return bool
     */
    _hasFiles: function (event) {
        return !(typeof event.originalEvent.dataTransfer.types === 'undefined' ||
            $.inArray('Files', event.originalEvent.dataTransfer.types) < 0 ||
            $.inArray(
                'application/x-moz-nativeimage',
                event.originalEvent.dataTransfer.types
            ) >= 0);
    },
    /**
     * Triggered when dragged file is being dragged over PMA UI
     *
     * @param event obj
     *
     * @return void
     */
    _dragover: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($('.noDragDrop').length !== 0) {
            return;
        }

        event.stopPropagation();
        event.preventDefault();
        if (!PMA_DROP_IMPORT._hasFiles(event)) {
            return;
        }
        $('.pma_drop_handler').fadeIn();
    },
    /**
     * Triggered when dragged objects are left
     *
     * @param event obj
     *
     * @return void
     */
    _dragleave: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($('.noDragDrop').length !== 0) {
            return;
        }
        event.stopPropagation();
        event.preventDefault();
        var $pma_drop_handler = $('.pma_drop_handler');
        $pma_drop_handler.clearQueue().stop();
        $pma_drop_handler.fadeOut();
        $pma_drop_handler.html(Messages.dropImportDropFiles);
    },
    /**
     * Called when upload has finished
     *
     * @param string, unique hash for a certain upload
     * @param bool, true if upload was aborted
     * @param bool, status of sql upload, as sent by server
     *
     * @return void
     */
    _importFinished: function (hash, aborted, status) {
        $('.pma_sql_import_status div li[data-hash="' + hash + '"]')
            .children('progress').hide();
        var icon = 'icon ic_s_success';
        // -- provide link to view upload status
        if (!aborted) {
            if (status) {
                $('.pma_sql_import_status div li[data-hash="' + hash +
                   '"] span.filesize span.pma_drop_file_status')
                    .html('<span>' + Messages.dropImportMessageSuccess + '</a>');
            } else {
                $('.pma_sql_import_status div li[data-hash="' + hash +
                   '"] span.filesize span.pma_drop_file_status')
                    .html('<span class="underline">' + Messages.dropImportMessageFailed +
                   '</a>');
                icon = 'icon ic_s_error';
            }
        } else {
            icon = 'icon ic_s_notice';
        }
        $('.pma_sql_import_status div li[data-hash="' + hash +
            '"] span.filesize span.pma_drop_file_status')
            .attr('task', 'info');

        // Set icon
        $('.pma_sql_import_status div li[data-hash="' + hash + '"]')
            .prepend('<img src="./themes/dot.gif" title="finished" class="' +
            icon + '"> ');

        // Decrease liveUploadCount by one
        $('.pma_import_count').html(--PMA_DROP_IMPORT.liveUploadCount);
        if (!PMA_DROP_IMPORT.liveUploadCount) {
            $('.pma_sql_import_status h2 .close').fadeIn();
        }
    },
    /**
     * Triggered when dragged objects are dropped to UI
     * From this function, the AJAX Upload operation is initiated
     *
     * @param event object
     *
     * @return void
     */
    _drop: function (event) {
        // We don't want to prevent users from using
        // browser's default drag-drop feature on some page(s)
        if ($('.noDragDrop').length !== 0) {
            return;
        }

        var dbname = CommonParams.get('db');
        var server = CommonParams.get('server');

        // if no database is selected -- no
        if (dbname !== '') {
            var files = event.originalEvent.dataTransfer.files;
            if (!files || files.length === 0) {
                // No files actually transferred
                $('.pma_drop_handler').fadeOut();
                event.stopPropagation();
                event.preventDefault();
                return;
            }
            $('.pma_sql_import_status').slideDown();
            for (var i = 0; i < files.length; i++) {
                var ext  = (PMA_DROP_IMPORT._getExtension(files[i].name));
                var hash = AJAX.hash(++PMA_DROP_IMPORT.uploadCount);

                var $pma_sql_import_status_div = $('.pma_sql_import_status div');
                $pma_sql_import_status_div.append('<li data-hash="' + hash + '">' +
                    ((ext !== '') ? '' : '<img src="./themes/dot.gif" title="invalid format" class="icon ic_s_notice"> ') +
                    escapeHtml(files[i].name) + '<span class="filesize" data-filename="' +
                    escapeHtml(files[i].name) + '">' + (files[i].size / 1024).toFixed(2) +
                    ' kb</span></li>');

                // scroll the UI to bottom
                $pma_sql_import_status_div.scrollTop(
                    $pma_sql_import_status_div.scrollTop() + 50
                );  // 50 hardcoded for now

                if (ext !== '') {
                    // Increment liveUploadCount by one
                    $('.pma_import_count').html(++PMA_DROP_IMPORT.liveUploadCount);
                    $('.pma_sql_import_status h2 .close').fadeOut();

                    $('.pma_sql_import_status div li[data-hash="' + hash + '"]')
                        .append('<br><progress max="100" value="2"></progress>');

                    // uploading
                    var fd = new FormData();
                    fd.append('import_file', files[i]);
                    fd.append('noplugin', Math.random().toString(36).substring(2, 12));
                    fd.append('db', dbname);
                    fd.append('server', server);
                    fd.append('token', CommonParams.get('token'));
                    fd.append('import_type', 'database');
                    // todo: method to find the value below
                    fd.append('MAX_FILE_SIZE', '4194304');
                    // todo: method to find the value below
                    fd.append('charset_of_file','utf-8');
                    // todo: method to find the value below
                    fd.append('allow_interrupt', 'yes');
                    fd.append('skip_queries', '0');
                    fd.append('format',ext);
                    fd.append('sql_compatibility','NONE');
                    fd.append('sql_no_auto_value_on_zero','something');
                    fd.append('ajax_request','true');
                    fd.append('hash', hash);

                    // init uploading
                    PMA_DROP_IMPORT._sendFileToServer(fd, hash);
                } else if (!PMA_DROP_IMPORT.liveUploadCount) {
                    $('.pma_sql_import_status h2 .close').fadeIn();
                }
            }
        }
        $('.pma_drop_handler').fadeOut();
        event.stopPropagation();
        event.preventDefault();
    }
};

/**
 * Called when some user drags, dragover, leave
 *       a file to the PMA UI
 * @param object Event data
 * @return void
 */
$(document).on('dragenter', PMA_DROP_IMPORT._dragenter);
$(document).on('dragover', PMA_DROP_IMPORT._dragover);
$(document).on('dragleave', '.pma_drop_handler', PMA_DROP_IMPORT._dragleave);

// when file is dropped to PMA UI
$(document).on('drop', 'body', PMA_DROP_IMPORT._drop);

// minimizing-maximising the sql ajax upload status
$(document).on('click', '.pma_sql_import_status h2 .minimize', function () {
    if ($(this).attr('toggle') === 'off') {
        $('.pma_sql_import_status div').css('height','270px');
        $(this).attr('toggle','on');
        $(this).html('-');  // to minimize
    } else {
        $('.pma_sql_import_status div').css('height','0px');
        $(this).attr('toggle','off');
        $(this).html('+');  // to maximise
    }
});

// closing sql ajax upload status
$(document).on('click', '.pma_sql_import_status h2 .close', function () {
    $('.pma_sql_import_status').fadeOut(function () {
        $('.pma_sql_import_status div').html('');
        PMA_DROP_IMPORT.importStatus = [];  // clear the message array
    });
});

// Closing the import result box
$(document).on('click', '.pma_drop_result h2 .close', function () {
    $(this).parent('h2').parent('div').remove();
});
