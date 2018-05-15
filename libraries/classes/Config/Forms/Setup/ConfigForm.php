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

class ConfigForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Config' => array(
                'DefaultLang',
                'ServerDefault'
            ),
        );
    }
}
