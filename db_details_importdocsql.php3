<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This script imports relation infos from docSQL (www.databay.de)
 */


/**
 * Get the values of the variables posted or sent to this script and display
 * the headers
 */
require('./libraries/grab_globals.lib.php3');
require('./header.inc.php3');


/**
 * Executes import if required
 */
if (isset($do) && $do == 'import') {
    // echo '<h1>Starting Import</h1>';
    if (substr($docpath, strlen($docpath) - 2, 1) != '/') {
        $docpath = $docpath . '/';
    }
    if (is_dir($docpath)) {
        // Get relation settings
        include('./libraries/relation.lib.php3');
        $cfgRelation = PMA_getRelationsParam();

        // Do the work
        $handle = opendir($docpath);
        while ($file = @readdir($handle)) {
            $filename = basename($file);
            // echo '<p>Working on file ' . $filename . '</p>';
            if (strpos(' ' . $filename, '_field_comment.txt')) {
                 $tab = substr($filename, 0, strlen($filename) - strlen('_field_comment.txt'));
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
                                 . '\'' . PMA_sqlAddslashes(trim($tab)) . '\','
                                 . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\','
                                 . '\'' . PMA_sqlAddslashes(trim($inf[1])) . '\')';
                            if (PMA_query_as_cu($qry)) {
                                echo '<p>Added comment for column ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]) . '</p>';
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
                                   . '\'' . PMA_sqlAddslashes(trim($tab)) . '\', '
                                   . '\'' . PMA_sqlAddslashes(trim($inf[0])) . '\', '
                                   . '\'' . PMA_sqlAddslashes($db) . '\', '
                                   . '\'' . PMA_sqlAddslashes(trim($for[0])) . '\','
                                   . '\'' . PMA_sqlAddslashes(trim($for[1])) . '\')';
                            if (PMA_query_as_cu($qry)) {
                                echo '<p>Added relation for column ' . htmlspecialchars($tab) . '.' . htmlspecialchars($inf[0]) . ' to ' . htmlspecialchars($for) . '</p>';
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


/**
 * Try to get the "$DOCUMENT_ROOT" variable whatever is the register_globals
 * value
 */
if (empty($DOCUMENT_ROOT)) {
    if (!empty($_SERVER) && isset($_SERVER['DOCUMENT_ROOT'])) {
        $DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];
    }
    else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['DOCUMENT_ROOT'])) {
        $DOCUMENT_ROOT = $HTTP_SERVER_VARS['DOCUMENT_ROOT'];
    }
    else if (!empty($_ENV) && isset($_ENV['DOCUMENT_ROOT'])) {
        $DOCUMENT_ROOT = $_ENV['DOCUMENT_ROOT'];
    }
    else if (!empty($HTTP_ENV_VARS) && isset($HTTP_ENV_VARS['DOCUMENT_ROOT'])) {
        $DOCUMENT_ROOT = $HTTP_ENV_VARS['DOCUMENT_ROOT'];
    }
    else if (@getenv('DOCUMENT_ROOT')) {
        $DOCUMENT_ROOT = getenv('DOCUMENT_ROOT');
    }
    else {
        $DOCUMENT_ROOT = '';
    }
} // end if


/**
 * Displays the form
 */
?>

<form method="post" action="db_details_importdocsql.php3">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo htmlspecialchars($db); ?>" />
    <input type="hidden" name="submit_show" value="true" />
    <input type="hidden" name="do" value="import" />
    <b>Please enter absolute path on webserver to docSQL Directory:</b>
    <br /><br />
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" name="docpath" size="50" value="<?php echo htmlspecialchars($DOCUMENT_ROOT); ?>" />
    &nbsp;<input type="submit" value="Import files" />
</form>

<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
