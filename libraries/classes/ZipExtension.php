<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for the zip extension
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use ZipArchive;

/**
 * Transformations class
 *
 * @package PhpMyAdmin
 */
class ZipExtension
{
    /**
     * Gets zip file contents
     *
     * @param string $file           path to zip file
     * @param string $specific_entry regular expression to match a file
     *
     * @return array ($error_message, $file_data); $error_message
     *                  is empty if no error
     */
    public static function getContents($file, $specific_entry = null)
    {
        /**
        * This function is used to "import" a SQL file which has been exported earlier
        * That means that this function works on the assumption that the zip file contains only a single SQL file
        * It might also be an ODS file, look below
        */

        $error_message = '';
        $file_data = '';

        $zip = new ZipArchive;
        $res = $zip->open($file);

        if ($res === TRUE) {
            if ($zip->numFiles === 0) {
                $error_message = __('No files found inside ZIP archive!');
                $zip->close();
                return (array('error' => $error_message, 'data' => $file_data));
            }

            /* Is the the zip really an ODS file? */
            $ods_mime = 'application/vnd.oasis.opendocument.spreadsheet';
            $first_zip_entry = $zip->getFromIndex(0);
            if (!strcmp($ods_mime, $first_zip_entry)) {
                $specific_entry = '/^content\.xml$/';
            }

            if (!isset($specific_entry)) {
                $file_data = $first_zip_entry;
                $zip->close();
                return (array('error' => $error_message, 'data' => $file_data));
            }

            /* Return the correct contents, not just the first entry */
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (@preg_match($specific_entry, $zip->getNameIndex($i))) {
                    $file_data = $zip->getFromIndex($i);
                    break;
                }
            }

            /* Couldn't find any files that matched $specific_entry */
            if (empty($file_data)) {
                $error_message = __('Error in ZIP archive:')
                    . ' Could not find "' . $specific_entry . '"';
            }

            $zip->close();
            return (array('error' => $error_message, 'data' => $file_data));
        } else {
            $error_message = __('Error in ZIP archive:') . ' ' . $zip->getStatusString();
            $zip->close();
            return (array('error' => $error_message, 'data' => $file_data));
        }
    }

    /**
     * Returns the filename of the first file that matches the given $file_regexp.
     *
     * @param string $file  path to zip file
     * @param string $regex regular expression for the file name to match
     *
     * @return string the file name of the first file that matches the given regular expression
     */
    public static function findFile($file, $regex)
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);

        if ($res === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match($regex, $zip->getNameIndex($i))) {
                    $filename = $zip->getNameIndex($i);
                    $zip->close();
                    return $filename;
                }
            }
        }
        return false;
    }

    /**
     * Returns the number of files in the zip archive.
     *
     * @param string $file path to zip file
     *
     * @return int the number of files in the zip archive or 0, either if there wern't any files or an error occured.
     */
    public static function getNumberOfFiles($file)
    {
        $num = 0;
        $zip = new ZipArchive;
        $res = $zip->open($file);

        if ($res === TRUE) {
            $num = $zip->numFiles;
        }
        return $num;
    }

    /**
     * Extracts the content of $entry.
     *
     * @param string $file  path to zip file
     * @param string $entry file in the archive that should be extracted
     *
     * @return string|bool data on sucess, false otherwise
     */
    public static function extract($file, $entry)
    {
        $zip = new ZipArchive;
        if ($zip->open($file) === true) {
            $result = $zip->getFromName($entry);
            $zip->close();
            return $result;
        }
        return false;
    }

    /**
     * Creates a zip file.
     * If $data is an array and $name is a string, the filenames will be indexed.
     * The function will return false if $data is a string but $name is an array or if $data is an array and $name is an array, but they don't have the same amount of elements.
     *
     * @param array|string $data contents of the file/files
     * @param array|string $name name of the file/files in the archive
     * @param integer      $time the current timestamp
     *
     * @return string|bool  the ZIP file contents, or false if there was an error.
     */
    public static function createFile($data, $name, $time = 0)
    {
        $datasec = array();  // Array to store compressed data
        $ctrl_dir = array(); // Central directory
        $old_offset = 0;     // Last offset position
        $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; // End of central directory record

        if (is_string($name) && is_string($data)) {
            $name = array($name);
            $data = array($data);
        } else {
            if (! is_array($name) || ! is_array($data) || count($name) != count($data)) {
                return false;
            }
        }

        for ($i = 0; $i < count($data); $i++) {
            $temp_name = str_replace('\\', '/', $name[$i]);

            /* Convert Unix timestamp to DOS timestamp */
            $timearray = ($time == 0) ? getdate() : getdate($time);

            if ($timearray['year'] < 1980) {
                $timearray['year'] = 1980;
                $timearray['mon'] = 1;
                $timearray['mday'] = 1;
                $timearray['hours'] = 0;
                $timearray['minutes'] = 0;
                $timearray['seconds'] = 0;
            }

            $time = (($timearray['year'] - 1980) << 25)
            | ($timearray['mon'] << 21)
            | ($timearray['mday'] << 16)
            | ($timearray['hours'] << 11)
            | ($timearray['minutes'] << 5)
            | ($timearray['seconds'] >> 1);

            $hexdtime = pack('V', $time);

            $unc_len = strlen($data[$i]);
            $crc = crc32($data[$i]);
            $zdata = gzcompress($data[$i]);
            $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
            $c_len = strlen($zdata);
            $fr = "\x50\x4b\x03\x04"
                . "\x14\x00"        // ver needed to extract
                . "\x00\x00"        // gen purpose bit flag
                . "\x08\x00"        // compression method
                . $hexdtime         // last mod time and date

                // "local file header" segment
                . pack('V', $crc)               // crc32
                . pack('V', $c_len)             // compressed filesize
                . pack('V', $unc_len)           // uncompressed filesize
                . pack('v', strlen($temp_name)) // length of filename
                . pack('v', 0)                  // extra field length
                . $temp_name

                // "file data" segment
                . $zdata;

            $datasec[] = $fr;

            $old_offset += strlen($fr);
            // now add to central directory record
            $cdrec = "\x50\x4b\x01\x02"
                . "\x00\x00"                     // version made by
                . "\x14\x00"                     // version needed to extract
                . "\x00\x00"                     // gen purpose bit flag
                . "\x08\x00"                     // compression method
                . $hexdtime                      // last mod time & date
                . pack('V', $crc)                // crc32
                . pack('V', $c_len)              // compressed filesize
                . pack('V', $unc_len)            // uncompressed filesize
                . pack('v', strlen($temp_name))  // length of filename
                . pack('v', 0)                   // extra field length
                . pack('v', 0)                   // file comment length
                . pack('v', 0)                   // disk number start
                . pack('v', 0)                   // internal file attributes
                . pack('V', 32)                  // external file attributes
                                                 // - 'archive' bit set
                . pack('V', $old_offset)         // relative offset of local header
                . $temp_name;                    // filename

            // optional extra field, file comment goes here
            // save to central directory
            $ctrl_dir[] = $cdrec;
        }

        /* Build string to return */
        $temp_ctrldir = implode('', $ctrl_dir);
        $header = $temp_ctrldir .
            $eof_ctrl_dir .
            pack('v', sizeof($ctrl_dir)) .      //total #of entries "on this disk"
            pack('v', sizeof($ctrl_dir)) .      //total #of entries overall
            pack('V', strlen($temp_ctrldir)) .  //size of central dir
            pack('V', $old_offset) .            //offset to start of central dir
            "\x00\x00";                         //.zip file comment length

        $data = implode('', $datasec);

        return $data . $header;
    }
}
