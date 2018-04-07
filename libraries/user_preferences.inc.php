<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common header for user preferences pages
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\TwoFactor;

if (!defined('PHPMYADMIN')) {
    exit;
}
// build user preferences menu

$form_param = isset($_GET['form']) ? $_GET['form'] : null;
$tabs_icons = array(
    'Features'    => 'b_tblops',
    'Sql'         => 'b_sql',
    'Navi'        => 'b_select',
    'Main'        => 'b_props',
    'Import'      => 'b_import',
    'Export'      => 'b_export');

$content = PhpMyAdmin\Util::getHtmlTab(
    array(
        'link' => 'prefs_manage.php',
        'text' => __('Manage your settings')
    )
) . "\n";
/* Second authentication factor */
$content .= PhpMyAdmin\Util::getHtmlTab(
    array(
        'link' => 'prefs_twofactor.php',
        'text' => __('Two-factor authentication')
    )
) . "\n";
$script_name = basename($GLOBALS['PMA_PHP_SELF']);
foreach (UserFormList::getAll() as $formset) {
    $formset_class = UserFormList::get($formset);
    $tab = array(
        'link' => 'prefs_forms.php',
        'text' => $formset_class::getName(),
        'icon' => $tabs_icons[$formset],
        'active' => ($script_name == 'prefs_forms.php' && $formset == $form_param));
    $content .= PhpMyAdmin\Util::getHtmlTab($tab, array('form' => $formset))
        . "\n";
}
echo PhpMyAdmin\Template::get('list/unordered')->render(
    array(
        'id' => 'topmenu2',
        'class' => 'user_prefs_tabs',
        'content' => $content,
    )
);
echo '<div class="clearfloat"></div>';

// show "configuration saved" message and reload navigation panel if needed
if (!empty($_GET['saved'])) {
    Message::rawSuccess(__('Configuration has been saved.'))->display();
}

// warn about using session storage for settings
$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();
if (! $cfgRelation['userconfigwork']) {
    $msg = __(
        'Your preferences will be saved for current session only. Storing them '
        . 'permanently requires %sphpMyAdmin configuration storage%s.'
    );
    $msg = Sanitize::sanitize(
        sprintf($msg, '[doc@linked-tables]', '[/doc]')
    );
    Message::notice($msg)->display();
}
