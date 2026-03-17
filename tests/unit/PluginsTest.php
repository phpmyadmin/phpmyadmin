<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportJson;
use PhpMyAdmin\Plugins\Export\ExportOds;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Plugins\Export\ExportXml;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Plugins\Import\ImportOds;
use PhpMyAdmin\Plugins\Import\ImportSql;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Plugins\Schema\SchemaPdf;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;

use function extension_loaded;

#[CoversClass(Plugins::class)]
class PluginsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testGetExport(): void
    {
        $plugins = Plugins::getExport(ExportType::Database, false);
        $isCurl = extension_loaded('curl');
        self::assertSame(ExportType::Database, ExportPlugin::$exportType);
        self::assertFalse(ExportPlugin::$singleTable);
        $pluginCount = $isCurl ? 15 : 14;
        self::assertCount($pluginCount, $plugins);
        self::assertContainsOnlyInstancesOf(ExportPlugin::class, $plugins);
    }

    public function testGetImport(): void
    {
        ImportSettings::$importType = 'database';
        $plugins = Plugins::getImport();
        self::assertCount(6, $plugins);
        self::assertContainsOnlyInstancesOf(ImportPlugin::class, $plugins);
    }

    public function testGetSchema(): void
    {
        $plugins = Plugins::getSchema();
        self::assertCount(4, $plugins);
        self::assertContainsOnlyInstancesOf(SchemaPlugin::class, $plugins);
    }

    #[DataProvider('providerForTestGetDefault')]
    public function testGetDefault(
        string $expected,
        string|int|null $actualConfig,
        string|null $actualGet,
        PluginType $pluginType,
        string $option,
        bool|null $timeoutPassed,
    ): void {
        $_GET = [];
        $_REQUEST = [];
        if ($timeoutPassed !== null) {
            ImportSettings::$timeoutPassed = $timeoutPassed;
            $_REQUEST[$option] = $actualGet;
        } elseif ($actualGet !== null) {
            $_GET[$option] = $actualGet;
        }

        Config::getInstance()->settings[$pluginType->value][$option] = $actualConfig;
        $default = Plugins::getDefault($pluginType, $option);
        self::assertSame($expected, $default);
    }

    /** @return array<array{string, string|int|null, string|null, PluginType, string, bool|null}> */
    public static function providerForTestGetDefault(): array
    {
        return [
            ['xml', 'xml', null, PluginType::Export, 'format', null],
            ['xml', 'sql', 'xml', PluginType::Export, 'format', null],
            ['xml', null, 'xml', PluginType::Export, 'format', null],
            ['', null, null, PluginType::Export, 'format', null],
            [
                'strLatexStructure strTest strLatexContinued',
                'strLatexStructure strTest strLatexContinued',
                null,
                PluginType::Export,
                'latex_structure_continued_caption',
                null,
            ],
            ['xml', 'sql', 'xml', PluginType::Export, 'format', true],
            ['sql', 'sql', 'xml', PluginType::Export, 'format', false],
            ['30', 30, null, PluginType::Import, 'skip_queries', null],
        ];
    }

    public function testGetChoice(): void
    {
        $config = new Config();
        $dbi = DatabaseInterface::getInstance($config);
        $relation = new Relation($dbi, $config);
        $transformations = new Transformations($dbi, $relation);
        $outputHandler = new OutputHandler();
        $exportList = [
            new ExportJson($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportOds($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportSql($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportXml($relation, $outputHandler, $transformations, $dbi, $config),
        ];
        $actual = Plugins::getChoice($exportList, 'xml');
        $expected = [
            ['name' => 'json', 'text' => 'JSON', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'ods', 'text' => 'OpenDocument Spreadsheet', 'is_selected' => false, 'is_binary' => true],
            ['name' => 'sql', 'text' => 'SQL', 'is_selected' => false, 'is_binary' => false],
            ['name' => 'xml', 'text' => 'XML', 'is_selected' => true, 'is_binary' => false],
        ];
        self::assertSame($expected, $actual);
    }

    public function testGetPlugin(): void
    {
        ContainerBuilder::$container = null;
        self::assertNull(Plugins::getPlugin('export', 'unknown'));
        self::assertInstanceOf(ExportSql::class, Plugins::getPlugin('export', 'sql', ExportType::Table, true));
        self::assertInstanceOf(ImportSql::class, Plugins::getPlugin('import', 'sql'));
        self::assertInstanceOf(SchemaPdf::class, Plugins::getPlugin('schema', 'pdf'));
    }

    public function testGetOptionsForImport(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT @@local_infile;', [['1']]);
        ContainerBuilder::$container = $this->getContainerForGetOptions($this->createDatabaseInterface($dbiDummy));
        ImportSettings::$importType = 'table';

        $options = Plugins::getOptions(PluginType::Import, Plugins::getImport());
        $dbiDummy->assertAllQueriesConsumed();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div id="csv_options" class="format_specific_options"><h3>CSV</h3>
            <div id="csv_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="csv_replace" value="y" id="checkbox_csv_replace" ><label class="form-check-label" for="checkbox_csv_replace">Update data when duplicate keys found on import (add ON DUPLICATE KEY UPDATE)</label></div>
            <li class="list-group-item"><label for="text_csv_terminated" class="form-label">Columns separated with:</label><input class="form-control" type="text" name="csv_terminated" value="," id="text_csv_terminated" size="2">
            <li class="list-group-item"><label for="text_csv_enclosed" class="form-label">Columns enclosed with:</label><input class="form-control" type="text" name="csv_enclosed" value="&quot;" id="text_csv_enclosed" size="2" maxlength="2">
            <li class="list-group-item"><label for="text_csv_escaped" class="form-label">Columns escaped with:</label><input class="form-control" type="text" name="csv_escaped" value="&quot;" id="text_csv_escaped" size="2" maxlength="2">
            <li class="list-group-item"><label for="text_csv_new_line" class="form-label">Lines terminated with:</label><input class="form-control" type="text" name="csv_new_line" value="auto" id="text_csv_new_line" size="2">
            <li class="list-group-item"><label for="number_csv_partial_import" class="form-label">Import these many number of rows (optional):</label><input class="form-control" type="number" name="csv_partial_import" value="" id="number_csv_partial_import" min="0">
            <li class="list-group-item"><label for="text_csv_columns" class="form-label">Column names: <span class="pma_hint"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_help"><span class="hide">If the data in each row of the file is not in the same order as in the database, list the corresponding column names here. Column names must be separated by commas and not enclosed in quotations.</span></span></label><input class="form-control" type="text" name="csv_columns" value="" id="text_csv_columns">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="csv_ignore" value="y" id="checkbox_csv_ignore" ><label class="form-check-label" for="checkbox_csv_ignore">Do not abort on INSERT error</label></div>
            </ul></div>
            </div>

            <div id="ldi_options" class="format_specific_options"><h3>CSV using LOAD DATA</h3>
            <div id="ldi_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ldi_replace" value="y" id="checkbox_ldi_replace" ><label class="form-check-label" for="checkbox_ldi_replace">Update data when duplicate keys found on import (add ON DUPLICATE KEY UPDATE)</label></div>
            <li class="list-group-item"><label for="text_ldi_terminated" class="form-label">Columns separated with:</label><input class="form-control" type="text" name="ldi_terminated" value=";" id="text_ldi_terminated" size="2">
            <li class="list-group-item"><label for="text_ldi_enclosed" class="form-label">Columns enclosed with:</label><input class="form-control" type="text" name="ldi_enclosed" value="&quot;" id="text_ldi_enclosed" size="2" maxlength="2">
            <li class="list-group-item"><label for="text_ldi_escaped" class="form-label">Columns escaped with:</label><input class="form-control" type="text" name="ldi_escaped" value="\" id="text_ldi_escaped" size="2" maxlength="2">
            <li class="list-group-item"><label for="text_ldi_new_line" class="form-label">Lines terminated with:</label><input class="form-control" type="text" name="ldi_new_line" value="auto" id="text_ldi_new_line" size="2">
            <li class="list-group-item"><label for="text_ldi_columns" class="form-label">Column names: </label><input class="form-control" type="text" name="ldi_columns" value="" id="text_ldi_columns">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ldi_ignore" value="y" id="checkbox_ldi_ignore" ><label class="form-check-label" for="checkbox_ldi_ignore">Do not abort on INSERT error</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ldi_local_option" value="y" id="checkbox_ldi_local_option"  checked><label class="form-check-label" for="checkbox_ldi_local_option">Use LOCAL keyword</label></div>
            </ul></div>
            </div>

            <div id="shp_options" class="format_specific_options"><h3>ESRI Shape File</h3><p class="card-text">This format has no options</p></div>

            <div id="mediawiki_options" class="format_specific_options"><h3>MediaWiki Table</h3><p class="card-text">This format has no options</p></div>

            <div id="ods_options" class="format_specific_options"><h3>OpenDocument Spreadsheet</h3>
            <div id="ods_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ods_col_names" value="y" id="checkbox_ods_col_names" ><label class="form-check-label" for="checkbox_ods_col_names">The first line of the file contains the table column names <i>(if this is unchecked, the first line will become part of the data)</i></label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ods_empty_rows" value="y" id="checkbox_ods_empty_rows"  checked><label class="form-check-label" for="checkbox_ods_empty_rows">Do not import empty rows</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ods_recognize_percentages" value="y" id="checkbox_ods_recognize_percentages"  checked><label class="form-check-label" for="checkbox_ods_recognize_percentages">Import percentages as proper decimals <i>(ex. 12.00% to .12)</i></label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ods_recognize_currency" value="y" id="checkbox_ods_recognize_currency"  checked><label class="form-check-label" for="checkbox_ods_recognize_currency">Import currencies <i>(ex. $5.00 to 5.00)</i></label></div>
            </ul></div>
            </div>

            <div id="sql_options" class="format_specific_options"><h3>SQL</h3>
            <div id="sql_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="select_sql_compatibility" class="form-label">SQL compatibility mode:</label><select class="form-select" name="sql_compatibility" id="select_sql_compatibility"><option value="NONE" selected>NONE</option><option value="ANSI">ANSI</option><option value="DB2">DB2</option><option value="MAXDB">MAXDB</option><option value="MYSQL323">MYSQL323</option><option value="MYSQL40">MYSQL40</option><option value="MSSQL">MSSQL</option><option value="ORACLE">ORACLE</option><option value="TRADITIONAL">TRADITIONAL</option></select><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fsql-mode.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_no_auto_value_on_zero" value="y" id="checkbox_sql_no_auto_value_on_zero"  checked><label class="form-check-label" for="checkbox_sql_no_auto_value_on_zero">Do not use <code>AUTO_INCREMENT</code> for zero values</label></div><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fsql-mode.html%23sqlmode_no_auto_value_on_zero" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            </ul></div>
            </div>

            <div id="xml_options" class="format_specific_options"><h3>XML</h3><p class="card-text">This format has no options</p></div>


            HTML;
        // phpcs:enable

        self::assertSame($expected, $options);
    }

    public function testGetOptionsForExport(): void
    {
        ContainerBuilder::$container = $this->getContainerForGetOptions($this->createDatabaseInterface());

        $options = Plugins::getOptions(PluginType::Export, Plugins::getExport(ExportType::Table, true));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div id="codegen_options" class="format_specific_options"><h3>CodeGen</h3>
            <div id="codegen_general_opts"><ul class="list-group">
            <li class="list-group-item"><input type="hidden" name="codegen_structure_or_data" value="data">
            <li class="list-group-item"><label for="select_codegen_format" class="form-label">Format:</label><select class="form-select" name="codegen_format" id="select_codegen_format"><option value="0" selected>NHibernate C# DO</option><option value="1">NHibernate XML</option></select>
            </ul></div>
            </div>

            <div id="csv_options" class="format_specific_options"><h3>CSV</h3>
            <div id="csv_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="text_csv_separator" class="form-label">Columns separated with:</label><input class="form-control" type="text" name="csv_separator" value="," id="text_csv_separator">
            <li class="list-group-item"><label for="text_csv_enclosed" class="form-label">Columns enclosed with:</label><input class="form-control" type="text" name="csv_enclosed" value="&quot;" id="text_csv_enclosed">
            <li class="list-group-item"><label for="text_csv_escaped" class="form-label">Columns escaped with:</label><input class="form-control" type="text" name="csv_escaped" value="&quot;" id="text_csv_escaped">
            <li class="list-group-item"><label for="text_csv_terminated" class="form-label">Lines terminated with:</label><input class="form-control" type="text" name="csv_terminated" value="AUTO" id="text_csv_terminated">
            <li class="list-group-item"><label for="text_csv_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="csv_null" value="NULL" id="text_csv_null">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="csv_removeCRLF" value="y" id="checkbox_csv_removeCRLF" ><label class="form-check-label" for="checkbox_csv_removeCRLF">Remove carriage return/line feed characters within columns</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="csv_columns" value="y" id="checkbox_csv_columns"  checked><label class="form-check-label" for="checkbox_csv_columns">Put columns names in the first row</label></div>
            <li class="list-group-item"><input type="hidden" name="csv_structure_or_data" value="data">
            </ul></div>
            </div>

            <div id="excel_options" class="format_specific_options"><h3>CSV for MS Excel</h3>
            <div id="excel_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="text_excel_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="excel_null" value="NULL" id="text_excel_null">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="excel_removeCRLF" value="y" id="checkbox_excel_removeCRLF" ><label class="form-check-label" for="checkbox_excel_removeCRLF">Remove carriage return/line feed characters within columns</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="excel_columns" value="y" id="checkbox_excel_columns"  checked><label class="form-check-label" for="checkbox_excel_columns">Put columns names in the first row</label></div>
            <li class="list-group-item"><label for="select_excel_edition" class="form-label">Excel edition:</label><select class="form-select" name="excel_edition" id="select_excel_edition"><option value="win" selected>Windows</option><option value="mac_excel2003">Excel 2003 / Macintosh</option><option value="mac_excel2008">Excel 2008 / Macintosh</option></select>
            <li class="list-group-item"><input type="hidden" name="excel_structure_or_data" value="data">
            </ul></div>
            </div>

            <div id="json_options" class="format_specific_options"><h3>JSON</h3>
            <div id="json_general_opts"><ul class="list-group">
            <li class="list-group-item"><input type="hidden" name="json_structure_or_data" value="data">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="json_pretty_print" value="y" id="checkbox_json_pretty_print" ><label class="form-check-label" for="checkbox_json_pretty_print">Output pretty-printed JSON (Use human-readable formatting)</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="json_unicode" value="y" id="checkbox_json_unicode"  checked><label class="form-check-label" for="checkbox_json_unicode">Output unicode characters unescaped</label></div>
            </ul></div>
            </div>

            <div id="latex_options" class="format_specific_options"><h3>LaTeX</h3>
            <div id="latex_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="latex_caption" value="y" id="checkbox_latex_caption"  checked><label class="form-check-label" for="checkbox_latex_caption">Include table caption</label></div>
            </ul></div>

            <div id="latex_dump_what"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="latex_structure_or_data" class="form-check-input" value="structure" id="radio_latex_structure_or_data_structure"><label class="form-check-label" for="radio_latex_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="latex_structure_or_data" class="form-check-input" value="data" id="radio_latex_structure_or_data_data"><label class="form-check-label" for="radio_latex_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="latex_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_latex_structure_or_data_structure_and_data" checked><label class="form-check-label" for="radio_latex_structure_or_data_structure_and_data">structure and data</label></div>
            </ul></div>

            <div id="latex_structure"><h5 class="card-title mt-4 mb-2">Object creation options</h5><ul class="list-group">
            <li class="list-group-item"><label for="text_latex_structure_caption" class="form-label">Table caption:</label><input class="form-control" type="text" name="latex_structure_caption" value="Structure of table @TABLE@" id="text_latex_structure_caption"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><label for="text_latex_structure_continued_caption" class="form-label">Table caption (continued):</label><input class="form-control" type="text" name="latex_structure_continued_caption" value="Structure of table @TABLE@ (continued)" id="text_latex_structure_continued_caption"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><label for="text_latex_structure_label" class="form-label">Label key:</label><input class="form-control" type="text" name="latex_structure_label" value="tab:@TABLE@-structure" id="text_latex_structure_label"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="latex_comments" value="y" id="checkbox_latex_comments"  checked><label class="form-check-label" for="checkbox_latex_comments">Display comments</label></div>
            </ul></div>

            <div id="latex_data"><h5 class="card-title mt-4 mb-2">Data dump options</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="latex_columns" value="y" id="checkbox_latex_columns"  checked><label class="form-check-label" for="checkbox_latex_columns">Put columns names in the first row:</label></div>
            <li class="list-group-item"><label for="text_latex_data_caption" class="form-label">Table caption:</label><input class="form-control" type="text" name="latex_data_caption" value="Content of table @TABLE@" id="text_latex_data_caption"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><label for="text_latex_data_continued_caption" class="form-label">Table caption (continued):</label><input class="form-control" type="text" name="latex_data_continued_caption" value="Content of table @TABLE@ (continued)" id="text_latex_data_continued_caption"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><label for="text_latex_data_label" class="form-label">Label key:</label><input class="form-control" type="text" name="latex_data_label" value="tab:@TABLE@-data" id="text_latex_data_label"><a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faq6-27" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><label for="text_latex_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="latex_null" value="\textit{NULL}" id="text_latex_null">
            </ul></div>
            </div>

            <div id="mediawiki_options" class="format_specific_options"><h3>MediaWiki Table</h3>
            <div id="mediawiki_general_opts"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="mediawiki_structure_or_data" class="form-check-input" value="structure" id="radio_mediawiki_structure_or_data_structure"><label class="form-check-label" for="radio_mediawiki_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="mediawiki_structure_or_data" class="form-check-input" value="data" id="radio_mediawiki_structure_or_data_data" checked><label class="form-check-label" for="radio_mediawiki_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="mediawiki_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_mediawiki_structure_or_data_structure_and_data"><label class="form-check-label" for="radio_mediawiki_structure_or_data_structure_and_data">structure and data</label></div>
            <li class="list-group-item"><ul class="list-group" id="ul_mediawiki_structure_or_data">
            </ul>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="mediawiki_caption" value="y" id="checkbox_mediawiki_caption"  checked><label class="form-check-label" for="checkbox_mediawiki_caption">Export table names</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="mediawiki_headers" value="y" id="checkbox_mediawiki_headers"  checked><label class="form-check-label" for="checkbox_mediawiki_headers">Export table headers</label></div>
            </ul></div>
            </div>

            <div id="htmlword_options" class="format_specific_options"><h3>Microsoft Word 2000</h3>
            <div id="htmlword_dump_what"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="htmlword_structure_or_data" class="form-check-input" value="structure" id="radio_htmlword_structure_or_data_structure"><label class="form-check-label" for="radio_htmlword_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="htmlword_structure_or_data" class="form-check-input" value="data" id="radio_htmlword_structure_or_data_data"><label class="form-check-label" for="radio_htmlword_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="htmlword_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_htmlword_structure_or_data_structure_and_data" checked><label class="form-check-label" for="radio_htmlword_structure_or_data_structure_and_data">structure and data</label></div>
            </ul></div>

            <div id="htmlword_dump_data_options"><h5 class="card-title mt-4 mb-2">Data dump options</h5><ul class="list-group">
            <li class="list-group-item"><label for="text_htmlword_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="htmlword_null" value="NULL" id="text_htmlword_null">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="htmlword_columns" value="y" id="checkbox_htmlword_columns" ><label class="form-check-label" for="checkbox_htmlword_columns">Put columns names in the first row</label></div>
            </ul></div>
            </div>

            <div id="ods_options" class="format_specific_options"><h3>OpenDocument Spreadsheet</h3>
            <div id="ods_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="text_ods_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="ods_null" value="NULL" id="text_ods_null">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="ods_columns" value="y" id="checkbox_ods_columns"  checked><label class="form-check-label" for="checkbox_ods_columns">Put columns names in the first row</label></div>
            <li class="list-group-item"><input type="hidden" name="ods_structure_or_data" value="data">
            </ul></div>
            </div>

            <div id="odt_options" class="format_specific_options"><h3>OpenDocument Text</h3>
            <div id="odt_general_opts"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="odt_structure_or_data" class="form-check-input" value="structure" id="radio_odt_structure_or_data_structure"><label class="form-check-label" for="radio_odt_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="odt_structure_or_data" class="form-check-input" value="data" id="radio_odt_structure_or_data_data"><label class="form-check-label" for="radio_odt_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="odt_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_odt_structure_or_data_structure_and_data" checked><label class="form-check-label" for="radio_odt_structure_or_data_structure_and_data">structure and data</label></div>
            </ul></div>

            <div id="odt_structure"><h5 class="card-title mt-4 mb-2">Object creation options</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="odt_comments" value="y" id="checkbox_odt_comments"  checked><label class="form-check-label" for="checkbox_odt_comments">Display comments</label></div>
            </ul></div>

            <div id="odt_data"><h5 class="card-title mt-4 mb-2">Data dump options</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="odt_columns" value="y" id="checkbox_odt_columns"  checked><label class="form-check-label" for="checkbox_odt_columns">Put columns names in the first row</label></div>
            <li class="list-group-item"><label for="text_odt_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="odt_null" value="NULL" id="text_odt_null">
            </ul></div>
            </div>

            <div id="pdf_options" class="format_specific_options"><h3>PDF</h3>
            <div id="pdf_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="text_pdf_report_title" class="form-label">Report title:</label><input class="form-control" type="text" name="pdf_report_title" value="" id="text_pdf_report_title">
            </ul></div>

            <div id="pdf_dump_what"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="pdf_structure_or_data" class="form-check-input" value="structure" id="radio_pdf_structure_or_data_structure"><label class="form-check-label" for="radio_pdf_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="pdf_structure_or_data" class="form-check-input" value="data" id="radio_pdf_structure_or_data_data" checked><label class="form-check-label" for="radio_pdf_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="pdf_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_pdf_structure_or_data_structure_and_data"><label class="form-check-label" for="radio_pdf_structure_or_data_structure_and_data">structure and data</label></div>
            </ul></div>
            </div>

            <div id="phparray_options" class="format_specific_options"><h3>PHP array</h3>
            <div id="phparray_general_opts"><ul class="list-group">
            <li class="list-group-item"><input type="hidden" name="phparray_structure_or_data" value="data">
            </ul></div>
            <p class="card-text">This format has no options</p></div>

            <div id="sql_options" class="format_specific_options"><h3>SQL</h3>
            <div id="sql_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_include_comments" value="y" id="checkbox_sql_include_comments"  checked><label class="form-check-label" for="checkbox_sql_include_comments">Display comments <i>(includes info such as export timestamp, PHP version, and server version)</i></label></div>
            <li class="list-group-item"><ul class="list-group" id="ul_sql_include_comments">
            <li class="list-group-item"><label for="text_sql_header_comment" class="form-label">Additional custom header comment (\n splits lines):</label><input class="form-control" type="text" name="sql_header_comment" value="" id="text_sql_header_comment">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_dates" value="y" id="checkbox_sql_dates" ><label class="form-check-label" for="checkbox_sql_dates">Include a timestamp of when databases were created, last updated, and last checked</label></div>
            </ul>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_use_transaction" value="y" id="checkbox_sql_use_transaction"  checked><label class="form-check-label" for="checkbox_sql_use_transaction">Enclose export in a transaction</label></div><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fmysqldump.html%23option_mysqldump_single-transaction" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_disable_fk" value="y" id="checkbox_sql_disable_fk" ><label class="form-check-label" for="checkbox_sql_disable_fk">Disable foreign key checks</label></div><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fserver-system-variables.html%23sysvar_foreign_key_checks" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_views_as_tables" value="y" id="checkbox_sql_views_as_tables" ><label class="form-check-label" for="checkbox_sql_views_as_tables">Export views as tables</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_metadata" value="y" id="checkbox_sql_metadata" ><label class="form-check-label" for="checkbox_sql_metadata">Export metadata</label></div>
            <li class="list-group-item"><label for="select_sql_compatibility" class="form-label">Database system or older MySQL server to maximize output compatibility with:</label><select class="form-select" name="sql_compatibility" id="select_sql_compatibility"><option value="NONE" selected>NONE</option><option value="ANSI">ANSI</option><option value="DB2">DB2</option><option value="MAXDB">MAXDB</option><option value="MYSQL323">MYSQL323</option><option value="MYSQL40">MYSQL40</option><option value="MSSQL">MSSQL</option><option value="ORACLE">ORACLE</option><option value="TRADITIONAL">TRADITIONAL</option></select><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fsql-mode.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check"><input type="radio" name="sql_structure_or_data" class="form-check-input" value="structure" id="radio_sql_structure_or_data_structure"><label class="form-check-label" for="radio_sql_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="sql_structure_or_data" class="form-check-input" value="data" id="radio_sql_structure_or_data_data"><label class="form-check-label" for="radio_sql_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="sql_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_sql_structure_or_data_structure_and_data" checked><label class="form-check-label" for="radio_sql_structure_or_data_structure_and_data">structure and data</label></div>
            <li class="list-group-item"><ul class="list-group" id="ul_sql_structure_or_data">
            </ul>
            </ul></div>

            <div id="sql_structure"><h5 class="card-title mt-4 mb-2">Object creation options</h5><ul class="list-group">
            <li class="list-group-item">Add statements:
            <li class="list-group-item"><ul class="list-group" id="ul_sql_add_statements">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_drop_table" value="y" id="checkbox_sql_drop_table" ><label class="form-check-label" for="checkbox_sql_drop_table">Add <code>DROP TABLE</code><code> / TRIGGER</code> statement</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_create_table" value="y" id="checkbox_sql_create_table"  checked><label class="form-check-label" for="checkbox_sql_create_table">Add <code>CREATE TABLE</code> statement</label></div>
            <li class="list-group-item"><ul class="list-group" id="ul_sql_create_table">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_if_not_exists" value="y" id="checkbox_sql_if_not_exists" ><label class="form-check-label" for="checkbox_sql_if_not_exists"><code>IF NOT EXISTS</code> (less efficient as indexes will be generated during table creation)</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_auto_increment" value="y" id="checkbox_sql_auto_increment"  checked><label class="form-check-label" for="checkbox_sql_auto_increment"><code>AUTO_INCREMENT</code> value</label></div>
            </ul>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_create_view" value="y" id="checkbox_sql_create_view"  checked><label class="form-check-label" for="checkbox_sql_create_view">Add <code>CREATE VIEW</code> statement</label></div>
            <li class="list-group-item"><ul class="list-group" id="ul_sql_create_view">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_simple_view_export" value="y" id="checkbox_sql_simple_view_export" ><label class="form-check-label" for="checkbox_sql_simple_view_export">Use simple view export</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_view_current_user" value="y" id="checkbox_sql_view_current_user" ><label class="form-check-label" for="checkbox_sql_view_current_user">Exclude definition of current user</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_or_replace_view" value="y" id="checkbox_sql_or_replace_view" ><label class="form-check-label" for="checkbox_sql_or_replace_view"><code>OR REPLACE</code> view</label></div>
            </ul>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_procedure_function" value="y" id="checkbox_sql_procedure_function"  checked><label class="form-check-label" for="checkbox_sql_procedure_function">Add <code>CREATE PROCEDURE / FUNCTION / EVENT</code> statement</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_create_trigger" value="y" id="checkbox_sql_create_trigger"  checked><label class="form-check-label" for="checkbox_sql_create_trigger">Add <code>CREATE TRIGGER</code> statement</label></div>
            </ul>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_backquotes" value="y" id="checkbox_sql_backquotes"  checked><label class="form-check-label" for="checkbox_sql_backquotes">Enclose table and column names with backquotes <i>(Protects column and table names formed with special characters or keywords)</i></label></div>
            </ul></div>

            <div id="sql_data"><h5 class="card-title mt-4 mb-2">Data creation options</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_truncate" value="y" id="checkbox_sql_truncate" ><label class="form-check-label" for="checkbox_sql_truncate">Truncate table before insert</label></div>
            <li class="list-group-item">Instead of <code>INSERT</code> statements, use:
            <li class="list-group-item"><ul class="list-group" id="ul_sql_insert_alternatives">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_delayed" value="y" id="checkbox_sql_delayed" ><label class="form-check-label" for="checkbox_sql_delayed"><code>INSERT DELAYED</code> statements</label></div><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Finsert-delayed.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_ignore" value="y" id="checkbox_sql_ignore" ><label class="form-check-label" for="checkbox_sql_ignore"><code>INSERT IGNORE</code> statements</label></div><a href="index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Finsert.html" target="mysql_doc"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>
            </ul>
            <li class="list-group-item"><label for="select_sql_type" class="form-label">Function to use when dumping data:</label><select class="form-select" name="sql_type" id="select_sql_type"><option value="INSERT" selected>INSERT</option><option value="UPDATE">UPDATE</option><option value="REPLACE">REPLACE</option></select>
            <li class="list-group-item">Syntax to use when inserting data:
            <li class="list-group-item"><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="sql_insert_syntax" class="form-check-input" value="complete" id="radio_sql_insert_syntax_complete"><label class="form-check-label" for="radio_sql_insert_syntax_complete">include column names in every <code>INSERT</code> statement <br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO tbl_name (col_A,col_B,col_C) VALUES (1,2,3)</code></label></div><div class="form-check"><input type="radio" name="sql_insert_syntax" class="form-check-input" value="extended" id="radio_sql_insert_syntax_extended"><label class="form-check-label" for="radio_sql_insert_syntax_extended">insert multiple rows in every <code>INSERT</code> statement<br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO tbl_name VALUES (1,2,3), (4,5,6), (7,8,9)</code></label></div><div class="form-check"><input type="radio" name="sql_insert_syntax" class="form-check-input" value="both" id="radio_sql_insert_syntax_both" checked><label class="form-check-label" for="radio_sql_insert_syntax_both">both of the above<br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO tbl_name (col_A,col_B,col_C) VALUES (1,2,3), (4,5,6), (7,8,9)</code></label></div><div class="form-check"><input type="radio" name="sql_insert_syntax" class="form-check-input" value="none" id="radio_sql_insert_syntax_none"><label class="form-check-label" for="radio_sql_insert_syntax_none">neither of the above<br> &nbsp; &nbsp; &nbsp; Example: <code>INSERT INTO tbl_name VALUES (1,2,3)</code></label></div>
            </ul>
            <li class="list-group-item"><label for="number_sql_max_query_size" class="form-label">Maximal length of created query</label><input class="form-control" type="number" name="sql_max_query_size" value="50000" id="number_sql_max_query_size" min="0">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_hex_for_binary" value="y" id="checkbox_sql_hex_for_binary"  checked><label class="form-check-label" for="checkbox_sql_hex_for_binary">Dump binary columns in hexadecimal notation <i>(for example, "abc" becomes 0x616263)</i></label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="sql_utc_time" value="y" id="checkbox_sql_utc_time"  checked><label class="form-check-label" for="checkbox_sql_utc_time">Dump TIMESTAMP columns in UTC <i>(enables TIMESTAMP columns to be dumped and reloaded between servers in different time zones)</i></label></div>
            </ul></div>
            </div>

            <div id="texytext_options" class="format_specific_options"><h3>Texy! text</h3>
            <div id="texytext_general_opts"><h5 class="card-title mt-4 mb-2">Dump table</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check"><input type="radio" name="texytext_structure_or_data" class="form-check-input" value="structure" id="radio_texytext_structure_or_data_structure"><label class="form-check-label" for="radio_texytext_structure_or_data_structure">structure</label></div><div class="form-check"><input type="radio" name="texytext_structure_or_data" class="form-check-input" value="data" id="radio_texytext_structure_or_data_data"><label class="form-check-label" for="radio_texytext_structure_or_data_data">data</label></div><div class="form-check"><input type="radio" name="texytext_structure_or_data" class="form-check-input" value="structure_and_data" id="radio_texytext_structure_or_data_structure_and_data" checked><label class="form-check-label" for="radio_texytext_structure_or_data_structure_and_data">structure and data</label></div>
            </ul></div>

            <div id="texytext_data"><h5 class="card-title mt-4 mb-2">Data dump options</h5><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="texytext_columns" value="y" id="checkbox_texytext_columns" ><label class="form-check-label" for="checkbox_texytext_columns">Put columns names in the first row</label></div>
            <li class="list-group-item"><label for="text_texytext_null" class="form-label">Replace NULL with:</label><input class="form-control" type="text" name="texytext_null" value="NULL" id="text_texytext_null">
            </ul></div>
            </div>

            <div id="toon_options" class="format_specific_options"><h3>TOON</h3>
            <div id="toon_general_opts"><ul class="list-group">
            <li class="list-group-item"><label for="text_toon_separator" class="form-label">Columns separated with:</label><input class="form-control" type="text" name="toon_separator" value="," id="text_toon_separator">
            <li class="list-group-item"><label for="text_toon_indent" class="form-label">Indentation:</label><input class="form-control" type="text" name="toon_indent" value="2" id="text_toon_indent">
            <li class="list-group-item"><input type="hidden" name="toon_structure_or_data" value="structure_and_data">
            </ul></div>
            </div>

            <div id="yaml_options" class="format_specific_options"><h3>YAML</h3>
            <div id="yaml_general_opts"><ul class="list-group">
            <li class="list-group-item"><input type="hidden" name="yaml_structure_or_data" value="data">
            </ul></div>
            <p class="card-text">This format has no options</p></div>


            HTML;
        // phpcs:enable

        self::assertSame($expected, $options);
    }

    public function testGetOptionsForSchema(): void
    {
        ContainerBuilder::$container = $this->getContainerForGetOptions($this->createDatabaseInterface());
        $options = Plugins::getOptions(PluginType::Schema, Plugins::getSchema());

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div id="dia_options" class="format_specific_options"><h3>Dia</h3>
            <div id="dia_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="dia_show_color" value="y" id="checkbox_dia_show_color"  checked><label class="form-check-label" for="checkbox_dia_show_color">Show color</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="dia_show_keys" value="y" id="checkbox_dia_show_keys" ><label class="form-check-label" for="checkbox_dia_show_keys">Only show keys</label></div>
            <li class="list-group-item"><label for="select_dia_orientation" class="form-label">Orientation</label><select class="form-select" name="dia_orientation" id="select_dia_orientation"><option value="L" selected>Landscape</option><option value="P">Portrait</option></select>
            <li class="list-group-item"><label for="select_dia_paper" class="form-label">Paper size</label><select class="form-select" name="dia_paper" id="select_dia_paper"><option value="A3">A3</option><option value="A4" selected>A4</option><option value="A5">A5</option><option value="letter">letter</option><option value="legal">legal</option></select>
            </ul></div>
            </div>

            <div id="eps_options" class="format_specific_options"><h3>EPS</h3>
            <div id="eps_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="eps_show_color" value="y" id="checkbox_eps_show_color"  checked><label class="form-check-label" for="checkbox_eps_show_color">Show color</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="eps_show_keys" value="y" id="checkbox_eps_show_keys" ><label class="form-check-label" for="checkbox_eps_show_keys">Only show keys</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="eps_all_tables_same_width" value="y" id="checkbox_eps_all_tables_same_width" ><label class="form-check-label" for="checkbox_eps_all_tables_same_width">Same width for all tables</label></div>
            <li class="list-group-item"><label for="select_eps_orientation" class="form-label">Orientation</label><select class="form-select" name="eps_orientation" id="select_eps_orientation"><option value="L" selected>Landscape</option><option value="P">Portrait</option></select>
            </ul></div>
            </div>

            <div id="pdf_options" class="format_specific_options"><h3>PDF</h3>
            <div id="pdf_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="pdf_show_color" value="y" id="checkbox_pdf_show_color"  checked><label class="form-check-label" for="checkbox_pdf_show_color">Show color</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="pdf_show_keys" value="y" id="checkbox_pdf_show_keys" ><label class="form-check-label" for="checkbox_pdf_show_keys">Only show keys</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="pdf_all_tables_same_width" value="y" id="checkbox_pdf_all_tables_same_width" ><label class="form-check-label" for="checkbox_pdf_all_tables_same_width">Same width for all tables</label></div>
            <li class="list-group-item"><label for="select_pdf_orientation" class="form-label">Orientation</label><select class="form-select" name="pdf_orientation" id="select_pdf_orientation"><option value="L" selected>Landscape</option><option value="P">Portrait</option></select>
            <li class="list-group-item"><label for="select_pdf_paper" class="form-label">Paper size</label><select class="form-select" name="pdf_paper" id="select_pdf_paper"><option value="A3">A3</option><option value="A4" selected>A4</option><option value="A5">A5</option><option value="letter">letter</option><option value="legal">legal</option></select>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="pdf_show_grid" value="y" id="checkbox_pdf_show_grid" ><label class="form-check-label" for="checkbox_pdf_show_grid">Show grid</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="pdf_with_doc" value="y" id="checkbox_pdf_with_doc"  checked><label class="form-check-label" for="checkbox_pdf_with_doc">Data dictionary</label></div>
            <li class="list-group-item"><label for="select_pdf_table_order" class="form-label">Order of the tables</label><select class="form-select" name="pdf_table_order" id="select_pdf_table_order"><option value="" selected>None</option><option value="name_asc">Name (Ascending)</option><option value="name_desc">Name (Descending)</option></select>
            </ul></div>
            </div>

            <div id="svg_options" class="format_specific_options"><h3>SVG</h3>
            <div id="svg_general_opts"><ul class="list-group">
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="svg_show_color" value="y" id="checkbox_svg_show_color"  checked><label class="form-check-label" for="checkbox_svg_show_color">Show color</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="svg_show_keys" value="y" id="checkbox_svg_show_keys" ><label class="form-check-label" for="checkbox_svg_show_keys">Only show keys</label></div>
            <li class="list-group-item"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="svg_all_tables_same_width" value="y" id="checkbox_svg_all_tables_same_width" ><label class="form-check-label" for="checkbox_svg_all_tables_same_width">Same width for all tables</label></div>
            </ul></div>
            </div>


            HTML;
        // phpcs:enable

        self::assertSame($expected, $options);
    }

    private function getContainerForGetOptions(DatabaseInterface $dbi): ContainerInterface
    {
        return new class ($dbi) implements ContainerInterface {
            public function __construct(private readonly DatabaseInterface $dbi)
            {
            }

            /** @psalm-suppress MethodSignatureMismatch */
            public function get(string $id): mixed
            {
                $config = new Config();
                $relation = new Relation($this->dbi, $config);

                return match ($id) {
                    DatabaseInterface::class => $this->dbi,
                    Config::class => $config,
                    Import::class => new Import($this->dbi, new ResponseRenderer(), $config),
                    Relation::class => $relation,
                    Transformations::class => new Transformations($this->dbi, $relation),
                    OutputHandler::class => new OutputHandler(),
                    default => null,
                };
            }

            public function has(string $id): bool
            {
                return true;
            }
        };
    }

    public function testValidatePluginNameOrUseDefaultForExport(): void
    {
        $config = new Config();
        $dbi = DatabaseInterface::getInstance($config);
        $relation = new Relation($dbi, $config);
        $transformations = new Transformations($dbi, $relation);
        $outputHandler = new OutputHandler();

        $exportList = [
            new ExportJson($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportOds($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportSql($relation, $outputHandler, $transformations, $dbi, $config),
            new ExportXml($relation, $outputHandler, $transformations, $dbi, $config),
        ];
        $actual = Plugins::validatePluginNameOrUseDefault($exportList, 'xml');
        self::assertSame('xml', $actual);
        $actual = Plugins::validatePluginNameOrUseDefault($exportList, 'lmx');
        self::assertSame('sql', $actual);
    }

    public function testValidatePluginNameOrUseDefaultForImport(): void
    {
        $config = new Config();
        $dbi = DatabaseInterface::getInstance($config);

        $importList = [
            new ImportOds(new Import($dbi, new ResponseRenderer(), $config), $dbi, $config),
            new ImportSql(new Import($dbi, new ResponseRenderer(), $config), $dbi, $config),
            new ImportXml(new Import($dbi, new ResponseRenderer(), $config), $dbi, $config),
        ];
        $actual = Plugins::validatePluginNameOrUseDefault($importList, 'xml');
        self::assertSame('xml', $actual);
        $actual = Plugins::validatePluginNameOrUseDefault($importList, 'lmx');
        self::assertSame('sql', $actual);
    }
}
