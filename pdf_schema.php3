<?php
/* $Id$ */

/**
 * Contributed by Maxime Delorme and merged by lem9
 */


/**
 * Gets some core scripts
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./libraries/relation.lib.php3');

$cfgRelation = PMA_getRelationsParam();
if(!$cfgRelation['relwork']) {
    echo sprintf($strNotSet,'relation','config.inc.php3') . '<br /><a href="Documentation.html#relation" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!$cfgRelation['displaywork']) {
    echo sprintf($strNotSet,'table_info','config.inc.php3') . '<br /><a href="Documentation.html#table_info" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!isset($cfgRelation['table_coords'])){
    echo sprintf($strNotSet,'table_coords','config.inc.php3') . '<br /><a href="Documentation.html#table_coords" target="documentation">' . $strDocu . '</a>';
    die();
}
if(!isset($cfgRelation['pdf_pages'])) {
    echo sprintf($strNotSet,'pdf_page','config.inc.php3') . '<br /><a href="Documentation.html#pdf_pages" target="documentation">' . $strDocu . '</a>';
    die();
}

/**
 * Gets the "fpdf" libraries and defines the pdf font path
 */
require('./libraries/fpdf/fpdf.php3');
// loic1: PHP3 compatibility
// define('FPDF_FONTPATH', './libraries/fpdf/font/');
$FPDF_font_path = './libraries/fpdf/font/';


/**
 * Emulates the "array_search" function with PHP < 4.0.5
 */
if (PMA_PHP_INT_VERSION < 40005) {
    function array_search($needle, $haystack) {
        $match         = FALSE;

        reset($haystack);
        while (list($key, $value) = each($haystack)) {
            if ($value == $needle) {
                $match = $key;
            }
        } // end while

        return $match;
    } // end of the "array_search" function
} // end if



/**
 * Extends the "FPDF" class and prepares the work
 *
 * @access  public
 *
 * @see     FPDF
 */
class PMA_PDF extends FPDF
{
    /**
     * Defines private properties
     */
    var $x_min;
    var $y_min;
    var $l_marg;
    var $t_marg;
    var $scale;
    var $title;


    /**
     * The PMA_PDF constructor
     *
     * This function just refers to the "FPDF" constructor: with PHP3 a class
     * must have a constructor
     *
     * @param  string  The page orientation (p, portrait, l or landscape)
     * @param  string  The unit for sizes (pt, mm, cm or in)
     * @param  mixed   The page format (A3, A4, A5, letter, legal or an array
     *                 with page sizes)
     *
     * @access public
     *
     * @see     FPDF::FPDF()
     */
    function PMA_PDF($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        $this->FPDF($orientation, $unit, $format);
    } // end of the "PMA_PDF()" method


    /**
     * Sets the scalling factor, defines minimum coordinates and margins
     *
     * @param  double  The scalling factor
     * @param  double  The minimum X coordinate
     * @param  double  The minimum Y coordinate
     * @param  double  The left margin
     * @param  double  The top margin
     *
     * @access public
     */
    function PMA_PDF_setScale($scale = 1, $x_min = 0, $y_min = 0, $l_marg = -1, $t_marg = -1)
    {
        $this->scale      = $scale;
        $this->x_min      = $x_min;
        $this->y_min      = $y_min;
        if ($this->l_marg != -1) {
            $this->l_marg = $l_marg;
        }
        if ($this->t_marg != -1) {
            $this->t_marg = $t_marg;
        }
    } // end of the "PMA_PDF_setScale" function


