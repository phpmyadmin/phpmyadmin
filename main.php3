<?php
/* $Id$ */


require("grab_globals.inc.php3");
 

if (!isset($message)) {
  include("header.inc.php3");
} else {
  include("header.inc.php3");
  include("lib.inc.php3");
  show_message($message);
}
?>

<h1><?php echo $strWelcome ?> phpMyAdmin 2.2.0pre3 (unofficial devel-branch)</h1>
<?php
if ($server > 0) {
  // Don't display server info if $server==0 (no server selected)
  $res_version = mysql_query("SELECT Version() as version") or mysql_die();
  $row_version = mysql_fetch_array($res_version);

  echo "<b>MySQL $row_version[version] $strRunning " . $cfgServer['host'];
  if (!empty($cfgServer['port'])) {
    echo ":" . $cfgServer['port'];
  }
  echo "</b><br>\n";
}
?>
<div align="left">
<?php

if (($server > 0) && isset($mode) && ($mode == "reload"))
   {
     $result = mysql_query("FLUSH PRIVILEGES");
     if ($result != 0) {
       echo "<b>$strMySQLReloaded</b>";
     } else {
       echo "<b>$strReloadFailed</b>";
     }
   }
?>
<ul>
<?php
if(count($cfgServers) > 1)
{
  echo "<li>";
  echo '<form action="index.php3" target="_top"><select name="server">';

  reset($cfgServers);
  while(list($key, $val) = each($cfgServers)) {
    if(!empty($val['host'])) {
      echo "<option value=\"$key\"";
      if(!empty($server) && ($server == $key)) {
	echo " selected";
      }
      echo ">";
      print(!empty($val['verbose']) ? $val['verbose'] :  $val['host']);
      
      if(!empty($val['port'])) {
	echo ":" . $val['port'];
      }
      if(!empty($val['only_db']))
	echo " - ".$val['only_db'];
      echo "\n";
    }
  }
  echo '</select><input type="hidden" name="lang" value="'.$lang.'">';
  echo '<input type="submit" value="'.$strGo.'"></form>';
}

if($server > 0) {
  // Don't display server-related links if $server==0 (no server selected)
  if(empty($cfgServer['only_db'])) {
    if($cfgServer['adv_auth'])
      {
	if (empty($cfgServer['port'])) {
	  $dbh = mysql_connect($cfgServer['host'],$cfgServer['stduser'],$cfgServer['stdpass']);
	} else {
	  $dbh = mysql_connect($cfgServer['host'].":".$cfgServer['port'],$cfgServer['stduser'],$cfgServer['stdpass']);
	}

	$rs_usr=mysql_db_query("mysql","select * from user where User=\"".$cfgServer['user']."\"",$dbh);
	$result_usr=mysql_fetch_array($rs_usr);
	$rs_db=mysql_db_query("mysql","select * from db where User=\"".$cfgServer['user']."\"",$dbh);
	
	if(mysql_num_rows($rs_db)>0) {
	  $result_db=mysql_fetch_array($rs_db);
	}
	
   if($result_usr['Create_priv']=='Y') {
     $CREATE=TRUE;
// BEGIN 
// 2001-05-04, Lem9: The create_priv on a single database does not allow
//     to create a database
// } elseif(!empty($result_db) && $result_db['Create_priv']=='Y') {
//   $CREATE=TRUE;
//  END  
   } else {
     $CREATE=FALSE;
   }

	if($CREATE) {
		?>
                <li>
                <form method="post" action="db_create.php3">
                <?php echo $strCreateNewDatabase;?> <?php print show_docu("manual_Reference.html#Create_database");?><br><input type="Hidden" name="server" value="<?php echo $server; ?>"><input type="hidden" name="lang" value="<?php echo $lang;?>"><input type="hidden" name="reload" value="true"><input type="text" name="db"><input type="submit" value="<?php echo $strCreate; ?>">
                </form>
                <?php
	}

	if($result_usr['References_priv']=='Y') {
		?>
                <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW STATUS");?>">
                <?php echo $strMySQLShowStatus;?></a> <?php print show_docu("manual_Reference.html#Show");?>

                <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW VARIABLES");?>">
                <?php echo $strMySQLShowVars;?></a> <?php print show_docu("manual_Performance.html#Performance");
            }

	if($result_usr['Process_priv']=='Y') {
                ?>
                <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW PROCESSLIST");?>">
                <?php echo $strMySQLShowProcess;?></a> <?php print show_docu("manual_Reference.html#Show");
	}

	if($result_usr['Reload_priv']=='Y') {
                ?>
                <li>
                <a href="main.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&mode=reload"><?php echo $strReloadMySQL; ?></a> <?php print show_docu("manual_Reference.html#Flush");
	}
            ?>
            <li><a href="index.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&old_usr=<?php echo $PHP_AUTH_USER;?>" target="_top"><b><?php echo $strLogout; ?></b></a>
            <?php
        }
        else
        { //No AdvAuth
            ?>
            <li>
            <form method="post" action="db_create.php3">
            <?php echo $strCreateNewDatabase;?> <?php print show_docu("manual_Reference.html#Create_database");?><br><input type="Hidden" name="server" value="<?php echo $server; ?>"><input type="hidden" name="lang" value="<?php echo $lang;?>"><input type="hidden" name="reload" value="true"><input type="text" name="db"><input type="submit" value="<?php echo $strCreate; ?>">
            </form>
            <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW STATUS");?>">
            <?php echo $strMySQLShowStatus;?></a> <?php print show_docu("manual_Reference.html#Show");?>
            <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW VARIABLES");?>">
            <?php echo $strMySQLShowVars;?></a> <?php print show_docu("manual_Performance.html#Performance");?>
            <li><a href="sql.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&db=mysql&sql_query=<?php echo urlencode("SHOW PROCESSLIST");?>">
            <?php echo $strMySQLShowProcess;?></a> <?php print show_docu("manual_Reference.html#Show");?>
            <li>
            <a href="main.php3?server=<?php echo $server;?>&lang=<?php echo $lang;?>&mode=reload"><?php echo $strReloadMySQL; ?></a> <?php print show_docu("manual_Reference.html#Flush");
        }
    }
}
?>

<li>
<a href="http://phpmyadmin.sourceforge.net/" target="_top"><?php print $strHomepageSourceforge; ?> </a> (sourceforge)
&nbsp;&nbsp;&nbsp;&nbsp; [ <a href="ChangeLog" target="_top">ChangeLog</a> ]
<li>
<a href="http://phpwizard.net/projects/phpMyAdmin/" target="_top"><?php print $strHomepageOfficial; ?> </a>
<li>
<a href="Documentation.html" target="_top">phpMyAdmin <?php print $strDocu;?></a>


<li><?php
        reset($available_languages);
        while(list ($id,$tmplang) = each ($available_languages) ) {
          $lang_name = ucfirst(substr(strstr($tmplang[0], '|'), 1));
?>
[ <a href="index.php3?server=<?php echo $server;?>&lang=<?php echo $id;?>" target="_top" title="<?php echo $lang_name;?>"><?php echo $id;?></a> ]
<?php
        }
?>
</ul>

</div>

<?php
require ("footer.inc.php3");
?>
