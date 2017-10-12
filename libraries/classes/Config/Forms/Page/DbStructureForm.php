<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class DbStructureForm extends BaseForm
{
    public static function getForms()
    {
        return [
            'DbStructure' => MainForm::getForms()['DbStructure']
        ];
    }
}

