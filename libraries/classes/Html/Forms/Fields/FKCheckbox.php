<?php
/**
 * HTML Generator for checkbox for foreign keys
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\Forms\Fields;

use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * HTML Generator for checkbox for foreign keys
 *
 * @package PhpMyAdmin
 */
class FKCheckbox implements FieldGenerator
{
    /**
     * Get HTML for Foreign key check checkbox
     *
     * @return string HTML for checkbox
     *
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function generate(): string
    {
        $template = new Template();
        return $template->render(
            'fk_checkbox',
            [
                'checked' => Util::isForeignKeyCheck(),
            ]
        );
    }
}
