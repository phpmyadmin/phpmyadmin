<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Interface for the zip extension
 * @package    phpMyAdmin
 * @version    $Id$
 */

/**
  * Gets zip file contents
  *
  * @param   string  $file
  * @return  array  ($error_message, $file_data); $error_message
  *                  is empty if no error
  * @author lem9
  */

function PMA_getZipContents($file)
{
    $error_message = '';
    $file_data = '';
    $zip_handle = zip_open($file);
    if (is_resource($zip_handle)) {
        $first_zip_entry = zip_read($zip_handle);
        if (false === $first_zip_entry) {
            $error_message = $GLOBALS['strNoFilesFoundInZip'];
        } else {
            /* Is the the zip really an ODS file? */
            $read = zip_entry_read($first_zip_entry);
            $ods_mime = 'application/vnd.oasis.opendocument.spreadsheet';
            if (!strcmp($ods_mime, $read)) {
                /* Return the correct contents, not just the first entry */
                for ( ; ; ) {
                    $entry = zip_read($zip_handle);
                    if (is_resource($entry)) {
                        if (!strcmp('content.xml', zip_entry_name($entry))) {
                            zip_entry_open($zip_handle, $entry, 'r');
                            $file_data = zip_entry_read($entry, zip_entry_filesize($entry));
                            zip_entry_close($entry);
                            break;
                        }
                    } else {
                        /**
                         * Either we have reached the end of the zip and still
                         * haven't found 'content.xml' or there was a parsing
                         * error that we must display
                         */
                        if ($entry === FALSE) {
                            $error_message = $GLOBALS['strErrorInZipFile'] . ' Could not find "content.xml"';
                        } else {
                            $error_message = $GLOBALS['strErrorInZipFile'] . ' ' . PMA_getZipError($zip_handle);
                        }
                        
                        break;
                    }
                }
            } else {
                zip_entry_open($zip_handle, $first_zip_entry, 'r');
                /* File pointer has already been moved, so include what was read above */
                $file_data = $read;
                $file_data .= zip_entry_read($first_zip_entry, zip_entry_filesize($first_zip_entry));
                zip_entry_close($first_zip_entry);
            }
        }
    } else {
        $error_message = $GLOBALS['strErrorInZipFile'] . ' ' . PMA_getZipError($zip_handle);
    }
    zip_close($zip_handle);
    return (array('error' => $error_message, 'data' => $file_data));
}

/**
  * Gets zip error message
  *
  * @param   integer  error code
  * @return  string  error message
  * @author lem9
 */
function PMA_getZipError($code)
{
    // I don't think this needs translation
    switch ($code) {
        case ZIPARCHIVE::ER_MULTIDISK:
            $message = 'Multi-disk zip archives not supported';
             break;
        case ZIPARCHIVE::ER_READ:
            $message = 'Read error';
             break;
        case ZIPARCHIVE::ER_CRC:
            $message = 'CRC error';
             break;
        case ZIPARCHIVE::ER_NOZIP:
            $message = 'Not a zip archive';
             break;
        case ZIPARCHIVE::ER_INCONS:
            $message = 'Zip archive inconsistent';
             break;
        default:
            $message = $code;
    }
    return $message;
}
?>
