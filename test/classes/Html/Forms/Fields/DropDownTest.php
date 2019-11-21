<?php
/**
 * Test for PhpMyAdmin\Html\Forms\Fields\MaxFileSize class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html\Forms\Fields;

use PhpMyAdmin\Html\Forms;
use PhpMyAdmin\Html\Forms\Fields\DropDown;
use PhpMyAdmin\Tests\PmaTestCase;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Test for PhpMyAdmin\Html\Forms\Fields\MaxFileSize class
 *
 * @package PhpMyAdmin-test
 */
class DropDownTest extends PmaTestCase
{

    /**
     * Test for getDropdown
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDropdownEmpty(): void
    {
        $name = 'test_dropdown_name';
        $choices = [];
        $active_choice = null;
        $id = 'test_&lt;dropdown&gt;_name';

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">' . "\n" . '</select>' . "\n";

        $this->assertEquals(
            $result,
            Forms\Fields\DropDown::generate(
                $name,
                $choices,
                $active_choice,
                $id
            )
        );
    }

    /**
     * Test for getDropdown
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDropdown(): void
    {
        $name = '&test_dropdown_name';
        $choices = [
            'value_1' => 'label_1',
            'value&_2"' => 'label_2',
        ];
        $active_choice = null;
        $id = 'test_&lt;dropdown&gt;_name';

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= "\n" . '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= "\n" . '</select>' . "\n";

        $this->assertEquals(
            $result,
            DropDown::generate(
                $name,
                $choices,
                $active_choice,
                $id
            )
        );
    }

    /**
     * Test for getDropdown
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDropdownWithActive(): void
    {
        $name = '&test_dropdown_name';
        $choices = [
            'value_1' => 'label_1',
            'value&_2"' => 'label_2',
        ];
        $active_choice = 'value&_2"';
        $id = 'test_&lt;dropdown&gt;_name';

        $result = '<select name="' . htmlspecialchars($name) . '" id="'
            . htmlspecialchars($id) . '">';
        foreach ($choices as $one_choice_value => $one_choice_label) {
            $result .= "\n";
            $result .= '<option value="' . htmlspecialchars($one_choice_value) . '"';
            if ($one_choice_value == $active_choice) {
                $result .= ' selected="selected"';
            }
            $result .= '>' . htmlspecialchars($one_choice_label) . '</option>';
        }
        $result .= "\n";
        $result .= '</select>' . "\n";

        $this->assertEquals(
            $result,
            DropDown::generate(
                $name,
                $choices,
                $active_choice,
                $id
            )
        );
    }
}
