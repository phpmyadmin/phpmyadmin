<?php
/* $Id$*/

require("./grab_globals.inc.php3");

function check_operations()
{
    global $server, $lang;
    global $strBack;
    global $self;
    ?>

    <div align=left>
    <ul>
    <li><a href="<? echo "$self?server=$server&lang=$lang&db=mysql&table=user"; ?>"><? echo $strBack; ?></a></li>
    </ul>
    </div>

    <?
}

function check_db($dbcheck)
{
    $select = "SELECT host, user FROM mysql.user ORDER BY host, user";
    $result = mysql_query($select);
    $rows = @mysql_num_rows($result);

    # Errors
    if (!isset($rows)) return -1;
    if ($rows == 0) return 0;

    table_grants_header($dbcheck);

    while ($row = mysql_fetch_array($result))
       table_grants($row{"host"}, $row{"user"}, $dbcheck);

    table_grants_tail();
}

function normal_operations()
{
    global $server, $lang, $self;
    global $strReloadMySQL, $strGo;

    global $strHostEmpty, $strUserEmpty, $strPasswordEmpty;
    global $strPasswordNotSame, $strAddUserMessage, $strRememberReload;
    global $strDatabase, $strHost, $strAnyHost, $strUserName;
    global $strAnyUser, $strPassword, $strNoPassword, $strReType;
    global $strPrivileges, $strAddUser, $strCheckDbPriv;

    ?>

    <script language="JavaScript">
    <!--

    function addUser(f) {
        var sql = "INSERT INTO user SET ";

        if (f.anyhost[0].checked) {
           if (f.host.value == "") {
               alert("<? echo $strHostEmpty; ?>");
               return;
              }
           sql += "host = '" + f.host.value + "'";
        } else sql += "host = '%'"

        if (f.anyuser[0].checked) {
           if (f.user.value == "") {
               alert("<? echo $strUserEmpty; ?>");
               return;
              }
           sql += ", user = '" + f.user.value + "'";
        } else sql += ", user = ''"

        if (f.nopass[0].checked) {
           if (f.pw.value == "") {
               alert("<? echo $strPasswordEmpty; ?>");
               return;
              }

           if (f.pw.value != f.pw2.value) {
               alert("<? echo $strPasswordNotSame; ?>");
               return;
              }
              sql += ", password = PASSWORD('" + f.pw.value + "')";
        }

        sql += ", " + privToString(f);

        url  = "sql.php3";
        url += "?sql_query=" + escape(sql);
        url += "&zero_rows=" + escape("<? echo "$strAddUserMessage<br>$strRememberReload"; ?>");
        url += "<? echo "&server=$server&lang=$lang&db=mysql&table=user"; ?>";
        url += "&goto=<? echo $self; ?>";

        location.href = url;
    }

    //-->
    </script>

    <div align=left>
    <ul>

    <li><a href="<? echo "$self?server=$server&lang=$lang&db=mysql&table=user&mode=reload"; ?>"><?php echo $strReloadMySQL; ?></a> <?php print show_docu("manual_Reference.html#Flush"); ?></li>

    <li><form name=userForm method="POST" action="<? echo $self; ?>"><? echo $strCheckDbPriv; ?>
    <table with="100%"><tr>
    <td><? echo $strDatabase; ?>:&nbsp;<select name="db">
<?
    $result = mysql_query("SHOW DATABASES");
    if (@mysql_num_rows($result))
        while ($row = mysql_fetch_row($result))
            echo "<option>" . $row[0] . "</option>\n";
?>
    </select></td>
    </tr></table>
    <input type="hidden" name="server" value="<? echo $server; ?>">
    <input type="hidden" name="lang" value="<? echo $lang; ?>">
    <input type="hidden" name="check" value="1">
    <input type="submit" value="<? echo $strGo; ?>">
    </form>
    </li>

    <li><form name=userForm onsubmit ="return false"><? echo $strAddUser; ?>
    <table>
    <tr>
    <td><input type="radio" name="anyhost">
    <? echo $strHost; ?>: <input type="text" name="host" size=10 onchange="javascript:anyhost[0].checked = true"></td>
    <td>&nbsp;</td><td><input type="radio" name="anyhost" checked><? echo $strAnyHost; ?></td>
    </tr>
    </table>
    <table>
    <tr>
    <td><input type="radio" name="anyuser" checked>
    <? echo $strUserName; ?>: <input type="text" name="user" size=10 onchange="javascript:anyuser[0].checked = true"></td>
    <td>&nbsp;</td><td><input type="radio" name="anyuser"><? echo $strAnyUser; ?></td>
    </tr>
    </table>
    <table>
    <tr>
    <td><input type="radio" name="nopass" checked>
    <? echo $strPassword; ?>: <input type="password" name="pw" size=10 onchange="javascript:nopass[0].checked = true"></td>
    <td><? echo $strReType; ?>: <input type="password" name="pw2" size=10 onchange="javascript:nopass[0].checked = true"></td>
    <td>&nbsp;</td>
    <td><input type="radio" name="nopass"><? echo $strNoPassword; ?></td></tr>
    <tr><td><br><? echo $strPrivileges; ?>:<br></td></tr>
    </table>
    <? table_privileges("userForm") ?>
    <input type="button" value="<? echo $strGo; ?>" onclick="addUser(document.userForm)"></p>
    </form>
    </li>
    </ul>
    </div>
    <?
}

