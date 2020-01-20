<?php
/**
 * Page preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseFormList;

/**
 * @package PhpMyAdmin\Config\Forms\Page
 */
class PageFormList extends BaseFormList
{
    /** @var array */
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
    /** @var string */
    protected static $ns = '\\PhpMyAdmin\\Config\\Forms\\Page\\';
}
