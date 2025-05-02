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

final readonly class ThemeSetController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private ThemeManager $themeManager,
        private UserPreferences $userPreferences,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $theme = $request->getParsedBodyParamAsString('set_theme');
        if (! $this->config->settings['ThemeManager'] || $theme === '') {
            if ($request->isAjax()) {
                $this->response->addJSON('themeColorMode', '');

                return $this->response->response();
            }

            $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

            return $this->response->response();
        }

        $this->themeManager->setActiveTheme($theme);

        $themeColorMode = $request->getParsedBodyParamAsString('themeColorMode', '');
        if ($themeColorMode !== '') {
            $this->themeManager->theme->setColorMode($themeColorMode);
        }

        $this->themeManager->setThemeCookie();

        $preferences = $this->userPreferences->load();
        $preferences['config_data']['ThemeDefault'] = $theme;
        $this->userPreferences->save($preferences['config_data']);

        if ($request->isAjax()) {
            $this->response->addJSON('themeColorMode', $this->themeManager->theme->getColorMode());

            return $this->response->response();
        }

        $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

        return $this->response->response();
    }
}
