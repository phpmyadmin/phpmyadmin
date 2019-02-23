<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * * Test for checkbox.phtml
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 * * Test for checkbox.phtml
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class GetCheckboxTest extends TestCase
{
    /**
     * @var Template
     */
    public $template;

    /**
     * Sets up the fixture
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->template = new Template();
    }

    /**
     * Test for checkbox.phtml
     *
     * @return void
     */
    public function testGetCheckbox()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertSame(
            $this->template->render('checkbox', [
                'html_field_name' => $name,
                'label' => $label,
                'checked' => false,
                'onclick' => false,
                'html_field_id' => $name,
            ]),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '"><label for="' . $name . '">' . $label
            . '</label>' . "\n"
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

        $this->assertSame(
            $this->template->render('checkbox', [
                'html_field_name' => $name,
                'label' => $label,
                'checked' => true,
                'onclick' => false,
                'html_field_id' => $name,
            ]),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked"><label for="' . $name . '">' . $label
            . '</label>' . "\n"
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

        $this->assertSame(
            $this->template->render('checkbox', [
                'html_field_name' => $name,
                'label' => $label,
                'checked' => false,
                'onclick' => true,
                'html_field_id' => $name,
            ]),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" class="autosubmit"><label for="' . $name . '">' . $label
            . '</label>' . "\n"
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

        $this->assertSame(
            $this->template->render('checkbox', [
                'html_field_name' => $name,
                'label' => $label,
                'checked' => true,
                'onclick' => true,
                'html_field_id' => $name,
            ]),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked" class="autosubmit"><label for="' . $name
            . '">' . $label . '</label>' . "\n"
        );
    }
}
