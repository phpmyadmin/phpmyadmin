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

/**
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
        parent::loadDefaultConfig();
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
        $method->setAccessible(true);
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportPhparray::class, 'properties');
        $attrProperties->setAccessible(true);
        $properties = $attrProperties->getValue($this->object);

        $this->assertInstanceOf(
            ExportPluginProperties::class,
            $properties
        );

        $this->assertEquals(
            'PHP array',
            $properties->getText()
        );

        $this->assertEquals(
            'php',
            $properties->getExtension()
        );

        $this->assertEquals(
            'text/plain',
            $properties->getMimeType()
        );

        $this->assertEquals(
            'Options',
            $properties->getOptionsText()
        );

        $options = $properties->getOptions();

        $this->assertInstanceOf(
            OptionsPropertyRootGroup::class,
            $options
        );

        $this->assertEquals(
            'Format Specific Options',
            $options->getName()
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray[0];

        $this->assertInstanceOf(
            OptionsPropertyMainGroup::class,
            $generalOptions
        );

        $this->assertEquals(
            'general_opts',
            $generalOptions->getName()
        );

        $generalProperties = $generalOptions->getProperties();

        $property = array_shift($generalProperties);

        $this->assertInstanceOf(
            HiddenPropertyItem::class,
            $property
        );
    }

    public function testExportHeader(): void
    {
        $GLOBALS['crlf'] = ' ';

        ob_start();
        $this->assertTrue(
            $this->object->exportHeader()
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            '<?php ',
            $result
        );
    }

    public function testExportFooter(): void
    {
        $this->assertTrue(
            $this->object->exportFooter()
        );
    }

    public function testExportDBHeader(): void
    {
        $GLOBALS['crlf'] = "\n";

        ob_start();
        $this->assertTrue(
            $this->object->exportDBHeader('db')
        );
        $result = ob_get_clean();

        $this->assertIsString($result);

        $this->assertStringContainsString(
            "/**\n * Database `db`\n */",
            $result
        );
    }

    public function testExportDBFooter(): void
    {
        $this->assertTrue(
            $this->object->exportDBFooter('testDB')
        );
    }

    public function testExportDBCreate(): void
    {
        $this->assertTrue(
            $this->object->exportDBCreate('testDB', 'database')
        );
    }

    public function testExportData(): void
    {
        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'test_db',
                'test_table',
                "\n",
                'phpmyadmin.net/err',
                'SELECT * FROM `test_db`.`test_table`;'
            )
        );
        $result = ob_get_clean();

        $this->assertEquals(
            "\n" . '/* `test_db`.`test_table` */' . "\n" .
            '$test_table = array(' . "\n" .
            '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
            ');' . "\n",
            $result
        );

        // case 2: test invalid variable name fix
        ob_start();
        $this->assertTrue(
            $this->object->exportData(
                'test_db',
                '0`932table',
                "\n",
                'phpmyadmin.net/err',
                'SELECT * FROM `test_db`.`test_table`;'
            )
        );
        $result = ob_get_clean();

        $this->assertIsString($result);
        $this->assertEquals(
            "\n" . '/* `test_db`.`0``932table` */' . "\n" .
            '$_0_932table = array(' . "\n" .
            '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
            ');' . "\n",
            $result
        );
    }
}
