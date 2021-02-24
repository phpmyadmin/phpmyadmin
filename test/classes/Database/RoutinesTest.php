<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Types;

class RoutinesTest extends AbstractTestCase
{
    /** @var Routines */
    private $routines;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setLanguage();
        parent::setTheme();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['text_dir'] = 'ltr';

        $this->routines = new Routines(
            $GLOBALS['dbi'],
            new Template(),
            Response::getInstance()
        );
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @dataProvider providerGetDataFromRequest
     */
    public function testGetDataFromRequest(array $in, array $out): void
    {
        unset($_POST);
        unset($_REQUEST);
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $_POST[$key] = $value;
            $_REQUEST[$key] = $value;
        }
        $this->assertEquals($out, $this->routines->getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequest
     *
     * @return array
     */
    public function providerGetDataFromRequest(): array
    {
        return [
            [
                [
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
                    'item_sqldataaccess'        => '',
                ],
                [
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
                    'item_param_dir'            => [],
                    'item_param_name'           => [],
                    'item_param_type'           => [],
                    'item_param_length'         => [],
                    'item_param_opts_num'       => [],
                    'item_param_opts_text'      => [],
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => '',
                ],
            ],
            [
                [
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
                    'item_param_dir'            => [
                        0 => 'IN',
                        1 => 'FAIL',
                    ],
                    'item_param_name'           => [
                        0 => 'bar',
                        1 => 'baz',
                    ],
                    'item_param_type'           => [
                        0 => 'INT',
                        1 => 'FAIL',
                    ],
                    'item_param_length'         => [
                        0 => '20',
                        1 => '',
                    ],
                    'item_param_opts_num'       => [
                        0 => 'UNSIGNED',
                        1 => '',
                    ],
                    'item_param_opts_text'      => [
                        0 => '',
                        1 => 'latin1',
                    ],
                    'item_returntype'           => '',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL',
                ],
                [
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
                    'item_param_dir'            => [
                        0 => 'IN',
                        1 => '',
                    ],
                    'item_param_name'           => [
                        0 => 'bar',
                        1 => 'baz',
                    ],
                    'item_param_type'           => [
                        0 => 'INT',
                        1 => '',
                    ],
                    'item_param_length'         => [
                        0 => '20',
                        1 => '',
                    ],
                    'item_param_opts_num'       => [
                        0 => 'UNSIGNED',
                        1 => '',
                    ],
                    'item_param_opts_text'      => [
                        0 => '',
                        1 => 'latin1',
                    ],
                    'item_returntype'           => '',
                    'item_isdeterministic'      => ' checked=\'checked\'',
                    'item_securitytype_definer' => '',
                    'item_securitytype_invoker' => ' selected=\'selected\'',
                    'item_sqldataaccess'        => 'NO SQL',
                ],
            ],
            [
                [
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
                    'item_param_dir'            => [
                        0 => '',
                        1 => '',
                    ],
                    'item_param_name'           => [
                        0 => 'bar',
                        1 => 'baz',
                    ],
                    'item_param_type'           => [
                        0 => '<s>XSS</s>',
                        1 => 'TEXT',
                    ],
                    'item_param_length'         => [
                        0 => '10,10',
                        1 => '',
                    ],
                    'item_param_opts_num'       => [
                        0 => 'UNSIGNED',
                        1 => '',
                    ],
                    'item_param_opts_text'      => [
                        0 => '',
                        1 => 'utf8',
                    ],
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => '',
                ],
                [
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
                    'item_param_dir'            => [],
                    'item_param_name'           => [
                        0 => 'bar',
                        1 => 'baz',
                    ],
                    'item_param_type'           => [
                        0 => '',
                        1 => 'TEXT',
                    ],
                    'item_param_length'         => [
                        0 => '10,10',
                        1 => '',
                    ],
                    'item_param_opts_num'       => [
                        0 => 'UNSIGNED',
                        1 => '',
                    ],
                    'item_param_opts_text'      => [
                        0 => '',
                        1 => 'utf8',
                    ],
                    'item_returntype'           => 'VARCHAR',
                    'item_isdeterministic'      => '',
                    'item_securitytype_definer' => ' selected=\'selected\'',
                    'item_securitytype_invoker' => '',
                    'item_sqldataaccess'        => '',
                ],
            ],
        ];
    }

    /**
     * Test for getParameterRow
     */
    public function testGetParameterRowEmpty(): void
    {
        $this->assertEquals('', $this->routines->getParameterRow([], 0));
    }

    /**
     * Test for getParameterRow
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRowEmpty
     * @dataProvider providerGetParameterRow
     */
    public function testGetParameterRow(array $data, int $index, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getParameterRow($data, $index)
        );
    }

