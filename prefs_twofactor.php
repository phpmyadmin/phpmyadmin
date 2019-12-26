<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
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
echo UserPreferencesHeader::getContent($template, $relation);

$two_factor = new TwoFactor($GLOBALS['cfg']['Server']['user']);

if (isset($_POST['2fa_remove'])) {
    if (! $two_factor->check(true)) {
        echo $template->render('preferences/two_factor/confirm', [
            'form' => $two_factor->render(),
        ]);
        exit;
    } else {
        $two_factor->configure('');
        Message::rawNotice(__('Two-factor authentication has been removed.'))->display();
    }
} elseif (isset($_POST['2fa_configure'])) {
    if (! $two_factor->configure($_POST['2fa_configure'])) {
        echo $template->render('preferences/two_factor/configure', [
            'form' => $two_factor->setup(),
            'configure' => $_POST['2fa_configure'],
        ]);
        exit;
    } else {
        Message::rawNotice(__('Two-factor authentication has been configured.'))->display();
    }
}

$backend = $two_factor->backend;
echo $template->render('preferences/two_factor/main', [
    'enabled' => $two_factor->writable,
    'num_backends' => count($two_factor->available),
    'backend_id' => $backend::$id,
    'backend_name' => $backend::getName(),
    'backend_description' => $backend::getDescription(),
    'backends' => $two_factor->getAllBackends(),
    'missing' => $two_factor->getMissingDeps(),
]);
