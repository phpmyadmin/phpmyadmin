<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 

@set_time_limit(600);
$crlf="\n";

if(empty($asfile)) 
{ 
	include("./header.inc.php3");
	print "<div align=left><pre>\n";
}
else
{
	include("./lib.inc.php3");
	$ext = "sql";
	if($what == "csv") $ext = "csv";
	header("Content-disposition: filename=$table.$ext");
	header("Content-type: application/octetstream");
	header("Pragma: no-cache");
	header("Expires: 0");

	// doing some DOS-CRLF magic...
    
	if (!isset($HTTP_USER_AGENT))
	{
		if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['HTTP_USER_AGENT']))
			$HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
		else 
			$HTTP_USER_AGENT = getenv('HTTP_USER_AGENT');
	}
	$client = $HTTP_USER_AGENT;

	if(ereg('[^(]*\((.*)\)[^)]*',$client,$regs))
	{
		$os = $regs[1];
		// this looks better under WinX
		if (eregi("Win",$os))
			$crlf="\r\n";
	}
}

function my_handler($sql_insert)
{
	global $crlf, $asfile;
	if(empty($asfile))
		echo htmlspecialchars("$sql_insert;$crlf");
	else
		echo "$sql_insert;$crlf";
}

function my_csvhandler($sql_insert)
{
	// 2001-05-07, Lem9: added $add_character

	global $crlf, $add_character, $asfile;
	if(empty($asfile))
		echo htmlspecialchars($sql_insert . $add_character . $crlf);
	else
		echo $sql_insert . $add_character . $crlf;
}

if (!isset($table)){
	$tables = mysql_list_tables($db);
	$num_tables = @mysql_numrows($tables);
}
else{
	$num_tables=1;
	$single=true;
}
if($num_tables == 0)
{
	echo $strNoTablesFound;
}
else
{
	if($what != "csv") 
	{
		echo "# phpMyAdmin MySQL-Dump$crlf";
		echo "# http://phpwizard.net/phpMyAdmin/$crlf";
		echo "# http://phpmyadmin.sourceforge.net/ (unofficial)$crlf";
		echo "#$crlf";
		echo "# $strHost: ".$cfgServer['host']."$crlf";
		echo "# $strGenTime: ".date("F j, Y, g:i a")."$crlf";
		echo "# $strServerVersion: ".MYSQL_MAJOR_VERSION.".".MYSQL_MINOR_VERSION."$crlf";
		if(!empty($cfgServer['port'])) echo ":" . $cfgServer['port'];
		echo "# $strDatabase: $db$crlf";

		$i = 0;
		if (isset($table_select)) {
			$tmp_select=implode($table_select,"|");
			$tmp_select="|".$tmp_select."|";
		}
		while($i < $num_tables)
		{

			if (!isset($single)) $table = mysql_tablename($tables, $i);
			if(isset($tmp_select) && is_int(strpos($tmp_select,"|".$table."|"))==false) $i++;
			else
			{

				if($what != "dataonly")
				{
					echo "# --------------------------------------------------------$crlf";
					echo "$crlf#$crlf";
					echo "# $strTableStructure '$table'$crlf";
					echo "#$crlf$crlf";

					echo get_table_def($db, $table, $crlf).";$crlf";
				}

				if(($what == "data") || ($what == "dataonly"))
				{
					echo "$crlf#$crlf";
					echo "# $strDumpingData '$table'$crlf"; 
					echo "#$crlf$crlf";

					get_table_content($db, $table, "my_handler");
				}
				$i++;
			}
		}
		echo "$crlf"; // Don't remove, it makes easier to select & copy from browser - staybyte
	} 
	else 
	{ // $what != "csv"
		get_table_csv($db, $table, $separator, "my_csvhandler");
	}
}

if(empty($asfile))
{
	echo "</pre></div>\n";
	include("./footer.inc.php3");
}
?>
