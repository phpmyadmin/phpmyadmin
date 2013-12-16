<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for the zip extension
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Gets zip file contents
 *
 * @param string $file           zip file
 * @param string $specific_entry regular expression to match a file
 *
 * @return array ($error_message, $file_data); $error_message
 *                  is empty if no error
 */
function PMA_getZipContents($file, $specific_entry = null)
{
    $error_message = '';
    $file_data = '';
    $zip_handle = zip_open($file);
    if (is_resource($zip_handle)) {
        $first_zip_entry = zip_read($zip_handle);
        if (false === $first_zip_entry) {
            $error_message = __('No files found inside ZIP archive!');
        } else {
            /* Is the the zip really an ODS file? */
            $read = zip_entry_read($first_zip_entry);
            $ods_mime = 'application/vnd.oasis.opendocument.spreadsheet';
            if (!strcmp($ods_mime, $read)) {
                $specific_entry = '/^content\.xml$/';
            }

            if (isset($specific_entry)) {
                /* Return the correct contents, not just the first entry */
                for ( ; ; ) {
                    $entry = zip_read($zip_handle);
                    if (is_resource($entry)) {
                        if (preg_match($specific_entry, zip_entry_name($entry))) {
                            zip_entry_open($zip_handle, $entry, 'r');
                            $file_data = zip_entry_read(
                                $entry,
                                zip_entry_filesize($entry)
                            );
                            zip_entry_close($entry);
                            break;
                        }
                    } else {
                        /**
                         * Either we have reached the end of the zip and still
                         * haven't found $specific_entry or there was a parsing
                         * error that we must display
                         */
                        if ($entry === false) {
                            $error_message = __('Error in ZIP archive:')
                                . ' Could not find "' . $specific_entry . '"';
                        } else {
                            $error_message = __('Error in ZIP archive:')
                                . ' ' . PMA_getZipError($zip_handle);
                        }

                        break;
                    }
                }
            } else {
                zip_entry_open($zip_handle, $first_zip_entry, 'r');
                /* File pointer has already been moved,
                 * so include what was read above */
                $file_data = $read;
                $file_data .= zip_entry_read(
                    $first_zip_entry,
                    zip_entry_filesize($first_zip_entry)
                );
                zip_entry_close($first_zip_entry);
            }
        }
    } else {
        $error_message = __('Error in ZIP archive:')
            . ' ' . PMA_getZipError($zip_handle);
    }
    zip_close($zip_handle);
    return (array('error' => $error_message, 'data' => $file_data));
}

/**
 * Returns the file name of the first file that matches the given $file_regexp.
 *
 * @param string $file_regexp regular expression for the file name to match
 * @param string $file        zip archive
 *
 * @return string the file name of the first file that matches the given regexp
 */
function PMA_findFileFromZipArchive ($file_regexp, $file)
{
    $zip_handle = zip_open($file);
    if (is_resource($zip_handle)) {
        $entry = zip_read($zip_handle);
        while (is_resource($entry)) {
            if (preg_match($file_regexp, zip_entry_name($entry))) {
                $file_name = zip_entry_name($entry);
                zip_close($zip_handle);
                return $file_name;
            }
            $entry = zip_read($zip_handle);
        }
    }
    zip_close($zip_handle);
    return false;
}

/**
 * Returns the number of files in the zip archive.
 *
 * @param string $file zip archive
 *
 * @return int the number of files in the zip archive
 */
function PMA_getNoOfFilesInZip($file)
{
    $count = 0;
    $zip_handle = zip_open($file);
    if (is_resource($zip_handle)) {
        $entry = zip_read($zip_handle);
        while (is_resource($entry)) {
            $count++;
            $entry = zip_read($zip_handle);
        }
    }
    zip_close($zip_handle);
    return $count;
}

/**
 * Extracts a set of files from the given zip archive to a given destinations.
 *
 * @param string $zip_path    path to the zip archive
 * @param string $destination destination to extract files
 * @param array  $entries     files in archive that should be extracted
 *
 * @return bool true on sucess, false otherwise
 */
function PMA_zipExtract($zip_path, $destination, $entries)
{
    $zip = new ZipArchive;
    if ($zip->open($zip_path) === true) {
        $zip->extractTo($destination, $entries);
        $zip->close();
        return true;
    }
    return false;
}

/**
  * Gets zip error message
  *
  * @param integer $code error code
  *
  * @return string error message
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
