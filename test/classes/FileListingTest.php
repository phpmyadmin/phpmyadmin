<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\FileListing;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function array_values;
use function extension_loaded;
use function is_bool;

use const TEST_PATH;

#[CoversClass(FileListing::class)]
class FileListingTest extends AbstractTestCase
{
    private FileListing $fileListing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileListing = new FileListing();
    }

    public function testGetDirContent(): void
    {
        $this->assertFalse($this->fileListing->getDirContent('nonexistent directory'));

        $fixturesDir = TEST_PATH . 'test/classes/_data/file_listing';

        $dirContent = $this->fileListing->getDirContent($fixturesDir);
        if (is_bool($dirContent)) {
            $dirContent = [];
        }

        $this->assertSame(
            ['one.txt', 'two.md'],
            array_values($dirContent),
        );
    }

    public function testGetFileSelectOptions(): void
    {
        $fixturesDir = TEST_PATH . 'test/classes/_data/file_listing';

        $this->assertFalse($this->fileListing->getFileSelectOptions('nonexistent directory'));

        $expectedHtmlWithoutActive = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md">' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedHtmlWithoutActive,
            $this->fileListing->getFileSelectOptions($fixturesDir),
        );

        $expectedHtmlWithActive = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md" selected="selected">' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedHtmlWithActive,
            $this->fileListing->getFileSelectOptions($fixturesDir, '', 'two.md'),
        );

        $expectedFilteredHtml = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n";

        $this->assertSame(
            $expectedFilteredHtml,
            $this->fileListing->getFileSelectOptions($fixturesDir, '/.*\.txt/'),
        );
    }

    public function testSupportedDecompressionsEmptyList(): void
    {
        $config = Config::getInstance();
        $config->settings['ZipDump'] = false;
        $config->settings['GZipDump'] = false;
        $config->settings['BZipDump'] = false;
        $this->assertEmpty($this->fileListing->supportedDecompressions());
    }

    #[RequiresPhpExtension('bz2')]
    public function testSupportedDecompressionsFull(): void
    {
        $config = Config::getInstance();
        $config->settings['ZipDump'] = true;
        $config->settings['GZipDump'] = true;
        $config->settings['BZipDump'] = true;
        $this->assertEquals('gz|bz2|zip', $this->fileListing->supportedDecompressions());
    }

    public function testSupportedDecompressionsPartial(): void
    {
        $config = Config::getInstance();
        $config->settings['ZipDump'] = true;
        $config->settings['GZipDump'] = true;
        $config->settings['BZipDump'] = true;
        $extensionString = 'gz';
        if (extension_loaded('bz2')) {
            $extensionString .= '|bz2';
        }

        $extensionString .= '|zip';
        $this->assertEquals($extensionString, $this->fileListing->supportedDecompressions());
    }
}