function grant_operations()
{
    global $server, $lang, $user, $host;
    global $dbgrant, $tablegrant;
    global $self;

    global $strBack, $strGo;
    global $strDbEmpty, $strTableEmpty;
    global $strAddPriv, $strAddPrivMessage;
    global $strDatabase, $strAnyDatabase;
    global $strTable, $strAnyTable;
    global $strColumn, $strAnyColumn, $strColumnEmpty;

    ?>

    <script language="JavaScript">
    <!--

    function getSelected(field) {
        var f = "";
        for (var i = 0; i < field.options.length; i++)
          if (field.options[i].selected)
              f += field.options[i].text + ", ";

        return f.substring(0, f.length - 2);
    }

    function addGrant(f) {
        var sql;
        var db, table, column = "";

        db = getSelected(f.database);
        table = getSelected(f.table);
        column = getSelected(f.column);

        if (f.anydb[1].checked) {

            if (db == "") {
               alert("<? echo $strDbEmpty; ?>");
               return;
            }

            if (f.anytable[1].checked) {

               if (table == "") {
                  alert("<? echo $strTableEmpty; ?>");
                  return;
               }

               if (f.anycolumn[1].checked) {
                  if (column == "") {
                     alert("<? echo $strColumnEmpty; ?>");
                     return;
                  }
                  column = " (" + column + ")";

               } else column = "";

            } else {table = "*"; column = ""; }

        } else { db = "*"; table = "*"; column = ""; }

        sql = "GRANT " + privGrantToString(f) + "" + column;
        sql += " ON " + db + "." + table
        sql += " TO '" + "<? echo $user; ?>" + "'@'" + "<? echo $host ?>'"
        if (f.Grant_priv.checked) sql += " with grant option";

        url  = "sql.php3";
        url += "?sql_query=" + escape(sql);
        url += "&zero_rows=" + escape("<? echo $strAddPrivMessage; ?>");
        url += "<? echo "&server=$server&lang=$lang"; ?>&db=mysql";
        url += "&goto=<? echo $self; ?>";

        location.href = url;
    }

    function change(f, param) {
        var l, p;

        l = location.href;
        if (param == "dbgrant") {
           p = l.indexOf("&" + param);
           if ( p >= 0) l = l.substring(0, p);
        }

        location.href = l + "&" + param + "=" + getSelected(f);
    }

    // -->
    </script>

    <div align=left>
    <ul>

    <li><a href="<? echo "$self?server=$server&lang=$lang&db=mysql&table=user"; ?>"><? echo $strBack; ?></a></li>

    <li><form name=userForm onsubmit ="return false"><? echo $strAddPriv; ?>
    <table with="100%"><tr>
    <td><input type="radio" name="anydb"<? echo ($dbgrant) ? "": " checked"; ?>><? echo $strAnyDatabase; ?></td>
    <td>&nbsp;</td>
    <td><input type="radio" name="anydb"<? echo ($dbgrant) ? " checked":""; ?>><? echo $strDatabase; ?>:&nbsp;
    <select name="database" onchange="javascript:change(userForm.database, 'dbgrant')">
<?
    if (!isset($dbgrant)) echo "<option selected></option>";
    $result = mysql_query("SHOW DATABASES");
    if (@mysql_num_rows($result))
        while ($row = mysql_fetch_row($result)) {
            $selected = ($row[0] == $dbgrant)? "SELECTED" : "";
            echo "<option $selected>" . $row[0] . "</option>\n";
        }
?>
    </select></td>
    </tr></table>

    <table with="100%"><tr>
    <td><input type="radio" name="anytable"<? echo ($tablegrant) ? "":" checked"; ?>><? echo $strAnyTable; ?></td>
    <td>&nbsp;</td>
    <td><input type="radio" name="anytable"<? echo ($tablegrant) ? " checked":""; ?>><? echo $strTable; ?>:&nbsp;
    <select name="table" onchange="javascript:change(userForm.table, 'tablegrant')"
    >
<?
    if (isset($dbgrant)) {
        if (!isset($tablegrant)) echo "<option selected></option>";
        $result = mysql_query("SHOW TABLES from $dbgrant");
        if (@mysql_num_rows($result))
           while ($row = mysql_fetch_row($result)) {
                $selected = ($row[0] == $tablegrant)? "SELECTED" : "";
                echo "<option $selected>" . $row[0] . "</option>\n";
                }
    }
?>
    </select></td>
    </tr></table>

    <table with="100%"><tr>
    <td VALIGN=TOP><input type="radio" name="anycolumn" checked><? echo $strAnyColumn; ?></td>
    <td>&nbsp;</td>
    <td VALIGN=TOP><input type="radio" name="anycolumn"><? echo $strColumn; ?>:</td>
    <td>
    <select name="column" onchange="javascript:anycolumn[1].checked = true" multiple>
<?

    if (isset($dbgrant) && isset($tablegrant)) {
       $result = mysql_query("SHOW COLUMNS FROM $dbgrant.$tablegrant");
       if (@mysql_num_rows($result))
           while ($row = mysql_fetch_row($result))
               echo "<option>" . $row[0] . "</option>\n";
    }
?>
    </select>
    </td>
    </tr></table>

    <table><tr><td><br><?php echo $strPrivileges; ?>:<br></td></tr></table>
    <? table_privileges("userForm") ?>
    <input type="button" value="<? echo $strGo; ?>" onclick="addGrant(userForm)"></p>
    </form>
    </li>
    </ul>
    </div>
    <?
}

