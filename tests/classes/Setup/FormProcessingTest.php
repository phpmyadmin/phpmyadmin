<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseRendererStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionProperty;
use Throwable;

use function ob_get_clean;
use function ob_start;

#[CoversClass(FormProcessing::class)]
class FormProcessingTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->settings['ServerDefault'] = 1;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testProcessFormSet(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();

        $responseStub = new ResponseRendererStub();
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, $responseStub);

        // case 1
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'getDisplay'])
            ->getMock();

        $formDisplay->expects(self::once())
            ->method('process')
            ->with(false)
            ->willReturn(false);

        $formDisplay->expects(self::once())
            ->method('getDisplay');

        FormProcessing::process($formDisplay);

        // case 2
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'hasErrors', 'displayErrors'])
            ->getMock();

        $formDisplay->expects(self::once())
            ->method('process')
            ->with(false)
            ->willReturn(true);

        $formDisplay->expects(self::once())
            ->method('hasErrors')
            ->with()
            ->willReturn(true);

        ob_start();
        FormProcessing::process($formDisplay);
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('<div class="error">', $result);

        self::assertStringContainsString('mode=revert', $result);

        self::assertStringContainsString('<a class="btn" href="../setup/index.php?route=/setup&', $result);

        self::assertStringContainsString('mode=edit', $result);

        // case 3
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'hasErrors'])
            ->getMock();

        $formDisplay->expects(self::once())
            ->method('process')
            ->with(false)
            ->willReturn(true);

        $formDisplay->expects(self::once())
            ->method('hasErrors')
            ->with()
            ->willReturn(false);

        try {
            FormProcessing::process($formDisplay);
        } catch (Throwable $throwable) {
        }

        self::assertInstanceOf(ExitException::class, $throwable ?? null);
        $response = $responseStub->getResponse();
        self::assertSame(['../setup/index.php?route=%2Fsetup&lang=en'], $response->getHeader('Location'));
        self::assertSame(303, $response->getStatusCode());
    }
}
