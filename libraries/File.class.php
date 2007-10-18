<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * file upload functions
 *
 * @version $Id$
 */

/**
 *
 * @todo replace error messages with localized string
 * @todo when uploading a file into a blob field, should we also consider using
 *       chunks like in import? UPDATE `table` SET `field` = `field` + [chunk]
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
     * constructor
     *
     * @access  public
     * @uses    PMA_File::setName()
     * @param   string  $name   file name
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
     * @uses    PMA_File::cleanUp()
     */
    function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes file if it is temporary, usally from a moved upload file
     *
     * @access  public
     * @uses    PMA_File::delet()
     * @uses    PMA_File::isTemp()
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
     * @uses    PMA_File::getName()
     * @uses    unlink()
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
     * @uses    PMA_File::$_is_temp to set and read it
     * @param   boolean sets the temp flag
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
     * @uses    PMA_File::$_name
     * @param   string  $name   file name
     * @access  public
     */
    function setName($name)
    {
        $this->_name = trim($name);
    }

    /**
     * @access  public
     * @uses    PMA_File::getName()
     * @uses    PMA_File::isUploaded()
     * @uses    PMA_File::checkUploadedFile()
     * @uses    PMA_File::isReadable()
     * @uses    PMA_File::$_content
     * @uses    function_exists()
     * @uses    file_get_contents()
     * @uses    filesize()
     * @uses    fread()
     * @uses    fopen()
     * @uses    bin2hex()
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
     * @uses    PMA_File::getName()
     * @uses    is_uploaded_file()
     */
    function isUploaded()
    {
        return is_uploaded_file($this->getName());
    }

    /**
     * accessor
     *
     * @access  public
     * @uses    PMA_File::$name as return value
     * @return  string  PMA_File::$_name
     */
    function getName()
    {
        return $this->_name;
    }

    /**
     * @todo replace error message with localized string
     * @access  public
     * @uses    PMA_File::isUploaded()
     * @uses    PMA_File::setName()
     * @uses    PMA_File::$_error_message
     * @param   string  name of file uploaded
     * @return  boolean success
     */
    function setUploadedFile($name)
    {
        $this->setName($name);

        if (! $this->isUploaded()) {
            $this->setName(null);
            $this->_error_message = 'not an uploaded file';
            return false;
        }

        return true;
    }

    /**
     * @access  public
     * @uses    PMA_File::fetchUploadedFromTblChangeRequestMultiple()
     * @uses    PMA_File::setUploadedFile()
     * @uses    PMA_File::$_error_message
     * @uses    $GLOBALS['strUploadErrorIniSize']
     * @uses    $GLOBALS['strUploadErrorFormSize']
     * @uses    $GLOBALS['strUploadErrorPartial']
     * @uses    $GLOBALS['strUploadErrorNoTempDir']
     * @uses    $GLOBALS['strUploadErrorCantWrite']
     * @uses    $GLOBALS['strUploadErrorExtension']
     * @uses    $GLOBALS['strUploadErrorUnknown']
     * @uses    $_FILES
     * @param   string  $key    a numeric key used to identify the different rows
     * @param   string  $primary_key
     * @return  boolean success
     */
    function setUploadedFromTblChangeRequest($key, $primary = null)
    {
        if (! isset($_FILES['fields_upload_' . $key])) {
            return false;
        }

        $file = $_FILES['fields_upload_' . $key];
        if (null !== $primary) {
            $file = PMA_File::fetchUploadedFromTblChangeRequestMultiple($file, $primary);
        }

        // check for file upload errors
        switch ($file['error']) {
            // cybot_tm: we do not use the PHP constants here cause not all constants
            // are defined in all versions of PHP - but the correct constants names
            // are given as comment
            case 0: //UPLOAD_ERR_OK:
                return $this->setUploadedFile($file['tmp_name']);
                break;
            case 4: //UPLOAD_ERR_NO_FILE:
                break;
            case 1: //UPLOAD_ERR_INI_SIZE:
                $this->_error_message = $GLOBALS['strUploadErrorIniSize'];
                break;
            case 2: //UPLOAD_ERR_FORM_SIZE:
                $this->_error_message = $GLOBALS['strUploadErrorFormSize'];
                break;
            case 3: //UPLOAD_ERR_PARTIAL:
                $this->_error_message = $GLOBALS['strUploadErrorPartial'];
                break;
            case 6: //UPLOAD_ERR_NO_TMP_DIR:
                $this->_error_message = $GLOBALS['strUploadErrorNoTempDir'];
                break;
            case 7: //UPLOAD_ERR_CANT_WRITE:
                $this->_error_message = $GLOBALS['strUploadErrorCantWrite'];
                break;
            case 8: //UPLOAD_ERR_EXTENSION:
                $this->_error_message = $GLOBALS['strUploadErrorExtension'];
                break;
            default:
                $this->_error_message = $GLOBALS['strUploadErrorUnknown'];
        } // end switch

        return false;
    }

    /**
     * strips some dimension from the multi-dimensional array from $_FILES
     *
     * <code>
     * $file['name']['multi_edit'][$primary] = [value]
     * $file['type']['multi_edit'][$primary] = [value]
     * $file['size']['multi_edit'][$primary] = [value]
     * $file['tmp_name']['multi_edit'][$primary] = [value]
     * $file['error']['multi_edit'][$primary] = [value]
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
     * @todo re-check if requirements changes to PHP >= 4.2.0
     * @access  public
     * @static
     * @param   array   $file       the array
     * @param   string  $primary
     * @return  array
     */
    function fetchUploadedFromTblChangeRequestMultiple($file, $primary)
    {
        $new_file = array(
            'name' => $file['name']['multi_edit'][$primary],
            'type' => $file['type']['multi_edit'][$primary],
            'size' => $file['size']['multi_edit'][$primary],
            'tmp_name' => $file['tmp_name']['multi_edit'][$primary],
            'error' => $file['error']['multi_edit'][$primary],
        );

        return $new_file;
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
     * @access  public
     * @uses    $_REQUEST
     * @uses    PMA_File::setLocalSelectedFile()
     * @uses    is_string()
     * @param   string  $key    a numeric key used to identify the different rows
     * @param   string  $primary_key
     * @return  boolean success
     */
    function setSelectedFromTblChangeRequest($key, $primary = null)
    {
        if (null !== $primary) {
            if (! empty($_REQUEST['fields_uploadlocal_' . $key]['multi_edit'][$primary])
             && is_string($_REQUEST['fields_uploadlocal_' . $key]['multi_edit'][$primary])) {
                // ... whether with multiple rows ...
                return $this->setLocalSelectedFile($_REQUEST['fields_uploadlocal_' . $key]['multi_edit'][$primary]);
            } else {
                return false;
            }
        } elseif (! empty($_REQUEST['fields_uploadlocal_' . $key])
         && is_string($_REQUEST['fields_uploadlocal_' . $key])) {
            return $this->setLocalSelectedFile($_REQUEST['fields_uploadlocal_' . $key]);
        }

         return false;
    }

    /**
     * @access  public
     * @uses    PMA_File->$_error_message as return value
     * @return  string  error message
     */
    function getError()
    {
        return $this->_error_message;
    }

    /**
     * @access  public
     * @uses    PMA_File->$_error_message to check it
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
     * @uses    PMA_File::setUploadedFromTblChangeRequest()
     * @uses    PMA_File::setSelectedFromTblChangeRequest()
     * @param   string  $key    a numeric key used to identify the different rows
     * @param   string  $primary_key
     * @return  boolean success
     */
    function checkTblChangeForm($key, $primary_key)
    {
        if ($this->setUploadedFromTblChangeRequest($key, $primary_key)) {
            // well done ...
            $this->_error_message = '';
            return true;
/*
        } elseif ($this->setUploadedFromTblChangeRequest($key)) {
            // well done ...
            $this->_error_message = '';
            return true;
*/
        } elseif ($this->setSelectedFromTblChangeRequest($key, $primary_key)) {
            // well done ...
            $this->_error_message = '';
            return true;
/*
        } elseif ($this->setSelectedFromTblChangeRequest($key)) {
            // well done ...
            $this->_error_message = '';
            return true;
*/
        }
        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     *
     * @access  public
     * @uses    $GLOBALS['strFileCouldNotBeRead']
     * @uses    PMA_File::setName()
     * @uses    PMA_securePath()
     * @uses    PMA_userDir()
     * @uses    $GLOBALS['cfg']['UploadDir']
     * @param   string  $name
     * @return  boolean success
     */
    function setLocalSelectedFile($name)
    {
        $this->setName(PMA_userDir($GLOBALS['cfg']['UploadDir']) . PMA_securePath($name));
        if (! $this->isReadable()) {
            $this->_error_message = $GLOBALS['strFileCouldNotBeRead'];
            $this->setName(null);
            return false;
        }

        return true;
    }

    /**
     * @access  public
     * @uses    PMA_File::getName()
     * @uses    is_readable()
     * @uses    ob_start()
     * @uses    ob_end_clean()
     * @return  boolean whether the file is readable or not
     */
    function isReadable()
    {
        // surpress warnings from beeing displayed, but not from beeing logged
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
     * @todo replace error message with localized string
     * @todo move check of $cfg['TempDir'] into PMA_Config?
     * @access  public
     * @uses    $cfg['TempDir']
     * @uses    $GLOBALS['strFieldInsertFromFileTempDirNotExists']
     * @uses    PMA_File::isReadable()
     * @uses    PMA_File::getName()
     * @uses    PMA_File::setName()
     * @uses    PMA_File::isTemp()
     * @uses    PMA_File::$_error_message
     * @uses    is_dir()
     * @uses    mkdir()
     * @uses    chmod()
     * @uses    is_writable()
     * @uses    basename()
     * @uses    move_uploaded_file()
     * @uses    ob_start()
     * @uses    ob_end_clean()
     * @return  boolean whether uploaded fiel is fine or not
     */
    function checkUploadedFile()
    {
        if ($this->isReadable()) {
            return true;
        }

        /**
         * it is not important if open_basedir is set - we just cannot read the file
         * so we try to move it
        if ('' != ini_get('open_basedir')) {
         */

        // check tmp dir config
        if (empty($GLOBALS['cfg']['TempDir'])) {
            $GLOBALS['cfg']['TempDir'] = 'tmp/';
        }

        // surpress warnings from beeing displayed, but not from beeing logged
        ob_start();
        // check tmp dir
        if (! is_dir($GLOBALS['cfg']['TempDir'])) {
            // try to create the tmp directory
            if (@mkdir($GLOBALS['cfg']['TempDir'], 0777)) {
                chmod($GLOBALS['cfg']['TempDir'], 0777);
            } else {
                // create tmp dir failed
                $this->_error_message = $GLOBALS['strFieldInsertFromFileTempDirNotExists'];
                ob_end_clean();
                return false;
            }
        }
        ob_end_clean();

        if (! is_writable($GLOBALS['cfg']['TempDir'])) {
            // cannot create directory or access, point user to FAQ 1.11
            $this->_error_message = $GLOBALS['strFieldInsertFromFileTempDirNotExists'];
            return false;
        }

        $new_file_to_upload = $GLOBALS['cfg']['TempDir'] . '/' . basename($this->getName());

        // surpress warnings from beeing displayed, but not from beeing logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $move_uploaded_file_result = move_uploaded_file($this->getName(), $new_file_to_upload);
        ob_end_clean();
        if (! $move_uploaded_file_result) {
            $this->_error_message = 'error while moving uploaded file';
            return false;
        }

        $this->setName($new_file_to_upload);
        $this->isTemp(true);

        if (! $this->isReadable()) {
            $this->_error_message = 'cannot read (moved) upload file';
            return false;
        }

        return true;
    }

    /**
     * Detects what compression filse uses
     *
     * @todo    move file read part into readChunk() or getChunk()
     * @todo    add support for compression plugins
     * @uses    $GLOBALS['strFileCouldNotBeRead']
     * @uses    PMA_File::$_compression to set it
     * @uses    PMA_File::getName()
     * @uses    fopen()
     * @uses    fread()
     * @uses    strlen()
     * @uses    fclose()
     * @uses    chr()
     * @uses    substr()
     * @access  protected
     * @return  string MIME type of compression, none for none
     */
    function _detectCompression()
    {
        // surpress warnings from beeing displayed, but not from beeing logged
        // f.e. any file access outside of open_basedir will issue a warning
        ob_start();
        $file = fopen($this->getName(), 'rb');
        ob_end_clean();

        if (! $file) {
            $this->_error_message = $GLOBALS['strFileCouldNotBeRead'];
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
     *
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
                    $this->_error_message = sprintf($GLOBALS['strUnsupportedCompressionDetected'], $this->getCompression());
                    return false;
                }
                break;
            case 'application/gzip':
                if ($GLOBALS['cfg']['GZipDump'] && @function_exists('gzopen')) {
                    $this->_handle = @gzopen($this->getName(), 'r');
                } else {
                    $this->_error_message = sprintf($GLOBALS['strUnsupportedCompressionDetected'], $this->getCompression());
                    return false;
                }
                break;
            case 'application/zip':
                if ($GLOBALS['cfg']['GZipDump'] && @function_exists('gzinflate')) {
                    include_once './libraries/unzip.lib.php';
                    $this->_handle = new SimpleUnzip();
                    $this->_handle->ReadFile($this->getName());
                    if ($this->_handle->Count() == 0) {
                        $this->_error_message = $GLOBALS['strNoFilesFoundInZip'];
                        return false;
                    } elseif ($this->_handle->GetError(0) != 0) {
                        $this->_error_message = $GLOBALS['strErrorInZipFile'] . ' ' . $this->_handle->GetErrorMsg(0);
                        return false;
                    } else {
                        $this->content_uncompressed = $this->_handle->GetData(0);
                    }
                    // We don't need to store it further
                    $this->_handle = null;
                } else {
                    $this->_error_message = sprintf($GLOBALS['strUnsupportedCompressionDetected'], $this->getCompression());
                    return false;
                }
                break;
            case 'none':
                $this->_handle = @fopen($this->getName(), 'r');
                break;
            default:
                $this->_error_message = sprintf($GLOBALS['strUnsupportedCompressionDetected'], $this->getCompression());
                return false;
                break;
        }


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
     * @uses    PMA_File::$_compression as return value
     * @uses    PMA_File::detectCompression()
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
     * @param   integer $length numbers of chars/bytes to skip
     * @return  boolean
     */
    function advanceFilePointer($length)
    {
        while ($length > 0) {
            // Disable read progresivity, otherwise we eat all memory!
            $read_multiply = 1; // required?
            $this->getNextChunk($length);
            $length -= $this->getChunkSize();
        }
    }

    /**
     * http://bugs.php.net/bug.php?id=29532
     * bzip reads a maximum of 8192 bytes on windows systems
     *
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
                include_once './libraries/unzip.lib.php';
                $import_handle = new SimpleUnzip();
                $import_handle->ReadFile($this->getName());
                if ($import_handle->Count() == 0) {
                    $this->_error_message = $GLOBALS['strNoFilesFoundInZip'];
                    return false;
                } elseif ($import_handle->GetError(0) != 0) {
                    $this->_error_message = $GLOBALS['strErrorInZipFile']
                        . ' ' . $import_handle->GetErrorMsg(0);
                    return false;
                } else {
                    $result = $import_handle->GetData(0);
                }
                break;
            case 'none':
                $result = fread($this->getHandle(), $size);
                break;
            default:
                return false;
        }

        echo $size . ' - ';
        echo strlen($result) . ' - ';
        echo (@$GLOBALS['__len__'] += strlen($result)) . ' - ';
        echo $this->_error_message;
        echo '<hr />';

        if ($GLOBALS['charset_conversion']) {
            $result = PMA_convert_string($this->getCharset(), $GLOBALS['charset'], $result);
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
}
?>
