<?php
/* $Id$*/


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');


/**
 * Displays the table of grants for an user
 *
 * @param   integer  the id of the query used to get hosts and databases lists
 * @param   mixed    the database to check garnts for, FALSE for all databases
 *
 * @return  boolean  always true
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 *
 * @see check_db()
 */
function table_grants(&$host_db_result, $dbcheck = FALSE) {
    global $lang, $server;
    ?>

<!-- Table of grants -->
<table border="<?php echo $GLOBALS['cfgBorder']; ?>">
<tr>
    <?php
    // 1. Table headers
    if ($dbcheck) {
        echo "\n";
        echo '    <th>' . $GLOBALS['strAction'] . '</th>' . "\n";
        echo '    <th>' . $GLOBALS['strHost'] . '</th>' . "\n";
        echo '    <th>' . $GLOBALS['strUser'] . '</th>';
    } else {
        echo "\n";
        echo '    <th colspan="2">' . $GLOBALS['strAction'] . '</th>';
    }
    echo "\n";
    echo '    <th>' . $GLOBALS['strDatabase'] . '</th>' . "\n";
    echo '    <th>' . UCFirst($GLOBALS['strTable']) . '</th>' . "\n";
    echo '    <th>' . $GLOBALS['strPrivileges'] . '</th>' . "\n";
    if (!$dbcheck) {
        echo '    <th>Grant Option</th>' . "\n";
    }
    ?>
</tr>
    <?php
    echo "\n";

    // 2. Table body
    $url_query  = 'lang=' . $lang . '&server=' . $server . '&db=mysql&table=user';

    while ($row = mysql_fetch_array($host_db_result)) {
        $local_query = 'SHOW GRANTS FOR \'' . $row['user'] . '\'@\'' . $row['host'] . '\'';
        $result      = mysql_query($local_query);
        $grants_cnt  = @mysql_num_rows($result);

        if ($grants_cnt) {
            $i = 0;
            while ($usr_row = mysql_fetch_row($result)) {
                if (eregi('GRANT (.*) ON ([^\.]+).([^\.]+) TO .*$', $usr_row[0], $parts)) {
                    $priv     = ($parts[1] != 'USAGE') ? trim($parts[1]) : '';
                    $db       = $parts[2];
                    $table    = trim($parts[3]);
                    $grantopt = eregi('WITH GRANT OPTION$', $usr_row[0]);
                } else {
                    $priv     = '';
                    $db       = '&nbsp';
                    $table    = '&nbsp';
                    $column   = '&nbsp';
                    $grantopt = FALSE;
                } // end if...else

                // Checking the database ...
                if ($dbcheck) {
                    if (!eregi($dbcheck . '|\*', $db) || ($priv == '')) {
                        continue;
                    }
                } // end if

                // Password Line
                if ($priv == '' && !$grantopt) {
                    continue;
                }

                $bgcolor    = ($i % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];
                $revoke_url = 'sql.php3'
                            . '?' . $url_query
                            . '&sql_query=' . urlencode('REVOKE ' . $priv . ' ON ' . backquote($db) . '.' . backquote($table) . ' FROM \'' . $row['user'] . '\'@\'' . $row['host'] . '\'')
                            . '&zero_rows=' . urlencode($GLOBALS['strRevokeMessage'] . ' <span style="color: #002E80">' . $row['user'] . '@' . $row['host'] . '</span>')
                            . '&goto=user_details.php3';
                if ($grantopt) {
                    $revoke_grant_url = 'sql.php3'
                                      . '?' . $url_query
                                      . '&sql_query=' . urlencode('REVOKE GRANT OPTION ON ' . backquote($db) . '.' . backquote($table) . ' FROM \'' . $row['user'] . '\'@\'' . $row['host'] . '\'')
                                      . '&zero_rows=' . urlencode($GLOBALS['strRevokeGrantMessage'] . ' <span style="color: #002E80">' . $row['user'] . '@' . $row['host'] . '</span>')
                                      . '&goto=user_details.php3';
                }
                ?>
<tr bgcolor="<?php echo $bgcolor; ?>">
                <?php
                if (!$dbcheck) {
                    if ($priv) {
                        echo "\n";
                        ?>
    <td<?php if (!$grantopt) echo ' colspan="2"'; ?>>
        <a href="<?php echo $revoke_url; ?>">
            <?php echo $GLOBALS['strRevokePriv']; ?></a>
    </td>
                        <?php
                    }
                    if ($grantopt) {
                        echo "\n";
                        ?>
    <td<?php if (!$priv) echo ' colspan="2"'; ?>>
        <a href="<?php echo $revoke_grant_url; ?>">
            <?php echo $GLOBALS['strRevokeGrant']; ?></a>
    </td>
                        <?php
                    }
                } else {
                    if ($priv) {
                        echo "\n";
                        ?>
    <td>
        <a href="<?php echo $revoke_url; ?>">
            <?php echo $GLOBALS['strRevoke']; ?></a>
    </td>
                        <?php
                    } else {
                        echo "\n";
                        ?>
    <td>&nbsp;</td>
                        <?php
                    }
                    echo "\n";
                    ?>
    <td><?php echo $row['host']; ?></td>
    <td><?php echo ($row['user']) ? $row['user'] : '<span style="color: #FF0000">' . $GLOBALS['strAny'] . '</span>'; ?></td>
                    <?php
                }
                echo "\n";
                ?>
    <td><?php echo ($db == '*') ? '<span style="color: #002E80">' . $GLOBALS['strAll'] . '</span>' : $db; ?></td>
    <td><?php echo ($table == '*') ? '<span style="color: #002E80">' . $GLOBALS['strAll'] . '</span>' : $table; ?></td>
    <td><?php echo ($priv != '') ? $priv : '<span style="color: #002E80">' . $GLOBALS['strNoPrivileges'] . '</span>'; ?></td>
                <?php
                if (!$dbcheck) {
                    echo "\n";
                    ?>
    <td><?php echo ($grantopt) ? $GLOBALS['strYes'] : $GLOBALS['strNo']; ?></td>
                    <?php
                }
                echo "\n";
                ?>
    <!-- Debug <td><?php echo $usr_row[0] ?></td> Debug -->
</tr>
                <?php
                $i++;
                echo "\n";
            } // end while $usr_row
        } // end if $grants_cnt >0
    } // end while $row
    ?>
</table>
<hr />

    <?php
    echo "\n";

    return TRUE;
} // end of the 'table_grants()' function


