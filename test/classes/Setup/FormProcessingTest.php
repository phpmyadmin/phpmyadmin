<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under Formset processing library
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for methods under Formset processing library
 *
 * @package PhpMyAdmin-test
 */
class FormProcessingTest extends PmaTestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['ServerDefault'] = 1;
    }

    /**
     * Test for process_formset()
     *
     * @return void
     */
    public function testProcessFormSet()
    {
        $this->mockResponse(
            [
                ['status: 303 See Other'],
                ['Location: index.php?lang=en'],
                303,
            ]
        );

        // case 1
        $formDisplay = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(['process', 'getDisplay'])
            ->getMock();

        $formDisplay->expects($this->once())
            ->method('process')
            ->with(false)
            ->will($this->returnValue(false));

        $formDisplay->expects($this->once())
            ->method('getDisplay')
            ->with(true, true);

        FormProcessing::process($formDisplay);

        // case 2
        $formDisplay = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(['process', 'hasErrors', 'displayErrors'])
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

        $this->assertStringContainsString(
            '<div class="error">',
            $result
        );

        $this->assertStringContainsString(
            'mode=revert',
            $result
        );

        $this->assertStringContainsString(
            '<a class="btn" href="index.php?',
            $result
        );

        $this->assertStringContainsString(
            'mode=edit',
            $result
        );

        // case 3
        $formDisplay = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(['process', 'hasErrors'])
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
