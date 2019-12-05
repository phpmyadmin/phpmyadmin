<?php
/**
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ThemeManager;

/**
 * Displays list of themes.
 * @package PhpMyAdmin\Controllers
 */
class ThemesController extends AbstractController
{
    /**
     * @return string
     */
    public function index(): string
    {
        $this->response->getFooter()->setMinimal();
        $header = $this->response->getHeader();
        $header->setBodyId('bodythemes');
        $header->setTitle('phpMyAdmin - ' . __('Theme'));
        $header->disableMenuAndConsole();

        return $this->template->render('themes', [
            'version' => preg_replace(
                '/([0-9]*)\.([0-9]*)\..*/',
                '\1_\2',
                PMA_VERSION
            ),
            'previews' => ThemeManager::getInstance()->getPrintPreviews(),
        ]);
    }
}
