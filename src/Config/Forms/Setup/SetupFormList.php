<?php
/**
 * Setup preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\BaseFormList;

class SetupFormList extends BaseFormList
{
    /** @var array<string, class-string<BaseForm>> */
    protected static array $all = [
        'Config' => ConfigForm::class,
        'Export' => ExportForm::class,
        'Features' => FeaturesForm::class,
        'Import' => ImportForm::class,
        'Main' => MainForm::class,
        'Navi' => NaviForm::class,
        'Servers' => ServersForm::class,
        'Sql' => SqlForm::class,
    ];
}
