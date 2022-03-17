<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportOds;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;
use function str_repeat;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportOds
 * @requires extension zip
 */
class ImportOdsTest extends AbstractTestCase
{
    /** @var ImportOds */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $GLOBALS['plugin_param'] = 'csv';
        $GLOBALS['error'] = null;
        $GLOBALS['timeout_passed'] = null;
        $GLOBALS['maximum_time'] = null;
        $GLOBALS['charset_conversion'] = null;
        $GLOBALS['db'] = '';
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['go_sql'] = null;
        $this->object = new ImportOds();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        /**
         * Load interface for zip extension.
        */
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'ods';

        //variable for Ods
        $_REQUEST['ods_recognize_percentages'] = true;
        $_REQUEST['ods_recognize_currency'] = true;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getProperties
     *
     * @group medium
     */
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('OpenDocument Spreadsheet'),
            $properties->getText()
        );
        $this->assertEquals(
            'ods',
            $properties->getExtension()
        );
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
        );
    }

    /**
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        $GLOBALS['sql_query_disabled'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.ods';
        $_REQUEST['ods_empty_rows'] = true;

        parent::setGlobalDbi();

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->setDecompressContent(true);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
            $GLOBALS['sql_query']
        );
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `ODS_DB`.`pma_bookmark`', $GLOBALS['sql_query']);
        $this->assertStringContainsString(
            'INSERT INTO `ODS_DB`.`pma_bookmark` (`A`, `B`, `C`, `D`) VALUES (1, \'dbbase\', NULL, \'ddd\');',
            $GLOBALS['sql_query']
        );

        //asset that all databases and tables are imported
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $GLOBALS['import_notice']
        );
        $this->assertStringContainsString('Go to database: `ODS_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `ODS_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Go to table: `pma_bookmark`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `pma_bookmark`', $GLOBALS['import_notice']);

        //asset that the import process is finished
        $this->assertTrue($GLOBALS['finished']);
    }

    public function dataProviderOdsEmptyRows(): array
    {
        return [
            'remove empty columns' => [true],
            'keep empty columns' => [false],
        ];
    }

    /**
     * Test for doImport using second dataset
     *
     * @group medium
     * @dataProvider dataProviderOdsEmptyRows
     * @requires extension simplexml
     */
    public function testDoImportDataset2(bool $odsEmptyRowsMode): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        $GLOBALS['sql_query_disabled'] = false;

        $GLOBALS['import_file'] = 'test/test_data/import-slim.ods.xml';
        $_REQUEST['ods_col_names'] = true;
        $_REQUEST['ods_empty_rows'] = $odsEmptyRowsMode;

        parent::setGlobalDbi();

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->setDecompressContent(false);// Not compressed
        $importHandle->open();

        // The process could probably detect that all the values for columns V to BL are empty
        // That would make the empty columns not needed and would create a cleaner structure

        $endOfSql = ');;';

        if (! $odsEmptyRowsMode) {
            $fullCols = 'NULL' . str_repeat(', NULL', 18);// 19 empty cells
            $endOfSql = '),' . "\n" . ' (' . $fullCols . '),' . "\n" . ' (' . $fullCols . ');;';
        }

        //Test function called
        $this->object->doImport($importHandle);

        $this->assertSame(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'CREATE TABLE IF NOT EXISTS `ODS_DB`.`Shop` ('
            . '`Artikelnummer` varchar(7), `Name` varchar(41), `keywords` varchar(15), `EK_Preis` varchar(21),'
            . ' `Preis` varchar(23), `Details` varchar(10), `addInfo` varchar(22), `Einheit` varchar(3),'
            . ' `Wirkstoff` varchar(10), `verkuerztHaltbar` varchar(21), `kuehlkette` varchar(7),'
            . ' `Gebinde` varchar(71), `Verbrauchsnachweis` varchar(7), `Genehmigungspflichtig` varchar(7),'
            . ' `Gefahrstoff` varchar(11), `GefahrArbeitsbereich` varchar(14), `Verwendungszweck` varchar(10),'
            . ' `Verbrauch` varchar(10), `showLagerbestand` varchar(7)) '
            . 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'CREATE TABLE IF NOT EXISTS `ODS_DB`.`Feuille 1` (`value` varchar(19)) '
            . 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'INSERT INTO `ODS_DB`.`Shop` ('
            . '`Artikelnummer`, `Name`, `keywords`, `EK_Preis`, `Preis`, `Details`, `addInfo`, `Einheit`,'
            . ' `Wirkstoff`, `verkuerztHaltbar`, `kuehlkette`, `Gebinde`, `Verbrauchsnachweis`,'
            . ' `Genehmigungspflichtig`, `Gefahrstoff`, `GefahrArbeitsbereich`, `Verwendungszweck`,'
            . ' `Verbrauch`, `showLagerbestand`) VALUES ('
            . 'NULL, NULL, \'Schlüsselwörter\', \'Einkaufspreis (Netto)\', \'VK-Preis (Orientierung)\', NULL,'
            . ' \'Hintergrundinformation\', \'VPE\', NULL, \'verkürzte Haltbarkeit\', \'ja/nein\','
            . ' \'Stück,Rolle,Pack,Flasche,Sack,Eimer,Karton,Palette,Beutel,Kanister,Paar\', \'ja/nein\','
            . ' \'ja/nein\', \'GHS01-GHS09\', \'Arbeitsbereich\', NULL, NULL, \'ja/nein\'),' . "\n"
            . ' (\'1005\', \'Beatmungsfilter\', NULL, \'0.85\', \'1,2\', NULL, NULL, \'5\', NULL, NULL, \'nein\','
            . ' \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'04-3-06\', \'Absaugkatheter, CH06 grün\', NULL, \'0.13\', \'0,13\', NULL, NULL, \'1\','
            . ' NULL, NULL,'
            . ' NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'04-3-10\', \'Absaugkatheter, CH10 schwarz\', NULL, \'0.13\', \'0,13\', NULL, NULL, \'1\','
            . ' NULL, NULL, NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'04-3-18\', \'Absaugkatheter, CH18 rot\', NULL, \'0.13\', \'0,13\', NULL, NULL, \'1\','
            . ' NULL, NULL, NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'06-38\', \'Bakterienfilter\', NULL, \'1.25\', \'1,25\', NULL, NULL, \'1\', NULL, NULL, NULL,'
            . ' \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'05-453\', \'Blockerspritze für Larynxtubus, Erwachsen\', NULL, \'2.6\', \'2,6\', NULL, NULL,'
            . ' \'1\', NULL, NULL, NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'04-402\', \'Absaugschlauch mit Fingertip für Accuvac\', NULL, \'1.7\', \'1,7\', NULL, NULL,'
            . ' \'1\', NULL, NULL, NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\'),' . "\n"
            . ' (\'02-580\', \'Einmalbeatmungsbeutel, Erwachsen\', NULL, \'8.9\', \'8,9\', NULL, NULL,'
            . ' \'1\', NULL, NULL, NULL, \'Stück\', \'nein\', \'nein\', NULL, NULL, NULL, NULL, \'ja\''
             . $endOfSql
             . 'INSERT INTO `ODS_DB`.`Feuille 1` (`value`) VALUES ('
             . '\'test@example.org\'),' . "\n"
             . ' (\'123 45\'),' . "\n"
             . ' (\'123 \'),' . "\n"
             . ' (\'test@example.fr\'),' . "\n"
             . ' (\'https://example.org\'),' . "\n"
             . ' (\'example.txt\'),' . "\n"
             . ' (\'\\\'Feuille 1\\\'!A1:A4\'),' . "\n"
             . ' (\'1,50\'),' . "\n"
             . ' (\'0.05\'),' . "\n"
             . ' (\'true\'),' . "\n"
             . ' (\'12\')'
             . ($odsEmptyRowsMode ? '' : ',' . "\n" . ' (NULL)')
             . ($odsEmptyRowsMode ? ';;' : ',' . "\n" . ' (NULL);;'),
            $GLOBALS['sql_query']
        );

        //asset that all databases and tables are imported
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $GLOBALS['import_notice']
        );
        $this->assertStringContainsString('Go to database: `ODS_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `ODS_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Go to table: `Shop`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `Shop`', $GLOBALS['import_notice']);

        //asset that the import process is finished
        $this->assertTrue($GLOBALS['finished']);
    }
}
