<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Setup preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseFormList;

class SetupFormList extends BaseFormList
{
    protected static $all = array(
        'Config',
        'Export',
        'Features',
        'Import',
        'Main',
        'Navi',
        'Servers',
        'Sql',
    );
    protected static $ns = '\\PhpMyAdmin\\Config\\Forms\\Setup\\';
}