/**
 * Displays the list of grants for a/all database/s
 *
 * @param   mixed    the database to check garnts for, FALSE for all databases
 *
 * @return  boolean  true/false in case of success/failure
 *
 * @see table_grants()
 */
function check_db($dbcheck)
{
    $local_query  = 'SELECT host, user FROM mysql.user ORDER BY host, user';
    $result       = mysql_query($local_query);
    $host_usr_cnt = @mysql_num_rows($result);

    if (!$host_usr_cnt) {
        return FALSE;
    }
    table_grants($result, $dbcheck);

    return TRUE;
} // end of the 'check_db()' function


/**
 * Displays the privileges part of a page
 *
 * @param   string   the name of the form for js validation
 * @param   array    the list of the privileges of the user
 *
 * @return  boolean  always true
 *
 * @see normal_operations()
 */
function table_privileges($form, $row = FALSE)
{
    ?>

            <table>
    <?php
    echo "\n";
    $list_priv = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Reload',
                       'Shutdown', 'Process', 'File', 'Grant', 'References', 'Index', 'Alter');
    $item      = 0;
    while ((list(,$priv) = each($list_priv)) && ++$item) {
        $priv_priv = $priv . '_priv';
        $checked   = ($row && $row[$priv_priv] == 'Y') ?  ' checked="checked"' : '';
        if ($item % 2 == 1) {
            echo '            <tr>' . "\n";
        } else {
            echo '                <td>&nbsp;</td>' . "\n";
        }
        echo '                <td>' . "\n";
        echo '                    <input type="checkbox" name="' . $priv . '_priv"' . $checked . ' />' . "\n";
        echo '                </td>' . "\n";
        echo '                <td>' . $priv . '</td>' . "\n";
        if ($item % 2 == 0) {
            echo '            </tr>' . "\n";
        }
    } // end while
    if ($item % 2 == 1) {
        echo '                <td colspan="2">&nbsp;<td>' . "\n";
        echo '            </tr>' . "\n";
    } // end if
    ?>
            </table>
            <table>
            <tr>
                <td>
                    <a href="#" onclick="checkForm('<?php echo $form; ?>', true); return false">
                        <?php echo $GLOBALS['strCheckAll']; ?></a>
                </td>
                <td>&nbsp;</td>
                <td>
                    <a href="#" onclick="checkForm('<?php echo $form; ?>', false); return false">
                        <?php echo $GLOBALS['strUncheckAll']; ?></a>
                </td>
            </tr>
            </table>
    <?php
    echo "\n";

    return TRUE;
} // end of the 'table_privileges()' function


/**
 * Displays the page for "normal" operations
 *
 * @return  boolean  always true
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 *
 * @see table_privileges()
 */
