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
        self::assertFalse($this->fileListing->getDirContent('nonexistent directory'));

        $fixturesDir = __DIR__ . '/_data/file_listing';

        $dirContent = $this->fileListing->getDirContent($fixturesDir);
        if (is_bool($dirContent)) {
            $dirContent = [];
        }

        self::assertSame(
            ['one.txt', 'two.md'],
            array_values($dirContent),
        );
    }

    public function testGetFileSelectOptions(): void
    {
        $fixturesDir = __DIR__ . '/_data/file_listing';

        self::assertFalse($this->fileListing->getFileSelectOptions('nonexistent directory'));

        $expectedHtmlWithoutActive = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md">' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        self::assertSame(
            $expectedHtmlWithoutActive,
            $this->fileListing->getFileSelectOptions($fixturesDir),
        );

        $expectedHtmlWithActive = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n"
            . '  <option value="two.md" selected>' . "\n"
            . '    two.md' . "\n"
            . '  </option>' . "\n";

        self::assertSame(
            $expectedHtmlWithActive,
            $this->fileListing->getFileSelectOptions($fixturesDir, '', 'two.md'),
        );

        $expectedFilteredHtml = '  <option value="one.txt">' . "\n"
            . '    one.txt' . "\n"
            . '  </option>' . "\n";

        self::assertSame(
            $expectedFilteredHtml,
            $this->fileListing->getFileSelectOptions($fixturesDir, '/.*\.txt/'),
        );
    }

    public function testSupportedDecompressionsEmptyList(): void
    {
        $config = Config::getInstance();
        $config->set('ZipDump', false);
        $config->set('GZipDump', false);
        $config->set('BZipDump', false);
        self::assertEmpty($this->fileListing->supportedDecompressions());
    }

    #[RequiresPhpExtension('bz2')]
    public function testSupportedDecompressionsFull(): void
    {
        self::assertSame('gz|bz2|zip', $this->fileListing->supportedDecompressions());
    }

    public function testSupportedDecompressionsPartial(): void
    {
        $extensionString = 'gz';
        if (extension_loaded('bz2')) {
            $extensionString .= '|bz2';
        }

        $extensionString .= '|zip';
        self::assertSame($extensionString, $this->fileListing->supportedDecompressions());
    }
}
