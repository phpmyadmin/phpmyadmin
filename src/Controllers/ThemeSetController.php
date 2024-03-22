<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function is_string;

final class ThemeSetController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private ThemeManager $themeManager,
        private UserPreferences $userPreferences,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $theme = $request->getParsedBodyParam('set_theme');
        if (! Config::getInstance()->settings['ThemeManager'] || ! is_string($theme) || $theme === '') {
            if ($request->isAjax()) {
                $this->response->addJSON('themeColorMode', '');

                return;
            }

            $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

            return;
        }

        $this->themeManager->setActiveTheme($theme);

        /** @var mixed $themeColorMode */
        $themeColorMode = $request->getParsedBodyParam('themeColorMode');
        if (is_string($themeColorMode) && $themeColorMode !== '') {
            $this->themeManager->theme->setColorMode($themeColorMode);
        }

        $this->themeManager->setThemeCookie();

        $preferences = $this->userPreferences->load();
        $preferences['config_data']['ThemeDefault'] = $theme;
        $this->userPreferences->save($preferences['config_data']);

        if ($request->isAjax()) {
            $this->response->addJSON('themeColorMode', $this->themeManager->theme->getColorMode());

            return;
        }

        $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));
    }
}