    /**
     * Outputs a scalled cell
     *
     * @param   double   The cell width
     * @param   double   The cell height
     * @param   string   The text to output
     * @param   mixed    Wether to add borders or not
     * @param   integer  Where to put the cursor once the output is done
     * @param   string   Align mode
     * @param   integer  Whether to fill the cell with a color or not
     *
     * @access public
     *
     * @see     FPDF::Cell()
     */
    function PMA_PDF_cellScale($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0)
    {
        $h = $h / $this->scale;
        $w = $w / $this->scale;
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill);
    } // end of the "PMA_PDF_cellScale" function


    /**
     * Draws a scalled line
     *
     * @param   double  The horizontal position of the starting point
     * @param   double  The vertical position of the starting point
     * @param   double  The horizontal position of the ending point
     * @param   double  The vertical position of the ending point
     *
     * @access public
     *
     * @see     FPDF::Line()
     */
    function PMA_PDF_lineScale($x1, $y1, $x2, $y2)
    {
        $x1 = ($x1 - $this->x_min) / $this->scale + $this->l_marg;
        $y1 = ($y1 - $this->y_min) / $this->scale + $this->t_marg;
        $x2 = ($x2 - $this->x_min) / $this->scale + $this->l_marg;
        $y2 = ($y2 - $this->y_min) / $this->scale + $this->t_marg;
        $this->Line($x1, $y1, $x2, $y2);
    } // end of the "PMA_PDF_lineScale" function


    /**
     * Sets x and y scalled positions
     *
     * @param   double  The x position
     * @param   double  The y position
     *
     * @access public
     *
     * @see     FPDF::SetXY()
     */
    function PMA_PDF_setXyScale($x, $y)
    {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $y = ($y - $this->y_min) / $this->scale + $this->t_marg;
        $this->SetXY($x, $y);
    } // end of the "PMA_PDF_setXyScale" function


    /**
     * Sets the X scalled positions
     *
     * @param   double  The x position
     *
     * @access public
     *
     * @see     FPDF::SetX()
     */
    function PMA_PDF_setXScale($x)
    {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $this->SetX($x);
    } // end of the "PMA_PDF_setXScale" function


    /**
     * Sets the scalled font size
     *
     * @param   double   The font size (in points)
     *
     * @access public
     *
     * @see     FPDF::SetFontSize()
     */
    function PMA_PDF_setFontSizeScale($size)
    {
        // Set font size in points
        $size = $size / $this->scale;
        $this->SetFontSize($size);
    } // end of the "PMA_PDF_setFontSizeScale" function


    /**
     * Sets the scalled line width
     *
     * @param   double  The line width
     *
     * @access public
     *
     * @see     FPDF::SetLineWidth()
     */
    function PMA_PDF_setLineWidthScale($width)
    {
        $width = $width / $this->scale;
        $this->SetLineWidth($width);
    } // end of the "PMA_PDF_setLineWidthScale" function


    /**
     * Displays an error message
     *
     * @param   string   the error mesage
     *
     * @global  array    the PMA configuration array
     * @global  integer  the current server id
     * @global  string   the current language
     * @global  string   the current database name
     * @global  string   the current charset
     * @global  string   the current text direction
     * @global  string   a localized string
     * @global  string   an other localized string
     *
     * @access  public
     */
    function PMA_PDF_die($error_message = '')
    {
        global $cfg;
        global $server, $lang, $db;
        global $charset, $text_dir, $strRunning, $strDatabase;

        include('./header.inc.php3');

        echo '<p><b>PDF - '. $GLOBALS['strError'] . '</b></p>' . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";

        echo '<a href="db_details_structure.php3'
             . '?lang=' . $lang
             . '&amp;convcharset=' . $convcharset
             . '&amp;server=' . $server
             . '&amp;db=' . urlencode($db)
             . '">' . $GLOBALS['strBack'] . '</a>';
        echo "\n";

        include('./footer.inc.php3');
        exit();
    } // end of the "PMA_PDF_die()" function


    /**
     * Aliases the "Error()" function from the FPDF class to the
     * "PMA_PDF_die()" one
     *
     * @param   string   the error mesage
     *
     * @access  public
     *
     * @see     PMA_PDF_die()
     */
    function Error($error_message = '')
    {
        $this->PMA_PDF_die($error_message);
    } // end of the "Error()" method
} // end of the "PMA_PDF" class


/**
 * Draws tables schema
 *
 * @access  private
 *
 * @see     PMA_RT
 */
class PMA_RT_Table
{
    /**
     * Defines private properties
     */
    var $nb_fiels;
    var $table_name;
    var $width = 0;
    var $height;
    var $fields      = array();
    var $height_cell = 6;
    var $x, $y;


    /**
     * Sets the width of the table
     *
     * @param   integer   The font size
     *
     * @global  object    The current PDF document
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT_Table_setWidth($ff)
    {
        global $pdf;

        reset($this->fields);
        while (list(, $field) = each($this->fields)) {
            $this->width = max($this->width, $pdf->GetStringWidth($field));
        }
        $this->width += $pdf->GetStringWidth('  ');
        $pdf->SetFont($ff, 'B');
        $this->width = max($this->width, $pdf->GetStringWidth('  ' . $this->table_name));
        $pdf->SetFont($ff, '');
    } // end of the "PMA_RT_Table_setWidth()" method


    /**
     * Sets the height of the table
     *
     * @access  private
     */
    function PMA_RT_Table_setHeight()
    {
        $this->height = (count($this->fields) + 1) * $this->height_cell;
    } // end of the "PMA_RT_Table_setHeight()" method


