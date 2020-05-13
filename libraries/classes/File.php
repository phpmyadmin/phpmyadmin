<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;
use function basename;
use function bin2hex;
use function bzopen;
use function bzread;
use function extension_loaded;
use function fclose;
use function feof;
use function file_get_contents;
use function filesize;
use function fopen;
use function fread;
use function function_exists;
use function gzopen;
use function gzread;
use function is_link;
use function is_readable;
use function is_string;
use function is_uploaded_file;
use function mb_strcut;
use function move_uploaded_file;
use function ob_end_clean;
use function ob_start;
use function sprintf;
use function strlen;
use function tempnam;
use function trim;
use function unlink;

/**
 * File wrapper class
 *
 * @todo when uploading a file into a blob field, should we also consider using
 *       chunks like in import? UPDATE `table` SET `field` = `field` + [chunk]
 */
class File
{
    /**
     * @var string the temporary file name
     * @access protected
     */
    protected $_name = null;

    /**
     * @var string the content
     * @access protected
     */
    protected $_content = null;

    /**
     * @var Message|null the error message
     * @access protected
     */
    protected $_error_message = null;

    /**
     * @var bool whether the file is temporary or not
     * @access protected
     */
    protected $_is_temp = false;

    /**
     * @var string type of compression
     * @access protected
     */
    protected $_compression = null;

    /** @var int */
    protected $_offset = 0;

    /** @var int size of chunk to read with every step */
    protected $_chunk_size = 32768;

    /** @var resource|null file handle */
    protected $_handle = null;

    /** @var bool whether to decompress content before returning */
    protected $_decompress = false;

    /** @var string charset of file */
    protected $_charset = null;

    /** @var ZipExtension */
    private $zipExtension;

    /**
     * @param bool|string $name file name or false
     *
     * @access public
     */
    public function __construct($name = false)
    {
        if ($name && is_string($name)) {
            $this->setName($name);
        }

        if (extension_loaded('zip')) {
            $this->zipExtension = new ZipExtension();
        }
    }

    /**
     * destructor
     *
     * @see     File::cleanUp()
     *
     * @access public
     */
    public function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes file if it is temporary, usually from a moved upload file
     *
     * @return bool success
     *
     * @access public
     */
    public function cleanUp(): bool
    {
        if ($this->isTemp()) {
            return $this->delete();
        }

        return true;
    }

    /**
     * deletes the file
     *
     * @return bool success
     *
     * @access public
     */
    public function delete(): bool
    {
        return unlink($this->getName());
    }

    /**
     * checks or sets the temp flag for this file
     * file objects with temp flags are deleted with object destruction
     *
     * @param bool $is_temp sets the temp flag
     *
     * @return bool File::$_is_temp
     *
     * @access public
     */
    public function isTemp(?bool $is_temp = null): bool
    {
        if ($is_temp !== null) {
            $this->_is_temp = $is_temp;
        }

        return $this->_is_temp;
    }

