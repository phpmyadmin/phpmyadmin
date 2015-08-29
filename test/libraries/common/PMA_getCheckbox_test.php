<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA\libraries\Util::getCheckbox from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 ** Test for PMA\libraries\Util::getCheckbox from Util.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GetCheckboxTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test for getCheckbox
     *
     * @return void
     */
    function testGetCheckbox()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA\libraries\Util::getCheckbox($name, $label, false, false, $name),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" /><label for="' . $name . '">' . $label
            . '</label>'
        );
    }

    /**
     * Test for getCheckbox
     *
     * @return void
     */
    function testGetCheckboxChecked()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA\libraries\Util::getCheckbox($name, $label, true, false, $name),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked" /><label for="' . $name . '">' . $label
            . '</label>'
        );
    }

    /**
     * Test for getCheckbox
     *
     * @return void
     */
    function testGetCheckboxOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA\libraries\Util::getCheckbox($name, $label, false, true, $name),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" class="autosubmit" /><label for="' . $name . '">' . $label
            . '</label>'
        );
    }

    /**
     * Test for getCheckbox
     *
     * @return void
     */
    function testGetCheckboxCheckedOnclick()
    {
        $name = "test_display_html_checkbox";
        $label = "text_label_for_checkbox";

        $this->assertEquals(
            PMA\libraries\Util::getCheckbox($name, $label, true, true, $name),
            '<input type="checkbox" name="' . $name . '" id="' . $name
            . '" checked="checked" class="autosubmit" /><label for="' . $name
            . '">' . $label . '</label>'
        );
    }
}
