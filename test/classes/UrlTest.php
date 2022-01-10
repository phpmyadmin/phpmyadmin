<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Url;
use PHPUnit\Framework\TestCase;

/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
class UrlTest extends TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    public function setUp()
    {
        unset($_COOKIE['pma_lang']);
        $GLOBALS['PMA_Config']->set('URLQueryEncryption', false);
    }

    /**
     * Test for Url::getCommon for DB only
     *
     * @return void
     */
    public function testDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

        $expected = '?db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, Url::getCommon(array('db' => 'db')));
    }

    /**
     * Test for Url::getCommon with new style
     *
     * @return void
     */
    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator) . 'lang=en' ;

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = array('db' => 'db', 'table' => 'table');
        $this->assertEquals($expected, Url::getCommon($params));
    }

    /**
     * Test for Url::getCommon with alternate divider
     *
     * @return void
     */
    public function testWithAlternateDivider()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = 'server=x' . $separator . 'lang=en' ;

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        $this->assertEquals(
            $expected,
            Url::getCommonRaw(
                array('db' => 'db', 'table' => 'table'), '#ABC#'
            )
        );
    }

    /**
     * Test for Url::getCommon
     *
     * @return void
     */
    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = Url::getArgSeparator();
        $expected = '?server=x' . htmlentities($separator) . 'lang=en' ;
        $this->assertEquals($expected, Url::getCommon());
    }

    /**
     * @return void
     */
    public function testBuildHttpQueryWithUrlQueryEncryptionDisabled()
    {
        global $PMA_Config;

        $PMA_Config->set('URLQueryEncryption', false);
        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        $this->assertEquals('db=test_db&table=test_table&pos=0', Url::buildHttpQuery($params));
    }

    /**
     * @return void
     */
    public function testBuildHttpQueryWithUrlQueryEncryptionEnabled()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryption', true);
        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $params = ['db' => 'test_db', 'table' => 'test_table', 'pos' => 0];
        $query = Url::buildHttpQuery($params);
        $this->assertStringStartsWith('pos=0&eq=', $query);
        parse_str($query, $queryParams);
        $this->assertCount(2, $queryParams);
        $this->assertSame('0', $queryParams['pos']);
        $this->assertTrue(is_string($queryParams['eq']));
        $this->assertNotSame('', $queryParams['eq']);
        $this->assertRegExp('/^[a-zA-Z0-9-_=]+$/', $queryParams['eq']);
        $decrypted = Url::decryptQuery($queryParams['eq']);
        $this->assertJson($decrypted);
        $this->assertSame('{"db":"test_db","table":"test_table"}', $decrypted);
    }

    /**
     * @return void
     */
    public function testQueryEncryption()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryption', true);
        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $query = '{"db":"test_db","table":"test_table"}';
        $encrypted = Url::encryptQuery($query);
        $this->assertNotSame($query, $encrypted);
        $this->assertNotSame('', $encrypted);
        $this->assertRegExp('/^[a-zA-Z0-9-_=]+$/', $encrypted);
        $decrypted = Url::decryptQuery($encrypted);
        $this->assertSame($query, $decrypted);
    }
}
