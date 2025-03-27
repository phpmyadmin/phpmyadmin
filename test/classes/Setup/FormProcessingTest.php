<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Tests\AbstractNetworkTestCase;

use function ob_get_clean;
use function ob_start;

/**
 * @covers \PhpMyAdmin\Setup\FormProcessing
 */
class FormProcessingTest extends AbstractNetworkTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['ServerDefault'] = 1;
    }

    /**
     * Test for process_formset()
     *
     * @requires PHPUnit < 10
     */
    public function testProcessFormSet(): void
    {
        $this->mockResponse(
            [
                ['status: 303 See Other'],
                ['Location: index.php?lang=en'],
                303,
            ]
        );

        // case 1
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'getDisplay'])
            ->getMock();

        $formDisplay->expects($this->once())
            ->method('process')
            ->with(false)
            ->will($this->returnValue(false));

        $formDisplay->expects($this->once())
            ->method('getDisplay');

        FormProcessing::process($formDisplay);

        // case 2
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'hasErrors', 'displayErrors'])
            ->getMock();

        $formDisplay->expects($this->once())
            ->method('process')
            ->with(false)
            ->will($this->returnValue(true));

        $formDisplay->expects($this->once())
            ->method('hasErrors')
            ->with()
            ->will($this->returnValue(true));

        ob_start();
        FormProcessing::process($formDisplay);
        $result = ob_get_clean();

        self::assertIsString($result);

        self::assertStringContainsString('<div class="error">', $result);

        self::assertStringContainsString('mode=revert', $result);

        self::assertStringContainsString('<a class="btn" href="index.php?', $result);

        self::assertStringContainsString('mode=edit', $result);

        // case 3
        $formDisplay = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['process', 'hasErrors'])
            ->getMock();

        $formDisplay->expects($this->once())
            ->method('process')
            ->with(false)
            ->will($this->returnValue(true));

        $formDisplay->expects($this->once())
            ->method('hasErrors')
            ->with()
            ->will($this->returnValue(false));

        FormProcessing::process($formDisplay);
    }
}
