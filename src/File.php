<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ZipArchive;

use function __;
use function basename;
use function bin2hex;
use function bzopen;
use function bzread;
use function extension_loaded;
use function fclose;
use function feof;
use function file_get_contents;
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

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

/**
 * File wrapper class
 *
 * @todo when uploading a file into a blob field, should we also consider using
 *       chunks like in import? UPDATE `table` SET `field` = `field` + [chunk]
 */
final class File
{
    /** @var string the temporary file name */
    private string $name = '';

    private string|null $content = null;

    private Message|null $errorMessage = null;

    private bool $isTemp = false;

    private string|null $compression = null;

    private int $offset = 0;

    /** @var resource|null file handle */
    private $handle;

    /** @var bool whether to decompress content before returning */
    private bool $decompress = false;

    private ZipExtension $zipExtension;
    private readonly Config $config;

    public function __construct(string|null $name = null)
    {
        $this->config = Config::getInstance();
        if ($name !== null && $name !== '') {
            $this->setName($name);
        }

        if (! extension_loaded('zip')) {
            return;
        }

        $this->zipExtension = new ZipExtension(new ZipArchive());
    }

    /**
     * destructor
     *
     * @see     File::cleanUp()
     */
    public function __destruct()
    {
        $this->cleanUp();
    }

    /**
     * deletes file if it is temporary, usually from a moved upload file
     */
    public function cleanUp(): bool
    {
        if ($this->isTemp) {
            return unlink($this->name);
        }

        return true;
    }

