<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_generate_common_url()
 *
 * @version $Id: PMA_get_real_size_test.php 10146 2007-03-20 14:16:18Z cybot_tm $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/core.lib.php';
require_once './libraries/url_generating.lib.php';

class PMA_generate_common_url_test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        unset($_COOKIE['pma_lang'], $_COOKIE['pma_charset'], $_COOKIE['pma_collation_connection']);
    }

    public function testOldStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, PMA_generate_common_url('db', 'table'));
    }

    public function testOldStyleDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . $expected;

        $this->assertEquals($expected, PMA_generate_common_url('db'));
    }

    public function testNewStyle()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;

        $expected = '?db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $params = array('db' => 'db', 'table' => 'table');
        $this->assertEquals($expected, PMA_generate_common_url($params));
    }

    public function testOldStyleWithAlternateSeparator()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . 'table=table'
            . htmlentities($separator) . $expected;
        $this->assertEquals($expected, PMA_generate_common_url('db', 'table', '&'));
    }

    public function testOldStyleWithAlternateSeparatorDbOnly()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;

        $expected = 'db=db'
            . htmlentities($separator) . $expected;
        $this->assertEquals($expected, PMA_generate_common_url('db', '', '&'));
    }

    public function testDefault()
    {
        $GLOBALS['server'] = 'x';
        $GLOBALS['lang'] = 'x';
        $GLOBALS['convcharset'] = 'x';
        $GLOBALS['collation_connection'] = 'x';
        $_SESSION[' PMA_token '] = 'x';
        $GLOBALS['cfg']['ServerDefault'] = 'y';

        $separator = PMA_get_arg_separator();
        $expected = 'server=x' . htmlentities($separator)
            . 'lang=x' . htmlentities($separator)
            . 'convcharset=x' . htmlentities($separator)
            . 'collation_connection=x' . htmlentities($separator)
            . 'token=x'
            ;
        $this->assertEquals($expected, PMA_generate_common_url());
    }
}
?>
