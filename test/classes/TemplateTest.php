<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\Template class
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/PMATestCase.php';

/**
 * Test for PMA\libraries\Template class
 *
 * @package PhpMyAdmin-test
 */
class TemplateTest extends PMATestCase
{
    /**
     * Test for set function
     *
     * @return void
     */
    public function testSet()
    {
        $template = PMA\libraries\Template::get('test/add_data');
        $template->set('variable1', 'value1');
        $template->set(
            array(
                'variable2' => 'value2'
            )
        );
        $result = $template->render();
        $this->assertContains('value1', $result);
        $this->assertContains('value2', $result);
    }

    /**
     * Test for setHelper
     *
     * @return void
     */
    public function testSetHelper()
    {
        $template = PMA\libraries\Template::get('test/set_helper');
        $template->setHelper('hello', function ($string) {
            return 'hello ' . $string;
        });
        $template->set(
            array(
                'variable' => 'world'
            )
        );
        $this->assertEquals('hello world', $template->render());
    }

    /**
     * Test for render
     *
     * @return void
     */
    public function testStaticRender()
    {
        $this->assertEquals(
            'static content',
            PMA\libraries\Template::get('test/static')->render()
        );
    }

    /**
     * Test for render
     *
     * @return void
     */
    public function testDynamicRender()
    {
        $this->assertEquals(
            'value',
            PMA\libraries\Template::get('test/echo')->render(
                array(
                    'variable' => 'value'
                )
            )
        );
    }
}
