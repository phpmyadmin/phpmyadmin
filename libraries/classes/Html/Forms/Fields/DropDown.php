<?php
/**
 * HTML Generator for drop down
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

use PhpMyAdmin\Template;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * HTML Generator for drop down
 */
class DropDown implements FieldGenerator
{
    /**
     * Generates and returns an HTML dropdown
     *
     * @param string $select_name   name for the select element
     * @param array  $choices       choices values
     * @param string $active_choice the choice to select by default
     * @param string $id            id of the select element; can be different in
     *                              case the dropdown is present more than once
     *                              on the page
     * @param string $class         class for the select element
     * @param string $placeholder   Placeholder for dropdown if nothing else
     *                              is selected
     *
     * @return string               html content
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     *
     * @todo    support titles
     */
    public static function generate(
        $select_name,
        array $choices,
        $active_choice,
        $id,
        $class = '',
        $placeholder = null
    ): string {
        $template = new Template();
        $resultOptions = [];
        $selected = false;

        foreach ($choices as $one_choice_value => $one_choice_label) {
            $resultOptions[$one_choice_value]['value'] = $one_choice_value;
            $resultOptions[$one_choice_value]['selected'] = false;

            if ($one_choice_value == $active_choice) {
                $resultOptions[$one_choice_value]['selected'] = true;
                $selected = true;
            }
            $resultOptions[$one_choice_value]['label'] = $one_choice_label;
        }
        return $template->render(
            'dropdown',
            [
                'select_name' => $select_name,
                'id' => $id,
                'class' => $class,
                'placeholder' => $placeholder,
                'selected' => $selected,
                'result_options' => $resultOptions,
            ]
        );
    }
}
