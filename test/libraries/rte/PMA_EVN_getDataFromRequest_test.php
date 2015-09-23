<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for fetching event data from HTTP request
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_events.lib.php';

/**
 * Test for fetching event data from HTTP request
 *
 * @package PhpMyAdmin-test
 */
class PMA_EVN_GetDataFromRequest_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_EVN_getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider provider
     */
    public function testgetDataFromRequestEmpty($in, $out)
    {
        global $_REQUEST;

        unset($_REQUEST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_REQUEST[$key] = $value;
            }
        }
        $this->assertEquals($out, PMA_EVN_getDataFromRequest());
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
                    'item_name'           => '',
                    'item_type'           => '',
                    'item_original_name'  => '',
                    'item_status'         => '',
                    'item_execute_at'     => '',
                    'item_interval_value' => '',
                    'item_interval_field' => '',
                    'item_starts'         => '',
                    'item_ends'           => '',
                    'item_definition'     => '',
                    'item_preserve'       => '',
                    'item_comment'        => '',
                    'item_definer'        => ''
                ),
                array(
                    'item_name'           => '',
                    'item_type'           => 'ONE TIME',
                    'item_type_toggle'    => 'RECURRING',
                    'item_original_name'  => '',
                    'item_status'         => '',
                    'item_execute_at'     => '',
                    'item_interval_value' => '',
                    'item_interval_field' => '',
                    'item_starts'         => '',
                    'item_ends'           => '',
                    'item_definition'     => '',
                    'item_preserve'       => '',
                    'item_comment'        => '',
                    'item_definer'        => ''
                )
            ),
            array(
                array(
                    'item_name'           => 'foo',
                    'item_type'           => 'RECURRING',
                    'item_original_name'  => 'foo',
                    'item_status'         => 'foo',
                    'item_execute_at'     => 'foo',
                    'item_interval_value' => 'foo',
                    'item_interval_field' => 'foo',
                    'item_starts'         => 'foo',
                    'item_ends'           => 'foo',
                    'item_definition'     => 'foo',
                    'item_preserve'       => 'foo',
                    'item_comment'        => 'foo',
                    'item_definer'        => 'foo'
                ),
                array(
                    'item_name'           => 'foo',
                    'item_type'           => 'RECURRING',
                    'item_type_toggle'    => 'ONE TIME',
                    'item_original_name'  => 'foo',
                    'item_status'         => 'foo',
                    'item_execute_at'     => 'foo',
                    'item_interval_value' => 'foo',
                    'item_interval_field' => 'foo',
                    'item_starts'         => 'foo',
                    'item_ends'           => 'foo',
                    'item_definition'     => 'foo',
                    'item_preserve'       => 'foo',
                    'item_comment'        => 'foo',
                    'item_definer'        => 'foo'
                )
            ),
        );
    }
}
