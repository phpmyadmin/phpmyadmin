<?php
/* $Id$ */


require("./grab_globals.inc.php3");
 

require("./header.inc.php3");

if(empty($Columns))
    $Columns = 3;  # initial number of columns

if(!isset($Add_Col))
    $Add_Col = "";

if(!isset($Add_Row))
    $Add_Row = "";

if(!isset($Rows))
    $Rows = "";

if(!isset($InsCol))
    $InsCol = "";

if(!isset($DelCol))
    $DelCol = "";

if(!isset($Criteria))
    $Criteria = "";

if(!isset($InsRow))
    $InsRow = "";

if(!isset($DelRow))
    $DelRow = "";

if(!isset($AndOrRow))
    $AndOrRow = "";

if(!isset($AndOrCol))
    $AndOrCol = "";


$wid =  "12";
$widem = $wid. "em";
$col = $Columns + $Add_Col;

if($col < 0)
    $col = 0;
$row = $Rows + $Add_Row;
if($row < 0)
    $row = 0;

$tbl_result = mysql_list_tables ($db);
$i = 0; $k = 0;

if(!empty($TableList))
{
    for($x=0; $x<sizeof($TableList); $x++)
        $tbl_names[$TableList[$x]] =  "selected";
}

while($i < mysql_num_rows($tbl_result))
{
    $tbl = mysql_tablename ($tbl_result, $i);
    $fld_results = mysql_list_fields($db, $tbl);
    $j = 0;

    if(empty($tbl_names[$tbl]) && !empty($TableList))
        $tbl_names[$tbl]= "";
    else
        $tbl_names[$tbl]= "selected";

    if($tbl_names[$tbl]== "selected")
    {
        $fld[$k++] =  "$tbl.*";
        while($j < mysql_num_fields($fld_results))
        {
            $fld[$k] = mysql_field_name ($fld_results, $j);
            $fld[$k] =  "$tbl.$fld[$k]";
            $k++;
            $j++;
        }
    }

    $i++;
}

echo "<form action='tbl_qbe.php3' method='POST'>\n";
echo "<table border='$cfgBorder'><tr>\n";

### Field columns
echo "<td align='RIGHT' bgcolor='$cfgThBgcolor'><b>$strField:</b></td>\n";
$z=0;
for($x = 0; $x < $col; $x++)
{
    if(isset($InsCol[$x]) && $InsCol[$x]== "on")
    {
        echo  "<td align=center bgcolor='$cfgBgcolorOne'><select style='width: $widem;' name='Field[$z]' size='1'>\n";
        echo  "<option value=''></option>\n";
        for($y = 0; $y < sizeof($fld); $y++)
        {
            $sel =  "";
            if($fld[$y] ==  "")
                $sel =  "selected";
            echo  "<option $sel value='$fld[$y]'>$fld[$y]</option>\n";
        }
        echo  "</select>\n";
        echo  "</td>\n";
        $z++;
    }

    if(isset($DelCol[$x]) && $DelCol[$x]== "on")
        continue;
    echo "<td align=center bgcolor='$cfgBgcolorOne'><select style='width: $widem;' name='Field[$z]' size='1'>\n";
    echo "<option value=''></option>\n";
    for($y = 0; $y < sizeof($fld); $y++)
    {
        $sel =  "";
        if($fld[$y] == $Field[$x])
        {
            $curField[$z]=$Field[$x];
            $sel =  "selected";}
            echo  "<option $sel  value='$fld[$y]'>$fld[$y]</option>\n";
        }
    $z++;
    echo  "</select>\n";
    echo  "</td>\n";
}
echo  "</tr><tr>\n";

### Sort columns
echo  "<td align='RIGHT' bgcolor='$cfgThBgcolor'><b>$strSort:</b></td>\n";
$z = 0;
for($x = 0; $x < $col; $x++)
{
    if(isset($InsCol[$x]) && $InsCol[$x]== "on")
    {
        echo "<td align=center bgcolor='$cfgBgcolorTwo'><select style='width: $widem;' name='Sort[$z]' size='1'>\n";
        echo "<option value=''></option>\n";
        echo "<option value='ASC'>$strAscending</option>\n";
        echo "<option value='DESC'>$strDecending</option>\n";
        echo "</select>\n";
        echo "</td>\n";
      $z++;
    }
    if(isset($DelCol[$x]) && $DelCol[$x]== "on")
        continue;
    echo  "<td align=center bgcolor='$cfgBgcolorTwo'><select style='width: $widem;' name='Sort[$z]' size='1'>\n";
    echo  "<option value=''></option>\n";
    if($Sort[$x] ==  "ASC")
    {
        $curSort[$z]=$Sort[$x];
        $sel =  "selected";
    }
    else
        $sel =  "";
    echo  "<option $sel value='ASC'>$strAscending</option>\n";
    if($Sort[$x] ==  "DESC")
    {
        $curSort[$z]=$Sort[$x];
        $sel =  "selected";
    }
    else
        $sel =  "";
    echo  "<option $sel value='DESC'>$strDescending</option>\n";
    echo  "</select>\n";
    echo  "</td>\n";
    $z++;
}

