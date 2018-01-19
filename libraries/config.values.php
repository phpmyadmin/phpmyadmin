<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database with allowed values for configuration stored in the $cfg array,
 * used by setup script and user preferences to generate forms.
 *
 * @package PhpMyAdmin
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Value meaning:
 * o array - select field, array contains allowed values
 * o string - type override
 *
 * Use normal array, paths won't be expanded
 */
$cfg_db = array();

$cfg_db['Servers'] = array(
    1 => array(
        'port'         => 'integer',
        'auth_type'    => array('config', 'http', 'signon', 'cookie'),
        'AllowDeny'    => array(
            'order' => array('', 'deny,allow', 'allow,deny', 'explicit')
        ),
        'only_db'      => 'array'
    )
);
$cfg_db['RecodingEngine'] = array('auto', 'iconv', 'recode', 'mb', 'none');
$cfg_db['OBGzip'] = array('auto', true, false);
$cfg_db['MemoryLimit'] = 'short_string';
$cfg_db['NavigationLogoLinkWindow'] = array('main', 'new');
$cfg_db['NavigationTreeDefaultTabTable'] = array(
    'structure' => __('Structure'), // fields list
    'sql' => __('SQL'),             // SQL form
    'search' => __('Search'),       // search page
    'insert' => __('Insert'),       // insert row page
    'browse' => __('Browse')        // browse page
);
$cfg_db['NavigationTreeDefaultTabTable2'] = array(
    '' => '', //don't display
    'structure' => __('Structure'), // fields list
    'sql' => __('SQL'),             // SQL form
    'search' => __('Search'),       // search page
    'insert' => __('Insert'),       // insert row page
    'browse' => __('Browse')        // browse page
);
$cfg_db['NavigationTreeDbSeparator'] = 'short_string';
$cfg_db['NavigationTreeTableSeparator'] = 'short_string';
$cfg_db['TableNavigationLinksMode'] = array(
    'icons' => __('Icons'),
    'text'  => __('Text'),
    'both'  => __('Both')
);
$cfg_db['MaxRows'] = array(25, 50, 100, 250, 500);
$cfg_db['Order'] = array('ASC', 'DESC', 'SMART');
$cfg_db['RowActionLinks'] = array(
    'none'  => __('Nowhere'),
    'left'  => __('Left'),
    'right' => __('Right'),
    'both'  => __('Both')
);
$cfg_db['TablePrimaryKeyOrder'] = array(
    'NONE'  => __('None'),
    'ASC'   => __('Ascending'),
    'DESC'  => __('Descending')
);
$cfg_db['ProtectBinary'] = array(false, 'blob', 'noblob', 'all');
$cfg_db['CharEditing'] = array('input', 'textarea');
$cfg_db['TabsMode'] = array(
    'icons' => __('Icons'),
    'text'  => __('Text'),
    'both'  => __('Both')
);
$cfg_db['PDFDefaultPageSize'] = array(
    'A3'     => 'A3',
    'A4'     => 'A4',
    'A5'     => 'A5',
    'letter' => 'letter',
    'legal'  => 'legal'
);
$cfg_db['ActionLinksMode'] = array(
    'icons' => __('Icons'),
    'text'  => __('Text'),
    'both'  => __('Both')
);
$cfg_db['GridEditing'] = array(
    'click' => __('Click'),
    'double-click' => __('Double click'),
    'disabled' => __('Disabled'),
);
$cfg_db['RelationalDisplay'] = array(
    'K' => __('key'),
    'D' => __('display column')
);
$cfg_db['DefaultTabServer'] = array(
    // the welcome page (recommended for multiuser setups)
    'welcome' => __('Welcome'),
    'databases' => __('Databases'),    // list of databases
    'status' => __('Status'),          // runtime information
    'variables' => __('Variables'),    // MySQL server variables
    'privileges' => __('Privileges')   // user management
);
$cfg_db['DefaultTabDatabase'] = array(
    'structure' => __('Structure'),   // tables list
    'sql' => __('SQL'),               // SQL form
    'search' => __('Search'),         // search query
    'operations' => __('Operations')  // operations on database
);
$cfg_db['DefaultTabTable'] = array(
    'structure' => __('Structure'),  // fields list
    'sql' => __('SQL'),              // SQL form
    'search' => __('Search'),        // search page
    'insert' => __('Insert'),        // insert row page
    'browse' => __('Browse')         // browse page
);
$cfg_db['InitialSlidersState'] = array(
    'open'     => __('Open'),
    'closed'   => __('Closed'),
    'disabled' => __('Disabled')
);
$cfg_db['SendErrorReports'] = array(
    'ask'     => __('Ask before sending error reports'),
    'always'   => __('Always send error reports'),
    'never' => __('Never send error reports')
);
$cfg_db['DefaultForeignKeyChecks'] = array(
    'default'   => __('Server default'),
    'enable'    => __('Enable'),
    'disable'   => __('Disable')
);
$cfg_db['Import']['format'] = array(
    'csv',    // CSV
    'docsql', // DocSQL
    'ldi',    // CSV using LOAD DATA
    'sql'     // SQL
);
$cfg_db['Import']['charset'] = array_merge(
    array(''),
    $GLOBALS['cfg']['AvailableCharsets']
);
$cfg_db['Import']['sql_compatibility']
    = $cfg_db['Export']['sql_compatibility'] = array(
        'NONE', 'ANSI', 'DB2', 'MAXDB', 'MYSQL323',
        'MYSQL40', 'MSSQL', 'ORACLE',
        // removed; in MySQL 5.0.33, this produces exports that
        // can't be read by POSTGRESQL (see our bug #1596328)
        //'POSTGRESQL',
        'TRADITIONAL'
    );
