<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ThemeManager;
use function preg_replace;

/**
 * Displays list of themes.
 */
class ThemesController extends AbstractController
{
    public function index(): void
    {
        $this->response->getFooter()->setMinimal();
        $header = $this->response->getHeader();
        $header->setBodyId('bodythemes');
        $header->setTitle('phpMyAdmin - ' . __('Theme'));
        $header->disableMenuAndConsole();

        $this->render('themes', [
            'version' => preg_replace(
                '/([0-9]*)\.([0-9]*)\..*/',
                '\1_\2',
                PMA_VERSION
            ),
            'previews' => ThemeManager::getInstance()->getPrintPreviews(),
        ]);
    }
}
