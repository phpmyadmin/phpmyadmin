<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Get the variables sent or posted to this script and displays the header
 */
require('./libraries/grab_globals.lib.php3');

/**
 * Gets a core script and starts output buffering work
 */
if (!defined('PMA_COMMON_LIB_INCLUDED')) {
    include('./libraries/common.lib.php3');
}

PMA_checkParameters(array('db', 'table', 'field'));

require('./libraries/ob.lib.php3');
if ($cfg['OBGzip']) {
    $ob_mode = PMA_outBufferModeGet();
    if ($ob_mode) {
        PMA_outBufferPre($ob_mode);
    }
}
include('./libraries/header_http.inc.php3');
$field = urldecode($field);

/**
 * Displays the frame
 */
// Gets the font sizes to use
PMA_setFontSizes();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $available_languages[$lang][2]; ?>" lang="<?php echo $available_languages[$lang][2]; ?>" dir="<?php echo $text_dir; ?>">

<head>
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
    <base<?php if (!empty($cfg['PmaAbsoluteUri'])) echo ' href="' . $cfg['PmaAbsoluteUri'] . '"'; ?> />
    <link rel="stylesheet" type="text/css" href="./css/phpmyadmin.css.php3?lang=<?php echo $lang; ?>&amp;js_frame=right" />
    <script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
    <script type="text/javascript" language="javascript">
    self.focus();
    function formupdate(field, key) {
        if (opener && opener.document && opener.document.insertForm && opener.document.insertForm.elements['field_' + field + '[]']) {
            opener.document.insertForm.elements['field_' + field + '[]'].value = key;
            self.close();
        } else {
            alert('<?php echo $strWindowNotFound; ?>');
        }
    }
    </script>
</head>

<body bgcolor="<?php echo $cfg['LeftBgColor']; ?>" style="margin-left: 5px; margin-top: 5px; margin-right: 5px; margin-bottom: 0px">
<?php
$per_page = 200;
require('./libraries/relation.lib.php3'); // foreign keys
require('./libraries/transformations.lib.php3'); // Transformations
$cfgRelation = PMA_getRelationsParam();
$foreigners  = ($cfgRelation['relwork'] ? PMA_getForeigners($db, $table) : FALSE);

$override_total = TRUE;

if (!isset($pos)) {
    $pos = 0;
}

$foreign_limit = 'LIMIT ' . $pos . ', ' . $per_page . ' ';
if (isset($foreign_navig) && $foreign_navig == $strShowAll) {
    unset($foreign_limit);
}

include('./libraries/get_foreign.lib.php3');
?>

<form action="browse_foreigners.php3" method="post">
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="field" value="<?php echo urlencode($field); ?>" />

<table width="100%">
<?php
if ($cfg['ShowAll'] && ($the_total > $per_page)) {
    $showall = '<input type="submit" name="foreign_navig" value="' . $strShowAll . '" />';
} else {
    $showall = '';
}

$session_max_rows = $per_page;
$pageNow = @floor($pos / $session_max_rows) + 1;
$nbTotalPage = @ceil($the_total / $session_max_rows);

if ($the_total > $per_page) {
    $gotopage = '<br />' . $GLOBALS['strPageNumber']
              . '<select name="goToPage" onChange="goToUrl(this, \'browse_foreigners.php3?field=' . urlencode($field) . '&amp;' . PMA_generate_common_url($db, $table) . '&amp;\');">';
    if ($nbTotalPage < 200) {
        $firstPage = 1;
        $lastPage  = $nbTotalPage;
    } else {
        $range = 20;
        $firstPage = ($pageNow - $range < 1 ? 1 : $pageNow - $range);
        $lastPage  = ($pageNow + $range > $nbTotalPage ? $nbTotalPage : $pageNow + $range);
    }

    for ($i=$firstPage; $i<=$lastPage; $i++){
        if ($i == $pageNow) {
            $selected = 'selected="selected"';
        } else {
            $selected = "";
        }
        $gotopage .= '                <option ' . $selected . ' value="' . (($i - 1) * $session_max_rows) . '">' . $i . '</option>' . "\n";
    }
} else {
    $gotopage = '';
}

