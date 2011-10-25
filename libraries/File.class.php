<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * file upload functions
 *
 * @package PhpMyAdmin
 */

/**
 *
 * @todo when uploading a file into a blob field, should we also consider using
 *       chunks like in import? UPDATE `table` SET `field` = `field` + [chunk]
 * @package PhpMyAdmin
 */
class PMA_File
{
    /**
     * @var string the temporary file name
     * @access protected
     */
    var $_name = null;

    /**
     * @var string the content
     * @access protected
     */
    var $_content = null;

    /**
     * @var string the error message
     * @access protected
     */
    var $_error_message = '';

    /**
     * @var bool whether the file is temporary or not
     * @access protected
     */
    var $_is_temp = false;

    /**
     * @var string type of compression
     * @access protected
     */
    var $_compression = null;

    /**
     * @var integer
     */
    var $_offset = 0;

    /**
     * @var integer size of chunk to read with every step
     */
    var $_chunk_size = 32768;

    /**
     * @var resource file handle
     */
    var $_handle = null;

    /**
     * @var boolean whether to decompress content before returning
     */
    var $_decompress = false;

    /**
     * @var string charset of file
     */
    var $_charset = null;

    /**
     * @staticvar string most recent BLOB repository reference
    */
    static $_recent_bs_reference = null;

    /**
     * constructor
     *
     * @access  public
     * @param string  $name   file name
     */
    function __construct($name = false)
    {
        if ($name) {
            $this->setName($name);
        }
    }

    /**
     * destructor
     *
     * @see     PMA_File::cleanUp()
     * @access  public
     */
    function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes file if it is temporary, usally from a moved upload file
     *
     * @access  public
     * @return  boolean success
     */
    function cleanUp()
    {
        if ($this->isTemp()) {
            return $this->delete();
        }

        return true;
    }

    /**
     * deletes the file
     *
     * @access  public
     * @return  boolean success
     */
    function delete()
    {
        return unlink($this->getName());
    }

    /**
     * checks or sets the temp flag for this file
     * file objects with temp flags are deleted with object destruction
     *
     * @access  public
     * @param boolean sets the temp flag
     * @return  boolean PMA_File::$_is_temp
     */
    function isTemp($is_temp = null)
    {
        if (null !== $is_temp) {
            $this->_is_temp = (bool) $is_temp;
        }

        return $this->_is_temp;
    }

    /**
     * accessor
     *
     * @access  public
     * @param string  $name   file name
     */
    function setName($name)
    {
        $this->_name = trim($name);
    }

    /**
     * @access  public
     * @return  string  binary file content
     */
    function getContent($as_binary = true, $offset = 0, $length = null)
    {
        if (null === $this->_content) {
            if ($this->isUploaded() && ! $this->checkUploadedFile()) {
                return false;
            }

            if (! $this->isReadable()) {
                return false;
            }

            if (function_exists('file_get_contents')) {
                $this->_content = file_get_contents($this->getName());
            } elseif ($size = filesize($this->getName())) {
                $this->_content = fread(fopen($this->getName(), 'rb'), $size);
            }
        }

        if (! empty($this->_content) && $as_binary) {
            return '0x' . bin2hex($this->_content);
        }

        if (null !== $length) {
            return substr($this->_content, $offset, $length);
        } elseif ($offset > 0) {
            return substr($this->_content, $offset);
        }

        return $this->_content;
    }

    /**
     * @access  public
     * @return bool
     */
    function isUploaded()
    {
        return is_uploaded_file($this->getName());
    }

    /**
     * accessor
     *
     * @access  public
     * @return  string  PMA_File::$_name
     */
    function getName()
    {
        return $this->_name;
    }

    /**
     * @access  public
     * @param string  name of file uploaded
     * @return  boolean success
     */
    function setUploadedFile($name)
    {
        $this->setName($name);

        if (! $this->isUploaded()) {
            $this->setName(null);
            $this->_error_message = __('File was not an uploaded file.');
            return false;
        }

        return true;
    }

