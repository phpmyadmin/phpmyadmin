<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

#[CoversClass(Transformations::class)]
class TransformationsTest extends AbstractTestCase
{
    private Transformations $transformations;

    /**
     * Set up global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = DatabaseInterface::$instance = $this->createDatabaseInterface();
        Current::$table = 'table';
        Current::$database = 'db';
        $config = Config::getInstance();
        $config->settings = ['ServerDefault' => 1, 'ActionLinksMode' => 'icons'];
        $config->selectedServer['pmadb'] = 'pmadb';
        $config->selectedServer['user'] = 'user';
        $config->selectedServer['bookmarktable'] = '';
        $config->selectedServer['relation'] = '';
        $config->selectedServer['table_info'] = '';
        $config->selectedServer['table_coords'] = '';
        $config->selectedServer['column_info'] = 'column_info';

        $this->transformations = new Transformations($dbi, new Relation($dbi));
    }

    /**
     * Test for parsing options.
     *
     * @param string   $input    String to parse
     * @param string[] $expected Expected result
     */
    #[DataProvider('getOptionsData')]
    public function testGetOptions(string $input, array $expected): void
    {
        self::assertSame(
            $expected,
            $this->transformations->getOptions($input),
        );
    }

    /**
     * Data provided for parsing options
     *
     * @return mixed[][]
     */
    public static function getOptionsData(): array
    {
        return [
            ['option1 , option2 ', ['option1 ', ' option2 ']],
            ["'option1' ,' option2' ", ['option1', ' option2']],
            ["'2,3' ,' ,, option ,,' ", ['2,3', ' ,, option ,,']],
            ["'',,", ['', '', '']],
            ['', []],
        ];
    }

    public function testGetTypes(): void
    {
        self::assertEquals(
            [
                'mimetype' => [
                    'Application/Octetstream' => 'Application/Octetstream',
                    'Image/JPEG' => 'Image/JPEG',
                    'Image/PNG' => 'Image/PNG',
                    'Text/Plain' => 'Text/Plain',
                    'Text/Octetstream' => 'Text/Octetstream',
                ],
                'transformation' => [
                    'Application/Octetstream: Download',
                    'Application/Octetstream: Hex',
                    'Image/JPEG: Inline',
                    'Image/JPEG: Link',
                    'Image/PNG: Inline',
                    'Text/Octetstream: Sql',
                    'Text/Plain: Binarytoip',
                    'Text/Plain: Bool2Text',
                    'Text/Plain: Dateformat',
                    'Text/Plain: External',
                    'Text/Plain: Formatted',
                    'Text/Plain: Imagelink',
                    'Text/Plain: Json',
                    'Text/Plain: Sql',
                    'Text/Plain: Xml',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ],
                'transformation_file' => [
                    'Output/Application_Octetstream_Download.php',
                    'Output/Application_Octetstream_Hex.php',
                    'Output/Image_JPEG_Inline.php',
                    'Output/Image_JPEG_Link.php',
                    'Output/Image_PNG_Inline.php',
                    'Output/Text_Octetstream_Sql.php',
                    'Output/Text_Plain_Binarytoip.php',
                    'Output/Text_Plain_Bool2Text.php',
                    'Output/Text_Plain_Dateformat.php',
                    'Output/Text_Plain_External.php',
                    'Output/Text_Plain_Formatted.php',
                    'Output/Text_Plain_Imagelink.php',
                    'Output/Text_Plain_Json.php',
                    'Output/Text_Plain_Sql.php',
                    'Output/Text_Plain_Xml.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ],
                'input_transformation' => [
                    'Image/JPEG: Upload',
                    'Text/Plain: FileUpload',
                    'Text/Plain: Iptobinary',
                    'Text/Plain: Iptolong',
                    'Text/Plain: JsonEditor',
                    'Text/Plain: RegexValidation',
                    'Text/Plain: SqlEditor',
                    'Text/Plain: XmlEditor',
                    'Text/Plain: Link',
                    'Text/Plain: Longtoipv4',
                    'Text/Plain: PreApPend',
                    'Text/Plain: Substring',
                ],
                'input_transformation_file' => [
                    'Input/Image_JPEG_Upload.php',
                    'Input/Text_Plain_FileUpload.php',
                    'Input/Text_Plain_Iptobinary.php',
                    'Input/Text_Plain_Iptolong.php',
                    'Input/Text_Plain_JsonEditor.php',
                    'Input/Text_Plain_RegexValidation.php',
                    'Input/Text_Plain_SqlEditor.php',
                    'Input/Text_Plain_XmlEditor.php',
                    'Text_Plain_Link.php',
                    'Text_Plain_Longtoipv4.php',
                    'Text_Plain_PreApPend.php',
                    'Text_Plain_Substring.php',
                ],
            ],
            $this->transformations->getAvailableMimeTypes(),
        );
    }

    /**
     * Tests getting mime types for table
     */
    public function testGetMime(): void
    {
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::MIME_WORK => true,
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::COLUMN_INFO => 'column_info',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        self::assertSame(
            [
                'o' => [
                    'column_name' => 'o',
                    'mimetype' => 'Text/plain',
                    'transformation' => 'Sql',
                    'transformation_options' => '',
                    'input_transformation' => 'regex',
                    'input_transformation_options' => '/pma/i',
                ],
                'col' => [
                    'column_name' => 'col',
                    'mimetype' => 'T',
                    'transformation' => 'O/P',
                    'transformation_options' => '',
                    'input_transformation' => 'i/p',
                    'input_transformation_options' => '',
                ],
            ],
            $this->transformations->getMime('pma_test', 'table1'),
        );
    }

    /**
     * Test for clear
     */
    public function testClear(): void
    {
        // Mock dbi
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn(self::createStub(DummyResult::class));

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);

        $this->transformations = new Transformations($dbi, new Relation($dbi));
        // Case 1 : no configuration storage
        $actual = $this->transformations->clear('db');
        self::assertFalse($actual);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::MIME_WORK => true,
            RelationParameters::COLUMN_INFO => 'column_info',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        // Case 2 : database delete
        $actual = $this->transformations->clear('db');
        self::assertTrue($actual);

        // Case 3 : table delete
        $actual = $this->transformations->clear('db', 'table');
        self::assertTrue($actual);

        // Case 4 : column delete
        $actual = $this->transformations->clear('db', 'table', 'col');
        self::assertTrue($actual);
    }

