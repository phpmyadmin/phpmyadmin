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
     * @dataProvider providerTestSet
     *
     * @return void
     */
    public function testSet($data)
    {
        $template = PMA\libraries\Template::get($data);
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
     * Data provider for testSet
     *
     * @return array
     */
    public function providerTestSet()
    {
        return [
            ['test/add_data'],
            ['test/add_data_twig'],
        ];
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
        $this->assertEquals(
            'static content',
            PMA\libraries\Template::get('test/static_twig')->render()
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
        $this->assertEquals(
            'value',
            PMA\libraries\Template::get('test/echo_twig')->render(
                array(
                    'variable' => 'value'
                )
            )
        );
    }

    /**
     * Test for render
     *
     * @return void
     */
    public function testRenderGettext()
    {
        $this->assertEquals(
            'Text',
            PMA\libraries\Template::get('test/gettext')->render()
        );
        $this->assertEquals(
            'Text',
            PMA\libraries\Template::get('test/gettext_twig')->render()
        );
    }
}
