<?php
/**
*   This script imports relationinfos from docSQL (www.databay.de)
**/
/**
 * Get the values of the variables posted or sent to this script and display
 * the headers
 */

require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/relation.lib.php3');

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

if (isset($do) && $do=='import') {
    // echo "<h1>Starting Import</h1>";
    if(substr($docpath,strlen($docpath)-2,1)!='/') {
        $docpath = $docpath.'/';
    }
    if (is_dir($docpath)) {
        $handle = opendir($docpath);
        while ($file = @readdir ($handle)) {
            $_filename=basename($file);
            // echo "<p>Working on file " . $_filename . "</p>";
            $_parts   = explode('_',$_filename);
            if (count($_parts)==3 && $_parts[1] == 'field' && $_parts[2] == 'comment.txt') {
                $_tab = $_parts[0];
                //echo "<H1>Working on Table " . $_tab . "</h1>";
                $fd   = fopen($docpath . $file, "r");
                if($fd) {
                    while (!feof($fd)) {
                        $_line = fgets($fd, 4096);
                        //echo "<p>" . $_line . "</p>";
                        $_inf  = explode('|',$_line);
                        if(!empty($_inf[1]) && strlen(trim($_inf[1]))>0){
                            $qry = 'INSERT INTO ' . PMA_backquote($cfgRelation['column_comments'])
                               . ' (db_name, table_name, column_name, comment) '
                               . ' VALUES('
                               . '\'' . PMA_sqlAddslashes($db) . '\','
                               . '\'' . PMA_sqlAddslashes(trim($_tab)) . '\','
                               . '\'' . PMA_sqlAddslashes(trim($_inf[0])) . '\','
                               . '\'' . PMA_sqlAddslashes(trim($_inf[1])) . '\')';
                            if(PMA_query_as_cu($qry)) {
                                echo "<p>added Comment for Column " . $_tab . '.' . $_inf[0] . "</p>";
                            } else {
                                echo "<p>writing of Comment not possible</p>";
                            }
                        }
                        if (!empty($_inf[2]) && strlen(trim($_inf[2]))>0) {
                            $_for = explode('->',$_inf[2]);

                            $qry  = 'INSERT INTO ' . PMA_backquote($cfgRelation['relation'])
                                . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                                . ' values('
                                . '\'' . PMA_sqlAddslashes($db) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($_tab)) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($_inf[0])) . '\', '
                                . '\'' . PMA_sqlAddslashes($db) . '\', '
                                . '\'' . PMA_sqlAddslashes(trim($_for[0])) . '\','
                                . '\'' . PMA_sqlAddslashes(trim($_for[1])) . '\')';
                            if(PMA_query_as_cu($qry)) {
                                echo "<p>added Relation for Column " . $_tab . '.' . $_inf[0] . " to " . $_for . "</p>";
                            } else {
                                echo "<p>writing of Relation not possible</p>";
                            }
                        }
                    }
                    echo '<p><font color="green">Import finished</font></p>';
                } else {
                    echo "<p><font color=\"red\">File could not be read</font></p>";
                }
            }else {
                echo "<p><font color=\"yellow\">Ignoring File $file</font></p>";
            }
        }
    } else {
        echo "This was not a Directory";
    }
}

?>
<form action="db_details_importdocsql.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="submit_show" value="true" />
    <input type="hidden" name="do" value="import" />
    <table>
        <tr><th colspan=2>Used Database: <?php echo $db;?></th></th>
        <tr><th>Please enter absolute path on webserver to docSQL Directory:</th>
            <td><input type="text" name="docpath" size="50" value="<?php if(isset($DOCUMENT_ROOT)) {echo $DOCUMENT_ROOT;}?>" /></td>
        </tr>
        <tr><th colspan=2><input type="submit" value="import files" /></th></tr>
    </table>
</form>