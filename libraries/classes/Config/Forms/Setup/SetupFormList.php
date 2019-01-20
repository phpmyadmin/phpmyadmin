<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Setup preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseFormList;

/**
 * Class SetupFormList
 * @package PhpMyAdmin\Config\Forms\Setup
 */
class SetupFormList extends BaseFormList
{
    /**
     * @var array
     */
    protected static $all = [
        'Config',
        'Export',
        'Features',
        'Import',
        'Main',
        'Navi',
        'Servers',
        'Sql',
    ];
    /**
     * @var string
     */
    protected static $ns = '\\PhpMyAdmin\\Config\\Forms\\Setup\\';
}
