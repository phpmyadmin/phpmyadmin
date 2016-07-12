<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Schema support library
 *
 * @package PhpMyAdmin-schema
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This Class interacts with the user to gather the information
 * about their tables for which they want to export the relational schema
 * export options are shown to user from they can choose
 *
 * @package PhpMyAdmin-schema
 */

class PMA_User_Schema
{

    public $chosenPage;
    public $autoLayoutForeign;
    public $autoLayoutInternal;
    public $pageNumber;
    public $c_table_rows;
    public $action;

    /**
     * Sets action to be performed with schema.
     *
     * @param string $value action name
     *
     * @return void
     */
    public function setAction($value)
    {
        $this->action = $value;
    }

    /**
     * This function will process the user defined pages
     * and tables which will be exported as Relational schema
     * you can set the table positions on the paper via scratchboard
     * for table positions, put the x,y co-ordinates
     *
     * $this->action tells what the Schema is supposed to do
     * create and select a page, generate schema etc
     *
     * @access public
     * @return void
     */
    public function processUserChoice()
    {
        global $db, $cfgRelation;

        if (isset($this->action)) {
            switch ($this->action) {
            case 'selectpage':
                $this->chosenPage = $_REQUEST['chpage'];
                if ('1' == $_REQUEST['action_choose']) {
                    $this->deleteCoordinates(
                        $db,
                        $cfgRelation,
                        $this->chosenPage
                    );
                    $this->deletePages(
                        $db,
                        $cfgRelation,
                        $this->chosenPage
                    );
                    $this->chosenPage = 0;
                }
                break;
            case 'createpage':
                $this->pageNumber = PMA_REL_createPage(
                    $_POST['newpage'],
                    $cfgRelation,
                    $db
                );
                $this->autoLayoutForeign = isset($_POST['auto_layout_foreign'])
                    ? "1"
                    : null;
                $this->autoLayoutInternal = isset($_POST['auto_layout_internal'])
                    ? "1"
                    : null;
                $this->processRelations(
                    $db,
                    $this->pageNumber,
                    $cfgRelation
                );
                break;
            case 'edcoord':
                $this->chosenPage = $_POST['chpage'];
                $this->c_table_rows = $_POST['c_table_rows'];
                $this->_editCoordinates($db, $cfgRelation);
                break;
            case 'delete_old_references':
                $this->_deleteTableRows(
                    $_POST['delrow'],
                    $cfgRelation,
                    $db,
                    $_POST['chpage']
                );
                break;
            case 'process_export':
                $this->_processExportSchema();
                break;

            } // end switch
        } // end if (isset($do))

    }

