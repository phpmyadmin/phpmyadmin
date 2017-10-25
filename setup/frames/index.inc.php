<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Overview (main page)
 *
 * @package PhpMyAdmin-Setup
 */

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Core;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Setup\Index as SetupIndex;
use PhpMyAdmin\Url;

if (!defined('PHPMYADMIN')) {
    exit;
}

// prepare unfiltered language list
$all_languages = LanguageManager::getInstance()->sortedLanguages();

/** @var ConfigFile $cf */
$cf = $GLOBALS['ConfigFile'];

// message handling
SetupIndex::messagesBegin();

//
// Check phpMyAdmin version
//
if (isset($_GET['version_check'])) {
    SetupIndex::versionCheck();
}

//
// Perform various security, compatibility and consistency checks
//
$configChecker = new ServerConfigChecks($GLOBALS['ConfigFile']);
$configChecker->performConfigChecks();

//
// Https connection warning (check done on the client side)
//
$text = __(
    'You are not using a secure connection; all data (including potentially '
    . 'sensitive information, like passwords) is transferred unencrypted!'
);
$text .= ' <a href="#">';
$text .= __(
    'If your server is also configured to accept HTTPS requests '
    . 'follow this link to use a secure connection.'
);
$text .= '</a>';
SetupIndex::messagesSet('notice', 'no_https', __('Insecure connection'), $text);

echo '<form id="select_lang" method="post">';
echo Url::getHiddenInputs();
echo '<bdo lang="en" dir="ltr"><label for="lang">';
echo __('Language') , (__('Language') != 'Language' ? ' - Language' : '');
echo '</label></bdo><br />';
echo '<select id="lang" name="lang" class="autosubmit" lang="en" dir="ltr">';

// create language list
$lang_list = array();
foreach ($all_languages as $each_lang) {
    //Is current one active?
    $selected = $each_lang->isActive() ? ' selected="selected"' : '';
    echo '<option value="' , $each_lang->getCode() , '"' , $selected , '>' , $each_lang->getName()
        , '</option>' , "\n";
}

echo '</select>';
echo '</form>';

// Check for done action info and set notice message if present
switch ($action_done) {
case 'config_saved':
    /* Use uniqid to display this message every time configuration is saved */
    SetupIndex::messagesSet(
        'notice', uniqid('config_saved'), __('Configuration saved.'),
        Sanitize::sanitize(
            __(
                'Configuration saved to file config/config.inc.php in phpMyAdmin '
                . 'top level directory, copy it to top level one and delete '
                . 'directory config to use it.'
            )
        )
    );
    break;
case 'config_not_saved':
    /* Use uniqid to display this message every time configuration is saved */
    SetupIndex::messagesSet(
        'notice', uniqid('config_not_saved'), __('Configuration not saved!'),
        Sanitize::sanitize(
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

echo '<h2>' , __('Overview') , '</h2>';

// message handling
SetupIndex::messagesEnd();
SetupIndex::messagesShowHtml();

echo '<a href="#" id="show_hidden_messages" class="hide">';
echo __('Show hidden messages (#MSG_COUNT)');
echo '</a>';

echo '<fieldset class="simple"><legend>';
echo __('Servers');
echo '</legend>';

//
// Display server list
//
echo FormDisplayTemplate::displayFormTop(
    'index.php', 'get',
    array(
        'page' => 'servers',
        'mode' => 'add'
    )
);
echo '<div class="form">';
if ($cf->getServerCount() > 0) {
    echo '<table cellspacing="0" class="datatable">';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>' , __('Name') , '</th>';
    echo '<th>' , __('Authentication type') , '</th>';
    echo '<th colspan="2">DSN</th>';
    echo '</tr>';

    foreach ($cf->getServers() as $id => $server) {
        echo '<tr>';
        echo '<td>' , $id  , '</td>';
        echo '<td>' , htmlspecialchars($cf->getServerName($id)) , '</td>';
        echo '<td>'
            , htmlspecialchars($cf->getValue("Servers/$id/auth_type"))
            ,  '</td>';
        echo '<td>' , htmlspecialchars($cf->getServerDSN($id)) , '</td>';
        echo '<td class="nowrap">';
        echo '<small>';
        echo '<a href="' , Url::getCommon(array('page' => 'servers', 'mode' => 'edit', 'id' => $id)), '">'
            , __('Edit') , '</a>';
        echo ' | ';
        echo '<a href="' , Url::getCommon(array('page' => 'servers', 'mode' => 'remove', 'id' => $id)), '">'
            , __('Delete') , '</a>';
        echo '</small>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<table width="100%">';
    echo '<tr>';
    echo '<td>';
    echo '<i>' , __('There are no configured servers') , '</i>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

echo '<table width="100%">';
echo '<tr>';
echo '<td class="lastrow left">';
echo '<input type="submit" name="submit" value="' , __('New server') , '" />';
echo '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';

echo FormDisplayTemplate::displayFormBottom();

echo '</fieldset>';

echo '<fieldset class="simple"><legend>' , __('Configuration file') , '</legend>';

//
// Display config file settings and load/save form
//
$form_display = new FormDisplay($cf);

echo FormDisplayTemplate::displayFormTop('config.php');
echo '<table width="100%" cellspacing="0">';

// Display language list
$opts = array(
    'doc' => $form_display->getDocLink('DefaultLang'),
    'values' => array(),
    'values_escaped' => true);
foreach ($all_languages as $each_lang) {
    $opts['values'][$each_lang->getCode()] = $each_lang->getName();
}
echo FormDisplayTemplate::displayInput(
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
echo FormDisplayTemplate::displayInput(
    'ServerDefault', __('Default server'), 'select',
    $cf->getValue('ServerDefault'), '', true, $opts
);

// Display EOL list
$opts = array(
    'values' => array(
        'unix' => 'UNIX / Linux (\n)',
        'win' => 'Windows (\r\n)'),
    'values_escaped' => true);
$eol = Core::ifSetOr($_SESSION['eol'], (PMA_IS_WINDOWS ? 'win' : 'unix'));
echo FormDisplayTemplate::displayInput(
    'eol', __('End of line'), 'select',
    $eol, '', true, $opts
);

echo '<tr>';
echo '<td colspan="2" class="lastrow left">';
echo '<input type="submit" name="submit_display" value="' , __('Display') , '" />';
echo '<input type="submit" name="submit_download" value="' , __('Download') , '" />';
echo '&nbsp; &nbsp;';
echo '<input type="submit" name="submit_clear" value="' , __('Clear')
    , '" class="red" />';
echo '</td>';
echo '</tr>';
echo '</table>';

echo FormDisplayTemplate::displayFormBottom();

echo '</fieldset>';
echo '<div id="footer">';
echo '<a href="../url.php?url=https://www.phpmyadmin.net/">' , __('phpMyAdmin homepage') , '</a>';
echo '<a href="../url.php?url=https://www.phpmyadmin.net/donate/">'
    ,  __('Donate') , '</a>';
echo '<a href="' ,  Url::getCommon(array('version_check' => '1')), '">'
    , __('Check for latest version') , '</a>';
echo '</div>';
