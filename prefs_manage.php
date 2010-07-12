<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';
require_once './libraries/user_preferences.lib.php';
require_once './libraries/config/config_functions.lib.php';
require_once './libraries/config/messages.inc.php';
require_once './libraries/config/ConfigFile.class.php';
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require './libraries/config/user_preferences.forms.php';

$error = '';
if (isset($_POST['submit_export']) && filter_input(INPUT_POST, 'export_type') == 'text_file') {
    // export to JSON file
    $filename = 'phpMyAdmin-config-' . urlencode(PMA_getenv('HTTP_HOST')) . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: ' . date(DATE_RFC1123));
    $settings = PMA_load_userprefs();
    echo json_encode($settings['config_data']);
    return;
} else if (isset($_POST['submit_get_json'])) {
    $settings = PMA_load_userprefs();
    header('Content-Type: application/json');
    echo json_encode(array(
        'prefs' => json_encode($settings['config_data']),
        'mtime' => $settings['mtime']));
    return;
} else if (isset($_POST['submit_import'])) {
    // load from JSON file
    $json = '';
    if (filter_input(INPUT_POST, 'import_type') == 'text_file'
            && isset($_FILES['import_file'])
            && $_FILES['import_file']['error'] == UPLOAD_ERR_OK
            && is_uploaded_file($_FILES['import_file']['tmp_name'])) {
        // read JSON from uploaded file
        $open_basedir = @ini_get('open_basedir');
        $file_to_unlink = '';
        $import_file = $_FILES['import_file']['tmp_name'];

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory
        if (!empty($open_basedir)) {
            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');
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
    $config = json_decode($json, true);
    if (!is_array($config)) {
        $error = __('Could not import configuration');
    } else {
        // sanitize input values: treat them as though they came from HTTP POST request
        $form_display = new FormDisplay();
        foreach ($forms as $formset_id => $formset) {
            foreach ($formset as $form_name => $form) {
                $form_display->registerForm($formset_id . ': ' . $form_name, $form);
            }
        }
        $cf = ConfigFile::getInstance();
        if (empty($_POST['import_merge'])) {
            $cf->resetConfigData();
        }
        $_POST_bak = $_POST;
        foreach ($cf->getFlatDefaultConfig() as $k => $v) {
            $_POST[str_replace('/', '-', $k)] = $v;
        }
        $_POST = array_merge($_POST, $config);
        $all_ok = $form_display->process(true, false);
        $_POST = $_POST_bak;

        if (!$all_ok) {
            // todo: ask about saving that what can be saved
            $form_display->displayErrors();
            die('errors');
        }

        // save settings
        $old_settings = PMA_load_userprefs();
        $result = PMA_save_userprefs($cf->getConfigArray());
        if ($result === true) {
            PMA_userprefs_redirect($forms, $old_settings, 'prefs_manage.php');
            exit;
        } else {
            $error = $result;
        }
    }
} else if (isset($_POST['submit_clear'])) {
    $old_settings = PMA_load_userprefs();
    $result = PMA_save_userprefs(array());
    ConfigFile::getInstance()->resetConfigData();
    if ($result === true) {
        PMA_userprefs_redirect($forms, $old_settings, 'prefs_manage.php');
        exit;
    } else {
        $error = $result;
    }
    exit;
}

$GLOBALS['js_include'][] = 'config.js';
require_once './libraries/header.inc.php';
require_once './libraries/user_preferences.inc.php';
?>
<script type="text/javascript">
<?php
PMA_printJsValue("PMA_messages['strSavedOn']", __('Saved on: __DATE__'));
?>
</script>
<div id="maincontainer">
    <div id="main_pane_left">
        <div class="group">
            <h2><?php echo __('Import') ?></h2>
            <form class="group-cnt prefs-form" name="prefs_import" action="prefs_manage.php" method="post" enctype="multipart/form-data">
                <?php
                echo PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";
                echo PMA_generate_common_hidden_inputs() . "\n";
                ?>
                <input type="hidden" name="json" value="" />
                <div style="padding-bottom:0.5em">
                    <input type="radio" id="import_text_file" name="import_type" value="text_file" checked="checked" />
                    <label for="import_text_file"><?php echo __('Import from text file') ?></label>
                    <br />
                    <input type="radio" id="import_local_storage" name="import_type" value="local_storage" disabled="disabled" />
                    <label for="import_local_storage"><?php echo __('Import from browser\'s storage') ?></label>
                </div>
                <div id="opts_import_text_file">
                    <label for="input_import_file"><?php echo __('Location of the text file'); ?></label>
                    <input style="margin: 5px" type="file" name="import_file" id="input_import_file" />
                </div>
                <div id="opts_import_local_storage" style="display:none">
                    <span class="localStorage-supported">
                        <?php echo __('Settings will be imported from your browser\'s local storage.') ?>
                        <span class="localStorage-exists">
                            <?php echo __('Saved on: __DATE__') ?>
                        </span>
                        <span class="localStorage-empty">
                            <?php  PMA_Message::notice(__('You have no saved settings!'))->display() ?>
                        </span>
                    </span>
                    <span class="localStorage-unsupported">
                        <?php PMA_Message::notice(__('This feature is not supported by your web browser'))->display() ?>
                    </span>
                </div>
                <input type="checkbox" id="import_merge" name="import_merge" />
                <label for="import_merge"><?php echo __('Merge with current configuration') ?></label>
                <br /><br />
                <input type="submit" name="submit_import" value="<?php echo __('Go'); ?>" />
            </form>
        </div>
    </div>
    <div id="main_pane_right">
        <div class="group">
            <h2><?php echo __('Export') ?></h2>
            <div class="click-hide-message group-cnt" style="display:none">
                <?php
                $message = PMA_Message::rawSuccess(__('Configuration has been saved'));
                $message->display();
                ?>
            </div>
            <form class="group-cnt prefs-form" name="prefs_export" action="prefs_manage.php" method="post">
            <?php echo PMA_generate_common_hidden_inputs() . "\n" ?>
                <div style="padding-bottom:0.5em">
                    <input type="radio" id="export_text_file" name="export_type" value="text_file" checked="checked" />
                    <label for="export_text_file"><?php echo __('Save as file') ?></label>
                    <br />
                    <input type="radio" id="export_local_storage" name="export_type" value="local_storage" disabled="disabled" />
                    <label for="export_local_storage"><?php echo __('Save to browser\'s storage') ?></label>
                </div>
                <div id="opts_export_local_storage" style="display:none">
                    <span class="localStorage-supported">
                        <?php echo __('Settings will be saved in your browser\'s local storage.') ?>
                        <span class="localStorage-exists">
                            <b><?php PMA_Message::notice(__('Existing settings will be overridden!'))->display() ?></b>
                        </span>
                    </span>
                    <span class="localStorage-unsupported">
                        <?php PMA_Message::notice(__('This feature is not supported by your web browser'))->display() ?>
                    </span>
                </div>
                <br />
                <input type="submit" name="submit_export" value="<?php echo __('Go'); ?>" />
            </form>
        </div>
        <div class="group">
            <h2><?php echo __('Reset') ?></h2>
            <form class="group-cnt prefs-form" name="prefs_reset" action="prefs_manage.php" method="post">
            <?php echo PMA_generate_common_hidden_inputs() . "\n" ?>
                <?php echo __('You can reset all your settings and restore them to default values') ?>
                <br /><br />
                <input type="submit" name="submit_clear" value="<?php echo __('Reset') ?>" />
            </form>

        </div>
    </div>
    <br class="clearfloat" />
</div>
<?php
/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>