<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

/**
 * Class ConfigForm
 * @package PhpMyAdmin\Config\Forms\Setup
 */
class ConfigForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Config' => [
                'DefaultLang',
                'ServerDefault',
            ],
        ];
    }
}
