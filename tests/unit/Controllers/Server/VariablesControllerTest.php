<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Server\VariableFormatter;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

use function __;
use function htmlspecialchars;
use function str_replace;

#[CoversClass(VariablesController::class)]
#[AllowMockObjectsWithoutExpectations]
class VariablesControllerTest extends AbstractTestCase
{
    private DatabaseInterface&MockObject $mockedDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $this->mockedDbi = $this->createMock(DatabaseInterface::class);

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $serverSessionVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '13',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
        ];

        $serverGlobalVariables = ['auto_increment_increment' => '0', 'auto_increment_offset' => '12'];

        $fetchResult = [
            ['SHOW SESSION VARIABLES;', 0, 1, ConnectionType::User, $serverSessionVariables],
            ['SHOW GLOBAL VARIABLES;', 0, 1, ConnectionType::User, $serverGlobalVariables],
        ];

        $this->mockedDbi->method('fetchResult')->willReturnMap($fetchResult);
    }

    public function testIndex(): void
    {
        $response = new ResponseStub();

        $resultStub = self::createStub(DummyResult::class);

        $this->mockedDbi->expects(self::once())
            ->method('tryQuery')
            ->with('SHOW SESSION VARIABLES;')
            ->willReturn($resultStub);

        $controller = new VariablesController(
            $response,
            new VariableFormatter(new Template(new Config())),
            $this->mockedDbi,
        );

        $controller(self::createStub(ServerRequest::class));
        $html = $response->getHTMLResult();

        self::assertStringContainsString(
            Generator::getIcon('b_save', __('Save')),
            $html,
        );
        self::assertStringContainsString(
            Generator::getIcon('b_close', __('Cancel')),
            $html,
        );
        self::assertStringContainsString('<div class="card-header">' . __('Filters') . '</div>', $html);
        self::assertStringContainsString(
            __('Containing the word:'),
            $html,
        );
        self::assertStringContainsString(
            __('Variable'),
            $html,
        );
        self::assertStringContainsString(
            __('Value'),
            $html,
        );

        $name = 'auto_increment_increment';
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        self::assertStringContainsString($value, $html);
        $name = 'auto_increment_offset';
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        self::assertStringContainsString($value, $html);
    }
}