$cfg_db['Import']['csv_terminated'] = 'short_string';
$cfg_db['Import']['csv_enclosed'] = 'short_string';
$cfg_db['Import']['csv_escaped'] = 'short_string';
$cfg_db['Import']['ldi_terminated'] = 'short_string';
$cfg_db['Import']['ldi_enclosed'] = 'short_string';
$cfg_db['Import']['ldi_escaped'] = 'short_string';
$cfg_db['Import']['ldi_local_option'] = array('auto', true, false);
$cfg_db['Export']['_sod_select'] = array(
    'structure'          => __('structure'),
    'data'               => __('data'),
    'structure_and_data' => __('structure and data')
);
$cfg_db['Export']['method'] = array(
    'quick'          => __('Quick - display only the minimal options to configure'),
    'custom'         => __('Custom - display all possible options to configure'),
    'custom-no-form' => __(
        'Custom - like above, but without the quick/custom choice'
    ),
);
$cfg_db['Export']['format'] = array(
    'codegen', 'csv', 'excel', 'htmlexcel','htmlword', 'latex', 'ods',
    'odt', 'pdf', 'sql', 'texytext', 'xml', 'yaml'
);
$cfg_db['Export']['compression'] = array('none', 'zip', 'gzip');
$cfg_db['Export']['charset'] = array_merge(
    array(''),
    $GLOBALS['cfg']['AvailableCharsets']
);
$cfg_db['Export']['codegen_format'] = array(
    '#', 'NHibernate C# DO', 'NHibernate XML'
);
$cfg_db['Export']['csv_separator'] = 'short_string';
$cfg_db['Export']['csv_terminated'] = 'short_string';
$cfg_db['Export']['csv_enclosed'] = 'short_string';
$cfg_db['Export']['csv_escaped'] = 'short_string';
$cfg_db['Export']['csv_null'] = 'short_string';
$cfg_db['Export']['excel_null'] = 'short_string';
$cfg_db['Export']['excel_edition'] = array(
    'win'           => 'Windows',
    'mac_excel2003' => 'Excel 2003 / Macintosh',
    'mac_excel2008' => 'Excel 2008 / Macintosh'
);
$cfg_db['Export']['sql_structure_or_data'] = $cfg_db['Export']['_sod_select'];
$cfg_db['Export']['sql_type'] = array('INSERT', 'UPDATE', 'REPLACE');
$cfg_db['Export']['sql_insert_syntax'] = array(
    'complete' => __('complete inserts'),
    'extended' => __('extended inserts'),
    'both'     => __('both of the above'),
    'none'     => __('neither of the above')
);
$cfg_db['Export']['htmlword_structure_or_data'] = $cfg_db['Export']['_sod_select'];
$cfg_db['Export']['htmlword_null'] = 'short_string';
$cfg_db['Export']['ods_null'] = 'short_string';
$cfg_db['Export']['odt_null'] = 'short_string';
$cfg_db['Export']['odt_structure_or_data'] = $cfg_db['Export']['_sod_select'];
$cfg_db['Export']['texytext_structure_or_data'] = $cfg_db['Export']['_sod_select'];
$cfg_db['Export']['texytext_null'] = 'short_string';

