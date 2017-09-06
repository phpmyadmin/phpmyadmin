<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseFormList;

class UserFormList extends BaseFormList
{
    protected static $all = array(
        'Features',
        'Sql',
        'Navi',
        'Main',
        'Import',
        'Export',
    );
    protected static $ns = '\\PhpMyAdmin\\Config\\Forms\\User\\';
}
