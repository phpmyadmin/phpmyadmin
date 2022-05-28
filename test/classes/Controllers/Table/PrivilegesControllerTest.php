<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\PrivilegesController;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;

/**
 * @covers \PhpMyAdmin\Controllers\Table\PrivilegesController
 */
class PrivilegesControllerTest extends AbstractTestCase
{
    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setTheme();
    }

    public function testIndex(): void
    {
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $response = new ResponseRenderer();
        (new PrivilegesController($response, new Template(), $serverPrivileges, $GLOBALS['dbi']))();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString($GLOBALS['db'] . '.' . $GLOBALS['table'], $actual);

        //validate 2: Url::getCommon
        $item = Url::getCommon([
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
        ], '');
        $this->assertStringContainsString($item, $actual);

        //validate 3: items
        $this->assertStringContainsString(
            __('User'),
            $actual
        );
        $this->assertStringContainsString(
            __('Host'),
            $actual
        );
        $this->assertStringContainsString(
            __('Type'),
            $actual
        );
        $this->assertStringContainsString(
            __('Privileges'),
            $actual
        );
        $this->assertStringContainsString(
            __('Grant'),
            $actual
        );
        $this->assertStringContainsString(
            __('Action'),
            $actual
        );
        $this->assertStringContainsString(
            __('No user found'),
            $actual
        );

        //_pgettext('Create new user', 'New')
        $this->assertStringContainsString(
            _pgettext('Create new user', 'New'),
            $actual
        );
    }
}
