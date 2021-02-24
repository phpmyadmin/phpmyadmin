<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;

class EventsTest extends AbstractTestCase
{
    /** @var Events */
    private $events;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setLanguage();
        parent::setTheme();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $this->events = new Events(
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
    public function testGetDataFromRequestEmpty(array $in, array $out): void
    {
        unset($_POST);
        foreach ($in as $key => $value) {
            if ($value === '') {
                continue;
            }

            $_POST[$key] = $value;
        }
        $this->assertEquals($out, $this->events->getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array
     */
    public function providerGetDataFromRequest(): array
    {
        return [
            [
                [
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
                    'item_definer'        => '',
                ],
                [
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
                    'item_definer'        => '',
                ],
            ],
            [
                [
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
                    'item_definer'        => 'foo',
                ],
                [
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
                    'item_definer'        => 'foo',
                ],
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array  $data    Data for routine
     * @param string $matcher Matcher
     *
     * @dataProvider providerGetEditorFormAdd
     */
    public function testGetEditorFormAdd(array $data, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->events->getEditorForm('add', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorFormAdd
     *
     * @return array
     */
    public function providerGetEditorFormAdd(): array
    {
        $data = [
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
            'item_definer'        => '',
        ];

        return [
            [
                $data,
                '<input name="add_item"',
            ],
            [
                $data,
                '<input type="text" name="item_name"',
            ],
            [
                $data,
                '<select name="item_status"',
            ],
            [
                $data,
                '<input name="item_type"',
            ],
            [
                $data,
                '<input type="text" name="item_execute_at"',
            ],
            [
                $data,
                '<input type="text" name="item_ends"',
            ],
            [
                $data,
                '<textarea name="item_definition"',
            ],
            [
                $data,
                '<input type="text" name="item_definer"',
            ],
            [
                $data,
                '<input type="text" name="item_comment"',
            ],
            [
                $data,
                '<input type="submit" name="editor_process_add"',
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array  $data    Data for routine
     * @param string $matcher Matcher
     *
     * @dataProvider providerGetEditorFormEdit
     */
    public function testGetEditorFormEdit(array $data, string $matcher): void
    {
        $this->assertStringContainsString(
            $matcher,
            $this->events->getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorFormEdit
     *
     * @return array
     */
    public function providerGetEditorFormEdit(): array
    {
        $data = [
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
            'item_definer'        => '',
        ];

        return [
            [
                $data,
                '<input name="edit_item"',
            ],
            [
                $data,
                '<input type="text" name="item_name"',
            ],
            [
                $data,
                '<select name="item_status"',
            ],
            [
                $data,
                '<input name="item_type"',
            ],
            [
                $data,
                '<input type="text" name="item_execute_at"',
            ],
            [
                $data,
                '<input type="text" name="item_ends"',
            ],
            [
                $data,
                '<textarea name="item_definition"',
            ],
            [
                $data,
                '<input type="text" name="item_definer"',
            ],
            [
                $data,
                '<input type="text" name="item_comment"',
            ],
            [
                $data,
                '<input type="submit" name="editor_process_edit"',
            ],
        ];
    }

    /**
     * Test for getEditorForm
     *
     * @param array  $data    Data for routine
     * @param string $matcher Matcher
     *
     * @dataProvider providerGetEditorFormAjax
     */
    public function testGetEditorFormAjax(array $data, string $matcher): void
    {
        Response::getInstance()->setAjax(true);
        $this->assertStringContainsString(
            $matcher,
            $this->events->getEditorForm('edit', 'change', $data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetEditorFormAjax
     *
     * @return array
     */
    public function providerGetEditorFormAjax(): array
    {
        $data = [
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
            'item_definer'        => '',
        ];

        return [
            [
                $data,
                '<select name="item_type"',
            ],
            [
                $data,
                '<input type="hidden" name="editor_process_edit"',
            ],
            [
                $data,
                '<input type="hidden" name="ajax_request"',
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
        global $errors;

        $errors = [];

        unset($_POST);
        $_POST = $request;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $this->assertEquals($query, $this->events->getQueryFromRequest());
        $this->assertCount($num_err, $errors);
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
                [ // simple once-off event
                    'item_name'       => 's o m e e v e n t\\',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE AT \'2050-01-01 ' .
                '00:00:00\' ON COMPLETION NOT PRESERVE DO SET @A=0;',
                0,
            ],
            [
                [ // full once-off event
                    'item_name'       => 'evn',
                    'item_definer'    => 'me@home',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_preserve'   => 'ON',
                    'item_status'     => 'ENABLED',
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE DEFINER=`me`@`home` EVENT `evn` ON SCHEDULE AT ' .
                '\'2050-01-01 00:00:00\' ON COMPLETION PRESERVE ENABLE DO SET @A=0;',
                0,
            ],
            [
                [ // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;',
                ],
                'CREATE EVENT `rec_````evn` ON SCHEDULE EVERY 365 DAY ON ' .
                'COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
                0,
            ],
            [
                [ // full recurring event
                    'item_name'           => 'rec_evn2',
                    'item_definer'        => 'evil``user><\\@work\\',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_starts'         => '1900-01-01',
                    'item_ends'           => '2050-01-01',
                    'item_preserve'       => 'ON',
                    'item_status'         => 'SLAVESIDE_DISABLED',
                    'item_definition'     => 'SET @A=0;',
                ],
                'CREATE DEFINER=`evil````user><\`@`work\` EVENT `rec_evn2` ON ' .
                'SCHEDULE EVERY 365 DAY STARTS \'1900-01-01\' ENDS \'2050-01-01\' ' .
                'ON COMPLETION PRESERVE DISABLE ON SLAVE DO SET @A=0;',
                0,
            ],
            // Testing failures
            [
                [], // empty request
                'CREATE EVENT ON SCHEDULE ON COMPLETION NOT PRESERVE DO ',
                3,
            ],
            [
                [
                    'item_name'       => 's o m e e v e n t\\',
                    'item_definer'    => 'someuser', // invalid definer format
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '', // no execution time
                    'item_definition' => 'SET @A=0;',
                ],
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DO SET @A=0;',
                2,
            ],
            [
                [
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '', // no interval value
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;',
                ],
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1,
            ],
            [
                [ // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'CENTURIES', // invalid interval field
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;',
                ],
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1,
            ],
        ];
    }
}
