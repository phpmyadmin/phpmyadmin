<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for PhpMyAdmin\Template class
 *
 * @package PhpMyAdmin-test
 */
class TemplateTest extends PmaTestCase
{
    /**
     * Test for set function
     *
     * @param string $data Template name
     *
     * @return void
     *
     * @dataProvider providerTestSet
     */
    public function testSet($data)
    {
        $template = Template::get($data);
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
        $template = Template::get('test/set_helper');
        $template->setHelper('hello', function ($string) {
            return 'hello ' . $string;
        });
        $template->set(['variable' => 'world']);
        $this->assertEquals('hello world', $template->render());

        $this->setExpectedException('LogicException');
        $template->setHelper('hello', 'again');
    }

    /**
     * Test for removeHelper
     *
     * @return void
     */
    public function testRemoveHelper()
    {
        $template = Template::get('test/set_helper');
        $template->setHelper('hello', function ($string) {
            return 'hello ' . $string;
        });
        $template->set(['variable' => 'world']);
        $template->removeHelper('hello');
        $this->setExpectedException('LogicException');
        $template->render();
    }

    /**
     * Test for removeHelper
     *
     * @return void
     */
    public function testRemoveHelperNotFound()
    {
        $template = Template::get('test/set_helper');
        $this->setExpectedException('LogicException');
        $template->removeHelper('not found');
    }

    /**
     * Test for render
     *
     * @param string $templateFile Template name
     * @param string $key          Template variable array key
     * @param string $value        Template variable array value
     *
     * @return void
     *
     * @dataProvider providerTestDynamicRender
     */
    public function testDynamicRender($templateFile, $key, $value)
    {
        $this->assertEquals(
            $value,
            Template::get($templateFile)->render([$key => $value])
        );
    }

    /**
     * Data provider for testDynamicRender
     *
     * @return array
     */
    public function providerTestDynamicRender()
    {
        return [
            ['test/echo', 'variable', 'value'],
            ['test/echo_twig', 'variable', 'value'],
        ];
    }

    /**
     * Test for render
     *
     * @return void
     */
    public function testRenderTemplateNotFound()
    {
        $this->setExpectedException('LogicException');
        Template::get('template not found')->render();
    }

    /**
     * Test for render
     *
     * @param string $templateFile   Template name
     * @param string $expectedResult Expected result
     *
     * @return void
     *
     * @dataProvider providerTestRender
     */
    public function testRender($templateFile, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            Template::get($templateFile)->render()
        );
    }

    /**
     * Data provider for testSet
     *
     * @return array
     */
    public function providerTestRender()
    {
        return [
            ['test/static', 'static content'],
            ['test/static_twig', 'static content'],
        ];
    }

    /**
     * Test for render
     *
     * @param string $templateFile   Template name
     * @param array  $renderParams   Render params
     * @param string $expectedResult Expected result
     *
     * @return void
     *
     * @dataProvider providerTestRenderGettext
     */
    public function testRenderGettext($templateFile, $renderParams, $expectedResult)
    {
        $this->assertEquals(
            $expectedResult,
            Template::get($templateFile)->render($renderParams)
        );
    }

    /**
     * Data provider for testRenderGettext
     *
     * @return array
     */
    public function providerTestRenderGettext()
    {
        return [
            ['test/gettext/gettext', [], 'Text'],
            ['test/gettext/gettext_twig', [], 'Text'],
            ['test/gettext/pgettext', [], 'Text'],
            ['test/gettext/pgettext_twig', [], 'Text'],
            ['test/gettext/notes_twig', [], 'Text'],
            ['test/gettext/plural_twig', ['table_count' => 1], 'One table'],
            ['test/gettext/plural_twig', ['table_count' => 2], '2 tables'],
            ['test/gettext/plural_notes_twig', ['table_count' => 1], 'One table'],
            ['test/gettext/plural_notes_twig', ['table_count' => 2], '2 tables'],
        ];
    }
}
