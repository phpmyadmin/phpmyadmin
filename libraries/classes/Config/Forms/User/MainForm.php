<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

class MainForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Startup' => array(
                'ShowCreateDb',
                'ShowStats',
                'ShowServerInfo'
            ),
            'DbStructure' => array(
                'ShowDbStructureCharset',
                'ShowDbStructureComment',
                'ShowDbStructureCreation',
                'ShowDbStructureLastUpdate',
                'ShowDbStructureLastCheck'
            ),
            'TableStructure' => array(
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
                ':group:end'
            ),
            'Browse' => array(
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
                'RelationalDisplay'
            ),
            'Edit' => array(
                'ProtectBinary',
                'ShowFunctionFields',
                'ShowFieldTypesInDataEditView',
                'InsertRows',
                'ForeignKeyDropdownOrder',
                'ForeignKeyMaxLimit'
            ),
            'Tabs' => array(
                'TabsMode',
                'DefaultTabServer',
                'DefaultTabDatabase',
                'DefaultTabTable'
            ),
            'DisplayRelationalSchema' => array(
                'PDFDefaultPageSize'
            ),
        );
    }

    public static function getName()
    {
        return __('Main panel');
    }
}
