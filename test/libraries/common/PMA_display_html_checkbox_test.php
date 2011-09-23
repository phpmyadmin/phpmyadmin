<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_display_html_checkbox from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_display_html_checkbox_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_display_html_checkbox_test extends PHPUnit_Extensions_OutputTestCase
{
    function testDisplayHtmlCheckbox()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->expectOutputString('<input type="checkbox" name="' . $name . '" id="' . $name . '" /><label for="' . $name . '">' . $label . '</label>');
        PMA_display_html_checkbox($name, $label, false, false);
    }

    function testDisplayHtmlCheckboxChecked()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->expectOutputString('<input type="checkbox" name="' . $name . '" id="' . $name . '" checked="checked" /><label for="' . $name . '">' . $label . '</label>');
        PMA_display_html_checkbox($name, $label, true, false);
    }

    function testDisplayHtmlCheckboxOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->expectOutputString('<input type="checkbox" name="' . $name . '" id="' . $name . '" class="autosubmit" /><label for="' . $name . '">' . $label . '</label>');
        PMA_display_html_checkbox($name, $label, false, true);
    }

    function testDisplayHtmlCheckboxCheckedOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->expectOutputString('<input type="checkbox" name="' . $name . '" id="' . $name . '" checked="checked" class="autosubmit" /><label for="' . $name . '">' . $label . '</label>');
        PMA_display_html_checkbox($name, $label, true, true);
    }
}

//PMA_display_html_checkbox
