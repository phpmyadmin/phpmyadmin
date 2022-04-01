<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function is_string;

final class ThemeSetController extends AbstractController
{
    /** @var ThemeManager */
    private $themeManager;

    /** @var UserPreferences */
    private $userPreferences;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        ThemeManager $themeManager,
        UserPreferences $userPreferences
    ) {
        parent::__construct($response, $template);
        $this->themeManager = $themeManager;
        $this->userPreferences = $userPreferences;
    }

    public function __invoke(ServerRequest $request): void
    {
        $theme = $request->getParsedBodyParam('set_theme');
        if (! $GLOBALS['cfg']['ThemeManager'] || ! is_string($theme) || $theme === '') {
            $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));

            return;
        }

        $this->themeManager->setActiveTheme($theme);
        $this->themeManager->setThemeCookie();

        $preferences = $this->userPreferences->load();
        $preferences['config_data']['ThemeDefault'] = $theme;
        $this->userPreferences->save($preferences['config_data']);

        $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
    }
}
