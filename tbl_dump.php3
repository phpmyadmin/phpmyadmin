<?php
/* $Id$ */

require("./grab_globals.inc.php3");
 
@set_time_limit(600);
$crlf="\n";

if (empty($asfile) && !empty($gzip)) {
    $asfile = 1;
}

if(empty($asfile)) 
{ 
	include("./header.inc.php3");
	print "<div align=left><pre>\n";
}
else
{
	if (!isset($table)) $filename=$db;
	else $filename=$table;
	include("./lib.inc.php3");
	$ext = "sql";
	if($what == "csv") $ext = "csv";
	if(isset($gzip))
        	if($gzip == "gzip") $ext = "gz";

	header('Content-Type: application/octetstream');
	header('Content-Disposition: filename="' . $filename . '.' . $ext . '"');
	header('Pragma: no-cache');
	header('Expires: 0');

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
	global $tmp_buffer;

	if(empty($asfile))
		$tmp_buffer.= htmlspecialchars("$sql_insert;$crlf");
	else
		$tmp_buffer.= "$sql_insert;$crlf";
}

function my_csvhandler($sql_insert)
{
	// 2001-05-07, Lem9: added $add_character

	global $crlf, $add_character, $asfile;
	global $tmp_buffer;

	if(empty($asfile))
		$tmp_buffer.= htmlspecialchars($sql_insert . $add_character . $crlf);
	else
		$tmp_buffer.= $sql_insert . $add_character . $crlf;
}

$dump_buffer="";

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
	echo "# $strNoTablesFound";
}
else
{
	if($what != "csv") 
	{
		$dump_buffer.= "# phpMyAdmin MySQL-Dump$crlf";
		$dump_buffer.= "# version ".PHPMYADMIN_VERSION."$crlf";
		$dump_buffer.= "# http://phpwizard.net/phpMyAdmin/$crlf";
		$dump_buffer.= "# http://phpmyadmin.sourceforge.net/ (download page)$crlf";
		$dump_buffer.= "#$crlf";
		$dump_buffer.= "# $strHost: ".$cfgServer['host'];
		if(!empty($cfgServer['port'])) $dump_buffer.= ":" . $cfgServer['port'];
		$dump_buffer.= $crlf;
		$dump_buffer.= "# $strGenTime: ".date("F j, Y, g:i a")."$crlf";
		$dump_buffer.= "# $strServerVersion: ".MYSQL_MAJOR_VERSION.".".MYSQL_MINOR_VERSION."$crlf";
		$dump_buffer.= "# $strPHPVersion: ".phpversion()."$crlf";
		$dump_buffer.= "# $strDatabase: $db$crlf";

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
					$dump_buffer.= "# --------------------------------------------------------$crlf";
					$dump_buffer.= "$crlf#$crlf";
					$dump_buffer.= "# $strTableStructure '$table'$crlf";
					$dump_buffer.= "#$crlf$crlf";
					$dump_buffer.= get_table_def($db,$table, $crlf).";$crlf"; 				
				}

				if(($what == "data") || ($what == "dataonly"))
				{
					$dump_buffer.= "$crlf#$crlf";
					$dump_buffer.= "# $strDumpingData '$table'$crlf"; 
					$dump_buffer.= "#$crlf$crlf";
					
					$tmp_buffer="";
					get_table_content($db, $table, "my_handler");
					$dump_buffer.=$tmp_buffer;
				}
				$i++;
			}
		}
		// Don't remove, it makes easier to select & copy frombrowser - staybyte
		$dump_buffer.= "$crlf";
	} 
	else 
	{ // $what != "csv"
		$tmp_buffer="";
		get_table_csv($db, $table, $separator, "my_csvhandler");
        	$dump_buffer.=$tmp_buffer;
	}
}

if(isset($gzip)) {
	if($gzip == "gzip" && function_exists("gzencode"))
		// without the optional parameter level because it bug
        	echo gzencode($dump_buffer);  
}
else
	echo $dump_buffer;

if(empty($asfile))
{
	echo "</pre></div>\n";
	include("./footer.inc.php3");
}
?>
