<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * theme test
 *
 * @package PhpMyAdmin-test
 */

define('PMA_TEST_THEME', true);

chdir('..');

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';
$GLOBALS['pmaThemeImage'] = '../' . $GLOBALS['pmaThemeImage'];

$lang_iso_code = $GLOBALS['available_languages'][$GLOBALS['lang']][1];

// start output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE HTML>
<html lang="<?php echo $lang_iso_code; ?>" dir="<?php echo $GLOBALS['text_dir']; ?>">
<head>
    <title>phpMyAdmin <?php echo PMA_VERSION; ?> -
        <?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?> - Theme Test</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css"
        href="../phpmyadmin.css.php?<?php echo PMA_URL_getCommon(); ?>&amp;nocache=<?php echo $GLOBALS['PMA_Config']->getThemeUniqueValue(); ?>" />
    <link rel="stylesheet" type="text/css" media="print"
        href="../print.css" />
    <script src="../js/jquery/jquery-1.11.1.min.js" type="text/javascript"></script>
    <script src="../js/messages.php" type="text/javascript"></script>
    <script type="text/javascript">
        var PMA_TEST_THEME = true;
    </script>
    <script src="../js/get_image.js.php" type="text/javascript"></script>
    <script src="../js/functions.js" type="text/javascript"></script>
</head>
<body>
<?php


$separator = '<span class=\'separator item\'>&nbsp;»</span>' . "\n";
$item = '<a href="%1$s?%2$s" class="item">'
    . ' <img class="icon %5$s" src="../themes/dot.gif"'
    . ' width="16" height="16" alt="" /> ' . "\n"
    . '%4$s: %3$s</a>' . "\n";

echo '<div id="serverinfo">' . "\n";
printf(
    $item,
    $GLOBALS['cfg']['DefaultTabServer'],
    PMA_URL_getCommon(),
    'Server',
    __('Server'),
    'ic_s_host'
);

echo $separator;
printf(
    $item,
    $GLOBALS['cfg']['DefaultTabDatabase'],
    '',
    'Database',
    __('Database'),
    'ic_s_db'
);

echo $separator;
printf(
    $item,
    $GLOBALS['cfg']['DefaultTabTable'],
    '',
    'Table',
    (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']
        ? __('View')
        : __('Table')),
    (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']
        ? 'ic_b_views'
        : 'ic_s_tbl')
);

echo '<span class="table_comment" id="span_table_comment">'
    . '&quot;Table comment&quot;</span>' . "\n";

echo '</div>';


/**
 * Displays tab links
 */
$tabs = array();

$tabs['databases']['icon'] = 's_db.png';
$tabs['databases']['link'] = 'server_databases.php';
$tabs['databases']['text'] = __('Databases');

$tabs['sql']['icon'] = 'b_sql.png';
$tabs['sql']['link'] = 'server_sql.php';
$tabs['sql']['text'] = __('SQL');

$tabs['status']['icon'] = 's_status.png';
$tabs['status']['link'] = 'server_status.php';
$tabs['status']['text'] = __('Status');

$tabs['vars']['icon'] = 's_vars.png';
$tabs['vars']['link'] = 'server_variables.php';
$tabs['vars']['text'] = __('Variables');

$tabs['charset']['icon'] = 's_asci.png';
$tabs['charset']['link'] = 'server_collations.php';
$tabs['charset']['text'] = __('Charsets');

$tabs['engine']['icon'] = 'b_engine.png';
$tabs['engine']['link'] = 'server_engines.php';
$tabs['engine']['text'] = __('Engines');

$tabs['rights']['icon'] = 's_rights.png';
$tabs['rights']['link'] = 'server_privileges.php';
$tabs['rights']['text'] = __('Privileges');

$tabs['binlog']['icon'] = 's_tbl.png';
$tabs['binlog']['link'] = 'server_binlog.php';
$tabs['binlog']['text'] = __('Binary log');

$tabs['export']['icon'] = 'b_export.png';
$tabs['export']['text'] = 'disabled';

$tabs['export2']['icon'] = 'b_export.png';
$tabs['export2']['text'] = 'disabled caution';
$tabs['export2']['class'] = 'caution';

$tabs['import']['icon'] = 'b_import.png';
$tabs['import']['link'] = 'server_import.php';
$tabs['import']['text'] = 'active';
$tabs['import']['class'] = 'active';

echo PMA_Util::getHtmlTabs($tabs, array(), 'topmenu');
unset($tabs);

if (@file_exists($pmaThemeImage . 'logo_right.png')) {
    ?>
    <img id="pmalogoright" src="../<?php echo $pmaThemeImage; ?>logo_right.png"
        alt="phpMyAdmin" />
    <?php
}
?>
<h1>
<?php
echo sprintf(
    __('Welcome to %s'),
    '<bdo dir="ltr" lang="en">phpMyAdmin ' . PMA_VERSION . '</bdo>'
);
?>
</h1>

<hr class="clearfloat" />

<form method="post" action="theme.php">
<fieldset>
    <legend><?php echo __('Theme'); ?></legend>
<?php
    echo $_SESSION['PMA_Theme_Manager']->getHtmlSelectBox(false);
?>
</fieldset>
</form>

<hr />

<h1>H1 Header</h1>
<h2>H2 Header</h2>
<h3>H3 Header</h3>
<h4>H4 Header</h4>

<div class="success">
    success message box content!
</div>
<div class="success">
    <h1>Success message box header!</h1>
    success message box content!