function table_grants_header($dbcheck = false) {
    global $cfgBorder;

    global $strAction;
    global $strHost, $strUser, $strDatabase, $strColumn;
    global $strTable, $strPrivileges, $strGrantOption;

    echo "<table border=$cfgBorder>\n<tr>";

    if ($dbcheck) {
       echo "<th>$strAction</td>";
       echo "<th>$strHost</th>";
       echo "<th>$strUser</th>";
    } else {
      echo "<th colspan=2>$strAction</td>";
    }

    echo "<th>$strDatabase</th>";
    echo "<th>$strTable</th>";
    echo "<th>$strPrivileges</th>";
    if (!$dbcheck) echo "<th>$strGrantOption</th>";
    echo "</tr>\n";
}

function table_grants_tail() {
    echo "</table>\n<hr>";
}


function table_grants($host, $user, $dbcheck = false)
{
    global $cfgBgcolorOne, $cfgBgcolorTwo;
    global $server, $lang, $db, $table;
    global $self;

    global $strEdit, $strDelete, $strAny, $strAll, $strYes, $strNo;
    global $strRevoke, $strRevokePriv, $strRevokeGrant;
    global $strRevokeMessage, $strRevokeGrantMessage;
    global $strNoPrivileges;

    $select = "SHOW GRANTS FOR '$user'@'$host'";
    $result = mysql_query($select);
    $rows = @mysql_num_rows($result);

    # Errors
    if (!isset($rows)) return -1;
    if ($rows == 0) return 0;

    $i = 0;
    while ($row = mysql_fetch_row($result)) {

        if (preg_match("/GRANT (.*) ON ([^\.]+).([^\.]+) TO .*$/i", $row[0], $parts)) {
            $priv = $parts[1];
            $db = $parts[2];
            $table = trim($parts[3]);
            $grantopt = eregi("WITH GRANT OPTION$", $row[0]);
        } else {
            $db = "&nbsp";
            $table = "&nbsp";
            $column = "&nbsp";
            $priv = "";
            $grantopt = false;
        }

        if ($priv == "USAGE") $priv = "";

        # Checking the database ...
        if ($dbcheck)
           if (!eregi($dbcheck . "|\*", $db) || (trim($priv) == "")) continue;

        # Password Line
        if ((trim($priv) == "") && !$grantopt) continue;

        if (!$dbcheck && !($show_header++)) table_grants_header();


        $bgcolor = $cfgBgcolorOne;
        $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;

        # Revoke
        $query = "server=$server&lang=$lang&db=mysql&table=user";
        $revoke_url  = "sql.php3";
        $revoke_url .= "?sql_query=".urlencode("REVOKE $priv ON $db.$table FROM '$user'@'$host'");
        $revoke_url .= "&$query";
        $revoke_url .= "&zero_rows=" . urlencode("$strRevokeMessage <font color=#002E80>$user@$host</font>");
        $revoke_url .= "&goto=$self";

        # Revoke GRANT OPTION
        $revoke_grant_url  = "sql.php3";
        $revoke_grant_url .= "?sql_query=".urlencode("REVOKE GRANT OPTION ON $db.$table FROM '$user'@'$host'");
        $revoke_grant_url .= "&$query";
        $revoke_grant_url .= "&zero_rows=" . urlencode("$strRevokeGrantMessage <font color=#002E80>$user@$host</font>");
        $revoke_grant_url .= "&goto=$self";
        ?>

        <tr bgcolor="<?php echo $bgcolor;?>">

        <? if (!$dbcheck) { ?>
        <td><a <? echo ($priv != "") ? "href = \"$revoke_url\"": ""; ?>><? echo $strRevokePriv; ?></a></td>
        <td><a <? echo ($grantopt) ? "href = \"$revoke_grant_url\"": ""; ?>><? echo $strRevokeGrant; ?></a></td>
        <? } else { ?>
        <td><a <? echo ($priv != "") ? "href = \"$revoke_url\"": ""; ?>><? echo $strRevoke; ?></a></td>
        <td><?php echo $host; ?></td>
        <td><?php echo ($user) ? $user : "<font color=\"#FF0000\">$strAny</font>"; ?></td>
        <? } ?>

        <td><?php echo ($db == "*") ? "<font color=\"#002E80\">$strAll</font>" : $db; ?></td>
        <td><?php echo ($table == "*") ? "<font color=\"#002E80\">$strAll</font>" : $table; ?></td>
        <td><?php echo ($priv != "") ? $priv : "<font color=\"#002E80\">$strNoPrivileges</font>"; ?></td>
        <? if (!$dbcheck) { ?>
        <td><?php echo ($grantopt) ? "$strYes" : "$strNo"; ?></td>
        <? } ?>
        <!-- <td><?php echo $row[0] ?></td> <!-- Debug -->
        </tr>

        <?
        $i++;
    }

    if (!$dbcheck && $show_header) table_grants_tail();
    return $rows;
}

