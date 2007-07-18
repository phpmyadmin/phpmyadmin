<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * theme test
 *
 * @uses    libraries/common.inc.php        global fnctions
 * @package phpMyAdmin-test
 * @version $Id$
 */

chdir('..');

/**
 * Gets core libraries and defines some variables
 */
require_once './libraries/common.inc.php';

$lang_iso_code = $GLOBALS['available_languages'][$GLOBALS['lang']][2];

// start output
header('Content-Type: text/html; charset=' . $GLOBALS['charset']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $lang_iso_code; ?>"
    lang="<?php echo $lang_iso_code; ?>"
    dir="<?php echo $GLOBALS['text_dir']; ?>">
<head>
    <title>phpMyAdmin <?php echo PMA_VERSION; ?> -
        <?php echo htmlspecialchars($HTTP_HOST); ?> - Theme Test</title>
    <meta http-equiv="Content-Type"
        content="text/html; charset=<?php echo $GLOBALS['charset']; ?>" />
    <link rel="stylesheet" type="text/css"
        href="../phpmyadmin.css.php?<?php echo PMA_generate_common_url(); ?>&amp;js_frame=right&amp;nocache=<?php echo $_SESSION['PMA_Config']->getMtime(); ?>" />
    <link rel="stylesheet" type="text/css" media="print"
        href="../print.css" />
    <script src="../js/functions.js" type="text/javascript"></script>
</head>
<body>
<?php


$separator = '<span class="separator">'
    .'<img class="icon" src=../"' . $GLOBALS['pmaThemeImage'] . 'item_ltr.png"'
    .' width="5" height="9" alt="-" /></span>' . "\n";
$item = '<a href="%1$s?%2$s" class="item">'
    .' <img class="icon" src="../' . $GLOBALS['pmaThemeImage'] . '%5$s"'
    .' width="16" height="16" alt="" /> ' . "\n"
    .'%4$s: %3$s</a>' . "\n";

echo '<div id="serverinfo">' . "\n";
printf($item,
        $GLOBALS['cfg']['DefaultTabServer'],
        PMA_generate_common_url(),
        'Server',
        $GLOBALS['strServer'],
        's_host.png');

echo $separator;
printf($item,
        $GLOBALS['cfg']['DefaultTabDatabase'],
        '',
        'Database',
        $GLOBALS['strDatabase'],
        's_db.png');

echo $separator;
printf($item,
        $GLOBALS['cfg']['DefaultTabTable'],
        '',
        'Table',
        (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']
            ? $GLOBALS['strView']
            : $GLOBALS['strTable']),
        (isset($GLOBALS['tbl_is_view']) && $GLOBALS['tbl_is_view']
            ? 'b_views'
            : 's_tbl') . '.png');

echo '<span class="table_comment" id="span_table_comment">'
    .'&quot;Table comment&quot</span>' . "\n";

echo '</div>';


/**
 * Displays tab links
 */
$tabs = array();

$tabs['databases']['icon'] = '../../../../' . $pmaThemeImage . 's_db.png';
$tabs['databases']['link'] = 'server_databases.php';
$tabs['databases']['text'] = $strDatabases;

$tabs['sql']['icon'] = '../../../../' . $pmaThemeImage . 'b_sql.png';
$tabs['sql']['link'] = 'server_sql.php';
$tabs['sql']['text'] = $strSQL;

$tabs['status']['icon'] = '../../../../' . $pmaThemeImage . 's_status.png';
$tabs['status']['link'] = 'server_status.php';
$tabs['status']['text'] = $strStatus;

$tabs['vars']['icon'] = '../../../../' . $pmaThemeImage . 's_vars.png';
$tabs['vars']['link'] = 'server_variables.php';
$tabs['vars']['text'] = $strServerTabVariables;

$tabs['charset']['icon'] = '../../../../' . $pmaThemeImage . 's_asci.png';
$tabs['charset']['link'] = 'server_collations.php';
$tabs['charset']['text'] = $strCharsets;

$tabs['engine']['icon'] = '../../../../' . $pmaThemeImage . 'b_engine.png';
$tabs['engine']['link'] = 'server_engines.php';
$tabs['engine']['text'] = $strEngines;

$tabs['rights']['icon'] = '../../../../' . $pmaThemeImage . 's_rights.png';
$tabs['rights']['link'] = 'server_privileges.php';
$tabs['rights']['text'] = $strPrivileges;

$tabs['binlog']['icon'] = '../../../../' . $pmaThemeImage . 's_tbl.png';
$tabs['binlog']['link'] = 'server_binlog.php';
$tabs['binlog']['text'] = $strBinaryLog;

$tabs['process']['icon'] = '../../../../' . $pmaThemeImage . 's_process.png';
$tabs['process']['link'] = 'server_processlist.php';
$tabs['process']['text'] = 'caution';
$tabs['process']['class'] = 'caution';

$tabs['export']['icon'] = '../../../../' . $pmaThemeImage . 'b_export.png';
$tabs['export']['text'] = 'disabled';

$tabs['export2']['icon'] = '../../../../' . $pmaThemeImage . 'b_export.png';
$tabs['export2']['text'] = 'disabled caution';
$tabs['export2']['class'] = 'caution';

$tabs['import']['icon'] = '../../../../' . $pmaThemeImage . 'b_import.png';
$tabs['import']['link'] = 'server_import.php';
$tabs['import']['text'] = 'active';
$tabs['import']['class'] = 'active';

echo PMA_getTabs($tabs);
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
echo sprintf($strWelcome,
    '<bdo dir="ltr" xml:lang="en">phpMyAdmin ' . PMA_VERSION . '</bdo>');
?>
</h1>

<hr class="clearfloat" />

<form method="post" action="theme.php" target="_parent">
<fieldset>
    <legend><?php echo $strTheme; ?></legend>
<?php
    echo $_SESSION['PMA_Theme_Manager']->getHtmlSelectBox(false);
?>
<noscript><input type="submit" value="Go" style="vertical-align: middle" /></noscript>
</fieldset>
</form>

<hr />

<h1>H1 Header</h1>
<h2>H2 Header</h2>
<h3>H3 Header</h3>
<h4>H4 Header</h4>

<h1 class="notice">Notice header!</h1>
<div class="notice">
    notice message box content!
</div>
<div class="notice">
    <h1>Notice message box header!</h1>
    notice message box content!
</div>

<h1 class="warning">Warning header!</h1>
<div class="warning">
    warning message box content!
</div>
<div class="warning">
    <h1>Warning message box header!</h1>
    warning message box content!
</div>

<h1 class="error">Error header!</h1>
<div class="error">
    error message box content!
</div>
<div class="error">
    <h1>Error message box header!</h1>
    error message box content!
</div>

<fieldset class="confirmation">
    <legend>Confirmation fieldset</legend>
    <tt>QUERY TO EXECUTE;</tt>
</fieldset>
<fieldset class="tblFooters">
    <input type="submit" name="yes" value="YES" />
    <input type="submit" name="no" value="NO" />
</fieldset>

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
            <th><label for="checkbox_1">th label</label</th>
            <td class="value">td.value</td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td>table.data tbody tr.odd td</td>
        </tr>
        <tr class="even">
            <td><input type="checkbox" id="checkbox_2" name="checkbox_2"
                    value="1" /></td>
            <th><label for="checkbox_2">th label</label</th>
            <td class="value">td.value</td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td>table.data tbody tr.even td</td>
        </tr>
        <tr class="odd">
            <td><input type="checkbox" id="checkbox_3" name="checkbox_3"
                    value="1" /></td>
            <th><label for="checkbox_3">th label</label</th>
            <td class="value">td.value</td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td>table.data tbody tr.odd td</td>
        </tr>
        <tr class="even">
            <td><input type="checkbox" id="checkbox_4" name="checkbox_4"
                    value="1" /></td>
            <th><label for="checkbox_4">th label</label</th>
            <td class="value">td.value</td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/bd_drop.png"
                    width="16" height="16" alt="drop" /></td>
            <td>table.data tbody tr.even td</td>
        </tr>
    </tbody>
</table>
</body>
</html>
