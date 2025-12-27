<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportToon;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportToon::class)]
#[Medium]
class ExportToonTest extends AbstractTestCase
{
    protected ExportToon $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        Current::$database = '';
        Current::$table = '';
        Current::$lang = '';

        $relation = new Relation($dbi);
        $this->object = new ExportToon($relation, new OutputHandler(), new Transformations($dbi, $relation));
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
        $method = new ReflectionMethod(ExportToon::class, 'setProperties');
        $method->invoke($this->object, null);

        $attrProperties = new ReflectionProperty(ExportToon::class, 'properties');
        $properties = $attrProperties->getValue($this->object);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'TOON',
            $properties->getText(),
        );

        self::assertSame(
            'toon',
            $properties->getExtension(),
        );

        self::assertSame(
            'text/toon',
            $properties->getMimeType(),
        );

        self::assertSame(
            'Options',
            $properties->getOptionsText(),
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
            'general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'separator',
            $property->getName(),
        );

        self::assertSame(
            'Columns separated with:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'indent',
            $property->getName(),
        );

        self::assertSame(
            'Indentation:',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(HiddenPropertyItem::class, $property);

        self::assertSame(
            'structure_or_data',
            $property->getName(),
        );
    }

    public function testExportHeader(): void
    {
        self::assertTrue($this->object->exportHeader());
    }

    public function testExportFooter(): void
    {
        self::assertTrue($this->object->exportFooter());
    }

    public function testExportDBHeader(): void
    {
        self::assertTrue($this->object->exportDBHeader('testDB'));
    }

    public function testExportDBFooter(): void
    {
        self::assertTrue($this->object->exportDBFooter('testDB'));
    }

    public function testExportDBCreate(): void
    {
        self::assertTrue($this->object->exportDBCreate('testDB'));
    }

    public function testExportData(): void
    {
        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            'test_db.test_table[3]{id,name,datetimefield}:' . "\n".
            '  1,abcd,2011-01-20 02:00:02' . "\n" .
            '  2,foo,2010-01-20 02:00:02' . "\n" .
            '  3,Abcd,2012-01-20 02:00:02' . "\n\n",
            $result,
        );
    }

    public function testExportDataWithCustomConfig(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['toon_separator' => '|', 'toon_indent' => 4]);

        $this->object->setExportOptions($request, []);
        $this->object->exportHeader();

        ob_start();
        self::assertTrue($this->object->exportData(
            'test_db',
            'test_table',
            'SELECT * FROM `test_db`.`test_table`;',
        ));
        $result = ob_get_clean();

        self::assertSame(
            'test_db.test_table[3|]{id|name|datetimefield}:' . "\n".
            '    1|abcd|2011-01-20 02:00:02' . "\n" .
            '    2|foo|2010-01-20 02:00:02' . "\n" .
            '    3|Abcd|2012-01-20 02:00:02' . "\n\n",
            $result,
        );
    }
}
