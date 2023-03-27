<?php
/**
 * Page preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\BaseFormList;

class PageFormList extends BaseFormList
{
    /** @var array<string, class-string<BaseForm>> */
    protected static array $all = [
        'Browse' => BrowseForm::class,
        'DbStructure' => DbStructureForm::class,
        'Edit' => EditForm::class,
        'Export' => ExportForm::class,
        'Import' => ImportForm::class,
        'Navi' => NaviForm::class,
        'Sql' => SqlForm::class,
        'TableStructure' => TableStructureForm::class,
    ];
}
