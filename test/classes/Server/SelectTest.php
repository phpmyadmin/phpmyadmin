<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server;

use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Tests\Server\SelectTest class
 *
 * this class is for testing PhpMyAdmin\Server\Select methods
 */
class SelectTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        //$_REQUEST
        $_REQUEST['log'] = 'index1';
        $_REQUEST['pos'] = 3;

        //$GLOBALS
        $GLOBALS['cfg']['MaxRows'] = 10;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 'server';
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['cfg']['SQP'] = [];
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ShowSQL'] = true;
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $GLOBALS['cfg']['LimitChars'] = 100;

        $GLOBALS['table'] = 'table';

        //$_SESSION
    }

    /**
     * Test for Select::render
     */
    public function testRender(): void
    {
        $not_only_options = false;
        $omit_fieldset = false;

        $GLOBALS['cfg']['DefaultTabServer'] = 'welcome';

        $GLOBALS['cfg']['Servers'] = [
            '0' => [
                'host' => 'host0',
                'port' => 'port0',
                'only_db' => 'only_db0',
                'user' => 'user0',
                'auth_type' => 'config',
            ],
            '1' => [
                'host' => 'host1',
                'port' => 'port1',
                'only_db' => 'only_db1',
                'user' => 'user1',
                'auth_type' => 'config',
            ],
        ];

        //$not_only_options=false & $omit_fieldset=false
        $html = Select::render($not_only_options, $omit_fieldset);
        $server = $GLOBALS['cfg']['Servers']['0'];

        //server items
        $this->assertStringContainsString(
            $server['host'],
            $html
        );
        $this->assertStringContainsString(
            $server['port'],
            $html
        );
        $this->assertStringContainsString(
            $server['only_db'],
            $html
        );
        $this->assertStringContainsString(
            $server['user'],
            $html
        );

        $not_only_options = true;
        $omit_fieldset = true;
        $GLOBALS['cfg']['DisplayServersList'] = null;

        //$not_only_options=true & $omit_fieldset=true
        $html = Select::render($not_only_options, $omit_fieldset);

        //$GLOBALS['cfg']['DefaultTabServer']
        $this->assertStringContainsString(
            Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabServer'],
                'server'
            ),
            $html
        );

        //labels
        $this->assertStringContainsString(
            __('Current server:'),
            $html
        );
        $this->assertStringContainsString(
            '(' . __('Servers') . ')',
            $html
        );

        //server items
        $server = $GLOBALS['cfg']['Servers']['0'];
        $this->assertStringContainsString(
            $server['host'],
            $html
        );
        $this->assertStringContainsString(
            $server['port'],
            $html
        );
        $this->assertStringContainsString(
            $server['only_db'],
            $html
        );
        $this->assertStringContainsString(
            $server['user'],
            $html
        );
    }
}