    /**
     * shows/displays the HTML FORM to create the page
     *
     * @param string $db name of the selected database
     *
     * @return void
     * @access public
     */
    public function showCreatePageDialog($db)
    {
        ?>
        <form method="post" action="schema_edit.php" name="frm_create_page">
        <fieldset>
        <legend>
        <?php echo __('Create a page') . "\n"; ?>
        </legend>
        <?php echo PMA_generate_common_hidden_inputs($db); ?>
        <input type="hidden" name="do" value="createpage" />
        <table>
        <tr>
        <td><label for="id_newpage"><?php echo __('Page name'); ?></label></td>
        <td>
        <input type="text" name="newpage" id="id_newpage" size="20" maxlength="50" />
        </td>
        </tr>
        <tr>
        <td><?php echo __('Automatic layout based on'); ?></td>
        <td>
        <input type="checkbox" name="auto_layout_internal" id="id_auto_layout_internal" /><label for="id_auto_layout_internal">
        <?php echo __('Internal relations'); ?></label><br />
        <?php
        /*
         * Check to see whether INNODB and PBXT storage engines
         * are Available in MYSQL PACKAGE
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
     * shows/displays the created page names in a drop down list
     * User can select any page number and edit it using dashboard etc
     *
     * @return void
     * @access public
     */
    public function selectPage()
    {
        global $db,$table,$cfgRelation;
        $page_query = 'SELECT * FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($cfgRelation['pdf_pages'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        $page_rs    = PMA_queryAsControlUser(
            $page_query, false, PMA_DBI_QUERY_STORE
        );

        if ($page_rs && PMA_DBI_num_rows($page_rs) > 0) {
            ?>
            <form method="get" action="schema_edit.php" name="frm_select_page">
            <fieldset>
            <legend>
            <?php echo __('Please choose a page to edit') . "\n"; ?>
            </legend>
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="do" value="selectpage" />
            <select name="chpage" id="chpage" class="autosubmit">
            <option value="0"><?php echo __('Select page'); ?></option>
            <?php
            while ($curr_page = PMA_DBI_fetch_assoc($page_rs)) {
                $page_nr = intval($curr_page['page_nr']);
                echo "\n" . '        '
                    . '<option value="' . $page_nr . '"';
                if (isset($this->chosenPage) && $this->chosenPage == page_nr) {
                    echo ' selected="selected"';
                }
                echo '>' . $page_nr . ': '
                    . htmlspecialchars($curr_page['page_descr']) . '</option>';
            } // end while
            echo "\n";
            ?>
            </select>
            <?php
            $choices = array(
                 '0' => __('Edit'),
                 '1' => __('Delete')
            );
            echo PMA_Util::getRadioFields(
                'action_choose', $choices, '0', false
            );
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
     * A dashboard is displayed to AutoLayout the position of tables
     * users can drag n drop the tables and change their positions
     *
     * @return void
     * @access public
     */
    public function showTableDashBoard()
    {
        global $db, $cfgRelation, $table, $with_field_names;
        /*
         * We will need an array of all tables in this db
         */
        $selectboxall = array('--');
        $alltab_rs    = PMA_DBI_query(
            'SHOW TABLES FROM ' . PMA_Util::backquote($db) . ';',
            null,
            PMA_DBI_QUERY_STORE
        );
        while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
               $selectboxall[] = $val[0];
        }

        $tabExist = array();

        /*
         * Now if we already have chosen a page number then we should
         * show the tables involved
         */
        if (isset($this->chosenPage) && $this->chosenPage > 0) {
            echo "\n";
            echo "<h2>" . __('Select Tables') . "</h2>";
            $page_query = 'SELECT * FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_coords'])
                . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND pdf_page_number = \''
                . PMA_Util::sqlAddSlashes($this->chosenPage) . '\'';
            $page_rs    = PMA_queryAsControlUser($page_query, false);
            $array_sh_page = array();
            while ($temp_sh_page = @PMA_DBI_fetch_assoc($page_rs)) {
                   $array_sh_page[] = $temp_sh_page;
            }
            /*
             * Display WYSIWYG parts
             */

            if (! isset($_POST['with_field_names'])
                && ! isset($_POST['showwysiwyg'])
            ) {
                $with_field_names = true;
            } elseif (isset($_POST['with_field_names'])) {
                $with_field_names = true;
            }
            $this->_displayScratchboardTables($array_sh_page);

            echo '<form method="post" action="schema_edit.php" name="edcoord">';

            echo PMA_generate_common_hidden_inputs($db, $table);
            echo '<input type="hidden" name="chpage" '
                . 'value="' . htmlspecialchars($this->chosenPage) . '" />';
            echo '<input type="hidden" name="do" value="edcoord" />';
            echo '<table>';
            echo '<tr>';
            echo '<th>' . __('Table') . '</th>';
            echo '<th>' . __('Delete') . '</th>';
            echo '<th>X</th>';
            echo '<th>Y</th>';
            echo '</tr>';

            if (isset($ctable)) {
                unset($ctable);
            }

            /*
             * Add one more empty row
             */
            $array_sh_page[] = array(
                'table_name' => '',
                'x' => '0',
                'y' => '0',
            );

            $i = 0;
            $odd_row = true;
            foreach ($array_sh_page as $sh_page) {
                $_mtab  = $sh_page['table_name'];
                if (! empty($_mtab)) {
                    $tabExist[$_mtab] = false;
                }

                echo '<tr class="noclick ';
                if ($odd_row) {
                    echo 'odd';
                } else {
                    echo 'even';
                }
                echo '">';
                $odd_row = !$odd_row;

                echo '<td>';
                echo '<select name="c_table_' . $i . '[name]">';

                foreach ($selectboxall as $value) {
                    echo '<option value="' . htmlspecialchars($value) . '"';
                    if (! empty($_mtab) && $value == $_mtab) {
                        echo ' selected="selected"';
                        $tabExist[$_mtab] = true;
                    }
                    echo '>' . htmlspecialchars($value) . '</option>';
                }
                echo '</select>';
                echo '</td>';

                echo '<td>';
                echo '<input type="checkbox" id="id_c_table_' . $i .'" '
                    . 'name="c_table_' . $i . '[delete]" value="y" />';
                echo '<label for="id_c_table_' . $i .'">'
                    . __('Delete') . '</label>';
                echo '</td>';

                echo '<td>';
                echo '<input type="text" class="position-change" data-axis="left" '
                    . 'data-number="' . $i . '" id="c_table_' . $i . '_x" '
                    . 'name="c_table_' . $i . '[x]" value="'
                    . $sh_page['x'] . '" />';
                echo '</td>';

                echo '<td>';
                echo '<input type="text" class="position-change" data-axis="top" '
                    . 'data-number="' . $i . '" id="c_table_' . $i . '_y" '
                    . 'name="c_table_' . $i . '[y]" value="'
                    . $sh_page['y'] . '" />';
                echo '</td>';
                echo '</tr>';
                $i++;
            }

            echo '</table>';

            echo '<input type="hidden" name="c_table_rows" value="' . $i . '" />';
            echo '<input type="hidden" id="showwysiwyg" name="showwysiwyg" value="'
                . ((isset($showwysiwyg) && $showwysiwyg == '1') ? '1' : '0')
                . '" />';
            echo '<input type="checkbox" id="id_with_field_names" '
                . 'name="with_field_names" '
                . (isset($with_field_names) ? 'checked="checked"' : ''). ' />';
            echo '<label for="id_with_field_names">'
                . __('Column names') . '</label><br />';
            echo '<input type="submit" value="' . __('Save') . '" />';
            echo '</form>' . "\n\n";
        } // end if

