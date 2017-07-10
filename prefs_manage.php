<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\ConfigFile;
use PMA\libraries\config\FormDisplay;
use PMA\libraries\File;
use PMA\libraries\Message;
use PMA\libraries\Response;
use PMA\libraries\Util;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;
use PMA\libraries\ThemeManager;

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require 'libraries/config/user_preferences.forms.php';

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
PMA_userprefsPageInit($cf);
$response = Response::getInstance();

$error = '';
if (isset($_POST['submit_export'])
    && isset($_POST['export_type'])
    && $_POST['export_type'] == 'text_file'
) {
    // export to JSON file
    $response->disable();
    $filename = 'phpMyAdmin-config-' . urlencode(PMA_getenv('HTTP_HOST')) . '.json';
    PMA_downloadHeader($filename, 'application/json');
    $settings = PMA_loadUserprefs();
    echo json_encode($settings['config_data'], JSON_PRETTY_PRINT);
    exit;
} elseif (isset($_POST['submit_export'])
    && isset($_POST['export_type'])
    && $_POST['export_type'] == 'php_file'
) {
    // export to JSON file
    $response->disable();
    $filename = 'phpMyAdmin-config-' . urlencode(PMA_getenv('HTTP_HOST')) . '.php';
    PMA_downloadHeader($filename, 'application/php');
    $settings = PMA_loadUserprefs();
    echo '/* ' . _('phpMyAdmin configuration snippet') . " */\n\n";
    echo '/* ' . _('Paste it to your config.inc.php') . " */\n\n";
    foreach ($settings['config_data'] as $key => $val) {
        echo '$cfg[\'' . str_replace('/', '\'][\'', $key) . '\'] = ';
        echo var_export($val, true) . ";\n";
    }
    exit;
} else if (isset($_POST['submit_get_json'])) {
    $settings = PMA_loadUserprefs();
    $response->addJSON('prefs', json_encode($settings['config_data']));
    $response->addJSON('mtime', $settings['mtime']);
    exit;
} else if (isset($_POST['submit_import'])) {
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
        $form_display = new FormDisplay($cf);
        foreach ($forms as $formset_id => $formset) {
            foreach ($formset as $form_name => $form) {
                $form_display->registerForm($formset_id . ': ' . $form_name, $form);
            }
        }
        $new_config = $cf->getFlatDefaultConfig();
        if (!empty($_POST['import_merge'])) {
            $new_config = array_merge($new_config, $cf->getConfigArray());
        }
        $new_config = array_merge($new_config, $config);
        $_POST_bak = $_POST;
        foreach ($new_config as $k => $v) {
            $_POST[str_replace('/', '-', $k)] = $v;
        }
        $cf->resetConfigData();
        $all_ok = $form_display->process(true, false);
        $all_ok = $all_ok && !$form_display->hasErrors();
        $_POST = $_POST_bak;

        if (!$all_ok && isset($_POST['fix_errors'])) {
            $form_display->fixErrors();
            $all_ok = true;
        }
        if (!$all_ok) {
            // mimic original form and post json in a hidden field
            include 'libraries/user_preferences.inc.php';
            $msg = Message::error(
                __('Configuration contains incorrect data for some fields.')
            );
            $msg->display();
            echo '<div class="config-form">';
            echo $form_display->displayErrors();
            echo '</div>';
            echo '<form action="prefs_manage.php" method="post">';
            echo URL::getHiddenInputs() , "\n";
            echo '<input type="hidden" name="json" value="'
                , htmlspecialchars($json) , '" />';
            echo '<input type="hidden" name="fix_errors" value="1" />';
            if (! empty($_POST['import_merge'])) {
                echo '<input type="hidden" name="import_merge" value="1" />';
            }
            if ($return_url) {
                echo '<input type="hidden" name="return_url" value="'
                    , htmlspecialchars($return_url) , '" />';
            }
            echo '<p>';
            echo __('Do you want to import remaining settings?');
            echo '</p>';
            echo '<input type="submit" name="submit_import" value="'
                , __('Yes') , '" />';
            echo '<input type="submit" name="submit_ignore" value="'
                , __('No') , '" />';
            echo '</form>';
            exit;
        }

        // check for ThemeDefault and fontsize
        $params = array();
        $tmanager = ThemeManager::getInstance();
        if (isset($config['ThemeDefault'])
            && $tmanager->theme->getId() != $config['ThemeDefault']
            && $tmanager->checkTheme($config['ThemeDefault'])
        ) {
            $tmanager->setActiveTheme($config['ThemeDefault']);
            $tmanager->setThemeCookie();
        }
        if (isset($config['fontsize'])
            && $config['fontsize'] != $GLOBALS['PMA_Config']->get('fontsize')
        ) {
            $params['set_fontsize'] = $config['fontsize'];
        }
        if (isset($config['lang'])
            && $config['lang'] != $GLOBALS['lang']
        ) {
            $params['lang'] = $config['lang'];
        }
        if (isset($config['collation_connection'])
            && $config['collation_connection'] != $GLOBALS['collation_connection']
        ) {
            $params['collation_connection'] = $config['collation_connection'];
        }

        // save settings
        $result = PMA_saveUserprefs($cf->getConfigArray());
        if ($result === true) {
            if ($return_url) {
                $query =  PMA\libraries\Util::splitURLQuery($return_url);
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
            PMA_userprefsRedirect($return_url, $params);
            exit;
        } else {
            $error = $result;
        }
    }
} else if (isset($_POST['submit_clear'])) {
    $result = PMA_saveUserprefs(array());
    if ($result === true) {
        $params = array();
        if ($GLOBALS['PMA_Config']->get('fontsize') != '82%') {
            $GLOBALS['PMA_Config']->removeCookie('pma_fontsize');
        }
        $GLOBALS['PMA_Config']->removeCookie('pma_collaction_connection');
        $GLOBALS['PMA_Config']->removeCookie('pma_lang');
        PMA_userprefsRedirect('prefs_manage.php', $params);
        exit;
    } else {
        $error = $result;
    }
    exit;
}

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('config.js');

