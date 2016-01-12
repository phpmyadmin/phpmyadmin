<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating CREATE EVENT query from HTTP request
 *
 * @package PhpMyAdmin-test
 */

/*
 * Needed for backquote()
 */


/*
 * Needed by PMA_EVN_getQueryFromRequest()
 */

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_events.lib.php';

/**
 * Test for generating CREATE EVENT query from HTTP request
 *
 * @package PhpMyAdmin-test
 */
class PMA_EVN_GetQueryFromRequest_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_EVN_getQueryFromRequest
     *
     * @param array  $request Request
     * @param string $query   Query
     * @param array  $num_err Error number
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testgetQueryFromRequest($request, $query, $num_err)
    {
        global $_REQUEST, $errors;

        $errors = array();
        PMA_EVN_setGlobals();

        unset($_REQUEST);
        $_REQUEST = $request;

        $this->assertEquals($query, PMA_EVN_getQueryFromRequest());
        $this->assertEquals($num_err, count($errors));
    }

    /**
     * Data provider for testgetQueryFromRequest
     *
     * @return array
     */
    public function provider()
    {
        return array(
            // Testing success
            array(
                array( // simple once-off event
                    'item_name'       => 's o m e e v e n t\\',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE AT \'2050-01-01 ' .
                '00:00:00\' ON COMPLETION NOT PRESERVE DO SET @A=0;',
                0
            ),
            array(
                array( // full once-off event
                    'item_name'       => 'evn',
                    'item_definer'    => 'me@home',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_preserve'   => 'ON',
                    'item_status'     => 'ENABLED',
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE DEFINER=`me`@`home` EVENT `evn` ON SCHEDULE AT ' .
                '\'2050-01-01 00:00:00\' ON COMPLETION PRESERVE ENABLE DO SET @A=0;',
                0
            ),
            array(
                array( // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE EVERY 365 DAY ON ' .
                'COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
                0
            ),
            array(
                array( // full recurring event
                    'item_name'           => 'rec_evn2',
                    'item_definer'        => 'evil``user><\\@work\\',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_starts'         => '1900-01-01',
                    'item_ends'           => '2050-01-01',
                    'item_preserve'       => 'ON',
                    'item_status'         => 'SLAVESIDE_DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE DEFINER=`evil````user><\`@`work\` EVENT `rec_evn2` ON ' .
                'SCHEDULE EVERY 365 DAY STARTS \'1900-01-01\' ENDS \'2050-01-01\' ' .
                'ON COMPLETION PRESERVE DISABLE ON SLAVE DO SET @A=0;',
                0
            ),
            // Testing failures
            array(
                array( // empty request
                ),
                'CREATE EVENT ON SCHEDULE ON COMPLETION NOT PRESERVE DO ',
                3
            ),
            array(
                array(
                    'item_name'       => 's o m e e v e n t\\',
                    'item_definer'    => 'someuser', // invalid definer format
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '', // no execution time
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DO SET @A=0;',
                2
            ),
            array(
                array(
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '', // no interval value
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1
            ),
            array(
                array( // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'CENTURIES', // invalid interval field
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1
            ),
        );
    }
}
