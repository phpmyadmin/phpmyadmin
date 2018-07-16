<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Rte\Routines
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Routines;
use PhpMyAdmin\Types;
use PHPUnit\Framework\TestCase;

/**
 * This class is for testing PhpMyAdmin\Rte\Routines methods
 *
 * @package PhpMyAdmin-test
 */
class RoutinesTest extends TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['ShowFunctionFields'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['DefaultFunctions']['FUNC_NUMBER'] = '';
        $GLOBALS['cfg']['DefaultFunctions']['FUNC_DATE'] = '';
        $GLOBALS['cfg']['DefaultFunctions']['FUNC_SPATIAL'] = 'GeomFromText';
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
    }

    /**
     * Test for Routines::getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider providerGetDataFromRequest
     */
    public function testGetDataFromRequest($in, $out)
    {
        global $_POST;

        unset($_POST); unset($_REQUEST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_POST[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }
        Routines::setGlobals();
        $this->assertEquals($out, Routines::getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequest
     *
     * @return array
     */
    public function providerGetDataFromRequest()
    {
        return array(
            array(
                array(
                    'item_name'                 => '',
                    'item_original_name'        => '',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => '',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => '',
                    'item_type_toggle'          => '',
                    'item_original_type'        => '',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => '',
                    'item_sqldataaccess'        => ''
                ),
                array(
                    'item_name'                 => '',
                    'item_original_name'        => '',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => '',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_num_params'           => 0,
                    'item_param_dir'            => array(),
                    'item_param_name'           => array(),
                    'item_param_type'           => array(),
                    'item_param_length'         => array(),
                    'item_param_opts_num'       => array(),
                    'item_param_opts_text'      => array(),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => ''
                )
            ),
            array(
                array(
                    'item_name'                 => 'proc2',
                    'item_original_name'        => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_param_dir'            => array(0 => 'IN', 1 => 'FAIL'),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => 'INT', 1 => 'FAIL'),
                    'item_param_length'         => array(0 => '20', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'latin1'),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                array(
                    'item_name'                 => 'proc2',
                    'item_original_name'        => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'PROCEDURE',
                    'item_type_toggle'          => 'FUNCTION',
                    'item_original_type'        => 'PROCEDURE',
                    'item_num_params'           => 2,
                    'item_param_dir'            => array(0 => 'IN', 1 => ''),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => 'INT', 1 => ''),
                    'item_param_length'         => array(0 => '20', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'latin1'),
                    'item_returntype'           => '',
                    'item_isdeterministic'      => ' checked=\'checked\'',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => ' selected=\'selected\'',
                    'item_sqldataaccess'        => 'NO SQL'
                )
            ),
            array(
                array(
                    'item_name'                 => 'func2',
                    'item_original_name'        => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'CHARSET utf8',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'FUNCTION',
                    'item_type_toggle'          => 'PROCEDURE',
                    'item_original_type'        => 'FUNCTION',
                    'item_param_dir'            => array(0 => '', 1 => ''),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(
                        0 => '<s>XSS</s>',
                        1 => 'TEXT'
                    ),
                    'item_param_length'         => array(0 => '10,10', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'utf8'),
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                array(
                    'item_name'                 => 'func2',
                    'item_original_name'        => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'CHARSET utf8',
                    'item_definition'           => 'SELECT NULL',
                    'item_comment'              => 'some text',
                    'item_definer'              => 'root@localhost',
                    'item_type'                 => 'FUNCTION',
                    'item_type_toggle'          => 'PROCEDURE',
                    'item_original_type'        => 'FUNCTION',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array(),
                    'item_param_name'           => array(0 => 'bar', 1 => 'baz'),
                    'item_param_type'           => array(0 => '', 1 => 'TEXT'),
                    'item_param_length'         => array(0 => '10,10', 1 => ''),
                    'item_param_opts_num'       => array(0 => 'UNSIGNED', 1 => ''),
                    'item_param_opts_text'      => array(0 => '', 1 => 'utf8'),
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => ' selected=\'selected\'',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => ''
                )
            ),
        );
    }

    /**
     * Test for Routines::getParameterRow
     *
     * @return void
     */
    public function testGetParameterRowEmpty()
    {
        Routines::setGlobals();
        $this->assertEquals('', Routines::getParameterRow(array(), 0));
    }

    /**
     * Test for Routines::getParameterRow
     *
     * @param array $data    Data for routine
     * @param mixed $index   Index
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRowEmpty
     * @dataProvider providerGetParameterRow
     */
    public function testGetParameterRow($data, $index, $matcher)
    {
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getParameterRow($data, $index)
        );
    }

    /**
     * Data provider for testGetParameterRow
     *
     * @return array
     */
    public function providerGetParameterRow()
    {
        $data = array(
            'item_name'                 => '',
            'item_original_name'        => '',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => '',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_type_toggle'          => 'FUNCTION',
            'item_original_type'        => 'PROCEDURE',
            'item_num_params'           => 1,
            'item_param_dir'            => array(0 => 'IN'),
            'item_param_name'           => array(0 => 'foo'),
            'item_param_type'           => array(0 => 'INT'),
            'item_param_length'         => array(0 => ''),
            'item_param_opts_num'       => array(0 => 'UNSIGNED'),
            'item_param_opts_text'      => array(0 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                0,
                "<select name='item_param_dir[0]'"
            ),
            array(
                $data,
                0,
                "<input name='item_param_name[0]'"
            ),
            array(
                $data,
                0,
                "<select name='item_param_type[0]'"
            ),
            array(
                $data,
                0,
                "<select name='item_param_opts_num[0]'"
            ),
            array(
                $data,
                0,
                "<a href='#' class='routine_param_remove_anchor'"
            ),
        );
    }

    /**
     * Test for Routines::getParameterRow
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRow
     * @dataProvider providerGetParameterRowAjax
     */
    public function testGetParameterRowAjax($data, $matcher)
    {
        Response::getInstance()->setAjax(true);
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getParameterRow($data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetParameterRowAjax
     *
     * @return array
     */
    public function providerGetParameterRowAjax()
    {
        $data = array(
            'item_name'                 => '',
            'item_original_name'        => '',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => '',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_type_toggle'          => 'FUNCTION',
            'item_original_type'        => 'PROCEDURE',
            'item_num_params'           => 1,
            'item_param_dir'            => array(0 => 'IN'),
            'item_param_name'           => array(0 => 'foo'),
            'item_param_type'           => array(0 => 'INT'),
            'item_param_length'         => array(0 => ''),
            'item_param_opts_num'       => array(0 => 'UNSIGNED'),
            'item_param_opts_text'      => array(0 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                "<select name='item_param_dir[%s]'"
            ),
            array(
                $data,
                "<input name='item_param_name[%s]'"
            ),
            array(
                $data,
                "<select name='item_param_dir[%s]'"
            ),
            array(
                $data,
                "<select name='item_param_opts_num[%s]'"
            ),
            array(
                $data,
                "<a href='#' class='routine_param_remove_anchor'"
            )
        );
    }

    /**
     * Test for Routines::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm1
     */
    public function testGetEditorForm1($data, $matcher)
    {
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getEditorForm('add', '', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm1
     *
     * @return array
     */
    public function providerGetEditorForm1()
    {
        $data = array(
            'item_name'                 => '',
            'item_original_name'        => '',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => '',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_type_toggle'          => 'FUNCTION',
            'item_original_type'        => 'PROCEDURE',
            'item_num_params'           => 0,
            'item_param_dir'            => array(),
            'item_param_name'           => array(),
            'item_param_type'           => array(),
            'item_param_length'         => array(),
            'item_param_opts_num'       => array(),
            'item_param_opts_text'      => array(),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
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
                "<input name='item_type'"
            ),
            array(
                $data,
                "name='routine_changetype'"
            ),
            array(
                $data,
                "name='routine_addparameter'"
            ),
            array(
                $data,
                "name='routine_removeparameter'"
            ),
            array(
                $data,
                "select name='item_returntype'"
            ),
            array(
                $data,
                "name='item_returnlength'"
            ),
            array(
                $data,
                "select name='item_returnopts_num'"
            ),
            array(
                $data,
                "<textarea name='item_definition'"
            ),
            array(
                $data,
                "name='item_isdeterministic'"
            ),
            array(
                $data,
                "name='item_definer'"
            ),
            array(
                $data,
                "select name='item_securitytype'"
            ),
            array(
                $data,
                "select name='item_sqldataaccess'"
            ),
            array(
                $data,
                "name='item_comment'"
            ),
            array(
                $data,
                "name='editor_process_add'"
            )
        );
    }

    /**
     * Test for Routines::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm2
     */
    public function testGetEditorForm2($data, $matcher)
    {
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm2
     *
     * @return array
     */
    public function providerGetEditorForm2()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_original_name'        => 'bar',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_type_toggle'          => 'FUNCTION',
            'item_original_type'        => 'PROCEDURE',
            'item_num_params'           => 1,
            'item_param_dir'            => array(0 => 'IN'),
            'item_param_name'           => array(0 => 'baz'),
            'item_param_type'           => array(0 => 'INT'),
            'item_param_length'         => array(0 => '20'),
            'item_param_opts_num'       => array(0 => 'UNSIGNED'),
            'item_param_opts_text'      => array(0 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL'
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
                "<input name='item_type' type='hidden' value='FUNCTION'"
            ),
            array(
                $data,
                "name='routine_changetype'"
            ),
            array(
                $data,
                "name='routine_addparameter'"
            ),
            array(
                $data,
                "name='routine_removeparameter'"
            ),
            array(
                $data,
                "name='item_returntype'"
            ),
            array(
                $data,
                "name='item_returnlength'"
            ),
            array(
                $data,
                "name='item_returnopts_num'"
            ),
            array(
                $data,
                "<textarea name='item_definition'"
            ),
            array(
                $data,
                "name='item_isdeterministic'"
            ),
            array(
                $data,
                "name='item_definer'"
            ),
            array(
                $data,
                "<select name='item_securitytype'"
            ),
            array(
                $data,
                "<select name='item_sqldataaccess'"
            ),
            array(
                $data,
                "name='item_comment'"
            ),
            array(
                $data,
                "name='editor_process_edit'"
            )
        );
    }

    /**
     * Test for Routines::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm3
     */
    public function testGetEditorForm3($data, $matcher)
    {
        Response::getInstance()->setAjax(true);
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getEditorForm('edit', 'remove', $data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetEditorForm3
     *
     * @return array
     */
    public function providerGetEditorForm3()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_original_name'        => 'bar',
            'item_returnlength'         => '',
            'item_returnopts_num'       => 'UNSIGNED',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'FUNCTION',
            'item_type_toggle'          => 'PROCEDURE',
            'item_original_type'        => 'FUNCTION',
            'item_num_params'           => 1,
            'item_param_dir'            => array(0 => ''),
            'item_param_name'           => array(0 => 'baz'),
            'item_param_type'           => array(0 => 'INT'),
            'item_param_length'         => array(0 => '20'),
            'item_param_opts_num'       => array(0 => 'UNSIGNED'),
            'item_param_opts_text'      => array(0 => ''),
            'item_returntype'           => 'INT',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL'
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
                "<select name='item_type'"
            ),
            array(
                $data,
                "name='routine_addparameter'"
            ),
            array(
                $data,
                "name='routine_removeparameter'"
            ),
            array(
                $data,
                "<select name='item_returntype'"
            ),
            array(
                $data,
                "name='item_returnlength'"
            ),
            array(
                $data,
                "<select name='item_returnopts_num'"
            ),
            array(
                $data,
                "<textarea name='item_definition'"
            ),
            array(
                $data,
                "name='item_isdeterministic'"
            ),
            array(
                $data,
                "name='item_definer'"
            ),
            array(
                $data,
                "<select name='item_securitytype'"
            ),
            array(
                $data,
                "<select name='item_sqldataaccess'"
            ),
            array(
                $data,
                "name='item_comment'"
            ),
            array(
                $data,
                "name='ajax_request'"
            ),
            array(
                $data,
                "name='editor_process_edit'"
            ),
        );
    }

    /**
     * Test for Routines::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm4
     */
    public function testGetEditorForm4($data, $matcher)
    {
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm4
     *
     * @return array
     */
    public function providerGetEditorForm4()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_original_name'        => 'bar',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'FUNCTION',
            'item_type_toggle'          => 'PROCEDURE',
            'item_original_type'        => 'PROCEDURE',
            'item_num_params'           => 1,
            'item_param_dir'            => array(0 => 'IN'),
            'item_param_name'           => array(0 => 'baz'),
            'item_param_type'           => array(0 => 'INT'),
            'item_param_length'         => array(0 => '20'),
            'item_param_opts_num'       => array(0 => 'UNSIGNED'),
            'item_param_opts_text'      => array(0 => ''),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL'
        );

        return array(
            array(
                $data,
                "<input name='item_type' type='hidden' value='PROCEDURE'"
            ),
        );
    }

    /**
     * Test for Routines::getExecuteForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetExecuteForm1
     */
    public function testGetExecuteForm1($data, $matcher)
    {
        Routines::setGlobals();
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $this->assertContains(
            $matcher,
            Routines::getExecuteForm($data)
        );
    }

    /**
     * Data provider for testGetExecuteForm1
     *
     * @return array
     */
    public function providerGetExecuteForm1()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => array(
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN'
            ),
            'item_param_name'           => array(
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe'
            ),
            'item_param_type'           => array(
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET'
            ),
            'item_param_length'         => array(
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'"
            ),
            'item_param_length_arr'     => array(
                0 => array(),
                1 => array('22'),
                2 => array(),
                3 => array(),
                4 => array("'a'", "'b'"),
                5 => array("'a'", "'b'")
            ),
            'item_param_opts_num'       => array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_param_opts_text'      => array(
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                "name='item_name'"
            ),
            array(
                $data,
                "name='funcs[foo]'"
            ),
            array(
                $data,
                "<input class='datefield' type='text' name='params[foo]' />"
            ),
            array(
                $data,
                "name='funcs[fob]'"
            ),
            array(
                $data,
                "<input class='datetimefield' type='text' name='params[fob]'"
            ),
            array(
                $data,
                "name='params[fod][]'"
            ),
            array(
                $data,
                "name='params[foe][]'"
            ),
            array(
                $data,
                "name='execute_routine'"
            ),
        );
    }

    /**
     * Test for Routines::getExecuteForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetExecuteForm2
     */
    public function testGetExecuteForm2($data, $matcher)
    {
        Response::getInstance()->setAjax(true);
        Routines::setGlobals();
        $this->assertContains(
            $matcher,
            Routines::getExecuteForm($data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetExecuteForm2
     *
     * @return array
     */
    public function providerGetExecuteForm2()
    {
        $data = array(
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => array(
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN'
            ),
            'item_param_name'           => array(
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe'
            ),
            'item_param_type'           => array(
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET'
            ),
            'item_param_length'         => array(
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'"
            ),
            'item_param_length_arr'     => array(
                0 => array(),
                1 => array('22'),
                2 => array(),
                3 => array(),
                4 => array("'a'", "'b'"),
                5 => array("'a'", "'b'")
            ),
            'item_param_opts_num'       => array(
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_param_opts_text'      => array(
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => ''
            ),
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => ''
        );

        return array(
            array(
                $data,
                "name='execute_routine'"
            ),
            array(
                $data,
                "name='ajax_request'"
            ),
        );
    }

    /**
     * Test for Routines::getQueryFromRequest
     *
     * @param array  $request Request
     * @param string $query   Query
     * @param int    $num_err Error number
     *
     * @return void
     *
     * @dataProvider providerGetQueryFromRequest
     */
    public function testGetQueryFromRequest($request, $query, $num_err)
    {
        global $_POST, $errors, $cfg;

        $cfg['ShowFunctionFields'] = false;

        $errors = array();
        Routines::setGlobals();

        $old_dbi = isset($GLOBALS['dbi']) ? $GLOBALS['dbi'] : null;
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will(
                $this->returnValueMap(
                    array(
                        array('foo', DatabaseInterface::CONNECT_USER, 'foo'),
                        array("foo's bar", DatabaseInterface::CONNECT_USER, "foo\'s bar"),
                        array('', DatabaseInterface::CONNECT_USER, '')
                    )
                )
            );
        $GLOBALS['dbi'] = $dbi;

        unset($_POST);
        $_POST = $request;
        $this->assertEquals($query, Routines::getQueryFromRequest());
        $this->assertCount($num_err, $errors);

        // reset
        $GLOBALS['dbi'] = $old_dbi;
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array
     */
    public function providerGetQueryFromRequest()
    {
        return array(
            // Testing success
            array(
                array(
                    'item_name'                 => 'p r o c',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => 'foo',
                    'item_definer'              => 'me@home',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '0',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                'CREATE DEFINER=`me`@`home` PROCEDURE `p r o c`() COMMENT \'foo\' '
                . 'DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;',
                0
            ),
            array(
                array(
                    'item_name'                 => 'pr``oc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => '',
                    'item_definer'              => 'someuser@somehost',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('IN', 'INOUT'),
                    'item_param_name'           => array('pa`ram', 'par 2'),
                    'item_param_type'           => array('INT', 'ENUM'),
                    'item_param_length'         => array('10', '\'a\', \'b\''),
                    'item_param_opts_num'       => array('ZEROFILL', ''),
                    'item_param_opts_text'      => array('utf8', 'latin1'),
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar'
                ),
                'CREATE DEFINER=`someuser`@`somehost` PROCEDURE `pr````oc`'
                . '(IN `pa``ram` INT(10) ZEROFILL, INOUT `par 2` ENUM(\'a\', \'b\')'
                . ' CHARSET latin1) NOT DETERMINISTIC SQL SECURITY DEFINER SELECT '
                . '\'foobar\';',
                0
            ),
            array(
                array(
                    'item_name'                 => 'func\\',
                    'item_returnlength'         => '5,5',
                    'item_returnopts_num'       => 'UNSIGNED ZEROFILL',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => 'foo\'s bar',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '1',
                    'item_param_dir'            => '',
                    'item_param_name'           => array('pa`ram'),
                    'item_param_type'           => array('VARCHAR'),
                    'item_param_length'         => array('45'),
                    'item_param_opts_num'       => array(''),
                    'item_param_opts_text'      => array('latin1'),
                    'item_returntype'           => 'DECIMAL',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'READ SQL DATA'
                ),
                'CREATE FUNCTION `func\\`(`pa``ram` VARCHAR(45) CHARSET latin1) '
                . 'RETURNS DECIMAL(5,5) UNSIGNED ZEROFILL COMMENT \'foo\\\'s bar\' '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT \'foobar\';',
                0
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '20',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'utf8',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '1',
                    'item_returntype'           => 'VARCHAR',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'READ SQL DATA'
                ),
                'CREATE FUNCTION `func`() RETURNS VARCHAR(20) CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;',
                0
            ),
            // Testing failures
            array(
                array(
                ),
                'CREATE () NOT DETERMINISTIC ', // invalid query
                3
            ),
            array(
                array(
                    'item_name'                 => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => 'foo',
                    'item_definer'              => 'mehome', // invalid definer format
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '0',
                    'item_param_dir'            => '',
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL'
                ),
                'CREATE PROCEDURE `proc`() COMMENT \'foo\' DETERMINISTIC '
                . 'NO SQL SQL SECURITY INVOKER SELECT 0;', // valid query
                1
            ),
            array(
                array(
                    'item_name'                 => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('FAIL', 'INOUT'), // invalid direction
                    'item_param_name'           => array('pa`ram', 'goo'),
                    'item_param_type'           => array('INT', 'ENUM'),
                    'item_param_length'         => array('10', ''), // missing ENUM values
                    'item_param_opts_num'       => array('ZEROFILL', ''),
                    'item_param_opts_text'      => array('utf8', 'latin1'),
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar' // invalid, will just be ignored without throwing errors
                ),
                'CREATE PROCEDURE `proc`((10) ZEROFILL, '
                . 'INOUT `goo` ENUM CHARSET latin1) NOT DETERMINISTIC '
                . 'SQL SECURITY DEFINER SELECT 0;', // invalid query
                2
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '', // missing length for VARCHAR
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'utf8',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '2',
                    'item_param_dir'            => array('IN'),
                    'item_param_name'           => array(''), // missing name
                    'item_param_type'           => array('INT'),
                    'item_param_length'         => array('10'),
                    'item_param_opts_num'       => array('ZEROFILL'),
                    'item_param_opts_text'      => array('latin1'),
                    'item_returntype'           => 'VARCHAR',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                'CREATE FUNCTION `func`() RETURNS VARCHAR CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                2
            ),
            array(
                array(
                    'item_name'                 => 'func',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '0',
                    'item_returntype'           => 'FAIL', // invalid return type
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => ''
                ),
                'CREATE FUNCTION `func`()  NOT DETERMINISTIC SQL '
                . 'SECURITY DEFINER SELECT 0;', // invalid query
                1
            ),
        );
    }
}