require 'libraries/user_preferences.inc.php';
if ($error) {
    if (!$error instanceof Message) {
        $error = Message::error($error);
    }
    $error->display();
}
?>
<script type="text/javascript">
<?php
Sanitize::printJsValue("PMA_messages['strSavedOn']", __('Saved on: @DATE@'));
?>
</script>
<div id="maincontainer">
    <div id="main_pane_left">
        <div class="group">
<?php
echo '<h2>' , __('Import') , '</h2>'
    , '<form class="group-cnt prefs-form disableAjax" name="prefs_import"'
    , ' action="prefs_manage.php" method="post" enctype="multipart/form-data">'
    , Util::generateHiddenMaxFileSize($GLOBALS['max_upload_size'])
    , URL::getHiddenInputs()
    , '<input type="hidden" name="json" value="" />'
    , '<input type="radio" id="import_text_file" name="import_type"'
    , ' value="text_file" checked="checked" />'
    , '<label for="import_text_file">' . __('Import from file') . '</label>'
    , '<div id="opts_import_text_file" class="prefsmanage_opts">'
    , '<label for="input_import_file">' , __('Browse your computer:') , '</label>'
    , '<input type="file" name="import_file" id="input_import_file" />'
    , '</div>'
    , '<input type="radio" id="import_local_storage" name="import_type"'
    , ' value="local_storage" disabled="disabled" />'
    , '<label for="import_local_storage">'
    , __('Import from browser\'s storage') , '</label>'
    , '<div id="opts_import_local_storage" class="prefsmanage_opts disabled">'
    , '<div class="localStorage-supported">'
    , __('Settings will be imported from your browser\'s local storage.')
    , '<br />'
    , '<div class="localStorage-exists">'
    , __('Saved on: @DATE@')
    , '</div>'
    , '<div class="localStorage-empty">';
