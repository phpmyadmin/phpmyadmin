<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class DbStructureForm extends BaseForm
{
    /** @return mixed[] */
    public static function getForms(): array
    {
        return ['DbStructure' => MainForm::getForms()['DbStructure']];
    }
}