function normal_operations()
{
    global $lang, $server;
    ?>

<ul>

    <li>
        <div style="margin-bottom: 10px">
        <a href="user_details.php3?lang=<?php echo $lang; ?>&server=<?php echo $server; ?>&db=mysql&table=user&mode=reload">
            <?php echo $GLOBALS['strReloadMySQL']; ?></a>&nbsp;
        <?php print show_docu('manual_Reference.html#FLUSH'); ?>
        </div>
    </li>

    <li>
        <form name="dbPrivForm" action="user_details.php3" method="post">
            <?php echo $GLOBALS['strCheckDbPriv'] . "\n"; ?>
            <table>
            <tr>
                <td>
                    <?php echo $GLOBALS['strDatabase']; ?>&nbsp;:&nbsp;
                    <select name="db">
    <?php
    echo "\n";
    $result = mysql_query('SHOW DATABASES');
    if (@mysql_num_rows($result)) {
        while ($row = mysql_fetch_row($result)) {
            echo '                        ';
            echo '<option value="' . str_replace('"', '&quot;', $row[0]) . '">' . htmlspecialchars($row[0]) . '</option>' . "\n";
        } // end while
    } // end if
    ?>
                    </select>
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    <input type="hidden" name="check" value="1" />
                    <input type="submit" value="<?php echo $GLOBALS['strGo']; ?>" />
                </td>
            </tr>
            </table>
        </form>
    </li>

    <li>
        <form action="user_details.php3" method="post" name="addUserForm" onsubmit="return checkAddUser()">
            <?php echo $GLOBALS['strAddUser'] . "\n"; ?>
            <table>
            <tr>
                <td>
                    <input type="radio" name="anyhost" checked="checked" />
                    <?php echo $GLOBALS['strAnyHost']; ?>
                </td>
                <td>&nbsp;</td>
                <td>
                    <input type="radio" name="anyhost" />
                    <?php echo $GLOBALS['strHost']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <input type="text" name="host" size="10" onchange="this.form.anyhost[1].checked = true" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="radio" name="anyuser" />
                    <?php echo $GLOBALS['strAnyUser']; ?>
                </td>
                <td>&nbsp;</td>
                <td>
                    <input type="radio" name="anyuser" checked="checked" />
                    <?php echo $GLOBALS['strUserName']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <input type="text" name="pma_user" size="10" onchange="this.form.anyuser[1].checked = true" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="radio" name="nopass" value="1" />
                    <?php echo $GLOBALS['strNoPassword'] . "\n"; ?>
                </td>
                <td>&nbsp;</td>
                <td>
                    <input type="radio" name="nopass" value="0" checked="checked" />
                    <?php echo $GLOBALS['strPassword']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <input type="password" name="pma_pw" size="10" onchange="nopass[1].checked = true" />
                    &nbsp;&nbsp;
                    <?php echo $GLOBALS['strReType']; ?>&nbsp;:&nbsp;
                    <input type="password" name="pma_pw2" size="10" onchange="nopass[1].checked = true" />
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <br />
                    <?php echo $GLOBALS['strPrivileges']; ?>&nbsp;:
                    <br />
                </td>
            </tr>
            </table>
    <?php
    echo "\n";
    table_privileges('addUserForm');
    ?>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="submit" name="submit_addUser" value="<?php echo $GLOBALS['strGo']; ?>" />
        </form>
    </li>

</ul>
    <?php

    return TRUE;
} // end of the 'normal_operations()' function


/**
 * Displays the grant operations part of the page
 *
 * @param   array    grants of the current user
 *
 * @return  boolean  always true
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 * @global  string   the host name to check grants for
 * @global  string   the username to check grants for
 * @global  string   the database to check grants for
 * @global  string   the table to check grants for
 *
 * @see table_privileges()
 */
