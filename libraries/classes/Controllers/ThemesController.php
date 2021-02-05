<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

class ThemesController extends AbstractController
{
    /** @var ThemeManager */
    private $themeManager;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template, ThemeManager $themeManager)
    {
        parent::__construct($response, $template);
        $this->themeManager = $themeManager;
    }

    public function index(): void
    {
        $themes = $this->themeManager->getThemesArray();
        $themesList = $this->template->render('home/themes', ['themes' => $themes]);
        $this->response->setAjax(true);
        $this->response->addJSON('themes', $themesList);
    }

    public function setTheme(): void
    {
        global $cfg;

        if (! $cfg['ThemeManager'] || ! isset($_POST['set_theme'])) {
            $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));

            return;
        }

        $this->themeManager->setActiveTheme($_POST['set_theme']);
        $this->themeManager->setThemeCookie();

        $userPreferences = new UserPreferences();
        $preferences = $userPreferences->load();
        $preferences['config_data']['ThemeDefault'] = $_POST['set_theme'];
        $userPreferences->save($preferences['config_data']);

        $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
    }
}
