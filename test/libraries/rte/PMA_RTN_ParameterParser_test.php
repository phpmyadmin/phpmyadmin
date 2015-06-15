<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for parsing of Routine parameters
 *
 * @package PhpMyAdmin-test
 */

/*
 * Needed for PMA_Util::unQuote() and PMA_SQP_parse()
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.lib.php';

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';

/**
 * Test for parsing of Routine parameters
 *
 * @package PhpMyAdmin-test
 */
class PMA_RTN_ParameterParser_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_RTN_parseRoutineDefiner
     *
     * @param string $source Source
     * @param array  $target Expected output
     *
     * @return void
     *
     * @dataProvider definerProvider
     */
    public function testParseDefiner($source, $target)
    {
        PMA_RTN_setGlobals();
        $this->assertEquals(
            $target,
            PMA_RTN_parseRoutineDefiner(PMA_SQP_parse($source))
        );
    }

    /**
     * Data provider for testParseDefiner
     *
     * @return array
     */
    public function definerProvider()
    {
        return array(
            array('CREATE PROCEDURE FOO() SELECT NULL', ''),
            array(
                'CREATE DEFINER=`root`@`localhost` PROCEDURE FOO() SELECT NULL',
                'root@localhost'
            ),
            array(
                'CREATE DEFINER=`root\\`@`localhost` PROCEDURE FOO() SELECT NULL',
                'root\\@localhost'
            ),
        );
    }
}
?>