function grant_operations($grants)
{
    global $lang, $server, $host, $pma_user;
    global $dbgrant, $tablegrant;
    ?>

<ul>

    <li>
        <div style="margin-bottom: 10px">
        <a href="user_details.php3?lang=<?php echo $lang; ?>&server=<?php echo $server; ?>&db=mysql&table=user">
            <?php echo $GLOBALS['strBack']; ?></a>
        </div>
    </li>

    <li>
        <form action="user_details.php3" method="post" name="userGrants">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="grants" value="1" />
            <input type="hidden" name="host" value="<?php echo str_replace('"', '&quot;', $host); ?>" />
            <input type="hidden" name="pma_user" value="<?php echo str_replace('"', '&quot;', $pma_user); ?>" />

            <?php echo $GLOBALS['strAddPriv'] . "\n"; ?>
            <table>
            <tr>
                <td>
                    <input type="radio" name="anydb" value="1"<?php echo ($dbgrant) ? '' : ' checked="checked"'; ?> />
                    <?php echo $GLOBALS['strAnyDatabase'] . "\n"; ?>
                </td>
                <td>&nbsp;&nbsp;&nbsp;</td>
                <td>
                    <input type="radio" name="anydb" value="0"<?php echo ($dbgrant) ? ' checked="checked"' : ''; ?> />
                    <?php echo $GLOBALS['strDatabase']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <select name="dbgrant" onchange="change(this)">
    <?php
    echo "\n";
    if (!isset($dbgrant)) {
        echo '                        ';
        echo '<option selected="selected"></option>' . "\n";
    }
    $result = mysql_query('SHOW DATABASES');
    if (@mysql_num_rows($result)) {
        while ($row = mysql_fetch_row($result)) {
            $selected = (($row[0] == $dbgrant) ? ' selected="selected"' : '');
            echo '                        ';
            echo '<option' . $selected . '>' . $row[0] . '</option>' . "\n";
        } // end while
    } // end if
    ?>
                    </select>
                </td>
                <td>
                    &nbsp;
                    <input type="submit" value="<?php echo $GLOBALS['strShowTables']; ?>" />
                </td>
            </tr>
            <tr>
                <td>
                    <input type="radio" name="anytable" value="1"<?php echo ($tablegrant) ? '' : ' checked="checked"'; ?> />
                    <?php echo $GLOBALS['strAnyTable'] . "\n"; ?>
                </td>
                <td>&nbsp;&nbsp;&nbsp;</td>
                <td>
                    <input type="radio" name="anytable" value="0"<?php echo ($tablegrant) ? ' checked="checked"' : ''; ?> />
                    <?php echo $GLOBALS['strTable']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <select name="tablegrant" onchange="change(this)">
    <?php
    echo "\n";
    if (!isset($tablegrant)) {
        echo '                        ';
        echo '<option selected="selected"></option>' . "\n";
    }
    if (isset($dbgrant)) {
        $result = mysql_query('SHOW TABLES FROM ' . backquote($dbgrant));
        if (@mysql_num_rows($result)) {
            while ($row = mysql_fetch_row($result)) {
                $selected = ((isset($tablegrant) && $row[0] == $tablegrant) ? ' selected="selected"' : '');
                echo '                        ';
                echo '<option' . $selected . '>' . $row[0] . '</option>' . "\n";
            } // end while
        } // end if
    } // end if
    ?>
                    </select>
                </td>
                <td>
                    &nbsp;
                    <input type="submit" value="<?php echo $GLOBALS['strShowCols']; ?>" />
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <input type="radio" name="anycolumn" value="1" checked="checked" />
                    <?php echo $GLOBALS['strAnyColumn'] . "\n"; ?>
                </td>
                <td>&nbsp;&nbsp;&nbsp;</td>
                <td valign="top">
                    <input type="radio" name="anycolumn" value="0" />
                    <?php echo $GLOBALS['strColumn']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <select name="colgrant[]" multiple="multiple" onchange="anycolumn[1].checked = true">
    <?php
    echo "\n";
    if (!isset($dbgrant) || !isset($tablegrant)) {
        echo '                        ';
        echo '<option></option>' . "\n";
    }
    if (isset($dbgrant) && isset($tablegrant)) {
        $result = mysql_query('SHOW COLUMNS FROM ' . backquote($dbgrant) . '.' . backquote($tablegrant));
        if (@mysql_num_rows($result)) {
            while ($row = mysql_fetch_row($result)) {
                echo '                        ';
                echo '<option value="' . str_replace('"', '&quot;', $row[0]) . '">' . $row[0] . '</option>' . "\n";
            } // end while
        } // end if
    } // end if
    ?>
                    </select>
                </td>
                <td></td>
            </tr>
            </table>

            <table>
            <tr>
                <td>
                    <br />
                    <?php echo $GLOBALS['strPrivileges']; ?>&nbsp;:&nbsp;
                    <br />
                </td>
            </tr>
            </table>
    <?php
    echo "\n";
    table_privileges('userGrants', $grants);
    ?>
            <input type="submit" name="upd_grants" value="<?php echo $GLOBALS['strGo']; ?>" />
        </form>
    </li>

</ul>
    <?php
    echo "\n";

    return TRUE;
} // end of the 'grant_operations()' function


/**
 * Displays the page to edit operations
 *
 * @param   string   the host name
 * @param   string   the user name
 *
 * @return  boolean  always true
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 * @global  string   the host name to check grants for
 * @global  string   the username to check grants for
 *
 * @see table_privileges()
 */
