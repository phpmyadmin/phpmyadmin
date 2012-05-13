<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_getRadioFields from common.lib.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_GetRadioFieldsTest extends PHPUnit_Framework_TestCase
{
    function testGetRadioFieldsEmpty()
    {
        $name = "test_display_radio";
        $choices = array();

        $this->expectOutputString("");
        PMA_displayHtmlRadio($name, $choices);
    }

    function testGetRadioFields()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices);
    }

    function testGetRadioFieldsWithChecked()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice);
    }

    function testGetRadioFieldsWithCheckedWithClass()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');
        $checked_choice = "value_2";
        $class = "test_class";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label . '</label>';
            $out .= '<br />';
            $out .= '</div>';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice, true, false, $class);
    }

    function testGetRadioFieldsWithoutBR()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value&_&lt;2&gt;'=>'choice_2');
        $checked_choice = "choice_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label . '</label>';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice, false);
    }

    function testGetRadioFieldsEscapeLabelEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . htmlspecialchars($choice_label) . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice, true, true);
    }

    function testGetRadioFieldsEscapeLabelNotEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice, true, false);
    }

    function testGetRadioFieldsEscapeLabelEscapeLabelWithClass()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";
        $class = "test_class";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . htmlspecialchars($choice_label) . '</label>';
            $out .= '<br />';
            $out .= '</div>';
            $out .= "\n";
        }

        $this->expectOutputString($out);
        PMA_displayHtmlRadio($name, $choices, $checked_choice, true, true, $class);
    }
}