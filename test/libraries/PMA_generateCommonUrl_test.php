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

/**
 * tests for PMA_URL_getCommon()
 *
 * @package PhpMyAdmin-test
 */
class PMA_GenerateCommonURL_Test extends PHPUnit_Framework_TestCase
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
        unset($_COOKIE['pma_lang'], $_COOKIE['pma_collation_connection']);
    }

    /**
     * Test for PMA_URL_getCommon with old style
     *
     * @return void
     */
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

    /**
     * Test for PMA_URL_getCommon with old style for DB only
     *
     * @return void
     */
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

    /**
     * Test for PMA_URL_getCommon with new style
     *
     * @return void
     */
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

    /**
     * Test for PMA_URL_getCommon with old style and alternate separator
     *
     * @return void
     */
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

    /**
     * Test for PMA_URL_getCommon with old style and alternate separator for DB only
     *
     * @return void
     */
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

    /**
     * Test for PMA_URL_getCommon
     *
     * @return void
     */
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
