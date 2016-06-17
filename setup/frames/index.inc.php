<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Overview (main page)
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './libraries/display_select_lang.lib.php';
require_once './libraries/config/FormDisplay.class.php';
require_once './setup/lib/index.lib.php';

// prepare unfiltered language list
$all_languages = PMA_langList();
uasort($all_languages, 'PMA_languageCmp');

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
    messages_set(
        'error', 'config_rw', __('Cannot load or save configuration'),
        PMA_lang(__('Please create web server writable folder [em]config[/em] in phpMyAdmin top level directory as described in [doc@setup_script]documentation[/doc]. Otherwise you will be only able to download or display it.'))
    );
}
//
// Check https connection
//
$is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
if (!$is_https) {
    $text = __('You are not using a secure connection; all data (including potentially sensitive information, like passwords) is transferred unencrypted!');

    $text .= ' <a href="#" onclick="window.location.href = \'https:\' + window.location.href.substring(window.location.protocol.length);">';

    // Temporary workaround to use tranlated message in older releases
    $text .= str_replace(
        array('[a@%s]', '[/a]'),
        array('', ''),
        __('If your server is also configured to accept HTTPS requests follow [a@%s]this link[/a] to use a secure connection.')
    );
    $text .= '</a>';
    messages_set('notice', 'no_https', __('Insecure connection'), $text);
}
?>

<form id="select_lang" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <?php echo PMA_generate_common_hidden_inputs() ?>
    <bdo lang="en" dir="ltr"><label for="lang">
    <?php echo __('Language') . (__('Language') != 'Language' ? ' - Language' : '') ?>
    </label></bdo><br />
    <select id="lang" name="lang" class="autosubmit" lang="en" dir="ltr">
    <?php
    // create language list
    $lang_list = array();
    foreach ($all_languages as $each_lang_key => $each_lang) {
        $lang_name = PMA_langName($each_lang);
        //Is current one active?
        $selected = ($GLOBALS['lang'] == $each_lang_key) ? ' selected="selected"' : '';
        echo '<option value="' . $each_lang_key . '"' . $selected . '>' . $lang_name
            . '</option>' . "\n";
    }
    ?>
    </select>
</form>

<?php
// Check for done action info and set notice message if present
switch ($action_done) {
case 'config_saved':
    /* Use uniqid to display this message every time configuration is saved */
    messages_set(
        'notice', uniqid('config_saved'), __('Configuration saved.'),
        PMA_lang(__('Configuration saved to file config/config.inc.php in phpMyAdmin top level directory, copy it to top level one and delete directory config to use it.'))
    );
    break;
case 'config_not_saved':
    /* Use uniqid to display this message every time configuration is saved */
    PMA_messagesSet(
        'notice', uniqid('config_not_saved'), __('Configuration not saved!'),
        PMA_sanitize(
            __(
                'Please create web server writable folder [em]config[/em] in '
                . 'phpMyAdmin top level directory as described in '
                . '[doc@setup_script]documentation[/doc]. Otherwise you will be '
                . 'only able to download or display it.'
            )
        )
    );
    break;
default:
    break;
}
?>

<h2><?php echo __('Overview') ?></h2>

<?php
// message handling
messages_end();
messages_show_html();
?>

<a href="#" id="show_hidden_messages" style="display:none"><?php echo __('Show hidden messages (#MSG_COUNT)') ?></a>

<fieldset class="simple"><legend><?php echo __('Servers') ?></legend>
<?php
//
// Display server list
//
PMA_displayFormTop(
    'index.php', 'get',
    array(
        'page' => 'servers',
        'mode' => 'add'
    )
);
?>
<div class="form">
<?php if ($cf->getServerCount() > 0) { ?>
<table cellspacing="0" class="datatable" style="table-layout: fixed">
<tr>
    <th>#</th>
    <th><?php echo __('Name') ?></th>
    <th><?php echo __('Authentication type') ?></th>
    <th colspan="2">DSN</th>
</tr>
<?php foreach ($cf->getServers() as $id => $server) { ?>
<tr>
    <td><?php echo $id ?></td>
    <td><?php echo htmlspecialchars($cf->getServerName($id)) ?></td>
    <td><?php echo htmlspecialchars($cf->getValue("Servers/$id/auth_type")) ?></td>
    <td><?php echo htmlspecialchars($cf->getServerDSN($id)) ?></td>
    <td style="white-space: nowrap">
        <small>
        <a href="<?php echo "?" . PMA_generate_common_url() . $separator . "page=servers{$separator}mode=edit{$separator}id=$id" ?>"><?php echo __('Edit') ?></a>
        | <a href="<?php echo "?" . PMA_generate_common_url() . $separator . "page=servers{$separator}mode=remove{$separator}id=$id" ?>"><?php echo __('Delete') ?></a>
        </small>
    </td>
</tr>
<?php } ?>
</table>
<?php } else { ?>
<table width="100%">
<tr>
    <td>
        <i><?php echo __('There are no configured servers') ?></i>
    </td>
</tr>
</table>
<?php } ?>
<table width="100%">
<tr>
    <td class="lastrow" style="text-align: left">
        <input type="submit" name="submit" value="<?php echo __('New server') ?>" />
    </td>
