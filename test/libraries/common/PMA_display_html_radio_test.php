<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_display_html_radio from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_display_html_radio_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_display_html_radio_test extends PHPUnit_Extensions_OutputTestCase
{
    function testDisplayHtmlRadioEmpty()
    {
        $name = "test_display_radio";
        $choices = array();

        $this->expectOutputString("");
        PMA_display_html_radio($name,$choices);
    }

    function testDisplayHtmlRadio()
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
        PMA_display_html_radio($name,$choices);
    }

    function testDisplayHtmlRadioWithChecked()
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
        PMA_display_html_radio($name,$choices,$checked_choice);
    }

    function testDisplayHtmlRadioWithCheckedWithClass()
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
        PMA_display_html_radio($name,$choices,$checked_choice,true,false,$class);
    }

    function testDisplayHtmlRadioWithoutBR()
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
        PMA_display_html_radio($name,$choices,$checked_choice,false);
    }

    function testDisplayHtmlRadioEscapeLabelEscapeLabel()
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
        PMA_display_html_radio($name,$choices,$checked_choice,true,true);
    }

    function testDisplayHtmlRadioEscapeLabelNotEscapeLabel()
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
        PMA_display_html_radio($name,$choices,$checked_choice,true,false);
    }

    function testDisplayHtmlRadioEscapeLabelEscapeLabelWithClass()
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
        PMA_display_html_radio($name,$choices,$checked_choice,true,true,$class);
    }
}