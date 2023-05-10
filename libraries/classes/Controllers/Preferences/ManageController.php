<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function define;
use function file_exists;
use function is_array;
use function is_string;
use function is_uploaded_file;
use function json_decode;
use function json_encode;
use function mb_strpos;
use function mb_substr;
use function parse_url;
use function str_replace;
use function urlencode;
use function var_export;

use const CONFIG_FILE;
use const JSON_PRETTY_PRINT;
use const PHP_URL_PATH;
use const UPLOAD_ERR_OK;

/**
 * User preferences management page.
 */
class ManageController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private UserPreferences $userPreferences,
        private Relation $relation,
        private Config $config,
        private ThemeManager $themeManager,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['cf'] ??= null;
        $GLOBALS['error'] ??= null;
        $GLOBALS['json'] ??= null;
        $GLOBALS['lang'] ??= null;
        $GLOBALS['new_config'] ??= null;
        $GLOBALS['return_url'] ??= null;
        $GLOBALS['form_display'] ??= null;
        $GLOBALS['all_ok'] ??= null;
        $GLOBALS['query'] ??= null;

        $route = $request->getRoute();

        $GLOBALS['cf'] = new ConfigFile($this->config->baseSettings);
        $this->userPreferences->pageInit($GLOBALS['cf']);

        $GLOBALS['error'] = '';
        if ($request->hasBodyParam('submit_export') && $request->getParsedBodyParam('export_type') === 'text_file') {
            // export to JSON file
            $this->response->disable();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.json';
            Core::downloadHeader($filename, 'application/json');
            $settings = $this->userPreferences->load();
            echo json_encode($settings['config_data'], JSON_PRETTY_PRINT);

            return;
        }

        if ($request->hasBodyParam('submit_export') && $request->getParsedBodyParam('export_type') === 'php_file') {
            // export to JSON file
            $this->response->disable();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.php';
            Core::downloadHeader($filename, 'application/php');
            $settings = $this->userPreferences->load();
            echo '/* ' . __('phpMyAdmin configuration snippet') . " */\n\n";
            echo '/* ' . __('Paste it to your config.inc.php') . " */\n\n";
            foreach ($settings['config_data'] as $key => $val) {
                echo '$cfg[\'' . str_replace('/', '\'][\'', $key) . '\'] = ';
                echo var_export($val, true) . ";\n";
            }

            return;
        }

        if ($request->hasBodyParam('submit_get_json')) {
            $settings = $this->userPreferences->load();
            $this->response->addJSON('prefs', json_encode($settings['config_data']));
            $this->response->addJSON('mtime', $settings['mtime']);

            return;
        }

        if ($request->hasBodyParam('submit_import')) {
            // load from JSON file
            $GLOBALS['json'] = '';
            if (
                $request->hasBodyParam('import_type')
                && $request->getParsedBodyParam('import_type') === 'text_file'
                && isset($_FILES['import_file'])
                && is_array($_FILES['import_file'])
                && $_FILES['import_file']['error'] == UPLOAD_ERR_OK
                && isset($_FILES['import_file']['tmp_name'])
                && is_string($_FILES['import_file']['tmp_name'])
                && is_uploaded_file($_FILES['import_file']['tmp_name'])
            ) {
                $importHandle = new File($_FILES['import_file']['tmp_name']);
                $importHandle->checkUploadedFile();
                if ($importHandle->isError()) {
                    $GLOBALS['error'] = $importHandle->getError();
                } else {
                    // read JSON from uploaded file
                    $GLOBALS['json'] = $importHandle->getRawContent();
                }
            } else {
                // read from POST value (json)
                $GLOBALS['json'] = $request->getParsedBodyParam('json');
            }

            // hide header message
            $_SESSION['userprefs_autoload'] = true;

            $configuration = json_decode($GLOBALS['json'], true);
            $GLOBALS['return_url'] = $request->getParsedBodyParam('return_url');
            if (! is_array($configuration)) {
                if (! isset($GLOBALS['error'])) {
                    $GLOBALS['error'] = __('Could not import configuration');
                }
            } else {
                // sanitize input values: treat them as though
                // they came from HTTP POST request
                $GLOBALS['form_display'] = new UserFormList($GLOBALS['cf']);
                $GLOBALS['new_config'] = $GLOBALS['cf']->getFlatDefaultConfig();
                if ($request->hasBodyParam('import_merge')) {
                    $GLOBALS['new_config'] = array_merge($GLOBALS['new_config'], $GLOBALS['cf']->getConfigArray());
                }

                $GLOBALS['new_config'] = array_merge($GLOBALS['new_config'], $configuration);
                $postParamBackup = $_POST;
                foreach ($GLOBALS['new_config'] as $k => $v) {
                    $_POST[str_replace('/', '-', (string) $k)] = $v;
                }

                $GLOBALS['cf']->resetConfigData();
                $GLOBALS['all_ok'] = $GLOBALS['form_display']->process(true, false);
                $GLOBALS['all_ok'] = $GLOBALS['all_ok'] && ! $GLOBALS['form_display']->hasErrors();
                $_POST = $postParamBackup;

                if (! $GLOBALS['all_ok'] && $request->hasBodyParam('fix_errors')) {
                    $GLOBALS['form_display']->fixErrors();
                    $GLOBALS['all_ok'] = true;
                }

                if (! $GLOBALS['all_ok']) {
                    // mimic original form and post json in a hidden field
                    $relationParameters = $this->relation->getRelationParameters();

                    echo $this->template->render('preferences/header', [
                        'route' => $route,
                        'is_saved' => $request->hasQueryParam('saved'),
                        'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
                    ]);

                    echo $this->template->render('preferences/manage/error', [
                        'form_errors' => $GLOBALS['form_display']->displayErrors(),
                        'json' => $GLOBALS['json'],
                        'import_merge' => $request->getParsedBodyParam('import_merge'),
                        'return_url' => $GLOBALS['return_url'],
                    ]);

                    return;
                }

                // check for ThemeDefault
                $redirectParams = [];
                if (
                    isset($configuration['ThemeDefault'])
                    && $this->themeManager->theme->getId() != $configuration['ThemeDefault']
                    && $this->themeManager->checkTheme($configuration['ThemeDefault'])
                ) {
                    $this->themeManager->setActiveTheme($configuration['ThemeDefault']);
                    $this->themeManager->setThemeCookie();
                }

                if (isset($configuration['lang']) && $configuration['lang'] != $GLOBALS['lang']) {
                    $redirectParams['lang'] = $configuration['lang'];
                }

                // save settings
                $result = $this->userPreferences->save($GLOBALS['cf']->getConfigArray());
                if ($result === true) {
                    if ($GLOBALS['return_url']) {
                        $GLOBALS['query'] = Util::splitURLQuery($GLOBALS['return_url']);
                        $GLOBALS['return_url'] = parse_url($GLOBALS['return_url'], PHP_URL_PATH);

                        foreach ($GLOBALS['query'] as $q) {
                            $pos = mb_strpos($q, '=');
                            $k = mb_substr($q, 0, (int) $pos);
                            if ($k === 'token') {
                                continue;
                            }

                            $redirectParams[$k] = mb_substr($q, $pos + 1);
                        }
                    } else {
                        $GLOBALS['return_url'] = 'index.php?route=/preferences/manage';
                    }

                    // reload config
                    $this->config->loadUserPreferences($this->themeManager);
                    $this->userPreferences->redirect($GLOBALS['return_url'] ?? '', $redirectParams);

                    return;
                }

                $GLOBALS['error'] = $result;
            }
        } elseif ($request->hasBodyParam('submit_clear')) {
            $result = $this->userPreferences->save([]);
            if ($result === true) {
                $this->config->removeCookie('pma_collaction_connection');
                $this->config->removeCookie('pma_lang');
                $this->userPreferences->redirect('index.php?route=/preferences/manage');

                return;
            }

            $GLOBALS['error'] = $result;

            return;
        }

        $relationParameters = $this->relation->getRelationParameters();

        echo $this->template->render('preferences/header', [
            'route' => $route,
            'is_saved' => $request->hasQueryParam('saved'),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        if ($GLOBALS['error']) {
            if (! $GLOBALS['error'] instanceof Message) {
                $GLOBALS['error'] = Message::error($GLOBALS['error']);
            }

            $GLOBALS['error']->getDisplay();
        }

        echo $this->template->render('preferences/manage/main', [
            'error' => $GLOBALS['error'],
            'max_upload_size' => $GLOBALS['config']->get('max_upload_size'),
            'exists_setup_and_not_exists_config' => @file_exists(ROOT_PATH . 'setup/index.php')
                && ! @file_exists(CONFIG_FILE),
        ]);

        if ($this->response->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            define('PMA_DISABLE_NAVI_SETTINGS', true);
        }
    }
}
