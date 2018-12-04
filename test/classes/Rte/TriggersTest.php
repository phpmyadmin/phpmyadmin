<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Rte\Triggers
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Rte;

use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Triggers;
use PHPUnit\Framework\TestCase;

/**
 * This class is for testing PhpMyAdmin\Rte\Triggers methods
 *
 * @package PhpMyAdmin-test
 */
class TriggersTest extends TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['ServerDefault'] = '';
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['SendErrorReports'] = 'ask';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'] = true;
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'structure';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = '';
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['Confirm'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    /**
     * Test for Triggers::getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider providerGetDataFromRequestEmpty
     */
    public function testGetDataFromRequestEmpty($in, $out)
    {
        global $_POST;

        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_POST[$key] = $value;
            }
        }
        $this->assertEquals($out, Triggers::getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array
     */
    public function providerGetDataFromRequestEmpty()
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

    /**
     * Test for Triggers::getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAdd
     * @group medium
     */
    public function testGetEditorFormAdd($data, $matcher)
    {
        $GLOBALS['server'] = 1;
        Triggers::setGlobals();
        $this->assertContains(
            $matcher,
            Triggers::getEditorForm('add', $data)
        );
    }

    /**
     * Provider for testGetEditorFormAdd
     *
     * @return array
     */
    public function providerGetEditorFormAdd()
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
     * Test for Triggers::getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormEdit
     * @group medium
     */
    public function testGetEditorFormEdit($data, $matcher)
    {
        $GLOBALS['server'] = 1;
        Triggers::setGlobals();
        $this->assertContains(
            $matcher,
            Triggers::getEditorForm('edit', $data)
        );
    }

    /**
     * Provider for testGetEditorFormEdit
     *
     * @return array
     */
    public function providerGetEditorFormEdit()
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
     * Test for Triggers::getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAjax
     */
    public function testGetEditorFormAjax($data, $matcher)
    {
        $GLOBALS['server'] = 1;
        Response::getInstance()->setAjax(true);
        Triggers::setGlobals();
        $this->assertContains(
            $matcher,
            Triggers::getEditorForm('edit', $data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Provider for testGetEditorFormAjax
     *
     * @return array
     */
    public function providerGetEditorFormAjax()
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

    /**
     * Test for Triggers::getQueryFromRequest
     *
     * @param string $definer    Definer
     * @param string $name       Name
     * @param string $timing     Timing
     * @param string $event      Event
     * @param string $table      Table
     * @param string $definition Definition
     * @param string $query      Query
     * @param string $num_err    Error number
     *
     * @return void
     *
     * @dataProvider providerGetQueryFromRequest
     */
    public function testGetQueryFromRequest(
        $definer, $name, $timing, $event, $table, $definition, $query, $num_err
    ) {
        global $_POST, $errors;

        $errors = array();
        Triggers::setGlobals();

        $_POST['item_definer']    = $definer;
        $_POST['item_name']       = $name;
        $_POST['item_timing']     = $timing;
        $_POST['item_event']      = $event;
        $_POST['item_table']      = $table;
        $_POST['item_definition'] = $definition;
        $GLOBALS['server'] = 1;

        $this->assertEquals($query, Triggers::getQueryFromRequest());
        $this->assertCount($num_err, $errors);
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array
     */
    public function providerGetQueryFromRequest()
    {
        return array(
            array('',
                '',
                '',
                '',
                '',
                '',
                'CREATE TRIGGER ON  FOR EACH ROW ',
                5
            ),
            array(
                'root',
                'trigger',
                'BEFORE',
                'INSERT',
                'table`2',
                'SET @A=NULL',
                'CREATE TRIGGER `trigger` BEFORE INSERT ON  FOR EACH ROW SET @A=NULL',
                2
            ),
            array(
                'foo`s@host',
                'trigger`s test',
                'AFTER',
                'foo',
                'table3',
                'BEGIN SET @A=1; SET @B=2; END',
                'CREATE DEFINER=`foo``s`@`host` TRIGGER `trigger``s test` AFTER ON  FOR EACH ROW BEGIN SET @A=1; SET @B=2; END',
                2
            ),
            array(
                'root@localhost',
                'trigger',
                'BEFORE',
                'INSERT',
                'table1',
                'SET @A=NULL',
                'CREATE DEFINER=`root`@`localhost` TRIGGER `trigger` BEFORE INSERT ON `table1` FOR EACH ROW SET @A=NULL',
                0
            ),
        );
    }
}
