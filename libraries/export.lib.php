<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function for the main export logic 
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Sets a session variable upon a possible fatal error during export 
 *
 * @return void 
 */
function PMA_shutdown()
{
    $a = error_get_last();
    if ($a != null && strpos($a['message'], "execution time")) {
        //write in partially downloaded file for future reference of user
        print_r($a);
        //set session variable to check if there was error while exporting
        $_SESSION['pma_export_error'] = $a['message'];
    }
}

/**
 * Detect ob_gzhandler
 *
 * @return bool
 */
function PMA_isGzHandlerEnabled()
{
    return in_array('ob_gzhandler', ob_list_handlers());
}

/**
 * Detect whether gzencode is needed; it might not be needed if
 * the server is already compressing by itself
 *
 * @return bool Whether gzencode is needed
 */
function PMA_gzencodeNeeded()
{
    if (@function_exists('gzencode')
        && ! @ini_get('zlib.output_compression')
        && ! PMA_isGzHandlerEnabled()
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Output handler for all exports, if needed buffering, it stores data into
 * $dump_buffer, otherwise it prints thems out.
 *
 * @param string $line the insert statement
 *
 * @return bool Whether output succeeded
 */
function PMA_exportOutputHandler($line)
{
    global $time_start, $dump_buffer, $dump_buffer_len, $save_filename;

    // Kanji encoding convert feature
    if ($GLOBALS['output_kanji_conversion']) {
        $line = PMA_Kanji_strConv(
            $line,
            $GLOBALS['knjenc'],
            isset($GLOBALS['xkana']) ? $GLOBALS['xkana'] : ''
        );
    }
    // If we have to buffer data, we will perform everything at once at the end
    if ($GLOBALS['buffer_needed']) {

        $dump_buffer .= $line;
        if ($GLOBALS['onfly_compression']) {

            $dump_buffer_len += strlen($line);

            if ($dump_buffer_len > $GLOBALS['memory_limit']) {
                if ($GLOBALS['output_charset_conversion']) {
                    $dump_buffer = PMA_convertString(
                        'utf-8',
                        $GLOBALS['charset_of_file'],
                        $dump_buffer
                    );
                }
                // as bzipped
                if ($GLOBALS['compression'] == 'bzip2'
                    && @function_exists('bzcompress')
                ) {
                    $dump_buffer = bzcompress($dump_buffer);
                } elseif ($GLOBALS['compression'] == 'gzip'
                    && PMA_gzencodeNeeded()
                ) {
                    // as a gzipped file
                    // without the optional parameter level because it bugs
                    $dump_buffer = gzencode($dump_buffer);
                }
                if ($GLOBALS['save_on_server']) {
                    $write_result = @fwrite($GLOBALS['file_handle'], $dump_buffer);
                    if ($write_result != strlen($dump_buffer)) {
                        $GLOBALS['message'] = PMA_Message::error(
                            __('Insufficient space to save the file %s.')
                        );
                        $GLOBALS['message']->addParam($save_filename);
                        return false;
                    }
                } else {
                    echo $dump_buffer;
                }
                $dump_buffer = '';
                $dump_buffer_len = 0;
            }
        } else {
            $time_now = time();
            if ($time_start >= $time_now + 30) {
                $time_start = $time_now;
                header('X-pmaPing: Pong');
            } // end if
        }
    } else {
        if ($GLOBALS['asfile']) {
            if ($GLOBALS['output_charset_conversion']) {
                $line = PMA_convertString(
                    'utf-8',
                    $GLOBALS['charset_of_file'],
                    $line
                );
            }
            if ($GLOBALS['save_on_server'] && strlen($line) > 0) {
                $write_result = @fwrite($GLOBALS['file_handle'], $line);
                if (! $write_result || ($write_result != strlen($line))) {
                    $GLOBALS['message'] = PMA_Message::error(
                        __('Insufficient space to save the file %s.')
                    );
                    $GLOBALS['message']->addParam($save_filename);
                    return false;
                }
                $time_now = time();
                if ($time_start >= $time_now + 30) {
                    $time_start = $time_now;
                    header('X-pmaPing: Pong');
                } // end if
            } else {
                // We export as file - output normally
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars($line);
        }
    }
    return true;
} // end of the 'PMA_exportOutputHandler()' function

/**
 * Returns HTML containing the footer for a displayed export
 *
 * @param string $back_button the link for going Back
 *
 * @return string $html the HTML output
 */
function PMA_getHtmlForDisplayedExportFooter($back_button)
{
    /**
     * Close the html tags and add the footers for on-screen export 
     */
    $html = '</textarea>'
        . '    </form>'
        // bottom back button
        . $back_button
        . '</div>'
        . '<script type="text/javascript">' . "\n"
        . '//<![CDATA[' . "\n"
        . 'var $body = $("body");' . "\n"
        . '$("#textSQLDUMP")' . "\n"
        . '.width($body.width() - 50)' . "\n"
        . '.height($body.height() - 100);' . "\n"
        . '//]]>' . "\n"
        . '</script>' . "\n";
    return $html;
}

/**
 * Computes the memory limit for export 
 *
 * @return int $memory_limit the memory limit 
 */
function PMA_getMemoryLimitForExport()
{
    $memory_limit = trim(@ini_get('memory_limit'));
    $memory_limit_num = (int)substr($memory_limit, 0, -1);
    // 2 MB as default
    if (empty($memory_limit) || '-1' == $memory_limit) {
        $memory_limit = 2 * 1024 * 1024;
    } elseif (strtolower(substr($memory_limit, -1)) == 'm') {
        $memory_limit = $memory_limit_num * 1024 * 1024;
    } elseif (strtolower(substr($memory_limit, -1)) == 'k') {
        $memory_limit = $memory_limit_num * 1024;
    } elseif (strtolower(substr($memory_limit, -1)) == 'g') {
        $memory_limit = $memory_limit_num * 1024 * 1024 * 1024;
    } else {
        $memory_limit = (int)$memory_limit;
    }

    // Some of memory is needed for other things and as threshold.
    // During export I had allocated (see memory_get_usage function)
    // approx 1.2MB so this comes from that.
    if ($memory_limit > 1500000) {
        $memory_limit -= 1500000;
    }

    // Some memory is needed for compression, assume 1/3
    $memory_limit /= 8;
    return $memory_limit;
}

/**
 * Return the filename and MIME type for export file 
 *
 * @param string $export_type       type of export
 * @param string $remember_template whether to remember template
 * @param object $export_plugin     the export plugin
 * @param string $compression       compression asked
 * @param string $filename_template the filename template
 *
 * @return array the filename template and mime type 
 */
function PMA_getExportFilenameAndMimetype(
    $export_type, $remember_template, $export_plugin, $compression,
    $filename_template
) {
    if ($export_type == 'server') {
        if (! empty($remember_template)) {
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_server_filename_template',
                'Export/file_template_server',
                $filename_template
            );
        }
    } elseif ($export_type == 'database') {
        if (! empty($remember_template)) {
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_db_filename_template',
                'Export/file_template_database',
                $filename_template
            );
        }
    } else {
        if (! empty($remember_template)) {
            $GLOBALS['PMA_Config']->setUserValue(
                'pma_table_filename_template',
                'Export/file_template_table',
                $filename_template
            );
        }
    }
    $filename = PMA_Util::expandUserString($filename_template);
    // remove dots in filename (coming from either the template or already
    // part of the filename) to avoid a remote code execution vulnerability
    $filename = PMA_sanitizeFilename($filename, $replaceDots = true);

    // Grab basic dump extension and mime type
    // Check if the user already added extension;
    // get the substring where the extension would be if it was included
    $extension_start_pos = strlen($filename) - strlen(
        $export_plugin->getProperties()->getExtension()
    ) - 1;
    $user_extension = substr($filename, $extension_start_pos, strlen($filename));
    $required_extension = "." . $export_plugin->getProperties()->getExtension();
    if (strtolower($user_extension) != $required_extension) {
        $filename  .= $required_extension;
    }
    $mime_type  = $export_plugin->getProperties()->getMimeType();

    // If dump is going to be compressed, set correct mime_type and add
    // compression to extension
    if ($compression == 'bzip2') {
        $filename  .= '.bz2';
        $mime_type = 'application/x-bzip2';
    } elseif ($compression == 'gzip') {
        $filename  .= '.gz';
        $mime_type = 'application/x-gzip';
    } elseif ($compression == 'zip') {
        $filename  .= '.zip';
        $mime_type = 'application/zip';
    }
    return array($filename, $mime_type);
}

/**
 * Open the export file
 *
 * @param string  $filename     the export filename
 * @param boolean $quick_export whether it's a quick export or not 
 *
 * @return array the full save filename, possible message and the file handle  
 */
function PMA_openExportFile($filename, $quick_export)
{
    $file_handle = null;
    $message = '';

    $save_filename = PMA_Util::userDir($GLOBALS['cfg']['SaveDir'])
        . preg_replace('@[/\\\\]@', '_', $filename);

    if (file_exists($save_filename)
        && ((! $quick_export && empty($_REQUEST['onserverover']))
        || ($quick_export
        && $_REQUEST['quick_export_onserverover'] != 'saveitover'))
    ) {
        $message = PMA_Message::error(
            __(
                'File %s already exists on server, '
                . 'change filename or check overwrite option.'
            )
        );
        $message->addParam($save_filename);
    } elseif (is_file($save_filename) && ! is_writable($save_filename)) {
        $message = PMA_Message::error(
            __(
                'The web server does not have permission '
                . 'to save the file %s.'
            )
        );
        $message->addParam($save_filename);
    } elseif (! $file_handle = @fopen($save_filename, 'w')) {
        $message = PMA_Message::error(
            __(
                'The web server does not have permission '
                . 'to save the file %s.'
            )
        );
        $message->addParam($save_filename);
    }
    return array($save_filename, $message, $file_handle);
}

/**
 * Close the export file
 *
 * @param resource $file_handle   the export file handle
 * @param string   $dump_buffer   the current dump buffer
 * @param string   $save_filename the export filename
 *
 * @return object $message a message object (or empty string)
 */
function PMA_closeExportFile($file_handle, $dump_buffer, $save_filename)
{
    $message = '';
    $write_result = @fwrite($file_handle, $dump_buffer);
    fclose($file_handle);
    if (strlen($dump_buffer) > 0
        && (! $write_result || ($write_result != strlen($dump_buffer)))
    ) {
        $message = new PMA_Message(
            __('Insufficient space to save the file %s.'),
            PMA_Message::ERROR,
            $save_filename
        );
    } else {
        $message = new PMA_Message(
            __('Dump has been saved to file %s.'),
            PMA_Message::SUCCESS,
            $save_filename
        );
    }
    return $message;
}

/**
 * Compress the export buffer 
 *
 * @param string $dump_buffer the current dump buffer
 * @param string $compression the compression mode
 *
 * @return object $message a message object (or empty string)
 */
function PMA_compressExport($dump_buffer, $compression)
{
    if ($compression == 'zip' && @function_exists('gzcompress')) {
        $zipfile = new ZipFile();
        $zipfile->addFile($dump_buffer, substr($filename, 0, -4));
        $dump_buffer = $zipfile->file();
    } elseif ($compression == 'bzip2' && @function_exists('bzcompress')) {
        $dump_buffer = bzcompress($dump_buffer);
    } elseif ($compression == 'gzip' && PMA_gzencodeNeeded()) {
        // without the optional parameter level because it bugs
        $dump_buffer = gzencode($dump_buffer);
    }
    return $dump_buffer;
}
?>
