<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportOds;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;
use function str_repeat;

#[CoversClass(ImportOds::class)]
#[RequiresPhpExtension('zip')]
#[Medium]
final class ImportOdsTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Import::$hasError = false;
        ImportSettings::$timeoutPassed = false;
        ImportSettings::$maximumTime = 0;
        ImportSettings::$charsetConversion = false;
        Current::$database = '';
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        Current::$sqlQuery = '';
        ImportSettings::$goSql = false;
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        ImportSettings::$readMultiply = 10;
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $importOds = $this->getImportOds();
        $properties = $importOds->getProperties();
        self::assertSame(
            __('OpenDocument Spreadsheet'),
            $properties->getText(),
        );
        self::assertSame(
            'ods',
            $properties->getExtension(),
        );
        self::assertSame(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail

        ImportSettings::$importFile = 'tests/test_data/db_test.ods';

        $importOds = $this->getImportOds();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'ods_recognize_percentages' => 'yes',
                'ods_recognize_currency' => 'yes',
                'ods_empty_rows' => 'yes',
            ]);
        $importOds->setImportOptions($request);

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->setDecompressContent(true);
        $importHandle->open();

        $importOds->doImport($importHandle);

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
            Current::$sqlQuery,
        );
        self::assertStringContainsString('CREATE TABLE IF NOT EXISTS `ODS_DB`.`pma_bookmark`', Current::$sqlQuery);
        self::assertStringContainsString(
            'INSERT INTO `ODS_DB`.`pma_bookmark` (`A`, `B`, `C`, `D`) VALUES (1, \'dbbase\', NULL, \'ddd\');',
            Current::$sqlQuery,
        );

        //assert that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            ImportSettings::$importNotice,
        );
        self::assertStringContainsString('Go to database: `ODS_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `ODS_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Go to table: `pma_bookmark`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `pma_bookmark`', ImportSettings::$importNotice);

        //assert that the import process is finished
        self::assertTrue(ImportSettings::$finished);
    }

    /** @return array<string, array<string|null>> */
    public static function dataProviderOdsEmptyRows(): array
    {
        return [
            'remove empty columns' => ['yes'],
            'keep empty columns' => [null],
        ];
    }

    /**
     * Test for doImport using second dataset
     */
    #[DataProvider('dataProviderOdsEmptyRows')]
    #[RequiresPhpExtension('simplexml')]
    public function testDoImportDataset2(string|null $odsEmptyRowsMode): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail

        ImportSettings::$importFile = 'tests/test_data/import-slim.ods.xml';

        $importOds = $this->getImportOds();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'ods_recognize_percentages' => 'yes',
                'ods_recognize_currency' => 'yes',
                'ods_col_names' => 'yes',
                'ods_empty_rows' => $odsEmptyRowsMode,
            ]);
        $importOds->setImportOptions($request);

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->setDecompressContent(false);// Not compressed
        $importHandle->open();

        // The process could probably detect that all the values for columns V to BL are empty
        // That would make the empty columns not needed and would create a cleaner structure

        $endOfSql = ');';

        if ($odsEmptyRowsMode === null) {
            $fullCols = 'NULL' . str_repeat(', NULL', 18);// 19 empty cells
            $endOfSql = '),' . "\n" . ' (' . $fullCols . '),' . "\n" . ' (' . $fullCols . ');';
        }

        $importOds->doImport($importHandle);

        self::assertSame(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'
            . 'CREATE TABLE IF NOT EXISTS `ODS_DB`.`Shop` ('
            . '`Artikelnummer` varchar(7), `Name` varchar(41), `keywords` varchar(15), `EK_Preis` varchar(21),'
            . ' `Preis` varchar(23), `Details` varchar(10), `addInfo` varchar(22), `Einheit` varchar(3),'
            . ' `Wirkstoff` varchar(10), `verkuerztHaltbar` varchar(21), `kuehlkette` varchar(7),'
            . ' `Gebinde` varchar(71), `Verbrauchsnachweis` varchar(7), `Genehmigungspflichtig` varchar(7),'
            . ' `Gefahrstoff` varchar(11), `GefahrArbeitsbereich` varchar(14), `Verwendungszweck` varchar(10),'
            . ' `Verbrauch` varchar(10), `showLagerbestand` varchar(7));'
            . 'CREATE TABLE IF NOT EXISTS `ODS_DB`.`Feuille 1` (`value` varchar(19));'
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
             . ($odsEmptyRowsMode !== null ? '' : ',' . "\n" . ' (NULL)')
             . ($odsEmptyRowsMode !== null ? ';' : ',' . "\n" . ' (NULL);'),
            Current::$sqlQuery,
        );

        //assert that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            ImportSettings::$importNotice,
        );
        self::assertStringContainsString('Go to database: `ODS_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `ODS_DB`', ImportSettings::$importNotice);
        self::assertStringContainsString('Go to table: `Shop`', ImportSettings::$importNotice);
        self::assertStringContainsString('Edit settings for `Shop`', ImportSettings::$importNotice);

        //assert that the import process is finished
        self::assertTrue(ImportSettings::$finished);
    }

    private function getImportOds(): ImportOds
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();

        return new ImportOds(new Import($dbi, new ResponseRenderer(), $config), $dbi, $config);
    }
}
