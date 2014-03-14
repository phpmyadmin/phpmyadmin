<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test PMA_Util::whichCrlf function
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

/**
 * Test PMA_Util::whichCrlf function
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_WhichCrlf_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Test for whichCrlf
     *
     * @return void
     *
     * @using runkit pecl extension
     * if not define PMA_USR_OS, then define it as Win
     * if installed runkit, then constant will not change
     */
    public function testWhichCrlf()
    {
        if (PMA_HAS_RUNKIT && defined('PMA_USR_OS')) {
            $pma_usr_os = PMA_USR_OS;
        }

        if (defined('PMA_USR_OS') && !PMA_HAS_RUNKIT) {

            if (PMA_USR_OS == 'Win') {
                $this->assertEquals(
                    "\r\n", PMA_Util::whichCrlf()
                );
            } else {
                $this->assertEquals(
                    "\n", PMA_Util::whichCrlf()
                );
            }

            $this->markTestIncomplete('Cannot redefine constant');

        } else {

            if (PMA_HAS_RUNKIT) {
                if (!defined('PMA_USR_OS')) {
                    define('PMA_USR_OS', 'Linux');
                } else {
                    runkit_constant_redefine('PMA_USR_OS', 'Linux');
                }

                $this->assertEquals(
                    "\n", PMA_Util::whichCrlf()
                );
            }

            if (PMA_HAS_RUNKIT) {
                runkit_constant_redefine('PMA_USR_OS', 'Win');
            } else {
                define('PMA_USR_OS', 'Win');
            }
            $this->assertEquals(
                "\r\n", PMA_Util::whichCrlf()
            );

        }

        if (PMA_HAS_RUNKIT) {
            if (isset($pma_usr_os)) {
                runkit_constant_redefine('PMA_USR_OS', 'Win');
            } else {
                runkit_constant_remove('PMA_USR_OS');
            }
        }
    }

}
?>