    /**
     * Do draw the table
     *
     * @param   boolean   Whether to display table position or not
     * @param   integer   The font size
     *
     * @global  object    The current PDF document
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT_Table_draw($show_info, $ff)
    {
        global $pdf;

        $pdf->PMA_PDF_setXyScale($this->x, $this->y);
        $pdf->SetFont($ff, 'B');
        $pdf->SetTextColor(200);
        $pdf->SetFillColor(0, 0, 128);
        if ($show_info){
            $pdf->PMA_PDF_cellScale($this->width, $this->height_cell, $pdf->_FPDF_round($this->width) . 'x' . $pdf->_FPDF_round($this->height) . ' ' . $this->table_name, 1, 1, 'C', 1);
        } else {
            $pdf->PMA_PDF_cellScale($this->width, $this->height_cell, $this->table_name, 1, 1, 'C', 1);
        }
        $pdf->PMA_PDF_setXScale($this->x);
        $pdf->SetFont($ff, '');
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);
        
        reset($this->fields);
        while (list(, $field) = each($this->fields)) {
            if($field == $this->primary){$pdf->SetFillColor(215,121,123);}
            if($field == $this->displayfield){$pdf->SetFillColor(142,159,224);}
            $pdf->PMA_PDF_cellScale($this->width, $this->height_cell, ' ' . $field, 1, 1, 'L', 1);
            $pdf->PMA_PDF_setXScale($this->x);
            $pdf->SetFillColor(255);
        }        

        if ($pdf->PageNo() > 1) {
            $pdf->PMA_PDF_die($GLOBALS['strScaleFactorSmall']);
        }
    } // end of the "PMA_RT_Table_draw()" method


    /**
     * The "PMA_RT_Table" constructor
     *
     * @param   string    The table name
     * @param   integer   The font size
     *
     * @global  object    The current PDF document
     * @global  integer   The current page number (from the
     *                    $cfg['Servers'][$i]['table_coords'] table)
     *
     * @access  private
     *
     * @see     PMA_PDF, PMA_RT_Table::PMA_RT_Table_setWidth,
     *          PMA_RT_Table::PMA_RT_Table_setHeight
     */
    function PMA_RT_Table($table_name, $ff)
    {
        global $pdf, $pdf_page_number,$cfgRelation,$db;

        $this->table_name = $table_name;
        $sql              = 'DESCRIBE ' .  PMA_backquote($table_name);
        $result           = PMA_mysql_query($sql);
        if (!$result || !mysql_num_rows($result)) {
            $pdf->PMA_PDF_die(sprintf($GLOBALS['strPdfInvalidTblName'], $table_name));
        }
        // load fields
        while ($row = PMA_mysql_fetch_array($result)) {
            $this->fields[] = $row[0];
        }

        //height and width
        $this->PMA_RT_Table_setWidth($ff);
        $this->PMA_RT_Table_setHeight();

        //x and y
        $sql    = 'SELECT x, y FROM '
                . PMA_backquote($cfgRelation['table_coords'])
                . ' WHERE       db_name = \'' . PMA_sqlAddslashes($db) . '\''
                . ' AND      table_name = \'' . PMA_sqlAddslashes($table_name) . '\''
                . ' AND pdf_page_number = ' . $pdf_page_number;
        $result = PMA_query_as_cu($sql);

        if (!$result || !mysql_num_rows($result)) {
            $pdf->PMA_PDF_die(sprintf($GLOBALS['strConfigureTableCoord'], $table_name));
        }
        list($this->x, $this->y) = PMA_mysql_fetch_array($result);
        $this->x = (double) $this->x;
        $this->y = (double) $this->y;

        //displayfield
        $this->displayfield = getDisplayField($db,$table_name);

        // index
        $sql    =  'SHOW index from '.PMA_backquote($table_name);
        $result = PMA_mysql_query($sql);

        if(mysql_num_rows($result)>0){
            while ($row = PMA_mysql_fetch_array($result)) {
                if($row['Key_name'] == 'PRIMARY'){
                    $this->primary = $row['Column_name'];
                }
            }
        }        
    } // end of the "PMA_RT_Table()" method
} // end class "PMA_RT_Table"



/**
 * Draws relation links
 *
 * @access  private
 *
 * @see     PMA_RT
 */
