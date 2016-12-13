<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function for the main export logic
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Encoding;
use PMA\libraries\Message;
use PMA\libraries\plugins\ExportPlugin;
use PMA\libraries\Table;
use PMA\libraries\ZipFile;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;


/**
 * Sets a session variable upon a possible fatal error during export
 *
 * @return void
 */
function PMA_shutdownDuringExport()
{
    $error = error_get_last();
    if ($error != null && mb_strpos($error['message'], "execution time")) {
        //set session variable to check if there was error while exporting
        $_SESSION['pma_export_error'] = $error['message'];
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
    /*
     * We should gzencode only if the function exists
     * but we don't want to compress twice, therefore
     * gzencode only if transparent compression is not enabled
     * and gz compression was not asked via $cfg['OBGzip']
     * but transparent compression does not apply when saving to server
     */
    $chromeAndGreaterThan43 = PMA_USR_BROWSER_AGENT == 'CHROME'
        && PMA_USR_BROWSER_VER >= 43; // see bug #4942

    if (@function_exists('gzencode')
        && ((! @ini_get('zlib.output_compression')
        && ! PMA_isGzHandlerEnabled())
        || $GLOBALS['save_on_server']
        || $chromeAndGreaterThan43)
    ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Output handler for all exports, if needed buffering, it stores data into
 * $dump_buffer, otherwise it prints them out.
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
        $line = Encoding::kanjiStrConv(
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
                    $dump_buffer = Encoding::convertString(
                        'utf-8',
                        $GLOBALS['charset'],
                        $dump_buffer
                    );
                }
                if ($GLOBALS['compression'] == 'gzip'
                    && PMA_gzencodeNeeded()
                ) {
                    // as a gzipped file
                    // without the optional parameter level because it bugs
                    $dump_buffer = gzencode($dump_buffer);
                }
                if ($GLOBALS['save_on_server']) {
                    $write_result = @fwrite($GLOBALS['file_handle'], $dump_buffer);
                    // Here, use strlen rather than mb_strlen to get the length
                    // in bytes to compare against the number of bytes written.
                    if ($write_result != strlen($dump_buffer)) {
                        $GLOBALS['message'] = Message::error(
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
                $line = Encoding::convertString(
                    'utf-8',
                    $GLOBALS['charset'],
                    $line
                );
            }
            if ($GLOBALS['save_on_server'] && mb_strlen($line) > 0) {
                $write_result = @fwrite($GLOBALS['file_handle'], $line);
                // Here, use strlen rather than mb_strlen to get the length
                // in bytes to compare against the number of bytes written.
                if (! $write_result
                    || $write_result != strlen($line)
                ) {
                    $GLOBALS['message'] = Message::error(
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
    $lowerLastChar = strtolower(substr($memory_limit, -1));
    // 2 MB as default
    if (empty($memory_limit) || '-1' == $memory_limit) {
        $memory_limit = 2 * 1024 * 1024;
    } elseif ($lowerLastChar == 'm') {
        $memory_limit = $memory_limit_num * 1024 * 1024;
    } elseif ($lowerLastChar == 'k') {
        $memory_limit = $memory_limit_num * 1024;
    } elseif ($lowerLastChar == 'g') {
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
 * @param string       $export_type       type of export
 * @param string       $remember_template whether to remember template
 * @param ExportPlugin $export_plugin     the export plugin
 * @param string       $compression       compression asked
 * @param string       $filename_template the filename template
 *
 * @return string[] the filename template and mime type
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
    $filename = PMA\libraries\Util::expandUserString($filename_template);
    // remove dots in filename (coming from either the template or already
    // part of the filename) to avoid a remote code execution vulnerability
    $filename = Sanitize::sanitizeFilename($filename, $replaceDots = true);

    // Grab basic dump extension and mime type
    // Check if the user already added extension;
    // get the substring where the extension would be if it was included
    $extension_start_pos = mb_strlen($filename) - mb_strlen(
        $export_plugin->getProperties()->getExtension()
    ) - 1;
    $user_extension = mb_substr(
        $filename, $extension_start_pos, mb_strlen($filename)
    );
    $required_extension = "." . $export_plugin->getProperties()->getExtension();
    if (mb_strtolower($user_extension) != $required_extension) {
        $filename  .= $required_extension;
    }
    $mime_type  = $export_plugin->getProperties()->getMimeType();

    // If dump is going to be compressed, set correct mime_type and add
    // compression to extension
    if ($compression == 'gzip') {
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

    $save_filename = PMA\libraries\Util::userDir($GLOBALS['cfg']['SaveDir'])
        . preg_replace('@[/\\\\]@', '_', $filename);

    if (file_exists($save_filename)
        && ((! $quick_export && empty($_REQUEST['onserver_overwrite']))
        || ($quick_export
        && $_REQUEST['quick_export_onserver_overwrite'] != 'saveitover'))
    ) {
        $message = Message::error(
            __(
                'File %s already exists on server, '
                . 'change filename or check overwrite option.'
            )
        );
        $message->addParam($save_filename);
    } elseif (@is_file($save_filename) && ! @is_writable($save_filename)) {
        $message = Message::error(
            __(
                'The web server does not have permission '
                . 'to save the file %s.'
            )
        );
        $message->addParam($save_filename);
    } elseif (! $file_handle = @fopen($save_filename, 'w')) {
        $message = Message::error(
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
 * @return Message $message a message object (or empty string)
 */
function PMA_closeExportFile($file_handle, $dump_buffer, $save_filename)
{
    $write_result = @fwrite($file_handle, $dump_buffer);
    fclose($file_handle);
    // Here, use strlen rather than mb_strlen to get the length
    // in bytes to compare against the number of bytes written.
    if (strlen($dump_buffer) > 0
        && (! $write_result || $write_result != strlen($dump_buffer))
    ) {
        $message = new Message(
            __('Insufficient space to save the file %s.'),
            Message::ERROR,
            array($save_filename)
        );
    } else {
        $message = new Message(
            __('Dump has been saved to file %s.'),
            Message::SUCCESS,
            array($save_filename)
        );
    }
    return $message;
}

/**
 * Compress the export buffer
 *
 * @param array|string $dump_buffer the current dump buffer
 * @param string       $compression the compression mode
 * @param string       $filename    the filename
 *
 * @return object $message a message object (or empty string)
 */
function PMA_compressExport($dump_buffer, $compression, $filename)
{
    if ($compression == 'zip' && @function_exists('gzcompress')) {
        $filename = substr($filename, 0, -4); // remove extension (.zip)
        $zipfile = new ZipFile();
        if (is_array($dump_buffer)) {
            foreach ($dump_buffer as $table => $dump) {
                $ext_pos = strpos($filename, '.');
                $extension = substr($filename, $ext_pos);
                $zipfile->addFile(
                    $dump,
                    str_replace(
                        $extension,
                        '_' . $table . $extension,
                        $filename
                    )
                );
            }
        } else {
            $zipfile->addFile($dump_buffer, $filename);
        }
        $dump_buffer = $zipfile->file();
    } elseif ($compression == 'gzip' && PMA_gzencodeNeeded()) {
        // without the optional parameter level because it bugs
        $dump_buffer = gzencode($dump_buffer);
    }
    return $dump_buffer;
}

/**
 * Saves the dump_buffer for a particular table in an array
 * Used in separate files export
 *
 * @param string  $object_name the name of current object to be stored
 * @param boolean $append      optional boolean to append to an existing index or not
 *
 * @return void
 */
function PMA_saveObjectInBuffer($object_name, $append = false)
{

    global $dump_buffer_objects, $dump_buffer, $dump_buffer_len;

    if (! empty($dump_buffer)) {
        if ($append && isset($dump_buffer_objects[$object_name])) {
            $dump_buffer_objects[$object_name] .= $dump_buffer;
        } else {
            $dump_buffer_objects[$object_name] = $dump_buffer;
        }
    }

    // Re - initialize
    $dump_buffer = '';
    $dump_buffer_len = 0;

}

/**
 * Returns HTML containing the header for a displayed export
 *
 * @param string $export_type the export type
 * @param string $db          the database name
 * @param string $table       the table name
 *
 * @return string[] the generated HTML and back button
 */
function PMA_getHtmlForDisplayedExportHeader($export_type, $db, $table)
{
    $html = '<div style="text-align: ' . $GLOBALS['cell_align_left'] . '">';

    /**
     * Displays a back button with all the $_REQUEST data in the URL
     * (store in a variable to also display after the textarea)
     */
    $back_button = '<p>[ <a href="';
    if ($export_type == 'server') {
        $back_button .= 'server_export.php' . URL::getCommon();
    } elseif ($export_type == 'database') {
        $back_button .= 'db_export.php' . URL::getCommon(array('db' => $db));
    } else {
        $back_button .= 'tbl_export.php' . URL::getCommon(
            array(
                'db' => $db, 'table' => $table
            )
        );
    }

    // Convert the multiple select elements from an array to a string
    if ($export_type == 'server' && isset($_REQUEST['db_select'])) {
        $_REQUEST['db_select'] = implode(",", $_REQUEST['db_select']);
    } elseif ($export_type == 'database') {
        if (isset($_REQUEST['table_select'])) {
            $_REQUEST['table_select'] = implode(",", $_REQUEST['table_select']);
        }
        if (isset($_REQUEST['table_structure'])) {
            $_REQUEST['table_structure'] = implode(
                ",",
                $_REQUEST['table_structure']
            );
        } else if (empty($_REQUEST['structure_or_data_forced'])) {
            $_REQUEST['table_structure'] = '';
        }
        if (isset($_REQUEST['table_data'])) {
            $_REQUEST['table_data'] = implode(",", $_REQUEST['table_data']);
        } else if (empty($_REQUEST['structure_or_data_forced'])) {
            $_REQUEST['table_data'] = '';
        }
    }

    foreach ($_REQUEST as $name => $value) {
        if (!is_array($value)) {
            $back_button .= '&amp;' . urlencode($name) . '=' . urlencode($value);
        }
    }
    $back_button .= '&amp;repopulate=1">' . __('Back') . '</a> ]</p>';

    $html .= $back_button
        . '<form name="nofunction">'
        . '<textarea name="sqldump" cols="50" rows="30" '
        . 'id="textSQLDUMP" wrap="OFF">';

    return array($html, $back_button);
}

/**
 * Export at the server level
 *
 * @param string       $db_select       the selected databases to export
 * @param string       $whatStrucOrData structure or data or both
 * @param ExportPlugin $export_plugin   the selected export plugin
 * @param string       $crlf            end of line character(s)
 * @param string       $err_url         the URL in case of error
 * @param string       $export_type     the export type
 * @param bool         $do_relation     whether to export relation info
 * @param bool         $do_comments     whether to add comments
 * @param bool         $do_mime         whether to add MIME info
 * @param bool         $do_dates        whether to add dates
 * @param array        $aliases         alias information for db/table/column
 * @param string       $separate_files  whether it is a separate-files export
 *
 * @return void
 */
function PMA_exportServer(
    $db_select, $whatStrucOrData, $export_plugin, $crlf, $err_url,
    $export_type, $do_relation, $do_comments, $do_mime, $do_dates,
    $aliases, $separate_files
) {
    if (! empty($db_select)) {
        $tmp_select = implode($db_select, '|');
        $tmp_select = '|' . $tmp_select . '|';
    }
    // Walk over databases
    foreach ($GLOBALS['dblist']->databases as $current_db) {
        if (isset($tmp_select)
            && mb_strpos(' ' . $tmp_select, '|' . $current_db . '|')
        ) {
            $tables = $GLOBALS['dbi']->getTables($current_db);
            PMA_exportDatabase(
                $current_db, $tables, $whatStrucOrData, $tables, $tables,
                $export_plugin, $crlf, $err_url, $export_type, $do_relation,
                $do_comments, $do_mime, $do_dates, $aliases,
                $separate_files == 'database' ? $separate_files : ''
            );
            if ($separate_files == 'server') {
                PMA_saveObjectInBuffer($current_db);
            }
        }
    } // end foreach database
}

/**
 * Export at the database level
 *
 * @param string       $db              the database to export
 * @param array        $tables          the tables to export
 * @param string       $whatStrucOrData structure or data or both
 * @param array        $table_structure whether to export structure for each table
 * @param array        $table_data      whether to export data for each table
 * @param ExportPlugin $export_plugin   the selected export plugin
 * @param string       $crlf            end of line character(s)
 * @param string       $err_url         the URL in case of error
 * @param string       $export_type     the export type
 * @param bool         $do_relation     whether to export relation info
 * @param bool         $do_comments     whether to add comments
 * @param bool         $do_mime         whether to add MIME info
 * @param bool         $do_dates        whether to add dates
 * @param array        $aliases         Alias information for db/table/column
 * @param string       $separate_files  whether it is a separate-files export
 *
 * @return void
 */
function PMA_exportDatabase(
    $db, $tables, $whatStrucOrData, $table_structure, $table_data,
    $export_plugin, $crlf, $err_url, $export_type, $do_relation,
    $do_comments, $do_mime, $do_dates, $aliases, $separate_files
) {
    $db_alias = !empty($aliases[$db]['alias'])
        ? $aliases[$db]['alias'] : '';

    if (! $export_plugin->exportDBHeader($db, $db_alias)) {
        return;
    }
    if (! $export_plugin->exportDBCreate($db, $export_type, $db_alias)) {
        return;
    }
    if ($separate_files == 'database') {
        PMA_saveObjectInBuffer('database', true);
    }

    if (($GLOBALS['sql_structure_or_data'] == 'structure'
        || $GLOBALS['sql_structure_or_data'] == 'structure_and_data')
        && isset($GLOBALS['sql_procedure_function'])
    ) {
        $export_plugin->exportRoutines($db, $aliases);

        if ($separate_files == 'database') {
            PMA_saveObjectInBuffer('routines');
        }
    }

    $views = array();

    foreach ($tables as $table) {
        $_table = new Table($table, $db);
        // if this is a view, collect it for later;
        // views must be exported after the tables
        $is_view = $_table->isView();
        if ($is_view) {
            $views[] = $table;
        }
        if (($whatStrucOrData == 'structure'
            || $whatStrucOrData == 'structure_and_data')
            && in_array($table, $table_structure)
        ) {
            // for a view, export a stand-in definition of the table
            // to resolve view dependencies (only when it's a single-file export)
            if ($is_view) {
                if ($separate_files == ''
                    && isset($GLOBALS['sql_create_view'])
                    && ! $export_plugin->exportStructure(
                        $db, $table, $crlf, $err_url, 'stand_in',
                        $export_type, $do_relation, $do_comments,
                        $do_mime, $do_dates, $aliases
                    )
                ) {
                    break;
                }
            } else if (isset($GLOBALS['sql_create_table'])) {

                $table_size = $GLOBALS['maxsize'];
                // Checking if the maximum table size constrain has been set
                // And if that constrain is a valid number or not
                if ($table_size !== '' && is_numeric($table_size)) {
                    // This obtains the current table's size
                    $query = 'SELECT data_length + index_length
                          from information_schema.TABLES
                          WHERE table_schema = "' . $GLOBALS['dbi']->escapeString($db) . '"
                          AND table_name = "' . $GLOBALS['dbi']->escapeString($table) . '"';

                    $size = $GLOBALS['dbi']->fetchValue($query);
                    //Converting the size to MB
                    $size = ($size / 1024) / 1024;
                    if ($size > $table_size) {
                        continue;
                    }
                }

                if (! $export_plugin->exportStructure(
                    $db, $table, $crlf, $err_url, 'create_table',
                    $export_type, $do_relation, $do_comments,
                    $do_mime, $do_dates, $aliases
                )) {
                    break;
                }

            }

        }
        // if this is a view or a merge table, don't export data
        if (($whatStrucOrData == 'data' || $whatStrucOrData == 'structure_and_data')
            && in_array($table, $table_data)
            && ! ($is_view)
        ) {
            $tableObj = new PMA\libraries\Table($table, $db);
            $nonGeneratedCols = $tableObj->getNonGeneratedColumns(true);

            $local_query  = 'SELECT ' . implode(', ', $nonGeneratedCols)
                .  ' FROM ' . PMA\libraries\Util::backquote($db)
                . '.' . PMA\libraries\Util::backquote($table);

            if (! $export_plugin->exportData(
                $db, $table, $crlf, $err_url, $local_query, $aliases
            )) {
                break;
            }
        }

        // this buffer was filled, we save it and go to the next one
        if ($separate_files == 'database') {
            PMA_saveObjectInBuffer('table_' . $table);
        }

        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if (isset($GLOBALS['sql_create_trigger']) && ($whatStrucOrData == 'structure'
            || $whatStrucOrData == 'structure_and_data')
            && in_array($table, $table_structure)
        ) {
            if (! $export_plugin->exportStructure(
                $db, $table, $crlf, $err_url, 'triggers',
                $export_type, $do_relation, $do_comments,
                $do_mime, $do_dates, $aliases
            )) {
                break;
            }

            if ($separate_files == 'database') {
                PMA_saveObjectInBuffer('table_' . $table, true);
            }
        }

    }

    if (isset($GLOBALS['sql_create_view'])) {

        foreach ($views as $view) {
            // no data export for a view
            if ($whatStrucOrData == 'structure'
                || $whatStrucOrData == 'structure_and_data'
            ) {
                if (! $export_plugin->exportStructure(
                    $db, $view, $crlf, $err_url, 'create_view',
                    $export_type, $do_relation, $do_comments,
                    $do_mime, $do_dates, $aliases
                )) {
                    break;
                }

                if ($separate_files == 'database') {
                    PMA_saveObjectInBuffer('view_' . $view);
                }
            }
        }

    }

    if (! $export_plugin->exportDBFooter($db)) {
        return;
    }

    // export metadata related to this db
    if (isset($GLOBALS['sql_metadata'])) {
        // Types of metadata to export.
        // In the future these can be allowed to be selected by the user
        $metadataTypes = PMA_getMetadataTypesToExport();
        $export_plugin->exportMetadata($db, $tables, $metadataTypes);

        if ($separate_files == 'database') {
            PMA_saveObjectInBuffer('metadata');
        }
    }

    if ($separate_files == 'database') {
        PMA_saveObjectInBuffer('extra');
    }

    if (($GLOBALS['sql_structure_or_data'] == 'structure'
        || $GLOBALS['sql_structure_or_data'] == 'structure_and_data')
        && isset($GLOBALS['sql_procedure_function'])
    ) {
        $export_plugin->exportEvents($db);

        if ($separate_files == 'database') {
            PMA_saveObjectInBuffer('events');
        }
    }
}

/**
 * Export at the table level
 *
 * @param string       $db              the database to export
 * @param string       $table           the table to export
 * @param string       $whatStrucOrData structure or data or both
 * @param ExportPlugin $export_plugin   the selected export plugin
 * @param string       $crlf            end of line character(s)
 * @param string       $err_url         the URL in case of error
 * @param string       $export_type     the export type
 * @param bool         $do_relation     whether to export relation info
 * @param bool         $do_comments     whether to add comments
 * @param bool         $do_mime         whether to add MIME info
 * @param bool         $do_dates        whether to add dates
 * @param string       $allrows         whether "dump all rows" was ticked
 * @param string       $limit_to        upper limit
 * @param string       $limit_from      starting limit
 * @param string       $sql_query       query for which exporting is requested
 * @param array        $aliases         Alias information for db/table/column
 *
 * @return void
 */
function PMA_exportTable(
    $db, $table, $whatStrucOrData, $export_plugin, $crlf, $err_url,
    $export_type, $do_relation, $do_comments, $do_mime, $do_dates,
    $allrows, $limit_to, $limit_from, $sql_query, $aliases
) {
    $db_alias = !empty($aliases[$db]['alias'])
        ? $aliases[$db]['alias'] : '';
    if (! $export_plugin->exportDBHeader($db, $db_alias)) {
        return;
    }
    if (isset($allrows)
        && $allrows == '0'
        && $limit_to > 0
        && $limit_from >= 0
    ) {
        $add_query  = ' LIMIT '
                    . (($limit_from > 0) ? $limit_from . ', ' : '')
                    . $limit_to;
    } else {
        $add_query  = '';
    }

    $_table = new Table($table, $db);
    $is_view = $_table->isView();
    if ($whatStrucOrData == 'structure'
        || $whatStrucOrData == 'structure_and_data'
    ) {

        if ($is_view) {

            if (isset($GLOBALS['sql_create_view'])) {
                if (! $export_plugin->exportStructure(
                    $db, $table, $crlf, $err_url, 'create_view',
                    $export_type, $do_relation, $do_comments,
                    $do_mime, $do_dates, $aliases
                )) {
                    return;
                }
            }

        } else if (isset($GLOBALS['sql_create_table'])) {

            if (! $export_plugin->exportStructure(
                $db, $table, $crlf, $err_url, 'create_table',
                $export_type, $do_relation, $do_comments,
                $do_mime, $do_dates, $aliases
            )) {
                return;
            }

        }

    }
    // If this is an export of a single view, we have to export data;
    // for example, a PDF report
    // if it is a merge table, no data is exported
    if ($whatStrucOrData == 'data'
        || $whatStrucOrData == 'structure_and_data'
    ) {
        if (! empty($sql_query)) {
            // only preg_replace if needed
            if (! empty($add_query)) {
                // remove trailing semicolon before adding a LIMIT
                $sql_query = preg_replace('%;\s*$%', '', $sql_query);
            }
            $local_query = $sql_query . $add_query;
            $GLOBALS['dbi']->selectDb($db);
        } else {
            // Data is exported only for Non-generated columns
            $tableObj = new PMA\libraries\Table($table, $db);
            $nonGeneratedCols = $tableObj->getNonGeneratedColumns(true);

            $local_query  = 'SELECT ' . implode(', ', $nonGeneratedCols)
                .  ' FROM ' . PMA\libraries\Util::backquote($db)
                . '.' . PMA\libraries\Util::backquote($table) . $add_query;
        }
        if (! $export_plugin->exportData(
            $db, $table, $crlf, $err_url, $local_query, $aliases
        )) {
            return;
        }
    }
    // now export the triggers (needs to be done after the data because
    // triggers can modify already imported tables)
    if (isset($GLOBALS['sql_create_trigger']) && ($whatStrucOrData == 'structure'
        || $whatStrucOrData == 'structure_and_data')
    ) {
        if (! $export_plugin->exportStructure(
            $db, $table, $crlf, $err_url, 'triggers',
            $export_type, $do_relation, $do_comments,
            $do_mime, $do_dates, $aliases
        )) {
            return;
        }
    }
    if (! $export_plugin->exportDBFooter($db)) {
        return;
    }

    if (isset($GLOBALS['sql_metadata'])) {
        // Types of metadata to export.
        // In the future these can be allowed to be selected by the user
        $metadataTypes = PMA_getMetadataTypesToExport();
        $export_plugin->exportMetadata($db, $table, $metadataTypes);
    }
}

/**
 * Loads correct page after doing export
 *
 * @param string $db          the database name
 * @param string $table       the table name
 * @param string $export_type Export type
 *
 * @return void
 */
function PMA_showExportPage($db, $table, $export_type)
{
    global $cfg;
    if ($export_type == 'server') {
        $active_page = 'server_export.php';
        include_once 'server_export.php';
    } elseif ($export_type == 'database') {
        $active_page = 'db_export.php';
        include_once 'db_export.php';
    } else {
        $active_page = 'tbl_export.php';
        include_once 'tbl_export.php';
    }
    exit();
}

/**
 * Merge two alias arrays, if array1 and array2 have
 * conflicting alias then array2 value is used if it
 * is non empty otherwise array1 value.
 *
 * @param array $aliases1 first array of aliases
 * @param array $aliases2 second array of aliases
 *
 * @return array resultant merged aliases info
 */
function PMA_mergeAliases($aliases1, $aliases2)
{
    // First do a recursive array merge
    // on aliases arrays.
    $aliases = array_merge_recursive($aliases1, $aliases2);
    // Now, resolve conflicts in aliases, if any
    foreach ($aliases as $db_name => $db) {
        // If alias key is an array then
        // it is a merge conflict.
        if (isset($db['alias']) && is_array($db['alias'])) {
            $val1 = $db['alias'][0];
            $val2 = $db['alias'][1];
            // Use aliases2 alias if non empty
            $aliases[$db_name]['alias']
                = empty($val2) ? $val1 : $val2;
        }
        if (!isset($db['tables'])) {
            continue;
        }
        foreach ($db['tables'] as $tbl_name => $tbl) {
            if (isset($tbl['alias']) && is_array($tbl['alias'])) {
                $val1 = $tbl['alias'][0];
                $val2 = $tbl['alias'][1];
                // Use aliases2 alias if non empty
                $aliases[$db_name]['tables'][$tbl_name]['alias']
                    = empty($val2) ? $val1 : $val2;
            }
            if (!isset($tbl['columns'])) {
                continue;
            }
            foreach ($tbl['columns'] as  $col => $col_as) {
                if (isset($col_as) && is_array($col_as)) {
                    $val1 = $col_as[0];
                    $val2 = $col_as[1];
                    // Use aliases2 alias if non empty
                    $aliases[$db_name]['tables'][$tbl_name]['columns'][$col]
                        = empty($val2) ? $val1 : $val2;
                }
            };
        };
    }
    return $aliases;
}

/**
 * Locks tables
 *
 * @param string $db       database name
 * @param array  $tables   list of table names
 * @param string $lockType lock type; "[LOW_PRIORITY] WRITE" or "READ [LOCAL]"
 *
 * @return mixed result of the query
 */
function PMA_lockTables($db, $tables, $lockType = "WRITE")
{
    $locks = array();
    foreach ($tables as $table) {
        $locks[] = PMA\libraries\Util::backquote($db) . "."
            . PMA\libraries\Util::backquote($table) . " " . $lockType;
    }

    $sql = "LOCK TABLES " . implode(", ", $locks);
    return $GLOBALS['dbi']->tryQuery($sql);
}

/**
 * Releases table locks
 *
 * @return mixed result of the query
 */
function PMA_unlockTables()
{
    return $GLOBALS['dbi']->tryQuery("UNLOCK TABLES");
}

/**
 * Returns all the metadata types that can be exported with a database or a table
 *
 * @return string[] metadata types.
 */
function PMA_getMetadataTypesToExport()
{
    return array(
        'column_info',
        'table_uiprefs',
        'tracking',
        'bookmark',
        'relation',
        'table_coords',
        'pdf_pages',
        'savedsearches',
        'central_columns',
        'export_templates',
    );
}

/**
 * Returns the checked clause, depending on the presence of key in array
 *
 * @param string $key   the key to look for
 * @param array  $array array to verify
 *
 * @return string the checked clause
 */
function PMA_getCheckedClause($key, $array)
{
    if (in_array($key, $array)) {
        return ' checked="checked"';
    } else {
        return '';
    }
}
