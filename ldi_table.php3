<?php
/* $Id$ */


// This file inserts a textfile into a table


require("./grab_globals.inc.php3");
 
require("./header.inc.php3");

$tables = mysql_list_tables($db);
$num_tables = @mysql_numrows($tables);
?>

<form action="ldi_check.php3" method="post"  enctype="multipart/form-data">
<input type="hidden" name="goto" value="tbl_properties.php3">
<table border="1">
<tr>
	<td><?php echo $strLocationTextfile; ?></td>
	<td colspan=2><input type="file" name="textfile"></td>
</tr>
<tr>
	<td><?php echo $strReplaceTable; ?></td>
	<td><input type="checkbox" name="replace" value="REPLACE"><?php echo $strReplace; ?></td>
	<td><?php echo $strTheContents; ?></td>
</tr>	
<tr>
	<td><?php echo $strFields; ?><br><?php echo $strTerminatedBy; ?></td>
	<td><input type="text" name="field_terminater" size="2" maxlength="2" value=";"></td>
	<td><?php echo $strTheTerminator; ?></td>
</tr>
<tr>
	<td><?php echo $strFields; ?><br><?php echo $strEnclosedBy; ?></td>
	<td><input type="text" name="enclosed" size="1" maxlength="1" value="&quot;">
		<input type="Checkbox" name="enclose_option" value="OPTIONALLY"><?php echo $strOptionally; ?>
	</td>
	<td><?php echo $strOftenQuotation; ?></td>
</tr>
<tr>
	<td><?php echo $strFields; ?><br><?php echo $strEscapedBy; ?></td>
	<td><input type="text" name="escaped" size="2" maxlength="2" value="\\"></td>
	<td><?php echo $strOptionalControls; ?></td>
</tr>
<tr>
	<td><?php echo $strLines; ?><br><?php echo $strTerminatedBy; ?></td>
	<td><input type="text" name="line_terminator" size="8" maxlength="8" value="\n"></td>
	<td><?php echo $strCarriage; ?><br><?php echo $strLineFeed; ?></td>
</tr>
<tr>
	<td><?php echo $strColumnNames; ?></td>
	<td><input type="text" name="column_name"></td>
	<td><?php echo $strIfYouWish; ?></td>
</tr>
<tr>
	<td colspan="3" align="center"><?php print show_docu("manual_Reference.html#Load");?></td>
</tr>
<tr>
	<td colspan="3" align="center">
	<input type="Hidden" name="server" value="<?php echo $server ?>">
	<input type="Hidden" name="db" value="<?php echo $db; ?>">
	<input type="Hidden" name="table" value="<?php echo $table; ?>">
	<input type="Hidden" name="zero_rows" value="<?php echo $strTheContent; ?>">
	<input type="Hidden" name="into_table" value="<?php echo $table; ?>">
	<input type="Submit" name="btnLDI" value=" <?php echo $strSubmit; ?> ">&nbsp;&nbsp;
	<input type="Reset" value=" <?php echo $strReset; ?> ">
	</td>
</tr>
</table>

</form>

<?php

require("./footer.inc.php3");
?>