class PMA_RT_Relation
{
    /**
     * Defines private properties
     */
    var $x_src, $y_src;
    var $src_dir ;
    var $dest_dir;
    var $x_dest, $y_dest;
    var $w_tick = 5;


    /**
     * Gets arrows coordinates
     *
     * @param   string    The current table name
     * @param   string    The relation column name
     *
     * @return  array     Arrows coordinates
     *
     * @access  private
     */
    function PMA_RT_Relation_getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);
        // x_left, x_right, y
        return array($table->x, $table->x + $table->width, $table->y + ($pos + 1.5) * $table->height_cell);
    } // end of the "PMA_RT_Relation_getXy()" method


    /**
     * Do draws relation links
     *
     * @param   boolean   Whether to use one color per relation or not
     * @param   integer   The id of the link to draw
     *
     * @global  object    The current PDF document
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT_Relation_draw($change_color, $i)
    {
        global $pdf;

        if ($change_color){
            $d    = $i % 6;
            $j    = ($i - $d) / 6;
            $j    = $j % 4;
            $j++;
            $case = array(
                        array(1, 0, 0),
                        array(0, 1, 0),
                        array(0, 0, 1),
                        array(1, 1, 0),
                        array(1, 0, 1),
                        array(0, 1, 1)
                    );
            list ($a, $b, $c) = $case[$d];
            $e    = (1 - ($j - 1) / 6);
            $pdf->SetDrawColor($a * 255 * $e, $b * 255 * $e, $c * 255 * $e);       }
        else {
            $pdf->SetDrawColor(0);
        } // end if... else...

        $pdf->PMA_PDF_setLineWidthScale(0.2);
        $pdf->PMA_PDF_lineScale($this->x_src, $this->y_src, $this->x_src + $this->src_dir * $this->w_tick, $this->y_src);
        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest, $this->x_dest, $this->y_dest);
        $pdf->PMA_PDF_setLineWidthScale(0.1);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick, $this->y_src, $this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest);

        //arrow
        $root2 = 2 * sqrt(2);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src + $this->w_tick / $root2);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src - $this->w_tick / $root2);

        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest + $this->w_tick / $root2);
        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest - $this->w_tick / $root2);
        $pdf->SetDrawColor(0);
    } // end of the "PMA_RT_Table_draw()" method


    /**
     * The "PMA_RT_Relation" constructor
     *
     * @param   string   The master table name
     * @param   string   The relation field in the master table
     * @param   string   The foreign table name
     * @param   string   The relation field in the foreign table
     *
     *
     * @access  private
     *
     * @see     PMA_RT_Relation::PMA_RT_Relation_getXy
     */
    function PMA_RT_Relation($master_table, $master_field,  $foreign_table, $foreign_field)
    {
        $src_pos    = $this->PMA_RT_Relation_getXy($master_table , $master_field);
        $dest_pos   = $this->PMA_RT_Relation_getXy($foreign_table, $foreign_field);
        $src_left   = $src_pos[0] - $this->w_tick;
        $src_right  = $src_pos[1] + $this->w_tick;
        $dest_left  = $dest_pos[0] - $this->w_tick;
        $dest_right = $dest_pos[1] + $this->w_tick;

        $d1 = abs($src_left  - $dest_left);
        $d2 = abs($src_right - $dest_left);
        $d3 = abs($src_left  - $dest_right);
        $d4 = abs($src_right - $dest_right);
        $d  = min($d1, $d2, $d3, $d4);

        if ($d == $d1) {
            $this->x_src    = $src_pos[0];
            $this->src_dir  = -1;
            $this->x_dest   = $dest_pos[0];
            $this->dest_dir = -1;
        } else if ($d == $d2) {
            $this->x_src    = $src_pos[1];
            $this->src_dir  = 1;
            $this->x_dest   = $dest_pos[0];
            $this->dest_dir = -1;
        } else if ($d == $d3) {
            $this->x_src    = $src_pos[0];
            $this->src_dir  = -1;
            $this->x_dest   = $dest_pos[1];
            $this->dest_dir = 1;
        } else {
            $this->x_src    =  $src_pos[1];
            $this->src_dir  = 1;
            $this->x_dest   = $dest_pos[1];
            $this->dest_dir = 1;
        }
        $this->y_src        = $src_pos[2];
        $this->y_dest       = $dest_pos[2];
    } // end of the "PMA_RT_Relation()" method
} // end of the "PMA_RT_Relation" class