function table_privileges($form, $row = false)
{
    global $strSelect, $strInsert, $strUpdate, $strDelete, $strCreate;
    global $strDrop, $strReload, $strShutdown, $strProcess;
    global $strFile, $strGrant, $strReferences, $strIndex, $strAlter;
    global $strCheckAll, $strUncheckAll;

    ?>
    <script language="JavaScript">
    <!--
    function checkForm(f, checked) {
        len = f.elements.length;
        var i=0;
        for( i=0; i<len; i++)
           if (f.elements[i].name.indexOf("_priv") >= 0) {
              f.elements[i].checked=checked;
              }
    }

    function privGrantToString(f) {
        var sql = "";

        for (var property in f)
           if (property.toString().indexOf("_priv") >= 0)
              if (f[property].checked && property.indexOf("Grant") == -1)
                 sql += ", " + property.substring(0, property.indexOf("_priv"));

        sql = sql.substring(2);
        if (sql == "") sql = "Usage";
        return sql;
    }

    function privToString(f) {
        var index = 0;
        var sql = "";

        for (var property in f)
        if (property.toString().indexOf("_priv") >= 0) {
           if (index > 0) sql += ", ";
               index++;

               if (f[property].checked) sql += property + " = 'Y'"
               else sql += property + " = 'N'"
            }

        return sql;
    }

    //-->
    </script>
    <table>
    <?
    $list_priv = array("Select", "Insert", "Update", "Delete", "Create", "Drop", "Reload",
		       "Shutdown", "Process", "File", "Grant", "References", "Index", "Alter");

    $item = 0;
    while ((list(,$priv) = each($list_priv)) && ++$item) {
       $priv_priv = $priv . "_priv";
       $checked = ($row{$priv_priv} == "Y") ?  "checked" : "";
       if ($item % 2 == 1) echo "<tr>";
       else echo "<td>&nbsp;</td>";
       echo "<td><input type=\"checkbox\" name=\"$priv_priv\" $checked></td>";
       echo "<td>" . ${"str$priv"} . "</td>";
       if ($item % 2 == 0) echo "</tr>\n";
    }
    if ($item % 2 == 1) echo "<td colspan=2>&nbsp;<td></tr>\n";

    ?>
    </table>
    <table>
    <tr><td><a href="javascript:checkForm(document.<? echo $form; ?>, true)"><? echo $strCheckAll; ?></a></td>
    <td>&nbsp;</td><td><a href="javascript:checkForm(document.<? echo $form; ?>, false)"><? echo $strUncheckAll; ?></a></td></tr>
    </table>
    <?
}

