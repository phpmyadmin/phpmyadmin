<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This script imports relation infos from docSQL (www.databay.de)
 */


/**
 * Get the values of the variables posted or sent to this script and display
 * the headers
 */
require_once('./libraries/read_dump.lib.php');
require_once('./libraries/grab_globals.lib.php');
require_once('./header.inc.php');

//require common added for string importing - Robbat2, 15 January 2003 9.34PM
//all hardcoded strings converted by Robbat2, 15 January 2003 9.34PM
require_once('./libraries/common.lib.php');

// Check parameters
PMA_checkParameters(array('db'));

// We do any work, only if docSQL import was enabled in config
if (isset($cfg['docSQLDir']) && !empty($cfg['docSQLDir'])) {

    if (substr($cfg['docSQLDir'], -1) != '/') {
        $cfg['docSQLDir'] .= '/';
    }

    /**
     * Imports docSQL files
     *
     * @param   string   the basepath
     * @param   string   the filename
     * @param   string   the complete filename
     * @param   string   the content of a file

     *
     * @return  boolean  always true
     *
     * @global  array    GLOBAL variables
     */
    function docsql_check($docpath = '', $file = '', $filename = '', $content = 'none') {
    global $GLOBALS;

        if (preg_match('@^(.*)_field_comment\.(txt|zip|bz2|bzip).*$@i', $filename)) {
            $tab = preg_replace('@^(.*)_field_comment\.(txt|zip|bz2|bzip).*@si', '\1', $filename);
            //echo '<h1>Working on Table ' . $_tab . '</h1>';
            if ($content == 'none') {
                $lines = array();
                $fd  = fopen($docpath . $file, 'r');
                if ($fd) {
                    while (!feof($fd)) {
                        $lines[]    = fgets($fd, 4096);
                    }
                }
            } else {
                $content = str_replace("\r\n", "\n", $content);
                $content = str_replace("\r", "\n", $content);
                $lines = explode("\n", $content);
            }

            if (isset($lines) && is_array($lines) && count($lines) > 0) {
                foreach($lines AS $lkey => $line) {
                    //echo '<p>' . $line . '</p>';
                    $inf     = explode('|',$line);
                    if (!empty($inf[1]) && strlen(trim($inf[1])) > 0) {
                        $qry = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['column_info'])
                                . ' (db_name, table_name, column_name, ' . PMA_backquote('comment') . ') '
                                . ' VALUES('
                                . '\'' . PMA_sqlAddslashes($GLOBALS['db']) . '\','
                                . '\'' . PMA_sqlAddslashes(trim($tab)) . '\','
                                . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\','
                                . '\'' . PMA_sqlAddslashes(trim($inf[1])) . '\')';
                        if (PMA_query_as_cu($qry)) {
                            echo '<p>' . $GLOBALS['strAddedColumnComment'] . ' ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]) . '</p>';
                        } else {
                            echo '<p>' . $GLOBALS['strWritingCommentNotPossible'] . '</p>';
                        }
                        echo "\n";
                    } // end inf[1] exists
                    if (!empty($inf[2]) && strlen(trim($inf[2])) > 0) {
                        $for = explode('->', $inf[2]);
                        $qry = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['relation'])
                                . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                                . ' VALUES('
                                . '\'' . PMA_sqlAddslashes($GLOBALS['db']) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($tab)) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\', '
                                . '\'' . PMA_sqlAddslashes($GLOBALS['db']) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($for[0])) . '\','
                                . '\'' . PMA_sqlAddslashes(trim($for[1])) . '\')';
                        if (PMA_query_as_cu($qry)) {
                            echo '<p>' . $GLOBALS['strAddedColumnRelation'] . ' ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]) . ' to ' . htmlspecialchars($inf[2]) . '</p>';
                        } else {
                            echo '<p>' . $GLOBALS['strWritingRelationNotPossible'] . '</p>';
                        }
                        echo "\n";
                    } // end inf[2] exists
                }
                echo '<p><font color="green">' . $GLOBALS['strImportFinished'] . '</font></p>' . "\n";
            } else {
                echo '<p><font color="red">' . $GLOBALS['strFileCouldNotBeRead'] . '</font></p>' . "\n";
            }

            return 1;
        } else {
            if ($content != 'none') {
                echo '<p><font color="orange">' . sprintf($GLOBALS['strIgnoringFile'], ' ' . htmlspecialchars($file)) . '</font></p>' . "\n";
            } else {
                // garvin: disabled. Shouldn't impose ANY non-submitted files ever.
                echo '<p><font color="orange">' . sprintf($GLOBALS['strIgnoringFile'], ' ' . '...') . '</font></p>' . "\n";
            }
            return 0;
        } // end working on table
    }

    /**
     * Try to get the "$DOCUMENT_ROOT" variable whatever is the register_globals
     * value
     */
    if (empty($DOCUMENT_ROOT)) {
        if (!empty($_SERVER) && isset($_SERVER['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
        }
        else if (!empty($_ENV) && isset($_ENV['DOCUMENT_ROOT'])) {
            $DOCUMENT_ROOT = $_ENV['DOCUMENT_ROOT'];
        }
        else if (@getenv('DOCUMENT_ROOT')) {
            $DOCUMENT_ROOT = getenv('DOCUMENT_ROOT');
        }
        else {
            $DOCUMENT_ROOT = '.';
        }
    } // end if

    /**
     * Executes import if required
     */
    if (isset($do) && $do == 'import') {
        $orig_docpath = $docpath;

        if (empty($sql_file)) {
            $sql_file  = 'none';
        }

        // Get relation settings
        require_once('./libraries/relation.lib.php');
        $cfgRelation = PMA_getRelationsParam();

        // Gets the query from a file if required
        if ($sql_file != 'none') {
            if (file_exists($sql_file)
                && is_uploaded_file($sql_file)) {

                $open_basedir = @ini_get('open_basedir');

                // If we are on a server with open_basedir, we must move the file
                // before opening it. The doc explains how to create the "./tmp"
                // directory

                if (!empty($open_basedir)) {

                    $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

                    // function is_writeable() is valid on PHP3 and 4
                    if (!is_writeable($tmp_subdir)) {
                        $docsql_text = PMA_readFile($sql_file, $sql_file_compression);
                        if ($docsql_text == FALSE) {
                            echo $strFileCouldNotBeRead;
                            exit();
                        }
                    }
                    else {
                        $sql_file_new = $tmp_subdir . basename($sql_file);
                        move_uploaded_file($sql_file, $sql_file_new);
                        $docsql_text = PMA_readFile($sql_file_new, $sql_file_compression);
                        unlink($sql_file_new);
                    }
                }
                else {
                    // read from the normal upload dir
                    $docsql_text = PMA_readFile($sql_file, $sql_file_compression);
                }

                // Convert the file's charset if necessary
                if ($cfg['AllowAnywhereRecoding'] && $allow_recoding
                    && isset($charset_of_file) && $charset_of_file != $charset) {
                    $docsql_text = PMA_convert_string($charset_of_file, $charset, $docsql_text);
                }

                if (!isset($docsql_text) || $docsql_text == FALSE || $docsql_text == '') {
                    echo '<p><font color="red">' . $GLOBALS['strFileCouldNotBeRead'] . '</font></p>' . "\n";
                } else {
                    docsql_check('', $sql_file_name, $sql_file_name, $docsql_text);
                }
            } // end uploaded file stuff
        } else {

            // echo '<h1>Starting Import</h1>';
            $docpath = $cfg['docSQLDir'] . preg_replace('@\.\.*@', '.', $docpath);
            if (substr($docpath, -1) != '/') {
                $docpath .= '/';
            }

            $matched_files = 0;

            if (is_dir($docpath)) {
                // Do the work
                $handle = opendir($docpath);
                while ($file = @readdir($handle)) {
                    $filename = basename($file);
                    // echo '<p>Working on file ' . $filename . '</p>';
                    $matched_files += docsql_check($docpath, $file, $filename);
                } // end while
            } else {
                echo '<p><font color="red">' .$docpath . ': ' . $strThisNotDirectory . "</font></p>\n";
            }
        }
    }


    /**
     * Displays the form
     */
    ?>

    <form method="post" action="db_details_importdocsql.php" <?php if ($is_upload) echo ' enctype="multipart/form-data"'; ?>>
        <?php echo PMA_generate_common_hidden_inputs($db); ?>
        <input type="hidden" name="submit_show" value="true" />
        <input type="hidden" name="do" value="import" />
        <b><?php echo $strAbsolutePathToDocSqlDir; ?>:</b>
        <br /><br />
        <?php echo $cfg['docSQLDir']; ?>/<input class="textfield" type="text" name="docpath" size="15" value="<?php echo (isset($orig_docpath) ? $orig_docpath : ''); ?>" />
    <?php
    // garvin: displays import dump feature only if file upload available
    if ($is_upload) {
        echo '<br /><br />';
        echo '            <i>' . $strOr . '</i> ' . $strLocationTextfile . '&nbsp;:<br />' . "\n";
        ?>
                <div style="margin-bottom: 5px">
                <input type="file" name="sql_file" class="textfield" /><br />
        <?php
        if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
            $temp_charset = reset($cfg['AvailableCharsets']);
            echo $strCharsetOfFile . "\n"
                 . '        <select name="charset_of_file" size="1">' . "\n"
                 . '                <option value="' . $temp_charset . '"';
            if ($temp_charset == $charset) {
                echo ' selected="selected"';
            }
            echo '>' . $temp_charset . '</option>' . "\n";
            while ($temp_charset = next($cfg['AvailableCharsets'])) {
                echo '                <option value="' . $temp_charset . '"';
                if ($temp_charset == $charset) {
                    echo ' selected="selected"';
                }
                echo '>' . $temp_charset . '</option>' . "\n";
            } // end while
            echo '            </select><br />' . "\n" . '    ';
        } // end if
        $is_gzip = ($cfg['GZipDump'] && @function_exists('gzopen'));
        $is_bzip = ($cfg['BZipDump'] && @function_exists('bzdecompress'));
        if ($is_bzip || $is_gzip) {
            echo '        ' . $strCompression . ':' . "\n"
               . '            <input type="radio" id="radio_sql_file_compression_auto" name="sql_file_compression" value="" checked="checked" />' . "\n"
               . '            <label for="radio_sql_file_compression_auto">' . $strAutodetect . '</label>&nbsp;&nbsp;&nbsp;' . "\n"
               . '            <input type="radio" id="radio_sql_file_compression_plain" name="sql_file_compression" value="text/plain" />' . "\n"
               . '            <label for="radio_sql_file_compression_plain">' . $strNone . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
            if ($is_gzip) {
                echo '            <input type="radio" id="radio_sql_file_compression_gzip" name="sql_file_compression" value="application/x-gzip" />' . "\n"
                   . '            <label for="radio_sql_file_compression_gzip">' . $strGzip . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
            }
            if ($is_bzip) {
                echo '            <input type="radio" id="radio_sql_file_compression_bzip" name="sql_file_compression" value="application/x-bzip" />' . "\n"
                   . '            <label for="radio_sql_file_compression_bzip">' . $strBzip . '</label>&nbsp;&nbsp;&nbsp;' . "\n";
            }
        } else {
            echo '        <input type="hidden" name="sql_file_compression" value="text/plain" />' . "\n";
        }
        ?>
                </div>
        <?php
    } // end if
    echo "\n";
    ?>
        <br />
        &nbsp;<input type="submit" value="<?php echo $strImportFiles; ?>" />
    </form>

<?php

} // End if use docSQL

/**
 * Displays the footer
 */
echo "\n";
require_once('./footer.inc.php');

?>
