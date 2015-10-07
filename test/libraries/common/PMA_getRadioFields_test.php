<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::getRadioFields from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 ** Test for PMA\libraries\Util::getRadioFields from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GetRadioFieldsTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFieldsEmpty()
    {
        $name = "test_display_radio";
        $choices = array();

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields($name, $choices),
            ""
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFields()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields($name, $choices),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFieldsWithChecked()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_2'=>'choice_2');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
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
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= '<br />';
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice, true, false, $class
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFieldsWithoutBR()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value&_&lt;2&gt;'=>'choice_2');
        $checked_choice = "choice_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice, false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFieldsEscapeLabelEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice, true, true
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
    function testGetRadioFieldsEscapeLabelNotEscapeLabel()
    {
        $name = "test_display_radio";
        $choices = array('value_1'=>'choice_1', 'value_&2'=>'choice&_&lt;2&gt;');
        $checked_choice = "value_2";

        $out = "";
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= '<br />';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice, true, false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     */
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
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= ' />' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= '<br />';
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            PMA\libraries\Util::getRadioFields(
                $name, $choices, $checked_choice, true, true, $class
            ),
            $out
        );
    }
}