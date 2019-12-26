<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

/**
 * Class MainForm
 * @package PhpMyAdmin\Config\Forms\User
 */
class MainForm extends BaseForm
{
    /**
     * @return array
     */
    public static function getForms()
    {
        return [
            'Startup' => [
                'ShowCreateDb',
                'ShowStats',
                'ShowServerInfo',
            ],
            'DbStructure' => [
                'ShowDbStructureCharset',
                'ShowDbStructureComment',
                'ShowDbStructureCreation',
                'ShowDbStructureLastUpdate',
                'ShowDbStructureLastCheck',
            ],
            'TableStructure' => [
                'HideStructureActions',
                'ShowColumnComments',
                ':group:' . __('Default transformations'),
                'DefaultTransformations/Hex',
                'DefaultTransformations/Substring',
                'DefaultTransformations/Bool2Text',
                'DefaultTransformations/External',
                'DefaultTransformations/PreApPend',
                'DefaultTransformations/DateFormat',
                'DefaultTransformations/Inline',
                'DefaultTransformations/TextImageLink',
                'DefaultTransformations/TextLink',
                ':group:end',
            ],
            'Browse' => [
                'TableNavigationLinksMode',
                'ActionLinksMode',
                'ShowAll',
                'MaxRows',
                'Order',
                'BrowsePointerEnable',
                'BrowseMarkerEnable',
                'GridEditing',
                'SaveCellsAtOnce',
                'RepeatCells',
                'LimitChars',
                'RowActionLinks',
                'RowActionLinksWithoutUnique',
                'TablePrimaryKeyOrder',
                'RememberSorting',
                'RelationalDisplay',
            ],
            'Edit' => [
                'ProtectBinary',
                'ShowFunctionFields',
                'ShowFieldTypesInDataEditView',
                'InsertRows',
                'ForeignKeyDropdownOrder',
                'ForeignKeyMaxLimit',
            ],
            'Tabs' => [
                'TabsMode',
                'DefaultTabServer',
                'DefaultTabDatabase',
                'DefaultTabTable',
            ],
            'DisplayRelationalSchema' => [
                'PDFDefaultPageSize',
            ],
        ];
    }

    /**
     * @return string
     */
    public static function getName()
    {
        return __('Main panel');
    }
}