function edit_operations($host, $user)
{
    global $lang, $server;
    global $host, $pma_user;

    $result = mysql_query('SELECT * FROM mysql.user WHERE user = \'' . $user . '\' and host = \'' . $host . '\'');
    $rows   = @mysql_num_rows($result);

    if (!$rows) {
        return FALSE;
    }

    $row = mysql_fetch_array($result);
    ?>

<ul>

    <li>
        <div style="margin-bottom: 10px">
        <a href="user_details.php3?lang=<?php echo $lang; ?>&server=<?php echo $server; ?>&db=mysql&table=user">
            <?php echo $GLOBALS['strBack']; ?></a>
        </div>
    </li>

    <li>
        <form action="user_details.php3" method="post" name="passForm" onsubmit="return checkPassword(this)">
            <?php echo $GLOBALS['strUpdatePassword'] . "\n"; ?>
            <table>
            <tr>
                <td>
                    <input type="radio" name="nopass" value="1"<?php if ($row['Password'] == '') echo ' checked="checked"'; ?> />
                    <?php echo $GLOBALS['strNoPassword'] . "\n"; ?>
                </td>
                <td>&nbsp;</td>
                <td>
                    <input type="radio" name="nopass" value="0"<?php if ($row['Password'] != '') echo ' checked="checked"'; ?> />
                    <?php echo $GLOBALS['strPassword']; ?>&nbsp;:&nbsp;
                </td>
                <td>
                    <input type="password" name="pma_pw" size="10" onchange="nopass[1].checked = true" />
                    &nbsp;&nbsp;
                    <?php echo $GLOBALS['strReType']; ?>&nbsp;:&nbsp;
                    <input type="password" name="pma_pw2" size="10" onchange="nopass[1].checked = true" />
                </td>
            </tr>
            </table>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="host" value="<?php echo str_replace('"', '&quot;', $host); ?>" />
            <input type="hidden" name="pma_user" value="<?php echo str_replace('"', '&quot;', $pma_user); ?>" />
            <input type="submit" name="submit_chgPswd" value="<?php echo $GLOBALS['strGo']; ?>" />
        </form>
    </li>

    <li>
        <form action="user_details.php3" method="post" name="privForm">
            <?php echo $GLOBALS['strEditPrivileges'] . "\n"; ?>
    <?php
    table_privileges('privForm', $row);
    echo "\n";
    ?>
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="host" value="<?php echo str_replace('"', '&quot;', $host); ?>" />
            <input type="hidden" name="pma_user" value="<?php echo str_replace('"', '&quot;', $pma_user); ?>" />
            <input type="submit" name="submit_chgPriv" value="<?php echo $GLOBALS['strGo']; ?>" />
        </form>
    </li>

</ul>
    <?php
    echo "\n";

    return TRUE;
} // end of the 'edit_operations()' function


/**
 * Checks whether the current user is a super-user or not
 *
 * @return  boolean  true as soon as the current user is super-user, no return
 *                   else
 *
 * @access  private
 */
function check_rights()
{
    $result = @mysql_query('USE mysql');
    if (mysql_error()) {
        mysql_die($GLOBALS['strNoRights'], '', FALSE, FALSE);
    }

    return true;
} // end of the 'check_rights()' function