</div>

<div class="notice">
    notice message box content!
</div>
<div class="notice">
    <h1>Notice message box header!</h1>
    notice message box content!
</div>
<div class="error">
    error message box content!
</div>
<div class="error">
    <h1>Error message box header!</h1>
    error message box content!
</div>

<fieldset class="confirmation">
    <legend>Confirmation fieldset</legend>
    <code>QUERY TO EXECUTE;</code>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="yes" value="YES" />
    <input type="submit" name="no" value="NO" />
</fieldset>

<hr />

<div class="success">
    success message box content!
</div>
<code class="sql">
<span class="syntax">
<span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span>
<span class="syntax_punct">*</span> <br />
<span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>
<span class="syntax_quote syntax_quote_backtick">`test`</span>
<span class="syntax_white syntax_white_newline"></span><br />
<span class="syntax_alpha syntax_alpha_reservedWord">LIMIT</span>
<span class="syntax_digit syntax_digit_integer">0</span>
<span class="syntax_punct syntax_punct_listsep">,</span>
<span class="syntax_digit syntax_digit_integer">30</span>;<br />
<span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span>
<span class="syntax_punct">*</span> <br />
<span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>
<span class="syntax_quote syntax_quote_backtick">`test`</span>
<span class="syntax_white syntax_white_newline"></span><br />
<span class="syntax_alpha syntax_alpha_reservedWord">LIMIT</span>
<span class="syntax_digit syntax_digit_integer">0</span>
<span class="syntax_punct syntax_punct_listsep">,</span>
<span class="syntax_digit syntax_digit_integer">30</span>;<br />
<span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span>
<span class="syntax_punct">*</span> <br />
<span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>
<span class="syntax_quote syntax_quote_backtick">`test`</span>
<span class="syntax_white syntax_white_newline"></span><br />
<span class="syntax_alpha syntax_alpha_reservedWord">LIMIT</span>
<span class="syntax_digit syntax_digit_integer">0</span>
<span class="syntax_punct syntax_punct_listsep">,</span>
<span class="syntax_digit syntax_digit_integer">30</span>;<br />
<span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span>
<span class="syntax_punct">*</span> <br />
<span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>
<span class="syntax_quote syntax_quote_backtick">`test`</span>
<span class="syntax_white syntax_white_newline"></span><br />
<span class="syntax_alpha syntax_alpha_reservedWord">LIMIT</span>
<span class="syntax_digit syntax_digit_integer">0</span>
<span class="syntax_punct syntax_punct_listsep">,</span>
<span class="syntax_digit syntax_digit_integer">30</span>;<br />
</span>
</code>
<div class="tools">
[
<a href="tbl_sql.php?db=test;table=test;sql_query=SELECT+%2A+FROM+%60test%60;show_query=1;token=266edabf70fa6368498d89b4054d01bf#querybox" >Bearbeiten</a>
] [
<a href="import.php?db=test;table=test;sql_query=EXPLAIN+SELECT+%2A+FROM+%60test%60;token=266edabf70fa6368498d89b4054d01bf" >SQL erklären</a>
] [
<a href="import.php?db=test;table=test;sql_query=SELECT+%2A+FROM+%60test%60;show_query=1;show_as_php=1;token=266edabf70fa6368498d89b4054d01bf" >PHP-Code erzeugen</a>
] [
<a href="import.php?db=test;table=test;sql_query=SELECT+%2A+FROM+%60test%60;show_query=1;token=266edabf70fa6368498d89b4054d01bf" >Aktualisieren</a>
]</div>

<hr />

<table class="data">
    <caption>table.data caption</caption>
    <thead>
        <tr><th></th>
            <th>table.data thead tr th</th>
            <th>table.data thead tr th</th>
            <th colspan="3">action</th>
            <th>table.data thead tr th</th>
        </tr>
    </thead>
    <tfoot>
        <tr><th></th>
            <th>table.data tfoot tr th</th>
            <th class="value">table.data tfoot tr th</th>
            <th colspan="3">action</th>
            <th>table.data tfoot tr th</th>
        </tr>
    </tfoot>
    <tbody>
        <tr class="odd">
            <td><input type="checkbox" id="checkbox_1" name="checkbox_1"
                    value="1" /></td>
            <th><label for="checkbox_1">th label</label></th>
            <td class="value">td.value</td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop" />
            </td>
            <td>table.data tbody tr.odd td</td>
        </tr>
        <tr class="even">
            <td><input type="checkbox" id="checkbox_2" name="checkbox_2"
                    value="1" /></td>
            <th><label for="checkbox_2">th label</label></th>
            <td class="value">td.value</td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop" />
            </td>
            <td>table.data tbody tr.even td</td>
        </tr>
        <tr class="odd">
            <td><input type="checkbox" id="checkbox_3" name="checkbox_3"
                    value="1" /></td>
            <th><label for="checkbox_3">th label</label></th>
            <td class="value">td.value</td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>table.data tbody tr.odd td</td>
        </tr>
        <tr class="even">
            <td><input type="checkbox" id="checkbox_4" name="checkbox_4"
                    value="1" /></td>
            <th><label for="checkbox_4">th label</label></th>
            <td class="value">td.value</td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>
                <img class="icon ic_bd_drop" src="../themes/dot.gif" alt="drop"/>
            </td>
            <td>table.data tbody tr.even td</td>
        </tr>
    </tbody>
</table>
</body>
</html>
