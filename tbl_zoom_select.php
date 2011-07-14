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
require_once './libraries/relation.lib.php';
require_once './libraries/tbl_info.inc.php';

$GLOBALS['js_include'][] = 'makegrid.js';
$GLOBALS['js_include'][] = 'sql.js';
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'tbl_zoom_plot.js';
$GLOBALS['js_include'][] = 'highcharts/highcharts.js';
/* Files required for chart exporting */
$GLOBALS['js_include'][] = 'highcharts/exporting.js';
$GLOBALS['js_include'][] = 'canvg/canvg.js';
$GLOBALS['js_include'][] = 'canvg/rgbcolor.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';


$titles['Browse'] = PMA_tbl_setTitle($GLOBALS['cfg']['PropertiesIconic'], $pmaThemeImage);
/**
 * Not selection yet required -> displays the selection form
 */

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

    list($fields_list, $fields_type, $fields_collation, $fields_null) = PMA_tbl_getFields($table,$db);
    $fields_cnt = count($fields_list);

    // retrieve keys into foreign fields, if any
    // check also foreigners even if relwork is FALSE (to get
    // foreign keys from innodb)
    $foreigners = PMA_getForeigners($db, $table);
    $flag = 1;
    $tbl_fields_type = $tbl_fields_collation = $tbl_fields_null = array();
    $maxPlotlLimit = $GLOBALS['cfg']['maxRowPlotLimit']; 

    ?>

<div id="sqlqueryresults"></div>
<fieldset id="fieldset_subtab">
<?php
$url_params = array();
$url_params['db']    = $db;
$url_params['table'] = $table;
echo PMA_generate_html_tabs(PMA_tbl_getSubTabs(), $url_params);

/**
 *  Set the field name,type,collation and whether null on select of a coulmn
 */
if(isset($inputs) && ($inputs[0] != __('pma_null') || $inputs[1] != __('pma_null')))
{
    $flag = 2;
    for($i = 0 ; $i < 4 ; $i++)
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

<?php
  
/*
 * Form for input criteria
 */

?>
<form method="post" action="tbl_zoom_select.php" name="zoomInputForm" id="zoom_search_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_zoom_select.php" />
<input type="hidden" name="flag" id="id_flag" value=<?php echo $flag; ?> />


<fieldset id="inputSection">

<legend><?php echo __('Do a "query by example" (wildcard: "%") for two columns') ?></legend>
<table class="data">
<?php echo PMA_tbl_setTableHeader();?>
<tbody>
<?php
    $odd_row = true;
   
for($i = 0 ; $i < 4 ; $i++){
    
    if($i == 2){
	echo "<tr><td>";
        echo __("Additional search criteria");
	echo "</td><tr>";
    }               

?>
    <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <th><select name="inputs[]" id=<?php echo 'tableid_' . $i?> >
        <option value= <?php echo __('pma_null')?>><?php echo __('None');  ?> </option>
        <?php
        for ($j = 0 ; $j < $fields_cnt ; $j++){
            if(isset($inputs[$i]) && $inputs[$i] == htmlspecialchars($fields_list[$j])){?>
                <option value=<?php echo htmlspecialchars($fields_list[$j]);?> Selected>  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
            }
            else{ ?>
                <option value=<?php echo htmlspecialchars($fields_list[$j]);?> >  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
            }
        } ?>
        </select></th>
        <td><?php if(isset($tbl_fields_type[$i]))echo $tbl_fields_type[$i]; ?></td>
        <td><?php if(isset($tbl_fields_collation[$i]))echo $tbl_fields_collation[$i]; ?></td>
	
	<td>
	<?php if(isset($inputs) && $inputs[$i] != __('pma_null')){ ?>
	    <select name="zoomFunc[]">
            <?php

	        if (strncasecmp($tbl_fields_type[$i], 'enum', 4) == 0) {
	            foreach ($GLOBALS['cfg']['EnumOperators'] as $fc) {
			if(isset($zoomFunc[$i]) && $zoomFunc[$i] == htmlspecialchars($fc)){
		            echo "\n" . '                        '
		            . '<option value="' . htmlspecialchars($fc) . '" Selected>'
		            . htmlspecialchars($fc) . '</option>';
			}
			else {
		            echo "\n" . '                        '
		            . '<option value="' . htmlspecialchars($fc) . '">'
		            . htmlspecialchars($fc) . '</option>';
			}
		    }
	        } elseif (preg_match('@char|blob|text|set@i', $tbl_fields_type[$i])) {
	            foreach ($GLOBALS['cfg']['TextOperators'] as $fc) {
			if(isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc){
		            echo "\n" . '                        '
		            . '<option value="' . htmlspecialchars($fc) . '" Selected>'
		            . htmlspecialchars($fc) . '</option>';
			}
			else {
		            echo "\n" . '                        '
		            . '<option value="' . htmlspecialchars($fc) . '">'
		            . htmlspecialchars($fc) . '</option>';
			}
		    }
	    	} else {
	            foreach ($GLOBALS['cfg']['NumOperators'] as $fc) {
			if(isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc){
		            echo "\n" . '                        '
		    	    . '<option value="' .  htmlspecialchars($fc) . '" Selected>'
		    	    . htmlspecialchars($fc) . '</option>';
			}
			else {
		            echo "\n" . '                        '
		    	    . '<option value="' .  htmlspecialchars($fc) . '">'
		    	    . htmlspecialchars($fc) . '</option>';
			}
		    }
	        } // end if... else...
	    
                if ($tbl_fields_null[$i]) {
	            foreach ($GLOBALS['cfg']['NullOperators'] as $fc) {
			if(isset($zoomFunc[$i]) && $zoomFunc[$i] == $fc){
		            echo "\n" . '                        '
		    	    . '<option value="' .  htmlspecialchars($fc) . '" Selected>'
		    	    . htmlspecialchars($fc) . '</option>';
			}
			else {
		            echo "\n" . '                        '
		    	    . '<option value="' .  htmlspecialchars($fc) . '">'
		    	    . htmlspecialchars($fc) . '</option>';
			}
	            }
	        }
            ?>
            </select>
            </td>
            <td>
	    <?php
	    $field = $inputs[$i];
	
            $foreignData = PMA_getForeignData($foreigners, $field, false, '', '');
	    if (isset($fields))
	        echo PMA_getForeignFields_Values($foreigners, $foreignData, $field, $tbl_fields_type, $i ,$db, $table, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], $fields);
	    else
	        echo PMA_getForeignFields_Values($foreigners, $foreignData, $field, $tbl_fields_type, $i ,$db, $table, $titles, $GLOBALS['cfg']['ForeignKeyMaxLimit'], '');
	
        }
        else{ ?>
 
       </td><td></td>

        <?php } ?>

        </td>
    </tr>

    <input type="hidden" name="types[<?php echo $i; ?>]"
        value="<?php if(isset($tbl_fields_type[$i]))echo $tbl_fields_type[$i]; ?>" />
    <input type="hidden" name="collations[<?php echo $i; ?>]"
        value="<?php if(isset($tbl_fields_collation[$i]))echo $tbl_fields_collation[$i]; ?>" />



<?php
    }//end for