### Show columns
echo  "</tr><tr><td align='RIGHT' bgcolor='$cfgThBgcolor'><b>$strShow:</b></td>\n";
$z=0;
for($x = 0; $x < $col; $x++)
{
    if(isset($InsCol[$x]) && $InsCol[$x]== "on")
    {
        echo  "<td align='CENTER'><input type='Checkbox' name='Show[$z]'></td>\n";
        $z++;
    }
    if(isset($DelCol[$x]) && $DelCol[$x]== "on")
        continue;
    $sel =  "";
    if(isset($Show[$x]))
    {
        $sel =  "checked";
        $curShow[$z] = $Show[$x];
    }
    echo  "<td align='CENTER' bgcolor='$cfgBgcolorOne'><input type='Checkbox' $sel name='Show[$z]'></td>\n";
    $z++;
}

### Criteria columns
echo  "</tr><tr><td align='RIGHT' bgcolor='$cfgThBgcolor'><b>$strCriteria:</b></td>\n";
$z=0;
for($x = 0; $x < $col; $x++)
{
    if(isset($InsCol[$x]) && $InsCol[$x]== "on")
    {
        echo  "<td align=center bgcolor='$cfgBgcolorTwo'><input type='Text' name='Criteria[$z]' value='' style='width: $widem;' size='20'></td>\n";
        $z++;
    }
    if(isset($DelCol[$x]) && $DelCol[$x]== "on")
        continue;
    if(get_magic_quotes_gpc()) {
      $stripped_Criteria = stripslashes($Criteria[$x]);
    } else {
      $stripped_Criteria = $Criteria[$x];
    }
    echo "<td align=center bgcolor='$cfgBgcolorTwo'>";
    echo "<input type=\"Text\" name=\"Criteria[$z]\" value=\"".$stripped_Criteria."\" style=\"width: $widem;\" size=\"20\">";
    echo "</td>\n";
    $curCriteria[$z] = $Criteria[$x];
    $z++;
}

### And/Or columns and rows
$w=0;
for($y = 0; $y <= $row; $y++)
{
    $bgcolor = $cfgBgcolorOne;
    if($y % 2 == 0)
        $bgcolor = $cfgBgcolorTwo;
    if($InsRow[$y]== "on")
    {
        echo "</tr><tr><td nowrap align='RIGHT' bgcolor='$bgcolor'>";
        $chk[ 'or'] =  "checked"; $chk[ 'and'] =  "";
        # Row controls
        echo  "
         <table bgcolor='$bgcolor'><tr>
         <td align=right nowrap><small>$strQBEIns:</small><input type='checkbox' name='InsRow[$w]'></td>
         <td align=right><b>$strAnd:</b></td>
         <td><input type='radio' ".$chk[ 'and'].  " name='AndOrRow[$w]' value='and'></td></tr>
         </tr><td align=right nowrap><small>$strQBEDel:</small><input type='checkbox' name='DelRow[$w]'></td>
         <td align=right><b>$strOr:</b></td>
         <td><input type='radio' ".$chk[ 'or']. " name='AndOrRow[$w]' value='or'></td></tr>
         </table></td>\n";
        $z=0;
        for ($x = 0; $x < $col; $x++)
        {
            if($InsCol[$x]== "on")
            {
                $or =  "Or".$w;
                echo  "<td align=center bgcolor='$bgcolor'><textarea style='width: $widem;' rows=2 name='".$or. "[$z]'></textarea></td>\n";
                $z++;
            }
            if($DelCol[$x]== "on")
                continue;
            $or =  "Or".$w;
            echo  "<td align=center bgcolor='$bgcolor'><textarea rows=2 style='width: $widem;' name='".$or. "[$z]'></textarea></td>\n";
            $z++;
        }
        $w++;
    }
    if($DelRow[$y]== "on")
        continue;
    echo  "</tr><tr><td nowrap align='RIGHT'>";
    $curAndOrRow[$w]=$AndOrRow[$y];
    if($AndOrRow[$y] ==  'and')
    {
        $chk[ 'and'] =  "checked";
        $chk[ 'or'] =  "";
    }
    else
    {
        $chk[ 'or'] =  "checked";
        $chk[ 'and'] =  "";
    }

    # Row controls
    echo  "
     <table bgcolor='$bgcolor'><tr>
     <td align=right nowrap><small>$strQBEIns:</small><input type='checkbox' name='InsRow[$w]'></td>
     <td align=right><b>$strAnd:</b></td>
     <td><input type='radio' ".$chk[ 'and'].  " name='AndOrRow[$w]' value='and'></td></tr>
     </tr><td align=right nowrap><small>$strQBEDel:</small><input type='checkbox' name='DelRow[$w]'></td>
     <td align=right><b>$strOr:</b></td>
     <td><input type='radio' ".$chk[ 'or']. " name='AndOrRow[$w]' value='or'></td></tr>
     </table></td>\n";
    $z=0;
    for ($x = 0; $x < $col; $x++)
    {
        if(isset($InsCol[$x]) && $InsCol[$x]== "on")
        {
            $or =  "Or".$w;
            echo  "<td bgcolor='$bgcolor'><textarea style='width: $widem;' rows=2 name='".$or. "[$z]'></textarea></td>\n";
            $z++;
        }
        if(isset($DelCol[$x]) && $DelCol[$x]== "on")
            continue;
        $or =  "Or".$y;
        if(!isset(${$or}))
            ${$or} = "";
	if(get_magic_quotes_gpc()) {
	  $stripped_or = stripslashes(${$or}[$x]);
	} else {
	  $stripped_or = ${$or}[$x];
	}
        echo  "<td bgcolor='$bgcolor'>";
	echo "<textarea rows=2 style='width: $widem;' name='Or".$w. "[$z]'>".$stripped_or. "</textarea>";
	echo "</td>\n";
        ${"cur".$or}[$z] = ${$or}[$x];
        $z++;
    }
    $w++;
}