/**
 * Displays the table of the users
 *
 * @param   string   the host name
 * @param   string   the user name
 *
 * @return  boolean  always true
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 */
function table_users($host = FALSE, $user = FALSE)
{
    global $lang, $server;

    $local_query     = 'SELECT * FROM mysql.user ';
    if ($host || $user) {
        $local_query .= ' WHERE 1 ';
    }
    if ($host) {
        $local_query .= ' AND host = \'' . $host . '\'';
        $local_query .= ' AND user = \'' . $user. '\'';
    }
    $local_query     .= ' ORDER BY host, user';
    $result          = mysql_query($local_query);
    $rows            = @mysql_num_rows($result);

    if (!$rows) {
        return FALSE;
    }

    echo '<i>' . $GLOBALS['strEnglishPrivileges'] . '</i><br />' . "\n";
    echo '<table border="' . $GLOBALS['cfgBorder'] . '">' . "\n";
    echo '<tr>' . "\n";
    echo '    <th colspan="'. (($user) ? '2' : '3') . '">' . $GLOBALS['strAction'] . '</th>' . "\n";
    echo '    <th>' . $GLOBALS['strHost'] . '</th>' . "\n";
    echo '    <th>' . $GLOBALS['strUser'] . '</th>' . "\n";
    echo '    <th>' . $GLOBALS['strPassword'] . '</th>' . "\n";
    echo '    <th>' . $GLOBALS['strPrivileges'] . '</th>' . "\n";
    echo '</tr>' . "\n";

    $i = 0;
    while ($row = mysql_fetch_array($result)) {

        $bgcolor = ($i % 2) ? $GLOBALS['cfgBgcolorOne'] : $GLOBALS['cfgBgcolorTwo'];

        $strPriv     = '';
        if ($row['Select_priv'] == 'Y') {
            $strPriv .= 'Select ';
        }
        if ($row['Insert_priv'] == 'Y') {
            $strPriv .= 'Insert ';
        }
        if ($row['Update_priv'] == 'Y') {
            $strPriv .= 'Update ';
        }
        if ($row['Delete_priv'] == 'Y') {
            $strPriv .= 'Delete ';
        }
        if ($row['Create_priv'] == 'Y') {
            $strPriv .= 'Create ';
        }
        if ($row['Drop_priv'] == 'Y') {
            $strPriv .= 'Drop ';
        }
        if ($row['Reload_priv'] == 'Y') {
            $strPriv .= 'Reload ';
        }
        if ($row['Shutdown_priv'] == 'Y') {
            $strPriv .= 'Shutdown ';
        }
        if ($row['Process_priv'] == 'Y') {
            $strPriv .= 'Process ';
        }
        if ($row['File_priv'] == 'Y') {
            $strPriv .= 'File ';
        }
        if ($row['Grant_priv'] == 'Y') {
            $strPriv .= 'Grant ';
        }
        if ($row['References_priv'] == 'Y') {
            $strPriv .= 'References ';
        }
        if ($row['Index_priv'] == 'Y') {
            $strPriv .= 'Index ';
        }
        if ($row['Alter_priv'] == 'Y') {
            $strPriv .= 'Alter ';
        }
        if ($strPriv == '') {
            $strPriv = '<span style="color: #002E80">' . $GLOBALS['strNoPrivileges'] . '</span>';
        }

        $query          = 'lang=' . $lang . '&server=' . $server . '&db=mysql&table=user';
        if (!$user) {
            $edit_url   = 'user_details.php3'
                        . '?lang=' . $lang . '&server=' . $server
                        . '&edit=1&host=' . urlencode($row['Host']) . '&pma_user=' . urlencode($row['User']);
        }
        $delete_url     = 'user_details.php3'
                        . '?' . $query
                        . '&delete=1&confirm=1&delete_host=' . urlencode($row['Host']) . '&delete_user=' . urlencode($row['User']);
        $check_url      = 'user_details.php3'
                        . '?lang=' . $lang . '&server=' . $server
                        . '&grants=1&host=' . urlencode($row['Host']) . '&pma_user=' . urlencode($row['User']);

//        $check_result = mysql_query('SHOW GRANTS FOR \'' . $row['User'] . '\'@\'' . $row['Host'] . '\'');
//        if (@mysql_num_rows($check_result) == 0) {
//            $check_url = '';
//        }
        ?>

<tr bgcolor="<?php echo $bgcolor;?>">
        <?php
        if (!$user) {
            echo "\n";
            ?>
    <td>
        <a href="<?php echo $edit_url; ?>">
            <?php echo $GLOBALS['strEdit']; ?></a>
    </td>
            <?php
        }
        echo "\n";
        ?>
    <td>
        <a href="<?php echo $delete_url; ?>">
            <?php echo $GLOBALS['strDelete']; ?></a>
    </td>
    <td>
        <a href="<?php echo $check_url; ?>">
            <?php echo $GLOBALS['strGrants']; ?></a>
    </td>
<!--
    <td>
        <a href="<?php echo (($check_url != '') ? $check_url : '#'); ?>">
            <?php echo $GLOBALS['strGrants']; ?></a>
    </td>
-->
    <td>
        <?php echo $row['Host'] . "\n"; ?>
    </td>
    <td>
        <?php echo (($row['User']) ? '<b>' . $row['User'] . '</b>' : '<span style="color: #FF0000">' . $GLOBALS['strAny'] . '</span>') . "\n"; ?>
    </td>
    <td>
        <?php echo (($row['Password']) ? $GLOBALS['strYes'] : '<span style="color: #FF0000">' . $GLOBALS['strNo'] . '</span>') . "\n"; ?>
    </td>
    <td>
        <?php echo $strPriv . "\n"; ?>
    </td>
</tr>
        <?php
        echo "\n";
        $i++;
    } //  end while

    echo "\n";
    ?>
</table>
<hr />
    <?php
    echo "\n";

    return TRUE;
} // end of the 'table_users()' function


/**
 * Displays a confirmation form
 *
 * @param   string   the host name and...
 * @param   string   ... the username to delete
 *
 * @global  string   the current language
 * @global  integer  the server to use (refers to the number in the
 *                   configuration file)
 */
function confirm($the_host, $the_user) {
    global $lang, $server;

    if (get_magic_quotes_gpc() == 1) {
        $the_host = stripslashes($the_host);
        $the_user = stripslashes($the_user);
    }

    echo $GLOBALS['strConfirm'] . '&nbsp;:&nbsp<br />' . "\n";
    echo 'DELETE FROM mysql.user WHERE host = \'' . $the_host . '\' AND user = \'' . $the_user . '\'' . '<br />' . "\n";
    ?>
<form action="user_details.php3" method="post">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="mysql" />
    <input type="hidden" name="table" value="user" />
    <input type="hidden" name="delete" value="<?php echo(isset($GLOBALS['delete']) ? '1' : '0'); ?>" />
    <input type="hidden" name="delete_host" value="<?php echo str_replace('"', '&quot;', $the_host); ?>" />
    <input type="hidden" name="delete_user" value="<?php echo str_replace('"', '&quot;', $the_user); ?>" />
    <input type="submit" name="btnConfirm" value="<?php echo $GLOBALS['strYes']; ?>" />
    <input type="submit" name="btnConfirm" value="<?php echo $GLOBALS['strNo']; ?>" />
</form>
    <?php
    echo "\n";

    include('./footer.inc.php3');
} // end of the 'confirm()' function



/**
 * Ensure the user is super-user and display headers
 */
check_rights();

if (empty($host)) {
    $db    = 'mysql';
    $table = 'user';
} else if (get_magic_quotes_gpc()) {
    $host         = stripslashes($host);
    if (!empty($pma_user)) {
        $pma_user = stripslashes($pma_user);
    }
}
    
