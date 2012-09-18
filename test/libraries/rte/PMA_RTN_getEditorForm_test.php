<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating routine editor
 *
 * @package PhpMyAdmin-test
 */
$GLOBALS['server'] = 0;
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once './libraries/Types.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/mysql_charsets.lib.php';
/*
 * Include to test.
 */
require_once 'libraries/rte/rte_routines.lib.php';

class PMA_RTN_getEditorForm_test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $cfg;

        $cfg['ShowFunctionFields'] = false;
        $GLOBALS['server'] = 0;
        $cfg['ServerDefault'] = 1;
        $GLOBALS['lang'] = 'en';
        $_SESSION[' PMA_token '] = 'token';
        $cfg['MySQLManualType'] = 'viewable';
        $cfg['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';

        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';

        $_SESSION[' PMA_token '] = 'token';
    }

    public function testgetParameterRow_empty()
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertEquals('', PMA_RTN_getParameterRow(array(), 0));
    }

    /**
     * @depends testgetParameterRow_empty
     * @dataProvider provider_row
     */
    public function testgetParameterRow($data, $index, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getParameterRow($data, $index), false);
    }

    public function provider_row()
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
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_dir[0]'
                    )
                )
            ),
            array(
                $data,
                0,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_param_name[0]'
                    )
                )
            ),
            array(
                $data,
                0,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_type[0]'
                    )
                )
            ),
            array(
                $data,
                0,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_param_length[0]'
                    )
                )
            ),
            array(
                $data,
                0,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_opts_num[0]'
                    )
                )
            ),
            array(
                $data,
                0,
                array(
                    'tag' => 'a',
                    'attributes' => array(
                        'class' => 'routine_param_remove_anchor'
                    )
                )
            ),
        );
    }

    /**
     * @depends testgetParameterRow
     * @dataProvider provider_row_ajax
     */
    public function testgetParameterRow_ajax($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getParameterRow($data), false);
    }

    public function provider_row_ajax()
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
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_dir[%s]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_param_name[%s]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_type[%s]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_param_length[%s]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_param_opts_num[%s]'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'a',
                    'attributes' => array(
                        'class' => 'routine_param_remove_anchor'
                    )
                )
            ),
        );
    }

    /**
     * @depends testgetParameterRow_ajax
     * @dataProvider provider_editor_1
     */
    public function testgetEditorForm_1($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getEditorForm('add', '', $data), false);
    }

    public function provider_editor_1()
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
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'add_item'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_type'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_changetype'
                    )
                )
            ),


            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_addparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_removeparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returntype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_returnlength'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returnopts_num'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'item_definition'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_isdeterministic'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_definer'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_securitytype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_sqldataaccess'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_comment'
                    )
                )
            ),
           array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_add'
                    )
                )
            ),

        );
    }

    /**
     * @depends testgetParameterRow_ajax
     * @dataProvider provider_editor_2
     */
    public function testgetEditorForm_2($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getEditorForm('edit', 'change', $data), false);
    }

    public function provider_editor_2()
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
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'edit_item'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_type',
                        'value' => 'FUNCTION'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_changetype'
                    )
                )
            ),


            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_addparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_removeparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returntype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_returnlength'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returnopts_num'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'item_definition'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_isdeterministic'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_definer'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_securitytype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_sqldataaccess'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_comment'
                    )
                )
            ),
           array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_edit'
                    )
                )
            ),

        );
    }

    /**
     * @depends testgetParameterRow_ajax
     * @dataProvider provider_editor_3
     */
    public function testgetEditorForm_3($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = true;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getEditorForm('edit', 'remove', $data), false);
    }

    public function provider_editor_3()
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
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'edit_item'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_name'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_type'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_addparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'routine_removeparameter'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returntype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_returnlength'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_returnopts_num'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'textarea',
                    'attributes' => array(
                        'name' => 'item_definition'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_isdeterministic'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_definer'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_securitytype'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'select',
                    'attributes' => array(
                        'name' => 'item_sqldataaccess'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_comment'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'ajax_request'
                    )
                )
            ),
            array(
                $data,
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'editor_process_edit'
                    )
                )
            ),

        );
    }

    /**
     * @depends testgetParameterRow_ajax
     * @dataProvider provider_editor_4
     */
    public function testgetEditorForm_4($data, $matcher)
    {
        $GLOBALS['is_ajax_request'] = false;
        PMA_RTN_setGlobals();
        $this->assertTag($matcher, PMA_RTN_getEditorForm('edit', 'change', $data), false);
    }

    public function provider_editor_4()
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
                array(
                    'tag' => 'input',
                    'attributes' => array(
                        'name' => 'item_type',
                        'value' => 'PROCEDURE'
                    )
                )
            ),
        );
    }
}
?>
