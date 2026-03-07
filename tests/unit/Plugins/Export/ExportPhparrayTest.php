<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Plugins\Export\ExportPhparray;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportPhparray::class)]
#[Medium]
final class ExportPhparrayTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = 'en';
    }

    public function testSetProperties(): void
    {
        $exportPhparray = $this->getExportPhparray();

        $method = new ReflectionMethod(ExportPhparray::class, 'setProperties');
        $method->invoke($exportPhparray, null);

        $attrProperties = new ReflectionProperty(ExportPhparray::class, 'properties');
        $properties = $attrProperties->getValue($exportPhparray);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'PHP array',
            $properties->getText(),
        );

        self::assertSame(
            'php',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/plain',
            $properties->getMimeType(),
        );

        $options = $properties->getOptions();

        self::assertInstanceOf(OptionsPropertyRootGroup::class, $options);

        self::assertSame(
            'Format Specific Options',
            $options->getName(),
        );

        $generalOptionsArray = $options->getProperties();
        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'phparray_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);
    }

    public function testExportHeader(): void
    {
        $exportPhparray = $this->getExportPhparray();

        ob_start();
        $exportPhparray->exportHeader();
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('<?php' . "\n", $result);
    }

    public function testExportFooter(): void
    {
        $exportPhparray = $this->getExportPhparray();
        $this->expectNotToPerformAssertions();
        $exportPhparray->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportPhparray = $this->getExportPhparray();

        ob_start();
        $exportPhparray->exportDBHeader('db');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("/**\n * Database `db`\n */", $result);
    }

    public function testExportDBFooter(): void
    {
        $exportPhparray = $this->getExportPhparray();
        $this->expectNotToPerformAssertions();
        $exportPhparray->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportPhparray = $this->getExportPhparray();
        $this->expectNotToPerformAssertions();
        $exportPhparray->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $exportPhparray = $this->getExportPhparray();

        ob_start();
        $exportPhparray->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertSame(
            "\n" . '/* `test_db`.`test_table` */' . "\n" .
            '$test_table = array(' . "\n" .
            '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
            ');' . "\n",
            $result,
        );

        // case 2: test invalid variable name fix
        ob_start();
        $exportPhparray->exportData('test_db', '0`932table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            "\n" . '/* `test_db`.`0``932table` */' . "\n" .
            '$_0_932table = array(' . "\n" .
            '  array(\'id\' => \'1\',\'name\' => \'abcd\',\'datetimefield\' => \'2011-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'2\',\'name\' => \'foo\',\'datetimefield\' => \'2010-01-20 02:00:02\'),' . "\n" .
            '  array(\'id\' => \'3\',\'name\' => \'Abcd\',\'datetimefield\' => \'2012-01-20 02:00:02\')' . "\n" .
            ');' . "\n",
            $result,
        );
    }

    private function getExportPhparray(): ExportPhparray
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $relation = new Relation($dbi, $config);

        return new ExportPhparray($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
