<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under Formset processing library
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */
require_once 'setup/lib/form_processing.lib.php';


/**
 * tests for methods under Formset processing library
 *
 * @package PhpMyAdmin-test
 */
class PMA_Form_Processing_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
    }

    /**
     * Test for process_formset()
     *
     * @return void
     */
    public function testProcessFormSet()
    {
        $restoreInstance = PMA\libraries\Response::getInstance();

        $mockResponse = $this->getMockBuilder('PMA\libraries\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('header', 'headersSent'))
            ->getMock();

        $mockResponse->expects($this->exactly(2))
            ->method('header')
            ->withConsecutive(
                ['HTTP/1.1 303 See Other'],
                ['Location: index.php?lang=en']
            );

        $mockResponse->expects($this->any())
            ->method('headersSent')
            ->with()
            ->will($this->returnValue(false));

        $attrInstance = new ReflectionProperty('PMA\libraries\Response', '_instance');
        $attrInstance->setAccessible(true);
        $attrInstance->setValue($mockResponse);

        // case 1
        $formDisplay = $this->getMockBuilder('PMA\libraries\config\FormDisplay')
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

        PMA_Process_formset($formDisplay);

        // case 2
        $formDisplay = $this->getMockBuilder('PMA\libraries\config\FormDisplay')
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
        PMA_Process_formset($formDisplay);
        $result = ob_get_clean();

        $this->assertContains(
            '<div class="error">',
            $result
        );

        $this->assertContains(
            '<a href="?lang=en&amp;page=&amp;mode=revert">',
            $result
        );

        $this->assertContains(
            '<a class="btn" href="index.php?lang=en">',
            $result
        );

        $this->assertContains(
            '<a class="btn" href="?lang=en&amp;page=&amp;mode=edit">',
            $result
        );

        // case 3
        $formDisplay = $this->getMockBuilder('PMA\libraries\config\FormDisplay')
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

        PMA_Process_formset($formDisplay);

    }

}