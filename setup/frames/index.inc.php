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
require_once './libraries/config/ServerConfigChecks.class.php';
require_once './libraries/VersionInformation.php';
require_once './setup/lib/index.lib.php';

// prepare unfiltered language list
$all_languages = PMA_langList();
uasort($all_languages, 'PMA_languageCmp');

/** @var ConfigFile $cf */
$cf = $GLOBALS['ConfigFile'];
$separator = PMA_URL_getArgSeparator('html');

// message handling
PMA_messagesBegin();

//
// Check phpMyAdmin version
//
if (isset($_GET['version_check'])) {
    PMA_versionCheck();
}

//
// Perform various security, compatibility and consistency checks
//
$configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
$configChecker->performConfigChecks();

//
// Check whether we can read/write configuration
//
$config_readable = false;
$config_writable = false;
$config_exists = false;
PMA_checkConfigRw($config_readable, $config_writable, $config_exists);
if (!$config_writable || !$config_readable) {
    PMA_messagesSet(
        'error', 'config_rw', __('Cannot load or save configuration'),
        PMA_sanitize(
            __(
                'Please create web server writable folder [em]config[/em] in '
                . 'phpMyAdmin top level directory as described in '
                . '[doc@setup_script]documentation[/doc]. Otherwise you will be '
                . 'only able to download or display it.'
            )
        )
    );
}
//
// Check https connection
//
$is_https = !empty($_SERVER['HTTPS'])
    && /*overload*/mb_strtolower($_SERVER['HTTPS']) == 'on';
if (!$is_https) {
    $text = __(
        'You are not using a secure connection; all data (including potentially '
        . 'sensitive information, like passwords) is transferred unencrypted!'
    );

    if (!empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])) {
        $link = htmlspecialchars(
            'https://' .  $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        );
        $text .= ' ';
        $text .= PMA_sanitize(
            sprintf(
                __(
                    'If your server is also configured to accept HTTPS requests '
                    . 'follow [a@%s]this link[/a] to use a secure connection.'
                ),
                $link
            )
        );
    }
    PMA_messagesSet('notice', 'no_https', __('Insecure connection'), $text);
}

echo '<form id="select_lang" method="post" action="'
    . htmlspecialchars($_SERVER['REQUEST_URI']) . '">';
echo PMA_URL_getHiddenInputs();
echo '<bdo lang="en" dir="ltr"><label for="lang">';
echo __('Language') . (__('Language') != 'Language' ? ' - Language' : '');
echo '</label></bdo><br />';
echo '<select id="lang" name="lang" class="autosubmit" lang="en" dir="ltr">';

// create language list
$lang_list = array();
foreach ($all_languages as $each_lang_key => $each_lang) {
    $lang_name = PMA_languageName($each_lang);
    //Is current one active?
    $selected = ($GLOBALS['lang'] == $each_lang_key) ? ' selected="selected"' : '';
    echo '<option value="' . $each_lang_key . '"' . $selected . '>' . $lang_name
        . '</option>' . "\n";
}

echo '</select>';
echo '</form>';

// Check for done action info and set notice message if present
switch ($action_done) {
case 'config_saved':
    /* Use uniqid to display this message every time configuration is saved */
    PMA_messagesSet(
        'notice', uniqid('config_saved'), __('Configuration saved.'),
        PMA_sanitize(
            __(
                'Configuration saved to file config/config.inc.php in phpMyAdmin '
                . 'top level directory, copy it to top level one and delete '
                . 'directory config to use it.'
            )
        )
    );
    break;
default:
    break;
}

echo '<h2>' . __('Overview') . '</h2>';

// message handling
PMA_messagesEnd();
PMA_messagesShowHtml();

echo '<a href="#" id="show_hidden_messages" style="display:none">';
echo __('Show hidden messages (#MSG_COUNT)');
echo '</a>';

echo '<fieldset class="simple"><legend>';
echo __('Servers');
echo '</legend>';

