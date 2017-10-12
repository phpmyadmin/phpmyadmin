<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences form
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Config\Forms\User;

use PhpMyAdmin\Config\Forms\BaseForm;

class ImportForm extends BaseForm
{
    public static function getForms()
    {
        return array(
            'Import_defaults' => array(
                'Import/format',
                'Import/charset',
                'Import/allow_interrupt',
                'Import/skip_queries'
            ),
            'Sql' => array(
                'Import/sql_compatibility',
                'Import/sql_no_auto_value_on_zero',
                'Import/sql_read_as_multibytes'
            ),
            'Csv' => array(
                ':group:' . __('CSV'),
                    'Import/csv_replace',
                    'Import/csv_ignore',
                    'Import/csv_terminated',
                    'Import/csv_enclosed',
                    'Import/csv_escaped',
                    'Import/csv_col_names',
                    ':group:end',
                ':group:' . __('CSV using LOAD DATA'),
                    'Import/ldi_replace',
                    'Import/ldi_ignore',
                    'Import/ldi_terminated',
                    'Import/ldi_enclosed',
                    'Import/ldi_escaped',
                    'Import/ldi_local_option'
            ),
            'Open_Document' => array(
                ':group:' . __('OpenDocument Spreadsheet'),
                    'Import/ods_col_names',
                    'Import/ods_empty_rows',
                    'Import/ods_recognize_percentages',
                    'Import/ods_recognize_currency'
            ),

        );
    }

    public static function getName()
    {
        return __('Import');
    }
}
