<?php
/* $Id$ */


require("grab_globals.inc.php3");
 

if(!isset($param) || $param[0] == "")
  {
   require("header.inc.php3");
   $result = mysql_list_fields($db, $table);
   if (!$result)
     {
      mysql_die();
     }
   else
    {
     ?>
       <form method="POST" ACTION="tbl_select.php3">
       <input type="hidden" name="server" value="<?php echo $server;?>">
       <input type="hidden" name="lang" value="<?php echo $lang;?>">
       <input type="hidden" name="db" value="<?php echo $db;?>">
       <input type="hidden" name="table" value="<?php echo $table;?>">
       <?php echo $strSelectFields; ?><br>
       <select multiple NAME="param[]" size="10">

       <?php
         for ($i=0 ; $i<mysql_num_fields($result); $i++)
           {
        $field = mysql_field_name($result,$i);
            if($i >= 0)
             echo "<option value=$field selected>$field</option>\n";
            else
             echo "<option value=$field>$field</option>\n";
           }
       ?>

       </select><br>
       <div align="left">
       <ul><li><?php echo $strDisplay; ?> <input type="text" size=4 name = "sessionMaxRows" value=<?php echo $cfgMaxRows; ?>>
                <?php echo $strLimitNumRows; ?>
       <li><?php echo $strAddSearchConditions; ?><br>
       <input type="text" name="where"> <?php print show_docu("manual_Reference.html#Functions");?><br>

   <br>
   <li><?php echo $strDoAQuery; ?><br>
   <table border="<?php echo $cfgBorder;?>">
   <tr>
   <th><?php echo $strField; ?></th>
   <th><?php echo $strType; ?></th>
   <th><?php echo $strValue; ?></th>
   </tr>
   <?php
   $result = mysql_list_fields($db, $table);
   for ($i=0;$i<mysql_num_fields($result);$i++)
       {
       $field = mysql_field_name($result,$i);;
       $type = mysql_field_type($result,$i);
       $len = mysql_field_len($result,$i);
       $bgcolor = $cfgBgcolorOne;
       $i % 2  ? 0: $bgcolor = $cfgBgcolorTwo;

       echo "<tr bgcolor=".$bgcolor.">";
       echo "<td>$field</td>";
       echo "<td>$type</td>";
       echo "<td><input type=text name=fields[] style=\"width: ".$cfgMaxInputsize."\" maxlength=".$len."></td>\n";
       echo "<input type=hidden name=names[] value=\"$field\">\n";
       echo "<input type=hidden name=types[] value=\"$type\">\n";
       echo "</tr>";
       }
       echo "</table><br>";
?>

       <input name="SUBMIT" value="<?php echo $strGo; ?>" type="SUBMIT">
     </form></ul>

  <?php
   }
   require ("footer.inc.php3");
 }
else
 {
       $sql_query="SELECT $param[0]";
       $i=0;
       $c=count($param);
       while($i < $c)
         {
           if($i>0) $sql_query .= ",$param[$i]";
           $i++;
         }
       $sql_query .= " from $table";
       if ($where != "") {
        $sql_query .= " where $where";
       } else {
    $sql_query .= " where 1";
       for ($i=0;$i<count($fields);$i++)
           {
        if (!empty($fields) && $fields[$i] != "") {
            $quot="";
               if ($types[$i]=="string"||$types[$i]=="blob") {
                $quot="\"";
                $cmp="like";
            } elseif($types[$i]=="date"||$types[$i]=="time") {
                $quot="\"";
                $cmp="=";
            } else {
                $cmp="=";
                $quot="";
                if (substr($fields[$i],0,1)=="<" || substr($fields[$i],0,1)==">") $cmp="";
            }
            $sql_query .= " and $names[$i] $cmp $quot$fields[$i]$quot";
        }
    }
       }
       Header("Location:sql.php3?sql_query=".urlencode($sql_query)."&goto=db_details.php3&server=$server&lang=$lang&db=$db&table=$table&pos=0&sessionMaxRows=$sessionMaxRows");
  }

?>
