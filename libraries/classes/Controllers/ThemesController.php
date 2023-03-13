<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;

class ThemesController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private ThemeManager $themeManager)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $themes = $this->themeManager->getThemesArray();
        $themesList = $this->template->render('home/themes', ['themes' => $themes]);
        $this->response->setAjax(true);
        $this->response->addJSON('themes', $themesList);
    }
}