function edit_operations($host, $user)
{
    global $server, $lang;
    global $self;

    global $strBack, $strGo;
    global $strDelPassMessage, $strRememberReload, $strUpdatePassMessage;
    global $strUpdatePrivMessage, $strRememberReload;
    global $strPasswordEmpty, $strPasswordNotSame;
    global $strDeletePassword, $strUpdatePassword, $strEditPrivileges;
    global $strPassword, $strReType;

    $result = mysql_query("SELECT * FROM mysql.user WHERE user = '$user' and host = '$host'");
    $rows = @mysql_num_rows($result);

    # Errors
    if (!isset($rows)) return -1;
    if ($rows == 0) return 0;

    $row = mysql_fetch_array($result);

    #Delete Password
    $del_url  = "sql.php3";
    $del_url .= "?sql_query=" . urlencode("UPDATE user SET password = '' WHERE user = '$user' and host = '$host'");
    $del_url .= "&zero_rows=" . urlencode("$strDelPassMessage <font color=#002E80>$user@$host</font><br>$strRememberReload");
    $del_url .= "&server=$server&lang=$lang&db=mysql&table=user";
    $del_url .= "&goto=$self";
    ?>

    <script language="JavaScript">
    <!--
    function changePrivileges(f) {
        var sql = "UPDATE user SET ";
        var url;

        sql += privToString(f);
        sql += " WHERE user = '<? echo $user; ?>' and host = '<? echo $host; ?>'";

        url  = "sql.php3";
        url += "?sql_query=" + escape(sql);
        url += "&zero_rows=" + escape("<? echo $strUpdatePrivMessage; ?> <font color=#002E80><? echo "$user@$host"; ?></font><br><? echo $strRememberReload; ?>");
        url += "<? echo "&server=$server&lang=$lang&db=mysql&table=user"; ?>";
        url += "&goto=<? echo $self; ?>";
        location.href = url;
    }

    function changePassword(f) {
        if (f.pw.value == "") {
            alert("<? echo $strPasswordEmpty; ?>");
            return;
           }

        if (f.pw.value != f.pw2.value) {
            alert("<? echo $strPasswordNotSame ?>");
            return;
           }

        url  = "sql.php3";
        url += "?sql_query=" + escape("UPDATE user SET password = PASSWORD('" + f.pw.value + "') WHERE user = '<? echo $user; ?>' and host = '<? echo $host; ?>'");
        url += "&zero_rows=" + escape("<? echo $strUpdatePassMessage; ?> <font color=#002E80><? echo "$user@$host"; ?></font><br><? echo $strRememberReload; ?>");
        url += "<? echo "&server=$server&lang=$lang&db=mysql&table=user"; ?>";
        url += "&goto=<? echo $self; ?>";
        location.href = url;
    }
    //-->
    </script>

    <div align=left>
    <ul>

    <li><a href="<? echo "$self?server=$server&lang=$lang&db=mysql&table=user"; ?>"><? echo $strBack; ?></a></li>

    <?      if ($row{"Password"} != "") { ?>
    <li><td><a href="<? echo $del_url; ?>"><? echo $strDeletePassword; ?></a></td></li>
    <? } ?>

    <li><form name=passForm onsubmit ="return false"><? echo $strUpdatePassword; ?>
    <table>
    <tr><td><? echo $strPassword; ?>: <input type="password" name="pw" size=10></td>
    <td>&nbsp;</td>
    <td><?echo $strReType; ?>: <input type="password" name="pw2" size=10></td></tr>
    </table>
    <input type="button" value="<? echo $strGo; ?>" onclick="changePassword(document.passForm)"></p>
    </form></li>

    <li><form name=privForm onsubmit ="return false"><? echo $strEditPrivileges; ?>
    <? table_privileges("privForm", $row); ?>
    <input type="button" value="<? echo $strGo; ?>" onclick="changePrivileges(document.privForm)"></p>
    </form>
    </li>
    </ul>
    </div>

    <?
}

