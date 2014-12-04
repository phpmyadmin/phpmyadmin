<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/config/FormDisplay.class.php';
require 'libraries/config/user_preferences.forms.php';

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
PMA_userprefsPageInit($cf);

$error = '';
if (isset($_POST['submit_export'])
    && filter_input(INPUT_POST, 'export_type') == 'text_file'
) {
    // export to JSON file
    PMA_Response::getInstance()->disable();
    $filename = 'phpMyAdmin-config-' . urlencode(PMA_getenv('HTTP_HOST')) . '.json';
    PMA_downloadHeader($filename, 'application/json');
    $settings = PMA_loadUserprefs();
    echo json_encode($settings['config_data']);
    exit;
} else if (isset($_POST['submit_get_json'])) {
    $settings = PMA_loadUserprefs();
    $response = PMA_Response::getInstance();
    $response->addJSON('prefs', json_encode($settings['config_data']));
    $response->addJSON('mtime', $settings['mtime']);
    exit;
} else if (isset($_POST['submit_import'])) {
    // load from JSON file
    $json = '';
    if (filter_input(INPUT_POST, 'import_type') == 'text_file'
        && isset($_FILES['import_file'])
        && $_FILES['import_file']['error'] == UPLOAD_ERR_OK
        && is_uploaded_file($_FILES['import_file']['tmp_name'])
    ) {
        // read JSON from uploaded file
        $open_basedir = @ini_get('open_basedir');
        $file_to_unlink = '';
        $import_file = $_FILES['import_file']['tmp_name'];

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory
        if (!empty($open_basedir)) {
            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : 'tmp/');
            if (is_writable($tmp_subdir)) {
                $import_file_new = tempnam($tmp_subdir, 'prefs');
                if (move_uploaded_file($import_file, $import_file_new)) {
                    $import_file = $import_file_new;
                    $file_to_unlink = $import_file_new;
                }
            }
        }
        $json = file_get_contents($import_file);
        if ($file_to_unlink) {
            unlink($file_to_unlink);
        }
    } else {
        // read from POST value (json)
        $json = filter_input(INPUT_POST, 'json');
    }

    // hide header message
    $_SESSION['userprefs_autoload'] = true;

    $config = json_decode($json, true);
    $return_url = filter_input(INPUT_POST, 'return_url');
    if (! is_array($config)) {
        $error = __('Could not import configuration');
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
            $msg = PMA_Message::error(
                __('Configuration contains incorrect data for some fields.')
            );
            $msg->display();
            echo '<div class="config-form">';
            $form_display->displayErrors();
            echo '</div>';
            echo '<form action="prefs_manage.php" method="post">';
            echo PMA_URL_getHiddenInputs() . "\n";
            echo '<input type="hidden" name="json" value="'
                . htmlspecialchars($json) . '" />';
            echo '<input type="hidden" name="fix_errors" value="1" />';
            if (! empty($_POST['import_merge'])) {
                echo '<input type="hidden" name="import_merge" value="1" />';
            }
            if ($return_url) {
                echo '<input type="hidden" name="return_url" value="'
                    . htmlspecialchars($return_url) . '" />';
            }
            echo '<p>';
            echo __('Do you want to import remaining settings?');
            echo '</p>';
            echo '<input type="submit" name="submit_import" value="'
                . __('Yes') . '" />';
            echo '<input type="submit" name="submit_ignore" value="'
                . __('No') . '" />';
            echo '</form>';
            exit;
        }

        // check for ThemeDefault and fontsize
        $params = array();
        if (isset($config['ThemeDefault'])
            && $_SESSION['PMA_Theme_Manager']->theme->getId() != $config['ThemeDefault']
            && $_SESSION['PMA_Theme_Manager']->checkTheme($config['ThemeDefault'])
        ) {
            $_SESSION['PMA_Theme_Manager']->setActiveTheme($config['ThemeDefault']);
            $_SESSION['PMA_Theme_Manager']->setThemeCookie();
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
                $query = explode('&', parse_url($return_url, PHP_URL_QUERY));
                $return_url = parse_url($return_url, PHP_URL_PATH);

                /** @var PMA_String $pmaString */
                $pmaString = $GLOBALS['PMA_String'];

                foreach ($query as $q) {
                    $pos = /*overload*/mb_strpos($q, '=');
                    $k = /*overload*/mb_substr($q, 0, $pos);
                    if ($k == 'token') {
                        continue;
                    }
                    $params[$k] = /*overload*/mb_substr($q, $pos+1);
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

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('config.js');

require 'libraries/user_preferences.inc.php';
if ($error) {
    if (!$error instanceof PMA_Message) {
        $error = PMA_Message::error($error);
    }
    $error->display();
}
?>
<script type="text/javascript">
<?php
PMA_printJsValue("PMA_messages['strSavedOn']", __('Saved on: @DATE@'));
?>
</script>
<div id="maincontainer">
    <div id="main_pane_left">
        <div class="group">
<?php
echo '<h2>' . __('Import') . '</h2>'
    . '<form class="group-cnt prefs-form disableAjax" name="prefs_import"'
    . ' action="prefs_manage.php" method="post" enctype="multipart/form-data">'
    . PMA_Util::generateHiddenMaxFileSize($GLOBALS['max_upload_size'])
    . PMA_URL_getHiddenInputs()
    . '<input type="hidden" name="json" value="" />'
    . '<input type="radio" id="import_text_file" name="import_type"'
    . ' value="text_file" checked="checked" />'
    . '<label for="import_text_file">' . __('Import from file') . '</label>'
    . '<div id="opts_import_text_file" class="prefsmanage_opts">'
    . '<label for="input_import_file">' . __('Browse your computer:') . '</label>'
    . '<input type="file" name="import_file" id="input_import_file" />'
    . '</div>'
    . '<input type="radio" id="import_local_storage" name="import_type"'
    . ' value="local_storage" disabled="disabled" />'
    . '<label for="import_local_storage">'
    . __('Import from browser\'s storage') . '</label>'
    . '<div id="opts_import_local_storage" class="prefsmanage_opts disabled">'
    . '<div class="localStorage-supported">'
    . __('Settings will be imported from your browser\'s local storage.')
    . '<br />'
    . '<div class="localStorage-exists">'
    . __('Saved on: @DATE@')
    . '</div>'
    . '<div class="localStorage-empty">';
PMA_Message::notice(__('You have no saved settings!'))->display();
echo  '</div>'
    . '</div>'
    . '<div class="localStorage-unsupported">';
PMA_Message::notice(
    __('This feature is not supported by your web browser')
)->display();
echo '</div>'
    . '</div>'
    . '<input type="checkbox" id="import_merge" name="import_merge" />'
    . '<label for="import_merge">'
    . __('Merge with current configuration') . '</label>'
    . '<br /><br />'
    . '<input type="submit" name="submit_import" value="'
    . __('Go') . '" />'
    . '</form>'
    . '</div>';
if (file_exists('setup/index.php')) {
            // show only if setup script is available, allows to disable this message
            // by simply removing setup directory
            ?>
            <div class="group">
            <h2><?php echo __('More settings') ?></h2>
            <div class="group-cnt">
                <?php
                echo sprintf(__('You can set more settings by modifying config.inc.php, eg. by using %sSetup script%s.'), '<a href="setup/index.php" target="_blank">', '</a>');
                echo PMA_Util::showDocu('setup', 'setup-script');
                ?>
            </div>
            </div>
        <?php
}
        ?>
    </div>
    <div id="main_pane_right">
        <div class="group">
            <h2><?php echo __('Export') ?></h2>
            <div class="click-hide-message group-cnt" style="display:none">
                <?php
PMA_Message::rawSuccess(
    __('Configuration has been saved.')
)->display();
echo '</div>'
    . '<form class="group-cnt prefs-form disableAjax" name="prefs_export"'
    . ' action="prefs_manage.php" method="post">'
    . PMA_URL_getHiddenInputs()
    . '<div style="padding-bottom:0.5em">'
    . '<input type="radio" id="export_text_file" name="export_type"'
    . ' value="text_file" checked="checked" />'
    . '<label for="export_text_file">' . __('Save as file') . '</label>'
    . '<br />'
    . '<input type="radio" id="export_local_storage" name="export_type"'
    . ' value="local_storage" disabled="disabled" />'
    . '<label for="export_local_storage">'
    .  __('Save to browser\'s storage') . '</label>'
    . '</div>'
    . '<div id="opts_export_local_storage" class="prefsmanage_opts disabled">'
    . '<span class="localStorage-supported">'
    . __('Settings will be saved in your browser\'s local storage.')
    . '<div class="localStorage-exists">'
    . '<b>' . __('Existing settings will be overwritten!') . '</b>'
    . '</div>'
    . '</span>'
    . '<div class="localStorage-unsupported">';
PMA_Message::notice(
    __('This feature is not supported by your web browser')
)->display();
?>
                    </div>
                </div>
                <br />
<?php
echo '<input type="submit" name="submit_export" value="' . __('Go') . '" />';
?>
            </form>
        </div>
        <div class="group">
<?php
echo '<h2>' . __('Reset') . '</h2>'
    . '<form class="group-cnt prefs-form disableAjax" name="prefs_reset"'
    . ' action="prefs_manage.php" method="post">'
    . PMA_URL_getHiddenInputs()
    . __('You can reset all your settings and restore them to default values.')
    . '<br /><br />'
    . '<input type="submit" name="submit_clear" value="'
    . __('Reset') . '" />'
    . '</form>';
?>
        </div>
    </div>
    <br class="clearfloat" />
</div>