### Modify columns
echo  "</tr><tr><td align='RIGHT'><b>$strModify:</b></td>\n";
$z=0;
for($x = 0; $x < $col; $x++)
{
    if(isset($InsCol[$x]) && $InsCol[$x]== "on")
    {
        $curAndOrCol[$z]=$AndOrCol[$y];
        if($AndOrCol[$z] ==  'or')
        {
            $chk[ 'or'] =  "checked";
            $chk[ 'and'] =  "";
        }
        else
        {
            $chk[ 'and'] =  "checked";
            $chk[ 'or'] =  "";
        }
        echo  "<td align=center>
        <b>$strOr:</b>
        <input type='radio' ".$chk[ 'or']. " name='AndOrCol[$z]' value='or'>
        <b>$strAnd:</b>
        <input type='radio' ".$chk[ 'and']. " name='AndOrCol[$z]' value='and'>
        <br>$strQBEIns:
        <input type='checkbox' name='InsCol[$z]'>
        &nbsp;&nbsp;$strQBEDel:
        <input type='checkbox' name='DelCol[$z]'>
        </td>\n";
        $z++;
    }
    if(isset($DelCol[$x]) && $DelCol[$x]== "on")
        continue;
    $curAndOrCol[$z]=$AndOrCol[$y];
    if(isset($AndOrCol[$z]) && $AndOrCol[$z] ==  'or')
    {
        $chk[ 'or'] =  "checked";
        $chk[ 'and'] =  "";
    }
    else
    {
        $chk[ 'and'] =  "checked";
        $chk[ 'or'] =  "";
    }
    echo  "<td align=center>
    <b>$strOr:</b>
    <input type='radio' ".$chk[ 'or']. " name='AndOrCol[$z]' value='or'>
    <b>$strAnd:</b>
    <input type='radio' ".$chk[ 'and']. " name='AndOrCol[$z]' value='and'>
    <br>$strQBEIns:
    <input type='checkbox' name='InsCol[$z]'>
    &nbsp;&nbsp;$strQBEDel:
    <input type='checkbox' name='DelCol[$z]'>
    </td>\n";
    $z++;
}
echo  "</table>\n";

### Other controls
echo  "
<table border=0><tr>
<td valign='TOP'>
<table border=0 align='LEFT' valign='TOP'><tr>
<td rowspan='3' valign='TOP'>$strUseTables:<br>
<select name='TableList[]' size='7' multiple>";

while(list( $key, $val ) = each($tbl_names))
    echo  "<option value='$key' $val>$key</option>\n";
echo "</select></td>
<td colspan='2' align='RIGHT' valign='BOTTOM'>\n";
$w--;
echo  "<input type='hidden' value='$db' name='db'>\n";
echo  "<input type='hidden' value='$z' name='Columns'>\n";
echo  "<input type='hidden' value='$w' name='Rows'>\n";

echo  "$strAddDeleteRow: <SELECT size=1 name='Add_Row'>
  <OPTION value='-3'>-3</OPTION>
  <OPTION value='-2'>-2</OPTION>
  <OPTION value='-1'>-1</OPTION>
  <OPTION selected value='0'>0</OPTION>
  <OPTION value='1'>1</OPTION>
  <OPTION value='2'>2</OPTION>
  <OPTION value='3'>3</OPTION>
  </SELECT>
  </td></tr><tr><td colspan='2' align='RIGHT' valign='BOTTOM'>";

