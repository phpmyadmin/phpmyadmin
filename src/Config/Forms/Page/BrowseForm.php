<?php
/**
 * User preferences form
 */

declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\Page;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\MainForm;

class BrowseForm extends BaseForm
{
    /** @return mixed[] */
    public static function getForms(): array
    {
        return ['Browse' => MainForm::getForms()['Browse']];
    }
}
