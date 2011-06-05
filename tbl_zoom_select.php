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

$titles['Browse'] = PMA_tbl_setTitle($GLOBALS['cfg']['PropertiesIconic'], $pmaThemeImage);


/**
 * Not selection yet required -> displays the selection form
 */
if (true) {
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


if(isset($zoom_submit)) {

    // Unlike tbl_search page this part builds two queries, Query1 for the search criteria on 1st column and Query2 for the other column. This has to be done because user can select two same columns having different criteria. 
    $chart ='';
    $cx = $cy = $w = array();
    $sql_query = 'SELECT ';

    // Add the colums to be selected
    $columns = $inputs;
    $columns = PMA_backquote($columns);
    $sql_query .= implode(', ', $columns);
    

    //Add the table
	
    $sql_query .= ' FROM ' . PMA_backquote($table);
    for($i = 0 ; $i < 2 ; $i++){

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
        //print_r($w);
        if ($w) {
            $sql_query .= ' WHERE ' . implode(' AND ', $w);
        }
    $result     = PMA_DBI_query( $sql_query . ";" , null, PMA_DBI_QUERY_STORE);
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $cx[] = $row[$inputs[0]];
	$cy[] = $row[$inputs[1]];
    }

    for($j = 0 ; $j < count($cx) ; $j++){
	$cx[$j] = 20 + 500 * $cx[$j]/ max($cx);
	$cy[$j] = 300 * $cy[$j]/ max($cy) -20 ;
    }
    $chart .= '
            <div style="float: right">
            <?xml version="1.0" standalone="no"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" 
            "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">

            <svg width="540" height="325" version="1.1"
            xmlns="http://www.w3.org/2000/svg">

            <defs>
            <marker id = "MidMarker" viewBox = "0 0 10 10" refX = "5" refY = "5" markerUnits = "strokeWidth" markerWidth = "3" markerHeight = "3" stroke = "lightblue" stroke-width = "2" fill = "none" orient = "auto">
            <path d = "M 0 0 L 10 10 M 0 10 L 10 0"/>
            </marker>
            <path id="myTextPath1"
              d="M10,180 L10,50"/>
            <path id="myTextPath2"
              d="M250,315 L370,315"/>
            </defs>
            <g id ="zoomplot" >
            <text x="6" y="190"  style="font-family: Arial; font-size  : 54; stroke:none; fill:#000000;" >
                <textPath xlink:href="#myTextPath1" >';
    $chart .= $inputs[0];       
    $chart .= '</textPath>
            </text>
            <text x="250" y="315"  style="font-family: Arial; font-size  : 54; stroke:none; fill:#000000;" >
                <textPath xlink:href="#myTextPath2" >';
    $chart .= $inputs[1];       
    $chart .= '</textPath>
            </text>

            <line x1="20" y1="300" x2="520" y2="300" style="stroke: black" marker-end = "url(#MidMarker)"/>
            <line x1="20" y1="300" x2="20" y2="0" style="stroke: black"/>';
            
            for($j = 0 ; $j < count($cx) ; $j++){
          	 $chart .= '<circle cx="' . $cx[$j] .'" cy="' . $cy[$j] . '" r=".1"  fill="red" stroke="blue" />';
            }

    $chart .='</g>
            </svg>
            </div>';

?>

<?php
}
?>
<form method="post" action="tbl_zoom_select.php" name="zoomInputForm" id="zoom_search_form" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : ''); ?>>
<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
<input type="hidden" name="goto" value="<?php echo $goto; ?>" />
<input type="hidden" name="back" value="tbl_select.php" />
<input type="hidden" name="flag" id="id_flag" value=<?php echo $flag; ?> />

<fieldset id="zoom_fieldset_table_qbe">
<?php if(isset($zoom_submit)){ 

	echo $chart; ?>
	
<?php } ?>
    <legend><?php echo __('Do a "query by example" (wildcard: "%") for two columns') ?></legend>
    <table class="data">
    <?php echo PMA_tbl_setTableHeader();?>
    <tbody>
<?php
    $odd_row = true;
   
    for($i = 0 ; $i < 2 ; $i++){
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
		        echo "\n" . '                        '
		        . '<option value="' . htmlspecialchars($fc) . '">'
		        . htmlspecialchars($fc) . '</option>';
		    }
	        } elseif (preg_match('@char|blob|text|set@i', $tbl_fields_type[$i])) {
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
	    
                if ($tbl_fields_null[$i]) {
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
 
        <input type="hidden" name="types[<?php echo $i; ?>]"
            value="<?php if(isset($tbl_fields_type[$i]))echo $tbl_fields_type[$i]; ?>" />
        <input type="hidden" name="collations[<?php echo $i; ?>]"
            value="<?php if(isset($tbl_fields_collation[$i]))echo $tbl_fields_collation[$i]; ?>" />
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


