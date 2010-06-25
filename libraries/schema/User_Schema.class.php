<?php
/**
 * This Class will interact with the user to gather the information
 * about their tables for which they want to export the relational schema
 */
	 
class PMA_User_Schema
{
/*	private $_exportType;
	public function __construct()
	{
		$this->_exportType='pdf'; // default export type
	}
	
	public function setExportType($type)
	{
		$this->_exportType=$type;
	}
	
	public function getExportType()
	{
		return $this->_exportType;
	}*/
	
	/**
	 * This function will process the user input
	 * 
	 */
	public function userInputProcess($do)
	{
	global $action_choose,$chpage,$db,$cfgRelation,$cfg,$auto_layout_foreign,$auto_layout_internal,$newpage,$c_table_rows,$query_default_option;
    // Now is the time to work on all changes
		//echo $do;
    if (isset($do)) {
        switch ($do) {
            case 'selectpage':
                if ($action_choose=="1") {
                    $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
                    PMA_query_as_controluser($ch_query, FALSE, $query_default_option);

                    $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                              .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                              .   ' AND   page_nr = \'' . PMA_sqlAddslashes($chpage) . '\'';
                    PMA_query_as_controluser($ch_query, FALSE, $query_default_option);

                    unset($chpage);
                }
                break;
            case 'createpage':
                
				$pdf_page_number = PMA_REL_create_page($newpage, $cfgRelation, $db, $query_default_option);
				
                // A u t o m a t i c    l a y o u t
                // ================================
                if (isset($auto_layout_internal) || isset($auto_layout_foreign)) {
                    $all_tables = array();
                }

                if (isset($auto_layout_foreign)) {
                    // get the tables list
                    $tables = PMA_DBI_get_tables_full($db);
                    // find the ones who support FOREIGN KEY; it's not
                    // important that we group together InnoDB tables
                    // and PBXT tables, as this logic is just to put
                    // the tables on the layout, not to determine relations
                    $foreignkey_tables = array();
                    foreach($tables as $table_name => $table_properties) {
                        if (PMA_foreignkey_supported($table_properties['ENGINE'])) {
                            $foreignkey_tables[] = $table_name;
                        }
                    }
                    $all_tables = $foreignkey_tables;
                    // could be improved by finding the tables which have the
                    // most references keys and placing them at the beginning
                    // of the array (so that they are all center of schema)
                    unset($tables, $foreignkey_tables);
                } // endif auto_layout_foreign

                if (isset($auto_layout_internal)) {
                    // get the tables that have relations, by descending
                    // number of links
                    $master_tables = 'SELECT COUNT(master_table), master_table'
                                . ' FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                                . ' WHERE master_db = \'' . $db . '\''
                                . ' GROUP BY master_table'
                                . ' ORDER BY ' . PMA_backquote('COUNT(master_table)') . ' DESC ';
                    $master_tables_rs = PMA_query_as_controluser($master_tables, FALSE, $query_default_option);
                    if ($master_tables_rs && PMA_DBI_num_rows($master_tables_rs) > 0) {
                        // first put all the master tables at beginning
                        // of the list, so they are near the center of
                        // the schema
                        while (list(, $master_table) = PMA_DBI_fetch_row($master_tables_rs)) {
                            $all_tables[] = $master_table;
                        }

                        // then for each master, add its foreigns into an array
                        // of foreign tables, if not already there
                        // (a foreign might be foreign for more than
                        // one table, and might be a master itself)

                        $foreign_tables = array();
                        foreach ($all_tables as $master_table) {
                            $foreigners = PMA_getForeigners($db, $master_table);
                            foreach ($foreigners as $foreigner) {
                                if (!in_array($foreigner['foreign_table'], $foreign_tables)) {
                                    $foreign_tables[] = $foreigner['foreign_table'];
                                }
                            }
                        }

                        // then merge the arrays
                        foreach ($foreign_tables as $foreign_table) {
                            if (!in_array($foreign_table, $all_tables)) {
                                $all_tables[] = $foreign_table;
                            }
                        }
                    } // endif there are master tables
                } // endif auto_layout_internal

                if (isset($auto_layout_internal) || isset($auto_layout_foreign)) {
                    // now generate the coordinates for the schema,
                    // in a clockwise spiral

                    $pos_x = 300;
                    $pos_y = 300;
                    $delta = 110;
                    $delta_mult = 1.10;
                    $direction = "right";
                    foreach ($all_tables as $current_table) {

                        // save current table's coordinates
                        $insert_query = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                      . '(db_name, table_name, pdf_page_number, x, y) '
                                      . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($current_table) . '\',' . $pdf_page_number . ',' . $pos_x . ',' . $pos_y . ')';
                        PMA_query_as_controluser($insert_query, FALSE, $query_default_option);

                        // compute for the next table
                        switch ($direction) {
                            case 'right':
                                $pos_x += $delta;
                                $direction = "down";
                                $delta *= $delta_mult;
                                break;
                            case 'down':
                                $pos_y += $delta;
                                $direction = "left";
                                $delta *= $delta_mult;
                                break;
                            case 'left':
                                $pos_x -= $delta;
                                $direction = "up";
                                $delta *= $delta_mult;
                                break;
                            case 'up':
                                $pos_y -= $delta;
                                $direction = "right";
                                $delta *= $delta_mult;
                                break;
                        } // end switch
                    } // end foreach
                } // end if some auto-layout to do

                $chpage = $pdf_page_number;

                break;

            case 'edcoord':
                for ($i = 0; $i < $c_table_rows; $i++) {
                    $arrvalue = 'c_table_' . $i;
					global $$arrvalue;
                    $arrvalue = $$arrvalue;
                    if (!isset($arrvalue['x']) || $arrvalue['x'] == '') {
                        $arrvalue['x'] = 0;
                    }
                    if (!isset($arrvalue['y']) || $arrvalue['y'] == '') {
                        $arrvalue['y'] = 0;
                    }
                    if (isset($arrvalue['name']) && $arrvalue['name'] != '--') {
                        $test_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                                    .   ' WHERE db_name = \'' .  PMA_sqlAddslashes($db) . '\''
                                    .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                    .   ' AND   pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
                       // echo $test_query;
						$test_rs    = PMA_query_as_controluser($test_query, FALSE, $query_default_option);
                        if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
                            if (isset($arrvalue['delete']) && $arrvalue['delete'] == 'y') {
                                $ch_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
                            } else {
                                $ch_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . 'SET x = ' . $arrvalue['x'] . ', y= ' . $arrvalue['y']
                                          .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                                          .   ' AND   table_name = \'' . PMA_sqlAddslashes($arrvalue['name']) . '\''
                                          .   ' AND   pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
                            }
                        } else {
                            $ch_query     = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' '
                                          . '(db_name, table_name, pdf_page_number, x, y) '
                                          . 'VALUES (\'' . PMA_sqlAddslashes($db) . '\', \'' . PMA_sqlAddslashes($arrvalue['name']) . '\', \'' . PMA_sqlAddslashes($chpage) . '\',' . $arrvalue['x'] . ',' . $arrvalue['y'] . ')';
                        }
												//echo $ch_query;
                        PMA_query_as_controluser($ch_query, FALSE, $query_default_option);
                    } // end if
                } // end for
                break;
            case 'deleteCrap':
                foreach ($delrow as $current_row) {
                    $d_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']) . ' ' . "\n"
                             .   ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'' . "\n"
                             .   ' AND   table_name = \'' . PMA_sqlAddslashes($current_row) . '\'' . "\n"
                             .   ' AND   pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
                    PMA_query_as_controluser($d_query, FALSE, $query_default_option);
                }
                break;
        } // end switch
    } // end if (isset($do))

	}
	
	/**
	* This function shows/displays the HTML FORM to create the page 
	*  
	*/
	public function createPage()
	{
	global $db,$table;
	?>
	<form method="post" action="export_relation_schema.php" name="frm_create_page">
		<fieldset>
		<legend>
		<?php echo __('Create a page !') . "\n"; ?>
		</legend>
		<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
		<input type="hidden" name="do" value="createpage" />
		<table>
		<tr>
		<td><label for="id_newpage"><?php echo __('Page name'); ?></label></td>
		<td><input type="text" name="newpage" id="id_newpage" size="20" maxlength="50" /></td>
		</tr>
		<tr>
		<td><?php echo __('Automatic layout based on'); ?></td>
		<td>
			<input type="checkbox" name="auto_layout_internal" id="id_auto_layout_internal" /><label for="id_auto_layout_internal">
			<?php echo __('Internal relations'); ?></label><br />
		<?php
		/**
		* Check to see whether INNODB and PBXT storage engines are Available in MYSQL PACKAGE
		* If available, then provide AutoLayout for Foreign Keys in Schema View
		*/ 
		if (PMA_StorageEngine::isValid('InnoDB') || PMA_StorageEngine::isValid('PBXT')) {
		    ?>
			<input type="checkbox" name="auto_layout_foreign" id="id_auto_layout_foreign" /><label for="id_auto_layout_foreign">
			<?php echo __('FOREIGN KEY'); ?></label><br />
		    <?php
		    }
		?>
		</td></tr>
		</table>
		</fieldset>
		<fieldset class="tblFooters">
		<input type="submit" value="<?php echo __('Go'); ?>" />
		</fieldset>
	</form>
    <?php
	}
	
	/**
	 * This function shows/displays the created page names 
	 * All page names in a list
	 * User can select any page number and edit it using dashboard etc
	 */
	public function selectPage()
	{
	global $db,$table,$query_default_option,$cfgRelation;
    $page_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'';
				//echo $page_query;
    $page_rs    = PMA_query_as_controluser($page_query, FALSE, $query_default_option);
			if ($page_rs && PMA_DBI_num_rows($page_rs) > 0) {
				?>
                <form method="get" action="export_relation_schema.php" name="frm_select_page">
                <fieldset>
                <legend>
                <?php echo __('Please select a page to edit') . "\n"; ?>
                </legend>
                <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
                <input type="hidden" name="do" value="selectpage" />
                <select name="chpage" onchange="this.form.submit()">
                    <?php
                    while ($curr_page = PMA_DBI_fetch_assoc($page_rs)) {
                     echo "\n" . '        '
                             . '<option value="' . $curr_page['page_nr'] . '"';
                        if (isset($chpage) && $chpage == $curr_page['page_nr']) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $curr_page['page_nr'] . ': ' . htmlspecialchars($curr_page['page_descr']) . '</option>';
                    } // end while
                    echo "\n";
                    ?>
                </select>
                <?php
                $choices = array(
                    '0' => __('Edit'),
                    '1' => __('Delete'));
                    PMA_display_html_radio('action_choose', $choices, '0', false);
                unset($choices);
                ?>
                </fieldset>
                <fieldset class="tblFooters">
                   <input type="submit" value="<?php echo __('Go'); ?>" /><br />
                </fieldset>
                </form>
                <?php
			} // end IF
    echo "\n";
	} // end function
	
	/** 
	* A dashboard will also be shown where you can position the tables
	*/	
	public function showTableDashBoard()
	{
		global $db,$cfgRelation,$table,$cfg,$with_field_names,$with_field_names,$chpage;
		// We will need an array of all tables in this db
		$selectboxall = array('--');
		$alltab_rs    = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($db) . ';', null, PMA_DBI_QUERY_STORE);
		while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
			$selectboxall[] = $val[0];
		}
		
		// Now if we already have chosen a page number then we should show the
		// tables involved
		if (isset($chpage) && $chpage > 0) {
			echo "\n";
			?>
		<h2><?php echo __('Select Tables') ;?></h2>
		
		<?php
		$page_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
					. ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
					. ' AND pdf_page_number = \'' . PMA_sqlAddslashes($chpage) . '\'';
		$page_rs    = PMA_query_as_controluser($page_query, FALSE, $query_default_option);
		$array_sh_page = array();
		$draginit = '';
		$reset_draginit = '';
		$i = 0;
		while ($temp_sh_page = @PMA_DBI_fetch_assoc($page_rs)) {
			$array_sh_page[] = $temp_sh_page;
		}
	
	// Display WYSIWYG-PDF parts?
	if ($cfg['WYSIWYG-PDF']) {
		if (!isset($_POST['with_field_names']) && !isset($_POST['showwysiwyg'])) {
			$with_field_names = TRUE;
		}
	?>
	<script type="text/javascript" src="./js/dom-drag.js"></script>
	<form method="post" action="export_relation_schema.php" name="dragdrop">
	<input type="button" name="dragdrop" value="<?php echo __('Toggle scratchboard'); ?>" onclick="ToggleDragDrop('pdflayout');" />
	 <input type="button" name="dragdropreset" value="<?php echo __('Reset'); ?>" onclick="resetDrag();" />
	</form>
	<div id="pdflayout" class="pdflayout" style="visibility: hidden;">
	<?php
	foreach ($array_sh_page as $key => $temp_sh_page) {
		$drag_x = $temp_sh_page['x'];
		$drag_y = $temp_sh_page['y'];
	
		$draginit       .= '    Drag.init(getElement("table_' . $i . '"), null, 0, parseInt(myid.style.width)-2, 0, parseInt(myid.style.height)-5);' . "\n";
		$draginit       .= '    getElement("table_' . $i . '").onDrag = function (x, y) { document.edcoord.elements["c_table_' . $i . '[x]"].value = parseInt(x); document.edcoord.elements["c_table_' . $i . '[y]"].value = parseInt(y) }' . "\n";
		$draginit       .= '    getElement("table_' . $i . '").style.left = "' . $drag_x . 'px";' . "\n";
		$draginit       .= '    getElement("table_' . $i . '").style.top  = "' . $drag_y . 'px";' . "\n";
		$reset_draginit .= '    getElement("table_' . $i . '").style.left = "2px";' . "\n";
		$reset_draginit .= '    getElement("table_' . $i . '").style.top  = "' . (15 * $i) . 'px";' . "\n";
		$reset_draginit .= '    document.edcoord.elements["c_table_' . $i . '[x]"].value = "2"' . "\n";
		$reset_draginit .= '    document.edcoord.elements["c_table_' . $i . '[y]"].value = "' . (15 * $i) . '"' . "\n";
	
		$local_query = 'SHOW FIELDS FROM '
					 .  PMA_backquote($temp_sh_page['table_name'])
					. ' FROM ' . PMA_backquote($db);
		$fields_rs = PMA_DBI_query($local_query);
		unset($local_query);
		$fields_cnt = PMA_DBI_num_rows($fields_rs);
	
		echo '<div id="table_' . $i . '" class="pdflayout_table"><u>' . $temp_sh_page['table_name'] . '</u>';
		if (isset($with_field_names)) {
			while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
				echo '<br />' . htmlspecialchars($row['Field']) . "\n";
			}
		}
		echo '</div>' . "\n";
		PMA_DBI_free_result($fields_rs);
		unset($fields_rs);
	
		$i++;
	}
	?>
	</div>
	<script type="text/javascript">
	//<![CDATA[
	function PDFinit() {
		refreshLayout();
		myid = getElement('pdflayout');
		<?php echo $draginit; ?>
	}
	
	function resetDrag() {
		<?php echo $reset_draginit; ?>
	}
	//]]>
	</script>
	<?php
	} // end if WYSIWYG-PDF
	?>
	
	<form method="post" action="export_relation_schema.php" name="edcoord">
		<?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
		<input type="hidden" name="chpage" value="<?php echo htmlspecialchars($chpage); ?>" />
		<input type="hidden" name="do" value="edcoord" />
		<table border="0">
		<tr>
			<th><?php echo __('Table'); ?></th>
			<th><?php echo __('Delete'); ?></th>
			<th>X</th>
			<th>Y</th>
		</tr>
			<?php
			if (isset($ctable)) {
				unset($ctable);
			}
	
	
			$i = 0;
			$odd_row = true;
			foreach ($array_sh_page as $dummy_sh_page => $sh_page) {
				$_mtab       = $sh_page['table_name'];
				$tabExist[$_mtab] = FALSE;
				echo "\n" . '    <tr class="';
				if ($odd_row) {
					echo 'odd';
				} else {
					echo 'even';
				}
				echo '">';
				$odd_row != $odd_row;
				echo "\n" . '        <td>'
					 . "\n" . '            <select name="c_table_' . $i . '[name]">';
				foreach ($selectboxall as $key => $value) {
					echo "\n" . '                <option value="' . htmlspecialchars($value) . '"';
					if ($value == $sh_page['table_name']) {
						echo ' selected="selected"';
						$tabExist[$_mtab] = TRUE;
					}
					echo '>' . htmlspecialchars($value) . '</option>';
				} // end while
				echo "\n" . '            </select>'
					 . "\n" . '        </td>';
				echo "\n" . '        <td>'
					 . "\n" . '            <input type="checkbox" name="c_table_' . $i . '[delete]" value="y" />' . __('Delete');
				echo "\n" . '        </td>';
				echo "\n" . '        <td>'
					 . "\n" . '            <input type="text" ' . ($cfg['WYSIWYG-PDF'] ? 'onchange="dragPlace(' . $i . ', \'x\', this.value)"' : '') . ' name="c_table_' . $i . '[x]" value="' . $sh_page['x'] . '" />';
				echo "\n" . '        </td>';
				echo "\n" . '        <td>'
					 . "\n" . '            <input type="text" ' . ($cfg['WYSIWYG-PDF'] ? 'onchange="dragPlace(' . $i . ', \'y\', this.value)"' : '') . ' name="c_table_' . $i . '[y]" value="' . $sh_page['y'] . '" />';
				echo "\n" . '        </td>';
				echo "\n" . '    </tr>';
				$i++;
			} // end while
			// Do one more empty row
			echo "\n" . '    <tr class="';
			if ($odd_row) {
				echo 'odd';
			} else {
				echo 'even';
			}
			$odd_row != $odd_row;
			echo '">';
			echo "\n" . '        <td>'
				 . "\n" . '            <select name="c_table_' . $i . '[name]">';
			foreach ($selectboxall as $key => $value) {
				echo "\n" . '                <option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</option>';
			}
			echo "\n" . '            </select>'
				 . "\n" . '        </td>';
			echo "\n" . '        <td>'
				 . "\n" . '            <input type="checkbox" name="c_table_' . $i . '[delete]" value="y" />' . __('Delete');
			echo "\n" . '        </td>';
			echo "\n" . '        <td>'
				 . "\n" . '            <input type="text" name="c_table_' . $i . '[x]" value="' . (isset($sh_page['x'])?$sh_page['x']:'') . '" />';
			echo "\n" . '        </td>';
			echo "\n" . '        <td>'
				 . "\n" . '            <input type="text" name="c_table_' . $i . '[y]" value="' . (isset($sh_page['y'])?$sh_page['y']:'') . '" />';
			echo "\n" . '        </td>';
			echo "\n" . '    </tr>';
			echo "\n" . '    </table>' . "\n";
	
			echo "\n" . '    <input type="hidden" name="c_table_rows" value="' . ($i + 1) . '" />';
			echo ($cfg['WYSIWYG-PDF'] ? "\n" . '    <input type="hidden" id="showwysiwyg" name="showwysiwyg" value="' . ((isset($showwysiwyg) && $showwysiwyg == '1') ? '1' : '0') . '" />' : '');
			echo "\n" . '    <input type="checkbox" name="with_field_names" ' . (isset($with_field_names) ? 'checked="checked"' : ''). ' />' . __('Column names') . '<br />';
			echo "\n" . '    <input type="submit" value="' . __('Save') . '" />';
			echo "\n" . '</form>' . "\n\n";
		} // end if
		
		$this->deleteTables($chpage);
		
		}// end function
	
	/** 
	 * show Export relational schema generation options
	 */	
	public function displaySchemaGenerationOptions()
	{
		global $cfg,$pmaThemeImage,$db,$test_rs,$chpage;
		?>
		<!-- Export relational schema -->
		<form method="post" action="process_relation_schema.php">
		<fieldset>
		<legend>
		<?php
		echo PMA_generate_common_hidden_inputs($db);
		if ($cfg['PropertiesIconic']) {
			echo '<img class="icon" src="' . $pmaThemeImage . 'b_view.png"'
				.' alt="" width="16" height="16" />';
		}
		echo __('Display Relational Schema');
		?>:
		</legend>
			
		<select name="export_type" id="export_type">
			<option value="pdf" selected="selected"><?php echo __('PDF');?></option>
			<option value="svg"><?php echo __('SVG');?></option>
			<option value="dia"><?php echo __('DIA');?></option>
			<option value="visio"><?php echo __('VISIO');?></option>
			<option value="eps"><?php echo __('EPS');?></option>
		</select>
		  <label><?php echo __('Select Export Relational Type');?></label><br />
		<?php
		if (isset($test_rs)) {
		?>
		<label for="pdf_page_number_opt"><?php echo __('Page number:'); ?></label>
		<select name="pdf_page_number" id="pdf_page_number_opt">
		<?php
		while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
			echo '                <option value="' . $pages['page_nr'] . '">'
				. $pages['page_nr'] . ': ' . htmlspecialchars($pages['page_descr']) . '</option>' . "\n";
		} // end while
		PMA_DBI_free_result($test_rs);
		unset($test_rs);
		?>
		</select><br />
		<?php } else { ?>
		<input type="hidden" name="pdf_page_number" value="<?php echo htmlspecialchars($chpage); ?>" />
		<?php } ?>
	
		<input type="checkbox" name="show_grid" id="show_grid_opt" />
		<label for="show_grid_opt"><?php echo __('Show grid'); ?></label><br />
		<input type="checkbox" name="show_color" id="show_color_opt"
			checked="checked" />
		<label for="show_color_opt"><?php echo __('Show color'); ?></label><br />
		<input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
		<label for="show_table_dim_opt"><?php echo __('Show dimension of tables'); ?>
			</label><br />
		<input type="checkbox" name="all_tab_same_wide" id="all_tab_same_wide" />
		<label for="all_tab_same_wide"><?php echo __('Display all tables with the same width'); ?>
			</label><br />
		<input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
		<label for="with_doc"><?php echo __('Data Dictionary'); ?></label><br />
		<input type="checkbox" name="show_keys" id="show_keys" />
		<label for="show_keys"><?php echo __('Only show keys'); ?></label><br />
	
		<label for="orientation_opt"><?php echo __('Data Dictionary Format'); ?></label>
		<select name="orientation" id="orientation_opt">
			<option value="L"><?php echo __('Landscape');?></option>
			<option value="P"><?php echo __('Portrait');?></option>
		</select><br />
	
		<label for="paper_opt"><?php echo __('Paper size'); ?></label>
		<select name="paper" id="paper_opt">
		<?php
			foreach ($cfg['PDFPageSizes'] as $key => $val) {
				echo '<option value="' . $val . '"';
				if ($val == $cfg['PDFDefaultPageSize']) {
					echo ' selected="selected"';
				}
				echo ' >' . $val . '</option>' . "\n";
			}
		?>
		</select>
		</fieldset>
		<fieldset class="tblFooters">
			<input type="submit" value="<?php echo __('Go'); ?>" />
		</fieldset>
		</form>
		<?php
	}// end function

	/**
	* Check if there are tables that need to be deleted in dashboard,
	* if there are, ask the user for allowance
	*/
	public function deleteTables($chpage)
	{
	global $db, $table,$tabExist;

		$_strtrans  = '';
		$_strname   = '';
		$shoot      = FALSE;
			if (!empty($tabExist) && is_array($tabExist)) {
				foreach ($tabExist as $key => $value) {
					if (!$value) {
						$_strtrans  .= '<input type="hidden" name="delrow[]" value="' . htmlspecialchars($key) . '" />' . "\n";
						$_strname   .= '<li>' . htmlspecialchars($key) . '</li>' . "\n";
						$shoot       = TRUE;
					}
				}
				if ($shoot) {
					echo '<form action="export_relation_schema.php" method="post">' . "\n"
					   . PMA_generate_common_hidden_inputs($db, $table)
					   . '<input type="hidden" name="do" value="deleteCrap" />' . "\n"
					   . '<input type="hidden" name="chpage" value="' . htmlspecialchars($chpage) . '" />' . "\n"
					   . __('The current page has references to tables that no longer exist. Would you like to delete those references?')
					   . '<ul>' . "\n"
					   . $_strname
					   . '</ul>' . "\n"
					   . $_strtrans
					   . '<input type="submit" value="' . __('Go') . '" />' . "\n"
					   . '</form>';
				}
			}
	} // end function
}
?>