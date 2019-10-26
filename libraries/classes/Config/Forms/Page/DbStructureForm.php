<?php
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

/**
 * Class DbStructureForm
 * @package PhpMyAdmin\Config\Forms\Page
 */
class DbStructureForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'DbStructure' => MainForm::getForms()['DbStructure'],
        ];
    }
}
