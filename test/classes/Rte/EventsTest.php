<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Rte\Events
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Rte;

use PhpMyAdmin\Response;
use PhpMyAdmin\Rte\Events;
use PHPUnit\Framework\TestCase;

/**
 * This class is for testing PhpMyAdmin\Rte\Events methods
 *
 * @package PhpMyAdmin-test
 */
class EventsTest extends TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
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
        $GLOBALS['cfg']['ServerDefault'] = '';
        $GLOBALS['tear_down']['server'] = true;
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
     * Test for Events::getDataFromRequest
     *
     * @param array $in  Input
     * @param array $out Expected output
     *
     * @return void
     *
     * @dataProvider providerGetDataFromRequest
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
        $this->assertEquals($out, Events::getDataFromRequest());
    }

    /**
     * Data provider for testGetDataFromRequestEmpty
     *
     * @return array
     */
    public function providerGetDataFromRequest()
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

    /**
     * Test for Events::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAdd
     */
    public function testGetEditorFormAdd($data, $matcher)
    {
        Events::setGlobals();
        $this->assertContains(
            $matcher,
            Events::getEditorForm('add', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorFormAdd
     *
     * @return array
     */
    public function providerGetEditorFormAdd()
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
     * Test for Events::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormEdit
     */
    public function testGetEditorFormEdit($data, $matcher)
    {
        Events::setGlobals();
        $this->assertContains(
            $matcher,
            Events::getEditorForm('edit', 'change', $data)
        );
    }

    /**
     * Data provider for testGetEditorFormEdit
     *
     * @return array
     */
    public function providerGetEditorFormEdit()
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
     * Test for Events::getEditorForm
     *
     * @param array $data    Data for routine
     * @param array $matcher Matcher
     *
     * @return void
     *
     * @dataProvider providerGetEditorFormAjax
     */
    public function testGetEditorFormAjax($data, $matcher)
    {
        Response::getInstance()->setAjax(true);
        Events::setGlobals();
        $this->assertContains(
            $matcher,
            Events::getEditorForm('edit', 'change', $data)
        );
        Response::getInstance()->setAjax(false);
    }

    /**
     * Data provider for testGetEditorFormAjax
     *
     * @return array
     */
    public function providerGetEditorFormAjax()
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

    /**
     * Test for Events::getQueryFromRequest
     *
     * @param array  $request Request
     * @param string $query   Query
     * @param array  $num_err Error number
     *
     * @return void
     *
     * @dataProvider providerGetQueryFromRequest
     */
    public function testGetQueryFromRequest($request, $query, $num_err)
    {
        global $_POST, $errors;

        $errors = array();
        Events::setGlobals();

        unset($_POST);
        $_POST = $request;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $this->assertEquals($query, Events::getQueryFromRequest());
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
            // Testing success
            array(
                array( // simple once-off event
                    'item_name'       => 's o m e e v e n t\\',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE AT \'2050-01-01 ' .
                '00:00:00\' ON COMPLETION NOT PRESERVE DO SET @A=0;',
                0
            ),
            array(
                array( // full once-off event
                    'item_name'       => 'evn',
                    'item_definer'    => 'me@home',
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '2050-01-01 00:00:00',
                    'item_preserve'   => 'ON',
                    'item_status'     => 'ENABLED',
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE DEFINER=`me`@`home` EVENT `evn` ON SCHEDULE AT ' .
                '\'2050-01-01 00:00:00\' ON COMPLETION PRESERVE ENABLE DO SET @A=0;',
                0
            ),
            array(
                array( // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE EVERY 365 DAY ON ' .
                'COMPLETION NOT PRESERVE DISABLE DO SET @A=0;',
                0
            ),
            array(
                array( // full recurring event
                    'item_name'           => 'rec_evn2',
                    'item_definer'        => 'evil``user><\\@work\\',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'DAY',
                    'item_starts'         => '1900-01-01',
                    'item_ends'           => '2050-01-01',
                    'item_preserve'       => 'ON',
                    'item_status'         => 'SLAVESIDE_DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE DEFINER=`evil````user><\`@`work\` EVENT `rec_evn2` ON ' .
                'SCHEDULE EVERY 365 DAY STARTS \'1900-01-01\' ENDS \'2050-01-01\' ' .
                'ON COMPLETION PRESERVE DISABLE ON SLAVE DO SET @A=0;',
                0
            ),
            // Testing failures
            array(
                array( // empty request
                ),
                'CREATE EVENT ON SCHEDULE ON COMPLETION NOT PRESERVE DO ',
                3
            ),
            array(
                array(
                    'item_name'       => 's o m e e v e n t\\',
                    'item_definer'    => 'someuser', // invalid definer format
                    'item_type'       => 'ONE TIME',
                    'item_execute_at' => '', // no execution time
                    'item_definition' => 'SET @A=0;'
                ),
                'CREATE EVENT `s o m e e v e n t\` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DO SET @A=0;',
                2
            ),
            array(
                array(
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '', // no interval value
                    'item_interval_field' => 'DAY',
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1
            ),
            array(
                array( // simple recurring event
                    'item_name'           => 'rec_``evn',
                    'item_type'           => 'RECURRING',
                    'item_interval_value' => '365',
                    'item_interval_field' => 'CENTURIES', // invalid interval field
                    'item_status'         => 'DISABLED',
                    'item_definition'     => 'SET @A=0;'
                ),
                'CREATE EVENT `rec_````evn` ON SCHEDULE ON COMPLETION NOT ' .
                'PRESERVE DISABLE DO SET @A=0;',
                1
            ),
        );
    }
}
