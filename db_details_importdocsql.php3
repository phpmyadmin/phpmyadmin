<?php
/* $Id$ */


/**
 * This script imports relation infos from docSQL (www.databay.de)
 */


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

if (isset($do) && $do == 'import') {
    // echo '<h1>Starting Import</h1>';
    if (substr($docpath, strlen($docpath) - 2, 1) != '/') {
        $docpath = $docpath . '/';
    }
    if (is_dir($docpath)) {
        $handle = opendir($docpath);
        while ($file = @readdir ($handle)) {
            $_filename = basename($file);
            // echo '<p>Working on file ' . $filename . '</p>';

            if (strpos($_filename,"_field_comment.txt")!=false)
            {
                 $_tab = substr($_filename,0,strlen($_filename)-strlen("_field_comment.txt"));
                //echo '<h1>Working on Table ' . $_tab . '</h1>';
                $fd  = fopen($docpath . $file, 'r');
                if ($fd) {
                    while (!feof($fd)) {
                        $line    = fgets($fd, 4096);
                        //echo '<p>' . $line . '</p>';
                        $inf     = explode('|',$line);
                        if (!empty($inf[1]) && strlen(trim($inf[1])) > 0) {
                            $qry = 'INSERT INTO ' . PMA_backquote($cfgRelation['column_comments'])
                                 . ' (db_name, table_name, column_name, comment) '
                                 . ' VALUES('
                                 . '\'' . PMA_sqlAddslashes($db) . '\','
                                 . '\'' . PMA_sqlAddslashes(trim($_tab)) . '\','
                                 . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\','
                                 . '\'' . PMA_sqlAddslashes(trim($inf[1])) . '\')';
                            if (PMA_query_as_cu($qry)) {
                                echo '<p>Added comment for column ' . htmlspecialchars($_tab) . '.' . htmlspecialchars($inf[0]) . '</p>';
                            } else {
                                echo '<p>Writing of comment not possible</p>';
                            }
                            echo "\n";
                        } // end inf[1] exists
                        if (!empty($inf[2]) && strlen(trim($inf[2])) > 0) {
                            $for = explode('->', $inf[2]);
                            $qry = 'INSERT INTO ' . PMA_backquote($cfgRelation['relation'])
                                   . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                                   . ' VALUES('
                                   . '\'' . PMA_sqlAddslashes($db) . '\', '
                                   . '\'' . PMA_sqlAddslashes(trim($_tab)) . '\', '
                                   . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\', '
                                   . '\'' . PMA_sqlAddslashes($db) . '\', '
                                   . '\'' . PMA_sqlAddslashes(trim($for[0])) . '\','
                                   . '\'' . PMA_sqlAddslashes(trim($for[1])) . '\')';
                            if (PMA_query_as_cu($qry)) {
                                echo '<p>Added relation for column ' . htmlspecialchars($_tab) . '.' . htmlspecialchars($inf[0]) . ' to ' . htmlspecialchars($for) . '</p>';
                            } else {
                                echo "<p>writing of Relation not possible</p>";
                            }
                            echo "\n";
                        } // end inf[2] exists
                    }
                    echo '<p><font color="green">Import finished</font></p>' . "\n";
                } else {
                    echo '<p><font color="red">File could not be read</font></p>' . "\n";
                }
            } else {
                echo '<p><font color="yellow">Ignoring file ' . $file . '</font></p>' . "\n";
            } // end working on table
        } // end while
    } else {
        echo 'This was not a Directory' . "\n";
    }
}
?>

<form method="post" action="db_details_importdocsql.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="submit_show" value="true" />
    <input type="hidden" name="do" value="import" />
    <table>
        <tr><th colspan="2">Used Database: <?php echo htmlspecialchars($db); ?></th></tr>
        <tr>
            <th>Please enter absolute path on webserver to docSQL Directory:</th>
            <td><input type="text" name="docpath" size="50" value="<?php if(isset($DOCUMENT_ROOT)) {echo $DOCUMENT_ROOT;} ?>" /></td>
        </tr>
        <tr><th colspan="2"><input type="submit" value="Import files" /></th></tr>
    </table>
</form>