function check_rights()
{
    global $strNoRights;

    $result = mysql_query("SELECT * FROM mysql.user");
    $rows = @mysql_num_rows($result);

    if (!isset($rows)) mysql_die($strNoRights);
}


function table_users($host = false, $user = false)
{
    global $cfgBorder, $cfgBgcolorOne, $cfgBgcolorTwo;
    global $server, $lang, $db, $table;
    global $self;

    global $strEdit, $strDelete, $strGrants;
    global $strAction, $strHost, $strUser, $strPassword, $strPrivileges;
    global $strSelect, $strInsert, $strUpdate, $strDelete, $strCreate;
    global $strDrop, $strReload, $strShutdown, $strProcess;
    global $strFile, $strGrant, $strReferences, $strIndex, $strAlter;
    global $strNoPrivileges, $strDeleteUserMessage, $strRememberReload;
    global $strYes, $strNo, $strAny;

    $select = "SELECT * FROM mysql.user ";
    if ($host || $user) $select = "$select WHERE 1 ";
    if ($host) {
       $select = "$select and host = '$host'";
       $select = "$select and user = '$user'";
       }
    $select .= " ORDER BY host, user";

    $result = mysql_query($select);
    $rows = @mysql_num_rows($result);

    # Errors
    if (!isset($rows)) return -1;
    if ($rows == 0) return 0;

    echo "<table border=$cfgBorder>\n";
    echo "<tr><th colspan=3>$strAction</td>";
    echo "<th>$strHost</th>";
    echo "<th>$strUser</th>";
    echo "<th>$strPassword</th>";
    echo "<th>$strPrivileges</th></tr>";

    $i = 0;
    while ($row = mysql_fetch_array($result)) {

        $bgcolor = $cfgBgcolorOne;
        $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;

        $strPriv = "";
        if ($row{"Select_priv"} == "Y")  $strPriv .= "$strSelect ";
        if ($row{"Insert_priv"} == "Y") $strPriv .= "$strInsert ";
        if ($row{"Update_priv"} == "Y") $strPriv .= "$strUpdate ";
        if ($row{"Delete_priv"} == "Y") $strPriv .= "$strDelete ";
        if ($row{"Create_priv"} == "Y") $strPriv .= "$strCreate ";
        if ($row{"Drop_priv"} == "Y") $strPriv .= "$strDrop ";
        if ($row{"Reload_priv"} == "Y") $strPriv .= "$strReload ";
        if ($row{"Shutdown_priv"} == "Y") $strPriv .= "$strShutdown ";
        if ($row{"Process_priv"} == "Y") $strPriv .= "$strProcess ";
        if ($row{"File_priv"} == "Y") $strPriv .= "$strFile ";
        if ($row{"Grant_priv"} == "Y") $strPriv .= "$strGrant ";
        if ($row{"References_priv"} == "Y") $strPriv .= "$strReferences ";
        if ($row{"Index_priv"} == "Y") $strPriv .= "$strIndex ";
        if ($row{"Alter_priv"} == "Y") $strPriv .= "$strAlter ";

        if ($strPriv == "") $strPriv  = "<font color=\"#002E80\">$strNoPrivileges</font>";

        $query = "server=$server&lang=$lang&db=mysql&table=user";

        # Edit
        $edit_url  = $self;
        $edit_url .= "?server=$server&lang=$lang";
        $edit_url .= "&edit=1&host=" . urlencode($row{"Host"}) . "&user=" . urlencode($row{"User"});

        # Delete
        $delete_url  = "$self?$query";
        $delete_url .= "&delete=1&confirm=1&delete_host=" . urlencode($row{"Host"}) . "&delete_user=" . urlencode($row{"User"});

        # Grants
        $check_url  = $self;
        $check_url .= "?server=$server&lang=$lang";
        $check_url .= "&grants=1&host=" . urlencode($row{"Host"}) . "&user=" . urlencode($row{"User"});

#        $check_result =  mysql_query("SHOW GRANTS FOR '" . $row{"User"} . "'@'" . $row{"Host"} ."'");
#        if (@mysql_num_rows($check_result) == 0) $check_url = ""
        ?>

        <tr bgcolor="<?php echo $bgcolor;?>">
        <td><a href="<? echo $edit_url; ?>"><? echo $strEdit; ?></a></td>
        <td><a href="<? echo $delete_url; ?>"><? echo $strDelete; ?></a></td>
        <td><a href="<? echo $check_url; ?>"><? echo $strGrants; ?></a></td>
<!--        <td><a <? if ($check_url != "") echo "href = \"" . $check_url . "\""; ?>>Grants</a></td> -->
        <td><?php echo $row{"Host"}; ?></td>
        <td><?php echo $row{"User"} ? "<b>" . $row{"User"}. "</b>" : "<font color=\"#FF0000\">$strAny</font>"; ?></td>
        <td><?php echo $row{"Password"} ? $strYes : "<font color=\"#FF0000\">$strNo</font>"; ?></td>
        <td><?php echo $strPriv; ?></td>
        </tr>

        <?
        $i++;
    }

    echo "</table>\n<hr>";
	return $rows;
}