/**
 * Default values overrides
 * Use only full paths
 */
$cfg_db['_overrides'] = array();

/**
 * Basic validator assignments (functions from libraries/config/Validator.php
 * and 'validators' object in js/config.js)
 * Use only full paths and form ids
 */
$cfg_db['_validators'] = array(
    'CharTextareaCols' => 'validatePositiveNumber',
    'CharTextareaRows' => 'validatePositiveNumber',
    'ExecTimeLimit' => 'validateNonNegativeNumber',
    'Export/sql_max_query_size' => 'validatePositiveNumber',
    'FirstLevelNavigationItems' => 'validatePositiveNumber',
    'ForeignKeyMaxLimit' => 'validatePositiveNumber',
    'Import/csv_enclosed' => array(array('validateByRegex', '/^.?$/')),
    'Import/csv_escaped' => array(array('validateByRegex', '/^.$/')),
    'Import/csv_terminated' => array(array('validateByRegex', '/^.$/')),
    'Import/ldi_enclosed' => array(array('validateByRegex', '/^.?$/')),
    'Import/ldi_escaped' => array(array('validateByRegex', '/^.$/')),
    'Import/ldi_terminated' => array(array('validateByRegex', '/^.$/')),
    'Import/skip_queries' => 'validateNonNegativeNumber',
    'InsertRows' => 'validatePositiveNumber',
    'NumRecentTables' => 'validateNonNegativeNumber',
    'NumFavoriteTables' => 'validateNonNegativeNumber',
    'LimitChars' => 'validatePositiveNumber',
    'LoginCookieValidity' => 'validatePositiveNumber',
    'LoginCookieStore' => 'validateNonNegativeNumber',
    'MaxDbList' => 'validatePositiveNumber',
    'MaxNavigationItems' => 'validatePositiveNumber',
    'MaxCharactersInDisplayedSQL' => 'validatePositiveNumber',
    'MaxRows' => 'validatePositiveNumber',
    'MaxTableList' => 'validatePositiveNumber',
    'MemoryLimit' => array(array('validateByRegex', '/^(-1|(\d+(?:[kmg])?))$/i')),
    'NavigationTreeTableLevel' => 'validatePositiveNumber',
    'QueryHistoryMax' => 'validatePositiveNumber',
    'RepeatCells' => 'validateNonNegativeNumber',
    'Server' => 'validateServer',
    'Server_pmadb' => 'validatePMAStorage',
    'Servers/1/port' => 'validatePortNumber',
    'Servers/1/hide_db' => 'validateRegex',
    'TextareaCols' => 'validatePositiveNumber',
    'TextareaRows' => 'validatePositiveNumber',
    'TrustedProxies' => 'validateTrustedProxies');

/**
 * Additional validators used for user preferences
 */
$cfg_db['_userValidators'] = array(
    'MaxDbList'       => array(
        array('validateUpperBound', 'value:MaxDbList')
    ),
    'MaxTableList'    => array(
        array('validateUpperBound', 'value:MaxTableList')
    ),
    'QueryHistoryMax' => array(
        array('validateUpperBound', 'value:QueryHistoryMax')
    )
);
