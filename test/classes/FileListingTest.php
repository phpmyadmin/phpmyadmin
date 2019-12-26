<?php
/**
 * Tests for PhpMyAdmin\FileListing
 * @package PhpMyAdmin\Tests
 */

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FileListing;
use PHPUnit\Framework\TestCase;

/**
 * Class FileListingTest
 * @package PhpMyAdmin\Tests
 */
class FileListingTest extends TestCase
{
    /**
     * @var FileListing $fileListing
     */
    private $fileListing;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->fileListing = new FileListing();
    }

    /**
     * @return void
     */
    public function testGetDirContent(): void
    {
        $this->assertFalse($this->fileListing->getDirContent('nonexistent directory'));
    }

    /**
     * @return void
     */
    public function testGetFileSelectOptions(): void
    {
        $this->assertFalse($this->fileListing->getFileSelectOptions('nonexistent directory'));
    }

    /**
     * @return void
     */
    public function testSupportedDecompressionsEmptyList(): void
    {
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $this->assertEmpty($this->fileListing->supportedDecompressions());
    }

    /**
     * @return void
     * @requires extension bz2 1
     */
    public function testSupportedDecompressionsFull(): void
    {
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = true;
        $GLOBALS['cfg']['BZipDump'] = true;
        $this->assertEquals('gz|bz2|zip', $this->fileListing->supportedDecompressions());
    }

    /**
     * @return void
     */
    public function testSupportedDecompressionsPartial(): void
    {
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = true;
        $GLOBALS['cfg']['BZipDump'] = true;
        $extensionString = 'gz';
        if (extension_loaded('bz2')) {
            $extensionString .= '|bz2';
        }
        $extensionString .= '|zip';
        $this->assertEquals($extensionString, $this->fileListing->supportedDecompressions());
    }
}
