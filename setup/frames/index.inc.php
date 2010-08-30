<?php
/**
 * Overview (main page)
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './libraries/display_select_lang.lib.php';
require_once './setup/lib/FormDisplay.class.php';
require_once './setup/lib/index.lib.php';

// prepare unfiltered language list
$all_languages = PMA_langList();
uasort($all_languages, 'PMA_language_cmp');

$cf = ConfigFile::getInstance();
$separator = PMA_get_arg_separator('html');

// message handling
messages_begin();

//
// Check phpMyAdmin version
//
if (isset($_GET['version_check'])) {
    PMA_version_check();
}

//
// Perform various security, compatibility and consistency checks
//
perform_config_checks();

//
// Check whether we can read/write configuration
//
$config_readable = false;
$config_writable = false;
$config_exists = false;
check_config_rw($config_readable, $config_writable, $config_exists);
if (!$config_writable || !$config_readable) {
    messages_set('error', 'config_rw', 'CannotLoadConfig', PMA_lang('CannotLoadConfigMsg'));
}
//
// Check https connection
//
$is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
if (!$is_https) {
    $text = $GLOBALS['strSetupInsecureConnectionMsg1'];
    if (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])) {
        $text .= ' ' . PMA_lang('InsecureConnectionMsg2',
            'https://' . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
    }
    messages_set('warning', 'no_https', 'InsecureConnection', $text);
}
?>

<form id="select_lang" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <?php echo PMA_generate_common_hidden_inputs() ?>
    <bdo xml:lang="en" dir="ltr"><label for="lang">
    <?php echo $GLOBALS['strLanguage'] . ($GLOBALS['strLanguage'] != 'Language' ? ' - Language' : '') ?>
    </label></bdo><br />
    <select id="lang" name="lang" onchange="this.form.submit();" xml:lang="en" dir="ltr">
    <?php
    // create language list
    $lang_list = array();
    foreach ($all_languages as $each_lang_key => $each_lang) {
        if (!file_exists($GLOBALS['lang_path'] . $each_lang[1] . '.inc.php')) {
            continue;
        }

        $lang_name = ucfirst(substr(strrchr($each_lang[0], '|'), 1));
        // Include native name if non empty
        if (!empty($each_lang[3])) {
            $lang_name = $each_lang[3] . ' - ' . $lang_name;
        }

        //Is current one active?
        $selected = ($GLOBALS['lang'] == $each_lang_key) ? ' selected="selected"' : '';
        echo '<option value="' . $each_lang_key . '"' . $selected . '>' . $lang_name
            . '</option>' . "\n";
    }
    ?>
    </select>
</form>

<h2><?php echo $GLOBALS['strSetupOverview'] ?></h2>

<?php
// message handling
messages_end();
messages_show_html();
?>

<a href="#" id="show_hidden_messages" style="display:none"><?php echo $GLOBALS['strSetupShowHiddenMessages'] ?></a>

<h3><?php echo $GLOBALS['strServers'] ?></h3>
<?php
//
// Display server list
//
display_form_top('index.php', 'get', array(
    'page' => 'servers',
    'mode' => 'add'
));
?>
<div class="form">
<?php if ($cf->getServerCount() > 0): ?>
<table cellspacing="0" class="datatable" style="table-layout: fixed">
<tr>
    <th>#</th>
    <th><?php echo $GLOBALS['strName'] ?></th>
    <th>Authentication type</th>
    <th colspan="2">DSN</th>
</tr>
<?php foreach ($_SESSION['ConfigFile']['Servers'] as $id => $server): ?>
<tr>
    <td><?php echo $id ?></td>
    <td><?php echo htmlspecialchars($cf->getServerName($id)) ?></td>
    <td><?php echo htmlspecialchars($cf->getValue("Servers/$id/auth_type")) ?></td>
    <td><?php echo htmlspecialchars($cf->getServerDSN($id)) ?></td>
    <td style="white-space: nowrap">
        <small>
        <a href="<?php echo "?page=servers{$separator}mode=edit{$separator}id=$id" ?>"><?php echo $GLOBALS['strEdit'] ?></a>
        | <a href="<?php echo "?page=servers{$separator}mode=remove{$separator}id=$id" ?>"><?php echo $GLOBALS['strDelete'] ?></a>
        </small>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<table width="100%">
<tr>
    <td>
        <i><?php echo $GLOBALS['strSetupNoServers'] ?></i>
    </td>
</tr>
</table>
<?php endif; ?>
<table width="100%">
<tr>
    <td class="lastrow" style="text-align: left">
        <input type="submit" name="submit" value="<?php echo $GLOBALS['strSetupNewServer'] ?>" />
    </td>
</tr>
</table>
</div>
<?php
display_form_bottom();
?>

<h3><?php echo $GLOBALS['strSetupConfigurationFile'] ?></h3>
<?php
//
// Display config file settings and load/save form
//
$form_display = new FormDisplay();

display_form_top('config.php');
display_fieldset_top('', '', null, array('class' => 'simple'));

// Display language list
$opts = array(
    'doc' => $form_display->getDocLink('DefaultLang'),
    'wiki' => $form_display->getWikiLink('DefaultLang'),
    'values' => array(),
    'values_escaped' => true);
foreach ($all_languages as $each_lang_key => $each_lang) {
    if (!file_exists($GLOBALS['lang_path'] . $each_lang[1] . '.inc.php')) {
        continue;
    }
    $lang_name = ucfirst(substr(strrchr($each_lang[0], '|'), 1));
    // Include native name if non empty
    if (!empty($each_lang[3])) {
        $lang_name = $each_lang[3] . ' - ' . $lang_name;
    }
    $opts['values'][$each_lang_key] = $lang_name;
}
display_input('DefaultLang', $GLOBALS['strSetupDefaultLanguage'], '', 'select',
    $cf->getValue('DefaultLang'), true, $opts);

// Display server list
$opts = array(
    'doc' => $form_display->getDocLink('ServerDefault'),
    'wiki' => $form_display->getWikiLink('ServerDefault'),
    'values' => array(),
    'values_disabled' => array());
if ($cf->getServerCount() > 0) {
    $opts['values']['0'] = $GLOBALS['strSetupLetUserChoose'];
    $opts['values']['-'] = '------------------------------';
    if ($cf->getServerCount() == 1) {
        $opts['values_disabled'][] = '0';
    }
    $opts['values_disabled'][] = '-';

    foreach ($_SESSION['ConfigFile']['Servers'] as $id => $server) {
        $opts['values'][(string)$id] = $cf->getServerName($id) . " [$id]";
    }
} else {
    $opts['values']['1'] = $GLOBALS['strSetupOptionNone'];
    $opts['values_escaped'] = true;
}
display_input('ServerDefault', $GLOBALS['strSetupDefaultServer'], '', 'select',
    $cf->getValue('ServerDefault'), true, $opts);

// Display EOL list
$opts = array(
    'values' => array(
        'unix' => 'UNIX / Linux (\n)',
        'win' => 'Windows (\r\n)'),
    'values_escaped' => true);
$eol = PMA_ifSetOr($_SESSION['eol'], (PMA_IS_WINDOWS ? 'win' : 'unix'));
display_input('eol', $GLOBALS['strSetupEndOfLine'], '', 'select',
    $eol, true, $opts);
?>
<tr>
    <td colspan="2" class="lastrow" style="text-align: left">
        <input type="submit" name="submit_display" value="<?php echo $GLOBALS['strSetupDisplay'] ?>" />
        <input type="submit" name="submit_download" value="<?php echo $GLOBALS['strSetupDownload'] ?>" />
        &nbsp; &nbsp;
        <input type="submit" name="submit_save" value="<?php echo $GLOBALS['strSave'] ?>"<?php if (!$config_writable) echo ' disabled="disabled"' ?> />
        <input type="submit" name="submit_load" value="<?php echo $GLOBALS['strSetupLoad'] ?>"<?php if (!$config_exists) echo ' disabled="disabled"' ?> />
        <input type="submit" name="submit_delete" value="<?php echo $GLOBALS['strDelete'] ?>"<?php if (!$config_exists || !$config_writable) echo ' disabled="disabled"' ?> />
        &nbsp; &nbsp;
        <input type="submit" name="submit_clear" value="<?php echo $GLOBALS['strSetupClear'] ?>" class="red" />
    </td>
</tr>
<?php
display_fieldset_bottom_simple();
display_form_bottom();
?>
<div id="footer">
    <a href="http://phpmyadmin.net"><?php echo $GLOBALS['strSetupHomepageLink'] ?></a>
    <a href="http://sourceforge.net/donate/index.php?group_id=23067"><?php echo $GLOBALS['strSetupDonateLink'] ?></a>
    <a href="?version_check=1<?php echo "{$separator}token=" . $_SESSION[' PMA_token '] ?>"><?php echo $GLOBALS['strSetupVersionCheckLink'] ?></a>
</div>