    /**
     * accessor
     *
     * @param string|null $name file name
     *
     * @access public
     */
    public function setName(?string $name): void
    {
        $this->_name = trim($name);
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content,
     *                      or false if no content
     *
     * @access public
     */
    public function getRawContent()
    {
        if ($this->_content === null) {
            if ($this->isUploaded() && ! $this->checkUploadedFile()) {
                return false;
            }

            if (! $this->isReadable()) {
                return false;
            }

            if (function_exists('file_get_contents')) {
                $this->_content = file_get_contents($this->getName());
            } elseif ($size = filesize($this->getName())) {
                $handle = fopen($this->getName(), 'rb');
                $this->_content = fread($handle, $size);
                fclose($handle);
            }
        }

        return $this->_content;
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content as a string,
     *                      or false if no content
     *
     * @access public
     */
    public function getContent()
    {
        $result = $this->getRawContent();
        if ($result === false) {
            return false;
        }

        return '0x' . bin2hex($result);
    }

    /**
     * Whether file is uploaded.
     *
     * @access public
     */
    public function isUploaded(): bool
    {
        if ($this->getName() === null) {
            return false;
        } else {
            return is_uploaded_file($this->getName());
        }
    }

    /**
     * accessor
     *
     * @return string|null File::$_name
     *
     * @access public
     */
    public function getName(): ?string
    {
        return $this->_name;
    }

    /**
     * Initializes object from uploaded file.
     *
     * @param string $name name of file uploaded
     *
     * @return bool success
     *
     * @access public
     */
    public function setUploadedFile(string $name): bool
    {
        $this->setName($name);

        if (! $this->isUploaded()) {
            $this->setName(null);
            $this->_error_message = Message::error(__('File was not an uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Loads uploaded file from table change request.
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     *
     * @return bool success
     *
     * @access public
     */
    public function setUploadedFromTblChangeRequest(
        string $key,
        string $rownumber
    ): bool {
        if (! isset($_FILES['fields_upload'])
            || empty($_FILES['fields_upload']['name']['multi_edit'][$rownumber][$key])
        ) {
            return false;
        }
        $file = $this->fetchUploadedFromTblChangeRequestMultiple(
            $_FILES['fields_upload'],
            $rownumber,
            $key
        );

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                return $this->setUploadedFile($file['tmp_name']);
            case UPLOAD_ERR_NO_FILE:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $this->_error_message = Message::error(__(
                    'The uploaded file exceeds the upload_max_filesize directive in '
                    . 'php.ini.'
                ));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $this->_error_message = Message::error(__(
                    'The uploaded file exceeds the MAX_FILE_SIZE directive that was '
                    . 'specified in the HTML form.'
                ));
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->_error_message = Message::error(__(
                    'The uploaded file was only partially uploaded.'
                ));
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->_error_message = Message::error(__('Missing a temporary folder.'));
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $this->_error_message = Message::error(__('Failed to write file to disk.'));
                break;
            case UPLOAD_ERR_EXTENSION:
                $this->_error_message = Message::error(__('File upload stopped by extension.'));
                break;
            default:
                $this->_error_message = Message::error(__('Unknown error in file upload.'));
        }

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
     * @param array  $file      the array
     * @param string $rownumber number of row to process
     * @param string $key       key to process
     *
     * @return array
     *
     * @access public
     * @static
     */
    public function fetchUploadedFromTblChangeRequestMultiple(
        array $file,
        string $rownumber,
        string $key
    ): array {
        return [
            'name' => $file['name']['multi_edit'][$rownumber][$key],
            'type' => $file['type']['multi_edit'][$rownumber][$key],
            'size' => $file['size']['multi_edit'][$rownumber][$key],
            'tmp_name' => $file['tmp_name']['multi_edit'][$rownumber][$key],
            'error' => $file['error']['multi_edit'][$rownumber][$key],
        ];
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     *
     * @return bool success
     *
     * @access public
     */
    public function setSelectedFromTblChangeRequest(
        string $key,
        ?string $rownumber = null
    ): bool {
        if (! empty($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
            && is_string($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
        ) {
            // ... whether with multiple rows ...
            return $this->setLocalSelectedFile(
                $_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key]
            );
        }

        return false;
    }

    /**
     * Returns possible error message.
     *
     * @return Message|null error message
     *
     * @access public
     */
    public function getError(): ?Message
    {
        return $this->_error_message;
    }

    /**
     * Checks whether there was any error.
     *
     * @return bool whether an error occurred or not
     *
     * @access public
     */
    public function isError(): bool
    {
        return $this->_error_message !== null;
    }

    /**
     * checks the superglobals provided if the tbl_change form is submitted
     * and uses the submitted/selected file
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     *
     * @return bool success
     *
     * @access public
     */
    public function checkTblChangeForm(string $key, string $rownumber): bool
    {
        if ($this->setUploadedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->_error_message = null;

            return true;
        } elseif ($this->setSelectedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->_error_message = null;

            return true;
        }
        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     * Sets named file to be read from UploadDir.
     *
     * @param string $name file name
     *
     * @return bool success
     *
     * @access public
     */
    public function setLocalSelectedFile(string $name): bool
    {
        if (empty($GLOBALS['cfg']['UploadDir'])) {
            return false;
        }

        $this->setName(
            Util::userDir($GLOBALS['cfg']['UploadDir']) . Core::securePath($name)
        );
        if (@is_link($this->getName())) {
            $this->_error_message = Message::error(__('File is a symbolic link'));
            $this->setName(null);

            return false;
        }
        if (! $this->isReadable()) {
            $this->_error_message = Message::error(__('File could not be read!'));
            $this->setName(null);

            return false;
        }

        return true;
    }

    /**
     * Checks whether file can be read.
     *
     * @return bool whether the file is readable or not
     *
     * @access public
     */
    public function isReadable(): bool
    {
        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        return @is_readable((string) $this->getName());
    }

    /**
     * If we are on a server with open_basedir, we must move the file
     * before opening it. The FAQ 1.11 explains how to create the "./tmp"
     * directory - if needed
     *
     * @return bool whether uploaded file is fine or not
     *
     * @todo move check of $cfg['TempDir'] into Config?
     * @access public
     */
    public function checkUploadedFile(): bool
    {
        if ($this->isReadable()) {
            return true;
        }

        $tmp_subdir = $GLOBALS['PMA_Config']->getUploadTempDir();
        if ($tmp_subdir === null) {
            // cannot create directory or access, point user to FAQ 1.11
            $this->_error_message = Message::error(__(
                'Error moving the uploaded file, see [doc@faq1-11]FAQ 1.11[/doc].'
            ));

            return false;
        }

        $new_file_to_upload = tempnam(
            $tmp_subdir,
            basename($this->getName())
        );

        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $move_uploaded_file_result = move_uploaded_file(
            $this->getName(),
            $new_file_to_upload
        );
        ob_end_clean();
        if (! $move_uploaded_file_result) {
            $this->_error_message = Message::error(__('Error while moving uploaded file.'));

            return false;
        }

        $this->setName($new_file_to_upload);
        $this->isTemp(true);

        if (! $this->isReadable()) {
            $this->_error_message = Message::error(__('Cannot read uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Detects what compression the file uses
     *
     * @return string|false false on error, otherwise string MIME type of
     *                      compression, none for none
     *
     * @todo   move file read part into readChunk() or getChunk()
     * @todo   add support for compression plugins
     * @access protected
     */
    protected function detectCompression()
    {
        // suppress warnings from being displayed, but not from being logged
        // f.e. any file access outside of open_basedir will issue a warning
        ob_start();
        $file = fopen($this->getName(), 'rb');
        ob_end_clean();

        if (! $file) {
            $this->_error_message = Message::error(__('File could not be read!'));

            return false;
        }

        $this->_compression = Util::getCompressionMimeType($file);

        return $this->_compression;
    }

    /**
     * Sets whether the content should be decompressed before returned
     *
     * @param bool $decompress whether to decompress
     */
    public function setDecompressContent(bool $decompress): void
    {
        $this->_decompress = $decompress;
    }

    /**
     * Returns the file handle
     *
     * @return resource file handle
     */
    public function getHandle()
    {
        if ($this->_handle === null) {
            $this->open();
        }

        return $this->_handle;
    }

    /**
     * Sets the file handle
     *
     * @param resource $handle file handle
     */
    public function setHandle($handle): void
    {
        $this->_handle = $handle;
    }

    /**
     * Sets error message for unsupported compression.
     */
    public function errorUnsupported(): void
    {
        $this->_error_message = Message::error(sprintf(
            __(
                'You attempted to load file with unsupported compression (%s). '
                . 'Either support for it is not implemented or disabled by your '
                . 'configuration.'
            ),
            $this->getCompression()
        ));
    }

    /**
     * Attempts to open the file.
     */
    public function open(): bool
    {
        if (! $this->_decompress) {
            $this->_handle = @fopen($this->getName(), 'r');
        }

        switch ($this->getCompression()) {
            case false:
                return false;
            case 'application/bzip2':
                if ($GLOBALS['cfg']['BZipDump'] && function_exists('bzopen')) {
                    $this->_handle = @bzopen($this->getName(), 'r');
                } else {
                    $this->errorUnsupported();

                    return false;
                }
                break;
            case 'application/gzip':
                if ($GLOBALS['cfg']['GZipDump'] && function_exists('gzopen')) {
                    $this->_handle = @gzopen($this->getName(), 'r');
                } else {
                    $this->errorUnsupported();

                    return false;
                }
                break;
            case 'application/zip':
                if ($GLOBALS['cfg']['ZipDump'] && function_exists('zip_open')) {
                    return $this->openZip();
                }

                $this->errorUnsupported();

                return false;
            case 'none':
                $this->_handle = @fopen($this->getName(), 'r');
                break;
            default:
                $this->errorUnsupported();

                return false;
        }

        return $this->_handle !== false;
    }

    /**
     * Opens file from zip
     *
     * @param string|null $specific_entry Entry to open
     */
    public function openZip(?string $specific_entry = null): bool
    {
        $result = $this->zipExtension->getContents($this->getName(), $specific_entry);
        if (! empty($result['error'])) {
            $this->_error_message = Message::rawError($result['error']);

            return false;
        }
        $this->_content = $result['data'];
        $this->_offset = 0;

        return true;
    }

    /**
     * Checks whether we've reached end of file
     */
    public function eof(): bool
    {
        if ($this->_handle !== null) {
            return feof($this->_handle);
        }

        return $this->_offset == strlen($this->_content);
    }

    /**
     * Closes the file
     */
    public function close(): void
    {
        if ($this->_handle !== null) {
            fclose($this->_handle);
            $this->_handle = null;
        } else {
            $this->_content = '';
            $this->_offset = 0;
        }
        $this->cleanUp();
    }

    /**
     * Reads data from file
     *
     * @param int $size Number of bytes to read
     */
    public function read(int $size): string
    {
        switch ($this->_compression) {
            case 'application/bzip2':
                return bzread($this->_handle, $size);
            case 'application/gzip':
                return gzread($this->_handle, $size);
            case 'application/zip':
                $result = mb_strcut($this->_content, $this->_offset, $size);
                $this->_offset += strlen($result);

                return $result;
            case 'none':
            default:
                return fread($this->_handle, $size);
        }
    }

    /**
     * Returns the character set of the file
     *
     * @return string character set of the file
     */
    public function getCharset(): string
    {
        return $this->_charset;
    }

    /**
     * Sets the character set of the file
     *
     * @param string $charset character set of the file
     */
    public function setCharset(string $charset): void
    {
        $this->_charset = $charset;
    }

    /**
     * Returns compression used by file.
     *
     * @return string MIME type of compression, none for none
     *
     * @access public
     */
    public function getCompression(): string
    {
        if ($this->_compression === null) {
            return $this->detectCompression();
        }

        return $this->_compression;
    }

    /**
     * Returns the offset
     *
     * @return int the offset
     */
    public function getOffset(): int
    {
        return $this->_offset;
    }

    /**
     * Returns the chunk size
     *
     * @return int the chunk size
     */
    public function getChunkSize(): int
    {
        return $this->_chunk_size;
    }

    /**
     * Sets the chunk size
     *
     * @param int $chunk_size the chunk size
     */
    public function setChunkSize(int $chunk_size): void
    {
        $this->_chunk_size = $chunk_size;
    }

    /**
     * Returns the length of the content in the file
     *
     * @return int the length of the file content
     */
    public function getContentLength(): int
    {
        return strlen($this->_content);
    }
}
