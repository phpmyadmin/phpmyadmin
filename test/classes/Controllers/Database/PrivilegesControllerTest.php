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
        global $dbi, $db, $server, $cfg, $PMA_PHP_SELF;

        $db = 'db';
        $server = 0;
        $cfg['Server']['DisableIS'] = false;
        $PMA_PHP_SELF = 'index.php';

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $actual = (new PrivilegesController(
            ResponseRenderer::getInstance(),
            new Template(),
            $db,
            $serverPrivileges,
            $dbi
        ))(['checkprivsdb' => $db]);

        self::assertStringContainsString(Url::getCommon(['db' => $db], ''), $actual);

        self::assertStringContainsString($db, $actual);

        self::assertStringContainsString(__('User'), $actual);
        self::assertStringContainsString(__('Host'), $actual);
        self::assertStringContainsString(__('Type'), $actual);
        self::assertStringContainsString(__('Privileges'), $actual);
        self::assertStringContainsString(__('Grant'), $actual);
        self::assertStringContainsString(__('Action'), $actual);

        //_pgettext('Create new user', 'New')
        self::assertStringContainsString(_pgettext('Create new user', 'New'), $actual);
        self::assertStringContainsString(Url::getCommon(['checkprivsdb' => $db]), $actual);
    }
}
