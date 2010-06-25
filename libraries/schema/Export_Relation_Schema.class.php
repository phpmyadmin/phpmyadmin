<?php
// Using Abstract Factory Pattern for exporting relational Schema in different Formats !
abstract class PMA_Export_Relation_Schema
{
	private $_pageTitle; // Title of the page
	private  $_autoLayoutType; // Internal or Foreign Key Relations;
	protected $same_wide;

	public function setPageTitle($title)
	{
		$this->_pageTitle=$title;
	}
	
	public function setExportType($type)
	{
		$this->_pageTitle=$title;
	}
		
	public function setSameWidthTables($width)
	{
		$this->same_wide=$width;
	}
	
	public function getAllTables($db,$page_number)
	{
	global $cfgRelation;
		 // Get All tables
        $tab_sql = 'SELECT table_name FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
         . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
         . ' AND pdf_page_number = ' . $page_number;
		// echo $tab_sql;
        $tab_rs = PMA_query_as_controluser($tab_sql, null, PMA_DBI_QUERY_STORE);
        if (!$tab_rs || !PMA_DBI_num_rows($tab_rs) > 0) {
            $this->PMA_SCHEMA_die('',__('No tables'));
        } while ($curr_table = @PMA_DBI_fetch_assoc($tab_rs)) {
            $alltables[] = PMA_sqlAddslashes($curr_table['table_name']);
        }
		return $alltables;
	}
	
	public function initTable()
	{
		
	}
	
	
	/**
     * Displays an error message
     */
    function PMA_Schema_die($type = '',$error_message = '')
    {
        global $db;

        require_once './libraries/header.inc.php';

        echo "<p><strong> {$type} " . __("SCHEMA ERROR") . "</strong></p>" . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";

        echo '<a href="export_relation_schema.php?' . PMA_generate_common_url($db)
         . '">' . __('Back') . '</a>';
        echo "\n";

        require_once './libraries/footer.inc.php';
    } // end of the "PMA_PDF_die()" function
}
?>