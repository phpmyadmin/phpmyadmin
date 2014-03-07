<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_URL_getCommon()
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/url_generating.lib.php';

class PMA_GenerateCommonURL_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        unset($_COOKIE['pma_lang'], $_COOKIE['pma_collation_connection']);
    }

    public function testOldStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=token'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, PMA_URL_getCommon('db', 'table'));
    }

    public function testOldStyleDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=token'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, PMA_URL_getCommon('db'));
    }

    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=token'
            ;

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = array('db' => 'db', 'table' => 'table');
        $this->assertEquals($expected, PMA_URL_getCommon($params));
    }

    public function testOldStyleWithAlternateSeparator()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . $separator
            . 'lang=en' . $separator
            . 'collation_connection=x' . $separator
            . 'token=token'
            ;

        $expected = 'db=db' . $separator . 'table=table' . $separator . $expected;
        $this->assertEquals($expected, PMA_URL_getCommon('db', 'table', '&'));
    }

    public function testOldStyleWithAlternateSeparatorDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . $separator
            . 'lang=en' . $separator
            . 'collation_connection=x' . $separator
            . 'token=token'
            ;

        $expected = 'db=db' . $separator . $expected;
        $this->assertEquals($expected, PMA_URL_getCommon('db', '', '&'));
    }

    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_URL_getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=token'
            ;
        $this->assertEquals($expected, PMA_URL_getCommon());
    }
}
?>
