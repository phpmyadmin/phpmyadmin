<?php
/* $Id$ */

/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');

/**
  Updates
*/

if(isset($submit_rel) && $submit_rel=='true'){
    //  first check if there is a entry allready
    $upd_query  = 'SELECT master_field,foreign_table,foreign_field from '.$cfg['Server']['relation'];
    $upd_query .= ' where master_table=\''.$table . '\'';
    $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('',$upd_query,'',$err_url_0);
    while ($foundrel = mysql_fetch_array($upd_rs)){
        $currfield=&$foundrel['master_field'];
        $existrel[$currfield] = $foundrel['foreign_table'].'.'.$foundrel['foreign_field'];
    }
    while (list($key,$value) = each($destination)){
        if($value!='nix'){
            if(!isset($existrel[$key])){
                    $for        = explode('.',$destination[$key]);
                    $upd_query  = 'INSERT INTO '.$cfg['Server']['relation'] . ' values( ';
                    $upd_query .= "'$table','$key','".$for[0]."','".$for[1]."')";
                    $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('',$upd_query,'',$err_url_0);
            }else{
                if($existrel[$key] != $value){
                    $for        = explode('.',$destination[$key]);
                    $upd_query  = 'UPDATE '.$cfg['Server']['relation'] . ' SET ';
                    $upd_query .= 'foreign_table=\''.$for[0].'\',foreign_field=\''.$for[1].'\' ';
                    $upd_query .= ' WHERE master_table=\''.$table.'\' AND master_field=\''.$key.'\'';
                    $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('',$upd_query,'',$err_url_0);
                }
            }
        }else{
            if(isset($existrel[$key])){
                $for        = explode('.',$destination[$key]);
                $upd_query  = 'DELETE FROM '.$cfg['Server']['relation'];
                $upd_query .= ' WHERE master_table=\''.$table.'\' AND master_field=\''.$key.'\'';
                $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('',$upd_query,'',$err_url_0);
            }
        }
    }
}


/**
 Dialog
*/

if($cfg['Server']['relation']){
    $rel_work=FALSE;
    // Mike Beck: get all Table-Fields to choose relation
    $tab_query = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $tab_rs      = mysql_query($tab_query) or PMA_mysqlDie('',$tab_query,'',$err_url_0);
    $selectboxall['nix']='--';
    while ($curr_table=mysql_fetch_array($tab_rs)){
        if(($curr_table[0]!=$table)&&($curr_table[0]!=$cfg['Server']['relation'])){
            $fi_query = 'SHOW KEYS from ' . PMA_backquote($curr_table[0]);
            $fi_rs    = mysql_query($fi_query) or PMA_mysqlDie('', $fi_query, '', $err_url_0);
            if (mysql_num_rows($fi_rs) > 0) {
                while ($curr_field = mysql_fetch_array($fi_rs)){
                    if($curr_field["Key_name"]=="PRIMARY"){
                        $field_full = $curr_field["Table"].".".$curr_field["Column_name"];
                        $field_v    = $curr_field["Table"]."->".$curr_field["Column_name"];
                        break;
                    }else if($curr_field["non_unique"]==0){
                    //  if we can't find a primary key we take any unique one
                        $field_full = $curr_field["Table"].".".$curr_field["Column_name"];
                        $field_v    = $curr_field["Table"]."->".$curr_field["Column_name"];
                    } // end if
                } // end while

                $selectboxall[$field_full] =  $field_v;
            } // end if (mysql_num_rows)
        }
        if($curr_table[0]==$cfg['Server']['relation']){
            $rel_work=TRUE;
        }
    }
        //  create Array of Relations (Mike Beck)
    if($rel_work){
        $rel_query   = "SELECT master_field,concat(foreign_table,'.',foreign_field) as rel ";
        $rel_query  .= 'FROM ' . $cfg['Server']['relation'];
        $rel_query  .= ' WHERE master_table = \'' . $table.'\'';

        $relations   = @mysql_query($rel_query) or PMA_mysqlDie('', $rel_query, '', $err_url);
        while ($relrow = @mysql_fetch_array($relations)){
            $rel_col = $relrow['master_field'];
            $rel_dest[$rel_col]=$relrow['rel'];
        }
    }
}

// now find out the columns of our $table
$col_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table);
$col_rs      = mysql_query($col_query) or PMA_mysqlDie('',$col_query,'',$err_url_0);

if(mysql_num_rows($col_rs)>0){
    ?>
    <TABLE>
        <FORM action="tbl_relation.php3" method="post" style="display:inline">
        <INPUT type="hidden" name="submit_rel" value="true">
        <INPUT type="hidden" name="table" value="<?php echo $table; ?>">
        <INPUT type="hidden" name="db" value="<?php echo $db; ?>">
    <TR>
        <TD></TD>
        <TD align="center"><b><?php echo $GLOBALS['strLinksTo']; ?></b></TD>
    </TR>
    <?php
}
while ($row=mysql_fetch_array($col_rs)){
    ?>
    <TR>
        <TH><?php echo $row[0]; ?></TH>
        <TD>
            <INPUT type="hidden" name="src_field" value="<?php echo $row['Field'];?>">
            <SELECT name="destination[<?php echo $row['Field'];?>]" onChange="javascript:document.forms['<?php echo $row['Field']; ?>'].submit();">
            <?php
            foreach ($selectboxall as $key => $value){
                $myfield=$row['Field'];
                //echo "Feld $myfield: vergleiche $key mit ".$rel_dest[$myfield]."\n";
                echo '<OPTION value="'.$key.'"';
                if(isset($rel_dest[$myfield]) && $key == $rel_dest[$myfield]){
                     echo ' selected';
                }
                echo '>'.$value.'</OPTION>'. "\n";
            }
            ?>
            </SELECT>
        </TD>
    </TR>
    <?php
}
if(mysql_num_rows($col_rs)>0){
    ?>
            <TR><TD colspan=2 align="center"><INPUT type="submit" value="<?php echo $GLOBALS['strGo'];?>"></TD></TR>
        </FORM>
    </TABLE>
    <?php
}
?>
</BODY>
</HTML>