$header = '    <tr>
    <th align="left" nowrap="nowrap">' . $strKey . '</th>
    <th>' . $strDescription . '</th>
    <td align="center" width="20%" valign="top">
        ' . $showall . '
        ' . $gotopage . '
    </td>
    <th>' . $strDescription . '</th>
    <th align="left" nowrap="nowrap">' . $strKey . '</th>
</tr>';

echo $header;

if (isset($disp) && $disp) {
    $count = 0;
    while ($relrow = @PMA_mysql_fetch_array($disp)) {
        $count++;
        $bgcolor = ($count % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

        $key   = $relrow[$foreign_field];
        if (strlen($relrow[$foreign_display]) <= $cfg['LimitChars']) {
            $value  = (($foreign_display != FALSE) ? htmlspecialchars($relrow[$foreign_display]) : '');
            $vtitle = '';
        } else {
            $vtitle = htmlspecialchars($relrow[$foreign_display]);
            $value  = (($foreign_display != FALSE) ? htmlspecialchars(substr($vtitle, 0, $cfg['LimitChars']) . '...') : '');
        }

        if ($count > $cfg['RepeatCells']) {
            echo $header;
            $count = -1;
        }
        $key_equals_data = isset($data) && $key == $data;
?>
    <tr>
        <td nowrap="nowrap" bgcolor="<?php echo $bgcolor; ?>"><?php echo ($key_equals_data ? '<b>' : '') . '<a href="#" title="' . $strUseThisValue . ($vtitle != '' ? ': ' . $vtitle : '') . '" onclick="formupdate(\'' . md5($field) . '\', \'' . htmlspecialchars($key) . '\'); return false;">' . htmlspecialchars($key) . '</a>' . ($key_equals_data ? '</b>' : ''); ?></td>
        <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ($key_equals_data ? '<b>' : '') .                 '<a href="#" title="' . $strUseThisValue . ($vtitle != '' ? ': ' . $vtitle : '') . '" onclick="formupdate(\'' . md5($field) . '\', \'' . htmlspecialchars($key) . '\'); return false;">' . $value . '</a>' . ($key_equals_data ? '</b>' : ''); ?></td>
        <td width="20%"><img src="images/spacer.gif" alt="" width="1" height="1"></td>
        <td bgcolor="<?php echo $bgcolor; ?>"><?php echo ($key_equals_data ? '<b>' : '') .                 '<a href="#" title="' . $strUseThisValue .  ($vtitle != '' ? ': ' . $vtitle : '') . '" onclick="formupdate(\'' . md5($field) . '\', \'' . htmlspecialchars($key) . '\'); return false;">' . $value . '</a>' . ($key_equals_data ? '</b>' : ''); ?></td>
        <td nowrap="nowrap" bgcolor="<?php echo $bgcolor; ?>"><?php echo ($key_equals_data ? '<b>' : '') . '<a href="#" title="' . $strUseThisValue .  ($vtitle != '' ? ': ' . $vtitle : '') . '" onclick="formupdate(\'' . md5($field) . '\', \'' . htmlspecialchars($key) . '\'); return false;">' . htmlspecialchars($key) . '</a>' . ($key_equals_data ? '</b>' : ''); ?></td>
    </tr>
<?php
        unset($key_equals_data);
    } // end while
}

echo $header;
?>
</table>
</form>

</body>
</html>

<?php
/**
 * Close MySql connections
 */
if (isset($dbh) && $dbh) {
    @mysql_close($dbh);
}
if (isset($userlink) && $userlink) {
    @mysql_close($userlink);
}


/**
 * Sends bufferized data
 */
if (isset($cfg['OBGzip']) && $cfg['OBGzip']
    && isset($ob_mode) && $ob_mode) {
     PMA_outBufferPost($ob_mode);
}
?>