        if (isset($tabExist)) {
            $this->_deleteTables($db, $this->chosenPage, $tabExist);
        }
    }

    /**
     * show Export relational schema generation options
     * user can select export type of his own choice
     * and the attributes related to it
     *
     * @return void
     * @access public
     */

    public function displaySchemaGenerationOptions()
    {
        global $cfg,$db,$test_rs,$chpage;
        ?>
        <form method="post" action="schema_export.php" class="disableAjax">
            <fieldset>
            <legend>
            <?php
            echo PMA_generate_common_hidden_inputs($db);
            if (in_array(
                    $GLOBALS['cfg']['ActionLinksMode'],
                    array('icons', 'both')
                )
            ) {
                echo PMA_Util::getImage('b_views.png');
            }
            echo __('Display relational schema');
            ?>:
            </legend>
            <select name="export_type" id="export_type">
                <option value="pdf" selected="selected">PDF</option>
                <option value="svg">SVG</option>
                <option value="dia">DIA</option>
                <option value="eps">EPS</option>
            </select>
            <label><?php echo __('Select Export Relational Type');?></label><br />
            <?php
            if (isset($test_rs)) {
            ?>
            <label for="pdf_page_number_opt"><?php echo __('Page number:'); ?></label>
            <select name="pdf_page_number" id="pdf_page_number_opt">
                <?php
                while ($pages = @PMA_DBI_fetch_assoc($test_rs)) {
                    $page_nr = intval($pages['page_nr']);
                    echo '                <option value="' . $page_nr . '">'
                        . $page_nr . ': ' . htmlspecialchars($pages['page_descr']) . '</option>' . "\n";
                } // end while
                PMA_DBI_free_result($test_rs);
                unset($test_rs);
                ?>
            </select><br />
            <?php
            } else {
            ?>
            <input type="hidden" name="pdf_page_number" value="<?php echo htmlspecialchars($this->chosenPage); ?>" />
            <?php
            }
            ?>
            <input type="hidden" name="do" value="process_export" />
            <input type="hidden" name="chpage" value="<?php echo $chpage; ?>" />
            <input type="checkbox" name="show_grid" id="show_grid_opt" />
            <label for="show_grid_opt"><?php echo __('Show grid'); ?></label><br />
            <input type="checkbox" name="show_color" id="show_color_opt" checked="checked" />
            <label for="show_color_opt"><?php echo __('Show color'); ?></label>
            <br />
            <input type="checkbox" name="show_table_dimension" id="show_table_dim_opt" />
            <label for="show_table_dim_opt">
            <?php echo __('Show dimension of tables'); ?>
            </label><br />
            <input type="checkbox" name="all_tables_same_width" id="all_tables_same_width" />
            <label for="all_tables_same_width">
            <?php echo __('Display all tables with the same width'); ?>
            </label><br />
            <input type="checkbox" name="with_doc" id="with_doc" checked="checked" />
            <label for="with_doc"><?php echo __('Data Dictionary'); ?></label><br />
            <input type="checkbox" name="show_keys" id="show_keys" />
            <label for="show_keys"><?php echo __('Only show keys'); ?></label><br />
            <select name="orientation" id="orientation_opt" class="paper-change">
                <option value="L"><?php echo __('Landscape');?></option>
                <option value="P"><?php echo __('Portrait');?></option>
            </select>
            <label for="orientation_opt"><?php echo __('Orientation'); ?></label>
            <br />
            <select name="paper" id="paper_opt" class="paper-change">
                <?php
                foreach ($cfg['PDFPageSizes'] as $val) {
                        echo '<option value="' . $val . '"';
                        if ($val == $cfg['PDFDefaultPageSize']) {
                            echo ' selected="selected"';
                        }
                        echo ' >' . $val . '</option>' . "\n";
                }
                ?>
            </select>
            <label for="paper_opt"><?php echo __('Paper size'); ?></label>
            </fieldset>
            <fieldset class="tblFooters">
            <input type="submit" value="<?php echo __('Go'); ?>" />
            </fieldset>
        </form>
        <?php
    }

    /**
    * Check if there are tables that need to be deleted in dashboard,
    * if there are, ask the user for allowance
    *
    * @param string  $db       name of database selected
    * @param integer $chpage   selected page
    * @param array   $tabExist array of booleans
    *
    * @return void
    * @access private
    */
    private function _deleteTables($db, $chpage, $tabExist)
    {
        $_strtrans  = '';
        $_strname   = '';
        $shoot      = false;
        if (empty($tabExist) || ! is_array($tabExist)) {
            return;
        }
        foreach ($tabExist as $key => $value) {
            if (! $value) {
                $_strtrans  .= '<input type="hidden" name="delrow[]" value="'
                    . htmlspecialchars($key) . '" />' . "\n";
                $_strname   .= '<li>' . htmlspecialchars($key) . '</li>' . "\n";
                $shoot       = true;
            }
        }
        if (!$shoot) {
            return;
        }
        echo '<br /><form action="schema_edit.php" method="post">' . "\n"
            . PMA_generate_common_hidden_inputs($db)
            . '<input type="hidden" name="do" value="delete_old_references" />'
            . "\n"
            . '<input type="hidden" name="chpage" value="'
            . htmlspecialchars($chpage) . '" />' . "\n"
            . __(
                'The current page has references to tables that no longer exist.'
                . ' Would you like to delete those references?'
            )
            . '<ul>' . "\n"
            . $_strname
            . '</ul>' . "\n"
            . $_strtrans
            . '<input type="submit" value="' . __('Go') . '" />' . "\n"
            . '</form>';
    }

    /**
     * Check if there are tables that need to be deleted in dashboard,
     * if there are, ask the user for allowance
     *
     * @param array $array_sh_page array of tables on page
     *
     * @return void
     * @access private
     */
    private function _displayScratchboardTables($array_sh_page)
    {
        global $with_field_names, $db;

        echo '<form method="post" action="schema_edit.php" name="dragdrop">';
        echo '<input type="button" name="dragdrop" id="toggle-dragdrop" '
            . 'value="' . __('Toggle scratchboard') . '" />';
        echo '<input type="button" name="dragdropreset" id="reset-dragdrop" '
            . 'value="' . __('Reset') . '" />';
        echo '</form>';
        echo '<div id="pdflayout" class="pdflayout" style="visibility: hidden;">';

        $i = 0;

        foreach ($array_sh_page as $temp_sh_page) {
            $drag_x = $temp_sh_page['x'];
            $drag_y = $temp_sh_page['y'];

            echo '<div id="table_' . $i . '" '
                . 'data-number="' . $i .'" '
                . 'data-x="' . $drag_x . '" '
                . 'data-y="' . $drag_y . '" '
                . 'class="pdflayout_table"'
                . '>'
                . '<u>'
                . htmlspecialchars($temp_sh_page['table_name'])
                . '</u>';

            if (isset($with_field_names)) {
                $fields = PMA_DBI_get_columns($db, $temp_sh_page['table_name']);
                // if the table has been dropped from outside phpMyAdmin,
                // we can no longer obtain its columns list
                if ($fields) {
                    foreach ($fields as $row) {
                        echo '<br />' . htmlspecialchars($row['Field']) . "\n";
                    }
                }
            }
            echo '</div>' . "\n";
            $i++;
        }

        echo '</div>';
    }

    /**
     * delete the table rows with table co-ordinates
     *
     * @param int     $delrow      delete selected table from list of tables
     * @param array   $cfgRelation relation settings
     * @param string  $db          database name
     * @param integer $chpage      selected page for adding relations etc
     *
     * @return void
     * @access private
     */
    private function _deleteTableRows($delrow,$cfgRelation,$db,$chpage)
    {
        foreach ($delrow as $current_row) {
            $del_query = 'DELETE FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                . PMA_Util::backquote($cfgRelation['table_coords']) . ' ' . "\n"
                .   ' WHERE db_name = \''
                . PMA_Util::sqlAddSlashes($db) . '\'' . "\n"
                .   ' AND table_name = \''
                . PMA_Util::sqlAddSlashes($current_row) . '\'' . "\n"
                .   ' AND pdf_page_number = \''
                . PMA_Util::sqlAddSlashes($chpage) . '\'';
            PMA_queryAsControlUser($del_query, false);
        }
    }

    /**
     * get all the export options and verify
     * call and include the appropriate Schema Class depending on $export_type
     *
     * @return void
     * @access private
     */
    private function _processExportSchema()
    {
        /**
        * Settings for relation stuff
        */
        include_once './libraries/transformations.lib.php';
        include_once './libraries/Index.class.php';
        /**
         * default is PDF, otherwise validate it's only letters a-z
         */
        global  $db,$export_type;
        if (!isset($export_type) || !preg_match('/^[a-zA-Z]+$/', $export_type)) {
            $export_type = 'pdf';
        }

        PMA_DBI_select_db($db);

        include "libraries/schema/" . ucfirst($export_type)
            . "_Relation_Schema.class.php";
        eval("new PMA_" . ucfirst($export_type) . "_Relation_Schema();");
    }

    /**
     * delete X and Y coordinates
     *
     * @param string  $db          The database name
     * @param array   $cfgRelation relation settings
     * @param integer $choosePage  selected page for adding relations etc
     *
     * @return void
     * @access private
     */
    public function deleteCoordinates($db, $cfgRelation, $choosePage)
    {
        $query = 'DELETE FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($cfgRelation['table_coords'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND   pdf_page_number = \''
            . PMA_Util::sqlAddSlashes($choosePage) . '\'';
        PMA_queryAsControlUser($query, false);
    }

    /**
     * delete pages
     *
     * @param string  $db          The database name
     * @param array   $cfgRelation relation settings
     * @param integer $choosePage  selected page for adding relations etc
     *
     * @return void
     * @access private
     */
    public function deletePages($db, $cfgRelation, $choosePage)
    {
        $query = 'DELETE FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($cfgRelation['pdf_pages'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND   page_nr = \'' . PMA_Util::sqlAddSlashes($choosePage) . '\'';
        PMA_queryAsControlUser($query, false);
    }

    /**
     * process internal and foreign key relations
     *
     * @param string  $db          The database name
     * @param integer $pageNumber  document number/Id
     * @param array   $cfgRelation relation settings
     *
     * @return void
     * @access private
     */
    public function processRelations($db, $pageNumber, $cfgRelation)
    {
        /*
         * A u t o m a t i c    l a y o u t
         *
         * There are 2 kinds of relations in PMA
         * 1) Internal Relations 2) Foreign Key Relations
         */
        if (isset($this->autoLayoutInternal) || isset($this->autoLayoutForeign)) {
            $all_tables = array();
        }

        if (isset($this->autoLayoutForeign)) {
            /*
             * get the tables list
             * who support FOREIGN KEY, it's not
             * important that we group together InnoDB tables
             * and PBXT tables, as this logic is just to put
             * the tables on the layout, not to determine relations
             */
            $tables = PMA_DBI_get_tables_full($db);
            $foreignkey_tables = array();
            foreach ($tables as $table_name => $table_properties) {
                if (PMA_Util::isForeignKeySupported($table_properties['ENGINE'])) {
                    $foreignkey_tables[] = $table_name;
                }
            }
            $all_tables = $foreignkey_tables;
            /*
             * could be improved by finding the tables which have the
             * most references keys and placing them at the beginning
             * of the array (so that they are all center of schema)
             */
            unset($tables, $foreignkey_tables);
        }

        if (isset($this->autoLayoutInternal)) {
            /*
             * get the tables list who support Internal Relations;
             * This type of relations will be created when
             * you setup the PMA tables correctly
             */
            $master_tables = 'SELECT COUNT(master_table), master_table'
                . ' FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                . PMA_Util::backquote($cfgRelation['relation'])
                . ' WHERE master_db = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' GROUP BY master_table'
                . ' ORDER BY COUNT(master_table) DESC';
            $master_tables_rs = PMA_queryAsControlUser(
                $master_tables, false, PMA_DBI_QUERY_STORE
            );
            if ($master_tables_rs && PMA_DBI_num_rows($master_tables_rs) > 0) {
                /* first put all the master tables at beginning
                 * of the list, so they are near the center of
                 * the schema
                 */
                while (list(, $master_table) = PMA_DBI_fetch_row($master_tables_rs)) {
                       $all_tables[] = $master_table;
                }

                /* Now for each master, add its foreigns into an array
                 * of foreign tables, if not already there
                 * (a foreign might be foreign for more than
                 * one table, and might be a master itself)
                 */

                $foreign_tables = array();
                foreach ($all_tables as $master_table) {
                    $foreigners = PMA_getForeigners($db, $master_table);
                    foreach ($foreigners as $foreigner) {
                        if (! in_array($foreigner['foreign_table'], $foreign_tables)) {
                            $foreign_tables[] = $foreigner['foreign_table'];
                        }
                    }
                }

                /*
                 * Now merge the master and foreign arrays/tables
                 */
                foreach ($foreign_tables as $foreign_table) {
                    if (! in_array($foreign_table, $all_tables)) {
                        $all_tables[] = $foreign_table;
                    }
                }
            }
        }

        if (isset($this->autoLayoutInternal) || isset($this->autoLayoutForeign)) {
            $this->addRelationCoordinates(
                $all_tables, $pageNumber, $db, $cfgRelation
            );
        }

        $this->chosenPage = $pageNumber;
    }

    /**
     * Add X and Y coordinates for a table
     *
     * @param array   $all_tables  A list of all tables involved
     * @param integer $pageNumber  document number/Id
     * @param string  $db          The database name
     * @param array   $cfgRelation relation settings
     *
     * @return void
     * @access private
     */
    public function addRelationCoordinates(
        $all_tables, $pageNumber, $db, $cfgRelation
    ) {
        /*
         * Now generate the coordinates for the schema
         * in a clockwise spiral and add to co-ordinates table
         */
        $pos_x = 300;
        $pos_y = 300;
        $delta = 110;
        $delta_mult = 1.10;
        $direction = "right";
        foreach ($all_tables as $current_table) {
            /*
            * save current table's coordinates
            */
            $insert_query = 'INSERT INTO '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                . PMA_Util::backquote($cfgRelation['table_coords']) . ' '
                . '(db_name, table_name, pdf_page_number, x, y) '
                . 'VALUES (\'' . PMA_Util::sqlAddSlashes($db) . '\', \''
                . PMA_Util::sqlAddSlashes($current_table) . '\',' . $pageNumber
                . ',' . $pos_x . ',' . $pos_y . ')';
            PMA_queryAsControlUser($insert_query, false);

            /*
             * compute for the next table
             */
            switch ($direction) {
            case 'right':
                $pos_x    += $delta;
                $direction = "down";
                $delta    *= $delta_mult;
                break;
            case 'down':
                $pos_y    += $delta;
                $direction = "left";
                $delta    *= $delta_mult;
                break;
            case 'left':
                $pos_x    -= $delta;
                $direction = "up";
                $delta    *= $delta_mult;
                break;
            case 'up':
                $pos_y    -= $delta;
                $direction = "right";
                $delta    *= $delta_mult;
                break;
            }
        }
    }

    /**
     * update X and Y coordinates for a table
     *
     * @param string $db          The database name
     * @param array  $cfgRelation relation settings
     *
     * @return void
     * @access private
     */
    private function _editCoordinates($db, $cfgRelation)
    {
        for ($i = 0; $i < $this->c_table_rows; $i++) {
            $arrvalue = $_POST['c_table_' . $i];

            if (! isset($arrvalue['x']) || $arrvalue['x'] == '') {
                $arrvalue['x'] = 0;
            }
            if (! isset($arrvalue['y']) || $arrvalue['y'] == '') {
                $arrvalue['y'] = 0;
            }
            if (isset($arrvalue['name']) && $arrvalue['name'] != '--') {
                $test_query = 'SELECT * FROM '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                    . PMA_Util::backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name = \'' .  PMA_Util::sqlAddSlashes($db) . '\''
                    . ' AND   table_name = \''
                    . PMA_Util::sqlAddSlashes($arrvalue['name']) . '\''
                    . ' AND   pdf_page_number = \''
                    . PMA_Util::sqlAddSlashes($this->chosenPage) . '\'';
                $test_rs = PMA_queryAsControlUser(
                    $test_query, false, PMA_DBI_QUERY_STORE
                );
                //echo $test_query;
                if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
                    if (isset($arrvalue['delete']) && $arrvalue['delete'] == 'y') {
                        $ch_query = 'DELETE FROM '
                            . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                            . '.'
                            . PMA_Util::backquote($cfgRelation['table_coords'])
                            . ' WHERE db_name = \''
                            . PMA_Util::sqlAddSlashes($db) . '\''
                            . ' AND   table_name = \''
                            . PMA_Util::sqlAddSlashes($arrvalue['name']) . '\''
                            . ' AND   pdf_page_number = \''
                            . PMA_Util::sqlAddSlashes($this->chosenPage) . '\'';
                    } else {
                        $ch_query = 'UPDATE '
                            . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                            . '.' . PMA_Util::backquote($cfgRelation['table_coords'])
                            . ' '
                            . 'SET x = ' . $arrvalue['x'] . ', y= ' . $arrvalue['y']
                            . ' WHERE db_name = \''
                            . PMA_Util::sqlAddSlashes($db) . '\''
                            . ' AND   table_name = \''
                            . PMA_Util::sqlAddSlashes($arrvalue['name']) . '\''
                            . ' AND   pdf_page_number = \''
                            . PMA_Util::sqlAddSlashes($this->chosenPage) . '\'';
                    }
                } else {
                    $ch_query = 'INSERT INTO '
                        . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                        . '.' . PMA_Util::backquote($cfgRelation['table_coords'])
                        . ' '
                        . '(db_name, table_name, pdf_page_number, x, y) '
                        . 'VALUES (\'' . PMA_Util::sqlAddSlashes($db) . '\', \''
                        . PMA_Util::sqlAddSlashes($arrvalue['name']) . '\', \''
                        . PMA_Util::sqlAddSlashes($this->chosenPage) . '\','
                        . $arrvalue['x'] . ',' . $arrvalue['y'] . ')';
                }
                //echo $ch_query;
                PMA_queryAsControlUser($ch_query, false);
            } // end if
        } // end for
    }
}
?>
