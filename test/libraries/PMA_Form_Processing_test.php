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
        if (!defined('PMA_TEST_HEADERS')) {
            $this->markTestSkipped(
                'Cannot redefine constant/function - missing runkit extension'
            );
        }

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
            '<a href="?lang=en&amp;token=token&amp;page=&amp;mode=revert">',
            $result
        );

        $this->assertContains(
            '<a class="btn" href="index.php?lang=en&amp;token=token">',
            $result
        );

        $this->assertContains(
            '<a class="btn" href="?lang=en&amp;token=token&amp;page=&amp;mode=edit">',
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

        $this->assertEquals(
            array('HTTP/1.1 303 See Other', 'Location: index.php?lang=en&amp;token=token'),
            $GLOBALS['header']
        );

    }


}
