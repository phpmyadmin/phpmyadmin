<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Plugins\Export\ExportPhparray;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;
use ReflectionProperty;

use function array_shift;
use function ob_get_clean;
use function ob_start;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Plugins\Export\ExportPhparray
 * @group medium
 */
class ExportPhparrayTest extends AbstractTestCase
{
    /** @var ExportPhparray */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['output_kanji_conversion'] = false;
        $GLOBALS['output_charset_conversion'] = false;
        $GLOBALS['buffer_needed'] = false;
        $GLOBALS['asfile'] = true;
        $GLOBALS['save_on_server'] = false;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $this->object = new ExportPhparray();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testSetProperties(): void
    {
        $method = new ReflectionMethod(ExportPhparray::class, 'setProperties');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportPhparray::class, 'properties');
        if (PHP_VERSION_ID < 80100) {
            $attrProperties->setAccessible(true);
        }

        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame('PHP array', $properties->getText());

        self::assertSame('php', $properties->getExtension());

        self::assertSame('text/plain', $properties->getMimeType());

        self::assertSame('Options', $properties->getOptionsText());

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame('Format Specific Options', $options->getName());

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame('general_opts', $generalOptions->getName());

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        self::assertInstanceOf(HiddenPropertyItem::class, $property);
    }

    public function testExportHeader(): void
    {
        $GLOBALS['crlf'] = ' ';

        ob_start();
        self::assertTrue($this->object->exportHeader());
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('<?php ', $result);
    }

    public function testExportFooter(): void
    {
        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        ob_start();
        self::assertTrue($this->object->exportDBHeader('db'));
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("/**\n * Database `db`\n */", $result);
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue($this->object->exportDBFooter('testDB'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB', 'database'));
    }

    public function testExportData(): void
    {
        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            "\n",
            'phpmyadmin.net/err',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertSame("\n" . '/* `test_db`.`test_table` */' . "\n" .
        '$test_table = array(' . "\n" .
        '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
        '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
        '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
        ');' . "\n", $result);

        // case 2: test invalid variable name fix
        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            '0`932table',
            "\n",
            'phpmyadmin.net/err',
            'SELECT * FROM `test_db`.`test_table`;'
        ));
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame("\n" . '/* `test_db`.`0``932table` */' . "\n" .
        '$_0_932table = array(' . "\n" .
        '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
        '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
        '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
        ');' . "\n", $result);
    }
}
