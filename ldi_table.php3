<?php
/* $Id$ */


/**
 * This file defines the forms used to insert a textfile into a table
 */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./header.inc.php3');


/**
 * Displays the form
 */
?>
<form action="ldi_check.php3" method="post" enctype="multipart/form-data">
    <table cellpadding="5" border="2">
    <tr>
        <td><?php echo $strLocationTextfile; ?></td>
        <td colspan="2"><input type="file" name="textfile" /></td>
    </tr>
    <tr>
        <td><?php echo $strReplaceTable; ?></td>
        <td><input type="checkbox" name="replace" value="REPLACE" /><?php echo $strReplace; ?></td>
        <td><?php echo $strTheContents; ?></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsTerminatedBy; ?></td>
        <td><input type="text" name="field_terminater" size="2" maxlength="2" value=";" /></td>
        <td><?php echo $strTheTerminator; ?></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsEnclosedBy; ?></td>
        <td>
            <input type="text" name="enclosed" size="1" maxlength="1" value="&quot;" />
            <input type="checkbox" name="enclose_option" value="OPTIONALLY" /><?php echo $strOptionally . "\n"; ?>
        </td>
        <td><?php echo $strOftenQuotation; ?></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsEscapedBy; ?></td>
        <td><input type="text" name="escaped" size="2" maxlength="2" value="\" /></td>
        <td><?php echo $strOptionalControls; ?></td>
    </tr>
    <tr>
        <td><?php echo $strLinesTerminatedBy; ?></td>
        <td><input type="text" name="line_terminator" size="8" maxlength="8" value="<?php echo ((which_crlf() == "\n") ? '\n' : '\r\n'); ?>" /></td>
        <td><?php echo $strCarriage; ?><br /><?php echo $strLineFeed; ?></td>
    </tr>
    <tr>
        <td><?php echo $strColumnNames; ?></td>
        <td><input type="text" name="column_name" /></td>
        <td><?php echo $strIfYouWish; ?></td>
    </tr>
    <tr>
        <td colspan="3" align="center"><?php print show_docu('manual_Reference.html#LOAD_DATA'); ?></td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <input type="hidden" name="zero_rows" value="<?php echo $strTheContent; ?>" />
            <input type="hidden" name="goto" value="tbl_properties.php3" />
            <input type="hidden" name="into_table" value="<?php echo $table; ?>" />
            <input type="submit" name="btnLDI" value="<?php echo $strSubmit; ?>" />&nbsp;&nbsp;
            <input type="reset" value="<?php echo $strReset; ?>" />
        </td>
    </tr>
</table>
</form>


<?php
/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
