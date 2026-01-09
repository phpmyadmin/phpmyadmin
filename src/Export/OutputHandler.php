<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\InsufficientSpaceExportException;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\UserAgentParser;
use PhpMyAdmin\ZipExtension;

use function __;
use function fclose;
use function file_exists;
use function fopen;
use function function_exists;
use function fwrite;
use function gzencode;
use function htmlspecialchars;
use function in_array;
use function ini_get;
use function is_file;
use function is_string;
use function is_writable;
use function ob_list_handlers;
use function preg_replace;
use function strlen;
use function substr;

use const ENT_COMPAT;

class OutputHandler
{
    private string $dumpBuffer = '';
    private int $dumpBufferLength = 0;
    /** @var string[] */
    private array $dumpBufferObjects = [];

    public bool $outputCharsetConversion = false;
    public bool $outputKanjiConversion = false;

    public static bool $asFile = false;
    private string $saveFilename = '';
    /** @var resource|null */
    private mixed $fileHandle = null;
    /** @var ''|'zip'|'gzip' */
    public string $compression = '';
    public string $kanjiEncoding = '';
    public string $xkana = '';
    public int $memoryLimit = 0;
    public bool $onFlyCompression = false;

    /** @throws InsufficientSpaceExportException */
    public function addLine(string $line): void
    {
        // Kanji encoding convert feature
        if ($this->outputKanjiConversion) {
            $line = Encoding::kanjiStrConv($line, $this->kanjiEncoding, $this->xkana);
        }

        // If we have to buffer data, we will perform everything at once at the end
        if ($this->compression !== '') {
            $this->dumpBuffer .= $line;
            if ($this->onFlyCompression) {
                $this->dumpBufferLength += strlen($line);

                if ($this->dumpBufferLength > $this->memoryLimit) {
                    $this->convertBufferCharset();

                    if ($this->compression === 'gzip' && $this->gzencodeNeeded()) {
                        // as a gzipped file
                        // without the optional parameter level because it bugs
                        $this->dumpBuffer = (string) gzencode($this->dumpBuffer);
                    }

                    if ($this->fileHandle !== null) {
                        $writeResult = @fwrite($this->fileHandle, $this->dumpBuffer);
                        if ($writeResult !== strlen($this->dumpBuffer)) {
                            Current::$message = Message::error(__('Insufficient space to save the file %s.'));
                            Current::$message->addParam($this->saveFilename);

                            throw new InsufficientSpaceExportException();
                        }
                    } else {
                        echo $this->dumpBuffer;
                    }

                    $this->dumpBuffer = '';
                    $this->dumpBufferLength = 0;
                }
            }

            return;
        }

        if (! self::$asFile) {
            // We export as html - replace special chars
            echo htmlspecialchars($line, ENT_COMPAT);

            return;
        }

        if ($this->outputCharsetConversion) {
            $line = Encoding::convertString('utf-8', Current::$charset ?? 'utf-8', $line);
        }

        if ($this->fileHandle !== null && $line !== '') {
            $writeResult = @fwrite($this->fileHandle, $line);
            if ($writeResult !== strlen($line)) {
                Current::$message = Message::error(__('Insufficient space to save the file %s.'));
                Current::$message->addParam($this->saveFilename);

                throw new InsufficientSpaceExportException();
            }
        } else {
            // We export as file - output normally
            echo $line;
        }
    }

    /**
     * Saves the dump buffer for a particular table in an array
     * Used in separate files export
     *
     * @param string $objectName the name of current object to be stored
     * @param bool   $append     optional boolean to append to an existing index or not
     */
    public function saveObjectInBuffer(string $objectName, bool $append = false): void
    {
        if ($this->dumpBuffer !== '') {
            if ($append && isset($this->dumpBufferObjects[$objectName])) {
                $this->dumpBufferObjects[$objectName] .= $this->dumpBuffer;
            } else {
                $this->dumpBufferObjects[$objectName] = $this->dumpBuffer;
            }
        }

        // Re - initialize
        $this->dumpBuffer = '';
        $this->dumpBufferLength = 0;
    }

