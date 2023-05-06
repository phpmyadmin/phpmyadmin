<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\Settings;
use PhpMyAdmin\Config\Settings\Console;
use PhpMyAdmin\Config\Settings\Debug;
use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\Config\Settings\Import;
use PhpMyAdmin\Config\Settings\Schema;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Config\Settings\SqlQueryBox;
use PhpMyAdmin\Config\Settings\Transformations;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function array_merge;

use const DIRECTORY_SEPARATOR;
use const ROOT_PATH;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps, Generic.Files.LineLength.TooLong

/**
 * @covers \PhpMyAdmin\Config\Settings
 * @covers \PhpMyAdmin\Config\Settings\Console
 * @covers \PhpMyAdmin\Config\Settings\Debug
 * @covers \PhpMyAdmin\Config\Settings\Export
 * @covers \PhpMyAdmin\Config\Settings\Import
 * @covers \PhpMyAdmin\Config\Settings\Schema
 * @covers \PhpMyAdmin\Config\Settings\Server
 * @covers \PhpMyAdmin\Config\Settings\SqlQueryBox
 * @covers \PhpMyAdmin\Config\Settings\Transformations
 */
class SettingsTest extends TestCase
{
    /** @var array<string, array|bool|int|string|null> */
    private array $defaultValues = [
        'MysqlSslWarningSafeHosts' => ['127.0.0.1', 'localhost'],
        'MemoryLimit' => '-1',
        'SkipLockedTables' => false,
        'ShowSQL' => true,
        'RetainQueryBox' => false,
        'CodemirrorEnable' => true,
        'LintEnable' => true,
        'AllowUserDropDatabase' => false,
        'Confirm' => true,
        'CookieSameSite' => 'Strict',
        'LoginCookieRecall' => true,
        'LoginCookieValidity' => 1440,
        'LoginCookieStore' => 0,
        'LoginCookieDeleteAll' => true,
        'UseDbSearch' => true,
        'IgnoreMultiSubmitErrors' => false,
        'URLQueryEncryption' => false,
        'URLQueryEncryptionSecretKey' => '',
        'AllowArbitraryServer' => false,
        'ArbitraryServerRegexp' => '',
        'CaptchaMethod' => 'invisible',
        'CaptchaApi' => 'https://www.google.com/recaptcha/api.js',
        'CaptchaCsp' => 'https://apis.google.com https://www.google.com/recaptcha/'
            . ' https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/',
        'CaptchaRequestParam' => 'g-recaptcha',
        'CaptchaResponseParam' => 'g-recaptcha-response',
        'CaptchaLoginPublicKey' => '',
        'CaptchaLoginPrivateKey' => '',
        'CaptchaSiteVerifyURL' => '',
        'enable_drag_drop_import' => true,
        'ShowDatabasesNavigationAsTree' => true,
        'FirstLevelNavigationItems' => 100,
        'MaxNavigationItems' => 50,
        'NavigationTreeEnableGrouping' => true,
        'NavigationTreeDbSeparator' => '_',
        'NavigationTreeTableSeparator' => '__',
        'NavigationTreeTableLevel' => 1,
        'NavigationLinkWithMainPanel' => true,
        'NavigationDisplayLogo' => true,
        'NavigationLogoLink' => 'index.php',
        'NavigationLogoLinkWindow' => 'main',
        'NumRecentTables' => 10,
        'NumFavoriteTables' => 10,
        'NavigationTreeDisplayItemFilterMinimum' => 30,
        'NavigationDisplayServers' => true,
        'DisplayServersList' => false,
        'NavigationTreeDisplayDbFilterMinimum' => 30,
        'NavigationTreeDefaultTabTable' => 'structure',
        'NavigationTreeDefaultTabTable2' => '',
        'NavigationTreeEnableExpansion' => true,
        'NavigationTreeShowTables' => true,
        'NavigationTreeShowViews' => true,
        'NavigationTreeShowFunctions' => true,
        'NavigationTreeShowProcedures' => true,
        'NavigationTreeShowEvents' => true,
        'NavigationWidth' => 240,
        'NavigationTreeAutoexpandSingleDb' => true,
        'ShowStats' => true,
        'ShowPhpInfo' => false,
        'ShowServerInfo' => true,
        'ShowChgPassword' => true,
        'ShowCreateDb' => true,
        'ShowDbStructureCharset' => false,
        'ShowDbStructureComment' => false,
        'ShowDbStructureCreation' => false,
        'ShowDbStructureLastUpdate' => false,
        'ShowDbStructureLastCheck' => false,
        'HideStructureActions' => true,
        'ShowColumnComments' => true,
        'TableNavigationLinksMode' => 'icons',
        'Order' => 'SMART',
        'SaveCellsAtOnce' => false,
        'GridEditing' => 'double-click',
        'RelationalDisplay' => 'K',
        'ProtectBinary' => 'blob',
        'ShowFunctionFields' => true,
        'ShowFieldTypesInDataEditView' => true,
        'CharEditing' => 'input',
        'MinSizeForInputField' => 4,
        'MaxSizeForInputField' => 60,
        'InsertRows' => 2,
        'ForeignKeyDropdownOrder' => ['content-id', 'id-content'],
        'ForeignKeyMaxLimit' => 100,
        'DefaultForeignKeyChecks' => 'default',
        'ZipDump' => true,
        'GZipDump' => true,
        'BZipDump' => true,
        'CompressOnFly' => true,
        'TabsMode' => 'both',
        'ActionLinksMode' => 'both',
        'PropertiesNumColumns' => 1,
        'DefaultTabServer' => 'welcome',
        'DefaultTabDatabase' => 'structure',
        'DefaultTabTable' => 'browse',
        'RowActionType' => 'both',
        'Export' => null,
        'Import' => null,
        'Schema' => null,
        'PDFPageSizes' => ['A3', 'A4', 'A5', 'letter', 'legal'],
        'PDFDefaultPageSize' => 'A4',
        'DefaultLang' => 'en',
        'DefaultConnectionCollation' => 'utf8mb4_unicode_ci',
        'Lang' => '',
        'FilterLanguages' => '',
        'RecodingEngine' => 'auto',
        'IconvExtraParams' => '//TRANSLIT',
        'AvailableCharsets' => [
            'iso-8859-1',
            'iso-8859-2',
            'iso-8859-3',
            'iso-8859-4',
            'iso-8859-5',
            'iso-8859-6',
            'iso-8859-7',
            'iso-8859-8',
            'iso-8859-9',
            'iso-8859-10',
            'iso-8859-11',
            'iso-8859-12',
            'iso-8859-13',
            'iso-8859-14',
            'iso-8859-15',
            'windows-1250',
            'windows-1251',
            'windows-1252',
            'windows-1256',
            'windows-1257',
            'koi8-r',
            'big5',
            'gb2312',
            'utf-16',
            'utf-8',
            'utf-7',
            'x-user-defined',
            'euc-jp',
            'ks_c_5601-1987',
            'tis-620',
            'SHIFT_JIS',
            'SJIS',
            'SJIS-win',
        ],
        'NavigationTreePointerEnable' => true,
        'BrowsePointerEnable' => true,
        'BrowseMarkerEnable' => true,
        'TextareaCols' => 40,
        'TextareaRows' => 15,
        'LongtextDoubleTextarea' => true,
        'TextareaAutoSelect' => false,
        'CharTextareaCols' => 40,
        'CharTextareaRows' => 7,
        'RowActionLinks' => 'left',
        'RowActionLinksWithoutUnique' => false,
        'TablePrimaryKeyOrder' => 'NONE',
        'RememberSorting' => true,
        'ShowBrowseComments' => true,
        'ShowPropertyComments' => true,
        'QueryHistoryDB' => false,
        'QueryHistoryMax' => 25,
        'AllowSharedBookmarks' => true,
        'BrowseMIME' => true,
        'MaxExactCount' => 50000,
        'MaxExactCountViews' => 0,
        'NaturalOrder' => true,
        'InitialSlidersState' => 'closed',
        'UserprefsDisallow' => [],
        'UserprefsDeveloperTab' => false,
        'TitleTable' => '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@',
        'TitleDatabase' => '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@',
        'TitleServer' => '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@',
        'TitleDefault' => '@HTTP_HOST@ | @PHPMYADMIN@',
        'ThemeManager' => true,
        'ThemeDefault' => 'pmahomme',
        'ThemePerServer' => false,
        'DefaultQueryTable' => 'SELECT * FROM @TABLE@ WHERE 1',
        'DefaultQueryDatabase' => '',
        'SQLQuery' => null,
        'EnableAutocompleteForTablesAndColumns' => true,
        'UploadDir' => '',
        'SaveDir' => '',
        'TempDir' => ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR,
        'GD2Available' => 'auto',
        'TrustedProxies' => [],
        'CheckConfigurationPermissions' => true,
        'LinkLengthLimit' => 1000,
        'CSPAllow' => '',
        'DisableMultiTableMaintenance' => false,
        'SendErrorReports' => 'ask',
        'ConsoleEnterExecutes' => false,
        'DBG' => null,
        'environment' => 'production',
        'DefaultFunctions' => [
            'FUNC_CHAR' => '',
            'FUNC_DATE' => '',
            'FUNC_NUMBER' => '',
            'FUNC_SPATIAL' => 'GeomFromText',
            'FUNC_UUID' => 'UUID',
            'first_timestamp' => 'NOW',
        ],
        'maxRowPlotLimit' => 500,
        'ShowGitRevision' => true,
        'DisableShortcutKeys' => false,
        'Console' => null,
        'DefaultTransformations' => null,
        'FirstDayOfCalendar' => 0,
    ];