    /**
     * Data provider for testGetParameterRow
     *
     * @return array
     */
    public function providerGetParameterRow(): array
    {
        $data = [
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
            'item_param_dir'            => [0 => 'IN'],
            'item_param_name'           => [0 => 'foo'],
            'item_param_type'           => [0 => 'INT'],
            'item_param_length'         => [0 => ''],
            'item_param_opts_num'       => [0 => 'UNSIGNED'],
            'item_param_opts_text'      => [0 => ''],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => '',
        ];

        return [
            [
                $data,
                0,
                '<select name="item_param_dir[0]"',
            ],
            [
                $data,
                0,
                '<input name="item_param_name[0]"',
            ],
            [
                $data,
                0,
                '<select name="item_param_type[0]"',
            ],
            [
                $data,
                0,
                '<select name="item_param_opts_num[0]"',
            ],
            [
                $data,
                0,
                '<a href="#" class="routine_param_remove_anchor"',
            ],
        ];
    }

    /**
     * Test for getParameterRow
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRow
     * @dataProvider providerGetParameterRowAjax
     */
    public function testGetParameterRowAjax(array $data, string $matcher): void
    {
        Response::getInstance()->setAjax(true);
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getParameterRow($data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetParameterRowAjax
     *
     * @return array
     */
    public function providerGetParameterRowAjax(): array
    {
        $data = [
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
            'item_param_dir'            => [0 => 'IN'],
            'item_param_name'           => [0 => 'foo'],
            'item_param_type'           => [0 => 'INT'],
            'item_param_length'         => [0 => ''],
            'item_param_opts_num'       => [0 => 'UNSIGNED'],
            'item_param_opts_text'      => [0 => ''],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => '',
        ];

        return [
            [
                $data,
                '<select name="item_param_dir[%s]"',
            ],
            [
                $data,
                '<input name="item_param_name[%s]"',
            ],
            [
                $data,
                '<select name="item_param_dir[%s]"',
            ],
            [
                $data,
                '<select name="item_param_opts_num[%s]"',
            ],
            [
                $data,
                '<a href="#" class="routine_param_remove_anchor"',
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm1
     */
    public function testGetEditorForm1(array $data, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getEditorForm('add', '', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm1
     *
     * @return array
     */
    public function providerGetEditorForm1(): array
    {
        $data = [
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
            'item_param_dir'            => [],
            'item_param_name'           => [],
            'item_param_type'           => [],
            'item_param_length'         => [],
            'item_param_opts_num'       => [],
            'item_param_opts_text'      => [],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => '',
        ];

        return [
            [
                $data,
                "<input name='add_item'",
            ],
            [
                $data,
                "<input type='text' name='item_name'",
            ],
            [
                $data,
                "<input name='item_type'",
            ],
            [
                $data,
                "name='routine_changetype'",
            ],
            [
                $data,
                "name='routine_addparameter'",
            ],
            [
                $data,
                "name='routine_removeparameter'",
            ],
            [
                $data,
                "select name='item_returntype'",
            ],
            [
                $data,
                "name='item_returnlength'",
            ],
            [
                $data,
                "select name='item_returnopts_num'",
            ],
            [
                $data,
                "<textarea name='item_definition'",
            ],
            [
                $data,
                "name='item_isdeterministic'",
            ],
            [
                $data,
                "name='item_definer'",
            ],
            [
                $data,
                "select name='item_securitytype'",
            ],
            [
                $data,
                "select name='item_sqldataaccess'",
            ],
            [
                $data,
                "name='item_comment'",
            ],
            [
                $data,
                "name='editor_process_add'",
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm2
     */
    public function testGetEditorForm2(array $data, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm2
     *
     * @return array
     */
    public function providerGetEditorForm2(): array
    {
        $data = [
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
            'item_param_dir'            => [0 => 'IN'],
            'item_param_name'           => [0 => 'baz'],
            'item_param_type'           => [0 => 'INT'],
            'item_param_length'         => [0 => '20'],
            'item_param_opts_num'       => [0 => 'UNSIGNED'],
            'item_param_opts_text'      => [0 => ''],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL',
        ];

        return [
            [
                $data,
                "name='edit_item'",
            ],
            [
                $data,
                "name='item_name'",
            ],
            [
                $data,
                "<input name='item_type' type='hidden' value='FUNCTION'",
            ],
            [
                $data,
                "name='routine_changetype'",
            ],
            [
                $data,
                "name='routine_addparameter'",
            ],
            [
                $data,
                "name='routine_removeparameter'",
            ],
            [
                $data,
                "name='item_returntype'",
            ],
            [
                $data,
                "name='item_returnlength'",
            ],
            [
                $data,
                "name='item_returnopts_num'",
            ],
            [
                $data,
                "<textarea name='item_definition'",
            ],
            [
                $data,
                "name='item_isdeterministic'",
            ],
            [
                $data,
                "name='item_definer'",
            ],
            [
                $data,
                "<select name='item_securitytype'",
            ],
            [
                $data,
                "<select name='item_sqldataaccess'",
            ],
            [
                $data,
                "name='item_comment'",
            ],
            [
                $data,
                "name='editor_process_edit'",
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm3
     */
    public function testGetEditorForm3(array $data, string $matcher): void
    {
        Response::getInstance()->setAjax(true);
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getEditorForm('edit', 'remove', $data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetEditorForm3
     *
     * @return array
     */
    public function providerGetEditorForm3(): array
    {
        $data = [
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
            'item_param_dir'            => [0 => ''],
            'item_param_name'           => [0 => 'baz'],
            'item_param_type'           => [0 => 'INT'],
            'item_param_length'         => [0 => '20'],
            'item_param_opts_num'       => [0 => 'UNSIGNED'],
            'item_param_opts_text'      => [0 => ''],
            'item_returntype'           => 'INT',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL',
        ];

        return [
            [
                $data,
                "name='edit_item'",
            ],
            [
                $data,
                "name='item_name'",
            ],
            [
                $data,
                "<select name='item_type'",
            ],
            [
                $data,
                "name='routine_addparameter'",
            ],
            [
                $data,
                "name='routine_removeparameter'",
            ],
            [
                $data,
                "<select name='item_returntype'",
            ],
            [
                $data,
                "name='item_returnlength'",
            ],
            [
                $data,
                "<select name='item_returnopts_num'",
            ],
            [
                $data,
                "<textarea name='item_definition'",
            ],
            [
                $data,
                "name='item_isdeterministic'",
            ],
            [
                $data,
                "name='item_definer'",
            ],
            [
                $data,
                "<select name='item_securitytype'",
            ],
            [
                $data,
                "<select name='item_sqldataaccess'",
            ],
            [
                $data,
                "name='item_comment'",
            ],
            [
                $data,
                "name='ajax_request'",
            ],
            [
                $data,
                "name='editor_process_edit'",
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array $data Data for routine
     *
     * @depends testGetParameterRowAjax
     * @dataProvider providerGetEditorForm4
     */
    public function testGetEditorForm4(array $data, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorForm4
     *
     * @return array
     */
    public function providerGetEditorForm4(): array
    {
        $data = [
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
            'item_param_dir'            => [0 => 'IN'],
            'item_param_name'           => [0 => 'baz'],
            'item_param_type'           => [0 => 'INT'],
            'item_param_length'         => [0 => '20'],
            'item_param_opts_num'       => [0 => 'UNSIGNED'],
            'item_param_opts_text'      => [0 => ''],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => 'NO SQL',
        ];

        return [
            [
                $data,
                "<input name='item_type' type='hidden' value='PROCEDURE'",
            ],
        ];
    }

    /**
     * Test for getExecuteForm
     *
     * @param array $data Data for routine
     *
     * @dataProvider providerGetExecuteForm1
     */
    public function testGetExecuteForm1(array $data, string $matcher): void
    {
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $this->assertStringContainsString(
            $matcher,
            $this->routines->getExecuteForm($data)
        );
    }

    /**
     * Data provider for testGetExecuteForm1
     *
     * @return array
     */
    public function providerGetExecuteForm1(): array
    {
        $data = [
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => [
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN',
            ],
            'item_param_name'           => [
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe',
            ],
            'item_param_type'           => [
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET',
            ],
            'item_param_length'         => [
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'",
            ],
            'item_param_length_arr'     => [
                0 => [],
                1 => ['22'],
                2 => [],
                3 => [],
                4 => [
                    "'a'",
                    "'b'",
                ],
                5 => [
                    "'a'",
                    "'b'",
                ],
            ],
            'item_param_opts_num'       => [
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
            ],
            'item_param_opts_text'      => [
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
            ],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => '',
        ];

        return [
            [
                $data,
                "name='item_name'",
            ],
            [
                $data,
                "name='funcs[foo]'",
            ],
            [
                $data,
                "<input class='datefield' type='text' name='params[foo]'>",
            ],
            [
                $data,
                "name='funcs[fob]'",
            ],
            [
                $data,
                "<input class='datetimefield' type='text' name='params[fob]'",
            ],
            [
                $data,
                "name='params[fod][]'",
            ],
            [
                $data,
                "name='params[foe][]'",
            ],
            [
                $data,
                "name='execute_routine'",
            ],
        ];
    }

    /**
     * Test for getExecuteForm
     *
     * @param array $data Data for routine
     *
     * @dataProvider providerGetExecuteForm2
     */
    public function testGetExecuteForm2(array $data, string $matcher): void
    {
        Response::getInstance()->setAjax(true);
        $this->assertStringContainsString(
            $matcher,
            $this->routines->getExecuteForm($data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetExecuteForm2
     *
     * @return array
     */
    public function providerGetExecuteForm2(): array
    {
        $data = [
            'item_name'                 => 'foo',
            'item_returnlength'         => '',
            'item_returnopts_num'       => '',
            'item_returnopts_text'      => '',
            'item_definition'           => 'SELECT 1;',
            'item_comment'              => '',
            'item_definer'              => '',
            'item_type'                 => 'PROCEDURE',
            'item_num_params'           => 6,
            'item_param_dir'            => [
                0 => 'IN',
                1 => 'OUT',
                2 => 'IN',
                3 => 'IN',
                4 => 'IN',
                5 => 'IN',
            ],
            'item_param_name'           => [
                0 => 'foo',
                1 => 'foa',
                2 => 'fob',
                3 => 'foc',
                4 => 'fod',
                5 => 'foe',
            ],
            'item_param_type'           => [
                0 => 'DATE',
                1 => 'VARCHAR',
                2 => 'DATETIME',
                3 => 'GEOMETRY',
                4 => 'ENUM',
                5 => 'SET',
            ],
            'item_param_length'         => [
                0 => '',
                1 => '22',
                2 => '',
                3 => '',
                4 => "'a','b'",
                5 => "'a','b'",
            ],
            'item_param_length_arr'     => [
                0 => [],
                1 => ['22'],
                2 => [],
                3 => [],
                4 => [
                    "'a'",
                    "'b'",
                ],
                5 => [
                    "'a'",
                    "'b'",
                ],
            ],
            'item_param_opts_num'       => [
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
            ],
            'item_param_opts_text'      => [
                0 => '',
                1 => 'utf8',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
            ],
            'item_returntype'           => '',
            'item_isdeterministic'      => '',
            'item_securitytype_definer' => '',
            'item_securitytype_invoker' => '',
            'item_sqldataaccess'        => '',
        ];

        return [
            [
                $data,
                "name='execute_routine'",
            ],
            [
                $data,
                "name='ajax_request'",
            ],
        ];
    }

    /**
     * Test for getQueryFromRequest
     *
     * @param array  $request Request
     * @param string $query   Query
     * @param int    $num_err Error number
     *
     * @dataProvider providerGetQueryFromRequest
     */
    public function testGetQueryFromRequest(array $request, string $query, int $num_err): void
    {
        global $errors, $cfg;

        $cfg['ShowFunctionFields'] = false;

        $errors = [];

        $old_dbi = $GLOBALS['dbi'] ?? null;
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'foo',
                            DatabaseInterface::CONNECT_USER,
                            'foo',
                        ],
                        [
                            "foo's bar",
                            DatabaseInterface::CONNECT_USER,
                            "foo\'s bar",
                        ],
                        [
                            '',
                            DatabaseInterface::CONNECT_USER,
                            '',
                        ],
                    ]
                )
            );
        $GLOBALS['dbi'] = $dbi;

        $routines = new Routines(
            $dbi,
            new Template(),
            Response::getInstance()
        );

        unset($_POST);
        $_POST = $request;
        $this->assertEquals($query, $routines->getQueryFromRequest());
        $this->assertCount($num_err, $errors);

        // reset
        $GLOBALS['dbi'] = $old_dbi;
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array
     */
    public function providerGetQueryFromRequest(): array
    {
        return [
            // Testing success
            [
                [
                    'item_name'                 => 'p r o c',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => 'foo',
                    'item_definer'              => 'me@home',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '0',
                    'item_param_dir'            => [],
                    'item_param_name'           => '',
                    'item_param_type'           => '',
                    'item_param_length'         => '',
                    'item_param_opts_num'       => '',
                    'item_param_opts_text'      => '',
                    'item_returntype'           => '',
                    'item_isdeterministic'      => '',
                    'item_securitytype'         => 'INVOKER',
                    'item_sqldataaccess'        => 'NO SQL',
                ],
                'CREATE DEFINER=`me`@`home` PROCEDURE `p r o c`() COMMENT \'foo\' '
                . 'DETERMINISTIC NO SQL SQL SECURITY INVOKER SELECT 0;',
                0,
            ],
            [
                [
                    'item_name'                 => 'pr``oc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => '',
                    'item_definer'              => 'someuser@somehost',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => [
                        'IN',
                        'INOUT',
                    ],
                    'item_param_name'           => [
                        'pa`ram',
                        'par 2',
                    ],
                    'item_param_type'           => [
                        'INT',
                        'ENUM',
                    ],
                    'item_param_length'         => [
                        '10',
                        '\'a\', \'b\'',
                    ],
                    'item_param_opts_num'       => [
                        'ZEROFILL',
                        '',
                    ],
                    'item_param_opts_text'      => [
                        'utf8',
                        'latin1',
                    ],
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar',
                ],
                'CREATE DEFINER=`someuser`@`somehost` PROCEDURE `pr````oc`'
                . '(IN `pa``ram` INT(10) ZEROFILL, INOUT `par 2` ENUM(\'a\', \'b\')'
                . ' CHARSET latin1) NOT DETERMINISTIC SQL SECURITY DEFINER SELECT '
                . '\'foobar\';',
                0,
            ],
            [
                [
                    'item_name'                 => 'func\\',
                    'item_returnlength'         => '5,5',
                    'item_returnopts_num'       => 'UNSIGNED ZEROFILL',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT \'foobar\';',
                    'item_comment'              => 'foo\'s bar',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '1',
                    'item_param_dir'            => [],
                    'item_param_name'           => ['pa`ram'],
                    'item_param_type'           => ['VARCHAR'],
                    'item_param_length'         => ['45'],
                    'item_param_opts_num'       => [''],
                    'item_param_opts_text'      => ['latin1'],
                    'item_returntype'           => 'DECIMAL',
                    'item_isdeterministic'      => 'ON',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'READ SQL DATA',
                ],
                'CREATE FUNCTION `func\\`(`pa``ram` VARCHAR(45) CHARSET latin1) '
                . 'RETURNS DECIMAL(5,5) UNSIGNED ZEROFILL COMMENT \'foo\\\'s bar\' '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT \'foobar\';',
                0,
            ],
            [
                [
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
                    'item_sqldataaccess'        => 'READ SQL DATA',
                ],
                'CREATE FUNCTION `func`() RETURNS VARCHAR(20) CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;',
                0,
            ],
            // Testing failures
            [
                [],
                'CREATE () NOT DETERMINISTIC ', // invalid query
                3,
            ],
            [
                [
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
                    'item_sqldataaccess'        => 'NO SQL',
                ],
                'CREATE PROCEDURE `proc`() COMMENT \'foo\' DETERMINISTIC '
                . 'NO SQL SQL SECURITY INVOKER SELECT 0;', // valid query
                1,
            ],
            [
                [
                    'item_name'                 => 'proc',
                    'item_returnlength'         => '',
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => '',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'PROCEDURE',
                    'item_num_params'           => '2',
                    'item_param_dir'            => [
                        'FAIL',
                        'INOUT',
                    ], // invalid direction
                    'item_param_name'           => [
                        'pa`ram',
                        'goo',
                    ],
                    'item_param_type'           => [
                        'INT',
                        'ENUM',
                    ],
                    'item_param_length'         => [
                        '10',
                        '',
                    ], // missing ENUM values
                    'item_param_opts_num'       => [
                        'ZEROFILL',
                        '',
                    ],
                    'item_param_opts_text'      => [
                        'utf8',
                        'latin1',
                    ],
                    'item_returntype'           => '',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => 'foobar', // invalid, will just be ignored without throwing errors
                ],
                'CREATE PROCEDURE `proc`((10) ZEROFILL, '
                . 'INOUT `goo` ENUM CHARSET latin1) NOT DETERMINISTIC '
                . 'SQL SECURITY DEFINER SELECT 0;', // invalid query
                2,
            ],
            [
                [
                    'item_name'                 => 'func',
                    'item_returnlength'         => '', // missing length for VARCHAR
                    'item_returnopts_num'       => '',
                    'item_returnopts_text'      => 'utf8',
                    'item_definition'           => 'SELECT 0;',
                    'item_comment'              => '',
                    'item_definer'              => '',
                    'item_type'                 => 'FUNCTION',
                    'item_num_params'           => '2',
                    'item_param_dir'            => ['IN'],
                    'item_param_name'           => [''], // missing name
                    'item_param_type'           => ['INT'],
                    'item_param_length'         => ['10'],
                    'item_param_opts_num'       => ['ZEROFILL'],
                    'item_param_opts_text'      => ['latin1'],
                    'item_returntype'           => 'VARCHAR',
                    'item_securitytype'         => 'DEFINER',
                    'item_sqldataaccess'        => '',
                ],
                'CREATE FUNCTION `func`() RETURNS VARCHAR CHARSET utf8 NOT '
                . 'DETERMINISTIC SQL SECURITY DEFINER SELECT 0;', // invalid query
                2,
            ],
            [
                [
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
                    'item_sqldataaccess'        => '',
                ],
                'CREATE FUNCTION `func`()  NOT DETERMINISTIC SQL '
                . 'SECURITY DEFINER SELECT 0;', // invalid query
                1,
            ],
        ];
    }
}
