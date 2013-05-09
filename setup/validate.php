<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Validation callback.
 *
 * @package PhpMyAdmin-Setup
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';

$validators = array();
require './libraries/config/validate.lib.php';

header('Content-type: application/json');

$vids = explode(',', filter_input(INPUT_POST, 'id'));
$values = json_decode(filter_input(INPUT_POST, 'values'));
if (!($values instanceof stdClass)) {
    PMA_fatalError(__('Wrong data'));
}
$values = (array)$values;
$result = PMA_config_validate($vids, $values, true);
if ($result === false) {
    $result = 'Wrong data or no validation for ' . $vids;
}
echo $result !== true ? json_encode($result) : '';
?>
