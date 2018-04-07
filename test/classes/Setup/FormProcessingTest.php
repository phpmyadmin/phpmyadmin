<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under Formset processing library
 *
 * @package PhpMyAdmin-test
 */
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
    public function setUp()
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
            array(
                array('status: 303 See Other'),
                array('Location: index.php?lang=en'),
                303
                )
            );

        // case 1
        $formDisplay = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(array('process', 'getDisplay'))
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
            ->setMethods(array('process', 'hasErrors', 'displayErrors'))
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

        $this->assertContains(
            '<div class="error">',
            $result
        );

        $this->assertContains(
            'mode=revert',
            $result
        );

        $this->assertContains(
            '<a class="btn" href="index.php?',
            $result
        );

        $this->assertContains(
            'mode=edit',
            $result
        );

        // case 3
        $formDisplay = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(array('process', 'hasErrors'))
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
