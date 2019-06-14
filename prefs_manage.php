<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\File;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\UserPreferencesHeader;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets some core libraries and displays a top message if required
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Template $template */
$template = $containerBuilder->get('template');
/** @var Relation $relation */
$relation = $containerBuilder->get('relation');

$userPreferences = new UserPreferences();

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
$userPreferences->pageInit($cf);
$response = Response::getInstance();

$error = '';
if (isset($_POST['submit_export'])
    && isset($_POST['export_type'])
    && $_POST['export_type'] == 'text_file'
) {
    // export to JSON file
    $response->disable();
    $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.json';
    Core::downloadHeader($filename, 'application/json');
    $settings = $userPreferences->load();
    echo json_encode($settings['config_data'], JSON_PRETTY_PRINT);
    exit;
} elseif (isset($_POST['submit_export'])
    && isset($_POST['export_type'])
    && $_POST['export_type'] == 'php_file'
) {
    // export to JSON file
    $response->disable();
    $filename = 'phpMyAdmin-config-' . urlencode(Core::getenv('HTTP_HOST')) . '.php';
    Core::downloadHeader($filename, 'application/php');
    $settings = $userPreferences->load();
    echo '/* ' . __('phpMyAdmin configuration snippet') . " */\n\n";
    echo '/* ' . __('Paste it to your config.inc.php') . " */\n\n";
    foreach ($settings['config_data'] as $key => $val) {
        echo '$cfg[\'' . str_replace('/', '\'][\'', $key) . '\'] = ';
        echo var_export($val, true) . ";\n";
    }
    exit;
} elseif (isset($_POST['submit_get_json'])) {
    $settings = $userPreferences->load();
    $response->addJSON('prefs', json_encode($settings['config_data']));
    $response->addJSON('mtime', $settings['mtime']);
    exit;
} elseif (isset($_POST['submit_import'])) {
    // load from JSON file
    $json = '';
    if (isset($_POST['import_type'])
        && $_POST['import_type'] == 'text_file'
        && isset($_FILES['import_file'])
        && $_FILES['import_file']['error'] == UPLOAD_ERR_OK
        && is_uploaded_file($_FILES['import_file']['tmp_name'])
    ) {
        $import_handle = new File($_FILES['import_file']['tmp_name']);
        $import_handle->checkUploadedFile();
        if ($import_handle->isError()) {
            $error = $import_handle->getError();
        } else {
            // read JSON from uploaded file
            $json = $import_handle->getRawContent();
        }
    } else {
        // read from POST value (json)
        $json = isset($_POST['json']) ? $_POST['json'] : null;
    }

    // hide header message
    $_SESSION['userprefs_autoload'] = true;

    $config = json_decode($json, true);
    $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : null;
    if (! is_array($config)) {
        if (! isset($error)) {
            $error = __('Could not import configuration');
        }
    } else {
        // sanitize input values: treat them as though
        // they came from HTTP POST request
        $form_display = new UserFormList($cf);
        $new_config = $cf->getFlatDefaultConfig();
        if (! empty($_POST['import_merge'])) {
            $new_config = array_merge($new_config, $cf->getConfigArray());
        }
        $new_config = array_merge($new_config, $config);
        $_POST_bak = $_POST;
        foreach ($new_config as $k => $v) {
            $_POST[str_replace('/', '-', $k)] = $v;
        }
        $cf->resetConfigData();
        $all_ok = $form_display->process(true, false);
        $all_ok = $all_ok && ! $form_display->hasErrors();
        $_POST = $_POST_bak;

        if (! $all_ok && isset($_POST['fix_errors'])) {
            $form_display->fixErrors();
            $all_ok = true;
        }
        if (! $all_ok) {
            // mimic original form and post json in a hidden field
            echo UserPreferencesHeader::getContent($template, $relation);

            echo $template->render('preferences/manage/error', [
                'form_errors' => $form_display->displayErrors(),
                'json' => $json,
                'import_merge' => isset($_POST['import_merge']) ? $_POST['import_merge'] : null,
                'return_url' => $return_url,
            ]);
            exit;
        }

        // check for ThemeDefault
        $params = [];
        $tmanager = ThemeManager::getInstance();
        if (isset($config['ThemeDefault'])
            && $tmanager->theme->getId() != $config['ThemeDefault']
            && $tmanager->checkTheme($config['ThemeDefault'])
        ) {
            $tmanager->setActiveTheme($config['ThemeDefault']);
            $tmanager->setThemeCookie();
        }
        if (isset($config['lang'])
            && $config['lang'] != $GLOBALS['lang']
        ) {
            $params['lang'] = $config['lang'];
        }

        // save settings
        $result = $userPreferences->save($cf->getConfigArray());
        if ($result === true) {
            if ($return_url) {
                $query = PhpMyAdmin\Util::splitURLQuery($return_url);
                $return_url = parse_url($return_url, PHP_URL_PATH);

                foreach ($query as $q) {
                    $pos = mb_strpos($q, '=');
                    $k = mb_substr($q, 0, $pos);
                    if ($k == 'token') {
                        continue;
                    }
                    $params[$k] = mb_substr($q, $pos + 1);
                }
            } else {
                $return_url = 'prefs_manage.php';
            }
            // reload config
            $GLOBALS['PMA_Config']->loadUserPreferences();
            $userPreferences->redirect($return_url, $params);
            exit;
        } else {
            $error = $result;
        }
    }
} elseif (isset($_POST['submit_clear'])) {
    $result = $userPreferences->save([]);
    if ($result === true) {
        $params = [];
        $GLOBALS['PMA_Config']->removeCookie('pma_collaction_connection');
        $GLOBALS['PMA_Config']->removeCookie('pma_lang');
        $userPreferences->redirect('prefs_manage.php', $params);
        exit;
    } else {
        $error = $result;
    }
    exit;
}

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('config.js');

echo UserPreferencesHeader::getContent($template, $relation);
if ($error) {
    if (! $error instanceof Message) {
        $error = Message::error($error);
    }
    $error->getDisplay();
}

echo $template->render('preferences/manage/main', [
    'error' => $error,
    'max_upload_size' => $GLOBALS['max_upload_size'],
    'exists_setup_and_not_exists_config' => @file_exists(ROOT_PATH . 'setup/index.php') && ! @file_exists(CONFIG_FILE),
]);

if ($response->isAjax()) {
    $response->addJSON('disableNaviSettings', true);
} else {
    define('PMA_DISABLE_NAVI_SETTINGS', true);
}