    /**
     * @access  public
     * @param string  $key the md5 hash of the column name
     * @param string  $rownumber
     * @return  boolean success
     */
    function setUploadedFromTblChangeRequest($key, $rownumber)
    {
        if (! isset($_FILES['fields_upload'])  || empty($_FILES['fields_upload']['name']['multi_edit'][$rownumber][$key])) {
            return false;
        }
        $file = PMA_File::fetchUploadedFromTblChangeRequestMultiple($_FILES['fields_upload'], $rownumber, $key);

        // for blobstreaming
        $is_bs_upload = false;

        // check if this field requires a repository upload
        if (isset($_REQUEST['upload_blob_repo']['multi_edit'][$rownumber][$key])) {
            $is_bs_upload = ($_REQUEST['upload_blob_repo']['multi_edit'][$rownumber][$key] == "on") ? true : false;
        }
        // if request is an upload to the BLOB repository
        if ($is_bs_upload) {
            $bs_db = $_REQUEST['db'];
            $bs_table = $_REQUEST['table'];
            $tmp_filename = $file['tmp_name'];
            $tmp_file_type = $file['type'];

            if (! $tmp_file_type) {
                $tmp_file_type = null;
            }

            if (! $bs_db || ! $bs_table) {
                $this->_error_message = __('Unknown error while uploading.');
                return false;
            }
            $blob_url =  PMA_BS_UpLoadFile($bs_db, $bs_table, $tmp_file_type, $tmp_filename);
            PMA_File::setRecentBLOBReference($blob_url);
         }   // end if ($is_bs_upload)

        // check for file upload errors
        switch ($file['error']) {
            // we do not use the PHP constants here cause not all constants
            // are defined in all versions of PHP - but the correct constants names
            // are given as comment
            case 0: //UPLOAD_ERR_OK:
                return $this->setUploadedFile($file['tmp_name']);
                break;
            case 4: //UPLOAD_ERR_NO_FILE:
                break;
            case 1: //UPLOAD_ERR_INI_SIZE:
                $this->_error_message = __('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
                break;
            case 2: //UPLOAD_ERR_FORM_SIZE:
                $this->_error_message = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
                break;
            case 3: //UPLOAD_ERR_PARTIAL:
                $this->_error_message = __('The uploaded file was only partially uploaded.');
                break;
            case 6: //UPLOAD_ERR_NO_TMP_DIR:
                $this->_error_message = __('Missing a temporary folder.');
                break;
            case 7: //UPLOAD_ERR_CANT_WRITE:
                $this->_error_message = __('Failed to write file to disk.');
                break;
            case 8: //UPLOAD_ERR_EXTENSION:
                $this->_error_message = __('File upload stopped by extension.');
                break;
            default:
                $this->_error_message = __('Unknown error in file upload.');
        } // end switch

        return false;
    }

    /**
     * strips some dimension from the multi-dimensional array from $_FILES
     *
     * <code>
     * $file['name']['multi_edit'][$rownumber][$key] = [value]
     * $file['type']['multi_edit'][$rownumber][$key] = [value]
     * $file['size']['multi_edit'][$rownumber][$key] = [value]
     * $file['tmp_name']['multi_edit'][$rownumber][$key] = [value]
     * $file['error']['multi_edit'][$rownumber][$key] = [value]
     *
     * // becomes:
     *
     * $file['name'] = [value]
     * $file['type'] = [value]
     * $file['size'] = [value]
     * $file['tmp_name'] = [value]
     * $file['error'] = [value]
     * </code>
     *
     * @access  public
     * @static
     * @param array   $file       the array
     * @param string  $rownumber
     * @param string  $key
     * @return  array
     */
    function fetchUploadedFromTblChangeRequestMultiple($file, $rownumber, $key)
    {
        $new_file = array(
            'name' => $file['name']['multi_edit'][$rownumber][$key],
            'type' => $file['type']['multi_edit'][$rownumber][$key],
            'size' => $file['size']['multi_edit'][$rownumber][$key],
            'tmp_name' => $file['tmp_name']['multi_edit'][$rownumber][$key],
            'error' => $file['error']['multi_edit'][$rownumber][$key],
        );

        return $new_file;
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
     * @access  public
     * @param string  $key the md5 hash of the column name
     * @param string  $rownumber
     * @return  boolean success
     */
    function setSelectedFromTblChangeRequest($key, $rownumber = null)
    {
        if (! empty($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
         && is_string($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])) {
            // ... whether with multiple rows ...
            // for blobstreaming
            $is_bs_upload = false;

            // check if this field requires a repository upload
            if (isset($_REQUEST['upload_blob_repo']['multi_edit'][$rownumber][$key])) {
                $is_bs_upload = ($_REQUEST['upload_blob_repo']['multi_edit'][$rownumber][$key] == "on") ? true : false;
            }

            // is a request to upload file to BLOB repository using uploadDir mechanism
            if ($is_bs_upload) {
                $bs_db = $_REQUEST['db'];
                $bs_table = $_REQUEST['table'];
                $tmp_filename = $GLOBALS['cfg']['UploadDir'] . '/' . $_REQUEST['fields_uploadlocal_' . $key]['multi_edit'][$rownumber];

                // check if fileinfo library exists
                if ($PMA_Config->get('FILEINFO_EXISTS')) {
                // attempt to init fileinfo
                    $finfo = finfo_open(FILEINFO_MIME);

                    // fileinfo exists
                    if ($finfo) {
                        // pass in filename to fileinfo and close fileinfo handle after
                        $tmp_file_type = finfo_file($finfo, $tmp_filename);
                        finfo_close($finfo);
                    }
                } else {
                    // no fileinfo library exists, use file command
                    $tmp_file_type = exec("file -bi " . escapeshellarg($tmp_filename));
                }

                if (! $tmp_file_type) {
                    $tmp_file_type = null;
                }

                if (! $bs_db || !$bs_table) {
                    $this->_error_message = __('Unknown error while uploading.');
                    return false;
                }
                $blob_url = PMA_BS_UpLoadFile($bs_db, $bs_table, $tmp_file_type, $tmp_filename);
                PMA_File::setRecentBLOBReference($blob_url);
            }   // end if ($is_bs_upload)

            return $this->setLocalSelectedFile($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key]);
        } else {
            return false;
        }
    }

    /**
     * @access  public
     * @return  string  error message
     */
    function getError()
    {
        return $this->_error_message;
    }

    /**
     * @access  public
     * @return  boolean whether an error occured or not
     */
    function isError()
    {
        return ! empty($this->_error_message);
    }

    /**
     * checks the superglobals provided if the tbl_change form is submitted
     * and uses the submitted/selected file
     *
     * @access  public
     * @param string  $key the md5 hash of the column name
     * @param string  $rownumber
     * @return  boolean success
     */
    function checkTblChangeForm($key, $rownumber)
    {
        if ($this->setUploadedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->_error_message = '';
            return true;
        } elseif ($this->setSelectedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->_error_message = '';
            return true;
        }
        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     *
     * @access  public
     * @param string  $name
     * @return  boolean success
     */
    function setLocalSelectedFile($name)
    {
        if (empty($GLOBALS['cfg']['UploadDir'])) return false;

        $this->setName(PMA_userDir($GLOBALS['cfg']['UploadDir']) . PMA_securePath($name));
        if (! $this->isReadable()) {
            $this->_error_message = __('File could not be read');
            $this->setName(null);
            return false;
        }

        return true;
    }

    /**
     * @access  public
     * @return  boolean whether the file is readable or not
     */
    function isReadable()
    {
        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $is_readable = is_readable($this->getName());
        ob_end_clean();
        return $is_readable;
    }

    /**
     * If we are on a server with open_basedir, we must move the file
     * before opening it. The FAQ 1.11 explains how to create the "./tmp"
     * directory - if needed
     *
     * @todo move check of $cfg['TempDir'] into PMA_Config?
     * @access  public
     * @return  boolean whether uploaded fiel is fine or not
     */
    function checkUploadedFile()
    {
        if ($this->isReadable()) {
            return true;
        }

        if (empty($GLOBALS['cfg']['TempDir']) || ! is_writable($GLOBALS['cfg']['TempDir'])) {
            // cannot create directory or access, point user to FAQ 1.11
            $this->_error_message = __('Error moving the uploaded file, see [a@./Documentation.html#faq1_11@Documentation]FAQ 1.11[/a]');
            return false;
        }

        $new_file_to_upload = tempnam(realpath($GLOBALS['cfg']['TempDir']), basename($this->getName()));

        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $move_uploaded_file_result = move_uploaded_file($this->getName(), $new_file_to_upload);
        ob_end_clean();
        if (! $move_uploaded_file_result) {
            $this->_error_message = __('Error while moving uploaded file.');
            return false;
        }

        $this->setName($new_file_to_upload);
        $this->isTemp(true);

        if (! $this->isReadable()) {
            $this->_error_message = __('Cannot read (moved) upload file.');
            return false;
        }

        return true;
    }

    /**
     * Detects what compression filse uses
     *
     * @todo    move file read part into readChunk() or getChunk()
     * @todo    add support for compression plugins
     * @access  protected
     * @return  string MIME type of compression, none for none
     */
    function _detectCompression()
    {
        // suppress warnings from being displayed, but not from being logged
        // f.e. any file access outside of open_basedir will issue a warning
        ob_start();
        $file = fopen($this->getName(), 'rb');
        ob_end_clean();

        if (! $file) {
            $this->_error_message = __('File could not be read');
            return false;
        }

        /**
         * @todo
         * get registered plugins for file compression

        foreach (PMA_getPlugins($type = 'compression') as $plugin) {
            if (call_user_func_array(array($plugin['classname'], 'canHandle'), array($this->getName()))) {
                $this->setCompressionPlugin($plugin);
                break;
            }
        }
         */

        $test = fread($file, 4);
        $len = strlen($test);
        fclose($file);

        if ($len >= 2 && $test[0] == chr(31) && $test[1] == chr(139)) {
            $this->_compression = 'application/gzip';
        } elseif ($len >= 3 && substr($test, 0, 3) == 'BZh') {
            $this->_compression = 'application/bzip2';
        } elseif ($len >= 4 && $test == "PK\003\004") {
            $this->_compression = 'application/zip';
        } else {
            $this->_compression = 'none';
        }

        return $this->_compression;
    }

    /**
     * whether the content should be decompressed before returned
     */
    function setDecompressContent($decompress)
    {
        $this->_decompress = (bool) $decompress;
    }

    function getHandle()
    {
        if (null === $this->_handle) {
            $this->open();
        }
        return $this->_handle;
    }

    function setHandle($handle)
    {
        $this->_handle = $handle;
    }

    /**
     * @return bool
     */
    function open()
    {
        if (! $this->_decompress) {
            $this->_handle = @fopen($this->getName(), 'r');
        }

        switch ($this->getCompression()) {
            case false:
                return false;
            case 'application/bzip2':
                if ($GLOBALS['cfg']['BZipDump'] && @function_exists('bzopen')) {
                    $this->_handle = @bzopen($this->getName(), 'r');
                } else {
                    $this->_error_message = sprintf(__('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.'), $this->getCompression());
                    return false;
                }
                break;
            case 'application/gzip':
                if ($GLOBALS['cfg']['GZipDump'] && @function_exists('gzopen')) {
                    $this->_handle = @gzopen($this->getName(), 'r');
                } else {
                    $this->_error_message = sprintf(__('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.'), $this->getCompression());
                    return false;
                }
                break;
            case 'application/zip':
                if ($GLOBALS['cfg']['ZipDump'] && @function_exists('zip_open')) {
                    include_once './libraries/zip_extension.lib.php';
                    $result = PMA_getZipContents($this->getName());
                    if (! empty($result['error'])) {
                        $this->_error_message = PMA_Message::rawError($result['error']);
                        return false;
                    } else {
                        $this->content_uncompressed = $result['data'];
                    }
                    unset($result);
                } else {
                    $this->_error_message = sprintf(__('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.'), $this->getCompression());
                    return false;
                }
                break;
            case 'none':
                $this->_handle = @fopen($this->getName(), 'r');
                break;
            default:
                $this->_error_message = sprintf(__('You attempted to load file with unsupported compression (%s). Either support for it is not implemented or disabled by your configuration.'), $this->getCompression());
                return false;
                break;
        }

        return true;
    }

    function getCharset()
    {
        return $this->_charset;
    }

    function setCharset($charset)
    {
        $this->_charset = $charset;
    }

    /**
     * @return  string MIME type of compression, none for none
     * @access  public
     */
    function getCompression()
    {
        if (null === $this->_compression) {
            return $this->_detectCompression();
        }

        return $this->_compression;
    }

    /**
     * advances the file pointer in the file handle by $length bytes/chars
     *
     * @param integer $length numbers of chars/bytes to skip
     * @return  boolean
     * @todo this function is unused
     */
    function advanceFilePointer($length)
    {
        while ($length > 0) {
            $this->getNextChunk($length);
            $length -= $this->getChunkSize();
        }
    }

    /**
     * http://bugs.php.net/bug.php?id=29532
     * bzip reads a maximum of 8192 bytes on windows systems
     * @todo this function is unused
     * @param int $max_size
     * @return bool|string
     */
    function getNextChunk($max_size = null)
    {
        if (null !== $max_size) {
            $size = min($max_size, $this->getChunkSize());
        } else {
            $size = $this->getChunkSize();
        }

        // $result = $this->handler->getNextChunk($size);
        $result = '';
        switch ($this->getCompression()) {
            case 'application/bzip2':
                $result = '';
                while (strlen($result) < $size - 8192 && ! feof($this->getHandle())) {
                    $result .= bzread($this->getHandle(), $size);
                }
                break;
            case 'application/gzip':
                $result = gzread($this->getHandle(), $size);
                break;
            case 'application/zip':
                /*
                 * if getNextChunk() is used some day,
                 * replace this code by code similar to the one
                 * in open()
                 *
                include_once './libraries/unzip.lib.php';
                $import_handle = new SimpleUnzip();
                $import_handle->ReadFile($this->getName());
                if ($import_handle->Count() == 0) {
                    $this->_error_message = __('No files found inside ZIP archive!');
                    return false;
                } elseif ($import_handle->GetError(0) != 0) {
                    $this->_error_message = __('Error in ZIP archive:')
                        . ' ' . $import_handle->GetErrorMsg(0);
                    return false;
                } else {
                    $result = $import_handle->GetData(0);
                }
                 */
                break;
            case 'none':
                $result = fread($this->getHandle(), $size);
                break;
            default:
                return false;
        }

        if ($GLOBALS['charset_conversion']) {
            $result = PMA_convert_string($this->getCharset(), 'utf-8', $result);
        } else {
            /**
             * Skip possible byte order marks (I do not think we need more
             * charsets, but feel free to add more, you can use wikipedia for
             * reference: <http://en.wikipedia.org/wiki/Byte_Order_Mark>)
             *
             * @todo BOM could be used for charset autodetection
             */
            if ($this->getOffset() === 0) {
                // UTF-8
                if (strncmp($result, "\xEF\xBB\xBF", 3) == 0) {
                    $result = substr($result, 3);
                // UTF-16 BE, LE
                } elseif (strncmp($result, "\xFE\xFF", 2) == 0
                 || strncmp($result, "\xFF\xFE", 2) == 0) {
                    $result = substr($result, 2);
                }
            }
        }

        $this->_offset += $size;
        if (0 === $result) {
            return true;
        }
        return $result;
    }

    function getOffset()
    {
        return $this->_offset;
    }

    function getChunkSize()
    {
        return $this->_chunk_size;
    }

    function setChunkSize($chunk_size)
    {
        $this->_chunk_size = (int) $chunk_size;
    }

    function getContentLength()
    {
        return strlen($this->_content);
    }

    function eof()
    {
        if ($this->getHandle()) {
            return feof($this->getHandle());
        } else {
            return ($this->getOffset() >= $this->getContentLength());
        }

    }

    /**
     * sets reference to most recent BLOB repository reference
     *
     * @access  public
     * @param string - BLOB repository reference
    */
    static function setRecentBLOBReference($ref)
    {
        PMA_File::$_recent_bs_reference = $ref;
    }

    /**
     * retrieves reference to most recent BLOB repository reference
     *
     * @access  public
     * @return  string - most recent BLOB repository reference
    */
    static function getRecentBLOBReference()
    {
        $ref = PMA_File::$_recent_bs_reference;
        PMA_File::$_recent_bs_reference = null;

        return $ref;
    }
}
?>
