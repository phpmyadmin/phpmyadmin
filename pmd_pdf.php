<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin-Designer
 */

/**
 *
 */
include_once 'pmd_common.php';
if (! isset($scale)) {
    $no_die_save_pos = 1;
    include_once 'pmd_save_pos.php';
}
require_once './libraries/relation.lib.php';

if (isset($scale) && ! isset($createpage)) {
    if (empty($pdf_page_number)) {
        die("<script>alert('Pages not found!');history.go(-2);</script>");
    }

    $pmd_table = PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($GLOBALS['cfgRelation']['designer_coords']);
    $pma_table = PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords']);
    $scale_q = PMA_sqlAddslashes($scale);
    $pdf_page_number_q = PMA_sqlAddslashes($pdf_page_number);

    if (isset($exp)) {

        $sql = "REPLACE INTO " . $pma_table . " (db_name, table_name, pdf_page_number, x, y) SELECT db_name, table_name, " . $pdf_page_number_q . ", ROUND(x/" . $scale_q . ") , ROUND(y/" . $scale_q . ") y FROM " . $pmd_table . " WHERE db_name = '" . PMA_sqlAddslashes($db) . "'";

        PMA_query_as_controluser($sql,TRUE,PMA_DBI_QUERY_STORE);
    }

    if (isset($imp)) {
        PMA_query_as_controluser(
        'UPDATE ' . $pma_table . ',' . $pmd_table .
        ' SET ' . $pmd_table . '.`x`= ' . $pma_table . '.`x` * '. $scale_q . ',
        ' . $pmd_table . '.`y`= ' . $pma_table . '.`y` * '. $scale_q .'
        WHERE
        ' . $pmd_table . '.`db_name`=' . $pma_table . '.`db_name`
        AND
        ' . $pmd_table . '.`table_name` = ' . $pma_table . '.`table_name`
        AND
        ' . $pmd_table . '.`db_name`=\''. PMA_sqlAddslashes($db) .'\'
        AND pdf_page_number = ' . $pdf_page_number_q . ';', TRUE, PMA_DBI_QUERY_STORE);     
    }

    die("<script>alert('$strModifications');history.go(-2);</script>");
}
if (isset($createpage)) {
    /*
     * @see pdf_pages.php
     */
    $query_default_option = PMA_DBI_QUERY_STORE;

    $pdf_page_number = PMA_REL_create_page($newpage, $cfgRelation, $db, $query_default_option);
}
// no need to use pmd/styles
require_once './libraries/header_meta_style.inc.php';
?>
</head>
<body>
<br>
<div>
  <form name="form1" method="post" action="pmd_pdf.php">
<?php echo PMA_generate_common_hidden_inputs($db); ?>
    <div>
    <fieldset><legend><?php echo $GLOBALS['strExport'] . '/' . $GLOBALS['strImport']; ?></legend>
    <p><?php echo $strExportImportToScale; ?>:
      <select name="scale">
        <option value="1">1:1</option>
        <option value="2">1:2</option>
    <option value="3" selected>1:3 (<?php echo $strRecommended; ?>)</option>
        <option value="4">1:4</option>
        <option value="5">1:5</option>
        </select>
      </p>
  <p><?php echo $strToFromPage; ?>:

      <select name="pdf_page_number">
      <?php
      $table_info_result = PMA_query_as_controluser('SELECT * FROM '.PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages']).'
                                             WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\'');
      while($page = PMA_DBI_fetch_assoc($table_info_result))
      {
      ?>
      <option value="<?php echo $page['page_nr'] ?>"><?php echo htmlspecialchars($page['page_descr']) ?></option>
      <?php
      }
      ?>
      </select>
  <input type="submit" name="exp" value="<?php echo $strExport; ?>">
  <input type="submit" name="imp" value="<?php echo $strImport; ?>">
    </fieldset>
    </div>
    <div>
    <fieldset><legend><?php echo $GLOBALS['strCreatePage']; ?></legend>
        <input type="text" name="newpage" />
        <input type="submit" name="createpage" value="<?php echo $strGo; ?>">
        </fieldset>
    </div>
  </form>
</div>
</body>
</html>

