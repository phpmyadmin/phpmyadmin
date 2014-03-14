<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for fetching trigger data from HTTP request
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_triggers.lib.php';

/**
 * Test for fetching trigger data from HTTP request
 *
 * @package PhpMyAdmin-test
 */
class PMA_TRI_GetDataFromRequest_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_TRI_getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testgetDataFromRequest_empty($in, $out)
    {
        global $_REQUEST;

        unset($_REQUEST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_REQUEST[$key] = $value;
            }
        }
        $this->assertEquals($out, PMA_TRI_getDataFromRequest());
    }

    /**
     * Data provider for testgetDataFromRequest_empty
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array(
                array(
                    'item_name'               => '',
                    'item_table'              => '',
                    'item_original_name'      => '',
                    'item_action_timing'      => '',
                    'item_event_manipulation' => '',
                    'item_definition'         => '',
                    'item_definer'            => ''
                ),
                array(
                    'item_name'               => '',
                    'item_table'              => '',
                    'item_original_name'      => '',
                    'item_action_timing'      => '',
                    'item_event_manipulation' => '',
                    'item_definition'         => '',
                    'item_definer'            => ''
                )
            ),
            array(
                array(
                    'item_name'               => 'foo',
                    'item_table'              => 'foo',
                    'item_original_name'      => 'foo',
                    'item_action_timing'      => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition'         => 'foo',
                    'item_definer'            => 'foo'
                ),
                array(
                    'item_name'               => 'foo',
                    'item_table'              => 'foo',
                    'item_original_name'      => 'foo',
                    'item_action_timing'      => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition'         => 'foo',
                    'item_definer'            => 'foo'
                )
            )
        );
    }
}
?>
