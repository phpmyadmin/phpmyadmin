<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config\Settings;

use PhpMyAdmin\Config\Settings\Server;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_merge;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * @covers \PhpMyAdmin\Config\Settings\Server
 */
class ServerTest extends TestCase
{
    /** @var array<string, array|bool|int|string|null> */
    private $defaultValues = [
        'host' => 'localhost',
        'port' => '',
        'socket' => '',
        'ssl' => false,
        'ssl_key' => null,
        'ssl_cert' => null,
        'ssl_ca' => null,
        'ssl_ca_path' => null,
        'ssl_ciphers' => null,
        'ssl_verify' => true,
        'compress' => false,
        'controlhost' => '',
        'controlport' => '',
        'controluser' => '',
        'controlpass' => '',
        'auth_type' => 'cookie',
        'auth_http_realm' => '',
        'user' => 'root',
        'password' => '',
        'SignonSession' => '',
        'SignonCookieParams' => ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false],
        'SignonScript' => '',
        'SignonURL' => '',
        'LogoutURL' => '',
        'only_db' => '',
        'hide_db' => '',
        'verbose' => '',
        'pmadb' => '',
        'bookmarktable' => '',
        'relation' => '',
        'table_info' => '',
        'table_coords' => '',
        'pdf_pages' => '',
        'column_info' => '',
        'history' => '',
        'recent' => '',
        'favorite' => '',
        'table_uiprefs' => '',
        'tracking' => '',
        'userconfig' => '',
        'users' => '',
        'usergroups' => '',
        'navigationhiding' => '',
        'savedsearches' => '',
        'central_columns' => '',
        'designer_settings' => '',
        'export_templates' => '',
        'MaxTableUiprefs' => 100,
        'SessionTimeZone' => '',
        'AllowRoot' => true,
        'AllowNoPassword' => false,
        'AllowDeny' => ['order' => '', 'rules' => []],
        'DisableIS' => false,
        'tracking_version_auto_create' => false,
        'tracking_default_statements' => 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,TRUNCATE,REPLACE,CREATE VIEW,ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE',
        'tracking_add_drop_view' => true,
        'tracking_add_drop_table' => true,
        'tracking_add_drop_database' => true,
        'hide_connection_errors' => false,
    ];

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
        $settings = new Server($actualValues);

        foreach (array_keys($expectedValues) as $key) {
            $this->assertSame($expected[$key], $settings->$key);
        }
    }

    /**
     * [setting key, actual value, expected value]
     *
     * @return mixed[][][][]
     * @psalm-return (array{0: string, 1: mixed, 2: mixed})[][][]
     */
    public function providerForTestConstructor(): array
    {
        return [
            'null values' => [
                [
                    ['host', null, 'localhost'],
                    ['port', null, ''],
                    ['socket', null, ''],
                    ['ssl', null, false],
                    ['ssl_key', null, null],
                    ['ssl_cert', null, null],
                    ['ssl_ca', null, null],
                    ['ssl_ca_path', null, null],
                    ['ssl_ciphers', null, null],
                    ['ssl_verify', null, true],
                    ['compress', null, false],
                    ['controlhost', null, ''],
                    ['controlport', null, ''],
                    ['controluser', null, ''],
                    ['controlpass', null, ''],
                    ['auth_type', null, 'cookie'],
                    ['auth_http_realm', null, ''],
                    ['user', null, 'root'],
                    ['password', null, ''],
                    ['SignonSession', null, ''],
                    ['SignonCookieParams', null, ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false]],
                    ['SignonScript', null, ''],
                    ['SignonURL', null, ''],
                    ['LogoutURL', null, ''],
                    ['only_db', null, ''],
                    ['hide_db', null, ''],
                    ['verbose', null, ''],
                    ['pmadb', null, ''],
                    ['bookmarktable', null, ''],
                    ['relation', null, ''],
                    ['table_info', null, ''],
                    ['table_coords', null, ''],
                    ['pdf_pages', null, ''],
                    ['column_info', null, ''],
                    ['history', null, ''],
                    ['recent', null, ''],
                    ['favorite', null, ''],
                    ['table_uiprefs', null, ''],
                    ['tracking', null, ''],
                    ['userconfig', null, ''],
                    ['users', null, ''],
                    ['usergroups', null, ''],
                    ['navigationhiding', null, ''],
                    ['savedsearches', null, ''],
                    ['central_columns', null, ''],
                    ['designer_settings', null, ''],
                    ['export_templates', null, ''],
                    ['MaxTableUiprefs', null, 100],
                    ['SessionTimeZone', null, ''],
                    ['AllowRoot', null, true],
                    ['AllowNoPassword', null, false],
                    ['AllowDeny', null, ['order' => '', 'rules' => []]],
                    ['DisableIS', null, false],
                    ['tracking_version_auto_create', null, false],
                    ['tracking_default_statements', null, 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,TRUNCATE,REPLACE,CREATE VIEW,ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE'],
                    ['tracking_add_drop_view', null, true],
                    ['tracking_add_drop_table', null, true],
                    ['tracking_add_drop_database', null, true],
                    ['hide_connection_errors', null, false],
                ],
            ],
            'valid values' => [
                [
                    ['host', 'test', 'test'],
                    ['port', 'test', 'test'],
                    ['socket', 'test', 'test'],
                    ['ssl', false, false],
                    ['ssl_key', 'test', 'test'],
                    ['ssl_cert', 'test', 'test'],
                    ['ssl_ca', 'test', 'test'],
                    ['ssl_ca_path', 'test', 'test'],
                    ['ssl_ciphers', 'test', 'test'],
                    ['ssl_verify', true, true],
                    ['compress', false, false],
                    ['controlhost', 'test', 'test'],
                    ['controlport', 'test', 'test'],
                    ['controluser', 'test', 'test'],
                    ['controlpass', 'test', 'test'],
                    ['auth_type', 'config', 'config'],
                    ['auth_http_realm', 'test', 'test'],
                    ['user', 'test', 'test'],
                    ['password', 'test', 'test'],
                    ['SignonSession', 'test', 'test'],
                    ['SignonCookieParams', ['lifetime' => 0, 'path' => 'test', 'domain' => 'test', 'secure' => false, 'httponly' => false, 'samesite' => 'Lax'], ['lifetime' => 0, 'path' => 'test', 'domain' => 'test', 'secure' => false, 'httponly' => false, 'samesite' => 'Lax']],
                    ['SignonScript', 'test', 'test'],
                    ['SignonURL', 'test', 'test'],
                    ['LogoutURL', 'test', 'test'],
                    ['only_db', ['test1', 'test2', 1234], ['test1', 'test2', '1234']],
                    ['hide_db', 'test', 'test'],
                    ['verbose', 'test', 'test'],
                    ['pmadb', 'test', 'test'],
                    ['bookmarktable', false, false],
                    ['relation', false, false],
                    ['table_info', false, false],
                    ['table_coords', false, false],
                    ['pdf_pages', false, false],
                    ['column_info', false, false],
                    ['history', false, false],
                    ['recent', false, false],
                    ['favorite', false, false],
                    ['table_uiprefs', false, false],
                    ['tracking', false, false],
                    ['userconfig', false, false],
                    ['users', false, false],
                    ['usergroups', false, false],
                    ['navigationhiding', false, false],
                    ['savedsearches', false, false],
                    ['central_columns', false, false],
                    ['designer_settings', false, false],
                    ['export_templates', false, false],
                    ['MaxTableUiprefs', 1, 1],
                    ['SessionTimeZone', 'test', 'test'],
                    ['AllowRoot', true, true],
                    ['AllowNoPassword', false, false],
                    ['AllowDeny', ['order' => '', 'rules' => ['allow root from 192.168.5.50', 'allow % from 192.168.6.10']], ['order' => '', 'rules' => ['allow root from 192.168.5.50', 'allow % from 192.168.6.10']]],
                    ['DisableIS', false, false],
                    ['tracking_version_auto_create', false, false],
                    ['tracking_default_statements', 'test', 'test'],
                    ['tracking_add_drop_view', true, true],
                    ['tracking_add_drop_table', true, true],
                    ['tracking_add_drop_database', true, true],
                    ['hide_connection_errors', true, true],
                ],
            ],
            'valid values 2' => [
                [
                    ['ssl', true, true],
                    ['ssl_verify', false, false],
                    ['compress', true, true],
                    ['auth_type', 'http', 'http'],
                    ['SignonCookieParams', ['lifetime' => 1, 'secure' => true, 'httponly' => true, 'samesite' => 'Strict'], ['lifetime' => 1, 'path' => '/', 'domain' => '', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']],
                    ['only_db', 'test', 'test'],
                    ['bookmarktable', 'test', 'test'],
                    ['relation', 'test', 'test'],
                    ['table_info', 'test', 'test'],
                    ['table_coords', 'test', 'test'],
                    ['pdf_pages', 'test', 'test'],
                    ['column_info', 'test', 'test'],
                    ['history', 'test', 'test'],
                    ['recent', 'test', 'test'],
                    ['favorite', 'test', 'test'],
                    ['table_uiprefs', 'test', 'test'],
                    ['tracking', 'test', 'test'],
                    ['userconfig', 'test', 'test'],
                    ['users', 'test', 'test'],
                    ['usergroups', 'test', 'test'],
                    ['navigationhiding', 'test', 'test'],
                    ['savedsearches', 'test', 'test'],
                    ['central_columns', 'test', 'test'],
                    ['designer_settings', 'test', 'test'],
                    ['export_templates', 'test', 'test'],
                    ['AllowRoot', false, false],
                    ['AllowNoPassword', true, true],
                    ['AllowDeny', ['order' => 'deny,allow'], ['order' => 'deny,allow', 'rules' => []]],
                    ['DisableIS', true, true],
                    ['tracking_version_auto_create', true, true],
                    ['tracking_add_drop_view', false, false],
                    ['tracking_add_drop_table', false, false],
                    ['tracking_add_drop_database', false, false],
                    ['hide_connection_errors', false, false],
                ],
            ],
            'valid values 3' => [
                [
                    ['auth_type', 'signon', 'signon'],
                    ['SignonCookieParams', [], ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false]],
                    ['AllowDeny', ['order' => 'allow,deny'], ['order' => 'allow,deny', 'rules' => []]],
                ],
            ],
            'valid values 4' => [
                [
                    ['auth_type', 'cookie', 'cookie'],
                    ['AllowDeny', ['order' => 'explicit'], ['order' => 'explicit', 'rules' => []]],
                ],
            ],
            'valid values 5' => [[['AllowDeny', [], ['order' => '', 'rules' => []]]]],
            'valid values with type coercion' => [
                [
                    ['host', 1234, '1234'],
                    ['port', 1234, '1234'],
                    ['socket', 1234, '1234'],
                    ['ssl', 1, true],
                    ['ssl_key', 1234, '1234'],
                    ['ssl_cert', 1234, '1234'],
                    ['ssl_ca', 1234, '1234'],
                    ['ssl_ca_path', 1234, '1234'],
                    ['ssl_ciphers', 1234, '1234'],
                    ['ssl_verify', 0, false],
                    ['compress', 1, true],
                    ['controlhost', 1234, '1234'],
                    ['controlport', 1234, '1234'],
                    ['controluser', 1234, '1234'],
                    ['controlpass', 1234, '1234'],
                    ['auth_http_realm', 1234, '1234'],
                    ['user', 1234, '1234'],
                    ['password', 1234, '1234'],
                    ['SignonSession', 1234, '1234'],
                    ['SignonCookieParams', ['lifetime' => '1', 'path' => 1234, 'domain' => 1234, 'secure' => 1, 'httponly' => 1], ['lifetime' => 1, 'path' => '1234', 'domain' => '1234', 'secure' => true, 'httponly' => true]],
                    ['SignonScript', 1234, '1234'],
                    ['SignonURL', 1234, '1234'],
                    ['LogoutURL', 1234, '1234'],
                    ['only_db', 1234, '1234'],
                    ['hide_db', 1234, '1234'],
                    ['verbose', 1234, '1234'],
                    ['pmadb', 1234, '1234'],
                    ['bookmarktable', true, '1'],
                    ['relation', true, '1'],
                    ['table_info', true, '1'],
                    ['table_coords', true, '1'],
                    ['pdf_pages', true, '1'],
                    ['column_info', true, '1'],
                    ['history', true, '1'],
                    ['recent', true, '1'],
                    ['favorite', true, '1'],
                    ['table_uiprefs', true, '1'],
                    ['tracking', true, '1'],
                    ['userconfig', true, '1'],
                    ['users', true, '1'],
                    ['usergroups', true, '1'],
                    ['navigationhiding', true, '1'],
                    ['savedsearches', true, '1'],
                    ['central_columns', true, '1'],
                    ['designer_settings', true, '1'],
                    ['export_templates', true, '1'],
                    ['MaxTableUiprefs', '1', 1],
                    ['SessionTimeZone', 1234, '1234'],
                    ['AllowRoot', 0, false],
                    ['AllowNoPassword', 1, true],
                    ['AllowDeny', ['rules' => [1234]], ['order' => '', 'rules' => ['1234']]],
                    ['DisableIS', 1, true],
                    ['tracking_version_auto_create', 1, true],
                    ['tracking_default_statements', 1234, '1234'],
                    ['tracking_add_drop_view', 0, false],
                    ['tracking_add_drop_table', 0, false],
                    ['tracking_add_drop_database', 0, false],
                    ['hide_connection_errors', 1, true],
                ],
            ],
            'invalid values' => [
                [
                    ['auth_type', 'invalid', 'cookie'],
                    ['SignonCookieParams', 'invalid', ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false]],
                    ['only_db', [], ''],
                    ['MaxTableUiprefs', -1, 100],
                    ['AllowDeny', 'invalid', ['order' => '', 'rules' => []]],
                ],
            ],
            'invalid values 2' => [
                [
                    ['SignonCookieParams', ['invalid' => 'invalid', 'lifetime' => -1, 'samesite' => 'invalid'], ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false]],
                    ['AllowDeny', ['invalid' => 'invalid', 'order' => 'invalid', 'rules' => 'invalid'], ['order' => '', 'rules' => []]],
                ],
            ],
        ];
    }
}
