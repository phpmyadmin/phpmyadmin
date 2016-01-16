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
     * Test for setData
     *
     * @return void
     */
    public function testSetData()
    {
        $template = PMA\libraries\Template::get('test/echo');
        $template->setData(
            array(
                'variable' => 'value'
            )
        );
        $this->assertEquals('value', $template->render());
    }

    /**
     * Test for addData
     *
     * @return void
     */
    public function testAddData()
    {
        $template = PMA\libraries\Template::get('test/add_data');
        $template->addData(
            array(
                'variable1' => 'value1'
            )
        );
        $template->addData(
            array(
                'variable2' => 'value2'
            )
        );
        $result = $template->render();
        $this->assertContains('value1', $result);
        $this->assertContains('value2', $result);
    }

    /**
     * Test for addFunction
     *
     * @return void
     */
    public function testAddFunction()
    {
        $template = PMA\libraries\Template::get('test/add_function');
        $template->addFunction('hello', function ($string) {
            return 'hello ' . $string;
        });
        $template->addData(
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

    /**
     * Test for trim
     *
     * @return void
     */
    public function testTrim()
    {
        $html = file_get_contents(PMA\libraries\Template::BASE_PATH . 'test/trim.phtml');

        $this->assertEquals(
            'outer <element>value</element> value',
            PMA\libraries\Template::trim($html)
        );
    }
}
