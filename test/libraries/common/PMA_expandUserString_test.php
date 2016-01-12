<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::expandUserString from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */



/**
 ** Test for PMA\libraries\Util::expandUserString function.
 *
 * @package PhpMyAdmin-test
 */
class PMA_ExpandUserString_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Setup variables needed by test.
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['PMA_Config'] = new PMA\libraries\Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg'] = array(
            'Server' => array(
                'host' => 'host&',
                'verbose' => 'verbose',
            )
        );
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = 'table';
    }

    /**
     * Test case for expanding strings
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testExpand($in, $out)
    {
        $out = str_replace('PMA_VERSION', PMA_VERSION, $out);
        $this->assertEquals(
            $out, PMA\libraries\Util::expandUserString($in)
        );
    }

    /**
     * Test case for expanding strings with escaping
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testExpandEscape($in, $out)
    {
        $out = str_replace('PMA_VERSION', PMA_VERSION, $out);
        $this->assertEquals(
            htmlspecialchars($out),
            PMA\libraries\Util::expandUserString(
                $in, 'htmlspecialchars'
            )
        );
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('@SERVER@', 'host&'),
            array('@VSERVER@', 'verbose'),
            array('@DATABASE@', 'database'),
            array('@TABLE@', 'table'),
            array('@IGNORE@', '@IGNORE@'),
            array('@PHPMYADMIN@', 'phpMyAdmin PMA_VERSION'),
            );
    }
}
