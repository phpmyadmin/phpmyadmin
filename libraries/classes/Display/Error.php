<?php
/**
 * Displays Error
 */

declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Displays Error
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
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
