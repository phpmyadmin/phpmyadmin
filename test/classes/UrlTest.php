<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Url;

use function is_string;
use function parse_str;
use function str_repeat;
use function urldecode;

/** @covers \PhpMyAdmin\Url */
class UrlTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        unset($_COOKIE['pma_lang']);
        $GLOBALS['config']->set('URLQueryEncryption', false);
    }

    /**
     * Test for Url::getCommon for DB only
     */
    public function testDbOnly(): void
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en';

        $expected = '?db=db'
            . $separator . $expected;

        $this->assertEquals($expected, Url::getCommon(['db' => 'db']));
    }

    /**
     * Test for Url::getCommon with new style
     */
    public function testNewStyle(): void
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en';

        $expected = '?db=db'
            . $separator . 'table=table'
            . $separator . $expected;
        $params = ['db' => 'db', 'table' => 'table'];
        $this->assertEquals($expected, Url::getCommon($params));
    }

    /**
     * Test for Url::getCommon with alternate divider
     */
    public function testWithAlternateDivider(): void
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en';

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        $this->assertEquals(
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
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = '?server=x' . $separator . 'lang=en';
        $this->assertEquals($expected, Url::getCommon());
    }

    /**
     * Test for Url::getFromRoute
     */
    public function testGetFromRoute(): void
    {
        unset($GLOBALS['server']);
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '%3\$s',
            'table' => '%2\$s',
            'field' => '%1\$s',
            'change_column' => 1,
        ]);
        $this->assertEquals(
            'index.php?route=/test&db=%253%5C%24s&table=%252%5C%24s&field=%251%5C%24s&change_column=1&lang=en',
            $generatedUrl,
        );
    }

    /**
     * Test for Url::getFromRoute
     */
    public function testGetFromRouteSpecialDbName(): void
    {
        unset($GLOBALS['server']);
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '&test=_database=',
            'table' => '&test=_database=',
            'field' => '&test=_database=',
            'change_column' => 1,
        ]);
        $expectedUrl = 'index.php?route=/test&db=%26test%3D_database%3D'
        . '&table=%26test%3D_database%3D&field=%26test%3D_database%3D&change_column=1&lang=en';
        $this->assertEquals($expectedUrl, $generatedUrl);

        $this->assertEquals(
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
        unset($GLOBALS['server']);
        $generatedUrl = Url::getFromRoute('/test', [
            'db' => '<script src="https://domain.tld/svn/trunk/html5.js"></script>',
            'table' => '<script src="https://domain.tld/maybeweshouldusegit/trunk/html5.js"></script>',
            'field' => true,
            'trees' => 1,
            'book' => false,
            'worm' => false,
        ]);
        $this->assertEquals(
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
        $this->assertSame('', Url::getHiddenFields([]));

        $_SESSION = [' PMA_token ' => '<b>token</b>'];
        $this->assertSame(
            '<input type="hidden" name="token" value="&lt;b&gt;token&lt;/b&gt;">',
            Url::getHiddenFields([]),
        );
    }

    public function testBuildHttpQueryWithUrlQueryEncryptionDisabled(): void
    {
        $GLOBALS['config']->set('URLQueryEncryption', false);
        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        $this->assertEquals('db=test_db&table=test_table&pos=0', Url::buildHttpQuery($params));
    }

    public function testBuildHttpQueryWithUrlQueryEncryptionEnabled(): void
    {
        $_SESSION = [];
        $GLOBALS['config']->set('URLQueryEncryption', true);
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        $query = Url::buildHttpQuery($params);
        $this->assertStringStartsWith('pos=0&eq=', $query);
        parse_str($query, $queryParams);
        $this->assertCount(2, $queryParams);
        $this->assertSame('0', $queryParams['pos']);
        $this->assertTrue(is_string($queryParams['eq']));
        $this->assertNotSame('', $queryParams['eq']);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9-_=]+$/', $queryParams['eq']);

        $decrypted = Url::decryptQuery($queryParams['eq']);
        $this->assertNotNull($decrypted);
        $this->assertJson($decrypted);
        $this->assertSame('{"db":"test_db","table":"test_table"}', $decrypted);
    }

    public function testQueryEncryption(): void
    {
        $_SESSION = [];
        $GLOBALS['config']->set('URLQueryEncryption', true);
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $query = '{"db":"test_db","table":"test_table"}';
        $encrypted = Url::encryptQuery($query);
        $this->assertNotSame($query, $encrypted);
        $this->assertNotSame('', $encrypted);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9-_=]+$/', $encrypted);

        $decrypted = Url::decryptQuery($encrypted);
        $this->assertSame($query, $decrypted);
    }
}
