<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
/*
 * Include to text.
 */
use PMA\libraries\URL;

/**
 * Tests for methods in URL class
 *
 * @package PhpMyAdmin-test
 */
class URLTest extends PHPUnit_Framework_TestCase
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
     * Test for URL::getCommon for DB only
     *
     * @return void
     */
    public function testDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = URL::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x'
            . htmlentities($separator) . 'token=token'
            ;

        $expected = '?db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, URL::getCommon(array('db' => 'db')));
    }

    /**
     * Test for URL::getCommon with new style
     *
     * @return void
     */
    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = URL::getArgSeparator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x'
            . htmlentities($separator) . 'token=token'
            ;

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = array('db' => 'db', 'table' => 'table');
        $this->assertEquals($expected, URL::getCommon($params));
    }

    /**
     * Test for URL::getCommon with alternate divider
     *
     * @return void
     */
    public function testWithAlternateDivider()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = URL::getArgSeparator();
        $expected = 'server=x' . $separator
            . 'lang=en' . $separator
            . 'collation_connection=x'
            . $separator . 'token=token'
            ;

        $expected = '#ABC#db=db' . $separator . 'table=table' . $separator
            . $expected;
        $this->assertEquals(
            $expected,
            URL::getCommonRaw(
                array('db' => 'db', 'table' => 'table'), '#ABC#'
            )
        );
    }

    /**
     * Test for URL::getCommon
     *
     * @return void
     */
    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = URL::getArgSeparator();
        $expected = '?server=x' . htmlentities($separator)
            . 'lang=en' . htmlentities($separator)
            . 'collation_connection=x'
            . htmlentities($separator) . 'token=token'
            ;
        $this->assertEquals($expected, URL::getCommon());
    }
}
