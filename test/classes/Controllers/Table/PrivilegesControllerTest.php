<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\PrivilegesController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
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
        global $dbi, $db, $table, $server, $cfg, $PMA_PHP_SELF;

        $db = 'db';
        $table = 'table';
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
            $table,
            $serverPrivileges,
            $dbi
        ))(['checkprivsdb' => $db, 'checkprivstable' => $table]);

        self::assertStringContainsString($db . '.' . $table, $actual);

        //validate 2: Url::getCommon
        $item = Url::getCommon([
            'db' => $db,
            'table' => $table,
        ], '');
        self::assertStringContainsString($item, $actual);

        //validate 3: items
        self::assertStringContainsString(__('User'), $actual);
        self::assertStringContainsString(__('Host'), $actual);
        self::assertStringContainsString(__('Type'), $actual);
        self::assertStringContainsString(__('Privileges'), $actual);
        self::assertStringContainsString(__('Grant'), $actual);
        self::assertStringContainsString(__('Action'), $actual);
        self::assertStringContainsString(__('No user found'), $actual);

        //_pgettext('Create new user', 'New')
        self::assertStringContainsString(_pgettext('Create new user', 'New'), $actual);
        self::assertStringContainsString(Url::getCommon([
            'checkprivsdb' => $db,
            'checkprivstable' => $table,
        ]), $actual);
    }
}
