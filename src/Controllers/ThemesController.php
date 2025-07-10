<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;

#[Route('/themes', ['GET'])]
final class ThemesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly ThemeManager $themeManager,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $themes = $this->themeManager->getThemesArray();
        $themesList = $this->template->render('home/themes', ['themes' => $themes]);
        if ($request->isAjax()) {
            $this->response->addJSON('themes', $themesList);

            return $this->response->response();
        }

        $this->response->addHTML($themesList);

        return $this->response->response();
    }
}
