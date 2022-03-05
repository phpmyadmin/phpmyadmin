<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database;

use PhpMyAdmin\Controllers\Database\PrivilegesController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

use function __;
use function _pgettext;

/**
 * @covers \PhpMyAdmin\Controllers\Database\PrivilegesController
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
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $actual = (new PrivilegesController(
            ResponseRenderer::getInstance(),
            new Template(),
            $serverPrivileges,
            $GLOBALS['dbi']
        ))(['checkprivsdb' => $GLOBALS['db']]);

        $this->assertStringContainsString(
            Url::getCommon(['db' => $GLOBALS['db']], ''),
            $actual
        );

        $this->assertStringContainsString($GLOBALS['db'], $actual);

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

        //_pgettext('Create new user', 'New')
        $this->assertStringContainsString(
            _pgettext('Create new user', 'New'),
            $actual
        );
        $this->assertStringContainsString(
            Url::getCommon(['checkprivsdb' => $GLOBALS['db']]),
            $actual
        );
    }
}
