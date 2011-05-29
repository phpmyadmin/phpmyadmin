<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/mysql_charsets.lib.php';
require_once './libraries/tbl_select.lib.php';

$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'tbl_select.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
if ($GLOBALS['cfg']['PropertiesIconic'] == true) {
    $titles['Browse'] =
        '<img class="icon" width="16" height="16" src="' . $pmaThemeImage
        .'b_browse.png" alt="' . __('Browse foreign values') . '" title="'
        . __('Browse foreign values') . '" />';

    if ($GLOBALS['cfg']['PropertiesIconic'] === 'both') {
        $titles['Browse'] .= __('Browse foreign values');
    }
} else {
    $titles['Browse'] = __('Browse foreign values');
}
/**
 * Not selection yet required -> displays the selection form
 */
if (! isset($zoom_submit)) {
    // Gets some core libraries
    require_once './libraries/tbl_common.php';
    //$err_url   = 'tbl_select.php' . $err_url;
    $url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';

    /**
     * Gets tables informations
     */
    require_once './libraries/tbl_info.inc.php';

    /**
     * Displays top menu links
     */
    require_once './libraries/tbl_links.inc.php';

    if (! isset($goto)) {
        $goto = $GLOBALS['cfg']['DefaultTabTable'];
    }
    // Defines the url to return to in case of error in the next sql statement
    $err_url   = $goto . '?' . PMA_generate_common_url($db, $table);

    // Gets the list and number of fields

    $fields_array = PMA_tbl_getFields($table,$db);
    $fields_list = $fields_array[0];
    $fields_type = $fields_array[1];
    $fields_collation = $fields_array[2];
    $fields_null = $fields_array[3];
    $fields_cnt = count($fields_list);


    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);
    $flag = 1;
    $tbl_fields_type =array(); 
    $tbl_fields_collation =array(); 
    $tbl_fields_null =array(); 
    ?>

<fieldset id="fieldset_subtab">
<?php
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;

echo PMA_generate_html_tabs(PMA_tbl_getSubTabs(), $url_params);
?>

<?php /* Form for Zoom Search input */ 

if(isset($inputs) && ($inputs[0] != __('pma_null') || $inputs[1] != __('pma_null')))
{
	$flag = 2;
	for($i = 0 ; $i < 2 ; $i++)
	{
		if($inputs[$i] != __('pma_null'))
		{
			$key = array_search($inputs[$i],$fields_list);
			$tbl_fields_type[$i] = $fields_type[$key];
			$tbl_fields_collation[$i] = $fields_collation[$key];
			$tbl_fields_null[$i] = $fields_null[$key];
		}

	}

}

?>

<form method="post" action="tbl_zoom_select.php" name="zoomInputForm" id="zoom_search_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_select.php" />
<input type="hidden" name="flag" id="id_flag" value=<?php echo $flag; ?> />

<fieldset id="zoom_fieldset_table_qbe">
    <legend><?php echo __('Do a "query by example" (wildcard: "%") for two columns') ?></legend>
    <table class="data">
    <thead>
    <tr><th><?php echo __('Column'); ?></th>
        <th><?php echo __('Type'); ?></th>
        <th><?php echo __('Collation'); ?></th>
        <th><?php echo __('Operator'); ?></th>
        <th><?php echo __('Value'); ?></th>
    </tr>
    </thead>
    <tbody>