/**
 * Draws and send the database schema
 *
 * @access  public
 *
 * @see     PMA_PDF
 */
class PMA_RT
{
    /**
     * Defines private properties
     */
    var $tables    = array();
    var $relations = array();
    var $ff        = 'Arial';
    var $x_max     = 0;
    var $y_max     = 0;
    var $scale;
    var $x_min     = 100000;
    var $y_min     = 100000;
    var $t_marg    = 10;
    var $b_marg    = 10;
    var $l_marg    = 10;
    var $r_marg    = 10;


    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param   string   The table name
     *
     * @access  private
     */
    function PMA_RT_setMinMax($table)
    {
        $this->x_max = max($this->x_max, $table->x + $table->width);
        $this->y_max = max($this->y_max, $table->y + $table->height);
        $this->x_min = min($this->x_min, $table->x);
        $this->y_min = min($this->y_min, $table->y);
    } // end of the "PMA_RT_setMinMax()" method


    /**
     * Defines relation objects
     *
     * @param   string   The master table name
     * @param   string   The relation field in the master table
     * @param   string   The foreign table name
     * @param   string   The relation field in the foreign table
     *
     * @access  private
     *
     * @see     PMA_RT_setMinMax()
     */
    function PMA_RT_addRelation($master_table , $master_field,  $foreign_table, $foreign_field)
    {
        if (!isset($this->tables[$master_table])) {
            $this->tables[$master_table] = new PMA_RT_Table($master_table, $this->ff);
            $this->PMA_RT_setMinMax($this->tables[$master_table]);
        }
        if (!isset($this->tables[$foreign_table])) {
            $this->tables[$foreign_table] = new PMA_RT_Table($foreign_table, $this->ff);
            $this->PMA_RT_setMinMax($this->tables[$foreign_table]);
        }
        $this->relations[] = new PMA_RT_Relation($this->tables[$master_table], $master_field, $this->tables[$foreign_table], $foreign_field);
    } // end of the "PMA_RT_addRelation()" method


    /**
     * Draws the grid
     *
     * @global  object  the current PMA_PDF instance
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT_strokeGrid()
    {
        global $pdf;

        $pdf->SetMargins(0, 0);
        $pdf->SetDrawColor(200, 200, 200);

        // Draws horizontal lines
        for ($l = 0; $l < 21; $l++) {
            $pdf->line(0, $l * 10, 297, $l * 10);
            // Avoid duplicates
            if ($l > 0) {
                $pdf->SetXY(0, $l * 10);
                $label = (string) $pdf->_FPDF_round(($l * 10 - $this->t_marg) * $this->scale + $this->y_min);
                $pdf->Cell(5, 5, ' ' . $label);
            } // end if
        } // end for

        // Draws vertical lines
        for ($j = 0; $j < 30 ;$j++) {
            $pdf->line($j * 10, 0, $j * 10, 210);
            $pdf->SetXY($j * 10, 0);
            $label = (string) $pdf->_FPDF_round(($j * 10 - $this->l_marg) * $this->scale + $this->x_min);
            $pdf->Cell(5, 7, $label);
        } // end for
    } // end of the "PMA_RT_strokeGrid()" method


    /**
     * Draws relation arrows
     *
     * @param   boolean  Whether to use one color per relation or not
     *
     * @access  private
     *
     * @see     PMA_RT_Relation::PMA_RT_Relation_draw()
     */
    function PMA_RT_drawRelations($change_color)
    {
        $i = 0;
        reset($this->relations);
        while (list(, $relation) = each($this->relations)) {
            $relation->PMA_RT_Relation_draw($change_color, $i);
            $i++;
        } // end while
    } // end of the "PMA_RT_drawRelations()" method


    /**
     * Draws tables
     *
     * @param   boolean  Whether to display table position or not
     *
     * @access  private
     *
     * @see     PMA_RT_Table::PMA_RT_Table_draw()
     */
    function PMA_RT_drawTables($show_info)
    {
        reset($this->tables);
        while (list(, $table) = each($this->tables)) {
            $table->PMA_RT_Table_draw($show_info, $this->ff);
        }
    } // end of the "PMA_RT_drawTables()" method


