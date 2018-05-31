<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for checkbox.phtml
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 ** Test for checkbox.phtml
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class GetCheckboxTest extends TestCase
{
    /**
     * Test for checkbox.phtml
     *
     * @return void
     */
    public function testGetCheckbox()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        // assertXmlStringEqualsXmlString require both inputs to be a valid xml string
        // dummy <root> tag will make input a valid xml string
        $this->assertXmlStringEqualsXmlString(
            '<root> ' . Template::get('checkbox')
            ->render(
                [
                    'html_field_name'   => $name,
                    'label'             => $label,
                    'checked'           => false,
                    'onclick'           => false,
                    'html_field_id'     => $name,
                ]
            ) . ' </root>',
            '<root> <input type="checkbox" name="' . $name . '" id="' . $name
            . '" /><label for="' . $name . '">' . $label
            . '</label> </root>'
        );
    }

    /**
     * Test for checkbox.phtml
     *
     * @return void
     */
    public function testGetCheckboxChecked()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Template::get('checkbox')
            ->render(
                [
                    'html_field_name'   => $name,
                    'label'             => $label,
                    'checked'           => true,
                    'onclick'           => false,
                    'html_field_id'     => $name,
                ]
            ) . '</root>',
            '<root> <input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked" /><label for="' . $name . '">' . $label
            . '</label> </root>'
        );
    }

    /**
     * Test for checkbox.phtml
     *
     * @return void
     */
    public function testGetCheckboxOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Template::get('checkbox')
            ->render(
                [
                    'html_field_name'   => $name,
                    'label'             => $label,
                    'checked'           => false,
                    'onclick'           => true,
                    'html_field_id'     => $name,
                ]
            ) . '</root>',
            '<root> <input type="checkbox" name="' . $name . '" id="' . $name
            . '" class="autosubmit" /><label for="' . $name . '">' . $label
            . '</label> </root>'
        );
    }

    /**
     * Test for checkbox.phtml
     *
     * @return void
     */
    public function testGetCheckboxCheckedOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Template::get('checkbox')
            ->render(
                [
                    'html_field_name'   => $name,
                    'label'             => $label,
                    'checked'           => true,
                    'onclick'           => true,
                    'html_field_id'     => $name,
                ]
            ) . '</root>',
            '<root> <input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked" class="autosubmit" /><label for="' . $name
            . '">' . $label . '</label> </root>'
        );
    }
}
