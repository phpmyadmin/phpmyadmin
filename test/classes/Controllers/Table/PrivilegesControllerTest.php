<?php
/**
 * @package PhpMyAdmin\Tests\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\PrivilegesController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Url;

/**
 * @package PhpMyAdmin\Tests\Controllers\Table
 */
class PrivilegesControllerTest extends PmaTestCase
{
    public function testIndex(): void
    {
        global $dbi, $db, $table, $server, $cfg, $PMA_PHP_SELF, $is_grantuser, $is_createuser;

        $db = 'db';
        $table = 'table';
        $server = 0;
        $cfg['Server']['DisableIS'] = false;
        $PMA_PHP_SELF = 'index.php';
        $is_grantuser = true;
        $is_createuser = true;

        $privileges = [];

        $serverPrivileges = $this->createMock(Privileges::class);
        $serverPrivileges->method('getAllPrivileges')
            ->willReturn($privileges);

        $controller = new PrivilegesController(
            Response::getInstance(),
            $dbi,
            new Template(),
            $db,
            $table,
            $serverPrivileges
        );
        $actual = $controller->index([
            'checkprivsdb' => $db,
            'checkprivstable' => $table,
        ]);

        $this->assertStringContainsString(
            $db . '.' . $table,
            $actual
        );

        //validate 2: Url::getCommon
        $item = Url::getCommon([
            'db' => $db,
            'table' => $table,
        ], '');
        $this->assertStringContainsString(
            $item,
            $actual
        );

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
        $this->assertStringContainsString(
            Url::getCommon([
                'checkprivsdb' => $db,
                'checkprivstable' => $table,
            ]),
            $actual
        );
    }
}
