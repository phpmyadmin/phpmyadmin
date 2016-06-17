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
require './libraries/config/Validator.class.php';

header('Content-type: application/json');

$ids = PMA_isValid($_POST['id'], 'scalar') ? $_POST['id'] : null;
$vids = explode(',', $ids);
$vals = PMA_isValid($_POST['values'], 'scalar') ? $_POST['values'] : null;
$values = json_decode($vals);
if (!($values instanceof stdClass)) {
    PMA_fatalError(__('Wrong data'));
}
$values = (array)$values;
$result = PMA_Validator::validate($GLOBALS['ConfigFile'], $vids, $values, true);
if ($result === false) {
    $result = sprintf(
        __('Wrong data or no validation for %s'),
        implode(',', $vids)
    );
}
echo $result !== true ? json_encode($result) : '';
?>