    private function setName(string|null $name): void
    {
        $this->name = trim((string) $name);
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content, or false if no content
     */
    public function getRawContent(): string|false
    {
        if ($this->content !== null) {
            return $this->content;
        }

        if ($this->isUploaded() && ! $this->checkUploadedFile()) {
            return false;
        }

        if (! $this->isReadable()) {
            return false;
        }

        $this->content = file_get_contents($this->name);

        return $this->content;
    }

    /**
     * Gets file content
     *
     * @return string|false the binary file content as a string,
     *                      or false if no content
     */
    public function getContent(): string|false
    {
        $result = $this->getRawContent();
        if ($result === false) {
            return false;
        }

        return '0x' . bin2hex($result);
    }

    /**
     * Whether file is uploaded.
     */
    public function isUploaded(): bool
    {
        if ($this->name === '') {
            return false;
        }

        return is_uploaded_file($this->name);
    }

    /**
     * Initializes object from uploaded file.
     *
     * @param string $name name of file uploaded
     */
    public function setUploadedFile(string $name): bool
    {
        $this->setName($name);

        if (! $this->isUploaded()) {
            $this->setName(null);
            $this->errorMessage = Message::error(__('File was not an uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Loads uploaded file from table change request.
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     */
    public function setUploadedFromTblChangeRequest(
        string $key,
        string $rownumber,
    ): bool {
        if (
            ! isset($_FILES['fields_upload'])
            || empty($_FILES['fields_upload']['name']['multi_edit'][$rownumber][$key])
        ) {
            return false;
        }

        $error = $_FILES['fields_upload']['error']['multi_edit'][$rownumber][$key];
        $tempName = $_FILES['fields_upload']['tmp_name']['multi_edit'][$rownumber][$key];

        switch ($error) {
            case UPLOAD_ERR_OK:
                return $this->setUploadedFile($tempName);

            case UPLOAD_ERR_NO_FILE:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $this->errorMessage = Message::error(__(
                    'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                ));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $this->errorMessage = Message::error(__(
                    'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                ));
                break;
            case UPLOAD_ERR_PARTIAL:
                $this->errorMessage = Message::error(__(
                    'The uploaded file was only partially uploaded.',
                ));
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $this->errorMessage = Message::error(__('Missing a temporary folder.'));
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $this->errorMessage = Message::error(__('Failed to write file to disk.'));
                break;
            case UPLOAD_ERR_EXTENSION:
                $this->errorMessage = Message::error(__('File upload stopped by extension.'));
                break;
            default:
                $this->errorMessage = Message::error(__('Unknown error in file upload.'));
        }

        return false;
    }

    /**
     * sets the name if the file to the one selected in the tbl_change form
     *
     * @param string      $key       the md5 hash of the column name
     * @param string|null $rownumber number of row to process
     */
    public function setSelectedFromTblChangeRequest(
        string $key,
        string|null $rownumber = null,
    ): bool {
        if (
            ! empty($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
            && is_string($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key])
        ) {
            // ... whether with multiple rows ...
            return $this->setLocalSelectedFile($_REQUEST['fields_uploadlocal']['multi_edit'][$rownumber][$key]);
        }

        return false;
    }

    /**
     * Returns possible error message.
     *
     * @return Message|null error message
     */
    public function getError(): Message|null
    {
        return $this->errorMessage;
    }

    /**
     * checks the superglobals provided if the tbl_change form is submitted
     * and uses the submitted/selected file
     *
     * @param string $key       the md5 hash of the column name
     * @param string $rownumber number of row to process
     */
    public function checkTblChangeForm(string $key, string $rownumber): bool
    {
        if ($this->setUploadedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->errorMessage = null;

            return true;
        }

        if ($this->setSelectedFromTblChangeRequest($key, $rownumber)) {
            // well done ...
            $this->errorMessage = null;

            return true;
        }

        // all failed, whether just no file uploaded/selected or an error

        return false;
    }

    /**
     * Sets named file to be read from UploadDir.
     *
     * @param string $name file name
     */
    public function setLocalSelectedFile(string $name): bool
    {
        if ($this->config->config->UploadDir === '') {
            return false;
        }

        $userDir = Util::userDir($this->config->selectedServer['user'], $this->config->config->UploadDir);
        $this->setName($userDir . Core::securePath($name));
        if (@is_link($this->name)) {
            $this->errorMessage = Message::error(__('File is a symbolic link'));
            $this->setName(null);

            return false;
        }

        if (! $this->isReadable()) {
            $this->errorMessage = Message::error(__('File could not be read!'));
            $this->setName(null);

            return false;
        }

        return true;
    }

    /**
     * Checks whether file can be read.
     */
    public function isReadable(): bool
    {
        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        return @is_readable($this->name);
    }

    /**
     * If we are on a server with open_basedir, we must move the file
     * before opening it. The FAQ 1.11 explains how to create the "./tmp"
     * directory - if needed
     *
     * @todo move check of $cfg['TempDir'] into Config?
     */
    public function checkUploadedFile(): bool
    {
        if ($this->isReadable()) {
            return true;
        }

        $tmpSubdir = $this->config->getUploadTempDir();
        if ($tmpSubdir === null) {
            // cannot create directory or access, point user to FAQ 1.11
            $this->errorMessage = Message::error(__(
                'Error moving the uploaded file, see [doc@faq1-11]FAQ 1.11[/doc].',
            ));

            return false;
        }

        $newFileToUpload = tempnam(
            $tmpSubdir,
            basename($this->name),
        );
        if ($newFileToUpload === false) {
            $this->errorMessage = Message::error(__('Cannot create temporary file.'));

            return false;
        }

        // suppress warnings from being displayed, but not from being logged
        // any file access outside of open_basedir will issue a warning
        ob_start();
        $moveUploadedFileResult = move_uploaded_file($this->name, $newFileToUpload);
        ob_end_clean();
        if (! $moveUploadedFileResult) {
            $this->errorMessage = Message::error(__('Error while moving uploaded file.'));

            return false;
        }

        $this->setName($newFileToUpload);
        $this->isTemp = true;

        if (! $this->isReadable()) {
            $this->errorMessage = Message::error(__('Cannot read uploaded file.'));

            return false;
        }

        return true;
    }

    /**
     * Detects what compression the file uses
     *
     * @return string empty string on error, otherwise string MIME type of
     *                      compression, none for none
     *
     * @todo   move file read part into readChunk() or getChunk()
     * @todo   add support for compression plugins
     */
    private function detectCompression(): string
    {
        // suppress warnings from being displayed, but not from being logged
        // f.e. any file access outside of open_basedir will issue a warning
        ob_start();
        $file = fopen($this->name, 'rb');
        ob_end_clean();

        if (! $file) {
            $this->errorMessage = Message::error(__('File could not be read!'));

            return '';
        }

        $this->compression = Util::getCompressionMimeType($file);

        return $this->compression;
    }

    /**
     * Sets whether the content should be decompressed before returned
     *
     * @param bool $decompress whether to decompress
     */
    public function setDecompressContent(bool $decompress): void
    {
        $this->decompress = $decompress;
    }

    /**
     * Sets error message for unsupported compression.
     */
    public function errorUnsupported(): void
    {
        $this->errorMessage = Message::error(sprintf(
            __(
                'You attempted to load file with unsupported compression (%s). '
                . 'Either support for it is not implemented or disabled by your '
                . 'configuration.',
            ),
            $this->getCompression(),
        ));
    }

    /**
     * Attempts to open the file.
     */
    public function open(): bool
    {
        if (! $this->decompress) {
            $this->handle = @fopen($this->name, 'r');
        }

        switch ($this->getCompression()) {
            case '':
                return false;

            case 'application/bzip2':
                if (! $this->config->config->BZipDump || ! function_exists('bzopen')) {
                    $this->errorUnsupported();

                    return false;
                }

                $this->handle = @bzopen($this->name, 'r');
                break;
            case 'application/gzip':
                if (! $this->config->config->GZipDump || ! function_exists('gzopen')) {
                    $this->errorUnsupported();

                    return false;
                }

                $this->handle = @gzopen($this->name, 'r');
                break;
            case 'application/zip':
                if ($this->config->config->ZipDump && function_exists('zip_open')) {
                    return $this->openZip();
                }

                $this->errorUnsupported();

                return false;

            case 'none':
                $this->handle = @fopen($this->name, 'r');
                break;
            default:
                $this->errorUnsupported();

                return false;
        }

        return $this->handle !== false;
    }

    /**
     * Opens file from zip
     *
     * @param string|null $specificEntry Entry to open
     */
    public function openZip(string|null $specificEntry = null): bool
    {
        $result = $this->zipExtension->getContents($this->name, $specificEntry);
        if ($result['error'] !== '') {
            $this->errorMessage = Message::rawError($result['error']);

            return false;
        }

        $this->content = $result['data'];
        $this->offset = 0;

        return true;
    }

    /**
     * Checks whether we've reached end of file
     */
    public function eof(): bool
    {
        if ($this->handle !== null) {
            return feof($this->handle);
        }

        return $this->offset === strlen($this->content ?? '');
    }

    /**
     * Closes the file
     */
    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        } else {
            $this->content = '';
            $this->offset = 0;
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
        if ($this->compression === 'application/zip') {
            $result = mb_strcut($this->content ?? '', $this->offset, $size);
            $this->offset += strlen($result);

            return $result;
        }

        if ($this->handle === null) {
            return '';
        }

        if ($this->compression === 'application/bzip2') {
            return (string) bzread($this->handle, $size);
        }

        if ($this->compression === 'application/gzip') {
            return (string) gzread($this->handle, $size);
        }

        return (string) fread($this->handle, $size);
    }

    /**
     * Returns compression used by file.
     *
     * @return string MIME type of compression, none for none
     */
    public function getCompression(): string
    {
        return $this->compression ?? $this->detectCompression();
    }
}
