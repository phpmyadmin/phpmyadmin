<?php
/**
 * Validation callback.
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @copyright  Copyright (c) 2008, Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';

$validators = array();
require './setup/lib/validate.lib.php';

header('Content-type: application/json');

$vids = explode(',', filter_input(INPUT_POST, 'id'));
$values = json_decode(filter_input(INPUT_POST, 'values'));
if (!($values instanceof stdClass)) {
    die('Wrong data');
}
$values = (array)$values;
$result = validate($vids, $values, true);
if ($result === false) {
    $result = 'Wrong data or no validation for ' . $vids;
}
echo $result !== true ? json_encode($result) : '';
?>