<?php
    $odd_row = true;
   
    for($i=1 ; $i<3 ; $i++){
?>
    <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <th><select name="inputs[]" id=<?php echo 'tableid_' . $i?> >
        <option value= <?php echo __('pma_null')?>><?php echo __('None');  ?> </option>
        <?php
        for ($j = 0; $j < $fields_cnt; $j++){
                if(isset($inputs[$i-1]) && $inputs[$i-1]==htmlspecialchars($fields_list[$j])){?>
                        <option value=<?php echo htmlspecialchars($fields_list[$j]);?> Selected>  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
                }
                else{ ?>
                        <option value=<?php echo htmlspecialchars($fields_list[$j]);?> >  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
                }
        } ?>
        </select></th>
        <td><?php if(isset($tbl_fields_type[$i-1]))echo $tbl_fields_type[$i-1]; ?></td>
        <td><?php if(isset($tbl_fields_collation[$i-1]))echo $tbl_fields_collation[$i-1]; ?></td>
	
	<td>
	<?php if(isset($inputs) && $inputs[$i-1] != __('pma_null')){ ?>
	<select name="zoomFunc[]">
        <?php

		if (strncasecmp($tbl_fields_type[$i-1], 'enum', 4) == 0) {
			foreach ($GLOBALS['cfg']['EnumOperators'] as $fc) {
				echo "\n" . '                        '
					. '<option value="' . htmlspecialchars($fc) . '">'
					. htmlspecialchars($fc) . '</option>';
			}
		} elseif (preg_match('@char|blob|text|set@i', $tbl_fields_type[$i-1])) {
			foreach ($GLOBALS['cfg']['TextOperators'] as $fc) {
				echo "\n" . '                        '
					. '<option value="' . htmlspecialchars($fc) . '">'
					. htmlspecialchars($fc) . '</option>';
			}
		} else {
			foreach ($GLOBALS['cfg']['NumOperators'] as $fc) {
				echo "\n" . '                        '
					. '<option value="' .  htmlspecialchars($fc) . '">'
					. htmlspecialchars($fc) . '</option>';
			}
		} // end if... else...
		if ($tbl_fields_null[$i-1]) {
			foreach ($GLOBALS['cfg']['NullOperators'] as $fc) {
				echo "\n" . '                        '
					. '<option value="' .  htmlspecialchars($fc) . '">'
					. htmlspecialchars($fc) . '</option>';
			}
		}
        ?>

        </select>
        </td>
        <td>
	<?php
	$field = $inputs[$i-1];

	$foreignData = PMA_getForeignData($foreigners, $field, false, '', '');

	if ($foreigners && isset($foreigners[$field]) && is_array($foreignData['disp_row'])) {
		// f o r e i g n    k e y s
		echo '            <select name="fields[' . ($i-1) . ']">' . "\n";
		// go back to first row

		// here, the 4th parameter is empty because there is no current
		// value of data for the dropdown (the search page initial values
		// are displayed empty)
		echo PMA_foreignDropdown($foreignData['disp_row'],
				$foreignData['foreign_field'],
				$foreignData['foreign_display'],
				'', $GLOBALS['cfg']['ForeignKeyMaxLimit']);
		echo '            </select>' . "\n";
	} elseif ($foreignData['foreign_link'] == true) {
		?>
			<input type="text" name="fields[<?php echo $i-1; ?>]"
			id="field_<?php echo md5($field); ?>[<?php echo $i-1; ?>]"
			class="textfield" />
			<script type="text/javascript">
			// <![CDATA[
			document.writeln('<a target="_blank" onclick="window.open(this.href, \'foreigners\', \'width=640,height=240,scrollbars=yes\'); return false" href="browse_foreigners.php?<?php echo PMA_generate_common_url($db, $table); ?>&amp;field=<?php echo urlencode($field); ?>&amp;fieldkey=<?php echo $i; ?>"><?php echo str_replace("'", "\'", $titles['Browse']); ?></a>');
		// ]]>
		</script>
			<?php
	} elseif (strncasecmp($tbl_fields_type[$i-1], 'enum', 4) == 0) {
		// e n u m s
		$enum_value=explode(', ', str_replace("'", '', substr($tbl_fields_type[$i-1], 5, -1)));
		$cnt_enum_value = count($enum_value);
		echo '            <select name="fields[' . ($i-1) . '][]"'
			.' multiple="multiple" size="' . min(3, $cnt_enum_value) . '">' . "\n";
		for ($j = 0; $j < $cnt_enum_value; $j++) {
			if(isset($fields[$i-1]) && is_array($fields[$i-1]) && in_array($enum_value[$j],$fields[$i-1])){
				echo '                <option value="' . $enum_value[$j] . '" Selected>'
					. $enum_value[$j] . '</option>';
			}
			else{
				echo '                <option value="' . $enum_value[$j] . '">'
					. $enum_value[$j] . '</option>';
			}
		} // end for
		echo '            </select>' . "\n";
	} else {
		// o t h e r   c a s e s
		$the_class = 'textfield';
		$type = $tbl_fields_type[$i-1];
		if ($type == 'date') {
			$the_class .= ' datefield';
		} elseif ($type == 'datetime' || substr($type, 0, 9) == 'timestamp') {
			$the_class .= ' datetimefield';
		}
		if(isset($fields[$i-1]) && is_string($fields[$i-1])){
		echo '            <input type="text" name="fields[' . ($i-1) . ']"'
			.' size="40" class="' . $the_class . '" id="field_' . ($i-1) . '" value = "' . $fields[$i-1] . '"/>' .  "\n";
		}
		else{
			echo '            <input type="text" name="fields[' . ($i-1) . ']"'
			.' size="40" class="' . $the_class . '" id="field_' . ($i-1) . '" />' .  "\n";
		}
	};
	}
	?>
            <input type="hidden" name="types[<?php echo ($i-1); ?>]"
                value="<?php if(isset($tbl_fields_type[$i-1]))echo $tbl_fields_type[$i-1]; ?>" />
            <input type="hidden" name="collations[<?php echo $i; ?>]"
                value="<?php if(isset($tbl_fields_collation[$i-1]))echo $tbl_fields_collation[$i-1]; ?>" />
        </td>
    </tr>

<?php
    }
?>
    </table>
</fieldset>
<fieldset class="tblFooters">
    <input type="hidden" name="max_number_of_fields"
        value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="zoom_submit" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>

<div id="sqlqueryresults"></div>
    <?php
    require './libraries/footer.inc.php';
?>

</fieldset>

<?php
}

else {

    // Unlike tbl_search page this part builds two queries, Query1 for the search criteria on 1st column and Query2 for the other column. This has to be done because user can select two same columns having different criteria. 

	for($i = 0 ;$i<2;$i++){

	    $sql_query = 'SELECT ';

	    // Add the colums to be selected
	
	    $sql_query .= PMA_backquote($inputs[$i]);

	    //Add the table
	
	    $sql_query .= ' FROM ' . PMA_backquote($table);

	    // The where clause
	    $charsets = array();
	    $cnt_func = count($zoomFunc[$i]);
	    reset($zoomFunc[$i]);
	    $func_type = $zoomFunc[$i];
	    list($charsets[$i]) = explode('_', $collations[$i]);
	    $unaryFlag =  (isset($GLOBALS['cfg']['UnaryOperators'][$func_type]) && $GLOBALS['cfg']['UnaryOperators'][$func_type] == 1) ? true : false;
            $w = PMA_tbl_search_getWhereClause($fields[$i],$inputs[$i], $types[$i], $collations[$i], $func_type, $unaryFlag);
	    if ($w != '') {
		    $sql_query .= ' WHERE ' . $w;
	    }
	    print $sql_query."<br>";
	}
}

?>
