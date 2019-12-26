<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\FeaturesForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

/**
 * Class EditForm
 * @package PhpMyAdmin\Config\Forms\Page
 */
class EditForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Edit' => MainForm::getForms()['Edit'],
            'Text_fields' => FeaturesForm::getForms()['Text_fields'],
        ];
    }
}
