<?php
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;

/**
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
