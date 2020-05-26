<?php
/**
 * Validation callback.
 */

declare(strict_types=1);

use PhpMyAdmin\Config\Validator;
use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

/**
 * Core libraries.
 */
require ROOT_PATH . 'setup/lib/common.inc.php';

$validators = [];

Core::headerJSON();

$ids = Core::isValid($_POST['id'], 'scalar') ? $_POST['id'] : null;
$vids = explode(',', $ids);
$vals = Core::isValid($_POST['values'], 'scalar') ? $_POST['values'] : null;
$values = json_decode($vals);
if (! ($values instanceof stdClass)) {
    Core::fatalError(__('Wrong data'));
}
$values = (array) $values;
$result = Validator::validate($GLOBALS['ConfigFile'], $vids, $values, true);
if ($result === false) {
    $result = sprintf(
        __('Wrong data or no validation for %s'),
        implode(',', $vids)
    );
}
echo $result !== true ? json_encode($result) : '';