?>
    </table>

    <?php
    /*
     * Other inputs like data label and mode go after selection of column criteria
     */

    //Set default datalabel if not selected
    if(isset($zoom_submit) && $inputs[0] != __('pma_null') && $inputs[1] != __('pma_null')) {
        if ($dataLabel == '') 
	    $dataLabel = PMA_getDisplayField($db,$table);
    }
    ?>
    <table>
    <tr><td><label for="label"><?php echo __("Data Label"); ?></label>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp</td>
    <td><select name="dataLabel" id='dataLabel' >
        <option value = ''> <?php echo __('None');  ?> </option>
        <?php
        for ($j = 0 ; $j < $fields_cnt ; $j++){
            if(isset($dataLabel) && $dataLabel == htmlspecialchars($fields_list[$j])){?>
                <option value=<?php echo htmlspecialchars($fields_list[$j]);?> Selected>  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
            }
            else{ ?>
                <option value=<?php echo htmlspecialchars($fields_list[$j]);?> >  <?php echo htmlspecialchars($fields_list[$j]);?></option>
        <?php
            }
        } ?>
    </select>
    </td></tr>
    </table>

</fieldset>
<fieldset class="tblFooters">
    <input type="hidden" name="max_number_of_fields"
        value="<?php echo $fields_cnt; ?>" />
    <input type="submit" name="zoom_submit" id="zoomSubmitId" value="<?php echo __('Go'); ?>" />
</fieldset>
</form>

<?php 

/*
 * Handle the input criteria and gerate the query result
 * Form for displaying query results
 */
