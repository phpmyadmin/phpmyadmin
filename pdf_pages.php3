<?php
/**
 *  pdf_pages.php3 mikebeck 2002-05-23
 *  create and edit the pages to output in pdf
 *
 */
/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
include('./db_details_common.php3');

/**
 * Settings for Relationstuff
 */
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

/**
 * now in ./libraries/relation.lib.php3 we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can work without.
 * this page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */

if(!$cfgRelation['relwork']) {
    echo sprintf($strNotSet,'relation','config.inc.php3') . '<br /><a href="Documentation.html#relation" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!$cfgRelation['displaywork']) {
    echo sprintf($strNotSet,'table_info','config.inc.php3') . '<br /><a href="Documentation.html#table_info" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!isset($cfgRelation['table_coords'])){
    echo sprintf($strNotSet,'table_coords','config.inc.php3') . '<br /><a href="Documentation.html#table_coords" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!isset($cfgRelation['pdf_pages'])) {
    echo sprintf($strNotSet,'pdf_page','config.inc.php3') . '<br /><a href="Documentation.html#pdf_pages" target="documentation">' . $strDocu . '</a>';
    die();
}

if ($cfgRelation['pdfwork']){
    //  now is the time to work on all changes
    if(isset($do)){
        switch($do){
            case 'createpage':
                if(!isset($newpage) || $newpage==''){
                    $newpage = $strNoDescription;
                }
                $ins_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['pdf_pages'])
                           . ' (db_name,page_descr) '
                           . ' VALUES (\'' . $db . '\',\'' . $newpage . '\')';
                PMA_query_as_cu($ins_query);
                break;
            case 'edcoord':
                while (list($key,$arrvalue) = each($ctable)) {
                    if(!isset($arrvalue['x']) || $arrvalue['x'] == ''){$arrvalue['x']=0;}
                    if(!isset($arrvalue['y']) || $arrvalue['y'] == ''){$arrvalue['y']=0;}
                    if(isset($arrvalue['name']) && $arrvalue['name'] != '--'){

                        $test_query = 'SELECT * FROM '.PMA_backquote($cfgRelation['table_coords'])
                                    . ' WHERE       db_name = \'' .  $db . '\''
                                    . ' AND      table_name = \'' . $arrvalue['name'] . '\'' .
                                      ' AND pdf_page_number = '.$chpage;
                        $test_rs = PMA_query_as_cu($test_query);
                        if(mysql_num_rows($test_rs)>0){
                            if(isset($arrvalue['delete']) && $arrvalue['delete'] == 'y'){
                                $ch_query = 'DELETE FROM '.PMA_backquote($cfgRelation['table_coords'])
                                          . ' WHERE       db_name = \'' . $db . '\''
                                          . ' AND      table_name = \''.$arrvalue['name'] . '\''
                                          . ' AND pdf_page_number = '.$chpage;
                            }else{
                                $ch_query = 'UPDATE '.PMA_backquote($cfgRelation['table_coords'])
                                          . ' SET x='.$arrvalue['x'] . ', y= '. $arrvalue['y']
                                          . ' WHERE       db_name = \'' . $db . '\''
                                          . ' AND      table_name = \''.$arrvalue['name'] . '\''
                                          . ' AND pdf_page_number = '.$chpage;
                            }
                        }else{
                            $ch_query = 'INSERT INTO '.PMA_backquote($cfgRelation['table_coords'])
                                      . ' (db_name,table_name,pdf_page_number,x,y) '
                                      . ' VALUES (\'' . $db . '\',\''.$arrvalue['name'].'\','
                                      . $chpage.','
                                      . $arrvalue['x'].','.$arrvalue['y'].')';
                        }
                        PMA_query_as_cu($ch_query);
                    }
                }
                break;
        }
    }  // End if (isset($do))

    //  we will need an array of all tables in this db
    $selectboxall[] = '--';
    $alltab_qry = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $alltab_rs  = @PMA_mysql_query($alltab_qry) or PMA_mysqlDie('', $alltab_qry, '', $err_url_0);
    while (list($table) = @PMA_mysql_fetch_array($alltab_rs)) {
        $selectboxall[] = $table;
    }

    //  now first show some possibility to choose a page for the pdf
    $page_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name = \'' . $db . '\'';
    $page_rs    = PMA_query_as_cu($page_query);
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
                if (isset($chpage) && $chpage==$curr_page['page_nr']) {
                    echo ' selected="selected"';
                }
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

        $page_query = 'SELECT * FROM ' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name = \'' . $db . '\''
                    . ' AND pdf_page_number=' . $chpage;
        $page_rs    = PMA_query_as_cu($page_query);

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
