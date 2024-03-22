<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\BaseFormList;

class UserFormList extends BaseFormList
{
    /** @var array<string, class-string<BaseForm>> */
    protected static array $all = [
        'Features' => FeaturesForm::class,
        'Sql' => SqlForm::class,
        'Navi' => NaviForm::class,
        'Main' => MainForm::class,
        'Export' => ExportForm::class,
        'Import' => ImportForm::class,
    ];
}