    /**
     * Sets the compression method
     *
     * @param 'zip'|'gzip' $compression
     */
    public function setCompression(string $compression, bool $compressOnFly = false): void
    {
        $this->compression = $compression;
        if ($compression !== 'gzip' || ! $compressOnFly) {
            return;
        }

        $this->onFlyCompression = true;
    }

    public function compress(bool $separateFiles, string $filename): void
    {
        $dumpBuffer = $separateFiles
            ? $this->dumpBufferObjects
            : $this->dumpBuffer;
        if ($this->compression === 'zip' && function_exists('gzcompress')) {
            $zipExtension = new ZipExtension();
            $filename = substr($filename, 0, -4); // remove extension (.zip)
            $this->dumpBuffer = $zipExtension->createFile($dumpBuffer, $filename);
        } elseif ($this->compression === 'gzip' && $this->gzencodeNeeded() && is_string($dumpBuffer)) {
            // without the optional parameter level because it bugs
            $this->dumpBuffer = gzencode($dumpBuffer);
        }
    }

    public function openFile(
        string $saveDirectory,
        string $filename,
        bool $quickExport,
        bool $quickOverwriteFile,
        bool $overwriteFile,
    ): Message|null {
        $this->saveFilename = Util::userDir($saveDirectory) . preg_replace('@[/\\\\]@', '_', $filename);

        if (
            @file_exists($this->saveFilename)
            && ((! $quickExport && ! $overwriteFile)
            || ($quickExport && ! $quickOverwriteFile))
        ) {
            $message = Message::error(
                __(
                    'File %s already exists on server, change filename or check overwrite option.',
                ),
            );
            $message->addParam($this->saveFilename);

            return $message;
        }

        if (@is_file($this->saveFilename) && ! @is_writable($this->saveFilename)) {
            $message = Message::error(
                __(
                    'The web server does not have permission to save the file %s.',
                ),
            );
            $message->addParam($this->saveFilename);

            return $message;
        }

        $fileHandle = @fopen($this->saveFilename, 'w');
        if ($fileHandle === false) {
            $message = Message::error(
                __(
                    'The web server does not have permission to save the file %s.',
                ),
            );
            $message->addParam($this->saveFilename);

            return $message;
        }

        $this->fileHandle = $fileHandle;

        return null;
    }

    public function closeFile(): Message
    {
        if ($this->fileHandle !== null) {
            $fileHandle = $this->fileHandle;
            $this->fileHandle = null;
            $writeResult = @fwrite($fileHandle, $this->dumpBuffer);
            fclose($fileHandle);

            if ($this->dumpBuffer !== '' && $writeResult !== strlen($this->dumpBuffer)) {
                return new Message(
                    __('Insufficient space to save the file %s.'),
                    MessageType::Error,
                    [$this->saveFilename],
                );
            }
        }

        return new Message(
            __('Dump has been saved to file %s.'),
            MessageType::Success,
            [$this->saveFilename],
        );
    }

    public function clearBuffer(): void
    {
        $this->dumpBuffer = '';
        $this->dumpBufferLength = 0;
    }

    public function getBuffer(): string
    {
        return $this->dumpBuffer;
    }

    public function convertBufferCharset(): void
    {
        if (! $this->outputCharsetConversion || $this->dumpBuffer === '') {
            return;
        }

        $this->dumpBuffer = Encoding::convertString('utf-8', Current::$charset ?? 'utf-8', $this->dumpBuffer);
    }

    /**
     * Detect whether gzencode is needed; it might not be needed if
     * the server is already compressing by itself
     */
    private function gzencodeNeeded(): bool
    {
        /**
         * We should gzencode only if the function exists
         * but we don't want to compress twice, therefore
         * gzencode only if transparent compression is not enabled
         * but transparent compression does not apply when saving to server
         */
        return function_exists('gzencode')
            && ((! ini_get('zlib.output_compression')
                    && ! $this->isGzHandlerEnabled())
                || $this->fileHandle !== null
                || (new UserAgentParser(Core::getEnv('HTTP_USER_AGENT')))->getUserBrowserAgent() === 'CHROME');
    }

    /**
     * Detect ob_gzhandler
     */
    private function isGzHandlerEnabled(): bool
    {
        /** @var string[] $handlers */
        $handlers = ob_list_handlers();

        return in_array('ob_gzhandler', $handlers, true);
    }
}
