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
?>
