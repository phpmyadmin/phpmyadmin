<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Server\Select
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;

/**
 * PhpMyAdmin\Tests\Server\SelectTest class
 *
 * this class is for testing PhpMyAdmin\Server\Select methods
 *
 * @package PhpMyAdmin-test
 */
class SelectTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        //$_REQUEST
        $_REQUEST['log'] = "index1";
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = "server";
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = array();
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;

        $GLOBALS['table'] = "table";

        //$_SESSION
    }

    /**
     * Test for Select::render
     *
     * @return void
     */
    public function testRender()
    {
        $not_only_options = false;
        $omit_fieldset = false;

        $GLOBALS['cfg']['DefaultTabServer'] = "welcome";

        $GLOBALS['cfg']['Servers'] = array(
            '0' => array(
                'host'=>'host0',
                'port'=>'port0',
                'only_db'=>'only_db0',
                'user'=>'user0',
                'auth_type'=>'config',
            ),
            '1' => array(
                'host'=>'host1',
                'port'=>'port1',
                'only_db'=>'only_db1',
                'user'=>'user1',
                'auth_type'=>'config',
            ),
        );

        //$not_only_options=false & $omit_fieldset=false
        $html = Select::render($not_only_options, $omit_fieldset);
        $server = $GLOBALS['cfg']['Servers']['0'];

        //server items
        $this->assertContains(
            $server['host'],
            $html
        );
        $this->assertContains(
            $server['port'],
            $html
        );
        $this->assertContains(
            $server['only_db'],
            $html
        );
        $this->assertContains(
            $server['user'],
            $html
        );

        $not_only_options = true;
        $omit_fieldset = true;
        $GLOBALS['cfg']['DisplayServersList'] = null;

        //$not_only_options=true & $omit_fieldset=true
        $html = Select::render($not_only_options, $omit_fieldset);

        //$GLOBALS['cfg']['DefaultTabServer']
        $this->assertContains(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabServer'], 'server'
            ),
            $html
        );

        //labels
        $this->assertContains(
            __('Current server:'),
            $html
        );
        $this->assertContains(
            '(' . __('Servers') . ')',
            $html
        );

        //server items
        $server = $GLOBALS['cfg']['Servers']['0'];
        $this->assertContains(
            $server['host'],
            $html
        );
        $this->assertContains(
            $server['port'],
            $html
        );
        $this->assertContains(
            $server['only_db'],
            $html
        );
        $this->assertContains(
            $server['user'],
            $html
        );
    }
}
