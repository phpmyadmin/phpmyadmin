<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Rte\Triggers
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Rte;

use PhpMyAdmin\Config;
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
     * @var Triggers
     */
    private $triggers;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $this->triggers = new Triggers($GLOBALS['dbi']);
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider providerGetDataFromRequestEmpty
     */
    public function testGetDataFromRequestEmpty($in, $out): void
    {
        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value !== '') {
                $_POST[$key] = $value;
            }
        }
        $this->assertEquals($out, $this->triggers->getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array
     */
    public function providerGetDataFromRequestEmpty()
    {
        return [
            [
                [
                    'item_name'               => '',
                    'item_table'              => '',
                    'item_original_name'      => '',
                    'item_action_timing'      => '',
                    'item_event_manipulation' => '',
                    'item_definition'         => '',
                    'item_definer'            => '',
                ],
                [
                    'item_name'               => '',
                    'item_table'              => '',
                    'item_original_name'      => '',
                    'item_action_timing'      => '',
                    'item_event_manipulation' => '',
                    'item_definition'         => '',
                    'item_definer'            => '',
                ],
            ],
            [
                [
                    'item_name'               => 'foo',
                    'item_table'              => 'foo',
                    'item_original_name'      => 'foo',
                    'item_action_timing'      => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition'         => 'foo',
                    'item_definer'            => 'foo',
                ],
                [
                    'item_name'               => 'foo',
                    'item_table'              => 'foo',
                    'item_original_name'      => 'foo',
                    'item_action_timing'      => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition'         => 'foo',
                    'item_definer'            => 'foo',
                ],
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAdd
     * @group medium
     */
    public function testGetEditorFormAdd($data, $matcher): void
    {
        $GLOBALS['server'] = 1;
        $this->triggers->setGlobals();
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('add', $data)
        );
    }

    /**
     * Provider for testGetEditorFormAdd
     *
     * @return array
     */
    public function providerGetEditorFormAdd()
    {
        $data = [
            'item_name'               => '',
            'item_table'              => 'table1',
            'item_original_name'      => '',
            'item_action_timing'      => '',
            'item_event_manipulation' => '',
            'item_definition'         => '',
            'item_definer'            => '',
        ];

        return [
            [
                $data,
                "name='add_item'",
            ],
            [
                $data,
                "name='item_name'",
            ],
            [
                $data,
                "name='item_table'",
            ],
            [
                $data,
                "name='item_timing'",
            ],
            [
                $data,
                "name='item_event'",
            ],
            [
                $data,
                "name='item_definition'",
            ],
            [
                $data,
                "name='item_definer'",
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
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormEdit
     * @group medium
     */
    public function testGetEditorFormEdit($data, $matcher): void
    {
        $GLOBALS['server'] = 1;
        $this->triggers->setGlobals();
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('edit', $data)
        );
    }

    /**
     * Provider for testGetEditorFormEdit
     *
     * @return array
     */
    public function providerGetEditorFormEdit()
    {
        $data = [
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => '',
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
                "name='item_table'",
            ],
            [
                $data,
                "name='item_timing'",
            ],
            [
                $data,
                "name='item_event'",
            ],
            [
                $data,
                "name='item_definition'",
            ],
            [
                $data,
                "name='item_definer'",
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
     * @param array $data    Data for trigger
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAjax
     */
    public function testGetEditorFormAjax($data, $matcher): void
    {
        $GLOBALS['server'] = 1;
        Response::getInstance()->setAjax(true);
        $this->triggers->setGlobals();
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('edit', $data)
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
        $data = [
            'item_name'               => 'foo',
            'item_table'              => 'table1',
            'item_original_name'      => 'bar',
            'item_action_timing'      => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition'         => 'SET @A=1;',
            'item_definer'            => '',
        ];

        return [
            [
                $data,
                "name='editor_process_edit'",
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
        $definer,
        $name,
        $timing,
        $event,
        $table,
        $definition,
        $query,
        $num_err
    ) {
        global $errors;

        $errors = [];
        $this->triggers->setGlobals();

        $_POST['item_definer']    = $definer;
        $_POST['item_name']       = $name;
        $_POST['item_timing']     = $timing;
        $_POST['item_event']      = $event;
        $_POST['item_table']      = $table;
        $_POST['item_definition'] = $definition;
        $GLOBALS['server'] = 1;

        $this->assertEquals($query, $this->triggers->getQueryFromRequest());
        $this->assertCount($num_err, $errors);
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array
     */
    public function providerGetQueryFromRequest()
    {
        return [
            [
                '',
                '',
                '',
                '',
                '',
                '',
                'CREATE TRIGGER ON  FOR EACH ROW ',
                5,
            ],
            [
                'root',
                'trigger',
                'BEFORE',
                'INSERT',
                'table`2',
                'SET @A=NULL',
                'CREATE TRIGGER `trigger` BEFORE INSERT ON  FOR EACH ROW SET @A=NULL',
                2,
            ],
            [
                'foo`s@host',
                'trigger`s test',
                'AFTER',
                'foo',
                'table3',
                'BEGIN SET @A=1; SET @B=2; END',
                'CREATE DEFINER=`foo``s`@`host` TRIGGER `trigger``s test` AFTER ON  FOR EACH ROW BEGIN SET @A=1; SET @B=2; END',
                2,
            ],
            [
                'root@localhost',
                'trigger',
                'BEFORE',
                'INSERT',
                'table1',
                'SET @A=NULL',
                'CREATE DEFINER=`root`@`localhost` TRIGGER `trigger` BEFORE INSERT ON `table1` FOR EACH ROW SET @A=NULL',
                0,
            ],
        ];
    }
}