function confirm() {
    global $self, $server, $lang;
    global $strYes, $strNo, $strConfirm;

?>
    <script language="JavaScript">
    <!--

    function clickNo() {
       location = "<? echo "$self?server=$server&lang=$lang&db=mysql&table=user"; ?>";
    }

    function clickYes() {
       location += "&clickyes=1";
    }

    // -->
    </script>

    <? echo $strConfirm; ?>

    <form action="javascript:return false;">
    <input type="button" name="btnDrop" value="<? echo $strYes; ?>" onclick="javascript:clickYes();">
    <input type="button" name="btnDrop" value="<? echo $strNo; ?>" onclick="javascript:clickNo();">
    </form>

<?
}

# Main Program

if(!isset($message))
{
    include("./header.inc.php3");
}
else
{
    show_message($message);
}

$self = "user_details.php3";
check_rights();

if (!empty($host)) {
    echo "<h1>";
    if ($host) echo "$strHost $host - $strUser ";
    echo ($user) ?  $user : "$strAny";
    echo "</h1>";
}

# Confirm the action ...
if (isset($confirm) && $confirm && !$clickyes) {
   confirm();
   exit();
}

if (($server > 0) && isset($mode) && ($mode == "reload"))
   {
     $result = mysql_query("FLUSH PRIVILEGES");
     if ($result != 0) {
       echo "<b>$strMySQLReloaded</b>";
     } else {
       echo "<b>$strReloadFailed</b>";
     }
   }

# Delete an user
if (isset($delete) && $delete && isset($delete_host) && isset($delete_user)) {

   # Delete Grants First!
   mysql_query("DELETE FROM mysql.columns_priv WHERE host = '$delete_host' and user = '$delete_user'");
   mysql_query("DELETE FROM mysql.db WHERE host = '$delete_host' and user = '$delete_user'");
   mysql_query("DELETE FROM mysql.tables_priv WHERE host = '$delete_host' and user = '$delete_user'");

   $result = mysql_query("DELETE FROM mysql.user WHERE host = '$delete_host' and user = '$delete_user'");
   if ($result != 0) {
      echo "<b>$strDeleteUserMessage <font color=#002E80>$delete_user@$delete_host</font><br>$strRememberReload</b>";
   } else {
      echo "<b>$strDeleteFailed</b>";
   }
}

if (isset($edit) && $edit) { # Edit an user
  table_users($host, $user);
  edit_operations($host, $user);

} elseif (isset($grants) && $grants) { # Revoke/Grant Privileges
  table_grants($host, $user);
  grant_operations();

} elseif (isset($check) && $check) { # Check Database Privileges
  check_db($db);
  check_operations();

} else {            # Users actions
  if (!isset($host)) $host = false;
  if (!isset($user)) $user = false;
  table_users($host, $user) || mysql_die($strNoUsersFound);
  normal_operations();
}

require("./footer.inc.php3");
?>
