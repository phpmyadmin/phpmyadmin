<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for the zip extension
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

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
     * @var ZipArchive
     */
    private $zip;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    /**
     * Gets zip file contents
     *
     * @param string $file           path to zip file
     * @param string $specific_entry regular expression to match a file
     *
     * @return array ($error_message, $file_data); $error_message
     *                  is empty if no error
     */
    public function getContents($file, $specific_entry = null)
    {
        /**
        * This function is used to "import" a SQL file which has been exported earlier
        * That means that this function works on the assumption that the zip file contains only a single SQL file
        * It might also be an ODS file, look below
        */

        $error_message = '';
        $file_data = '';

        $res = $this->zip->open($file);

        if ($res === true) {
            if ($this->zip->numFiles === 0) {
                $error_message = __('No files found inside ZIP archive!');
                $this->zip->close();
                return [
                    'error' => $error_message,
                    'data' => $file_data,
                ];
            }

            /* Is the the zip really an ODS file? */
            $ods_mime = 'application/vnd.oasis.opendocument.spreadsheet';
            $first_zip_entry = $this->zip->getFromIndex(0);
            if (! strcmp($ods_mime, $first_zip_entry)) {
                $specific_entry = '/^content\.xml$/';
            }

            if (! isset($specific_entry)) {
                $file_data = $first_zip_entry;
                $this->zip->close();
                return [
                    'error' => $error_message,
                    'data' => $file_data,
                ];
            }

            /* Return the correct contents, not just the first entry */
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                if (@preg_match($specific_entry, $this->zip->getNameIndex($i))) {
                    $file_data = $this->zip->getFromIndex($i);
                    break;
                }
            }

            /* Couldn't find any files that matched $specific_entry */
            if (empty($file_data)) {
                $error_message = __('Error in ZIP archive:')
                    . ' Could not find "' . $specific_entry . '"';
            }

            $this->zip->close();
            return [
                'error' => $error_message,
                'data' => $file_data,
            ];
        } else {
            $error_message = __('Error in ZIP archive:') . ' ' . $this->zip->getStatusString();
            $this->zip->close();
            return [
                'error' => $error_message,
                'data' => $file_data,
            ];
        }
    }

    /**
     * Returns the filename of the first file that matches the given $file_regexp.
     *
     * @param string $file  path to zip file
     * @param string $regex regular expression for the file name to match
     *
     * @return string|false the file name of the first file that matches the given regular expression
     */
    public function findFile($file, $regex)
    {
        $res = $this->zip->open($file);

        if ($res === true) {
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                if (preg_match($regex, $this->zip->getNameIndex($i))) {
                    $filename = $this->zip->getNameIndex($i);
                    $this->zip->close();
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
    public function getNumberOfFiles($file)
    {
        $num = 0;
        $res = $this->zip->open($file);

        if ($res === true) {
            $num = $this->zip->numFiles;
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
    public function extract($file, $entry)
    {
        if ($this->zip->open($file) === true) {
            $result = $this->zip->getFromName($entry);
            $this->zip->close();
            return $result;
        }
        return false;
    }

    /**
     * Creates a zip file.
     * If $data is an array and $name is a string, the filenames will be indexed.
     * The function will return false if $data is a string but $name is an array
     * or if $data is an array and $name is an array, but they don't have the
     * same amount of elements.
     *
     * @param array|string $data contents of the file/files
     * @param array|string $name name of the file/files in the archive
     * @param integer      $time the current timestamp
     *
     * @return string|bool the ZIP file contents, or false if there was an error.
     */
    public function createFile($data, $name, $time = 0)
    {
        $datasec = []; // Array to store compressed data
        $ctrl_dir = []; // Central directory
        $old_offset = 0; // Last offset position
        $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; // End of central directory record

        if (is_string($data) && is_string($name)) {
            $data = [$name => $data];
        } elseif (is_array($data) && is_string($name)) {
            $ext_pos = strpos($name, '.');
            $extension = substr($name, $ext_pos);
            $newData = [];
            foreach ($data as $key => $value) {
                $newName = str_replace(
                    $extension,
                    '_' . $key . $extension,
                    $name
                );
                $newData[$newName] = $value;
            }
            $data = $newData;
        } elseif (is_array($data) && is_array($name) && count($data) === count($name)) {
            $data = array_combine($name, $data);
        } else {
            return false;
        }

        foreach ($data as $table => $dump) {
            $temp_name = str_replace('\\', '/', $table);

            /* Get Local Time */
            $timearray = getdate();

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

            $unc_len = strlen($dump);
            $crc = crc32($dump);
            $zdata = gzcompress($dump);
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
            $old_offset += strlen($fr);
            // optional extra field, file comment goes here
            // save to central directory
            $ctrl_dir[] = $cdrec;
        }

        /* Build string to return */
        $temp_ctrldir = implode('', $ctrl_dir);
        $header = $temp_ctrldir .
            $eof_ctrl_dir .
            pack('v', count($ctrl_dir)) . //total #of entries "on this disk"
            pack('v', count($ctrl_dir)) . //total #of entries overall
            pack('V', strlen($temp_ctrldir)) . //size of central dir
            pack('V', $old_offset) . //offset to start of central dir
            "\x00\x00";                         //.zip file comment length

        $data = implode('', $datasec);

        return $data . $header;
    }
}