    /**
     * @param string $value    value
     * @param string $expected expected result
     */
    #[DataProvider('fixupData')]
    public function testFixup(string $value, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->transformations->fixUpMime($value),
        );
    }

    /** @return mixed[][] */
    public static function fixupData(): array
    {
        return [
            ['text_plain_bool2text.php', 'Text_Plain_Bool2Text.php'],
            ['application_octetstream_download.php', 'Application_Octetstream_Download.php'],
            ['text_plain_json.php', 'Text_Plain_Json.php'],
            ['image_jpeg_link.php', 'Image_JPEG_Link.php'],
            ['text_plain_dateformat.php', 'Text_Plain_Dateformat.php'],
        ];
    }

    /**
     * Test for getDescription
     *
     * @param string $file                transformation file
     * @param string $expectedDescription expected description
     */
    #[DataProvider('providerGetDescription')]
    public function testGetDescription(string $file, string $expectedDescription): void
    {
        self::assertSame(
            $expectedDescription,
            $this->transformations->getDescription($file),
        );
    }

    /** @return mixed[][] */
    public static function providerGetDescription(): array
    {
        return [
            ['../../../../test', ''],
            ['Input/Text_Plain_SqlEditor', 'Syntax highlighted CodeMirror editor for SQL.'],
            ['Output/Text_Plain_Sql', 'Formats text as SQL query with syntax highlighting.'],
        ];
    }

    /**
     * Test for getName
     *
     * @param string $file         transformation file
     * @param string $expectedName expected name
     */
    #[DataProvider('providerGetName')]
    public function testGetName(string $file, string $expectedName): void
    {
        self::assertSame(
            $expectedName,
            $this->transformations->getName($file),
        );
    }

    /** @return mixed[][] */
    public static function providerGetName(): array
    {
        return [['../../../../test', ''], ['Input/Text_Plain_SqlEditor', 'SQL'], ['Output/Text_Plain_Sql', 'SQL']];
    }
}
