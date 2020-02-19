<?php
/**
 * HTML Generator for radio buttons list
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

use PhpMyAdmin\Template;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * HTML Generator for radio buttons list
 *
 * @package PhpMyAdmin
 */
class RadioList implements FieldGenerator
{

    /**
     * Generates a set of radio HTML fields
     *
     * @param string  $html_field_name the radio HTML field
     * @param array   $choices         the choices values and labels
     * @param string  $checked_choice  the choice to check by default
     * @param boolean $line_break      whether to add HTML line break after a choice
     * @param boolean $escape_label    whether to use htmlspecialchars() on label
     * @param string  $class           enclose each choice with a div of this class
     * @param string  $id_prefix       prefix for the id attribute, name will be
     *                                 used if this is not supplied
     *
     * @return string                  set of html radio fields
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function generate(
        $html_field_name,
        array $choices,
        $checked_choice = '',
        $line_break = true,
        $escape_label = true,
        $class = '',
        $id_prefix = ''
    ): string {
        $template = new Template();
        $radio_html = '';

        foreach ($choices as $choice_value => $choice_label) {
            if (! $id_prefix) {
                $id_prefix = $html_field_name;
            }
            $html_field_id = $id_prefix . '_' . $choice_value;

            if ($choice_value == $checked_choice) {
                $checked = 1;
            } else {
                $checked = 0;
            }
            $radio_html .= $template->render(
                'radio_fields',
                [
                    'class' => $class,
                    'html_field_name' => $html_field_name,
                    'html_field_id' => $html_field_id,
                    'choice_value' => $choice_value,
                    'is_line_break' => $line_break,
                    'choice_label' => $choice_label,
                    'escape_label' => $escape_label,
                    'checked' => $checked,
                ]
            );
        }

        return $radio_html;
    }
}
