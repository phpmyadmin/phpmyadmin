<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

use function ini_get;
use function parse_str;
use function str_repeat;
use function urldecode;

#[CoversClass(Url::class)]
class UrlTest extends AbstractTestCase
{
    /** @var string|false|null */
    private static string|bool|null $inputArgSeparator = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        unset($_COOKIE['pma_lang']);
        Config::getInstance()->set('URLQueryEncryption', false);
    }

    /**
     * Test for Url::getCommon for DB only
     */
    public function testDbOnly(): void
    {
        Current::$server = 2;

        $separator = Url::getArgSeparator();
        $expected = 'server=2' . $separator . 'lang=en';

        $expected = '?db=db'
            . $separator . $expected;

        self::assertSame($expected, Url::getCommon(['db' => 'db']));
    }

    /**
     * Test for Url::getCommon with new style
     */
    public function testNewStyle(): void
    {
        Current::$server = 2;

        $separator = Url::getArgSeparator();
        $expected = 'server=2' . $separator . 'lang=en';

        $expected = '?db=db'
            . $separator . 'table=table'
            . $separator . $expected;
        $params = ['db' => 'db', 'table' => 'table'];
        self::assertSame($expected, Url::getCommon($params));
    }

    /**
     * Test for Url::getCommon with alternate divider
     */
    public function testWithAlternateDivider(): void
    {
        Current::$server = 2;

        $separator = Url::getArgSeparator();
        $expected = 'server=2' . $separator . 'lang=en';

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        self::assertSame(
            $expected,
            Url::getCommonRaw(
                ['db' => 'db', 'table' => 'table'],
                '#ABC#',
            ),
        );
    }

    /**
     * Test for Url::getCommon
     */
    public function testDefault(): void
    {
        Current::$server = 2;

        $separator = Url::getArgSeparator();
        $expected = '?server=2' . $separator . 'lang=en';
        self::assertSame($expected, Url::getCommon());
    }

    /**
     * Test for Url::getFromRoute
     */
    public function testGetFromRoute(): void
    {
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '%3\$s',
            'table' => '%2\$s',
            'field' => '%1\$s',
            'change_column' => 1,
        ]);
        self::assertSame(
            'index.php?route=/test&db=%253%5C%24s&table=%252%5C%24s&field=%251%5C%24s&change_column=1&lang=en',
            $generatedUrl,
        );
    }

    /**
     * Test for Url::getFromRoute
     */
    public function testGetFromRouteSpecialDbName(): void
    {
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '&test=_database=',
            'table' => '&test=_database=',
            'field' => '&test=_database=',
            'change_column' => 1,
        ]);
        $expectedUrl = 'index.php?route=/test&db=%26test%3D_database%3D'
        . '&table=%26test%3D_database%3D&field=%26test%3D_database%3D&change_column=1&lang=en';
        self::assertSame($expectedUrl, $generatedUrl);

        self::assertSame(
            'index.php?route=/test&db=&test=_database=&table=&'
            . 'test=_database=&field=&test=_database=&change_column=1&lang=en',
            urldecode(
                $expectedUrl,
            ),
        );
    }

    /**
     * Test for Url::getFromRoute
     */
    public function testGetFromRouteMaliciousScript(): void
    {
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '<script src="https://domain.tld/svn/trunk/html5.js"></script>',
            'table' => '<script src="https://domain.tld/maybeweshouldusegit/trunk/html5.js"></script>',
            'field' => true,
            'trees' => 1,
            'book' => false,
            'worm' => false,
        ]);
        self::assertSame(
            'index.php?route=/test&db=%3Cscript+src%3D%22https%3A%2F%2Fdomain.tld%2Fsvn'
            . '%2Ftrunk%2Fhtml5.js%22%3E%3C%2Fscript%3E&table=%3Cscript+src%3D%22'
            . 'https%3A%2F%2Fdomain.tld%2Fmaybeweshouldusegit%2Ftrunk%2Fhtml5.js%22%3E%3C%2F'
            . 'script%3E&field=1&trees=1&book=0&worm=0&lang=en',
            $generatedUrl,
        );
    }

    public function testGetHiddenFields(): void
    {
        $_SESSION = [];
        self::assertSame('', Url::getHiddenFields([]));

        $_SESSION = [' PMA_token ' => '<b>token</b>'];
        self::assertSame(
            '<input type="hidden" name="token" value="&lt;b&gt;token&lt;/b&gt;">',
            Url::getHiddenFields([]),
        );
    }

    public function testBuildHttpQueryWithUrlQueryEncryptionDisabled(): void
    {
        Config::getInstance()->set('URLQueryEncryption', false);
        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        self::assertSame('db=test_db&table=test_table&pos=0', Url::buildHttpQuery($params));
    }

    public function testBuildHttpQueryWithUrlQueryEncryptionEnabled(): void
    {
        $_SESSION = [];
        $config = Config::getInstance();
        $config->set('URLQueryEncryption', true);
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        $query = Url::buildHttpQuery($params);
        self::assertStringStartsWith('pos=0&eq=', $query);
        parse_str($query, $queryParams);
        self::assertCount(2, $queryParams);
        self::assertSame('0', $queryParams['pos']);
        self::assertIsString($queryParams['eq']);
        self::assertNotSame('', $queryParams['eq']);
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9-_=]+$/', $queryParams['eq']);

        $decrypted = Url::decryptQuery($queryParams['eq']);
        self::assertNotNull($decrypted);
        self::assertJson($decrypted);
        self::assertSame('{"db":"test_db","table":"test_table"}', $decrypted);
    }

    public function testQueryEncryption(): void
    {
        $_SESSION = [];
        $config = Config::getInstance();
        $config->set('URLQueryEncryption', true);
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $query = '{"db":"test_db","table":"test_table"}';
        $encrypted = Url::encryptQuery($query);
        self::assertNotSame($query, $encrypted);
        self::assertNotSame('', $encrypted);
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9-_=]+$/', $encrypted);

        $decrypted = Url::decryptQuery($encrypted);
        self::assertSame($query, $decrypted);
    }

    /** @param string|false $iniValue */
    #[DataProvider('getArgSeparatorProvider')]
    public function testGetArgSeparator(string $expected, string|bool $iniValue, string|null $cacheValue): void
    {
        $property = new ReflectionProperty(Url::class, 'inputArgSeparator');
        $property->setValue(null, $cacheValue);

        self::$inputArgSeparator = $iniValue;
        self::assertSame($expected, Url::getArgSeparator());

        self::$inputArgSeparator = null;
        $property->setValue(null, null);
    }

    /** @return array<string, array{string, string|false, string|null}> */
    public static function getArgSeparatorProvider(): array
    {
        return [
            'ampersand' => ['&', '&', null],
            'semicolon' => [';', ';', null],
            'prefer ampersand' => ['&', '+;&$', null],
            'prefer semicolon' => [';', '+;$', null],
            'first char' => ['+', '+$', null],
            'cache' => ['$', '&', '$'],
            'empty value' => ['&', '', null],
            'false' => ['&', false, null],
        ];
    }

    /**
     * Test double for ini_get('arg_separator.input') as it can't be changed using ini_set()
     *
     * @see Url::getArgSeparatorValueFromIni
     *
     * @return string|false
     */
    public static function getInputArgSeparator(): string|bool
    {
        return self::$inputArgSeparator ?? ini_get('arg_separator.input');
    }
}
