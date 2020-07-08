<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ThemeManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use function preg_replace;

/**
 * Displays list of themes.
 */
class ThemesController extends AbstractController
{
    public function index(Request $request, Response $response): Response
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

        return $response;
    }
}