Message::notice(__('You have no saved settings!'))->display();
echo  '</div>'
    , '</div>'
    , '<div class="localStorage-unsupported">';
Message::notice(
    __('This feature is not supported by your web browser')
)->display();
echo '</div>'
    , '</div>'
    , '<input type="checkbox" id="import_merge" name="import_merge" />'
    , '<label for="import_merge">'
    , __('Merge with current configuration') . '</label>'
    , '<br /><br />'
    , '<input type="submit" name="submit_import" value="'
    , __('Go') . '" />'
    , '</form>'
    , '</div>';
if (@file_exists('setup/index.php') && ! @file_exists(CONFIG_FILE)) {
            // show only if setup script is available, allows to disable this message
            // by simply removing setup directory
            // Also do not show in config exists (and setup would refuse to work)
            ?>
            <div class="group">
            <h2><?php echo __('More settings') ?></h2>
            <div class="group-cnt">
                <?php
                echo sprintf(
                    __(
                        'You can set more settings by modifying config.inc.php, eg. '
                        . 'by using %sSetup script%s.'
                    ), '<a href="setup/index.php" target="_blank">', '</a>'
                ) , PMA\libraries\Util::showDocu('setup', 'setup-script');
                ?>
            </div>
            </div>
        <?php
}
        ?>
    </div>
    <div id="main_pane_right">
        <div class="group">
            <h2><?php echo __('Export'); ?></h2>
            <div class="click-hide-message group-cnt" style="display:none">
                <?php
                Message::rawSuccess(
                    __('Configuration has been saved.')
                )->display();
                ?>
            </div>
            <form class="group-cnt prefs-form disableAjax" name="prefs_export"
                  action="prefs_manage.php" method="post">
                <?php echo URL::getHiddenInputs(); ?>
                <div style="padding-bottom:0.5em">
                    <input type="radio" id="export_text_file" name="export_type"
                           value="text_file" checked="checked" />
                    <label for="export_text_file">
                        <?php echo __('Save as file'); ?>
                    </label><br />
                    <input type="radio" id="export_php_file" name="export_type"
                           value="php_file" />
                    <label for="export_php_file">
                        <?php echo __('Save as PHP file'); ?>
                    </label><br />
                    <input type="radio" id="export_local_storage" name="export_type"
                           value="local_storage" disabled="disabled" />
                    <label for="export_local_storage">
                        <?php echo __('Save to browser\'s storage'); ?></label>
                </div>
                <div id="opts_export_local_storage"
                     class="prefsmanage_opts disabled">
                    <span class="localStorage-supported">
                        <?php
                        echo __(
                            'Settings will be saved in your browser\'s local '
                            . 'storage.'
                        );
                        ?>
                        <div class="localStorage-exists">
                            <b>
                                <?php
                                echo __(
                                    'Existing settings will be overwritten!'
                                );
                                ?>
                            </b>
                        </div>
                    </span>
                    <div class="localStorage-unsupported">
                        <?php
                        Message::notice(
                            __('This feature is not supported by your web browser')
                        )->display();
                        ?>
                    </div>
                </div>
                <br />
                <?php
                echo '<input type="submit" name="submit_export" value="' , __(
                    'Go'
                ) , '" />';
                ?>
            </form>
        </div>
        <div class="group">
            <h2><?php echo __('Reset'); ?></h2>
            <form class="group-cnt prefs-form disableAjax" name="prefs_reset"
                  action="prefs_manage.php" method="post">
                <?php
                echo URL::getHiddenInputs() , __(
                    'You can reset all your settings and restore them to default '
                    . 'values.'
                );
                ?>
                <br /><br />
                <input type="submit" name="submit_clear"
                       value="<?php echo __('Reset'); ?>"/>
            </form>
        </div>
    </div>
    <br class="clearfloat" />
</div>

<?php
if ($response->isAjax()) {
    $response->addJSON('_disableNaviSettings', true);
} else {
    define('PMA_DISABLE_NAVI_SETTINGS', true);
}
