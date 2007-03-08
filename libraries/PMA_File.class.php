<?php
/**
 * file upload functions
 *
 */

/**
 *
 * @todo replace error messages with localized string
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
     * old PHP 4 style constructor
     *
     * @see     PMA_File::__construct()
     * @uses    PMA_File::__construct()
     * @access  public
     */
    function PMA_File($name = false)
    {
        $this->__construct($name);
    }

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
     * @uses    PMA_File::cleanUp()
     */
    function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes temp file from upload
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
    function getContent()
    {
        if (null !== $this->_content) {
            return $this->_content;
        }

        if ($this->isUploaded() && ! $this->checkUploadedFile()) {
            return false;
        }

        if (! $this->isReadable()) {
            return false;
        }

        // check if file is not empty
        if (function_exists('file_get_contents')) {
            $this->_content = file_get_contents($this->getName());
        } elseif ($size = filesize($this->getName())) {
            $this->_content = fread(fopen($this->getName(), 'rb'), $size);
        }

        if (! empty($this->_content)) {
            $this->_content = '0x' . bin2hex($this->_content);
        }

        return $this->_content;
    }

    /**
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
     * @uses    PMA_File::$name as return value
     * @return  string  PMA_File::$_name
     */
    function getName()
    {
        return $this->_name;
    }

    /**
     * @todo replace error message with localized string
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
            //'error' => $file['error']['multi_edit'][$primary],
        );

        // ['error'] exists since PHP 4.2.0
        if (isset($file['error'])) {
            $new_file['error'] = $file['error']['multi_edit'][$primary];
        }

        return $new_file;
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
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
     * @uses    PMA_File->$_error_message as return value
     * @return  string  error message
     */
    function getError()
    {
        return $this->_error_message;
    }

    /**
     * @uses    PMA_File->$_error_message to check it
     * @return  boolean whether an error occured or not
     */
    function isError()
    {
        return ! empty($this->_error_message);
    }

    /**
     * chacks the supergloabls provided if the tbl_change form is submitted
     * and uses the submitted/selected file
     *
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
            return true;
        } elseif ($this->setUploadedFromTblChangeRequest($key)) {
            // well done ...
            return true;
        } elseif ($this->setSelectedFromTblChangeRequest($key, $primary_key)) {
            // well done ...
            return true;
        } elseif ($this->setSelectedFromTblChangeRequest($key)) {
            // well done ...
            return true;
        }
        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     *
     * @uses    PMA_File::setName()
     * @uses    preg_replace()
     * @uses    PMA_userDir()
     * @uses    $GLOBALS['cfg']['UploadDir']
     * @param   string  $name
     */
    function setLocalSelectedFile($name)
    {
        $this->setName(PMA_userDir($GLOBALS['cfg']['UploadDir']) . preg_replace('@\.\.*@', '.', $name));
    }

    /**
     * @uses    PMA_File::getName()
     * @uses    is_readable()
     * @uses    ob_start()
     * @uses    ob_end_clean()
     * @return  boolean whether the file is readable or not
     */
    function isReadable()
    {
        // surprees warnings form beeing displayed, but not from beeing logged
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

        // surprees warnings form beeing displayed, but not from beeing logged
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

        // surprees warnings form beeing displayed, but not from beeing logged
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
}
?>