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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function array_merge;

use const DIRECTORY_SEPARATOR;
use const ROOT_PATH;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps, Generic.Files.LineLength.TooLong
#[CoversClass(Settings::class)]
#[CoversClass(Console::class)]
#[CoversClass(Debug::class)]
#[CoversClass(Export::class)]
#[CoversClass(Import::class)]
#[CoversClass(Schema::class)]
#[CoversClass(Server::class)]
#[CoversClass(SqlQueryBox::class)]
#[CoversClass(Transformations::class)]
class SettingsTest extends TestCase
{
    private const DEFAULT_VALUES = [
        'MemoryLimit' => '-1',
        'CookieSameSite' => 'Strict',
        'LoginCookieValidity' => 1440,
        'LoginCookieStore' => 0,
        'URLQueryEncryptionSecretKey' => '',
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
        'FirstLevelNavigationItems' => 100,
        'MaxNavigationItems' => 50,
        'NavigationTreeDbSeparator' => '_',
        'NavigationTreeTableSeparator' => '__',
        'NavigationTreeTableLevel' => 1,
        'NavigationLogoLink' => 'index.php',
        'NavigationLogoLinkWindow' => 'main',
        'NumRecentTables' => 10,
        'NumFavoriteTables' => 10,
        'NavigationTreeDisplayItemFilterMinimum' => 30,
        'NavigationTreeDisplayDbFilterMinimum' => 30,
        'NavigationTreeDefaultTabTable' => 'structure',
        'NavigationTreeDefaultTabTable2' => '',
        'NavigationWidth' => 240,
        'TableNavigationLinksMode' => 'icons',
        'Order' => 'SMART',
        'GridEditing' => 'double-click',
        'RelationalDisplay' => 'K',
        'ProtectBinary' => 'blob',
        'CharEditing' => 'input',
        'MinSizeForInputField' => 4,
        'MaxSizeForInputField' => 60,
        'InsertRows' => 2,
        'ForeignKeyDropdownOrder' => ['content-id', 'id-content'],
        'ForeignKeyMaxLimit' => 100,
        'DefaultForeignKeyChecks' => 'default',
        'TabsMode' => 'both',
        'ActionLinksMode' => 'both',
        'PropertiesNumColumns' => 1,
        'DefaultTabServer' => '/',
        'DefaultTabDatabase' => '/database/structure',
        'DefaultTabTable' => '/sql',
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
        'TextareaCols' => 40,
        'TextareaRows' => 15,
        'CharTextareaCols' => 40,
        'CharTextareaRows' => 7,
        'RowActionLinks' => 'left',
        'QueryHistoryMax' => 25,
        'MaxExactCount' => 50000,
        'MaxExactCountViews' => 0,
        'InitialSlidersState' => 'closed',
        'UserprefsDisallow' => [],
        'TitleTable' => '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@',
        'TitleDatabase' => '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@',
        'TitleServer' => '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@',
        'TitleDefault' => '@HTTP_HOST@ | @PHPMYADMIN@',
        'ThemeDefault' => 'pmahomme',
        'DefaultQueryTable' => 'SELECT * FROM @TABLE@ WHERE 1',
        'DefaultQueryDatabase' => '',
        'SQLQuery' => null,
        'UploadDir' => '',
        'SaveDir' => '',
        'TempDir' => ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR,
        'GD2Available' => 'auto',
        'TrustedProxies' => [],
        'LinkLengthLimit' => 1000,
        'CSPAllow' => '',
        'SendErrorReports' => 'ask',
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
        'Console' => null,
        'DefaultTransformations' => null,
        'FirstDayOfCalendar' => 0,
    ];

