<?php
/**
 *  pdf_pages.php3 mikebeck 2002-05-23
 *  create and edit the pages to output in pdf
 *
 *  requires a separate table:
 *  CREATE TABLE `PMA_pdf_pages` (
 *  `page_nr` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 *  `page_descr` VARCHAR(50) NOT NULL
 *   ) TYPE = MYISAM
 *   COMMENT = 'PDF Relationpages for PMA';
 *
 *   also requires a new variable in config.inc.php3:
 *   $cfg['Servers'][$i]['pdf_pages'] = 'PMA_pdf_pages'; // table to describe pages of relationpdf
 */
/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');

/**
 * Select page:
 */

if (!empty($cfg['Server']['pdf_pages'])){
    //  First we get all tables in the current db
    $tab_query           = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $tab_rs              = PMA_mysql_query($tab_query) or PMA_mysqlDie('', $tab_query, '', $err_url_0);
    $selectboxall[] = '--';
    while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
        //  i'd like to check if all required tables are present
        //  and otherwise give some hint
        if($curr_table[0] == $cfg['Server']['relation']){$relex =1;}else{
            if($curr_table[0] == $cfg['Server']['table_info']){$info =1;}else{
                if($curr_table[0] == $cfg['Server']['table_coords']){$coords =1;}else{
                //  if it is not one of the PMA tables add it to the
                //  selectbox
                    $selectboxall[] = $curr_table[0];
                }
            }
        }
    }
    //  now check if we found all required tables
    //  this will fail if either the variable was not set or the table does not
    //  exist
    //
    if(!isset($relex)){
        echo sprintf($strNotSet,'relation','config.inc.php3') . '<br /><a href="Documentation.html#relation" target="documentation">' . $strDocu . '</a>';
        die();
    }
    if(!isset($info))  {
        echo sprintf($strNotSet,'table_info','config.inc.php3') . '<br /><a href="Documentation.html#table_info" target="documentation">' . $strDocu . '</a>';
        die();
    }
    if(!isset($coords)){
        echo sprintf($strNotSet,'table_coords','config.inc.php3') . '<br /><a href="Documentation.html#table_coords" target="documentation">' . $strDocu . '</a>';
        die();
    }

    //  now is the time to work on all changes
    if(isset($do)){
        switch($do){
            case 'createpage':
                if(!isset($newpage) || $newpage==''){
                    $newpage = $strNoDescription;
                }
                $ins_query = 'INSERT INTO ' . PMA_backquote($cfg['Server']['pdf_pages']) .
                             ' (page_descr) VALUES (\'' . $newpage . '\')';
                PMA_mysql_query($ins_query) or PMA_mysqlDie('', $ins_query, '', $err_url_0);
                break;
            case 'edcoord':
                while (list($key,$arrvalue) = each($ctable)) {
                    if(!isset($arrvalue['x']) || $arrvalue['x'] == ''){$arrvalue['x']=0;}
                    if(!isset($arrvalue['y']) || $arrvalue['y'] == ''){$arrvalue['y']=0;}
                    if(isset($arrvalue['name']) && $arrvalue['name'] != '--'){
                        $test_query = 'SELECT * FROM '.PMA_backquote($cfg['Server']['table_coords']) .
                                      ' WHERE table_name = \''.$arrvalue['name'] . '\'' .
                                      ' AND pdf_page_number = '.$chpage;
                        $test_rs = PMA_mysql_query($test_query) or PMA_mysqlDie('', $test_query, '', $err_url_0);
                        if(mysql_num_rows($test_rs)>0){
                            if($arrvalue['delete'] == 'y'){
                                $ch_query = 'DELETE FROM '.PMA_backquote($cfg['Server']['table_coords']) .
                                         ' WHERE table_name = \''.$arrvalue['name'] . '\'' .
                                        ' AND pdf_page_number = '.$chpage;
                            }else{
                                $ch_query = 'UPDATE '.PMA_backquote($cfg['Server']['table_coords']) .
                                            ' SET x='.$arrvalue['x'] . ', y= '. $arrvalue['y'] .
                                            ' WHERE table_name = \''.$arrvalue['name'] . '\'' .
                                            ' AND pdf_page_number = '.$chpage;
                            }
                        }else{
                            $ch_query = 'INSERT INTO '.PMA_backquote($cfg['Server']['table_coords']) .
                                        ' VALUES (\''.$arrvalue['name'].'\','.$chpage.','.
                                        $arrvalue['x'].','.$arrvalue['y'].')';
                        }
                        PMA_mysql_query($ch_query) or PMA_mysqlDie('', $ch_query, '', $err_url_0);
                    }
                }
                break;
        }
    }
    //  now first show some possibility to choose a page for the pdf
    $page_query           = 'SELECT * FROM ' .PMA_backquote($cfg['Server']['pdf_pages']);
    $page_rs              = PMA_mysql_query($page_query) or PMA_mysqlDie('', $page_query, '', $err_url_0);
    if(mysql_num_rows($page_rs)>0){
        ?>
        <form action="pdf_pages.php3" method="post" name="selpage">
        <?php echo $strChoosePage; ?>
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="table" value="<?php echo $table; ?>" />
        <input type="hidden" name="do" value="choosepage" />
        <select name="chpage" onChange="this.form.submit()">
        <?php
        while ($curr_page = @PMA_mysql_fetch_array($page_rs)) {
            echo '<option value="'.$curr_page['page_nr'].'"';
                if($chpage==$curr_page['page_nr']){echo ' selected="selected"';}
                echo '>';
                echo $curr_page['page_nr'] . ': '.$curr_page['page_descr'].'</option>';
        }
        ?>
        </select>
        <input type="submit" value="<?php echo $strGo; ?>" />
        </form>
        <?php
    }
    //  possibility to create a new page:
    ?>
    <form action="pdf_pages.php3" method="post" name="crpage">
        <?php echo $strCreatePage; ?>
        <input type="hidden" name="db" value="<?php echo $db; ?>" />
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <input type="hidden" name="server" value="<?php echo $server; ?>" />
        <input type="hidden" name="table" value="<?php echo $table; ?>" />
        <input type="hidden" name="do" value="createpage" />
        <input type="text" name="newpage" size="20" maxlength="50" />
        <input type="submit" value="<?php echo $strGo; ?>" />
    </form>
    <?php
    //  now if we allready have choosen a pagenumer then we should show the tables involved
    if(isset($chpage) && $chpage>0){
        ?>
        <hr /><h2><?php echo $strSelectTables ;?></h2>
        <form action="pdf_pages.php3" method="post" name="edcoord">
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="chpage" value="<?php echo $chpage; ?>" />
            <input type="hidden" name="do" value="edcoord" />
            <table border=0>
                <tr><th><?php echo $strTable;?></th><th><?php echo $strDelete;?></th><th>X</th><th>Y</th></tr>
        <?php
        if(isset($ctable)){unset($ctable);}
        $page_query = 'SELECT * FROM' . PMA_backquote($cfg['Server']['table_coords']) .
                                 ' WHERE pdf_page_number='.$chpage;
        $page_rs   = PMA_mysql_query($page_query) or PMA_mysqlDie('', $page_query, '', $err_url_0);
        $i=0;
        while ($sh_page = @PMA_mysql_fetch_array($page_rs)) {
            echo '<tr ';
            if($i % 2==0){
                echo 'bgcolor="'.$cfg['BgcolorOne'].'"';
            }else{
                echo 'bgcolor="'.$cfg['BgcolorTwo'].'"';
            }
            echo '>';
            echo '<td><select name="ctable['.$i.'][name]">';
            reset($selectboxall);
            while (list($key,$value) = each($selectboxall)) {
                echo '<option value="'.$value.'"';
                if($value==$sh_page['table_name']){
                    echo ' selected="selected"';
                }
                echo '>'.$value.'</option>'."\n";
            }
            echo '</select></td>'."\n";
            echo '<td><INPUT type="checkbox" name="ctable['.$i.'][delete]" value="y" />'.$strDelete.'</td>'."\n";
            echo '<td><INPUT type="text" name="ctable['.$i.'][x]" value="'.$sh_page['x'].'"></td>'."\n";
            echo '<td><INPUT type="text" name="ctable['.$i.'][y]" value="'.$sh_page['y'].'"></td>'."\n";
            echo '</tr>'."\n";
            $i++;
        }
        // do one more empty row
        echo '<tr ';
        if($i % 2==0){
            echo 'bgcolor="'.$cfg['BgcolorOne'].'"';
        }else{
            echo 'bgcolor="'.$cfg['BgcolorTwo'].'"';
        }
        echo '>';
        echo '<td><select name="ctable['.$i.'][name]">';
        reset($selectboxall);
        while (list($key,$value) = each($selectboxall)) {
            echo '<option value="'.$value.'"';
            echo '>'.$value.'</option>'."\n";
        }
        echo '</select></td>'."\n";
        echo '<td><INPUT type="checkbox" name="ctable['.$i.'][delete]" value="y" />'.$strDelete.'</td>'."\n";
        echo '<td><INPUT type="text" name="ctable['.$i.'][x]"></td>'."\n";
        echo '<td><INPUT type="text" name="ctable['.$i.'][y]"></td>'."\n";
        echo '</tr>'."\n";
        echo '</table><input type="submit" value="'.$strGo.'" /></form>'."\n";
    }
}


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