echo  "$strAddDeleteColumn: <SELECT size=1 name='Add_Col'>
  <OPTION value='-3'>-3</OPTION>
  <OPTION value='-2'>-2</OPTION>
  <OPTION value='-1'>-1</OPTION>
  <OPTION selected value='0'>0</OPTION>
  <OPTION value='1'>1</OPTION>
  <OPTION value='2'>2</OPTION>
  <OPTION value='3'>3</OPTION>
  </SELECT>
  </td></tr><tr valign=top><td>";
echo  "<input type='Submit' name='modify' value='$strUpdateQuery'>\n";
echo  "<input type='hidden' name='server' value='$server'>\n";
echo  "<input type='hidden' name='lang' value='$lang'>\n";
echo  "</form></td><td>";

### Generate a query

echo  "<form method='get' action='sql.php3'>\n";
echo  "<input type='hidden' name='server' value='$server'>\n";
echo  "<input type='hidden' name='lang' value='$lang'>\n";
echo  "<input type='hidden' name='goto' value='db_details.php3'>\n";
echo  "<input type='hidden' name='db' value='$db'>\n";
echo  "<input type='hidden' name='zero_rows' value='$strSuccess'>";
echo  "<input type='submit' name='SQL' value='$strRunQuery'></td></tr></table></td><td>";
echo  " $strRunSQLQuery<b>$db</b>:<br>";
echo  "<textarea cols=30 rows=7 name='sql_query'>";

#   SELECT
$last_select = 0;
if(!isset($qry_select))
    $qry_select = "";
for($x=0; $x<$col; $x++)
{
    if($last_select && !empty($curField[$x]) && isset($curShow[$x]) && $curShow[$x]== 'on')
        $qry_select .=  ", ";
    if(!empty($curField[$x]) && isset($curShow[$x]) && $curShow[$x]== 'on')
    {
        $qry_select .= $curField[$x];
        $last_select = 1;
    }
}
if(!empty($qry_select))
    echo  "SELECT ".$qry_select. "\n";

#   FROM
if(!isset($TableList))
    $TableList = array();

if(!isset($qry_from))
    $qry_from = "";
for($x=0; $x<sizeof($TableList); $x++)
{
    if($x)
        $qry_from .=  ", ";
    $qry_from .=  "$TableList[$x]";
}
if(!empty($qry_select))
    echo  "FROM ".$qry_from. "\n";

#   WHERE
$qry_where =  "(";
for($x=0; $x<$col; $x++)
{
    if(!empty($curField[$x]) && !empty($curCriteria[$x]) && $x && isset($last_where))
        $qry_where .= strtoupper($curAndOrCol[$last_where]);
    if(!empty($curField[$x]) && !empty($curCriteria[$x]))
    {
        $qry_where .=  "($curField[$x] $curCriteria[$x])";
        $last_where = $x;
    }
}

$qry_where .=  ")";
# OR rows ${"cur".$or}[$x]
if(!isset($curAndOrRow))
    $curAndOrRow = array();
for($y=0; $y<=$row; $y++)
{
    $qry_orwhere =  "(";
    $last_orwhere =  "";
    for($x=0; $x<$col; $x++)
    {
        if(!empty($curField[$x]) && !empty(${ "curOr".$y}[$x]) && $x)
            $qry_orwhere .= strtoupper($curAndOrCol[$last_orwhere]);
        if(!empty($curField[$x]) && !empty(${ 'curOr'.$y}[$x]))
        {
            $qry_orwhere .=  "($curField[$x] ".${ 'curOr'.$y}[$x]. ")";
            $last_orwhere = $x;
        }
    }
    $qry_orwhere .=  ")";
    if($qry_orwhere != "()")
        $qry_where .=  "\n".strtoupper(isset($curAndOrRow[$y]) ? $curAndOrRow[$y]: "").$qry_orwhere;
}

if($qry_where != "()")
    if(get_magic_quotes_gpc()) {
      echo  "WHERE ".stripslashes($qry_where). "\n";
    } else {
      echo  "WHERE ".($qry_where). "\n";
    }

#   ORDER BY
$last_orderby=0;
if(!isset($qry_orderby))
    $qry_orderby = "";

for ($x=0; $x<$col; $x++)
{
    if($last_orderby && $x && !empty($curField[$x]) && !empty($curSort[$x]))
        $qry_orderby .=  ", ";
    if(!empty($curField[$x]) && !empty($curSort[$x]))
    {
        $qry_orderby .=  "$curField[$x] $curSort[$x]";
        $last_orderby = 1;
    }
}

if(!empty($qry_orderby))
    echo  "ORDER BY ".$qry_orderby. "\n";

echo  "</textarea></form></td></tr></table>";

?>

<?php
require("./footer.inc.php3");
?>
