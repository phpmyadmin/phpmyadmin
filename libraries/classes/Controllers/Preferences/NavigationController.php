<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\NaviForm;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

use function define;
use function ltrim;

class NavigationController extends AbstractController
{
    /** @var UserPreferences */
    private $userPreferences;

    /** @var Relation */
    private $relation;

    /** @var Config */
    private $config;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        UserPreferences $userPreferences,
        Relation $relation,
        Config $config
    ) {
        parent::__construct($response, $template);
        $this->userPreferences = $userPreferences;
        $this->relation = $relation;
        $this->config = $config;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['cf'] = $GLOBALS['cf'] ?? null;
        $GLOBALS['error'] = $GLOBALS['error'] ?? null;
        $GLOBALS['tabHash'] = $GLOBALS['tabHash'] ?? null;
        $GLOBALS['hash'] = $GLOBALS['hash'] ?? null;
        $GLOBALS['server'] = $GLOBALS['server'] ?? null;

        $GLOBALS['cf'] = new ConfigFile($this->config->baseSettings);
        $this->userPreferences->pageInit($GLOBALS['cf']);

        $formDisplay = new NaviForm($GLOBALS['cf'], 1);

        if (isset($_POST['revert'])) {
            // revert erroneous fields to their default values
            $formDisplay->fixErrors();
            $this->redirect('/preferences/navigation');

            return;
        }

        $GLOBALS['error'] = null;
        if ($formDisplay->process(false) && ! $formDisplay->hasErrors()) {
            // Load 2FA settings
            $twoFactor = new TwoFactor($GLOBALS['cfg']['Server']['user']);
            // save settings
            $result = $this->userPreferences->save($GLOBALS['cf']->getConfigArray());
            // save back the 2FA setting only
            $twoFactor->save();
            if ($result === true) {
                // reload config
                $this->config->loadUserPreferences();
                $GLOBALS['tabHash'] = $_POST['tab_hash'] ?? null;
                $GLOBALS['hash'] = ltrim($GLOBALS['tabHash'], '#');
                $this->userPreferences->redirect('index.php?route=/preferences/navigation', null, $GLOBALS['hash']);

                return;
            }

            $GLOBALS['error'] = $result;
        }

        $this->addScriptFiles(['config.js']);

        $relationParameters = $this->relation->getRelationParameters();

        $this->render('preferences/header', [
            'route' => $request->getRoute(),
            'is_saved' => ! empty($_GET['saved']),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        if ($formDisplay->hasErrors()) {
            $formErrors = $formDisplay->displayErrors();
        }

        $this->render('preferences/forms/main', [
            'error' => $GLOBALS['error'] ? $GLOBALS['error']->getDisplay() : '',
            'has_errors' => $formDisplay->hasErrors(),
            'errors' => $formErrors ?? null,
            'form' => $formDisplay->getDisplay(
                true,
                Url::getFromRoute('/preferences/navigation'),
                ['server' => $GLOBALS['server']]
            ),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);

            return;
        }

        define('PMA_DISABLE_NAVI_SETTINGS', true);
    }
}
