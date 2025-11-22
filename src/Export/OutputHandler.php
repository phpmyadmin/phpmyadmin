<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Utils\UserAgentParser;
use PhpMyAdmin\ZipExtension;

use function __;
use function fclose;
use function function_exists;
use function fwrite;
use function gzencode;
use function header;
use function htmlspecialchars;
use function in_array;
use function ini_get;
use function is_string;
use function ob_list_handlers;
use function strlen;
use function substr;
use function time;

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
    public string $saveFilename = '';
    /** @var resource|null */
    public mixed $fileHandle = null;
    /** @var ''|'zip'|'gzip' */
    public string $compression = '';
    public string $kanjiEncoding = '';
    public string $xkana = '';
    public int $memoryLimit = 0;
    public bool $onFlyCompression = false;
    public int $timeStart = 0;

    public function __invoke(string $line): bool
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
                    if ($this->outputCharsetConversion) {
                        $this->dumpBuffer = Encoding::convertString(
                            'utf-8',
                            Current::$charset ?? 'utf-8',
                            $this->dumpBuffer,
                        );
                    }

                    if ($this->compression === 'gzip' && $this->gzencodeNeeded()) {
                        // as a gzipped file
                        // without the optional parameter level because it bugs
                        $this->dumpBuffer = (string) gzencode($this->dumpBuffer);
                    }

                    if ($this->fileHandle !== null) {
                        $writeResult = @fwrite($this->fileHandle, $this->dumpBuffer);
                        // Here, use strlen rather than mb_strlen to get the length
                        // in bytes to compare against the number of bytes written.
                        if ($writeResult !== strlen($this->dumpBuffer)) {
                            Current::$message = Message::error(
                                __('Insufficient space to save the file %s.'),
                            );
                            Current::$message->addParam($this->saveFilename);

                            return false;
                        }
                    } else {
                        echo $this->dumpBuffer;
                    }

                    $this->dumpBuffer = '';
                    $this->dumpBufferLength = 0;
                }
            } else {
                $timeNow = time();
                if ($this->timeStart >= $timeNow + 30) {
                    $this->timeStart = $timeNow;
                    header('X-pmaPing: Pong');
                }
            }
        } elseif (self::$asFile) {
            if ($this->outputCharsetConversion) {
                $line = Encoding::convertString('utf-8', Current::$charset ?? 'utf-8', $line);
            }

            if ($this->fileHandle !== null && $line !== '') {
                $writeResult = @fwrite($this->fileHandle, $line);
                // Here, use strlen rather than mb_strlen to get the length
                // in bytes to compare against the number of bytes written.
                if ($writeResult !== strlen($line)) {
                    Current::$message = Message::error(
                        __('Insufficient space to save the file %s.'),
                    );
                    Current::$message->addParam($this->saveFilename);

                    return false;
                }

                $timeNow = time();
                if ($this->timeStart >= $timeNow + 30) {
                    $this->timeStart = $timeNow;
                    header('X-pmaPing: Pong');
                }
            } else {
                // We export as file - output normally
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars($line, ENT_COMPAT);
        }

        return true;
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

    public function closeFile(): Message
    {
        $writeResult = false;
        if ($this->fileHandle !== null) {
            $writeResult = @fwrite($this->fileHandle, $this->dumpBuffer);
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }

        // Here, use strlen rather than mb_strlen to get the length
        // in bytes to compare against the number of bytes written.
        if ($this->dumpBuffer !== '' && $writeResult !== strlen($this->dumpBuffer)) {
            return new Message(
                __('Insufficient space to save the file %s.'),
                MessageType::Error,
                [$this->saveFilename],
            );
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
