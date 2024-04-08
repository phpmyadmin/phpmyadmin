<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function is_string;

final class ThemeSetController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly ThemeManager $themeManager,
        private readonly UserPreferences $userPreferences,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $theme = $request->getParsedBodyParam('set_theme');
        if (! Config::getInstance()->settings['ThemeManager'] || ! is_string($theme) || $theme === '') {
            if ($request->isAjax()) {
                $this->response->addJSON('themeColorMode', '');

                return null;
            }

            $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

            return null;
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

            return null;
        }

        $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

        return null;
    }
}
