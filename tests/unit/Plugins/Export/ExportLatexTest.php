<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\Settings\Export as SettingsExport;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\Export\ExportLatex;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyRootGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Plugins\ExportPluginProperties;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionMethod;
use ReflectionProperty;

use function __;
use function ob_get_clean;
use function ob_start;

#[CoversClass(ExportLatex::class)]
#[Medium]
final class ExportLatexTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OutputHandler::$asFile = true;
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;
        Current::$database = 'db';
        Current::$table = 'table';
    }

    public function testSetProperties(): void
    {
        ExportPlugin::$exportType = ExportType::Raw;
        ExportPlugin::$singleTable = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::RELATION => 'relation',
            RelationParameters::COLUMN_INFO => 'column_info',
            RelationParameters::REL_WORK => true,
            RelationParameters::MIME_WORK => true,
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $exportLatex = $this->getExportLatex();

        $method = new ReflectionMethod(ExportLatex::class, 'setProperties');
        $properties = $method->invoke($exportLatex, null);

        self::assertInstanceOf(ExportPluginProperties::class, $properties);

        self::assertSame(
            'LaTeX',
            $properties->getText(),
        );

        self::assertSame(
            'tex',
            $properties->getExtension(),
        );

        self::assertSame(
            'application/x-tex',
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
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'latex_general_opts',
            $generalOptions->getName(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'latex_caption',
            $property->getName(),
        );

        self::assertSame(
            'Include table caption',
            $property->getText(),
        );

        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'latex_dump_what',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Dump table',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();

        self::assertInstanceOf(RadioPropertyItem::class, $property);

        self::assertSame(
            'latex_structure_or_data',
            $property->getName(),
        );

        self::assertSame(
            ['structure' => __('structure'), 'data' => __('data'), 'structure_and_data' => __('structure and data')],
            $property->getValues(),
        );

        // hide structure
        $generalOptions = $generalOptionsArray->current();
        $generalOptionsArray->next();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'latex_structure',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Object creation options',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_structure_caption',
            $property->getName(),
        );

        self::assertSame(
            'Table caption:',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_structure_continued_caption',
            $property->getName(),
        );

        self::assertSame(
            'Table caption (continued):',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_structure_label',
            $property->getName(),
        );

        self::assertSame(
            'Label key:',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'latex_relation',
            $property->getName(),
        );

        self::assertSame(
            'Display foreign key relationships',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'latex_comments',
            $property->getName(),
        );

        self::assertSame(
            'Display comments',
            $property->getText(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'latex_mime',
            $property->getName(),
        );

        self::assertSame(
            'Display media types',
            $property->getText(),
        );

        // data options
        $generalOptions = $generalOptionsArray->current();

        self::assertInstanceOf(OptionsPropertyMainGroup::class, $generalOptions);

        self::assertSame(
            'latex_data',
            $generalOptions->getName(),
        );

        self::assertSame(
            'Data dump options',
            $generalOptions->getText(),
        );

        $generalProperties = $generalOptions->getProperties();

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(BoolPropertyItem::class, $property);

        self::assertSame(
            'latex_columns',
            $property->getName(),
        );

        self::assertSame(
            'Put columns names in the first row:',
            $property->getText(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_data_caption',
            $property->getName(),
        );

        self::assertSame(
            'Table caption:',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_data_continued_caption',
            $property->getName(),
        );

        self::assertSame(
            'Table caption (continued):',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();
        $generalProperties->next();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_data_label',
            $property->getName(),
        );

        self::assertSame(
            'Label key:',
            $property->getText(),
        );

        self::assertSame(
            'faq6-27',
            $property->getDoc(),
        );

        $property = $generalProperties->current();

        self::assertInstanceOf(TextPropertyItem::class, $property);

        self::assertSame(
            'latex_null',
            $property->getName(),
        );

        self::assertSame(
            'Replace NULL with:',
            $property->getText(),
        );

        // case 2
        ExportPlugin::$exportType = ExportType::Table;
        ExportPlugin::$singleTable = false;

        $method->invoke($exportLatex, null);

        $generalOptionsArray = $options->getProperties();

        self::assertCount(4, $generalOptionsArray);
    }

    public function testExportHeader(): void
    {
        $config = new Config();
        $config->selectedServer['port'] = 80;
        $config->selectedServer['host'] = 'localhost';

        $exportLatex = $this->getExportLatex(config: $config);

        ob_start();
        $exportLatex->exportHeader();
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString("\n% Host: localhost:80", $result);
    }

    public function testExportFooter(): void
    {
        $exportLatex = $this->getExportLatex();
        $this->expectNotToPerformAssertions();
        $exportLatex->exportFooter();
    }

    public function testExportDBHeader(): void
    {
        $exportLatex = $this->getExportLatex();
        $this->expectOutputString("% \n% Database: 'testDB'\n% \n");
        $exportLatex->exportDBHeader('testDB');
    }

    public function testExportDBFooter(): void
    {
        $exportLatex = $this->getExportLatex();
        $this->expectNotToPerformAssertions();
        $exportLatex->exportDBFooter('testDB');
    }

    public function testExportDBCreate(): void
    {
        $exportLatex = $this->getExportLatex();
        $this->expectNotToPerformAssertions();
        $exportLatex->exportDBCreate('testDB');
    }

    public function testExportData(): void
    {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'latex_caption' => 'On',
                'latex_columns' => 'On',
                'latex_data_caption' => 'latex data caption',
                'latex_data_continued_caption' => 'continued caption',
                'latex_data_label' => 'datalabel',
                'latex_null' => 'null',
            ]);

        $exportLatex = $this->getExportLatex();
        $exportLatex->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportLatex->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertSame(
            "\n" . '%' . "\n" .
            '% Data: test_table' . "\n" .
            '%' . "\n" .
            ' \begin{longtable}{|l|l|l|} ' . "\n" .
            ' \hline \endhead \hline \endfoot \hline ' . "\n" .
            ' \caption{latex data caption} \label{datalabel} \\\\\hline \multicolumn{1}{|c|}' .
            '{\textbf{id}} & \multicolumn{1}{|c|}{\textbf{name}} & \multicolumn{1}{|c|}' .
            '{\textbf{datetimefield}} \\\ \hline \hline  \endfirsthead ' . "\n" .
            '\caption{continued caption} \\\ \hline \multicolumn{1}{|c|}{\textbf{id}} & \multicolumn{1}' .
            '{|c|}{\textbf{name}} & \multicolumn{1}{|c|}{\textbf{datetimefield}}' .
            ' \\\ \hline \hline \endhead \endfoot' . "\n" .
            '1 & abcd & 2011-01-20 02:00:02 \\\\ \hline ' . "\n" .
            '2 & foo & 2010-01-20 02:00:02 \\\\ \hline ' . "\n" .
            '3 & Abcd & 2012-01-20 02:00:02 \\\\ \hline ' . "\n" .
            ' \end{longtable}' . "\n",
            $result,
        );

        // case 2
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'latex_caption' => 'On',
                'latex_data_caption' => 'latex data caption',
                'latex_data_continued_caption' => 'continued caption',
                'latex_data_label' => 'datalabel',
                'latex_null' => 'null',
            ]);

        $exportLatex->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportLatex->exportData('test_db', 'test_table', 'SELECT * FROM `test_db`.`test_table`;');
        $result = ob_get_clean();

        self::assertIsString($result);
        self::assertSame(
            "\n" . '%' . "\n" .
            '% Data: test_table' . "\n" .
            '%' . "\n" .
            ' \begin{longtable}{|l|l|l|} ' . "\n" .
            ' \hline \endhead \hline \endfoot \hline ' . "\n" .
            ' \caption{latex data caption} \label{datalabel} \\\\\\\\ \hline' .
            '1 & abcd & 2011-01-20 02:00:02 \\\\ \hline ' . "\n" .
            '2 & foo & 2010-01-20 02:00:02 \\\\ \hline ' . "\n" .
            '3 & Abcd & 2012-01-20 02:00:02 \\\\ \hline ' . "\n" .
            ' \end{longtable}' . "\n",
            $result,
        );
    }

    public function testExportStructure(): void
    {
        $keys = [['Non_unique' => 0, 'Column_name' => 'name1'], ['Non_unique' => 1, 'Column_name' => 'name2']];

        // case 1

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                [],
                ['name1' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'testmimetype_']],
            );

        $columns = [
            new Column('name1', 'set(abc)enum123', null, true, 'PRI', null, '', '', ''),
            new Column('fields', '', null, false, 'COMP', 'def', '', '', ''),
        ];
        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn($columns);

        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        $exportLatex = $this->getExportLatex($dbi);

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['latex_relation' => 'On', 'latex_mime' => 'On', 'latex_comments' => 'On']);

        $exportLatex->setExportOptions($request, new SettingsExport());

        ob_start();
        $exportLatex->exportStructure('database', '', 'test');
        $result = ob_get_clean();

        //echo $result; die;
        self::assertSame(
            "\n" . '%' . "\n" .
            '% Structure: ' . "\n" .
            '%' . "\n" .
            ' \\begin{longtable}{|l|c|c|c|l|l|} ' . "\n" .
            ' \\hline \\multicolumn{1}{|c|}{\\textbf{Column}} & ' .
            '\\multicolumn{1}{|c|}{\\textbf{Type}} & \\multicolumn{1}{|c|}' .
            '{\\textbf{Null}} & \\multicolumn{1}{|c|}{\\textbf{Default}} &' .
            ' \\multicolumn{1}{|c|}{\\textbf{Comments}} & \\multicolumn{1}' .
            '{|c|}{\\textbf{MIME}} \\\\ \\hline \\hline' . "\n" .
            '\\endfirsthead' . "\n" . ' \\hline \\multicolumn{1}{|c|}' .
            '{\\textbf{Column}} & \\multicolumn{1}{|c|}{\\textbf{Type}}' .
            ' & \\multicolumn{1}{|c|}{\\textbf{Null}} & \\multicolumn' .
            '{1}{|c|}{\\textbf{Default}} & \\multicolumn{1}{|c|}{\\textbf' .
            '{Comments}} & \\multicolumn{1}{|c|}{\\textbf{MIME}} \\\\ ' .
            '\\hline \\hline \\endhead \\endfoot ' . "\n" . '\\textbf{\\textit' .
            '{name1}} & set(abc) & Yes & NULL &  ' .
            '& Testmimetype/ \\\\ \\hline ' . "\n" .
            'fields &   & No & def &  &  \\\\ \\hline ' . "\n" .
            ' \\end{longtable}' . "\n",
            $result,
        );

        // case 2

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('fetchResult')
            ->willReturn(
                ['name1' => ['foreign_table' => 'ftable', 'foreign_field' => 'ffield'], 'foreign_keys_data' => []],
                ['field' => ['values' => 'test-', 'transformation' => 'testfoo', 'mimetype' => 'test<']],
            );

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn($columns);

        $dbi->expects(self::once())
            ->method('tryQueryAsControlUser')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(1);

        $resultStub->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['comment' => 'testComment']);

        $exportLatex = $this->getExportLatex($dbi);
        $exportLatex->setExportOptions($request, new SettingsExport());

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::REL_WORK => true,
            RelationParameters::COMM_WORK => true,
            RelationParameters::MIME_WORK => true,
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        ob_start();
        $exportLatex->exportStructure('database', '', 'test');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString(
            '\\textbf{\\textit{name1}} & set(abc) & Yes & NULL & ' .
            'ftable (ffield) &  &  \\\\ \\hline',
            $result,
        );

        // case 3

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getTableIndexes')
            ->with('database', '')
            ->willReturn($keys);

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('database', '')
            ->willReturn($columns);

        $dbi->expects(self::never())
            ->method('tryQuery');

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'latex_caption' => 'On',
                'latex_structure_caption' => 'latexstructure',
                'latex_structure_continued_caption' => 'latexcontinued',
                'latex_structure_label' => 'latexlabel',
            ]);

        $exportLatex = $this->getExportLatex($dbi);
        $exportLatex->setExportOptions($request, new SettingsExport());

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'database',
            RelationParameters::RELATION => 'rel',
            RelationParameters::COLUMN_INFO => 'col',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        ob_start();
        $exportLatex->exportStructure('database', '', 'test');
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('\\caption{latexstructure} \\label{latexlabel}', $result);

        self::assertStringContainsString('caption{latexcontinued}', $result);

        // case 4
        $exportLatex->exportStructure('database', '', 'triggers');
    }

    public function testTexEscape(): void
    {
        self::assertSame(
            '\\$\\%\\{foo\\&bar\\}\\#\\_\\^',
            ExportLatex::texEscape('$%{foo&bar}#_^'),
        );
    }

    #[DataProvider('providerForGetTranslatedText')]
    public function testGetTranslatedText(string $text, string $expected): void
    {
        $exportLatex = $this->getExportLatex();
        self::assertSame($expected, $exportLatex->getTranslatedText($text));
    }

    /** @return iterable<array{string, string}> */
    public static function providerForGetTranslatedText(): iterable
    {
        return [
            ['strTest strTest strTest', 'strTest strTest strTest'],
            ['strTest strLatexContent strTest', 'strTest Content of table @TABLE@ strTest'],
            ['strTest strLatexContinued strTest', 'strTest (continued) strTest'],
            ['strTest strLatexStructure strTest', 'strTest Structure of table @TABLE@ strTest'],
            [
                'strTest strLatexStructure strLatexContent strLatexContinued strTest',
                'strTest Structure of table @TABLE@ Content of table @TABLE@ (continued) strTest',
            ],
        ];
    }

    public function testExportTableCallsExportStructureMethod(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody(['latex_structure_or_data' => 'structure']);

        $exportLatex = $this->getExportLatex($dbi);
        $exportLatex->setExportOptions($request, new SettingsExport());
        ob_start();
        $export = new Export($dbi, new OutputHandler());
        $export->exportTable(
            'testdb',
            'testtable',
            $exportLatex,
            null,
            '0',
            '0',
            '',
            [],
        );
        $output = ob_get_clean();
        self::assertStringContainsString("% Database: 'testdb'", $output);
        self::assertStringContainsString("%\n", $output);
        self::assertStringContainsString('% Structure: testtable', $output);
    }

    private function getExportLatex(DatabaseInterface|null $dbi = null, Config|null $config = null): ExportLatex
    {
        $dbi ??= $this->createDatabaseInterface();
        $config ??= new Config();
        $relation = new Relation($dbi, $config);

        return new ExportLatex($relation, new OutputHandler(), new Transformations($dbi, $relation), $dbi, $config);
    }
}
