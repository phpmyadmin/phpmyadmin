<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display selection for relational field values
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets a core script and starts output buffering work
 */
require_once './libraries/common.inc.php';

PMA_checkParameters(array('db', 'table', 'field'));

require_once './libraries/ob.lib.php';
PMA_outBufferPre();

require_once './libraries/header_http.inc.php';

/**
 * Displays the frame
 */
$per_page = 200;
require_once './libraries/relation.lib.php'; // foreign keys
require_once './libraries/transformations.lib.php'; // Transformations
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

$foreignData = PMA_getForeignData($foreigners, $field, $override_total, isset($foreign_filter) ? $foreign_filter : '', $foreign_limit);

if (isset($pk)) {
    $pk_uri = '&amp;pk=' . urlencode($pk);
    ?>
<input type="hidden" name="pk" value="<?php echo htmlspecialchars($pk); ?>" />
    <?php
} else {
    $pk_uri = '';
}

$gotopage = '';
$showall = '';

if (is_array($foreignData['disp_row'])) {

    if ($cfg['ShowAll'] && ($foreignData['the_total'] > $per_page)) {
        $showall = '<input type="submit" name="foreign_navig" value="' . $strShowAll . '" />';
    }

    $session_max_rows = $per_page;
    $pageNow = @floor($pos / $session_max_rows) + 1;
    $nbTotalPage = @ceil($foreignData['the_total'] / $session_max_rows);

    if ($foreignData['the_total'] > $per_page) {
        $gotopage = PMA_pageselector(
                      'browse_foreigners.php?field='    . urlencode($field) .
                                       '&amp;'          . PMA_generate_common_url($db, $table)
                                                        . $pk_uri .
                                       '&amp;fieldkey=' . (isset($fieldkey) ? urlencode($fieldkey) : '') .
                                       '&amp;foreign_filter=' . (isset($foreign_filter) ? urlencode($foreign_filter) : '') .
                                       '&amp;',
                      $session_max_rows,
                      $pageNow,
                      $nbTotalPage,
                      200,
                      5,
                      5,
                      20,
                      10,
                      $GLOBALS['strPageNumber']
                    );
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="<?php echo $available_languages[$lang][2]; ?>"
    lang="<?php echo $available_languages[$lang][2]; ?>"
    dir="<?php echo $text_dir; ?>">

<head>
    <title>phpMyAdmin</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
    <link rel="stylesheet" type="text/css"
        href="phpmyadmin.css.php?<?php echo PMA_generate_common_url('', ''); ?>&amp;js_frame=right&amp;nocache=<?php echo $_SESSION['PMA_Config']->getThemeUniqueValue(); ?>" />
    <script src="./js/functions.js" type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[
    self.focus();
    function formupdate(field, key) {
        if (opener && opener.document && opener.document.insertForm) {
            var field = 'field_' + field;

            <?php if (isset($pk)) { ?>
            var element_name = field + '[multi_edit][<?php echo htmlspecialchars($pk); ?>][]';
            <?php } else { ?>
            var element_name = field + '[]';
            <?php } ?>

            <?php if (isset($fieldkey) && is_numeric($fieldkey)) { ?>
            var element_name_alt = field + '[<?php echo $fieldkey; ?>]';
            <?php } else { ?>
            var element_name_alt = field + '[0]';
            <?php } ?>

            if (opener.document.insertForm.elements[element_name]) {
                // Edit/Insert form
                opener.document.insertForm.elements[element_name].value = key;
                self.close();
                return false;
            } else if (opener.document.insertForm.elements[element_name_alt]) {
                // Search form
                opener.document.insertForm.elements[element_name_alt].value = key;
                self.close();
                return false;
            }
        }

        alert('<?php echo PMA_jsFormat($strWindowNotFound); ?>');
    }
    //]]>
    </script>
</head>

<body id="body_browse_foreigners">

<form action="browse_foreigners.php" method="post">
<fieldset>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="field" value="<?php echo htmlspecialchars($field); ?>" />
<input type="hidden" name="fieldkey"
    value="<?php echo isset($fieldkey) ? htmlspecialchars($fieldkey) : ''; ?>" />
<?php if (isset($pk)) { ?>
<input type="hidden" name="pk" value="<?php echo htmlspecialchars($pk); ?>" />
<?php } ?>
<span class="formelement">
    <label for="input_foreign_filter"><?php echo $strSearch . ':'; ?></label>
    <input type="text" name="foreign_filter" id="input_foreign_filter"
        value="<?php echo isset($foreign_filter) ? htmlspecialchars($foreign_filter) : ''; ?>" />
    <input type="submit" name="submit_foreign_filter" value="<?php echo $strGo;?>" />
</span>
<span class="formelement">
    <?php echo $gotopage; ?>
</span>
<span class="formelement">
    <?php echo $showall; ?>
</span>
</fieldset>
</form>

<table width="100%">
<?php
if (is_array($foreignData['disp_row'])) {
    $header = '<tr>
        <th>' . $strKeyname . '</th>
        <th>' . $strDescription . '</th>
        <td width="20%"></td>
        <th>' . $strDescription . '</th>
        <th>' . $strKeyname . '</th>
    </tr>';

    echo '<thead>' . $header . '</thead>' . "\n"
        .'<tfoot>' . $header . '</tfoot>' . "\n"
        .'<tbody>' . "\n";

    $values = array();
    $keys   = array();
    foreach ($foreignData['disp_row'] as $relrow) {
        if ($foreignData['foreign_display'] != FALSE) {
            $values[] = $relrow[$foreignData['foreign_display']];
        } else {
            $values[] = '';
        }

        $keys[] = $relrow[$foreignData['foreign_field']];
    }

    asort($keys);

    $hcount = 0;
    $odd_row = true;
    $val_ordered_current_row = 0;
    $val_ordered_current_equals_data = false;
    $key_ordered_current_equals_data = false;
    foreach ($keys as $key_ordered_current_row => $value) {
    //for ($i = 0; $i < $count; $i++) {
        $hcount++;

        if ($cfg['RepeatCells'] > 0 && $hcount > $cfg['RepeatCells']) {
            echo $header;
            $hcount = 0;
            $odd_row = true;
        }

        $key_ordered_current_key = $keys[$key_ordered_current_row];
        $key_ordered_current_val = $values[$key_ordered_current_row];

        $val_ordered_current_key = $keys[$val_ordered_current_row];
        $val_ordered_current_val = $values[$val_ordered_current_row];

        $val_ordered_current_row++;

        if (PMA_strlen($val_ordered_current_val) <= $cfg['LimitChars']) {
            $val_ordered_current_val = htmlspecialchars($val_ordered_current_val);
            $val_ordered_current_val_title = '';
        } else {
            $val_ordered_current_val_title =
                htmlspecialchars($val_ordered_current_val);
            $val_ordered_current_val =
                htmlspecialchars(PMA_substr($val_ordered_current_val, 0,
                    $cfg['LimitChars']) . '...');
        }
        if (PMA_strlen($key_ordered_current_val) <= $cfg['LimitChars']) {
            $key_ordered_current_val = htmlspecialchars($key_ordered_current_val);
            $key_ordered_current_val_title = '';
        } else {
            $key_ordered_current_val_title =
                htmlspecialchars($key_ordered_current_val);
            $key_ordered_current_val =
                htmlspecialchars(PMA_substr($key_ordered_current_val, 0,
                    $cfg['LimitChars']) . '...');
        }

        if (! empty($data)) {
            $val_ordered_current_equals_data = $val_ordered_current_key == $data;
            $key_ordered_current_equals_data = $key_ordered_current_key == $data;
        }

        ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <td nowrap="nowrap">
        <?php
        echo ($key_ordered_current_equals_data ? '<strong>' : '')
            .'<a href="#" title="' . $strUseThisValue
            . ($key_ordered_current_val_title != '' ? ': ' . $key_ordered_current_val_title : '') . '"'
            .' onclick="formupdate(\'' . md5($field) . '\', \''
            . PMA_jsFormat($key_ordered_current_key, false) . '\'); return false;">'
            .htmlspecialchars($key_ordered_current_key) . '</a>' . ($key_ordered_current_equals_data ? '</strong>' : '');
        ?></td>
        <td>
        <?php
        echo ($key_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . $strUseThisValue . ($key_ordered_current_val_title != '' ? ': '
            . $key_ordered_current_val_title : '') . '" onclick="formupdate(\''
            . md5($field) . '\', \'' . PMA_jsFormat($key_ordered_current_key, false) . '\'); return false;">'
            . $key_ordered_current_val . '</a>' . ($key_ordered_current_equals_data ? '</strong>' : '');
        ?></td>
        <td width="20%">
            <img src="<?php echo $GLOBALS['pmaThemeImage'] . 'spacer.png'; ?>"
                alt="" width="1" height="1"></td>

        <td>
        <?php
        echo ($val_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . $strUseThisValue .  ($val_ordered_current_val_title != '' ? ': '
            . $val_ordered_current_val_title : '') . '" onclick="formupdate(\'' . md5($field)
            . '\', \'' . PMA_jsFormat($val_ordered_current_key, false) . '\'); return false;">'
            . $val_ordered_current_val . '</a>' . ($val_ordered_current_equals_data ? '</strong>' : '');
        ?></td>
        <td nowrap="nowrap">
        <?php
        echo ($val_ordered_current_equals_data ? '<strong>' : '') . '<a href="#" title="'
        . $strUseThisValue .  ($val_ordered_current_val_title != '' ? ': ' . $val_ordered_current_val_title : '')
        . '" onclick="formupdate(\'' . md5($field) . '\', \''
        . PMA_jsFormat($val_ordered_current_key, false) . '\'); return false;">' . htmlspecialchars($val_ordered_current_key)
        . '</a>' . ($val_ordered_current_equals_data ? '</strong>' : '');
        ?></td>
    </tr>
        <?php
    } // end while
}
?>
</tbody>
</table>

</body>
</html>