    /**
     * @param mixed[][] $values
     * @psalm-param (array{0: string, 1: mixed, 2: mixed})[] $values
     */
    #[DataProvider('providerForTestConstructor')]
    public function testConstructor(array $values): void
    {
        $actualValues = [];
        $expectedValues = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($values as $value) {
            $actualValues[$value[0]] = $value[1];
            $expectedValues[$value[0]] = $value[2];
        }

        $expected = array_merge(self::DEFAULT_VALUES, $expectedValues);
        $settings = new Settings($actualValues);
        $settingsArray = $settings->asArray();
        foreach (array_keys($expectedValues) as $key) {
            if ($key === 'Console') {
                self::assertInstanceOf(Console::class, $settings->Console);
                continue;
            }

            if ($key === 'Export') {
                self::assertInstanceOf(Export::class, $settings->Export);
                continue;
            }

            if ($key === 'Import') {
                self::assertInstanceOf(Import::class, $settings->Import);
                continue;
            }

            if ($key === 'Schema') {
                self::assertInstanceOf(Schema::class, $settings->Schema);
                continue;
            }

            if ($key === 'SQLQuery') {
                self::assertInstanceOf(SqlQueryBox::class, $settings->SQLQuery);
                continue;
            }

            if ($key === 'DefaultTransformations') {
                self::assertInstanceOf(Transformations::class, $settings->DefaultTransformations);
                continue;
            }

            self::assertSame($expected[$key], $settings->$key);
            self::assertArrayHasKey($key, $settingsArray);
            self::assertSame($expected[$key], $settingsArray[$key]);
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
                    ['MemoryLimit', null, '-1'],
                    ['CookieSameSite', null, 'Strict'],
                    ['LoginCookieValidity', null, 1440],
                    ['LoginCookieStore', null, 0],
                    ['URLQueryEncryptionSecretKey', null, ''],
                    ['ArbitraryServerRegexp', null, ''],
                    ['CaptchaMethod', null, 'invisible'],
                    ['CaptchaApi', null, 'https://www.google.com/recaptcha/api.js'],
                    ['CaptchaCsp', null, 'https://apis.google.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/'],
                    ['CaptchaRequestParam', null, 'g-recaptcha'],
                    ['CaptchaResponseParam', null, 'g-recaptcha-response'],
                    ['CaptchaLoginPublicKey', null, ''],
                    ['CaptchaLoginPrivateKey', null, ''],
                    ['CaptchaSiteVerifyURL', null, ''],
                    ['FirstLevelNavigationItems', null, 100],
                    ['MaxNavigationItems', null, 50],
                    ['NavigationTreeDbSeparator', null, '_'],
                    ['NavigationTreeTableSeparator', null, '__'],
                    ['NavigationTreeTableLevel', null, 1],
                    ['NavigationLogoLink', null, 'index.php'],
                    ['NavigationLogoLinkWindow', null, 'main'],
                    ['NumRecentTables', null, 10],
                    ['NumFavoriteTables', null, 10],
                    ['NavigationTreeDisplayItemFilterMinimum', null, 30],
                    ['NavigationTreeDisplayDbFilterMinimum', null, 30],
                    ['NavigationTreeDefaultTabTable', null, '/table/structure'],
                    ['NavigationTreeDefaultTabTable2', null, ''],
                    ['NavigationWidth', null, 240],
                    ['TableNavigationLinksMode', null, 'icons'],
                    ['Order', null, 'SMART'],
                    ['GridEditing', null, 'double-click'],
                    ['RelationalDisplay', null, 'K'],
                    ['ProtectBinary', null, 'blob'],
                    ['CharEditing', null, 'input'],
                    ['MinSizeForInputField', null, 4],
                    ['MaxSizeForInputField', null, 60],
                    ['InsertRows', null, 2],
                    ['ForeignKeyDropdownOrder', null, ['content-id', 'id-content']],
                    ['ForeignKeyMaxLimit', null, 100],
                    ['DefaultForeignKeyChecks', null, 'default'],
                    ['TabsMode', null, 'both'],
                    ['ActionLinksMode', null, 'both'],
                    ['PropertiesNumColumns', null, 1],
                    ['DefaultTabServer', null, '/'],
                    ['DefaultTabDatabase', null, '/database/structure'],
                    ['DefaultTabTable', null, '/sql'],
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
                    ['TextareaCols', null, 40],
                    ['TextareaRows', null, 15],
                    ['CharTextareaCols', null, 40],
                    ['CharTextareaRows', null, 7],
                    ['RowActionLinks', null, 'left'],
                    ['QueryHistoryMax', null, 25],
                    ['MaxExactCount', null, 50000],
                    ['MaxExactCountViews', null, 0],
                    ['InitialSlidersState', null, 'closed'],
                    ['UserprefsDisallow', null, []],
                    ['TitleTable', null, '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@'],
                    ['TitleDatabase', null, '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@'],
                    ['TitleServer', null, '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@'],
                    ['TitleDefault', null, '@HTTP_HOST@ | @PHPMYADMIN@'],
                    ['ThemeDefault', null, 'pmahomme'],
                    ['DefaultQueryTable', null, 'SELECT * FROM @TABLE@ WHERE 1'],
                    ['DefaultQueryDatabase', null, ''],
                    ['SQLQuery', null, null],
                    ['UploadDir', null, ''],
                    ['SaveDir', null, ''],
                    ['TempDir', null, ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR],
                    ['GD2Available', null, 'auto'],
                    ['TrustedProxies', null, []],
                    ['LinkLengthLimit', null, 1000],
                    ['CSPAllow', null, ''],
                    ['SendErrorReports', null, 'ask'],
                    ['environment', null, 'production'],
                    ['DefaultFunctions', null, ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW']],
                    ['maxRowPlotLimit', null, 500],
                    ['Console', null, null],
                    ['DefaultTransformations', null, null],
                    ['FirstDayOfCalendar', null, 0],
                ],
            ],
            'valid values' => [
                [
                    ['MemoryLimit', '16M', '16M'],
                    ['CookieSameSite', 'Lax', 'Lax'],
                    ['LoginCookieValidity', 1, 1],
                    ['LoginCookieStore', 0, 0],
                    ['URLQueryEncryptionSecretKey', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],
                    ['ArbitraryServerRegexp', 'test', 'test'],
                    ['CaptchaMethod', 'checkbox', 'checkbox'],
                    ['CaptchaApi', 'test', 'test'],
                    ['CaptchaCsp', 'test', 'test'],
                    ['CaptchaRequestParam', 'test', 'test'],
                    ['CaptchaResponseParam', 'test', 'test'],
                    ['CaptchaLoginPublicKey', 'test', 'test'],
                    ['CaptchaLoginPrivateKey', 'test', 'test'],
                    ['CaptchaSiteVerifyURL', 'test', 'test'],
                    ['FirstLevelNavigationItems', 1, 1],
                    ['MaxNavigationItems', 1, 1],
                    ['NavigationTreeDbSeparator', 'test', 'test'],
                    ['NavigationTreeTableSeparator', 'test', 'test'],
                    ['NavigationTreeTableLevel', 1, 1],
                    ['NavigationLogoLink', 'test', 'test'],
                    ['NavigationLogoLinkWindow', 'new', 'new'],
                    ['NumRecentTables', 0, 0],
                    ['NumFavoriteTables', 0, 0],
                    ['NavigationTreeDisplayItemFilterMinimum', 1, 1],
                    ['NavigationTreeDisplayDbFilterMinimum', 1, 1],
                    ['NavigationTreeDefaultTabTable', 'browse', '/sql'],
                    ['NavigationTreeDefaultTabTable2', 'browse', '/sql'],
                    ['NavigationWidth', 0, 0],
                    ['TableNavigationLinksMode', 'text', 'text'],
                    ['Order', 'ASC', 'ASC'],
                    ['GridEditing', 'click', 'click'],
                    ['RelationalDisplay', 'D', 'D'],
                    ['ProtectBinary', 'noblob', 'noblob'],
                    ['CharEditing', 'textarea', 'textarea'],
                    ['MinSizeForInputField', 0, 0],
                    ['MaxSizeForInputField', 1, 1],
                    ['InsertRows', 1, 1],
                    ['ForeignKeyDropdownOrder', ['id-content', 'content-id'], ['id-content', 'content-id']],
                    ['ForeignKeyMaxLimit', 1, 1],
                    ['DefaultForeignKeyChecks', 'enable', 'enable'],
                    ['TabsMode', 'icons', 'icons'],
                    ['ActionLinksMode', 'icons', 'icons'],
                    ['PropertiesNumColumns', 1, 1],
                    ['DefaultTabServer', 'privileges', '/server/privileges'],
                    ['DefaultTabDatabase', 'operations', '/database/operations'],
                    ['DefaultTabTable', 'structure', '/table/structure'],
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
                    ['TextareaCols', 1, 1],
                    ['TextareaRows', 1, 1],
                    ['CharTextareaCols', 1, 1],
                    ['CharTextareaRows', 1, 1],
                    ['RowActionLinks', 'none', 'none'],
                    ['QueryHistoryMax', 1, 1],
                    ['MaxExactCount', 1, 1],
                    ['MaxExactCountViews', 0, 0],
                    ['InitialSlidersState', 'open', 'open'],
                    ['UserprefsDisallow', ['DisableMultiTableMaintenance', 'Export/lock_tables'], ['DisableMultiTableMaintenance', 'Export/lock_tables']],
                    ['TitleTable', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleDatabase', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleServer', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['TitleDefault', '@PHPMYADMIN@', '@PHPMYADMIN@'],
                    ['ThemeDefault', 'test', 'test'],
                    ['DefaultQueryTable', 'test', 'test'],
                    ['DefaultQueryDatabase', 'test', 'test'],
                    ['SQLQuery', [], null],
                    ['UploadDir', 'test', 'test'],
                    ['SaveDir', 'test', 'test'],
                    ['TempDir', 'test', 'test'],
                    ['GD2Available', 'yes', 'yes'],
                    ['TrustedProxies', ['1.2.3.4' => 'HTTP_X_FORWARDED_FOR', 'key' => 'value'], ['1.2.3.4' => 'HTTP_X_FORWARDED_FOR', 'key' => 'value']],
                    ['LinkLengthLimit', 1, 1],
                    ['CSPAllow', 'phpmyadmin.net', 'phpmyadmin.net'],
                    ['SendErrorReports', 'never', 'never'],
                    ['environment', 'development', 'development'],
                    ['DefaultFunctions', ['key' => 'value', 'key2' => 'value2'], ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW', 'key' => 'value', 'key2' => 'value2']],
                    ['maxRowPlotLimit', 1, 1],
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
                    ['NavigationTreeDefaultTabTable', 'insert', '/table/change'],
                    ['NavigationTreeDefaultTabTable2', 'insert', '/table/change'],
                    ['TableNavigationLinksMode', 'both', 'both'],
                    ['Order', 'DESC', 'DESC'],
                    ['GridEditing', 'disabled', 'disabled'],
                    ['RelationalDisplay', 'K', 'K'],
                    ['ProtectBinary', 'all', 'all'],
                    ['CharEditing', 'input', 'input'],
                    ['ForeignKeyDropdownOrder', ['content-id', 'id-content'], ['content-id', 'id-content']],
                    ['DefaultForeignKeyChecks', 'disable', 'disable'],
                    ['TabsMode', 'text', 'text'],
                    ['ActionLinksMode', 'text', 'text'],
                    ['DefaultTabServer', 'welcome', '/'],
                    ['DefaultTabDatabase', 'structure', '/database/structure'],
                    ['DefaultTabTable', 'browse', '/sql'],
                    ['RowActionType', 'text', 'text'],
                    ['RecodingEngine', 'auto', 'auto'],
                    ['RowActionLinks', 'left', 'left'],
                    ['InitialSlidersState', 'closed', 'closed'],
                    ['GD2Available', 'auto', 'auto'],
                    ['TrustedProxies', [], []],
                    ['SendErrorReports', 'ask', 'ask'],
                    ['environment', 'production', 'production'],
                    ['DefaultFunctions', [], ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW']],
                    ['FirstDayOfCalendar', 0, 0],
                ],
            ],
            'valid values 3' => [
                [
                    ['CookieSameSite', 'Strict', 'Strict'],
                    ['NavigationTreeTableSeparator', [1234], ['1234']],
                    ['NavigationTreeDefaultTabTable', 'structure', '/table/structure'],
                    ['NavigationTreeDefaultTabTable2', 'structure', '/table/structure'],
                    ['TableNavigationLinksMode', 'icons', 'icons'],
                    ['Order', 'SMART', 'SMART'],
                    ['GridEditing', 'double-click', 'double-click'],
                    ['ProtectBinary', 'blob', 'blob'],
                    ['ForeignKeyDropdownOrder', ['content-id'], ['content-id']],
                    ['DefaultForeignKeyChecks', 'default', 'default'],
                    ['TabsMode', 'both', 'both'],
                    ['ActionLinksMode', 'both', 'both'],
                    ['DefaultTabServer', 'databases', '/server/databases'],
                    ['DefaultTabDatabase', 'sql', '/database/sql'],
                    ['DefaultTabTable', 'sql', '/table/sql'],
                    ['RowActionType', 'both', 'both'],
                    ['RecodingEngine', 'iconv', 'iconv'],
                    ['RowActionLinks', 'right', 'right'],
                    ['InitialSlidersState', 'disabled', 'disabled'],
                    ['GD2Available', 'no', 'no'],
                    ['SendErrorReports', 'always', 'always'],
                ],
            ],
            'valid values 4' => [
                [
                    ['NavigationTreeDefaultTabTable', 'sql', '/table/sql'],
                    ['NavigationTreeDefaultTabTable2', 'sql', '/table/sql'],
                    ['ProtectBinary', false, false],
                    ['ForeignKeyDropdownOrder', ['id-content'], ['id-content']],
                    ['DefaultTabServer', 'status', '/server/status'],
                    ['DefaultTabDatabase', 'search', '/database/search'],
                    ['DefaultTabTable', 'search', '/table/search'],
                    ['RecodingEngine', 'mb', 'mbstring'],
                    ['RowActionLinks', 'both', 'both'],
                ],
            ],
            'valid values 5' => [
                [
                    ['NavigationTreeDefaultTabTable', 'search', '/table/search'],
                    ['NavigationTreeDefaultTabTable2', 'search', '/table/search'],
                    ['DefaultTabServer', 'variables', '/server/variables'],
                    ['DefaultTabDatabase', 'db_structure.php', '/database/structure'],
                    ['DefaultTabTable', 'insert', '/table/change'],
                ],
            ],
            'valid values 6' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_structure.php', '/table/structure'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_structure.php', '/table/structure'],
                    ['DefaultTabServer', 'index.php', '/'],
                    ['DefaultTabDatabase', 'db_sql.php', '/database/sql'],
                    ['DefaultTabTable', 'tbl_structure.php', '/table/structure'],
                ],
            ],
            'valid values 7' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_sql.php', '/table/sql'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_sql.php', '/table/sql'],
                    ['DefaultTabServer', 'server_databases.php', '/server/databases'],
                    ['DefaultTabDatabase', 'db_search.php', '/database/search'],
                    ['DefaultTabTable', 'tbl_sql.php', '/table/sql'],
                ],
            ],
            'valid values 8' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_select.php', '/table/search'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_select.php', '/table/search'],
                    ['DefaultTabServer', 'server_status.php', '/server/status'],
                    ['DefaultTabDatabase', 'db_operations.php', '/database/operations'],
                    ['DefaultTabTable', 'tbl_select.php', '/table/search'],
                ],
            ],
            'valid values 9' => [
                [
                    ['NavigationTreeDefaultTabTable', 'tbl_change.php', '/table/change'],
                    ['NavigationTreeDefaultTabTable2', 'tbl_change.php', '/table/change'],
                    ['DefaultTabServer', 'server_variables.php', '/server/variables'],
                    ['DefaultTabTable', 'tbl_change.php', '/table/change'],
                ],
            ],
            'valid values 10' => [
                [
                    ['NavigationTreeDefaultTabTable', 'sql.php', '/sql'],
                    ['NavigationTreeDefaultTabTable2', 'sql.php', '/sql'],
                    ['DefaultTabServer', 'server_privileges.php', '/server/privileges'],
                    ['DefaultTabTable', 'sql.php', '/sql'],
                ],
            ],
            'valid values 11' => [[['NavigationTreeDefaultTabTable2', '', '']]],
            'valid values with type coercion' => [
                [
                    ['MemoryLimit', 1234, '1234'],
                    ['LoginCookieValidity', '1', 1],
                    ['LoginCookieStore', '1', 1],
                    ['ArbitraryServerRegexp', 1234, '1234'],
                    ['CaptchaApi', 1234, '1234'],
                    ['CaptchaCsp', 1234, '1234'],
                    ['CaptchaRequestParam', 1234, '1234'],
                    ['CaptchaResponseParam', 1234, '1234'],
                    ['CaptchaLoginPublicKey', 1234, '1234'],
                    ['CaptchaLoginPrivateKey', 1234, '1234'],
                    ['CaptchaSiteVerifyURL', 1234, '1234'],
                    ['FirstLevelNavigationItems', '1', 1],
                    ['MaxNavigationItems', '1', 1],
                    ['NavigationTreeDbSeparator', 1234, '1234'],
                    ['NavigationTreeTableSeparator', true, '1'],
                    ['NavigationTreeTableLevel', '2', 2],
                    ['NavigationLogoLink', 1234, '1234'],
                    ['NumRecentTables', '1', 1],
                    ['NumFavoriteTables', '1', 1],
                    ['NavigationTreeDisplayItemFilterMinimum', '1', 1],
                    ['NavigationTreeDisplayDbFilterMinimum', '1', 1],
                    ['NavigationWidth', '1', 1],
                    ['MinSizeForInputField', '0', 0],
                    ['MaxSizeForInputField', '1', 1],
                    ['InsertRows', '1', 1],
                    ['ForeignKeyMaxLimit', '1', 1],
                    ['PropertiesNumColumns', '2', 2],
                    ['PDFPageSizes', [1234 => 1234, 'test' => 'test'], ['1234', 'test']],
                    ['PDFDefaultPageSize', 1234, '1234'],
                    ['DefaultLang', 1234, '1234'],
                    ['DefaultConnectionCollation', 1234, '1234'],
                    ['Lang', 1234, '1234'],
                    ['FilterLanguages', 1234, '1234'],
                    ['IconvExtraParams', 1234, '1234'],
                    ['TextareaCols', '1', 1],
                    ['TextareaRows', '1', 1],
                    ['CharTextareaCols', '1', 1],
                    ['CharTextareaRows', '1', 1],
                    ['QueryHistoryMax', '1', 1],
                    ['MaxExactCount', '1', 1],
                    ['MaxExactCountViews', '1', 1],
                    ['UserprefsDisallow', [1234 => 1234, 'test' => 'test'], ['1234', 'test']],
                    ['TitleTable', 1234, '1234'],
                    ['TitleDatabase', 1234, '1234'],
                    ['TitleServer', 1234, '1234'],
                    ['TitleDefault', 1234, '1234'],
                    ['ThemeDefault', 1234, '1234'],
                    ['DefaultQueryTable', 1234, '1234'],
                    ['DefaultQueryDatabase', 1234, '1234'],
                    ['UploadDir', 1234, '1234'],
                    ['SaveDir', 1234, '1234'],
                    ['TempDir', 1234, '1234'],
                    ['TrustedProxies', ['test' => 1234], ['test' => '1234']],
                    ['LinkLengthLimit', '1', 1],
                    ['CSPAllow', 1234, '1234'],
                    ['DefaultFunctions', ['FUNC_UUID' => 1234], ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => '1234', 'first_timestamp' => 'NOW']],
                    ['maxRowPlotLimit', '1', 1],
                    ['FirstDayOfCalendar', '1', 1],
                ],
            ],
            'invalid values' => [
                [
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
                    ['NavigationTreeDefaultTabTable', 'invalid', '/table/structure'],
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
                    ['DefaultTabServer', 'invalid', '/'],
                    ['DefaultTabDatabase', 'invalid', '/database/structure'],
                    ['DefaultTabTable', 'invalid', '/sql'],
                    ['RowActionType', 'invalid', 'both'],
                    ['PDFPageSizes', 'invalid', ['A3', 'A4', 'A5', 'letter', 'legal']],
                    ['RecodingEngine', 'invalid', 'auto'],
                    ['TextareaCols', 0, 40],
                    ['TextareaRows', 0, 15],
                    ['CharTextareaCols', 0, 40],
                    ['CharTextareaRows', 0, 7],
                    ['RowActionLinks', 'invalid', 'left'],
                    ['QueryHistoryMax', 0, 25],
                    ['MaxExactCount', 0, 50000],
                    ['MaxExactCountViews', -1, 0],
                    ['InitialSlidersState', 'invalid', 'closed'],
                    ['UserprefsDisallow', 'invalid', []],
                    ['SQLQuery', 'invalid', null],
                    ['GD2Available', 'invalid', 'auto'],
                    ['TrustedProxies', 'invalid', []],
                    ['LinkLengthLimit', 0, 1000],
                    ['SendErrorReports', 'invalid', 'ask'],
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
                    ['DefaultFunctions', [1234 => 'invalid', 'valid' => 'valid'], ['FUNC_CHAR' => '', 'FUNC_DATE' => '', 'FUNC_NUMBER' => '', 'FUNC_SPATIAL' => 'GeomFromText', 'FUNC_UUID' => 'UUID', 'first_timestamp' => 'NOW', 'valid' => 'valid']],
                    ['FirstDayOfCalendar', -1, 0],
                    ['RecodingEngine', 'recode', 'auto'],
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

    #[DataProvider('valuesForPmaAbsoluteUriProvider')]
    public function testPmaAbsoluteUri(mixed $actual, string $expected): void
    {
        $settings = new Settings(['PmaAbsoluteUri' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->PmaAbsoluteUri);
        self::assertSame($expected, $settingsArray['PmaAbsoluteUri']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForPmaAbsoluteUriProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['https://www.phpmyadmin.net/', 'https://www.phpmyadmin.net/'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForAuthLogProvider')]
    public function testAuthLog(mixed $actual, string $expected): void
    {
        $settings = new Settings(['AuthLog' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->authLog);
        self::assertSame($expected, $settingsArray['AuthLog']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForAuthLogProvider(): iterable
    {
        yield 'null value' => [null, 'auto'];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['/path/to/file', '/path/to/file'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testAuthLogSuccess(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['AuthLogSuccess' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->authLogSuccess);
        self::assertSame($expected, $settingsArray['AuthLogSuccess']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testPmaNoRelationDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['PmaNoRelation_DisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->PmaNoRelation_DisableWarning);
        self::assertSame($expected, $settingsArray['PmaNoRelation_DisableWarning']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSuhosinDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['SuhosinDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->SuhosinDisableWarning);
        self::assertSame($expected, $settingsArray['SuhosinDisableWarning']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testLoginCookieValidityDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LoginCookieValidityDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->LoginCookieValidityDisableWarning);
        self::assertSame($expected, $settingsArray['LoginCookieValidityDisableWarning']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testReservedWordDisableWarning(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ReservedWordDisableWarning' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ReservedWordDisableWarning);
        self::assertSame($expected, $settingsArray['ReservedWordDisableWarning']);
    }

    #[DataProvider('valuesForTranslationWarningThresholdProvider')]
    public function testTranslationWarningThreshold(mixed $actual, int $expected): void
    {
        $settings = new Settings(['TranslationWarningThreshold' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->TranslationWarningThreshold);
        self::assertSame($expected, $settingsArray['TranslationWarningThreshold']);
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

    #[DataProvider('valuesForAllowThirdPartyFramingProvider')]
    public function testAllowThirdPartyFraming(mixed $actual, bool|string $expected): void
    {
        $settings = new Settings(['AllowThirdPartyFraming' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->AllowThirdPartyFraming);
        self::assertSame($expected, $settingsArray['AllowThirdPartyFraming']);
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

    #[DataProvider('valuesForBlowfishSecretProvider')]
    public function testBlowfishSecret(mixed $actual, string $expected): void
    {
        $settings = new Settings(['blowfish_secret' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->blowfish_secret);
        self::assertSame($expected, $settingsArray['blowfish_secret']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForBlowfishSecretProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['blowfish_secret', 'blowfish_secret'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    /** @param array<int, Server> $expected */
    #[DataProvider('valuesForServersProvider')]
    public function testServers(mixed $actual, array $expected): void
    {
        $settings = new Settings(['Servers' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertEquals($expected, $settings->Servers);
        $expectedArray = array_map(static fn (Server $server): array => $server->asArray(), $expected);
        self::assertSame($expectedArray, $settingsArray['Servers']);
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
        yield 'invalid value 3' => [[[]], [1 => $server]];
    }

    #[DataProvider('valuesForServerDefaultProvider')]
    public function testServerDefault(mixed $actual, int $expected): void
    {
        $settings = new Settings(['ServerDefault' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ServerDefault);
        self::assertSame($expected, $settingsArray['ServerDefault']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForServerDefaultProvider(): iterable
    {
        yield 'null value' => [null, 1];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['0', 0];
        yield 'invalid value' => [-1, 1];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testVersionCheck(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['VersionCheck' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->VersionCheck);
        self::assertSame($expected, $settingsArray['VersionCheck']);
    }

    #[DataProvider('valuesForProxyUrlProvider')]
    public function testProxyUrl(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyUrl' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ProxyUrl);
        self::assertSame($expected, $settingsArray['ProxyUrl']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyUrlProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForProxyUserProvider')]
    public function testProxyUser(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyUser' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ProxyUser);
        self::assertSame($expected, $settingsArray['ProxyUser']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyUserProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForProxyPassProvider')]
    public function testProxyPass(mixed $actual, string $expected): void
    {
        $settings = new Settings(['ProxyPass' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ProxyPass);
        self::assertSame($expected, $settingsArray['ProxyPass']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForProxyPassProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['', ''];
        yield 'valid value 2' => ['test', 'test'];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('valuesForMaxDbListProvider')]
    public function testMaxDbList(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxDbList' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->MaxDbList);
        self::assertSame($expected, $settingsArray['MaxDbList']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxDbListProvider(): iterable
    {
        yield 'null value' => [null, 100];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 100];
    }

    #[DataProvider('valuesForMaxTableListProvider')]
    public function testMaxTableList(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxTableList' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->MaxTableList);
        self::assertSame($expected, $settingsArray['MaxTableList']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxTableListProvider(): iterable
    {
        yield 'null value' => [null, 250];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 250];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowHint(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowHint' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowHint);
        self::assertSame($expected, $settingsArray['ShowHint']);
    }

    #[DataProvider('valuesForMaxCharactersInDisplayedSQLProvider')]
    public function testMaxCharactersInDisplayedSQL(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxCharactersInDisplayedSQL' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->MaxCharactersInDisplayedSQL);
        self::assertSame($expected, $settingsArray['MaxCharactersInDisplayedSQL']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxCharactersInDisplayedSQLProvider(): iterable
    {
        yield 'null value' => [null, 1000];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [0, 1000];
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

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testPersistentConnections(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['PersistentConnections' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->PersistentConnections);
        self::assertSame($expected, $settingsArray['PersistentConnections']);
    }

    #[DataProvider('valuesForExecTimeLimitProvider')]
    public function testExecTimeLimit(mixed $actual, int $expected): void
    {
        $settings = new Settings(['ExecTimeLimit' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ExecTimeLimit);
        self::assertSame($expected, $settingsArray['ExecTimeLimit']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForExecTimeLimitProvider(): iterable
    {
        yield 'null value' => [null, 300];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['0', 0];
        yield 'invalid value' => [-1, 300];
    }

    #[DataProvider('valuesForSessionSavePathProvider')]
    public function testSessionSavePath(mixed $actual, string $expected): void
    {
        $settings = new Settings(['SessionSavePath' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->SessionSavePath);
        self::assertSame($expected, $settingsArray['SessionSavePath']);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function valuesForSessionSavePathProvider(): iterable
    {
        yield 'null value' => [null, ''];
        yield 'valid value' => ['test', 'test'];
        yield 'valid value 2' => ['', ''];
        yield 'valid value with type coercion' => [1234, '1234'];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowAll(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowAll' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->showAll);
        self::assertSame($expected, $settingsArray['ShowAll']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultFalseProvider(): iterable
    {
        yield 'null value' => [null, false];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value with type coercion' => [1, true];
    }

    #[DataProvider('valuesForMaxRowsProvider')]
    public function testMaxRows(mixed $actual, int $expected): void
    {
        $settings = new Settings(['MaxRows' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->maxRows);
        self::assertSame($expected, $settingsArray['MaxRows']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForMaxRowsProvider(): iterable
    {
        yield 'null value' => [null, 25];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['2', 2];
        yield 'invalid value' => [0, 25];
    }

    #[DataProvider('valuesForLimitCharsProvider')]
    public function testLimitChars(mixed $actual, int $expected): void
    {
        $settings = new Settings(['LimitChars' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->limitChars);
        self::assertSame($expected, $settingsArray['LimitChars']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForLimitCharsProvider(): iterable
    {
        yield 'null value' => [null, 50];
        yield 'valid value' => [1, 1];
        yield 'valid value with type coercion' => ['2', 2];
        yield 'invalid value' => [0, 50];
    }

    #[DataProvider('valuesForRepeatCellsProvider')]
    public function testRepeatCells(mixed $actual, int $expected): void
    {
        $settings = new Settings(['RepeatCells' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->repeatCells);
        self::assertSame($expected, $settingsArray['RepeatCells']);
    }

    /** @return iterable<string, array{mixed, int}> */
    public static function valuesForRepeatCellsProvider(): iterable
    {
        yield 'null value' => [null, 100];
        yield 'valid value' => [0, 0];
        yield 'valid value with type coercion' => ['1', 1];
        yield 'invalid value' => [-1, 100];
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testZeroConf(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ZeroConf' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->zeroConf);
        self::assertSame($expected, $settingsArray['ZeroConf']);
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function booleanWithDefaultTrueProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [true, true];
        yield 'valid value 2' => [false, false];
        yield 'valid value with type coercion' => [0, false];
    }

    /** @param array<string, bool> $expected */
    #[DataProvider('valuesForDebugProvider')]
    public function testDebug(mixed $actual, array $expected): void
    {
        $settings = new Settings(['DBG' => $actual]);
        $settingsArray = $settings->asArray();
        $expectedDebug = new Debug($expected);
        self::assertEquals($expectedDebug, $settings->debug);
        self::assertSame($expectedDebug->asArray(), $settingsArray['DBG']);
    }

    /** @return iterable<string, array{mixed, array<string, bool>}> */
    public static function valuesForDebugProvider(): iterable
    {
        yield 'null value' => [null, []];
        yield 'valid value' => [[], []];
        yield 'valid value 2' => [['demo' => true], ['demo' => true]];
        yield 'invalid value' => ['invalid', []];
    }

    /** @param array{internal: int, human: string} $expected */
    #[DataProvider('valuesForMysqlMinVersionProvider')]
    public function testMysqlMinVersion(mixed $actual, array $expected): void
    {
        $settings = new Settings(['MysqlMinVersion' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->mysqlMinVersion);
        self::assertSame($expected, $settingsArray['MysqlMinVersion']);
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

    #[DataProvider('valuesForTablePrimaryKeyOrderProvider')]
    public function testTablePrimaryKeyOrder(mixed $actual, string $expected): void
    {
        $settings = new Settings(['TablePrimaryKeyOrder' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->TablePrimaryKeyOrder);
        self::assertSame($expected, $settingsArray['TablePrimaryKeyOrder']);
    }

    /** @return iterable<string, array{mixed, non-empty-string}> */
    public static function valuesForTablePrimaryKeyOrderProvider(): iterable
    {
        yield 'null value' => [null, 'NONE'];
        yield 'valid value' => ['ASC', 'ASC'];
        yield 'valid value 2' => ['DESC', 'DESC'];
        yield 'valid value 3' => ['NONE', 'NONE'];
        yield 'valid value 4' => ['asc', 'ASC'];
        yield 'valid value 5' => ['desc', 'DESC'];
        yield 'invalid value' => ['invalid', 'NONE'];
    }

    /** @param list<non-empty-string> $expected */
    #[DataProvider('valuesForMysqlSslWarningSafeHostsProvider')]
    public function testMysqlSslWarningSafeHosts(mixed $actual, array $expected): void
    {
        $settings = new Settings(['MysqlSslWarningSafeHosts' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->MysqlSslWarningSafeHosts);
        self::assertSame($expected, $settingsArray['MysqlSslWarningSafeHosts']);
    }

    /** @return iterable<string, array{mixed, list<non-empty-string>}> */
    public static function valuesForMysqlSslWarningSafeHostsProvider(): iterable
    {
        yield 'null value' => [null, ['127.0.0.1', 'localhost']];
        yield 'valid value' => [['local.host', '::1'], ['local.host', '::1']];
        yield 'valid value 2' => [['127.0.0.1', 'localhost'], ['127.0.0.1', 'localhost']];
        yield 'valid value 3' => [[], []];
        yield 'valid value with type coercion' => [
            ['127.0.0.1' => 'local', 4321 => 1234, true],
            ['local', '1234', '1'],
        ];

        yield 'invalid value' => ['invalid', ['127.0.0.1', 'localhost']];
        yield 'invalid list values' => [[false, [], ['localhost'], '', null, 'localhost'], ['localhost']];
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSkipLockedTables(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['SkipLockedTables' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->SkipLockedTables);
        self::assertSame($expected, $settingsArray['SkipLockedTables']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testRetainQueryBox(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['RetainQueryBox' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->RetainQueryBox);
        self::assertSame($expected, $settingsArray['RetainQueryBox']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testAllowUserDropDatabase(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['AllowUserDropDatabase' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->AllowUserDropDatabase);
        self::assertSame($expected, $settingsArray['AllowUserDropDatabase']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testIgnoreMultiSubmitErrors(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['IgnoreMultiSubmitErrors' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->IgnoreMultiSubmitErrors);
        self::assertSame($expected, $settingsArray['IgnoreMultiSubmitErrors']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testURLQueryEncryption(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['URLQueryEncryption' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->URLQueryEncryption);
        self::assertSame($expected, $settingsArray['URLQueryEncryption']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testAllowArbitraryServer(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['AllowArbitraryServer' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->AllowArbitraryServer);
        self::assertSame($expected, $settingsArray['AllowArbitraryServer']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testDisplayServersList(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['DisplayServersList' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->DisplayServersList);
        self::assertSame($expected, $settingsArray['DisplayServersList']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowPhpInfo(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowPhpInfo' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowPhpInfo);
        self::assertSame($expected, $settingsArray['ShowPhpInfo']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowDbStructureCharset(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDbStructureCharset' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDbStructureCharset);
        self::assertSame($expected, $settingsArray['ShowDbStructureCharset']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowDbStructureComment(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDbStructureComment' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDbStructureComment);
        self::assertSame($expected, $settingsArray['ShowDbStructureComment']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowDbStructureCreation(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDbStructureCreation' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDbStructureCreation);
        self::assertSame($expected, $settingsArray['ShowDbStructureCreation']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowDbStructureLastUpdate(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDbStructureLastUpdate' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDbStructureLastUpdate);
        self::assertSame($expected, $settingsArray['ShowDbStructureLastUpdate']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testShowDbStructureLastCheck(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDbStructureLastCheck' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDbStructureLastCheck);
        self::assertSame($expected, $settingsArray['ShowDbStructureLastCheck']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testSaveCellsAtOnce(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['SaveCellsAtOnce' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->SaveCellsAtOnce);
        self::assertSame($expected, $settingsArray['SaveCellsAtOnce']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testTextareaAutoSelect(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['TextareaAutoSelect' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->TextareaAutoSelect);
        self::assertSame($expected, $settingsArray['TextareaAutoSelect']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testRowActionLinksWithoutUnique(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['RowActionLinksWithoutUnique' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->RowActionLinksWithoutUnique);
        self::assertSame($expected, $settingsArray['RowActionLinksWithoutUnique']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testQueryHistoryDB(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['QueryHistoryDB' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->QueryHistoryDB);
        self::assertSame($expected, $settingsArray['QueryHistoryDB']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testUserprefsDeveloperTab(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['UserprefsDeveloperTab' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->UserprefsDeveloperTab);
        self::assertSame($expected, $settingsArray['UserprefsDeveloperTab']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testThemePerServer(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ThemePerServer' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ThemePerServer);
        self::assertSame($expected, $settingsArray['ThemePerServer']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testDisableMultiTableMaintenance(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['DisableMultiTableMaintenance' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->DisableMultiTableMaintenance);
        self::assertSame($expected, $settingsArray['DisableMultiTableMaintenance']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testConsoleEnterExecutes(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ConsoleEnterExecutes' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ConsoleEnterExecutes);
        self::assertSame($expected, $settingsArray['ConsoleEnterExecutes']);
    }

    #[DataProvider('booleanWithDefaultFalseProvider')]
    public function testDisableShortcutKeys(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['DisableShortcutKeys' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->DisableShortcutKeys);
        self::assertSame($expected, $settingsArray['DisableShortcutKeys']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowSQL(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowSQL' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowSQL);
        self::assertSame($expected, $settingsArray['ShowSQL']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testCodemirrorEnable(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['CodemirrorEnable' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->CodemirrorEnable);
        self::assertSame($expected, $settingsArray['CodemirrorEnable']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLintEnable(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LintEnable' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->LintEnable);
        self::assertSame($expected, $settingsArray['LintEnable']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testConfirm(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['Confirm' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->Confirm);
        self::assertSame($expected, $settingsArray['Confirm']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLoginCookieRecall(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LoginCookieRecall' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->LoginCookieRecall);
        self::assertSame($expected, $settingsArray['LoginCookieRecall']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLoginCookieDeleteAll(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LoginCookieDeleteAll' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->LoginCookieDeleteAll);
        self::assertSame($expected, $settingsArray['LoginCookieDeleteAll']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testUseDbSearch(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['UseDbSearch' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->UseDbSearch);
        self::assertSame($expected, $settingsArray['UseDbSearch']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testEnableDragDropImport(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['enable_drag_drop_import' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->enable_drag_drop_import);
        self::assertSame($expected, $settingsArray['enable_drag_drop_import']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowDatabasesNavigationAsTree(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowDatabasesNavigationAsTree' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowDatabasesNavigationAsTree);
        self::assertSame($expected, $settingsArray['ShowDatabasesNavigationAsTree']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeEnableGrouping(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeEnableGrouping' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeEnableGrouping);
        self::assertSame($expected, $settingsArray['NavigationTreeEnableGrouping']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationLinkWithMainPanel(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationLinkWithMainPanel' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationLinkWithMainPanel);
        self::assertSame($expected, $settingsArray['NavigationLinkWithMainPanel']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationDisplayLogo(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationDisplayLogo' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationDisplayLogo);
        self::assertSame($expected, $settingsArray['NavigationDisplayLogo']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationDisplayServers(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationDisplayServers' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationDisplayServers);
        self::assertSame($expected, $settingsArray['NavigationDisplayServers']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeEnableExpansion(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeEnableExpansion' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeEnableExpansion);
        self::assertSame($expected, $settingsArray['NavigationTreeEnableExpansion']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeShowTables(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeShowTables' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeShowTables);
        self::assertSame($expected, $settingsArray['NavigationTreeShowTables']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeShowViews(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeShowViews' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeShowViews);
        self::assertSame($expected, $settingsArray['NavigationTreeShowViews']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeShowFunctions(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeShowFunctions' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeShowFunctions);
        self::assertSame($expected, $settingsArray['NavigationTreeShowFunctions']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeShowProcedures(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeShowProcedures' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeShowProcedures);
        self::assertSame($expected, $settingsArray['NavigationTreeShowProcedures']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeShowEvents(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeShowEvents' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeShowEvents);
        self::assertSame($expected, $settingsArray['NavigationTreeShowEvents']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreeAutoexpandSingleDb(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreeAutoexpandSingleDb' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreeAutoexpandSingleDb);
        self::assertSame($expected, $settingsArray['NavigationTreeAutoexpandSingleDb']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowStats(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowStats' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowStats);
        self::assertSame($expected, $settingsArray['ShowStats']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowChgPassword(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowChgPassword' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowChgPassword);
        self::assertSame($expected, $settingsArray['ShowChgPassword']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowCreateDb(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowCreateDb' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowCreateDb);
        self::assertSame($expected, $settingsArray['ShowCreateDb']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testHideStructureActions(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['HideStructureActions' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->HideStructureActions);
        self::assertSame($expected, $settingsArray['HideStructureActions']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowColumnComments(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowColumnComments' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowColumnComments);
        self::assertSame($expected, $settingsArray['ShowColumnComments']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowFunctionFields(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowFunctionFields' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowFunctionFields);
        self::assertSame($expected, $settingsArray['ShowFunctionFields']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowFieldTypesInDataEditView(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowFieldTypesInDataEditView' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowFieldTypesInDataEditView);
        self::assertSame($expected, $settingsArray['ShowFieldTypesInDataEditView']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testZipDump(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ZipDump' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ZipDump);
        self::assertSame($expected, $settingsArray['ZipDump']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testGZipDump(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['GZipDump' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->GZipDump);
        self::assertSame($expected, $settingsArray['GZipDump']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testBZipDump(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['BZipDump' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->BZipDump);
        self::assertSame($expected, $settingsArray['BZipDump']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testCompressOnFly(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['CompressOnFly' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->CompressOnFly);
        self::assertSame($expected, $settingsArray['CompressOnFly']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNavigationTreePointerEnable(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NavigationTreePointerEnable' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NavigationTreePointerEnable);
        self::assertSame($expected, $settingsArray['NavigationTreePointerEnable']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testBrowsePointerEnable(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['BrowsePointerEnable' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->BrowsePointerEnable);
        self::assertSame($expected, $settingsArray['BrowsePointerEnable']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testBrowseMarkerEnable(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['BrowseMarkerEnable' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->BrowseMarkerEnable);
        self::assertSame($expected, $settingsArray['BrowseMarkerEnable']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testLongtextDoubleTextarea(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['LongtextDoubleTextarea' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->LongtextDoubleTextarea);
        self::assertSame($expected, $settingsArray['LongtextDoubleTextarea']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testRememberSorting(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['RememberSorting' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->RememberSorting);
        self::assertSame($expected, $settingsArray['RememberSorting']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowBrowseComments(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowBrowseComments' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowBrowseComments);
        self::assertSame($expected, $settingsArray['ShowBrowseComments']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowPropertyComments(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowPropertyComments' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowPropertyComments);
        self::assertSame($expected, $settingsArray['ShowPropertyComments']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testAllowSharedBookmarks(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['AllowSharedBookmarks' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->AllowSharedBookmarks);
        self::assertSame($expected, $settingsArray['AllowSharedBookmarks']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testBrowseMIME(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['BrowseMIME' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->BrowseMIME);
        self::assertSame($expected, $settingsArray['BrowseMIME']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testNaturalOrder(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['NaturalOrder' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->NaturalOrder);
        self::assertSame($expected, $settingsArray['NaturalOrder']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testThemeManager(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ThemeManager' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ThemeManager);
        self::assertSame($expected, $settingsArray['ThemeManager']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testEnableAutocompleteForTablesAndColumns(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['EnableAutocompleteForTablesAndColumns' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->EnableAutocompleteForTablesAndColumns);
        self::assertSame($expected, $settingsArray['EnableAutocompleteForTablesAndColumns']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testCheckConfigurationPermissions(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['CheckConfigurationPermissions' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->CheckConfigurationPermissions);
        self::assertSame($expected, $settingsArray['CheckConfigurationPermissions']);
    }

    #[DataProvider('booleanWithDefaultTrueProvider')]
    public function testShowGitRevision(mixed $actual, bool $expected): void
    {
        $settings = new Settings(['ShowGitRevision' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowGitRevision);
        self::assertSame($expected, $settingsArray['ShowGitRevision']);
    }

    #[DataProvider('valuesForShowServerInfoProvider')]
    public function testShowServerInfo(mixed $actual, bool|string $expected): void
    {
        $settings = new Settings(['ShowServerInfo' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->ShowServerInfo);
        self::assertSame($expected, $settingsArray['ShowServerInfo']);
    }

    /** @return iterable<string, array{mixed, (bool|'database-server'|'web-server')}> */
    public static function valuesForShowServerInfoProvider(): iterable
    {
        yield 'null value' => [null, true];
        yield 'valid value' => [false, false];
        yield 'valid value 2' => [true, true];
        yield 'valid value 3' => ['database-server', 'database-server'];
        yield 'valid value 4' => ['web-server', 'web-server'];
        yield 'valid value with type coercion' => [0, false];
    }

    /** @param list<string> $expected */
    #[DataProvider('valuesForAvailableCharsetsProvider')]
    public function testAvailableCharsets(mixed $actual, array $expected): void
    {
        $settings = new Settings(['AvailableCharsets' => $actual]);
        $settingsArray = $settings->asArray();
        self::assertSame($expected, $settings->AvailableCharsets);
        self::assertSame($expected, $settingsArray['AvailableCharsets']);
    }

    /** @return iterable<string, array{mixed, list<string>}> */
    public static function valuesForAvailableCharsetsProvider(): iterable
    {
        $defaultValues = ['iso-8859-1', 'iso-8859-2', 'iso-8859-3', 'iso-8859-4', 'iso-8859-5', 'iso-8859-6', 'iso-8859-7', 'iso-8859-8', 'iso-8859-9', 'iso-8859-10', 'iso-8859-11', 'iso-8859-12', 'iso-8859-13', 'iso-8859-14', 'iso-8859-15', 'windows-1250', 'windows-1251', 'windows-1252', 'windows-1256', 'windows-1257', 'koi8-r', 'big5', 'gb2312', 'utf-16', 'utf-8', 'utf-7', 'x-user-defined', 'euc-jp', 'ks_c_5601-1987', 'tis-620', 'SHIFT_JIS', 'SJIS', 'SJIS-win'];

        yield 'null value' => [null, $defaultValues];
        yield 'valid value' => [['utf-8', 'iso-8859-1'], ['utf-8', 'iso-8859-1']];
        yield 'valid value 2' => [$defaultValues, $defaultValues];
        yield 'valid value 3' => [[], []];
        yield 'valid value with type coercion' => [[4321 => 1234, 'test' => 'test', true], ['1234', 'test', '1']];
        yield 'invalid value' => ['invalid', $defaultValues];
        yield 'invalid list values' => [[[], ['utf-8'], 'utf-8'], ['utf-8']];
    }
}
