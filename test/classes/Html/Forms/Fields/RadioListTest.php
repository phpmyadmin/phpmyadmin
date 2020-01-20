<?php
/**
 * Test for PhpMyAdmin\Html\Forms\Fields\MaxFileSize class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html\Forms\Fields;

use PhpMyAdmin\Html\Forms;
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
class RadioListTest extends PmaTestCase
{
    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsEmpty(): void
    {
        $name = 'test_display_radio';
        $choices = [];

        $this->assertEquals(
            Forms\Fields\RadioList::generate($name, $choices),
            ''
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFields(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_2' => 'choice_2',
        ];

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate($name, $choices),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsWithChecked(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_2' => 'choice_2',
        ];
        $checked_choice = 'value_2';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsWithCheckedWithClass(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_2' => 'choice_2',
        ];
        $checked_choice = 'value_2';
        $class = 'test_class';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= "\n";
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice,
                true,
                false,
                $class
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsWithoutBR(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value&_&lt;2&gt;' => 'choice_2',
        ];
        $checked_choice = 'choice_2';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice,
                false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsEscapeLabelEscapeLabel(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_&2' => 'choice&_&lt;2&gt;',
        ];
        $checked_choice = 'value_2';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice,
                true,
                true
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsEscapeLabelNotEscapeLabel(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_&2' => 'choice&_&lt;2&gt;',
        ];
        $checked_choice = 'value_2';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">' . $choice_label
                . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice,
                true,
                false
            ),
            $out
        );
    }

    /**
     * Test for getRadioFields
     *
     * @return void
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetRadioFieldsEscapeLabelEscapeLabelWithClass(): void
    {
        $name = 'test_display_radio';
        $choices = [
            'value_1' => 'choice_1',
            'value_&2' => 'choice&_&lt;2&gt;',
        ];
        $checked_choice = 'value_2';
        $class = 'test_class';

        $out = '';
        foreach ($choices as $choice_value => $choice_label) {
            $html_field_id = $name . '_' . $choice_value;
            $out .= '<div class="' . $class . '">';
            $out .= "\n";
            $out .= '<input type="radio" name="' . $name . '" id="' . $html_field_id
                . '" value="' . htmlspecialchars($choice_value) . '"';
            if ($choice_value == $checked_choice) {
                $out .= ' checked="checked"';
            }
            $out .= '>' . "\n";
            $out .= '<label for="' . $html_field_id . '">'
                . htmlspecialchars($choice_label) . '</label>';
            $out .= "\n";
            $out .= '<br>';
            $out .= "\n";
            $out .= '</div>';
            $out .= "\n";
        }

        $this->assertEquals(
            Forms\Fields\RadioList::generate(
                $name,
                $choices,
                $checked_choice,
                true,
                true,
                $class
            ),
            $out
        );
    }
}
