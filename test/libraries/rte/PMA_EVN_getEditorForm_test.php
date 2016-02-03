<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating event editor
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/url_generating.lib.php';

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_events.lib.php';

/**
 * Test for generating event editor
 *
 * @package PhpMyAdmin-test
 */
class PMA_EVN_GetEditorForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['tear_down']['server'] = false;
        if (! isset($GLOBALS['cfg']['ServerDefault'])) {
            $GLOBALS['cfg']['ServerDefault'] = '';
            $GLOBALS['tear_down']['server'] = true;
        }
    }

    /**
     * Tear down
     *
     * @return void
     */
    public function tearDown()
    {
        if ($GLOBALS['tear_down']['server']) {
            unset($GLOBALS['cfg']['ServerDefault']);
        }
        unset($GLOBALS['tear_down']);
    }

    /**
     * Test for PMA_EVN_getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerAdd
     */
    public function testgetEditorFormAdd($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_EVN_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_EVN_getEditorForm('add', 'change', $data)
        );
    }

    /**
     * Data provider for testgetEditorFormAdd
     *
     * @return array
     */
    public function providerAdd()
    {
        $data = array(
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
        );

        return array(
            array(
                $data,
                "<input name='add_item'"
            ),
            array(
                $data,
                "<input type='text' name='item_name'"
            ),
            array(
                $data,
                "<select name='item_status'"
            ),
            array(
                $data,
                "<input name='item_type'"
            ),
            array(
                $data,
                "<input type='text' name='item_execute_at'"
            ),
            array(
                $data,
                "<input type='text' name='item_ends'"
            ),
            array(
                $data,
                "<textarea name='item_definition'"
            ),
            array(
                $data,
                "<input type='text' name='item_definer'"
            ),
            array(
                $data,
                "<input type='text' name='item_comment'"
            ),
            array(
                $data,
                "<input type='submit' name='editor_process_add'"
            )
        );
    }

    /**
     * Test for PMA_EVN_getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerEdit
     */
    public function testgetEditorFormEdit($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_EVN_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_EVN_getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testgetEditorForm_edit
     *
     * @return array
     */
    public function providerEdit()
    {
        $data = array(
            'item_name'           => 'foo',
            'item_type'           => 'RECURRING',
            'item_type_toggle'    => 'ONE TIME',
            'item_original_name'  => 'bar',
            'item_status'         => 'ENABLED',
            'item_execute_at'     => '',
            'item_interval_value' => '1',
            'item_interval_field' => 'DAY',
            'item_starts'         => '',
            'item_ends'           => '',
            'item_definition'     => 'SET @A=1;',
            'item_preserve'       => '',
            'item_comment'        => '',
            'item_definer'        => ''
        );

        return array(
            array(
                $data,
                "<input name='edit_item'"
            ),
            array(
                $data,
                "<input type='text' name='item_name'"
            ),
            array(
                $data,
                "<select name='item_status'"
            ),
            array(
                $data,
                "<input name='item_type'"
            ),
            array(
                $data,
                "<input type='text' name='item_execute_at'"
            ),
            array(
                $data,
                "<input type='text' name='item_ends'"
            ),
            array(
                $data,
                "<textarea name='item_definition'"
            ),
            array(
                $data,
                "<input type='text' name='item_definer'"
            ),
            array(
                $data,
                "<input type='text' name='item_comment'"
            ),
            array(
                $data,
                "<input type='submit' name='editor_process_edit'"
            )
        );
    }

    /**
     * Test for PMA_EVN_getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerAjax
     */
    public function testgetEditorFormAjax($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        PMA_EVN_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_EVN_getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testgetEditorForm_ajax
     *
     * @return array
     */
    public function providerAjax()
    {
        $data = array(
            'item_name'           => '',
            'item_type'           => 'RECURRING',
            'item_type_toggle'    => 'ONE TIME',
            'item_original_name'  => '',
            'item_status'         => 'ENABLED',
            'item_execute_at'     => '',
            'item_interval_value' => '',
            'item_interval_field' => 'DAY',
            'item_starts'         => '',
            'item_ends'           => '',
            'item_definition'     => '',
            'item_preserve'       => '',
            'item_comment'        => '',
            'item_definer'        => ''
        );

        return array(
            array(
                $data,
                "<select name='item_type'"
            ),
            array(
                $data,
                "<input type='hidden' name='editor_process_edit'"
            ),
            array(
                $data,
                "<input type='hidden' name='ajax_request'"
            )
        );
    }
}
