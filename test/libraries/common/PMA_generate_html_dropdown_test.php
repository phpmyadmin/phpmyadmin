<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_generate_html_dropdown_test from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_display_html_checkbox_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_generate_html_dropdown_test extends PHPUnit_Framework_TestCase
{
    function testGenerateHtmlDropdownEmpty()
    {
        $name = "test_dropdown_name";
        $choices = array();
        $active_choice = null;
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '"></select>';

        $this->assertEquals($result, PMA_generate_html_dropdown($name,$choices,$active_choice,$id));
    }

    function testGenerateHtmlDropdown()
    {
        $name = "&test_dropdown_name";
        $choices = array("value_1" => "label_1", "value&_2\"" => "label_2");
        $active_choice = null;
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= '</select>';

        $this->assertEquals($result, PMA_generate_html_dropdown($name,$choices,$active_choice,$id));
    }

    function testGenerateHtmlDropdownWithActive()
    {
        $name = "&test_dropdown_name";
        $choices = array("value_1" => "label_1", "value&_2\"" => "label_2");
        $active_choice = "value&_2\"";
        $id = "test_&lt;dropdown&gt;_name";

        $result = '<select name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= '</select>';

        $this->assertEquals($result, PMA_generate_html_dropdown($name,$choices,$active_choice,$id));
    }
}