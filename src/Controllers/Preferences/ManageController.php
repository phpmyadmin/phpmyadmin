<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Preferences;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
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
#[Route('/preferences/manage', ['GET', 'POST'])]
final class ManageController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly UserPreferences $userPreferences,
        private readonly Relation $relation,
        private readonly Config $config,
        private readonly ThemeManager $themeManager,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $route = $request->getRoute();

        $configFile = new ConfigFile($this->config->baseSettings);
        $this->userPreferences->pageInit($configFile);

        $error = null;
        if ($request->hasBodyParam('submit_export') && $request->getParsedBodyParam('export_type') === 'text_file') {
            // export to JSON file
            $response = $this->responseFactory->createResponse();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getEnv('HTTP_HOST')) . '.json';
            Core::downloadHeader($filename, 'application/json');
            $settings = $this->userPreferences->load();

            return $response->write((string) json_encode($settings['config_data'], JSON_PRETTY_PRINT));
        }

        if ($request->hasBodyParam('submit_export') && $request->getParsedBodyParam('export_type') === 'php_file') {
            // export to PHP file
            $response = $this->responseFactory->createResponse();
            $filename = 'phpMyAdmin-config-' . urlencode(Core::getEnv('HTTP_HOST')) . '.php';
            Core::downloadHeader($filename, 'application/php');
            $settings = $this->userPreferences->load();

            $output = '/* ' . __('phpMyAdmin configuration snippet') . " */\n\n";
            $output .= '/* ' . __('Paste it to your config.inc.php') . " */\n\n";
            foreach ($settings['config_data'] as $key => $val) {
                $output .= '$cfg[\'' . str_replace('/', '\'][\'', $key) . '\'] = ';
                $output .= var_export($val, true) . ";\n";
            }

            return $response->write($output);
        }

        if ($request->hasBodyParam('submit_get_json')) {
            $settings = $this->userPreferences->load();
            $this->response->addJSON('prefs', json_encode($settings['config_data']));
            $this->response->addJSON('mtime', $settings['mtime']);

            return $this->response->response();
        }

        if ($request->hasBodyParam('submit_import')) {
            // load from JSON file
            $json = '';
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
                    $error = $importHandle->getError();
                } else {
                    // read JSON from uploaded file
                    $json = $importHandle->getRawContent();
                }
            } else {
                // read from POST value (json)
                $json = $request->getParsedBodyParamAsString('json');
            }

            // hide header message
            $_SESSION['userprefs_autoload'] = true;

            $configuration = json_decode($json, true);
            $returnUrl = $request->getParsedBodyParamAsStringOrNull('return_url');
            if (! is_array($configuration)) {
                if ($error === null) {
                    $error = Message::error(__('Could not import configuration'));
                }
            } else {
                // sanitize input values: treat them as though
                // they came from HTTP POST request
                $formDisplay = new UserFormList($configFile);
                $newConfig = $configFile->getFlatDefaultConfig();
                if ($request->hasBodyParam('import_merge')) {
                    $newConfig = array_merge($newConfig, $configFile->getConfigArray());
                }

                $newConfig = array_merge($newConfig, $configuration);
                $postParamBackup = $_POST;
                foreach ($newConfig as $k => $v) {
                    $_POST[str_replace('/', '-', (string) $k)] = $v;
                }

                $configFile->resetConfigData();
                $allOk = $formDisplay->process(true, false);
                $allOk = $allOk && ! $formDisplay->hasErrors();
                $_POST = $postParamBackup;

                if (! $allOk && $request->hasBodyParam('fix_errors')) {
                    $formDisplay->fixErrors();
                    $allOk = true;
                }

                if (! $allOk) {
                    // mimic original form and post json in a hidden field
                    $relationParameters = $this->relation->getRelationParameters();

                    $this->response->render('preferences/header', [
                        'route' => $route,
                        'is_saved' => $request->hasQueryParam('saved'),
                        'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
                    ]);

                    $this->response->render('preferences/manage/error', [
                        'form_errors' => $formDisplay->displayErrors(),
                        'json' => $json,
                        'import_merge' => $request->getParsedBodyParam('import_merge'),
                        'return_url' => $returnUrl,
                    ]);

                    return $this->response->response();
                }

                // check for ThemeDefault
                $redirectParams = [];
                if (
                    isset($configuration['ThemeDefault'])
                    && $this->themeManager->theme->getId() != $configuration['ThemeDefault']
                    && $this->themeManager->themeExists($configuration['ThemeDefault'])
                ) {
                    $this->themeManager->setActiveTheme($configuration['ThemeDefault']);
                    $this->themeManager->setThemeCookie();
                }

                if (isset($configuration['lang']) && $configuration['lang'] !== Current::$lang) {
                    $redirectParams['lang'] = $configuration['lang'];
                }

                // save settings
                $result = $this->userPreferences->save($configFile->getConfigArray());
                if ($result === true) {
                    if ($returnUrl !== null && $returnUrl !== '') {
                        $query = Util::splitURLQuery($returnUrl);
                        $returnUrl = parse_url($returnUrl, PHP_URL_PATH);

                        foreach ($query as $q) {
                            $pos = mb_strpos($q, '=');
                            $k = mb_substr($q, 0, (int) $pos);
                            if ($k === 'token') {
                                continue;
                            }

                            $redirectParams[$k] = mb_substr($q, $pos + 1);
                        }
                    } else {
                        $returnUrl = 'index.php?route=/preferences/manage';
                    }

                    // reload config
                    $this->config->loadUserPreferences($this->themeManager);

                    return $this->userPreferences->redirect($returnUrl ?? '', $redirectParams);
                }

                $error = $result;
            }
        } elseif ($request->hasBodyParam('submit_clear')) {
            $result = $this->userPreferences->save([]);
            if ($result === true) {
                $this->config->removeCookie('pma_lang');

                return $this->userPreferences->redirect('index.php?route=/preferences/manage');
            }

            return $this->response->response();
        }

        $relationParameters = $this->relation->getRelationParameters();

        $this->response->render('preferences/header', [
            'route' => $route,
            'is_saved' => $request->hasQueryParam('saved'),
            'has_config_storage' => $relationParameters->userPreferencesFeature !== null,
        ]);

        $this->response->render('preferences/manage/main', [
            'error' => $error instanceof Message ? $error->getDisplay() : '',
            'max_upload_size' => Util::getUploadSizeInBytes(),
            'exists_setup_and_not_exists_config' => @file_exists(ROOT_PATH . 'setup/index.php')
                && ! @file_exists(CONFIG_FILE),
        ]);

        if ($request->isAjax()) {
            $this->response->addJSON('disableNaviSettings', true);
        } else {
            Navigation::$isSettingsEnabled = false;
        }

        return $this->response->response();
    }
}
