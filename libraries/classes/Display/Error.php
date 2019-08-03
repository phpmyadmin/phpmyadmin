<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays Error
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Displays Error
 *
 * @package PhpMyAdmin
 */
class Error
{
    /**
     * @param Template $template     Template object used to render the error
     * @param string   $lang         Lang of the HTML page
     * @param string   $dir          Direction of text of the HTML page
     * @param string   $errorHeader  Error header
     * @param string   $errorMessage Error message
     *
     * @return string
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public static function display(
        Template $template,
        string $lang,
        string $dir,
        string $errorHeader,
        string $errorMessage
    ): string {
        return $template->render(
            'error/generic',
            [
                'lang' => $lang,
                'dir' => $dir,
                'error_header' => $errorHeader,
                'error_message' => Sanitize::sanitizeMessage($errorMessage),
            ]
        );
    }
}
