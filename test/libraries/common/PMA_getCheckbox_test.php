<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::getCheckbox from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

class PMA_GetCheckboxTest extends PHPUnit_Framework_TestCase
{
    function testGetCheckbox()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA_Util::getCheckbox($name, $label, false, false),
            '<input type="checkbox" name="' . $name . '" id="' . $name . '" /><label for="' . $name . '">' . $label . '</label>'
        );
    }

    function testGetCheckboxChecked()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA_Util::getCheckbox($name, $label, true, false),
            '<input type="checkbox" name="' . $name . '" id="' . $name . '" checked="checked" /><label for="' . $name . '">' . $label . '</label>'
        );
    }

    function testGetCheckboxOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA_Util::getCheckbox($name, $label, false, true),
            '<input type="checkbox" name="' . $name . '" id="' . $name . '" class="autosubmit" /><label for="' . $name . '">' . $label . '</label>'
        );
    }

    function testGetCheckboxCheckedOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA_Util::getCheckbox($name, $label, true, true),
            '<input type="checkbox" name="' . $name . '" id="' . $name . '" checked="checked" class="autosubmit" /><label for="' . $name . '">' . $label . '</label>'
        );
    }
}