if(isset($zoom_submit) && $inputs[0] != __('pma_null') && $inputs[1] != __('pma_null')) {

    /*
     * Query generation part
     */
    $w = $data = array(); 
    $sql_query = 'SELECT *';

    //Add the table
	
    $sql_query .= ' FROM ' . PMA_backquote($table);
    for($i = 0 ; $i < 4 ; $i++){
        if($inputs[$i] == __('pma_null'))
	    continue;
        $tmp = array();
        // The where clause
        $charsets = array();
        $cnt_func = count($zoomFunc[$i]);
        $func_type = $zoomFunc[$i];
        list($charsets[$i]) = explode('_', $collations[$i]);
        $unaryFlag =  (isset($GLOBALS['cfg']['UnaryOperators'][$func_type]) && $GLOBALS['cfg']['UnaryOperators'][$func_type] == 1) ? true : false;
        $whereClause = PMA_tbl_search_getWhereClause($fields[$i],$inputs[$i], $types[$i], $collations[$i], $func_type, $unaryFlag);
        if($whereClause)
                $w[] = $whereClause;

        } // end for
        if ($w) {
            $sql_query .= ' WHERE ' . implode(' AND ', $w);
        }
	$sql_query .= ' LIMIT ' . $maxPlotlLimit;

    /*
     * Query execution part
     */
    $result     = PMA_DBI_query( $sql_query . ";" , null, PMA_DBI_QUERY_STORE);
    $fields_meta = PMA_DBI_get_fields_meta($result);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        //Need a row with indexes as 0,1,2 for the PMA_getUniqueCondition hence using a temporary array
	$tmpRow = array();
	foreach($row as $val)
	    $tmpRow[] = $val;
        //Get unique conditon on each row (will be needed for row update)
	$uniqueCondition = PMA_getUniqueCondition($result, $fields_cnt, $fields_meta, $tmpRow, true);
	//Append it to row array as where_clause
	$row['where_clause'] = $uniqueCondition[0];
        $data[] = $row;
    }	

?>

<?php
    /*
     * Form for displaying point data and also the scatter plot
     */
?>   
    <form method="post" action="tbl_zoom_select.php" name="displayResultForm" id="zoom_display_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="back" value="tbl_zoom_select.php" />

    <div id="overlay" class="web_dialog_overlay"></div>
    <div id="dialog" class="web_dialog" style="display:none">
    <fieldset id="displaySection">
        <legend><?php echo __('Browse/Edit the points') ?></legend>
        <?php
            //JSON encode the data(query result)
            if(isset($zoom_submit) && !empty($data)){ ?>
                <div id='resizer' style="width:600px;height:400px;float:right">
	        <?php if (isset($data)) ?><center> <a href="#" onClick="displayHelp();"><?php echo __('How to use'); ?></a> </center>
                <div id="querydata" style="display:none">
                    <?php if(isset($data)) echo json_encode($data); ?>
                </div>
	        <div id="querychart" style="float:right"></div>
                </div>
                <?php 
	    } ?>
    
    <fieldset id='dataDisplay'>
        <legend><?php echo __('Data point content') ?></legend>
        <fieldset>
        <table class="data">
            <thead>
                <tr>
		<th> <?php echo __('Column'); ?> </th>
		<th> <?php echo __('Null'); ?> </th>
                <th> <?php echo __('Value'); ?> </th>
		</tr>
            </thead>
            <tbody>
            <?php
                $odd_row = true;
        	for ($i = 4; $i < $fields_cnt + 4 ; $i++) {
            	    $tbl_fields_type[$i] = $fields_type[$i - 4];
            	    $fieldpopup = $fields_list[$i - 4];
            	    $foreignData = PMA_getForeignData($foreigners, $fieldpopup, false, '', '');
                    ?>
                    <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
                        <th><?php echo htmlspecialchars($fields_list[$i - 4]); ?></th>
		    	<th><?php echo '<input type="checkbox" class="checkbox_null" name="fields_null[ ' . $i . ' ]" id="fields_null_id_' . $i . '" />'; ?></th>
                        <th><?php echo PMA_getForeignFields_Values($foreigners, $foreignData, $fieldpopup, $tbl_fields_type, $i, $db, $table, $titles,$GLOBALS['cfg']['ForeignKeyMaxLimit'], '' ); ?> </th>
                    </tr>
                    <?php 
		} ?>
            </tbody>
        </table>
        </fieldset>
        <fieldset class="tblFooters">
            <input type="submit" id="buttonID" name="edit_point" value="<?php echo __('Submit'); ?>" />
        </fieldset>
    </fieldset>

    </fieldset>
    </div>
    <input type="hidden" id="queryID" name="sql_query" />
    </form>
    </fieldset>
    <?php 
}
?>

    <?php
    require './libraries/footer.inc.php';
?>
