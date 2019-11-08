<?php
/**
 * Simple script to set correct charset for the license
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

/**
 * Simple script to set correct charset for the license
 * @package PhpMyAdmin\Controllers
 */
class LicenseController extends AbstractController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $this->response->disable();
        $this->response->header('Content-type: text/plain; charset=utf-8');

        $filename = LICENSE_FILE;

        // Check if the file is available, some distributions remove these.
        if (@is_readable($filename)) {
            readfile($filename);
        } else {
            printf(
                __(
                    'The %s file is not available on this system, please visit ' .
                    '%s for more information.'
                ),
                $filename,
                'https://www.phpmyadmin.net/'
            );
        }
    }
}
