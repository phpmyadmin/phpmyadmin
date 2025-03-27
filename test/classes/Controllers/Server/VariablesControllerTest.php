<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\VariablesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Providers\ServerVariables\ServerVariablesProvider;
use PhpMyAdmin\Providers\ServerVariables\VoidProvider as ServerVariablesVoidProvider;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

use function __;
use function htmlspecialchars;
use function str_replace;

/**
 * @covers \PhpMyAdmin\Controllers\Server\VariablesController
 */
class VariablesControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalConfig();
        parent::setLanguage();
        parent::setTheme();

        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        //this data is needed when PhpMyAdmin\Server\Status\Data constructs
        $serverSessionVariables = [
            'auto_increment_increment' => '1',
            'auto_increment_offset' => '13',
            'automatic_sp_privileges' => 'ON',
            'back_log' => '50',
            'big_tables' => 'OFF',
        ];

        $serverGlobalVariables = [
            'auto_increment_increment' => '0',
            'auto_increment_offset' => '12',
        ];

        $fetchResult = [
            [
                'SHOW SESSION VARIABLES;',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                $serverSessionVariables,
            ],
            [
                'SHOW GLOBAL VARIABLES;',
                0,
                1,
                DatabaseInterface::CONNECT_USER,
                $serverGlobalVariables,
            ],
        ];

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $GLOBALS['dbi'] = $dbi;
    }

    public function testIndex(): void
    {
        $response = new ResponseStub();

        $resultStub = $this->createMock(DummyResult::class);

        /** @var MockObject&DatabaseInterface $dbi */
        $dbi = $GLOBALS['dbi'];
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SHOW SESSION VARIABLES;')
            ->willReturn($resultStub);

        $controller = new VariablesController($response, new Template(), $dbi);

        $controller();
        $html = $response->getHTMLResult();

        self::assertStringContainsString(Generator::getIcon('b_save', __('Save')), $html);
        self::assertStringContainsString(Generator::getIcon('b_close', __('Cancel')), $html);
        self::assertStringContainsString('<div class="card-header">' . __('Filters') . '</div>', $html);
        self::assertStringContainsString(__('Containing the word:'), $html);
        self::assertStringContainsString(__('Variable'), $html);
        self::assertStringContainsString(__('Value'), $html);

        $name = 'auto_increment_increment';
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        self::assertStringContainsString($value, $html);
        $name = 'auto_increment_offset';
        $value = htmlspecialchars(str_replace('_', ' ', $name));
        self::assertStringContainsString($value, $html);
    }

    /**
     * Test for formatVariable()
     */
    public function testFormatVariable(): void
    {
        $controller = new VariablesController(ResponseRenderer::getInstance(), new Template(), $GLOBALS['dbi']);

        $nameForValueByte = 'byte_variable';
        $nameForValueNotByte = 'not_a_byte_variable';

        //name is_numeric and the value type is byte
        $args = [
            $nameForValueByte,
            '3',
        ];
        $voidProviderMock = $this->getMockBuilder(ServerVariablesVoidProvider::class)->getMock();

        $voidProviderMock
            ->expects($this->exactly(2))
            ->method('getVariableType')
            ->willReturnOnConsecutiveCalls('byte', 'string');

        $response = new ReflectionProperty(ServerVariablesProvider::class, 'instance');
        $response->setAccessible(true);
        $response->setValue(null, $voidProviderMock);

        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );

        self::assertEquals('<abbr title="3">3 B</abbr>', $formattedValue);
        self::assertTrue($isHtmlFormatted);

        //name is_numeric and the value type is not byte
        $args = [
            $nameForValueNotByte,
            '3',
        ];
        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );
        self::assertEquals('3', $formattedValue);
        self::assertFalse($isHtmlFormatted);

        //value is not a number
        $args = [
            $nameForValueNotByte,
            'value',
        ];
        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );
        self::assertEquals('value', $formattedValue);
        self::assertFalse($isHtmlFormatted);
    }

    /**
     * Test for formatVariable()
     */
    public function testFormatVariableMariaDbMySqlKbs(): void
    {
        if (! ServerVariablesProvider::mariaDbMySqlKbsExists()) {
            $this->markTestSkipped('MariaDbMySqlKbs is missing');
        }

        $response = new ReflectionProperty(ServerVariablesProvider::class, 'instance');
        $response->setAccessible(true);
        $response->setValue(null, null);

        $controller = new VariablesController(ResponseRenderer::getInstance(), new Template(), $GLOBALS['dbi']);

        $nameForValueByte = 'wsrep_replicated_bytes';
        $nameForValueNotByte = 'wsrep_thread_count';

        //name is_numeric and the value type is byte
        $args = [
            $nameForValueByte,
            '3',
        ];

        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );

        self::assertEquals('<abbr title="3">3 B</abbr>', $formattedValue);
        self::assertTrue($isHtmlFormatted);

        //name is_numeric and the value type is not byte
        $args = [
            $nameForValueNotByte,
            '3',
        ];
        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );
        self::assertEquals('3', $formattedValue);
        self::assertFalse($isHtmlFormatted);

        //value is not a number
        $args = [
            $nameForValueNotByte,
            'value',
        ];
        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );
        self::assertEquals('value', $formattedValue);
        self::assertFalse($isHtmlFormatted);
    }

    /**
     * Test for formatVariable() using VoidProvider
     */
    public function testFormatVariableVoidProvider(): void
    {
        $response = new ReflectionProperty(ServerVariablesProvider::class, 'instance');
        $response->setAccessible(true);
        $response->setValue(null, new ServerVariablesVoidProvider());

        $controller = new VariablesController(ResponseRenderer::getInstance(), new Template(), $GLOBALS['dbi']);

        $nameForValueByte = 'wsrep_replicated_bytes';

        //name is_numeric and the value type is byte
        $args = [
            $nameForValueByte,
            '3',
        ];

        [$formattedValue, $isHtmlFormatted] = $this->callFunction(
            $controller,
            VariablesController::class,
            'formatVariable',
            $args
        );

        self::assertEquals('3', $formattedValue);
        self::assertFalse($isHtmlFormatted);
    }
}
