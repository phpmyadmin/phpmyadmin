<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

class ConfigForm extends BaseForm
{
    /** @return mixed[] */
    public static function getForms(): array
    {
        return ['Config' => ['DefaultLang', 'ServerDefault']];
    }
}