//
// Display server list
//
echo PMA_displayFormTop(
    'index.php', 'get',
    array(
        'page' => 'servers',
        'mode' => 'add'
    )
);
echo '<div class="form">';
if ($cf->getServerCount() > 0) {
    echo '<table cellspacing="0" class="datatable" style="table-layout: fixed">';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>' . __('Name') . '</th>';
    echo '<th>' . __('Authentication type') . '</th>';
    echo '<th colspan="2">DSN</th>';
    echo '</tr>';

    foreach ($cf->getServers() as $id => $server) {
        echo '<tr>';
        echo '<td>' . $id  . '</td>';
        echo '<td>' . htmlspecialchars($cf->getServerName($id)) . '</td>';
        echo '<td>'
            . htmlspecialchars($cf->getValue("Servers/$id/auth_type"))
            .  '</td>';
        echo '<td>' . htmlspecialchars($cf->getServerDSN($id)) . '</td>';
        echo '<td style="white-space: nowrap">';
        echo '<small>';
        echo '<a href="' . PMA_URL_getCommon() . $separator . 'page=servers'
            . $separator . 'mode=edit' . $separator . 'id=' . $id . '">'
            . __('Edit') . '</a>';
        echo ' | ';
        echo '<a href="' . PMA_URL_getCommon() . $separator . 'page=servers'
            . $separator . 'mode=remove' . $separator . 'id=' . $id . '">'
            . __('Delete') . '</a>';
        echo '</small>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<table width="100%">';
    echo '<tr>';
    echo '<td>';
    echo '<i>' . __('There are no configured servers') . '</i>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

echo '<table width="100%">';
echo '<tr>';
echo '<td class="lastrow" style="text-align: left">';
echo '<input type="submit" name="submit" value="' . __('New server') . '" />';
echo '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';

echo PMA_displayFormBottom();

echo '</fieldset>';

echo '<fieldset class="simple"><legend>' . __('Configuration file') . '</legend>';

//
// Display config file settings and load/save form
//
$form_display = new FormDisplay($cf);

echo PMA_displayFormTop('config.php');
echo '<table width="100%" cellspacing="0">';

// Display language list
$opts = array(
    'doc' => $form_display->getDocLink('DefaultLang'),
    'values' => array(),
    'values_escaped' => true);
foreach ($all_languages as $each_lang_key => $each_lang) {
    $lang_name = PMA_languageName($each_lang);
    $opts['values'][$each_lang_key] = $lang_name;
}
echo PMA_displayInput(
    'DefaultLang', __('Default language'), 'select',
    $cf->getValue('DefaultLang'), '', true, $opts
);

// Display server list
$opts = array(
    'doc' => $form_display->getDocLink('ServerDefault'),
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
echo PMA_displayInput(
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
echo PMA_displayInput(
    'eol', __('End of line'), 'select',
    $eol, '', true, $opts
);

echo '<tr>';
echo '<td colspan="2" class="lastrow" style="text-align: left">';
echo '<input type="submit" name="submit_display" value="' . __('Display') . '" />';
echo '<input type="submit" name="submit_download" value="' . __('Download') . '" />';
echo '&nbsp; &nbsp;';

echo '<input type="submit" name="submit_save" value="' . __('Save') . '"';
if (!$config_writable) {
    echo ' disabled="disabled"';
}
echo '/>';

echo '<input type="submit" name="submit_load" value="' . __('Load') . '"';
if (!$config_exists) {
    echo ' disabled="disabled"';
}
echo '/>';

echo '<input type="submit" name="submit_delete" value="' . __('Delete') . '"';
if (!$config_exists || !$config_writable) {
    echo ' disabled="disabled"';
}
echo '/>';

echo '&nbsp; &nbsp;';
echo '<input type="submit" name="submit_clear" value="' . __('Clear')
    . '" class="red" />';
echo '</td>';
echo '</tr>';
echo '</table>';

echo PMA_displayFormBottom();

echo '</fieldset>';
echo '<div id="footer">';
echo '<a href="https://www.phpmyadmin.net/">' . __('phpMyAdmin homepage') . '</a>';
echo '<a href="https://www.phpmyadmin.net/donate/">'
    .  __('Donate') . '</a>';
echo '<a href="' .  PMA_URL_getCommon() . $separator . 'version_check=1">'
    . __('Check for latest version') . '</a>';
echo '</div>';