</tr>
</table>
</div>
<?php
PMA_displayFormBottom();
?>
</fieldset>

<fieldset class="simple"><legend><?php echo __('Configuration file') ?></legend>
<?php
//
// Display config file settings and load/save form
//
$form_display = new FormDisplay();

PMA_displayFormTop('config.php');
?>
<table width="100%" cellspacing="0">
<?php

// Display language list
$opts = array(
    'doc' => $form_display->getDocLink('DefaultLang'),
    'wiki' => $form_display->getWikiLink('DefaultLang'),
    'values' => array(),
    'values_escaped' => true);
foreach ($all_languages as $each_lang_key => $each_lang) {
    $lang_name = PMA_langName($each_lang);
    $opts['values'][$each_lang_key] = $lang_name;
}
PMA_displayInput(
    'DefaultLang', __('Default language'), 'select',
    $cf->getValue('DefaultLang'), '', true, $opts
);

// Display server list
$opts = array(
    'doc' => $form_display->getDocLink('ServerDefault'),
    'wiki' => $form_display->getWikiLink('ServerDefault'),
    'values' => array(),
    'values_disabled' => array());
if ($cf->getServerCount() > 0) {
    $opts['values']['0'] = __('let the user choose');
    $opts['values']['-'] = '------------------------------';
    if ($cf->getServerCount() == 1) {
        $opts['values_disabled'][] = '0';
    }
    $opts['values_disabled'][] = '-';

    foreach ($cf->getServers() as $id => $server) {
        $opts['values'][(string)$id] = $cf->getServerName($id) . " [$id]";
    }
} else {
    $opts['values']['1'] = __('- none -');
    $opts['values_escaped'] = true;
}
PMA_displayInput(
    'ServerDefault', __('Default server'), 'select',
    $cf->getValue('ServerDefault'), '', true, $opts
);

// Display EOL list
$opts = array(
    'values' => array(
        'unix' => 'UNIX / Linux (\n)',
        'win' => 'Windows (\r\n)'),
    'values_escaped' => true);
$eol = PMA_ifSetOr($_SESSION['eol'], (PMA_IS_WINDOWS ? 'win' : 'unix'));
PMA_displayInput(
    'eol', __('End of line'), 'select',
    $eol, '', true, $opts
);
?>
<tr>
    <td colspan="2" class="lastrow" style="text-align: left">
        <input type="submit" name="submit_display" value="<?php echo __('Display') ?>" />
        <input type="submit" name="submit_download" value="<?php echo __('Download') ?>" />
        &nbsp; &nbsp;
        <input type="submit" name="submit_save" value="<?php echo __('Save') ?>"<?php
if (!$config_writable) {
    echo ' disabled="disabled"';
} ?> />
        <input type="submit" name="submit_load" value="<?php echo __('Load') ?>"<?php
if (!$config_exists) {
    echo ' disabled="disabled"';
} ?> />
        <input type="submit" name="submit_delete" value="<?php echo __('Delete')
        ?>"<?php
if (!$config_exists || !$config_writable) {
    echo ' disabled="disabled"';
} ?> />
        &nbsp; &nbsp;
        <input type="submit" name="submit_clear" value="<?php echo __('Clear')
        ?>" class="red" />
    </td>
</tr>
</table>
<?php
PMA_displayFormBottom();
?>
</fieldset>
<div id="footer">
    <a href="../url.php?url=https://www.phpmyadmin.net/"><?php echo __('phpMyAdmin homepage') ?></a>
    <a href="../url.php?url=https://www.phpmyadmin.net/donate/"><?php
    echo __('Donate') ?></a>
    <a href="?version_check=1<?php
    echo "{$separator}token="
    . $_SESSION[' PMA_token '] ?>"><?php echo __('Check for latest version') ?></a>
</div>