    public function testAsArray(): void
    {
        $settings = new Settings([]);
        $config = $settings->asArray();
        $this->assertIsArray($config['Console']);
        $this->assertIsArray($config['DBG']);
        $this->assertIsArray($config['Export']);
        $this->assertIsArray($config['Import']);
        $this->assertIsArray($config['Schema']);
        $this->assertIsArray($config['SQLQuery']);
        $this->assertIsArray($config['DefaultTransformations']);
        $this->assertIsArray($config['Servers']);
        $this->assertIsArray($config['Servers'][1]);
    }

    /**
     * @param mixed[][] $values
     * @psalm-param (array{0: string, 1: mixed, 2: mixed})[] $values
     *
     * @dataProvider providerForTestConstructor
     */
    public function testConstructor(array $values): void
    {
        $actualValues = [];
        $expectedValues = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            $actualValues[$value[0]] = $value[1];
            $expectedValues[$value[0]] = $value[2];
        }

        $expected = array_merge($this->defaultValues, $expectedValues);
        $settings = new Settings($actualValues);
        $settingsArray = $settings->asArray();
        foreach (array_keys($expectedValues) as $key) {
            if ($key === 'Console') {
                $this->assertInstanceOf(Console::class, $settings->Console);
                continue;
            }

            if ($key === 'DBG') {
                $this->assertInstanceOf(Debug::class, $settings->DBG);
                continue;
            }

            if ($key === 'Export') {
                $this->assertInstanceOf(Export::class, $settings->Export);
                continue;
            }

            if ($key === 'Import') {
                $this->assertInstanceOf(Import::class, $settings->Import);
                continue;
            }

            if ($key === 'Schema') {
                $this->assertInstanceOf(Schema::class, $settings->Schema);
                continue;
            }

            if ($key === 'SQLQuery') {
                $this->assertInstanceOf(SqlQueryBox::class, $settings->SQLQuery);
                continue;
            }

            if ($key === 'DefaultTransformations') {
                $this->assertInstanceOf(Transformations::class, $settings->DefaultTransformations);
                continue;
            }

            $this->assertSame($expected[$key], $settings->$key);
            $this->assertArrayHasKey($key, $settingsArray);
            $this->assertSame($expected[$key], $settingsArray[$key]);
        }
    }

    /**
     * [setting key, actual value, expected value]
     *
     * @return mixed[][][][]
     * @psalm-return (array{0: string, 1: mixed, 2: mixed})[][][]
     */
    public static function providerForTestConstructor(): array
    {
        return [
            'null values' => [
                [
                    ['MysqlSslWarningSafeHosts', null, ['127.0.0.1', 'localhost']],
                    ['MemoryLimit', null, '-1'],
                    ['SkipLockedTables', null, false],
                    ['ShowSQL', null, true],
                    ['RetainQueryBox', null, false],
                    ['CodemirrorEnable', null, true],
                    ['LintEnable', null, true],
                    ['AllowUserDropDatabase', null, false],
                    ['Confirm', null, true],
                    ['CookieSameSite', null, 'Strict'],
                    ['LoginCookieRecall', null, true],
                    ['LoginCookieValidity', null, 1440],
                    ['LoginCookieStore', null, 0],
                    ['LoginCookieDeleteAll', null, true],
                    ['UseDbSearch', null, true],
                    ['IgnoreMultiSubmitErrors', null, false],
                    ['URLQueryEncryption', null, false],
                    ['URLQueryEncryptionSecretKey', null, ''],
                    ['AllowArbitraryServer', null, false],
                    ['ArbitraryServerRegexp', null, ''],
                    ['CaptchaMethod', null, 'invisible'],
                    ['CaptchaApi', null, 'https://www.google.com/recaptcha/api.js'],
                    ['CaptchaCsp', null, 'https://apis.google.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/'],
                    ['CaptchaRequestParam', null, 'g-recaptcha'],
                    ['CaptchaResponseParam', null, 'g-recaptcha-response'],
                    ['CaptchaLoginPublicKey', null, ''],
                    ['CaptchaLoginPrivateKey', null, ''],
                    ['CaptchaSiteVerifyURL', null, ''],
                    ['enable_drag_drop_import', null, true],
                    ['ShowDatabasesNavigationAsTree', null, true],
                    ['FirstLevelNavigationItems', null, 100],
                    ['MaxNavigationItems', null, 50],
                    ['NavigationTreeEnableGrouping', null, true],
                    ['NavigationTreeDbSeparator', null, '_'],
                    ['NavigationTreeTableSeparator', null, '__'],
                    ['NavigationTreeTableLevel', null, 1],
                    ['NavigationLinkWithMainPanel', null, true],
                    ['NavigationDisplayLogo', null, true],
                    ['NavigationLogoLink', null, 'index.php'],
                    ['NavigationLogoLinkWindow', null, 'main'],
                    ['NumRecentTables', null, 10],
                    ['NumFavoriteTables', null, 10],
                    ['NavigationTreeDisplayItemFilterMinimum', null, 30],
                    ['NavigationDisplayServers', null, true],
                    ['DisplayServersList', null, false],
                    ['NavigationTreeDisplayDbFilterMinimum', null, 30],
                    ['NavigationTreeDefaultTabTable', null, 'structure'],
                    ['NavigationTreeDefaultTabTable2', null, ''],
                    ['NavigationTreeEnableExpansion', null, true],
                    ['NavigationTreeShowTables', null, true],
                    ['NavigationTreeShowViews', null, true],
                    ['NavigationTreeShowFunctions', null, true],
                    ['NavigationTreeShowProcedures', null, true],
                    ['NavigationTreeShowEvents', null, true],
                    ['NavigationWidth', null, 240],
                    ['NavigationTreeAutoexpandSingleDb', null, true],
                    ['ShowStats', null, true],
                    ['ShowPhpInfo', null, false],
                    ['ShowServerInfo', null, true],
                    ['ShowChgPassword', null, true],
                    ['ShowCreateDb', null, true],
                    ['ShowDbStructureCharset', null, false],
                    ['ShowDbStructureComment', null, false],
                    ['ShowDbStructureCreation', null, false],
                    ['ShowDbStructureLastUpdate', null, false],
                    ['ShowDbStructureLastCheck', null, false],
                    ['HideStructureActions', null, true],
                    ['ShowColumnComments', null, true],
                    ['TableNavigationLinksMode', null, 'icons'],
                    ['Order', null, 'SMART'],
                    ['SaveCellsAtOnce', null, false],
                    ['GridEditing', null, 'double-click'],
                    ['RelationalDisplay', null, 'K'],
                    ['ProtectBinary', null, 'blob'],
                    ['ShowFunctionFields', null, true],
                    ['ShowFieldTypesInDataEditView', null, true],
                    ['CharEditing', null, 'input'],
                    ['MinSizeForInputField', null, 4],
                    ['MaxSizeForInputField', null, 60],
                    ['InsertRows', null, 2],
                    ['ForeignKeyDropdownOrder', null, ['content-id', 'id-content']],
                    ['ForeignKeyMaxLimit', null, 100],
                    ['DefaultForeignKeyChecks', null, 'default'],
                    ['ZipDump', null, true],
                    ['GZipDump', null, true],
                    ['BZipDump', null, true],
                    ['CompressOnFly', null, true],
                    ['TabsMode', null, 'both'],
                    ['ActionLinksMode', null, 'both'],
                    ['PropertiesNumColumns', null, 1],
                    ['DefaultTabServer', null, 'welcome'],
                    ['DefaultTabDatabase', null, 'structure'],
                    ['DefaultTabTable', null, 'browse'],
                    ['RowActionType', null, 'both'],
                    ['Export', null, null],
                    ['Import', null, null],
                    ['Schema', null, null],
                    ['PDFPageSizes', null, ['A3', 'A4', 'A5', 'letter', 'legal']],
                    ['PDFDefaultPageSize', null, 'A4'],
                    ['DefaultLang', null, 'en'],
                    ['DefaultConnectionCollation', null, 'utf8mb4_unicode_ci'],
                    ['Lang', null, ''],
                    ['FilterLanguages', null, ''],
                    ['RecodingEngine', null, 'auto'],
                    ['IconvExtraParams', null, '//TRANSLIT'],
                    ['AvailableCharsets', null, ['iso-8859-1', 'iso-8859-2', 'iso-8859-3', 'iso-8859-4', 'iso-8859-5', 'iso-8859-6', 'iso-8859-7', 'iso-8859-8', 'iso-8859-9', 'iso-8859-10', 'iso-8859-11', 'iso-8859-12', 'iso-8859-13', 'iso-8859-14', 'iso-8859-15', 'windows-1250', 'windows-1251', 'windows-1252', 'windows-1256', 'windows-1257', 'koi8-r', 'big5', 'gb2312', 'utf-16', 'utf-8', 'utf-7', 'x-user-defined', 'euc-jp', 'ks_c_5601-1987', 'tis-620', 'SHIFT_JIS', 'SJIS', 'SJIS-win']],
                    ['NavigationTreePointerEnable', null, true],
                    ['BrowsePointerEnable', null, true],
                    ['BrowseMarkerEnable', null, true],
                    ['TextareaCols', null, 40],
                    ['TextareaRows', null, 15],
                    ['LongtextDoubleTextarea', null, true],
                    ['TextareaAutoSelect', null, false],
                    ['CharTextareaCols', null, 40],
                    ['CharTextareaRows', null, 7],
                    ['RowActionLinks', null, 'left'],
                    ['RowActionLinksWithoutUnique', null, false],
                    ['TablePrimaryKeyOrder', null, 'NONE'],
                    ['RememberSorting', null, true],
                    ['ShowBrowseComments', null, true],
                    ['ShowPropertyComments', null, true],
                    ['QueryHistoryDB', null, false],
                    ['QueryHistoryMax', null, 25],
                    ['AllowSharedBookmarks', null, true],
                    ['BrowseMIME', null, true],
                    ['MaxExactCount', null, 50000],
                    ['MaxExactCountViews', null, 0],
                    ['NaturalOrder', null, true],
                    ['InitialSlidersState', null, 'closed'],
                    ['UserprefsDisallow', null, []],
                    ['UserprefsDeveloperTab', null, false],
                    ['TitleTable', null, '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@'],
                    ['TitleDatabase', null, '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@'],
                    ['TitleServer', null, '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@'],
                    ['TitleDefault', null, '@HTTP_HOST@ | @PHPMYADMIN@'],
                    ['ThemeManager', null, true],
                    ['ThemeDefault', null, 'pmahomme'],
                    ['ThemePerServer', null, false],
                    ['DefaultQueryTable', null, 'SELECT * FROM @TABLE@ WHERE 1'],
                    ['DefaultQueryDatabase', null, ''],
                    ['SQLQuery', null, null],
                    ['EnableAutocompleteForTablesAndColumns', null, true],
                    ['UploadDir', null, ''],
                    ['SaveDir', null, ''],
                    ['TempDir', null, ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR],
                    ['GD2Available', null, 'auto'],
                    ['TrustedProxies', null, []],
                    ['CheckConfigurationPermissions', null, true],
                    ['LinkLengthLimit', null, 1000],
                    ['CSPAllow', null, ''],
                    ['DisableMultiTableMaintenance', null, false],
                    ['SendErrorReports', null, 'ask'],
                    ['ConsoleEnterExecutes', null, false],
                    ['DBG', null, null],
                    ['environment', null, 'production'],
                    ['DefaultFunctions', null, ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW']],
                    ['maxRowPlotLimit', null, 500],
                    ['ShowGitRevision', null, true],
                    ['DisableShortcutKeys', null, false],
                    ['Console', null, null],
                    ['DefaultTransformations', null, null],
                    ['FirstDayOfCalendar', null, 0],
                ],
            ],
            'valid values' => [
                [
                    ['MysqlSslWarningSafeHosts', ['test1', 'test2'], ['test1', 'test2']],
                    ['MemoryLimit', '16M', '16M'],
                    ['SkipLockedTables', true, true],
                    ['ShowSQL', false, false],
                    ['RetainQueryBox', true, true],
                    ['CodemirrorEnable', false, false],
                    ['LintEnable', false, false],
                    ['AllowUserDropDatabase', true, true],
                    ['Confirm', false, false],
                    ['CookieSameSite', 'Lax', 'Lax'],
                    ['LoginCookieRecall', false, false],
                    ['LoginCookieValidity', 1, 1],
                    ['LoginCookieStore', 0, 0],
                    ['LoginCookieDeleteAll', false, false],
                    ['UseDbSearch', false, false],
                    ['IgnoreMultiSubmitErrors', true, true],
                    ['URLQueryEncryption', true, true],
                    ['URLQueryEncryptionSecretKey', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
                    ['AllowArbitraryServer', true, true],
                    ['ArbitraryServerRegexp', 'test', 'test'],
                    ['CaptchaMethod', 'checkbox', 'checkbox'],
                    ['CaptchaApi', 'test', 'test'],
                    ['CaptchaCsp', 'test', 'test'],
                    ['CaptchaRequestParam', 'test', 'test'],
                    ['CaptchaResponseParam', 'test', 'test'],
                    ['CaptchaLoginPublicKey', 'test', 'test'],
                    ['CaptchaLoginPrivateKey', 'test', 'test'],
                    ['CaptchaSiteVerifyURL', 'test', 'test'],
                    ['enable_drag_drop_import', false, false],
                    ['ShowDatabasesNavigationAsTree', false, false],
                    ['FirstLevelNavigationItems', 1, 1],
                    ['MaxNavigationItems', 1, 1],
                    ['NavigationTreeEnableGrouping', false, false],
                    ['NavigationTreeDbSeparator', 'test', 'test'],
                    ['NavigationTreeTableSeparator', 'test', 'test'],
                    ['NavigationTreeTableLevel', 1, 1],
                    ['NavigationLinkWithMainPanel', false, false],
                    ['NavigationDisplayLogo', false, false],
                    ['NavigationLogoLink', 'test', 'test'],
                    ['NavigationLogoLinkWindow', 'new', 'new'],
                    ['NumRecentTables', 0, 0],
                    ['NumFavoriteTables', 0, 0],
                    ['NavigationTreeDisplayItemFilterMinimum', 1, 1],
                    ['NavigationDisplayServers', false, false],
                    ['DisplayServersList', true, true],
                    ['NavigationTreeDisplayDbFilterMinimum', 1, 1],
                    ['NavigationTreeDefaultTabTable', 'browse', 'browse'],
                    ['NavigationTreeDefaultTabTable2', 'browse', 'browse'],
                    ['NavigationTreeEnableExpansion', false, false],
                    ['NavigationTreeShowTables', false, false],
                    ['NavigationTreeShowViews', false, false],
                    ['NavigationTreeShowFunctions', false, false],
                    ['NavigationTreeShowProcedures', false, false],
                    ['NavigationTreeShowEvents', false, false],
                    ['NavigationWidth', 0, 0],
                    ['NavigationTreeAutoexpandSingleDb', false, false],
                    ['ShowStats', false, false],
                    ['ShowPhpInfo', true, true],
                    ['ShowServerInfo', false, false],
                    ['ShowChgPassword', false, false],
                    ['ShowCreateDb', false, false],
                    ['ShowDbStructureCharset', true, true],
                    ['ShowDbStructureComment', true, true],
                    ['ShowDbStructureCreation', true, true],
                    ['ShowDbStructureLastUpdate', true, true],
                    ['ShowDbStructureLastCheck', true, true],
                    ['HideStructureActions', false, false],
                    ['ShowColumnComments', false, false],
                    ['TableNavigationLinksMode', 'text', 'text'],
                    ['Order', 'ASC', 'ASC'],
                    ['SaveCellsAtOnce', true, true],
                    ['GridEditing', 'click', 'click'],
                    ['RelationalDisplay', 'D', 'D'],
                    ['ProtectBinary', 'noblob', 'noblob'],
                    ['ShowFunctionFields', false, false],
                    ['ShowFieldTypesInDataEditView', false, false],
                    ['CharEditing', 'textarea', 'textarea'],
                    ['MinSizeForInputField', 0, 0],
                    ['MaxSizeForInputField', 1, 1],
                    ['InsertRows', 1, 1],
                    ['ForeignKeyDropdownOrder', ['id-content', 'content-id'], ['id-content', 'content-id']],
                    ['ForeignKeyMaxLimit', 1, 1],
                    ['DefaultForeignKeyChecks', 'enable', 'enable'],
                    ['ZipDump', false, false],
                    ['GZipDump', false, false],
                    ['BZipDump', false, false],
                    ['CompressOnFly', false, false],
                    ['TabsMode', 'icons', 'icons'],
                    ['ActionLinksMode', 'icons', 'icons'],
                    ['PropertiesNumColumns', 1, 1],
                    ['DefaultTabServer', 'privileges', 'privileges'],
                    ['DefaultTabDatabase', 'operations', 'operations'],
                    ['DefaultTabTable', 'structure', 'structure'],
                    ['RowActionType', 'icons', 'icons'],
                    ['Export', [], null],
                    ['Import', [], null],
                    ['Schema', [], null],
                    ['PDFPageSizes', ['test1', 'test2'], ['test1', 'test2']],
                    ['PDFDefaultPageSize', 'test1', 'test1'],
                    ['DefaultLang', 'pt_br', 'pt_br'],
                    ['DefaultConnectionCollation', 'utf8_unicode_ci', 'utf8_unicode_ci'],
                    ['Lang', 'pt_br', 'pt_br'],
                    ['FilterLanguages', '^(pt_br|en)', '^(pt_br|en)'],
                    ['RecodingEngine', 'none', 'none'],
                    ['IconvExtraParams', '//IGNORE', '//IGNORE'],
                    ['AvailableCharsets', ['utf-8', 'iso-8859-1'], ['utf-8', 'iso-8859-1']],
                    ['NavigationTreePointerEnable', false, false],
                    ['BrowsePointerEnable', false, false],
                    ['BrowseMarkerEnable', false, false],
                    ['TextareaCols', 1, 1],
                    ['TextareaRows', 1, 1],
                    ['LongtextDoubleTextarea', false, false],
                    ['TextareaAutoSelect', true, true],
                    ['CharTextareaCols', 1, 1],
                    ['CharTextareaRows', 1, 1],
                    ['RowActionLinks', 'none', 'none'],
                    ['RowActionLinksWithoutUnique', true, true],
                    ['TablePrimaryKeyOrder', 'DESC', 'DESC'],
                    ['RememberSorting', false, false],
                    ['ShowBrowseComments', false, false],
                    ['ShowPropertyComments', false, false],
                    ['QueryHistoryDB', true, true],
                    ['QueryHistoryMax', 1, 1],
                    ['AllowSharedBookmarks', false, false],
                    ['BrowseMIME', false, false],
                    ['MaxExactCount', 1, 1],
                    ['MaxExactCountViews', 0, 0],
                    ['NaturalOrder', false, false],
                    ['InitialSlidersState', 'open', 'open'],
                    ['UserprefsDisallow', ['DisableMultiTableMaintenance', 'Export/lock_tables'], ['DisableMultiTableMaintenance', 'Export/lock_tables']],
                    ['UserprefsDeveloperTab', true, true],
                    ['TitleTable', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleDatabase', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleServer', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleDefault', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['ThemeManager', false, false],
                    ['ThemeDefault', 'test', 'test'],
                    ['ThemePerServer', true, true],
                    ['DefaultQueryTable', 'test', 'test'],
                    ['DefaultQueryDatabase', 'test', 'test'],
                    ['SQLQuery', [], null],
                    ['EnableAutocompleteForTablesAndColumns', false, false],
                    ['UploadDir', 'test', 'test'],
                    ['SaveDir', 'test', 'test'],
                    ['TempDir', 'test', 'test'],
                    ['GD2Available', 'yes', 'yes'],
                    ['TrustedProxies', ['1.2.3.4' => 'HTTP_X_FORWARDED_FOR', 'key' => 'value'], ['1.2.3.4' => 'HTTP_X_FORWARDED_FOR', 'key' => 'value']],
                    ['CheckConfigurationPermissions', false, false],
                    ['LinkLengthLimit', 1, 1],
                    ['CSPAllow', 'phpmyadmin.net', 'phpmyadmin.net'],
                    ['DisableMultiTableMaintenance', true, true],
                    ['SendErrorReports', 'never', 'never'],
                    ['ConsoleEnterExecutes', true, true],
                    ['DBG', [], null],
                    ['environment', 'development', 'development'],
                    ['DefaultFunctions', ['key' => 'value', 'key2' => 'value2'], ['key' => 'value', 'key2' => 'value2']],
                    ['maxRowPlotLimit', 1, 1],
                    ['ShowGitRevision', false, false],
                    ['DisableShortcutKeys', true, true],
                    ['Console', [], null],
                    ['DefaultTransformations', [], null],
                    ['FirstDayOfCalendar', 7, 7],
                ],
            ],
            'valid values 2' => [
                [
                    ['CookieSameSite', 'None', 'None'],
                    ['CaptchaMethod', 'invisible', 'invisible'],
                    ['NavigationTreeTableSeparator', false, false],
                    ['NavigationLogoLinkWindow', 'main', 'main'],
                    ['NavigationTreeDefaultTabTable', 'insert', 'insert'],
                    ['NavigationTreeDefaultTabTable2', 'insert', 'insert'],
                    ['TableNavigationLinksMode', 'both', 'both'],
                    ['ShowServerInfo', 'database-server', 'database-server'],
                    ['Order', 'DESC', 'DESC'],
                    ['GridEditing', 'disabled', 'disabled'],
                    ['RelationalDisplay', 'K', 'K'],
                    ['ProtectBinary', 'all', 'all'],
                    ['CharEditing', 'input', 'input'],
                    ['ForeignKeyDropdownOrder', ['content-id', 'id-content'], ['content-id', 'id-content']],
                    ['DefaultForeignKeyChecks', 'disable', 'disable'],
                    ['TabsMode', 'text', 'text'],
                    ['ActionLinksMode', 'text', 'text'],
                    ['DefaultTabServer', 'welcome', 'welcome'],
                    ['DefaultTabDatabase', 'structure', 'structure'],
                    ['DefaultTabTable', 'browse', 'browse'],
                    ['RowActionType', 'text', 'text'],
                    ['RecodingEngine', 'auto', 'auto'],
                    ['RowActionLinks', 'left', 'left'],
                    ['TablePrimaryKeyOrder', 'NONE', 'NONE'],
                    ['InitialSlidersState', 'closed', 'closed'],
                    ['GD2Available', 'auto', 'auto'],
                    ['TrustedProxies', [], []],
                    ['SendErrorReports', 'ask', 'ask'],
                    ['environment', 'production', 'production'],
                    ['DefaultFunctions', [], []],
                    ['FirstDayOfCalendar', 0, 0],
                ],
            ],
            'valid values 3' => [
                [
                    ['CookieSameSite', 'Strict', 'Strict'],
                    ['NavigationTreeTableSeparator', [1234], ['1234']],
                    ['NavigationTreeDefaultTabTable', 'structure', 'structure'],
                    ['NavigationTreeDefaultTabTable2', 'structure', 'structure'],
                    ['TableNavigationLinksMode', 'icons', 'icons'],
                    ['ShowServerInfo', 'web-server', 'web-server'],
                    ['Order', 'SMART', 'SMART'],
                    ['GridEditing', 'double-click', 'double-click'],
                    ['ProtectBinary', 'blob', 'blob'],
                    ['ForeignKeyDropdownOrder', ['content-id'], ['content-id']],
                    ['DefaultForeignKeyChecks', 'default', 'default'],
                    ['TabsMode', 'both', 'both'],
                    ['ActionLinksMode', 'both', 'both'],
                    ['DefaultTabServer', 'databases', 'databases'],
                    ['DefaultTabDatabase', 'sql', 'sql'],
                    ['DefaultTabTable', 'sql', 'sql'],
                    ['RowActionType', 'both', 'both'],
                    ['RecodingEngine', 'iconv', 'iconv'],
                    ['RowActionLinks', 'right', 'right'],
                    ['TablePrimaryKeyOrder', 'ASC', 'ASC'],
                    ['InitialSlidersState', 'disabled', 'disabled'],
                    ['GD2Available', 'no', 'no'],
                    ['SendErrorReports', 'always', 'always'],
                ],
            ],
            'valid values 4' => [
                [
                    ['NavigationTreeDefaultTabTable', 'sql', 'sql'],
                    ['NavigationTreeDefaultTabTable2', 'sql', 'sql'],
                    ['ProtectBinary', false, false],
                    ['ForeignKeyDropdownOrder', ['id-content'], ['id-content']],
                    ['DefaultTabServer', 'status', 'status'],
                    ['DefaultTabDatabase', 'search', 'search'],
                    ['DefaultTabTable', 'search', 'search'],
                    ['RecodingEngine', 'recode', 'recode'],
                    ['RowActionLinks', 'both', 'both'],
                ],
            ],
            'valid values 5' => [
                [
                    ['NavigationTreeDefaultTabTable', 'search', 'search'],
                    ['NavigationTreeDefaultTabTable2', 'search', 'search'],
                    ['DefaultTabServer', 'variables', 'variables'],
                    ['DefaultTabDatabase', 'db_structure.php', 'structure'],
                    ['DefaultTabTable', 'insert', 'insert'],
                    ['RecodingEngine', 'mb', 'mb'],
                ],
            ],
            'valid values 6' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_structure.php', 'structure'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_structure.php', 'structure'],
                    ['DefaultTabServer', 'index.php', 'welcome'],
                    ['DefaultTabDatabase', 'db_sql.php', 'sql'],
                    ['DefaultTabTable', 'tbl_structure.php', 'structure'],
                ],
            ],
            'valid values 7' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_sql.php', 'sql'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_sql.php', 'sql'],
                    ['DefaultTabServer', 'server_databases.php', 'databases'],
                    ['DefaultTabDatabase', 'db_search.php', 'search'],
                    ['DefaultTabTable', 'tbl_sql.php', 'sql'],
                ],
            ],
            'valid values 8' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_select.php', 'search'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_select.php', 'search'],
                    ['DefaultTabServer', 'server_status.php', 'status'],
                    ['DefaultTabDatabase', 'db_operations.php', 'operations'],
                    ['DefaultTabTable', 'tbl_select.php', 'search'],
                ],
            ],
            'valid values 9' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_change.php', 'insert'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_change.php', 'insert'],
                    ['DefaultTabServer', 'server_variables.php', 'variables'],
                    ['DefaultTabTable', 'tbl_change.php', 'insert'],
                ],
            ],
            'valid values 10' => [
                [
                    ['NavigationTreeDefaultTabTable', 'sql.php', 'browse'],
                    ['NavigationTreeDefaultTabTable2', 'sql.php', 'browse'],
                    ['DefaultTabServer', 'server_privileges.php', 'privileges'],
                    ['DefaultTabTable', 'sql.php', 'browse'],
                ],
            ],
            'valid values 11' => [[['NavigationTreeDefaultTabTable2', '', '']]],
            'valid values with type coercion' => [
                [
                    ['MysqlSslWarningSafeHosts', ['127.0.0.1' => 'local', false, 1234 => 1234], ['local', '1234']],
                    ['MemoryLimit', 1234, '1234'],
                    ['SkipLockedTables', 1, true],
                    ['ShowSQL', 0, false],
                    ['RetainQueryBox', 1, true],
                    ['CodemirrorEnable', 0, false],
                    ['LintEnable', 0, false],
                    ['AllowUserDropDatabase', 1, true],
                    ['Confirm', 0, false],
                    ['LoginCookieRecall', 0, false],
                    ['LoginCookieValidity', '1', 1],
                    ['LoginCookieStore', '1', 1],
                    ['LoginCookieDeleteAll', 0, false],
                    ['UseDbSearch', 0, false],
                    ['IgnoreMultiSubmitErrors', 1, true],
                    ['URLQueryEncryption', 1, true],
                    ['AllowArbitraryServer', 1, true],
                    ['ArbitraryServerRegexp', 1234, '1234'],
                    ['CaptchaApi', 1234, '1234'],
                    ['CaptchaCsp', 1234, '1234'],
                    ['CaptchaRequestParam', 1234, '1234'],
                    ['CaptchaResponseParam', 1234, '1234'],
                    ['CaptchaLoginPublicKey', 1234, '1234'],
                    ['CaptchaLoginPrivateKey', 1234, '1234'],
                    ['CaptchaSiteVerifyURL', 1234, '1234'],
                    ['enable_drag_drop_import', 0, false],
                    ['ShowDatabasesNavigationAsTree', 0, false],
                    ['FirstLevelNavigationItems', '1', 1],
                    ['MaxNavigationItems', '1', 1],
                    ['NavigationTreeEnableGrouping', 0, false],
                    ['NavigationTreeDbSeparator', 1234, '1234'],
                    ['NavigationTreeTableSeparator', true, '1'],
                    ['NavigationTreeTableLevel', '2', 2],
                    ['NavigationLinkWithMainPanel', 0, false],
                    ['NavigationDisplayLogo', 0, false],
                    ['NavigationLogoLink', 1234, '1234'],
                    ['NumRecentTables', '1', 1],
                    ['NumFavoriteTables', '1', 1],
                    ['NavigationTreeDisplayItemFilterMinimum', '1', 1],
                    ['NavigationDisplayServers', 0, false],
                    ['DisplayServersList', 1, true],
                    ['NavigationTreeDisplayDbFilterMinimum', '1', 1],
                    ['NavigationTreeEnableExpansion', 0, false],
                    ['NavigationTreeShowTables', 0, false],
                    ['NavigationTreeShowViews', 0, false],
                    ['NavigationTreeShowFunctions', 0, false],
                    ['NavigationTreeShowProcedures', 0, false],
                    ['NavigationTreeShowEvents', 0, false],
                    ['NavigationWidth', '1', 1],
                    ['NavigationTreeAutoexpandSingleDb', 0, false],
                    ['ShowStats', 0, false],
                    ['ShowPhpInfo', 1, true],
                    ['ShowServerInfo', 0, false],
                    ['ShowChgPassword', 0, false],
                    ['ShowCreateDb', 0, false],
                    ['ShowDbStructureCharset', 1, true],
                    ['ShowDbStructureComment', 1, true],
                    ['ShowDbStructureCreation', 1, true],
                    ['ShowDbStructureLastUpdate', 1, true],
                    ['ShowDbStructureLastCheck', 1, true],
                    ['HideStructureActions', 0, false],
                    ['ShowColumnComments', 0, false],
                    ['SaveCellsAtOnce', 1, true],
                    ['ShowFunctionFields', 0, false],
                    ['ShowFieldTypesInDataEditView', 0, false],
                    ['MinSizeForInputField', '0', 0],
                    ['MaxSizeForInputField', '1', 1],
                    ['InsertRows', '1', 1],
                    ['ForeignKeyMaxLimit', '1', 1],
                    ['ZipDump', 0, false],
                    ['GZipDump', 0, false],
                    ['BZipDump', 0, false],
                    ['CompressOnFly', 0, false],
                    ['PropertiesNumColumns', '2', 2],
                    ['PDFPageSizes', [1234 => 1234, 'test' => 'test'], ['1234', 'test']],
                    ['PDFDefaultPageSize', 1234, '1234'],
                    ['DefaultLang', 1234, '1234'],
                    ['DefaultConnectionCollation', 1234, '1234'],
                    ['Lang', 1234, '1234'],
                    ['FilterLanguages', 1234, '1234'],
                    ['IconvExtraParams', 1234, '1234'],
                    ['AvailableCharsets', [1234 => 1234, 'test' => 'test'], ['1234', 'test']],
                    ['NavigationTreePointerEnable', 0, false],
                    ['BrowsePointerEnable', 0, false],
                    ['BrowseMarkerEnable', 0, false],
                    ['TextareaCols', '1', 1],
                    ['TextareaRows', '1', 1],
                    ['LongtextDoubleTextarea', 0, false],
                    ['TextareaAutoSelect', 1, true],
                    ['CharTextareaCols', '1', 1],
                    ['CharTextareaRows', '1', 1],
                    ['RowActionLinksWithoutUnique', 1, true],
                    ['RememberSorting', 0, false],
                    ['ShowBrowseComments', 0, false],
                    ['ShowPropertyComments', 0, false],
                    ['QueryHistoryDB', 1, true],
                    ['QueryHistoryMax', '1', 1],
                    ['AllowSharedBookmarks', 0, false],
                    ['BrowseMIME', 0, false],
                    ['MaxExactCount', '1', 1],
                    ['MaxExactCountViews', '1', 1],
                    ['NaturalOrder', 0, false],
                    ['UserprefsDisallow', [1234 => 1234, 'test' => 'test'], ['1234', 'test']],
                    ['UserprefsDeveloperTab', 1, true],
                    ['TitleTable', 1234, '1234'],
                    ['TitleDatabase', 1234, '1234'],
                    ['TitleServer', 1234, '1234'],
                    ['TitleDefault', 1234, '1234'],
                    ['ThemeManager', 0, false],
                    ['ThemeDefault', 1234, '1234'],
                    ['ThemePerServer', 1, true],
                    ['DefaultQueryTable', 1234, '1234'],
                    ['DefaultQueryDatabase', 1234, '1234'],
                    ['EnableAutocompleteForTablesAndColumns', 0, false],
                    ['UploadDir', 1234, '1234'],
                    ['SaveDir', 1234, '1234'],
                    ['TempDir', 1234, '1234'],
                    ['TrustedProxies', ['test' => 1234], ['test' => '1234']],
                    ['CheckConfigurationPermissions', 0, false],
                    ['LinkLengthLimit', '1', 1],
                    ['CSPAllow', 1234, '1234'],
                    ['DisableMultiTableMaintenance', 1, true],
                    ['ConsoleEnterExecutes', 1, true],
                    ['DefaultFunctions', ['test' => 1234], ['test' => '1234']],
                    ['maxRowPlotLimit', '1', 1],
                    ['ShowGitRevision', 0, false],
                    ['DisableShortcutKeys', 1, true],
                    ['FirstDayOfCalendar', '1', 1],
                ],
            ],
            'invalid values' => [
                [
                    ['MysqlSslWarningSafeHosts', 'invalid', ['127.0.0.1', 'localhost']],
                    ['CookieSameSite', 'invalid', 'Strict'],
                    ['LoginCookieValidity', 0, 1440],
                    ['LoginCookieStore', -1, 0],
                    ['CaptchaMethod', 'invalid', 'invisible'],
                    ['FirstLevelNavigationItems', 0, 100],
                    ['MaxNavigationItems', 0, 50],
                    ['NavigationTreeTableSeparator', [], '__'],
                    ['NavigationTreeTableLevel', 0, 1],
                    ['NavigationLogoLinkWindow', 'invalid', 'main'],
                    ['NumRecentTables', -1, 10],
                    ['NumFavoriteTables', -1, 10],
                    ['NavigationTreeDisplayItemFilterMinimum', 0, 30],
                    ['NavigationTreeDisplayDbFilterMinimum', 0, 30],
                    ['NavigationTreeDefaultTabTable', 'invalid', 'structure'],
                    ['NavigationTreeDefaultTabTable2', 'invalid', ''],
                    ['NavigationWidth', -1, 240],
                    ['TableNavigationLinksMode', 'invalid', 'icons'],
                    ['Order', 'invalid', 'SMART'],
                    ['GridEditing', 'invalid', 'double-click'],
                    ['RelationalDisplay', 'invalid', 'K'],
                    ['ProtectBinary', true, 'blob'],
                    ['CharEditing', 'invalid', 'input'],
                    ['MinSizeForInputField', -1, 4],
                    ['MaxSizeForInputField', 0, 60],
                    ['InsertRows', 0, 2],
                    ['ForeignKeyDropdownOrder', ['invalid'], ['content-id', 'id-content']],
                    ['ForeignKeyMaxLimit', 0, 100],
                    ['DefaultForeignKeyChecks', 'invalid', 'default'],
                    ['TabsMode', 'invalid', 'both'],
                    ['ActionLinksMode', 'invalid', 'both'],
                    ['PropertiesNumColumns', 0, 1],
                    ['DefaultTabServer', 'invalid', 'welcome'],
                    ['DefaultTabDatabase', 'invalid', 'structure'],
                    ['DefaultTabTable', 'invalid', 'browse'],
                    ['RowActionType', 'invalid', 'both'],
                    ['PDFPageSizes', 'invalid', ['A3', 'A4', 'A5', 'letter', 'legal']],
                    ['RecodingEngine', 'invalid', 'auto'],
                    ['AvailableCharsets', 'invalid', ['iso-8859-1', 'iso-8859-2', 'iso-8859-3', 'iso-8859-4', 'iso-8859-5', 'iso-8859-6', 'iso-8859-7', 'iso-8859-8', 'iso-8859-9', 'iso-8859-10', 'iso-8859-11', 'iso-8859-12', 'iso-8859-13', 'iso-8859-14', 'iso-8859-15', 'windows-1250', 'windows-1251', 'windows-1252', 'windows-1256', 'windows-1257', 'koi8-r', 'big5', 'gb2312', 'utf-16', 'utf-8', 'utf-7', 'x-user-defined', 'euc-jp', 'ks_c_5601-1987', 'tis-620', 'SHIFT_JIS', 'SJIS', 'SJIS-win']],
                    ['TextareaCols', 0, 40],
                    ['TextareaRows', 0, 15],
                    ['CharTextareaCols', 0, 40],
                    ['CharTextareaRows', 0, 7],
                    ['RowActionLinks', 'invalid', 'left'],
                    ['TablePrimaryKeyOrder', 'invalid', 'NONE'],
                    ['QueryHistoryMax', 0, 25],
                    ['MaxExactCount', 0, 50000],
                    ['MaxExactCountViews', -1, 0],
                    ['InitialSlidersState', 'invalid', 'closed'],
                    ['UserprefsDisallow', 'invalid', []],
                    ['SQLQuery', 'invalid', null],
                    ['EnableAutocompleteForTablesAndColumns', null, true],
                    ['GD2Available', 'invalid', 'auto'],
                    ['TrustedProxies', 'invalid', []],
                    ['LinkLengthLimit', 0, 1000],
                    ['SendErrorReports', 'invalid', 'ask'],
                    ['DBG', 'invalid', null],
                    ['environment', 'invalid', 'production'],
                    ['DefaultFunctions', 'invalid', ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW']],
                    ['maxRowPlotLimit', 0, 500],
                    ['Console', 'invalid', null],
                    ['FirstDayOfCalendar', 8, 0],
                ],
            ],
            'invalid values 2' => [
                [
                    ['ForeignKeyDropdownOrder', ['id-content', 'invalid'], ['id-content']],
                    ['TrustedProxies', [1234 => 'invalid', 'valid' => 'valid'], ['valid' => 'valid']],
                    ['DefaultFunctions', [1234 => 'invalid', 'valid' => 'valid'], ['valid' => 'valid']],
                    ['FirstDayOfCalendar', -1, 0],
                ],
            ],
            'invalid values 3' => [
                [
                    ['ForeignKeyDropdownOrder', 'invalid', ['content-id', 'id-content']],
                ],
            ],
            'invalid values 4' => [[['ForeignKeyDropdownOrder', [1 => 'content-id'], ['content-id', 'id-content']]]],
        ];
    }

    /** @dataProvider valuesForPmaAbsoluteUriProvider */
    public function testPmaAbsoluteUri(mixed $actual, string $expected): void
    {
        $settings = new Settings(['PmaAbsoluteUri' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->PmaAbsoluteUri);
        $this->assertArrayHasKey('PmaAbsoluteUri', $settingsArray);
        $this->assertSame($expected, $settingsArray['PmaAbsoluteUri']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPmaAbsoluteUriProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['https://www.phpmyadmin.net/', 'https://www.phpmyadmin.net/'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForAuthLogProvider */
    public function testAuthLog(mixed $actual, string $expected): void
    {
        $settings = new Settings(['AuthLog' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->authLog);
        $this->assertArrayHasKey('AuthLog', $settingsArray);
        $this->assertSame($expected, $settingsArray['AuthLog']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForAuthLogProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['/path/to/file', '/path/to/file'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testAuthLogSuccess(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['AuthLogSuccess' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->authLogSuccess);
        $this->assertArrayHasKey('AuthLogSuccess', $settingsArray);
        $this->assertSame($expected, $settingsArray['AuthLogSuccess']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testPmaNoRelationDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['PmaNoRelation_DisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->PmaNoRelation_DisableWarning);
        $this->assertArrayHasKey('PmaNoRelation_DisableWarning', $settingsArray);
        $this->assertSame($expected, $settingsArray['PmaNoRelation_DisableWarning']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testSuhosinDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['SuhosinDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->SuhosinDisableWarning);
        $this->assertArrayHasKey('SuhosinDisableWarning', $settingsArray);
        $this->assertSame($expected, $settingsArray['SuhosinDisableWarning']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testLoginCookieValidityDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LoginCookieValidityDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->LoginCookieValidityDisableWarning);
        $this->assertArrayHasKey('LoginCookieValidityDisableWarning', $settingsArray);
        $this->assertSame($expected, $settingsArray['LoginCookieValidityDisableWarning']);
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testReservedWordDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ReservedWordDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ReservedWordDisableWarning);
        $this->assertArrayHasKey('ReservedWordDisableWarning', $settingsArray);
        $this->assertSame($expected, $settingsArray['ReservedWordDisableWarning']);
    }

    /** @dataProvider valuesForTranslationWarningThresholdProvider */
    public function testTranslationWarningThreshold(mixed $actual, int $expected): void
    {
        $settings = new Settings(['TranslationWarningThreshold' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->TranslationWarningThreshold);
        $this->assertArrayHasKey('TranslationWarningThreshold', $settingsArray);
        $this->assertSame($expected, $settingsArray['TranslationWarningThreshold']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForTranslationWarningThresholdProvider(): iterable
    {
        yield 'null value' => [null, 80];
        yield 'valid value' => [100, 100];
        yield 'valid value with type coercion' => ['0', 0];
        yield 'invalid value' => [-1, 80];
        yield 'invalid value 2' => [101, 100];
    }

    /** @dataProvider valuesForAllowThirdPartyFramingProvider */
    public function testAllowThirdPartyFraming(mixed $actual, bool|string $expected): void
    {
        $settings = new Settings(['AllowThirdPartyFraming' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->AllowThirdPartyFraming);
        $this->assertArrayHasKey('AllowThirdPartyFraming', $settingsArray);
        $this->assertSame($expected, $settingsArray['AllowThirdPartyFraming']);
    }

    /** @return iterable<string, array{mixed, bool|string}> */
    public static function valuesForAllowThirdPartyFramingProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value 3' => ['sameorigin', 'sameorigin'];
        yield 'valid value with type coercion' => [1, true];
    }

    /** @dataProvider valuesForBlowfishSecretProvider */
    public function testBlowfishSecret(mixed $actual, string $expected): void
    {
        $settings = new Settings(['blowfish_secret' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->blowfish_secret);
        $this->assertArrayHasKey('blowfish_secret', $settingsArray);
        $this->assertSame($expected, $settingsArray['blowfish_secret']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForBlowfishSecretProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['blowfish_secret', 'blowfish_secret'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /**
     * @param array<int, Server> $expected
     *
     * @dataProvider valuesForServersProvider
     */
    public function testServers(mixed $actual, array $expected): void
    {
        $settings = new Settings(['Servers' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertEquals($expected, $settings->Servers);
        $this->assertArrayHasKey('Servers', $settingsArray);
        $expectedArray = array_map(static fn ($server) => $server->asArray(), $expected);
        $this->assertSame($expectedArray, $settingsArray['Servers']);
    }

    /** @return iterable<string, array{mixed, array<int, Server>}> */
    public static function valuesForServersProvider(): iterable
    {
        $server = new Server();

        yield 'null value' => [null, [1 => $server]];
        yield 'valid value' => [[1 => [], 2 => []], [1 => $server, 2 => $server]];
        yield 'valid value 2' => [[2 => ['host' => 'test']], [2 => new Server(['host' => 'test'])]];
        yield 'valid value 3' => [
            [4 => ['host' => '', 'verbose' => '']],
            [4 => new Server(['host' => '', 'verbose' => 'Server 4'])],
        ];

        yield 'invalid value' => ['invalid', [1 => $server]];
        yield 'invalid value 2' => [[0 => [], 2 => 'invalid', 'invalid' => [], 4 => []], [4 => $server]];
        yield 'invalid value 3' => [[0 => []], [1 => $server]];
    }

    /** @dataProvider valuesForServerDefaultProvider */
    public function testServerDefault(mixed $actual, int $expected): void
    {
        $settings = new Settings(['ServerDefault' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ServerDefault);
        $this->assertArrayHasKey('ServerDefault', $settingsArray);
        $this->assertSame($expected, $settingsArray['ServerDefault']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForServerDefaultProvider(): iterable
    {
        yield 'null value' => [null, 1];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['0', 0];
        yield 'invalid value' => [-1, 1];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testVersionCheck(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['VersionCheck' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->VersionCheck);
        $this->assertArrayHasKey('VersionCheck', $settingsArray);
        $this->assertSame($expected, $settingsArray['VersionCheck']);
    }

    /** @dataProvider valuesForProxyUrlProvider */
    public function testProxyUrl(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyUrl' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ProxyUrl);
        $this->assertArrayHasKey('ProxyUrl', $settingsArray);
        $this->assertSame($expected, $settingsArray['ProxyUrl']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyUrlProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForProxyUserProvider */
    public function testProxyUser(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyUser' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ProxyUser);
        $this->assertArrayHasKey('ProxyUser', $settingsArray);
        $this->assertSame($expected, $settingsArray['ProxyUser']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyUserProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForProxyPassProvider */
    public function testProxyPass(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyPass' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ProxyPass);
        $this->assertArrayHasKey('ProxyPass', $settingsArray);
        $this->assertSame($expected, $settingsArray['ProxyPass']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyPassProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider valuesForMaxDbListProvider */
    public function testMaxDbList(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxDbList' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->MaxDbList);
        $this->assertArrayHasKey('MaxDbList', $settingsArray);
        $this->assertSame($expected, $settingsArray['MaxDbList']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxDbListProvider(): iterable
    {
        yield 'null value' => [null, 100];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 100];
    }

    /** @dataProvider valuesForMaxTableListProvider */
    public function testMaxTableList(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxTableList' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->MaxTableList);
        $this->assertArrayHasKey('MaxTableList', $settingsArray);
        $this->assertSame($expected, $settingsArray['MaxTableList']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxTableListProvider(): iterable
    {
        yield 'null value' => [null, 250];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 250];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testShowHint(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowHint' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ShowHint);
        $this->assertArrayHasKey('ShowHint', $settingsArray);
        $this->assertSame($expected, $settingsArray['ShowHint']);
    }

    /** @dataProvider valuesForMaxCharactersInDisplayedSQLProvider */
    public function testMaxCharactersInDisplayedSQL(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxCharactersInDisplayedSQL' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->MaxCharactersInDisplayedSQL);
        $this->assertArrayHasKey('MaxCharactersInDisplayedSQL', $settingsArray);
        $this->assertSame($expected, $settingsArray['MaxCharactersInDisplayedSQL']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxCharactersInDisplayedSQLProvider(): iterable
    {
        yield 'null value' => [null, 1000];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 1000];
    }

    /** @dataProvider valuesForOBGzipProvider */
    public function testOBGzip(mixed $actual, string|bool $expected): void
    {
        $settings = new Settings(['OBGzip' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->OBGzip);
        $this->assertArrayHasKey('OBGzip', $settingsArray);
        $this->assertSame($expected, $settingsArray['OBGzip']);
    }

    /** @return iterable<string, array{mixed, string|bool}> */
    public static function valuesForOBGzipProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value 3' => ['auto', 'auto'];
        yield 'valid value with type coercion' => [0, false];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testPersistentConnections(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['PersistentConnections' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->PersistentConnections);
        $this->assertArrayHasKey('PersistentConnections', $settingsArray);
        $this->assertSame($expected, $settingsArray['PersistentConnections']);
    }

    /** @dataProvider valuesForExecTimeLimitProvider */
    public function testExecTimeLimit(mixed $actual, int $expected): void
    {
        $settings = new Settings(['ExecTimeLimit' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->ExecTimeLimit);
        $this->assertArrayHasKey('ExecTimeLimit', $settingsArray);
        $this->assertSame($expected, $settingsArray['ExecTimeLimit']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForExecTimeLimitProvider(): iterable
    {
        yield 'null value' => [null, 300];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['0', 0];
        yield 'invalid value' => [-1, 300];
    }

    /** @dataProvider valuesForSessionSavePathProvider */
    public function testSessionSavePath(mixed $actual, string $expected): void
    {
        $settings = new Settings(['SessionSavePath' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->SessionSavePath);
        $this->assertArrayHasKey('SessionSavePath', $settingsArray);
        $this->assertSame($expected, $settingsArray['SessionSavePath']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSessionSavePathProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @dataProvider booleanWithDefaultFalseProvider */
    public function testShowAll(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowAll' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->showAll);
        $this->assertArrayHasKey('ShowAll', $settingsArray);
        $this->assertSame($expected, $settingsArray['ShowAll']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    /** @dataProvider valuesForMaxRowsProvider */
    public function testMaxRows(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxRows' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->maxRows);
        $this->assertArrayHasKey('MaxRows', $settingsArray);
        $this->assertSame($expected, $settingsArray['MaxRows']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxRowsProvider(): iterable
    {
        yield 'null value' => [null, 25];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['2', 2];
        yield 'invalid value' => [0, 25];
    }

    /** @dataProvider valuesForLimitCharsProvider */
    public function testLimitChars(mixed $actual, int $expected): void
    {
        $settings = new Settings(['LimitChars' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->limitChars);
        $this->assertArrayHasKey('LimitChars', $settingsArray);
        $this->assertSame($expected, $settingsArray['LimitChars']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForLimitCharsProvider(): iterable
    {
        yield 'null value' => [null, 50];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['2', 2];
        yield 'invalid value' => [0, 50];
    }

    /** @dataProvider valuesForRepeatCellsProvider */
    public function testRepeatCells(mixed $actual, int $expected): void
    {
        $settings = new Settings(['RepeatCells' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->repeatCells);
        $this->assertArrayHasKey('RepeatCells', $settingsArray);
        $this->assertSame($expected, $settingsArray['RepeatCells']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForRepeatCellsProvider(): iterable
    {
        yield 'null value' => [null, 100];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 100];
    }

    /** @dataProvider booleanWithDefaultTrueProvider */
    public function testZeroConf(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ZeroConf' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->zeroConf);
        $this->assertArrayHasKey('ZeroConf', $settingsArray);
        $this->assertSame($expected, $settingsArray['ZeroConf']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    /**
     * @param array{internal: int, human: string} $expected
     *
     * @dataProvider valuesForMysqlMinVersionProvider
     */
    public function testMysqlMinVersion(mixed $actual, array $expected): void
    {
        $settings = new Settings(['MysqlMinVersion' => $actual]);
        $settingsArray = $settings->asArray();
        $this->assertSame($expected, $settings->mysqlMinVersion);
        $this->assertArrayHasKey('MysqlMinVersion', $settingsArray);
        $this->assertSame($expected, $settingsArray['MysqlMinVersion']);
    }

    /** @return iterable<string, array{mixed, array{internal: int, human: string}}> */
    public static function valuesForMysqlMinVersionProvider(): iterable
    {
        yield 'null value' => [null, ['internal' => 50500, 'human' => '5.5.0']];
        yield 'valid value' => [
            ['internal' => 80026, 'human' => '8.0.26'],
            ['internal' => 80026, 'human' => '8.0.26'],
        ];

        yield 'valid value 2' => [[], ['internal' => 50500, 'human' => '5.5.0']];
        yield 'valid value with type coercion' => [
            ['internal' => '50500', 'human' => 550],
            ['internal' => 50500, 'human' => '550'],
        ];

        yield 'invalid value' => ['invalid', ['internal' => 50500, 'human' => '5.5.0']];
    }
}
