<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Page preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseFormList;

/**
 * Class PageFormList
 * @package PhpMyAdmin\Config\Forms\Page
 */
class PageFormList extends BaseFormList
{
    /**
     * @var array
     */
    protected static $all = [
        'Browse',
        'DbStructure',
        'Edit',
        'Export',
        'Import',
        'Navi',
        'Sql',
        'TableStructure',
    ];
    /**
     * @var string
     */
    protected static $ns = '\\PhpMyAdmin\\Config\\Forms\\Page\\';
}
