<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used for page-related settings
 *
 * Extends groups defined in user_preferences.forms.php
 * specific to page-related settings
 *
 * See more info in user_preferences.forms.php
 *
 * @package PhpMyAdmin
 */

if (!is_array($forms)) {
    $forms = array();
}

$forms['Browse'] = array();
$forms['Browse']['Browse'] = $forms['Main_panel']['Browse'];

$forms['DbStructure'] = array();
$forms['DbStructure']['DbStructure'] = $forms['Main_panel']['DbStructure'];

$forms['Edit'] = array();
$forms['Edit']['Edit'] = $forms['Main_panel']['Edit'];
$forms['Edit']['Text_fields'] = $forms['Features']['Text_fields'];

$forms['TableStructure'] = array();
$forms['TableStructure']['TableStructure'] = $forms['Main_panel']['TableStructure'];
