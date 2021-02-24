<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FileListing;
use function array_values;
use function extension_loaded;
use function is_bool;

class FileListingTest extends AbstractTestCase
{
    /** @var FileListing $fileListing */
    private $fileListing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileListing = new FileListing();
    }

    public function testGetDirContent(): void
    {
        $this->assertFalse($this->fileListing->getDirContent('nonexistent directory'));

        $fixturesDir = ROOT_PATH . 'test/classes/_data/file_listing';

        $dirContent = $this->fileListing->getDirContent($fixturesDir);
        if (is_bool($dirContent)) {
            $dirContent = [];
        }
        $this->assertSame(
            array_values([
                'one.txt',
                'two.md',
            ]),
            array_values($dirContent)
        );
    }

    public function testGetFileSelectOptions(): void
    {
        $fixturesDir = ROOT_PATH . 'test/classes/_data/file_listing';

        $this->assertFalse($this->fileListing->getFileSelectOptions('nonexistent directory'));

        $expectedHtmlWithoutActive =
              '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md">' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedHtmlWithoutActive,
            $this->fileListing->getFileSelectOptions($fixturesDir)
        );

        $expectedHtmlWithActive =
              '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md" selected="selected">' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedHtmlWithActive,
            $this->fileListing->getFileSelectOptions($fixturesDir, '', 'two.md')
        );

        $expectedFilteredHtml =
              '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedFilteredHtml,
            $this->fileListing->getFileSelectOptions($fixturesDir, '/.*\.txt/')
        );
    }

    public function testSupportedDecompressionsEmptyList(): void
    {
        $GLOBALS['cfg']['ZipDump'] = false;
        $GLOBALS['cfg']['GZipDump'] = false;
        $GLOBALS['cfg']['BZipDump'] = false;
        $this->assertEmpty($this->fileListing->supportedDecompressions());
    }

    /**
     * @requires extension bz2 1
     */
    public function testSupportedDecompressionsFull(): void
    {
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = true;
        $GLOBALS['cfg']['BZipDump'] = true;
        $this->assertEquals('gz|bz2|zip', $this->fileListing->supportedDecompressions());
    }

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