    /**
     * Ouputs the PDF document to a file
     *
     * @global  object   The current PDF document
     * @global  string   The current database name
     * @global  integer  The current page number (from the
     *                   $cfg['Servers'][$i]['table_coords'] table)
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT_showRt()
    {
        global $pdf, $db, $pdf_page_number;

        $pdf->SetDisplayMode('fullpage');
        $pdf->Output($db . '_' . $pdf_page_number . '.pdf', TRUE);
    } // end of the "PMA_RT_showRt()" method


    /**
     * The "PMA_RT" constructor
     *
     * @param   mixed    The scalling factor
     * @param   integer  The page number to draw (from the
     *                   $cfg['Servers'][$i]['table_coords'] table)
     * @param   boolean  Whether to display table position or not
     * @param   boolean  Whether to use one color per relation or not
     * @param   boolean  Whether to draw grids or not
     *
     * @global  object   The current PDF document
     *
     * @access  private
     *
     * @see     PMA_PDF
     */
    function PMA_RT($scale, $which_rel, $show_info = 0, $change_color = 0 , $show_grid = 0)
    {
        global $pdf, $db, $cfgRelation;;

        // Font face depends on the current language
        $this->ff     = str_replace('"', '', substr($GLOBALS['right_font_family'], 0, strpos($GLOBALS['right_font_family'], ',')));

        // Initializes a new document
        $pdf          = new PMA_PDF('L');
        $pdf->title   = sprintf($GLOBALS['strPdfDbSchema'], $GLOBALS['db'], $which_rel);
        $pdf->cMargin = 0;
        $pdf->Open();
        $pdf->SetTitle($pdf->title);
        $pdf->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $pdf->AliasNbPages();
        $pdf->Addpage();
        $pdf->SetFont($this->ff, '', 14);
        $pdf->SetAutoPageBreak('auto');

        //  get tables on this page
        $tab_sql  = 'SELECT table_name FROM ' . PMA_backquote($cfgRelation['table_coords'])
                  . ' WHERE db_name = \'' . $db . '\''
                  . ' AND pdf_page_number=' .$which_rel;
        $tab_rs   = PMA_query_as_cu($tab_sql);

        while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
            $alltables[]     = $curr_table['table_name'];
            $intable         = "'" . implode("','",$alltables) . "'";
        }

        $sql = 'SELECT *'
             . ' FROM ' . PMA_backquote($cfgRelation['relation'])
             . ' WHERE master_db   = \'' . $db . '\' '
             . ' AND foreign_db    = \'' . $db . '\' '
             . ' AND master_table  IN (' . $intable .')'
             . ' AND foreign_table IN (' . $intable .')';
        $result =  PMA_query_as_cu($sql);

        if (!$result || !mysql_num_rows($result)) {
            $pdf->PMA_PDF_die($GLOBALS['strPdfInvalidPageNum']);
        }
        while ($row = PMA_mysql_fetch_array($result)) {
            $this->PMA_RT_addRelation($row['master_table'] , $row['master_field'], $row['foreign_table'], $row['foreign_field']);
        }
        
        // Defines the scale factor
        if ($scale == 'auto') {
            $this->scale = ceil(max(($this->x_max - $this->x_min) / (297 - $this->r_marg - $this->l_marg), ($this->y_max - $this->y_min) / (210 - $this->t_marg - $this->b_marg)) * 100) / 100;
            $pdf->PMA_PDF_setScale($this->scale, $this->x_min, $this->y_min, $this->l_marg, $this->t_marg);
        } else {
            $this->scale = $scale;
            $pdf->PMA_PDF_setScale($scale);
        } // end if... else...

        // Builds and save the PDF document
        $pdf->PMA_PDF_setLineWidthScale(0.1);

        if ($show_grid) {
            $pdf->SetFontSize(10);
            $this->PMA_RT_strokeGrid();
        }
        $pdf->PMA_PDF_setFontSizeScale(14);
        $this->PMA_RT_drawRelations($change_color);
        $this->PMA_RT_drawTables($show_info);

        $this->PMA_RT_showRt();
    } // end of the "PMA_RT()" method
} // end of the "PMA_RT" class




/**
 * Main logic
 */
if (!isset($pdf_page_number)) {
    $pdf_page_number  = 1;
}
$show_grid            = (isset($show_grid) && $show_grid == 'on') ? 1 : 0;
$show_color           = (isset($show_color) && $show_color == 'on') ? 1 : 0;
$show_table_dimension = (isset($show_table_dimension) && $show_table_dimension == 'on') ? 1 : 0;

PMA_mysql_select_db($db);

$rt  = new PMA_RT('auto', $pdf_page_number, $show_table_dimension, $show_color, $show_grid);
?>