$js_to_run = 'user_details.js';
include('./header.inc.php3');
if (!empty($host)) {
    echo '<h1>' . "\n";
    echo '    ' . $strHost . ' ' . $host . ' - ' . $strUser . ' ' . (($pma_user) ?  $pma_user : $strAny) . "\n";
    echo '</h1>';
}


/**
 * Some actions has been submitted
 */
// Confirms an action
if (isset($confirm) && $confirm) {
    confirm($delete_host, $delete_user);
    exit();
}

// Reloads mysql
else if (($server > 0) && isset($mode) && ($mode == 'reload')) {
    $result = mysql_query('FLUSH PRIVILEGES');
    if ($result != 0) {
        echo '<p><b>' . $strMySQLReloaded . '</b></p>' . "\n";
    } else {
        echo '<p><b>' . $strReloadFailed . '</b></p>' . "\n";
    }
}

// Deletes an user
else if (isset($delete) && $delete
         && isset($btnConfirm) && $btnConfirm == $strYes) {
    if (get_magic_quotes_gpc()) {
        $delete_host  = stripslashes($delete_host);
        $delete_user  = stripslashes($delete_user);
    }
    $common_where     = ' WHERE host = \'' . sql_addslashes($delete_host) . '\' AND user = \'' . sql_addslashes($delete_user) . '\'';

    // Delete Grants First!
    mysql_query('DELETE FROM mysql.columns_priv' . $common_where);
    mysql_query('DELETE FROM mysql.db' . $common_where);
    mysql_query('DELETE FROM mysql.tables_priv' . $common_where);

    $result = mysql_query('DELETE FROM mysql.user' . $common_where);
    if ($result) {
        echo '<p><b>' . $strDeleteUserMessage . ' <span style="color: #002E80">' . $delete_user . '@' . $delete_host . '</span><br />';
        echo '    ' . $strRememberReload . '</b></p>';
    } else {
        echo '<p><b>' . $strDeleteFailed . '</b></p>';
    }
}

// Adds an user
else if (isset($submit_addUser)) {
    $show_query   = 'y';
    if (empty($host)) {
        $host     = '%';
    }
    if (empty($pma_user)) {
        $pma_user = '%';
    }

    // Password is not confirmed
    if ((!isset($nopass) || !$nopass) && empty($pma_pw)) {
        echo '<p><b>' . $strError . '&nbsp;:&nbsp;' . $strPasswordEmpty . '</b></p>' . "\n";
        unset($host);
        unset($pma_user);
    }
    else if (!empty($pma_pw)
        && (!isset($pma_pw2) || $pma_pw != $pma_pw2)) {
        echo '<p><b>' . $strError . '&nbsp;:&nbsp;' . $strPasswordNotSame . '</b></p>' . "\n";
        unset($host);
        unset($pma_user);
    }

    // Password confirmed
    else {
        $sql_query = '';
        $list_priv = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Reload',
                           'Shutdown', 'Process', 'File', 'Grant', 'References', 'Index', 'Alter');
        for ($i = 0; $i < 14; $i++) {
            $priv_name = $list_priv[$i] . '_priv';
            if (isset($$priv_name)) {
                $sql_query .= (empty($sql_query) ? $priv_name : ', ' . $priv_name) . ' = \'Y\'';
            } else  {
                $sql_query .= (empty($sql_query) ? $priv_name : ', ' . $priv_name) . ' = \'N\'';
            }
        } // end for
        unset($list_priv);

        $sql_query  = 'INSERT INTO mysql.user '
                    . 'SET host = \'' . sql_addslashes($host) . '\', user = \'' . sql_addslashes($pma_user) . '\', password = ' . (empty($pma_pw) ? '\'\'' : 'PASSWORD(\'' . sql_addslashes($pma_pw) . '\')')
                    . ', ' . $sql_query;
        $result     = @mysql_query($sql_query) or mysql_die('', '', FALSE);
        unset($host);
        unset($pma_user);
        show_message($strAddUserMessage . '<br />' . $strRememberReload);
    } // end else
}

// Changes the password of an user
else if (isset($submit_chgPswd)) {
    $show_query   = 'y';
    $edit         = TRUE;
    if (empty($host)) {
        $host     = '%';
    }
    if (empty($pma_user)) {
        $pma_user = '%';
    }

    // Password is not confirmed
    if ((!isset($nopass) || !$nopass) && empty($pma_pw)) {
        echo '<p><b>' . $strError . '&nbsp;:&nbsp;' . $strPasswordEmpty . '</b></p>' . "\n";
    }
    else if (!empty($pma_pw)
        && (!isset($pma_pw2) || $pma_pw != $pma_pw2)) {
        echo '<p><b>' . $strError . '&nbsp;:&nbsp;' . $strPasswordNotSame . '</b></p>' . "\n";
    }

    // Password confirmed
    else {
        $sql_query  = 'UPDATE user '
                    . 'SET password = ' . (empty($pma_pw) ? '\'\'' : 'PASSWORD(\'' . sql_addslashes($pma_pw) . '\')') . ' '
                    . 'WHERE user = \'' . sql_addslashes($pma_user) . '\' AND host = \'' . sql_addslashes($host) . '\'';
        $result     = @mysql_query($sql_query) or mysql_die('', '', FALSE);
        show_message($strUpdatePassMessage . ' <span style="color: #002E80">' . $pma_user . '@' . $host . '</span><br />' . $strRememberReload);
    } // end else
}

