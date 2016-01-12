<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating trigger editor
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/url_generating.lib.php';

require_once 'libraries/database_interface.inc.php';

/*
 * Include to test.
 */
require_once 'libraries/rte/rte_triggers.lib.php';

/**
 * Test for generating trigger editor
 *
 * @package PhpMyAdmin-test
 */
class PMA_TRI_GetEditorForm_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['ServerDefault'] = '';
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['db'] = 'pma_test';
    }


    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerAdd
     * @group medium
     */
    public function testGetEditorFormAdd($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['server'] = 1;
        PMA_TRI_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_TRI_getEditorForm('add', $data)
        );
    }

    /**
     * Provider for testGetEditorFormAdd
     *
     * @return array
     */
    public function providerAdd()
    {
        $data = array(
            'item_name'               => '',
            'item_table'              => 'table1',
            'item_original_name'      => '',
            'item_action_timing'      => '',
            'item_event_manipulation' => '',
            'item_definition'         => '',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                "name='add_item'"
            ),
            array(
                $data,
                "name='item_name'"
            ),
            array(
                $data,
                "name='item_table'"
            ),
            array(
                $data,
                "name='item_timing'"
            ),
            array(
                $data,
                "name='item_event'"
            ),
            array(
                $data,
                "name='item_definition'"
            ),
            array(
                $data,
                "name='item_definer'"
            ),
            array(
                $data,
                "name='editor_process_add'"
            )
        );
    }

    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerEdit
     * @group medium
     */
    public function testGetEditorFormEdit($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['server'] = 1;
        PMA_TRI_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_TRI_getEditorForm('edit', $data)
        );
    }

    /**
     * Provider for testGetEditorFormEdit
     *
     * @return array
     */
    public function providerEdit()
    {
        $data = array(
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                "name='edit_item'"
            ),
            array(
                $data,
                "name='item_name'"
            ),
            array(
                $data,
                "name='item_table'"
            ),
            array(
                $data,
                "name='item_timing'"
            ),
            array(
                $data,
                "name='item_event'"
            ),
            array(
                $data,
                "name='item_definition'"
            ),
            array(
                $data,
                "name='item_definer'"
            ),
            array(
                $data,
                "name='editor_process_edit'"
            )
        );
    }

    /**
     * Test for PMA_TRI_getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerAjax
     */
    public function testGetEditorFormAjax($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        $GLOBALS['server'] = 1;
        PMA_TRI_setGlobals();
        $this->assertContains(
            $matcher,
            PMA_TRI_getEditorForm('edit', $data)
        );
    }

    /**
     * Provider for testGetEditorFormAjax
     *
     * @return array
     */
    public function providerAjax()
    {
        $data = array(
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => ''
        );

        return array(
            array(
                $data,
                "name='editor_process_edit'"
            ),
            array(
                $data,
                "name='ajax_request'"
            )
        );
    }
}
