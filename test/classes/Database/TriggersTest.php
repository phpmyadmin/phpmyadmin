<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Triggers;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Database\Triggers */
class TriggersTest extends AbstractTestCase
{
    private Triggers $triggers;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setGlobalConfig();

        parent::setLanguage();

        parent::setTheme();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table';

        $this->triggers = new Triggers(
            $GLOBALS['dbi'],
            new Template(),
            ResponseRenderer::getInstance(),
        );
    }

    /**
     * Test for getDataFromRequest
     *
     * @param array<string, string> $in  Input
     * @param array<string, string> $out Expected output
     *
     * @dataProvider providerGetDataFromRequestEmpty
     */
    public function testGetDataFromRequestEmpty(array $in, array $out): void
    {
        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $_POST[$key] = $value;
        }

        $this->assertEquals($out, $this->triggers->getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array<array{array<string, string>, array<string, string>}>
     */
    public static function providerGetDataFromRequestEmpty(): array
    {
        return [
            [
                [
                    'item_name' => '',
                    'item_table' => '',
                    'item_original_name' => '',
                    'item_action_timing' => '',
                    'item_event_manipulation' => '',
                    'item_definition' => '',
                    'item_definer' => '',
                ],
                [
                    'item_name' => '',
                    'item_table' => '',
                    'item_original_name' => '',
                    'item_action_timing' => '',
                    'item_event_manipulation' => '',
                    'item_definition' => '',
                    'item_definer' => '',
                ],
            ],
            [
                [
                    'item_name' => 'foo',
                    'item_table' => 'foo',
                    'item_original_name' => 'foo',
                    'item_action_timing' => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition' => 'foo',
                    'item_definer' => 'foo',
                ],
                [
                    'item_name' => 'foo',
                    'item_table' => 'foo',
                    'item_original_name' => 'foo',
                    'item_action_timing' => 'foo',
                    'item_event_manipulation' => 'foo',
                    'item_definition' => 'foo',
                    'item_definer' => 'foo',
                ],
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array<string, string> $data Data for trigger
     *
     * @dataProvider providerGetEditorFormAdd
     * @group medium
     */
    public function testGetEditorFormAdd(array $data, string $matcher): void
    {
        $GLOBALS['server'] = 1;
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('pma_test', 'table', 'add', $data),
        );
    }

    /**
     * Provider for testGetEditorFormAdd
     *
     * @return array<array{array<string, string>, string}>
     */
    public static function providerGetEditorFormAdd(): array
    {
        $data = [
            'item_name' => '',
            'item_table' => 'table1',
            'item_original_name' => '',
            'item_action_timing' => '',
            'item_event_manipulation' => '',
            'item_definition' => '',
            'item_definer' => '',
        ];

        return [
            [$data, 'name="add_item"'],
            [$data, 'name="item_name"'],
            [$data, 'name="item_table"'],
            [$data, 'name="item_timing"'],
            [$data, 'name="item_event"'],
            [$data, 'name="item_definition"'],
            [$data, 'name="item_definer"'],
            [$data, 'name="editor_process_add"'],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array<string, string> $data Data for trigger
     *
     * @dataProvider providerGetEditorFormEdit
     * @group medium
     */
    public function testGetEditorFormEdit(array $data, string $matcher): void
    {
        $GLOBALS['server'] = 1;
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('pma_test', 'table', 'edit', $data),
        );
    }

    /**
     * Provider for testGetEditorFormEdit
     *
     * @return array<array{array<string, string>, string}>
     */
    public static function providerGetEditorFormEdit(): array
    {
        $data = [
            'item_name' => 'foo',
            'item_table' => 'table1',
            'item_original_name' => 'bar',
            'item_action_timing' => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition' => 'SET @A=1;',
            'item_definer' => '',
        ];

        return [
            [$data, 'name="edit_item"'],
            [$data, 'name="item_name"'],
            [$data, 'name="item_table"'],
            [$data, 'name="item_timing"'],
            [$data, 'name="item_event"'],
            [$data, 'name="item_definition"'],
            [$data, 'name="item_definer"'],
            [$data, 'name="editor_process_edit"'],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array<string, string> $data Data for trigger
     *
     * @dataProvider providerGetEditorFormAjax
     */
    public function testGetEditorFormAjax(array $data, string $matcher): void
    {
        $GLOBALS['server'] = 1;
        ResponseRenderer::getInstance()->setAjax(true);
        $this->assertStringContainsString(
            $matcher,
            $this->triggers->getEditorForm('pma_test', 'table', 'edit', $data),
        );
        ResponseRenderer::getInstance()->setAjax(false);
    }

    /**
     * Provider for testGetEditorFormAjax
     *
     * @return array<array{array<string, string>, string}>
     */
    public static function providerGetEditorFormAjax(): array
    {
        $data = [
            'item_name' => 'foo',
            'item_table' => 'table1',
            'item_original_name' => 'bar',
            'item_action_timing' => 'BEFORE',
            'item_event_manipulation' => 'INSERT',
            'item_definition' => 'SET @A=1;',
            'item_definer' => '',
        ];

        return [[$data, 'name="editor_process_edit"'], [$data, 'name="ajax_request"']];
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
     * @param int    $numErr     Error number
     *
     * @dataProvider providerGetQueryFromRequest
     */
    public function testGetQueryFromRequest(
        string $definer,
        string $name,
        string $timing,
        string $event,
        string $table,
        string $definition,
        string $query,
        int $numErr,
    ): void {
        $GLOBALS['errors'] = [];

        $_POST['item_definer'] = $definer;
        $_POST['item_name'] = $name;
        $_POST['item_timing'] = $timing;
        $_POST['item_event'] = $event;
        $_POST['item_table'] = $table;
        $_POST['item_definition'] = $definition;
        $GLOBALS['server'] = 1;

        $this->assertEquals($query, $this->triggers->getQueryFromRequest());
        $this->assertCount($numErr, $GLOBALS['errors']);
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array<array{string, string, string, string, string, string, string, int}>
     */
    public static function providerGetQueryFromRequest(): array
    {
        return [
            ['', '', '', '', '', '', 'CREATE TRIGGER ON  FOR EACH ROW ', 5],
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
                'CREATE DEFINER=`foo``s`@`host` TRIGGER `trigger``s test`'
                    . ' AFTER ON  FOR EACH ROW BEGIN SET @A=1; SET @B=2; END',
                2,
            ],
            [
                'root@localhost',
                'trigger',
                'BEFORE',
                'INSERT',
                'table1',
                'SET @A=NULL',
                'CREATE DEFINER=`root`@`localhost` TRIGGER `trigger`'
                    . ' BEFORE INSERT ON `table1` FOR EACH ROW SET @A=NULL',
                0,
            ],
        ];
    }
}
