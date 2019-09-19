<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server create and edit view
 *
 * @package PhpMyAdmin-Setup
 */

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\Setup\ServersForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Url;

if (!defined('PHPMYADMIN')) {
    exit;
}

$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
$id = Core::isValid($_GET['id'], 'numeric') ? intval($_GET['id']) : null;

/** @var ConfigFile $cf */
$cf = $GLOBALS['ConfigFile'];
$server_exists = !empty($id) && $cf->get("Servers/$id") !== null;

if ($mode == 'edit' && $server_exists) {
    $page_title = __('Edit server')
        . ' ' . $id
        . ' <small>(' . htmlspecialchars($cf->getServerDSN($id)) . ')</small>';
} elseif ($mode == 'remove' && $server_exists && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $cf->removeServer($id);
    header('Location: index.php' . Url::getCommonRaw());
    exit;
} elseif ($mode == 'revert' && $server_exists) {
    // handled by process_formset()
} else {
    $page_title = __('Add a new server');
    $id = 0;
}
if (isset($page_title)) {
    echo '<h2>' , $page_title . '</h2>';
}
$form_display = new ServersForm($cf, $id);
FormProcessing::process($form_display);