// Changes the privileges of an user
else if (isset($submit_chgPriv)) {
    $show_query   = 'y';
    $edit         = TRUE;
    if (empty($host)) {
        $host     = '%';
    }
    if (empty($pma_user)) {
        $pma_user = '%';
    }

    $sql_query = '';
    $list_priv = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Reload',
                       'Shutdown', 'Process', 'File', 'Grant', 'References', 'Index', 'Alter');
    for ($i = 0; $i < 14; $i++) {
        $priv_name = $list_priv[$i] . '_priv';
        if (isset($$priv_name)) {
            $sql_query .= (empty($sql_query) ? $priv_name : ', ' . $priv_name) . ' = \'Y\'';
        } else  {
            $sql_query .= (empty($sql_query) ? $priv_name : ', ' . $priv_name) . ' = \'N\'';
        }
    } // end for
    unset($list_priv);

    $sql_query = 'UPDATE user SET '
               . $sql_query
               . ' WHERE host = \'' . sql_addslashes($host) . '\' AND user = \'' . sql_addslashes($pma_user) . '\'';
    $result     = @mysql_query($sql_query) or mysql_die('', '', FALSE);
    show_message($strUpdatePrivMessage . ' <span style="color: #002E80">' . $pma_user . '@' . $host . '</span><br />' . $strRememberReload);
}

// Revoke/Grant privileges
else if (isset($grants) && $grants) {
    $show_query   = 'y';
    if (empty($host)) {
        $host     = '%';
    }
    if (empty($pma_user)) {
        $pma_user = '%';
    }

    if (isset($upd_grants)) {
        $sql_query = '';
        $col_list  = '';

        if (isset($colgrant) && !$anycolumn) {
            $colgrant_cnt = count($colgrant);
            for ($i = 0; $i < $colgrant_cnt; $i++) {
                if (get_magic_quotes_gpc()) {
                    $colgrant[$i] = stripslashes($colgrant[$i]);
                }
                $col_list .= (empty($col_list) ?  backquote($colgrant[$i]) : ', ' . backquote($colgrant[$i]));
            } // end for
            unset($colgrant);
            $col_list     = ' (' . $col_list . ')';
        } // end if

        $list_priv = array('Select', 'Insert', 'Update', 'Delete', 'Create', 'Drop', 'Reload',
                           'Shutdown', 'Process', 'File', 'Grant', 'References', 'Index', 'Alter');
        for ($i = 0; $i < 14; $i++) {
            $priv_name = $list_priv[$i] . '_priv';
            if (isset($$priv_name)) {
                $sql_query .= (empty($sql_query) ? $list_priv[$i] : ', ' . $list_priv[$i]) . $col_list;
            }
        } // end for
        unset($list_priv);

        $sql_query .= ' ON '
                   . (($anydb || empty($dbgrant)) ? '*' : backquote($dbgrant))
                   . '.'
                   . (($anytable || empty($tablegrant)) ? '*' : backquote($tablegrant));

        $sql_query .= ' TO ' . '\'' . sql_addslashes($pma_user) . '\'' . '@' . '\'' . sql_addslashes($host) . '\'';

        $sql_query  = 'GRANT ' . $sql_query;
        $result     = @mysql_query($sql_query) or mysql_die('', '', FALSE);
        show_message($strAddPrivMessage);
    } // end if
}



/**
 * Displays the page
 */
// Edit an user properies
if (isset($edit) && $edit) {
    table_users($host, $pma_user);
    edit_operations($host, $pma_user);
}

// Revoke/Grant privileges for an user
else if (isset($grants) && $grants) {
    $result = mysql_query('SELECT * FROM mysql.user WHERE host = \'' . sql_addslashes($host) . '\' AND user = \'' . sql_addslashes($pma_user) . '\'');
    grant_operations(mysql_fetch_array($result));
}

// Check database privileges
else if (isset($check) && $check) {
    check_db($db);
    ?>
<ul>
    <li>
        <a href="user_details.php3?lang=<?php echo $lang;?>&server=<?php echo $server; ?>&db=mysql&table=user">
            <?php echo $strBack; ?></a>
    </li>
</ul>
    <?php
    echo "\n";
}

// Displays all users profiles
else {
    if (!isset($host)) {
        $host = FALSE;
    }
    if (!isset($pma_user)) {
        $pma_user = FALSE;
    }
    table_users($host, $pma_user) or mysql_die($strNoUsersFound, '', FALSE, FALSE);
    normal_operations();
}


/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
