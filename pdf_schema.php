<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contributed by Maxime Delorme and merged by lem9
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Gets some core scripts
 */
require_once './libraries/common.inc.php';

/**
 * Settings for relation stuff
 */
require_once './libraries/relation.lib.php';
require_once './libraries/transformations.lib.php';
require_once './libraries/Index.class.php';

$cfgRelation = PMA_getRelationsParam();

/**
 * Now in ./libraries/relation.lib.php we check for all tables
 * that we need, but if we don't find them we are quiet about it
 * so people can work without.
 * This page is absolutely useless if you didn't set up your tables
 * correctly, so it is a good place to see which tables we can and
 * complain ;-)
 */
if (!$cfgRelation['pdfwork']) {
    echo '<font color="red">' . $strError . '</font><br />' . "\n";
    $url_to_goto = '<a href="' . $cfg['PmaAbsoluteUri'] . 'chk_rel.php?' . $url_query . '">';
    echo sprintf($strRelationNotWorking, $url_to_goto, '</a>') . "\n";
}

/**
 * Font used in PDF.
 *
 * @todo Make this configuratble (at least Sans/Serif).
 */
define('PMA_PDF_FONT', 'DejaVuSans');
require_once './libraries/tcpdf/tcpdf.php';

/**
 * Extends the "FPDF" class and prepares the work
 *
 * @access public
 * @see FPDF
 * @package phpMyAdmin
 */
class PMA_PDF extends TCPDF {
    /**
     * Defines private properties
     */
    var $x_min;
    var $y_min;
    var $l_marg = 10;
    var $t_marg = 10;
    var $scale;
    var $PMA_links;
    var $Outlines = array();
    var $def_outlines;
    var $Alias = array();
    var $widths;

    public function getFh()
    {
        return $this->fh;
    }

    public function getFw()
    {
        return $this->fw;
    }

    public function setCMargin($c_margin)
    {
        $this->cMargin = $c_margin;
    }

    function SetAlias($name, $value)
    {
        $this->Alias[$name] = $value ;
    }

    function _putpages()
    {
        if (count($this->Alias) > 0) {
            $nb = $this->page;
            foreach ($this->Alias AS $alias => $value) {
                for ($n = 1;$n <= $nb;$n++)
                    $this->pages[$n]=str_replace($alias, $value, $this->pages[$n]);
            }
        }
        parent::_putpages();
    }

    /**
     * Sets the scaling factor, defines minimum coordinates and margins
     *
     * @param double $ The scaling factor
     * @param double $ The minimum X coordinate
     * @param double $ The minimum Y coordinate
     * @param double $ The left margin
     * @param double $ The top margin
     * @access public
     */
    function PMA_PDF_setScale($scale = 1, $x_min = 0, $y_min = 0, $l_marg = -1, $t_marg = -1)
    {
        $this->scale = $scale;
        $this->x_min = $x_min;
        $this->y_min = $y_min;
        if ($this->l_marg != -1) {
            $this->l_marg = $l_marg;
        }
        if ($this->t_marg != -1) {
            $this->t_marg = $t_marg;
        }
    } // end of the "PMA_PDF_setScale" function
    /**
     * Outputs a scaled cell
     *
     * @param double $ The cell width
     * @param double $ The cell height
     * @param string $ The text to output
     * @param mixed $ Whether to add borders or not
     * @param integer $ Where to put the cursor once the output is done
     * @param string $ Align mode
     * @param integer $ Whether to fill the cell with a color or not
     * @access public
     * @see FPDF::Cell()
     */
    function PMA_PDF_cellScale($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
    {
        $h = $h / $this->scale;
        $w = $w / $this->scale;
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    } // end of the "PMA_PDF_cellScale" function
    /**
     * Draws a scaled line
     *
     * @param double $ The horizontal position of the starting point
     * @param double $ The vertical position of the starting point
     * @param double $ The horizontal position of the ending point
     * @param double $ The vertical position of the ending point
     * @access public
     * @see FPDF::Line()
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
     * Sets x and y scaled positions
     *
     * @param double $ The x position
     * @param double $ The y position
     * @access public
     * @see FPDF::SetXY()
     */
    function PMA_PDF_setXyScale($x, $y)
    {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $y = ($y - $this->y_min) / $this->scale + $this->t_marg;
        $this->SetXY($x, $y);
    } // end of the "PMA_PDF_setXyScale" function
    /**
     * Sets the X scaled positions
     *
     * @param double $ The x position
     * @access public
     * @see FPDF::SetX()
     */
    function PMA_PDF_setXScale($x)
    {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $this->SetX($x);
    } // end of the "PMA_PDF_setXScale" function
    /**
     * Sets the scaled font size
     *
     * @param double $ The font size (in points)
     * @access public
     * @see FPDF::SetFontSize()
     */
    function PMA_PDF_setFontSizeScale($size)
    {
        // Set font size in points
        $size = $size / $this->scale;
        $this->SetFontSize($size);
    } // end of the "PMA_PDF_setFontSizeScale" function
    /**
     * Sets the scaled line width
     *
     * @param double $ The line width
     * @access public
     * @see FPDF::SetLineWidth()
     */
    function PMA_PDF_setLineWidthScale($width)
    {
        $width = $width / $this->scale;
        $this->SetLineWidth($width);
    } // end of the "PMA_PDF_setLineWidthScale" function
    /**
     * Displays an error message
     *
     * @param string $ the error mesage
     * @global array    the PMA configuration array
     * @global integer  the current server id
     * @global string   the current language
     * @global string   the charset to convert to
     * @global string   the current database name
     * @global string   the current charset
     * @global string   the current text direction
     * @global string   a localized string
     * @global string   an other localized string
     * @access public
     */
    function PMA_PDF_die($error_message = '')
    {
        global $cfg;
        global $server, $lang, $convcharset, $db;
        global $charset, $text_dir, $strRunning, $strDatabase;

        require_once './libraries/header.inc.php';

        echo '<p><strong>PDF - ' . $GLOBALS['strError'] . '</strong></p>' . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";

        echo '<a href="db_structure.php?' . PMA_generate_common_url($db)
         . '">' . $GLOBALS['strBack'] . '</a>';
        echo "\n";

        require_once './libraries/footer.inc.php';
    } // end of the "PMA_PDF_die()" function
    /**
     * Aliases the "Error()" function from the FPDF class to the
     * "PMA_PDF_die()" one
     *
     * @param string $ the error mesage
     * @access public
     * @see PMA_PDF_die
     */
    function Error($error_message = '')
    {
        $this->PMA_PDF_die($error_message);
    } // end of the "Error()" method
    function Header()
    {
        // $datefmt
        // We only show this if we find something in the new pdf_pages table

        // This function must be named "Header" to work with the FPDF library
        global $cfgRelation, $db, $pdf_page_number, $with_doc;
        if ($with_doc) {
            $test_query = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
             . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
             . ' AND page_nr = \'' . $pdf_page_number . '\'';
            $test_rs = PMA_query_as_controluser($test_query);
            $pages = @PMA_DBI_fetch_assoc($test_rs);
            $this->SetFont('', 'B', 14);
            $this->Cell(0, 6, ucfirst($pages['page_descr']), 'B', 1, 'C');
            $this->SetFont('', '');
            $this->Ln();
        }
    }
    function Footer()
    {
        // This function must be named "Footer" to work with the FPDF library
        global $with_doc;
        if ($with_doc) {
            $this->SetY(-15);
            $this->SetFont('', '', 14);
            $this->Cell(0, 6, $GLOBALS['strPageNumber'] . ' ' . $this->PageNo() . '/{nb}', 'T', 0, 'C');
            $this->Cell(0, 6, PMA_localisedDate(), 0, 1, 'R');
            $this->SetY(20);
        }
    }
    function Bookmark($txt, $level = 0, $y = 0)
    {
        // Add a bookmark
        $this->Outlines[0][] = $level;
        $this->Outlines[1][] = $txt;
        $this->Outlines[2][] = $this->page;
        if ($y == -1) {
            $y = $this->GetY();
        }
        $this->Outlines[3][] = round($this->hPt - $y * $this->k, 2);
    }

    function _putbookmarks()
    {
        if (count($this->Outlines) > 0) {
            // Save object number
            $memo_n = $this->n;
            // Take the number of sub elements for an outline
            $nb_outlines = sizeof($this->Outlines[0]);
            $first_level = array();
            $parent = array();
            $parent[0] = 1;
            for ($i = 0; $i < $nb_outlines; $i++) {
                $level = $this->Outlines[0][$i];
                $kids = 0;
                $last = -1;
                $prev = -1;
                $next = -1;
                if ($i > 0) {
                    $cursor = $i-1;
                    // Take the previous outline in the same level
                    while ($this->Outlines[0][$cursor] > $level && $cursor > 0)
                    $cursor--;
                    if ($this->Outlines[0][$cursor] == $level) {
                        $prev = $cursor;
                    }
                }
                if ($i < $nb_outlines-1) {
                    $cursor = $i + 1;
                    while (isset($this->Outlines[0][$cursor]) && $this->Outlines[0][$cursor] > $level) {
                        // Take the immediate kid in level + 1
                        if ($this->Outlines[0][$cursor] == $level + 1) {
                            $kids++;
                            $last = $cursor;
                        }
                        $cursor++;
                    }
                    $cursor = $i + 1;
                    // Take the next outline in the same level
                    while ($this->Outlines[0][$cursor] > $level && ($cursor + 1 < sizeof($this->Outlines[0])))
                    $cursor++;
                    if ($this->Outlines[0][$cursor] == $level) {
                        $next = $cursor;
                    }
                }
                $this->_newobj();
                $parent[$level + 1] = $this->n;
                if ($level == 0) {
                    $first_level[] = $this->n;
                }
                $this->_out('<<');
                $this->_out('/Title (' . $this->Outlines[1][$i] . ')');
                $this->_out('/Parent ' . $parent[$level] . ' 0 R');
                if ($prev != -1) {
                    $this->_out('/Prev ' . ($memo_n + $prev + 1) . ' 0 R');
                }
                if ($next != -1) {
                    $this->_out('/Next ' . ($this->n + $next - $i) . ' 0 R');
                }
                $this->_out('/Dest [' . (1 + (2 * $this->Outlines[2][$i])) . ' 0 R /XYZ null ' . $this->Outlines[3][$i] . ' null]');
                if ($kids > 0) {
                    $this->_out('/First ' . ($this->n + 1) . ' 0 R');
                    $this->_out('/Last ' . ($this->n + $last - $i) . ' 0 R');
                    $this->_out('/Count -' . $kids);
                }
                $this->_out('>>');
                $this->_out('endobj');
            }
            // First page of outlines
            $this->_newobj();
            $this->def_outlines = $this->n;
            $this->_out('<<');
            $this->_out('/Type');
            $this->_out('/Outlines');
            $this->_out('/First ' . $first_level[0] . ' 0 R');
            $this->_out('/Last ' . $first_level[sizeof($first_level)-1] . ' 0 R');
            $this->_out('/Count ' . sizeof($first_level));
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    function _putresources()
    {
        parent::_putresources();
        $this->_putbookmarks();
    }

    function _putcatalog()
    {
        parent::_putcatalog();
        if (count($this->Outlines) > 0) {
            $this->_out('/Outlines ' . $this->def_outlines . ' 0 R');
            $this->_out('/PageMode /UseOutlines');
        }
    }
    function SetWidths($w)
    {
        // column widths
        $this->widths = $w;
    }

    function Row($data, $links)
    {
        // line height
        $nb = 0;
        $data_cnt = count($data);
        for ($i = 0;$i < $data_cnt;$i++)
        $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $il = $this->FontSize;
        $h = ($il + 1) * $nb;
        // page break if necessary
        $this->CheckPageBreak($h);
        // draw the cells
        $data_cnt = count($data);
        for ($i = 0;$i < $data_cnt;$i++) {
            $w = $this->widths[$i];
            // save current position
            $x = $this->GetX();
            $y = $this->GetY();
            // draw the border
            $this->Rect($x, $y, $w, $h);
            if (isset($links[$i])) {
                $this->Link($x, $y, $w, $h, $links[$i]);
            }
            // print text
            $this->MultiCell($w, $il + 1, $data[$i], 0, 'L');
            // go to right side
            $this->SetXY($x + $w, $y);
        }
        // go to line
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        // if height h overflows, manual page break
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt)
    {
        // compute number of lines used by a multicell of width w
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w-2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb-1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += isset($cw[ord($c)])?$cw[ord($c)]:0 ;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
} // end of the "PMA_PDF" class


/**
 * Draws tables schema
 *
 * @access private
 * @see PMA_RT
 * @package phpMyAdmin
 */
class PMA_RT_Table {
    /**
     * Defines private properties
     */
    var $nb_fiels;
    var $table_name;
    var $width = 0;
    var $height;
    var $fields = array();
    var $height_cell = 6;
    var $x, $y;
    var $primary = array();
    var $show_info = false;

    /**
     * Returns title of the current table,
     * title can have the dimensions of the table
     *
     * @access private
     */
    function getTitle()
    {
        return ($this->show_info ? sprintf('%.0f', $this->width) . 'x' . sprintf('%.0f', $this->height) : '') . ' ' . $this->table_name;
    } // end of the "getTitle" function
    /**
     * Sets the width of the table
     *
     * @param integer $ The font size
     * @global object    The current PDF document
     * @access private
     * @see PMA_PDF
     */
    function PMA_RT_Table_setWidth($ff)
    {
        global $pdf;

        foreach ($this->fields AS $field) {
            $this->width = max($this->width, $pdf->GetStringWidth($field));
        }
        $this->width += $pdf->GetStringWidth('  ');
        $pdf->SetFont($ff, 'B');
        // it is unknown what value must be added, because
        // table title is affected by the tabe width value
        while ($this->width < $pdf->GetStringWidth($this->getTitle())) {
            $this->width += 5;
        }
        $pdf->SetFont($ff, '');
    } // end of the "PMA_RT_Table_setWidth()" method
    /**
     * Sets the height of the table
     *
     * @access private
     */
    function PMA_RT_Table_setHeight()
    {
        $this->height = (count($this->fields) + 1) * $this->height_cell;
    } // end of the "PMA_RT_Table_setHeight()" method
    /**
     * Do draw the table
     *
     * @param integer $ The font size
     * @param boolean $ Whether to display color
     * @param integer $ The max. with among tables
     * @global object    The current PDF document
     * @access private
     * @see PMA_PDF
     */
    function PMA_RT_Table_draw($ff, $setcolor = 0)
    {
        global $pdf, $with_doc;

        $pdf->PMA_PDF_setXyScale($this->x, $this->y);
        $pdf->SetFont($ff, 'B');
        if ($setcolor) {
            $pdf->SetTextColor(200);
            $pdf->SetFillColor(0, 0, 128);
        }
        if ($with_doc) {
            $pdf->SetLink($pdf->PMA_links['RT'][$this->table_name]['-'], -1);
        } else {
            $pdf->PMA_links['doc'][$this->table_name]['-'] = '';
        }

        $pdf->PMA_PDF_cellScale($this->width, $this->height_cell, $this->getTitle(), 1, 1, 'C', $setcolor, $pdf->PMA_links['doc'][$this->table_name]['-']);
        $pdf->PMA_PDF_setXScale($this->x);
        $pdf->SetFont($ff, '');
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);

        foreach ($this->fields AS $field) {
            // loic1 : PHP3 fix
            // if (in_array($field, $this->primary)) {
            if ($setcolor) {
                if (in_array($field, $this->primary)) {
                    $pdf->SetFillColor(215, 121, 123);
                }
                if ($field == $this->displayfield) {
                    $pdf->SetFillColor(142, 159, 224);
                }
            }
            if ($with_doc) {
                $pdf->SetLink($pdf->PMA_links['RT'][$this->table_name][$field], -1);
            } else {
                $pdf->PMA_links['doc'][$this->table_name][$field] = '';
            }

            $pdf->PMA_PDF_cellScale($this->width, $this->height_cell, ' ' . $field, 1, 1, 'L', $setcolor, $pdf->PMA_links['doc'][$this->table_name][$field]);
            $pdf->PMA_PDF_setXScale($this->x);
            $pdf->SetFillColor(255);
        } // end while
        /*if ($pdf->PageNo() > 1) {
            $pdf->PMA_PDF_die($GLOBALS['strScaleFactorSmall']);
        } */
    } // end of the "PMA_RT_Table_draw()" method
    /**
     * The "PMA_RT_Table" constructor
     *
     * @param string $ The table name
     * @param integer $ The font size
     * @param integer $ The max. with among tables
     * @param boolean $ Whether to display keys or not
     * @param boolean $ Whether to display table position or not
     * @global object    The current PDF document
     * @global integer   The current page number (from the
     *                     $cfg['Servers'][$i]['table_coords'] table)
     * @global array     The relations settings
     * @global string    The current db name
     * @access private
     * @see PMA_PDF, PMA_RT_Table::PMA_RT_Table_setWidth,
          PMA_RT_Table::PMA_RT_Table_setHeight
     */
    function __construct($table_name, $ff, &$same_wide_width, $show_keys = false, $show_info = false)
    {
        global $pdf, $pdf_page_number, $cfgRelation, $db;

        $this->table_name = $table_name;
        $sql = 'DESCRIBE ' . PMA_backquote($table_name);
        $result = PMA_DBI_try_query($sql, null, PMA_DBI_QUERY_STORE);
        if (!$result || !PMA_DBI_num_rows($result)) {
            $pdf->PMA_PDF_die(sprintf($GLOBALS['strPdfInvalidTblName'], $table_name));
        }
        // load fields
        //check to see if it will load all fields or only the foreign keys
        if ($show_keys) {
            $indexes = PMA_Index::getFromTable($this->table_name, $db);
            $all_columns = array();
            foreach ($indexes as $index) {
            $all_columns = array_merge($all_columns, array_flip(array_keys($index->getColumns())));
            }
            $this->fields = array_keys($all_columns);
        } else {
            while ($row = PMA_DBI_fetch_row($result)) {
                $this->fields[] = $row[0];
            }
        }

        $this->show_info = $show_info;

        // height and width
        $this->PMA_RT_Table_setHeight();
        // setWidth must me after setHeight, because title
        // can include table height which changes table width
        $this->PMA_RT_Table_setWidth($ff);
        if ($same_wide_width < $this->width) {
            $same_wide_width = $this->width;
        }
        // x and y
        $sql = 'SELECT x, y FROM '
         . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
         . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
         . ' AND   table_name = \'' . PMA_sqlAddslashes($table_name) . '\''
         . ' AND   pdf_page_number = ' . $pdf_page_number;
        $result = PMA_query_as_controluser($sql, false, PMA_DBI_QUERY_STORE);

        if (!$result || !PMA_DBI_num_rows($result)) {
            $pdf->PMA_PDF_die(sprintf($GLOBALS['strConfigureTableCoord'], $table_name));
        }
        list($this->x, $this->y) = PMA_DBI_fetch_row($result);
        $this->x = (double) $this->x;
        $this->y = (double) $this->y;
        // displayfield
        $this->displayfield = PMA_getDisplayField($db, $table_name);
        // index
        $result = PMA_DBI_query('SHOW INDEX FROM ' . PMA_backquote($table_name) . ';', null, PMA_DBI_QUERY_STORE);
        if (PMA_DBI_num_rows($result) > 0) {
            while ($row = PMA_DBI_fetch_assoc($result)) {
                if ($row['Key_name'] == 'PRIMARY') {
                    $this->primary[] = $row['Column_name'];
                }
            }
        } // end if
    } // end of the "PMA_RT_Table()" method
} // end class "PMA_RT_Table"
/**
 * Draws relation links
 *
 * @access private
 * @see PMA_RT
 * @package phpMyAdmin
 */
class PMA_RT_Relation {
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
     * @param string $ The current table name
     * @param string $ The relation column name
     * @return array Arrows coordinates
     * @access private
     */
    function PMA_RT_Relation_getXy($table, $column)
    {
        $pos = array_search($column, $table->fields);
        // x_left, x_right, y
        return array($table->x, $table->x + + $table->width, $table->y + ($pos + 1.5) * $table->height_cell);
    } // end of the "PMA_RT_Relation_getXy()" method
    /**
     * Do draws relation links
     *
     * @param boolean $ Whether to use one color per relation or not
     * @param integer $ The id of the link to draw
     * @global object    The current PDF document
     * @access private
     * @see PMA_PDF
     */
    function PMA_RT_Relation_draw($change_color, $i)
    {
        global $pdf;

        if ($change_color) {
            $d = $i % 6;
            $j = ($i - $d) / 6;
            $j = $j % 4;
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
            $e = (1 - ($j - 1) / 6);
            $pdf->SetDrawColor($a * 255 * $e, $b * 255 * $e, $c * 255 * $e);
        } else {
            $pdf->SetDrawColor(0);
        } // end if... else...
        $pdf->PMA_PDF_setLineWidthScale(0.2);
        $pdf->PMA_PDF_lineScale($this->x_src, $this->y_src, $this->x_src + $this->src_dir * $this->w_tick, $this->y_src);
        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest, $this->x_dest, $this->y_dest);
        $pdf->PMA_PDF_setLineWidthScale(0.1);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick, $this->y_src, $this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest);
        // arrow
        $root2 = 2 * sqrt(2);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src + $this->w_tick / $root2);
        $pdf->PMA_PDF_lineScale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src - $this->w_tick / $root2);

        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest + $this->w_tick / $root2);
        $pdf->PMA_PDF_lineScale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest - $this->w_tick / $root2);
        $pdf->SetDrawColor(0);
    } // end of the "PMA_RT_Relation_draw()" method
    /**
     * The "PMA_RT_Relation" constructor
     *
     * @param string $ The master table name
     * @param string $ The relation field in the master table
     * @param string $ The foreign table name
     * @param string $ The relation field in the foreign table
     * @access private
     * @see PMA_RT_Relation::PMA_RT_Relation_getXy
     */
    function __construct($master_table, $master_field, $foreign_table, $foreign_field)
    {
        $src_pos = $this->PMA_RT_Relation_getXy($master_table, $master_field);
        $dest_pos = $this->PMA_RT_Relation_getXy($foreign_table, $foreign_field);
        $src_left = $src_pos[0] - $this->w_tick;
        $src_right = $src_pos[1] + $this->w_tick;
        $dest_left = $dest_pos[0] - $this->w_tick;
        $dest_right = $dest_pos[1] + $this->w_tick;

        $d1 = abs($src_left - $dest_left);
        $d2 = abs($src_right - $dest_left);
        $d3 = abs($src_left - $dest_right);
        $d4 = abs($src_right - $dest_right);
        $d = min($d1, $d2, $d3, $d4);

        if ($d == $d1) {
            $this->x_src = $src_pos[0];
            $this->src_dir = -1;
            $this->x_dest = $dest_pos[0];
            $this->dest_dir = -1;
        } elseif ($d == $d2) {
            $this->x_src = $src_pos[1];
            $this->src_dir = 1;
            $this->x_dest = $dest_pos[0];
            $this->dest_dir = -1;
        } elseif ($d == $d3) {
            $this->x_src = $src_pos[0];
            $this->src_dir = -1;
            $this->x_dest = $dest_pos[1];
            $this->dest_dir = 1;
        } else {
            $this->x_src = $src_pos[1];
            $this->src_dir = 1;
            $this->x_dest = $dest_pos[1];
            $this->dest_dir = 1;
        }
        $this->y_src = $src_pos[2];
        $this->y_dest = $dest_pos[2];
    } // end of the "PMA_RT_Relation()" method
} // end of the "PMA_RT_Relation" class
/**
 * Draws and send the database schema
 *
 * @access public
 * @see PMA_PDF
 * @package phpMyAdmin
 */
class PMA_RT {
    /**
     * Defines private properties
     */
    var $tables = array();
    var $relations = array();
    var $ff = PMA_PDF_FONT;
    var $x_max = 0;
    var $y_max = 0;
    var $scale;
    var $x_min = 100000;
    var $y_min = 100000;
    var $t_marg = 10;
    var $b_marg = 10;
    var $l_marg = 10;
    var $r_marg = 10;
    var $tablewidth;
    var $same_wide = 0;

    /**
     * Sets X and Y minimum and maximum for a table cell
     *
     * @param string $ The table name
     * @access private
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
     * @param string $ The master table name
     * @param string $ The relation field in the master table
     * @param string $ The foreign table name
     * @param string $ The relation field in the foreign table
     * @param boolean $ Whether to display table position or not
     * @access private
     * @see PMA_RT_setMinMax
     */
    function PMA_RT_addRelation($master_table, $master_field, $foreign_table, $foreign_field, $show_info)
    {
        if (!isset($this->tables[$master_table])) {
            $this->tables[$master_table] = new PMA_RT_Table($master_table, $this->ff, $this->tablewidth, false, $show_info);
            $this->PMA_RT_setMinMax($this->tables[$master_table]);
        }
        if (!isset($this->tables[$foreign_table])) {
            $this->tables[$foreign_table] = new PMA_RT_Table($foreign_table, $this->ff, $this->tablewidth, false, $show_info);
            $this->PMA_RT_setMinMax($this->tables[$foreign_table]);
        }
        $this->relations[] = new PMA_RT_Relation($this->tables[$master_table], $master_field, $this->tables[$foreign_table], $foreign_field);
    } // end of the "PMA_RT_addRelation()" method
    /**
     * Draws the grid
     *
     * @global object  the current PMA_PDF instance
     * @access private
     * @see PMA_PDF
     */
    function PMA_RT_strokeGrid()
    {
        global $pdf;

        $pdf->SetMargins(0, 0);
        $pdf->SetDrawColor(200, 200, 200);
        // Draws horizontal lines
        for ($l = 0; $l < 21; $l++) {
            $pdf->line(0, $l * 10, $pdf->getFh(), $l * 10);
            // Avoid duplicates
            if ($l > 0) {
                $pdf->SetXY(0, $l * 10);
                $label = (string) sprintf('%.0f', ($l * 10 - $this->t_marg) * $this->scale + $this->y_min);
                $pdf->Cell(5, 5, ' ' . $label);
            } // end if
        } // end for
        // Draws vertical lines
        for ($j = 0; $j < 30 ;$j++) {
            $pdf->line($j * 10, 0, $j * 10, $pdf->getFw());
            $pdf->SetXY($j * 10, 0);
            $label = (string) sprintf('%.0f', ($j * 10 - $this->l_marg) * $this->scale + $this->x_min);
            $pdf->Cell(5, 7, $label);
        } // end for
    } // end of the "PMA_RT_strokeGrid()" method
    /**
     * Draws relation arrows
     *
     * @param boolean $ Whether to use one color per relation or not
     * @access private
     * @see PMA_RT_Relation::PMA_RT_Relation_draw()
     */
    function PMA_RT_drawRelations($change_color)
    {
        $i = 0;
        foreach ($this->relations AS $relation) {
            $relation->PMA_RT_Relation_draw($change_color, $i);
            $i++;
        } // end while
    } // end of the "PMA_RT_drawRelations()" method
    /**
     * Draws tables
     *
     * @param boolean $ Whether to display table position or not
     * @access private
     * @see PMA_RT_Table::PMA_RT_Table_draw()
     */
    function PMA_RT_drawTables($draw_color = 0)
    {
        foreach ($this->tables AS $table) {
            $table->PMA_RT_Table_draw($this->ff, $draw_color);
        }
    } // end of the "PMA_RT_drawTables()" method
    /**
     * Ouputs the PDF document to a file
     *
     * @global object   The current PDF document
     * @global string   The current database name
     * @global integer  The current page number (from the
     *                    $cfg['Servers'][$i]['table_coords'] table)
     * @access private
     * @see PMA_PDF
     */
    function PMA_RT_showRt()
    {
        global $pdf, $db, $pdf_page_number, $cfgRelation;

        $pdf->SetFontSize(14);
        $pdf->SetLineWidth(0.2);
        $pdf->SetDisplayMode('fullpage');
        // Get the name of this pdfpage to use as filename (Mike Beck)
        $_name_sql = 'SELECT page_descr FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
         . ' WHERE page_nr = ' . $pdf_page_number;
        $_name_rs = PMA_query_as_controluser($_name_sql);
        if ($_name_rs) {
            $_name_row = PMA_DBI_fetch_row($_name_rs);
            $filename = $_name_row[0] . '.pdf';
        }
        // i don't know if there is a chance for this to happen, but rather be on the safe side:
        if (empty($filename)) {
            $filename = $pdf_page_number . '.pdf';
        }
        // $pdf->Output($db . '_' . $filename, TRUE);
        $pdf->Output($db . '_' . $filename, 'I'); // destination: Inline
    } // end of the "PMA_RT_showRt()" method
    /**
     * The "PMA_RT" constructor
     *
     * @param mixed $ The scaling factor
     * @param integer $ The page number to draw (from the
     *                    $cfg['Servers'][$i]['table_coords'] table)
     * @param boolean $ Whether to display table position or not
     * @param boolean $ Was originally whether to use one color per
     *                    relation or not, now enables/disables color
     *                    everywhere, due to some problems printing with color
     * @param boolean $ Whether to draw grids or not
     * @param boolean $ Whether all tables should have the same width or not
     * @param boolean $ Wheter to show all field or only the keys
     * @global object   The current PDF document
     * @global string   The current db name
     * @global array    The relations settings
     * @access private
     * @see PMA_PDF
     */
    function __construct($which_rel, $show_info = 0, $change_color = 0, $show_grid = 0, $all_tab_same_wide = 0, $orientation = 'L', $paper = 'A4', $show_keys = 0)
    {
        global $pdf, $db, $cfgRelation, $with_doc;

        $this->same_wide = $all_tab_same_wide;
        // Initializes a new document
        $pdf = new PMA_PDF('L', 'mm', $paper);
        $pdf->SetTitle(sprintf($GLOBALS['strPdfDbSchema'], $GLOBALS['db'], $which_rel));
        $pdf->setCMargin(0);
        $pdf->Open();
        $pdf->SetAuthor('phpMyAdmin ' . PMA_VERSION);
        $pdf->AliasNbPages();
        $pdf->AddFont('DejaVuSans', '', 'dejavusans.php');
        $pdf->AddFont('DejaVuSans', 'B', 'dejavusansb.php');
        $pdf->AddFont('DejaVuSerif', '', 'dejavuserif.php');
        $pdf->AddFont('DejaVuSerif', 'B', 'dejavuserifb.php');
        $this->ff = PMA_PDF_FONT;
        $pdf->SetFont($this->ff, '', 14);
        $pdf->SetAutoPageBreak('auto');
        // Gets tables on this page
        $tab_sql = 'SELECT table_name FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
         . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
         . ' AND pdf_page_number = ' . $which_rel;
        $tab_rs = PMA_query_as_controluser($tab_sql, null, PMA_DBI_QUERY_STORE);
        if (!$tab_rs || !PMA_DBI_num_rows($tab_rs) > 0) {
            $pdf->PMA_PDF_die($GLOBALS['strPdfNoTables']);
            // die('No tables');
        } while ($curr_table = @PMA_DBI_fetch_assoc($tab_rs)) {
            $alltables[] = PMA_sqlAddslashes($curr_table['table_name']);
            // $intable     = '\'' . implode('\', \'', $alltables) . '\'';
        }
        // make doc                    //
        if ($with_doc) {
            $pdf->SetAutoPageBreak('auto', 15);
            $pdf->setCMargin(1);
            PMA_RT_DOC($alltables);
            $pdf->SetAutoPageBreak('auto');
            $pdf->setCMargin(0);
        }

        $pdf->Addpage();

        if ($with_doc) {
            $pdf->SetLink($pdf->PMA_links['RT']['-'], -1);
            $pdf->Bookmark($GLOBALS['strRelationalSchema']);
            $pdf->SetAlias('{00}', $pdf->PageNo()) ;
            $this->t_marg = 18;
            $this->b_marg = 18;
        }

        /* snip */

        foreach ($alltables AS $table) {
            if (!isset($this->tables[$table])) {
                $this->tables[$table] = new PMA_RT_Table($table, $this->ff, $this->tablewidth, $show_keys, $show_info);
            }

            if ($this->same_wide) {
                $this->tables[$table]->width = $this->tablewidth;
            }
            $this->PMA_RT_setMinMax($this->tables[$table]);
        }
        // Defines the scale factor
        $this->scale = ceil(
            max(
                ($this->x_max - $this->x_min) / ($pdf->getFh() - $this->r_marg - $this->l_marg),
                ($this->y_max - $this->y_min) / ($pdf->getFw() - $this->t_marg - $this->b_marg))
             * 100) / 100;

        $pdf->PMA_PDF_setScale($this->scale, $this->x_min, $this->y_min, $this->l_marg, $this->t_marg);
        // Builds and save the PDF document
        $pdf->PMA_PDF_setLineWidthScale(0.1);

        if ($show_grid) {
            $pdf->SetFontSize(10);
            $this->PMA_RT_strokeGrid();
        }
        $pdf->PMA_PDF_setFontSizeScale(14);
        // $sql    = 'SELECT * FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
        // .   ' WHERE master_db   = \'' . PMA_sqlAddslashes($db) . '\' '
        // .   ' AND foreign_db    = \'' . PMA_sqlAddslashes($db) . '\' '
        // .   ' AND master_table  IN (' . $intable . ')'
        // .   ' AND foreign_table IN (' . $intable . ')';
        // $result =  PMA_query_as_controluser($sql);

        // lem9:
        // previous logic was checking master tables and foreign tables
        // but I think that looping on every table of the pdf page as a master
        // and finding its foreigns is OK (then we can support innodb)
        $seen_a_relation = false;
        foreach ($alltables AS $one_table) {
            $exist_rel = PMA_getForeigners($db, $one_table, '', 'both');
            if ($exist_rel) {
                $seen_a_relation = true;
                foreach ($exist_rel AS $master_field => $rel) {
                    // put the foreign table on the schema only if selected
                    // by the user
                    // (do not use array_search() because we would have to
                    // to do a === FALSE and this is not PHP3 compatible)
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->PMA_RT_addRelation($one_table, $master_field, $rel['foreign_table'], $rel['foreign_field'], $show_info);
                    }
                } // end while
            } // end if
        } // end while
        // loic1: also show tables without relations
        // $norelations     = TRUE;
        // if ($result && PMA_DBI_num_rows($result) > 0) {
        // $norelations = FALSE;
        // while ($row = PMA_DBI_fetch_assoc($result)) {
        // $this->PMA_RT_addRelation($row['master_table'], $row['master_field'], $row['foreign_table'], $row['foreign_field']);
        // }
        // }
        // if ($norelations == FALSE) {
        if ($seen_a_relation) {
            $this->PMA_RT_drawRelations($change_color);
        }

        $this->PMA_RT_drawTables($change_color);

        $this->PMA_RT_showRt();
    } // end of the "PMA_RT()" method
} // end of the "PMA_RT" class

function PMA_RT_DOC($alltables)
{
    global $db, $pdf, $orientation, $paper;
    // TOC
    $pdf->addpage($GLOBALS['orientation']);
    $pdf->Cell(0, 9, $GLOBALS['strTableOfContents'], 1, 0, 'C');
    $pdf->Ln(15);
    $i = 1;
    foreach ($alltables AS $table) {
        $pdf->PMA_links['doc'][$table]['-'] = $pdf->AddLink();
        $pdf->SetX(10);
        // $pdf->Ln(1);
        $pdf->Cell(0, 6, $GLOBALS['strPageNumber'] . ' {' . sprintf("%02d", $i + 1) . '}', 0, 0, 'R', 0, $pdf->PMA_links['doc'][$table]['-']);
        $pdf->SetX(10);
        $pdf->Cell(0, 6, $i . ' ' . $table, 0, 1, 'L', 0, $pdf->PMA_links['doc'][$table]['-']);
        // $pdf->Ln(1);
        $result = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table) . ';');
        while ($row = PMA_DBI_fetch_assoc($result)) {
            $pdf->SetX(20);
            $field_name = $row['Field'];
            $pdf->PMA_links['doc'][$table][$field_name] = $pdf->AddLink();
            // $pdf->Cell(0, 6, $field_name,0,1,'L',0, $pdf->PMA_links['doc'][$table][$field_name]);
        }
        $lasttable = $table;
        $i++;
    }
    $pdf->PMA_links['RT']['-'] = $pdf->AddLink();
    $pdf->SetX(10);
    $pdf->Cell(0, 6, $GLOBALS['strPageNumber'] . ' {' . sprintf("%02d", $i + 1) . '}', 0, 0, 'R', 0, $pdf->PMA_links['doc'][$lasttable]['-']);
    $pdf->SetX(10);
    $pdf->Cell(0, 6, $i + 1 . ' ' . $GLOBALS['strRelationalSchema'], 0, 1, 'L', 0, $pdf->PMA_links['RT']['-']);
    $z = 0;
    foreach ($alltables AS $table) {
        $z++;
        $pdf->addpage($GLOBALS['orientation']);
        $pdf->Bookmark($table);
        $pdf->SetAlias('{' . sprintf("%02d", $z) . '}', $pdf->PageNo()) ;
        $pdf->PMA_links['RT'][$table]['-'] = $pdf->AddLink();
        $pdf->SetLink($pdf->PMA_links['doc'][$table]['-'], -1);
        $pdf->SetFont('', 'B', 18);
        $pdf->Cell(0, 8, $z . ' ' . $table, 1, 1, 'C', 0, $pdf->PMA_links['RT'][$table]['-']);
        $pdf->SetFont('', '', 8);
        $pdf->ln();

        $cfgRelation = PMA_getRelationsParam();
        $comments = PMA_getComments($db, $table);
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);
        }

        /**
         * Gets table informations
         */
        $showtable    = PMA_Table::sGetStatusInfo($db, $table);
        $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
        $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');
        $create_time  = (isset($showtable['Create_time']) ? PMA_localisedDate(strtotime($showtable['Create_time'])) : '');
        $update_time  = (isset($showtable['Update_time']) ? PMA_localisedDate(strtotime($showtable['Update_time'])) : '');
        $check_time   = (isset($showtable['Check_time']) ? PMA_localisedDate(strtotime($showtable['Check_time'])) : '');

        /**
         * Gets table keys and retains them
         */
        $result = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($table) . ';');
        $primary = '';
        $indexes = array();
        $lastIndex = '';
        $indexes_info = array();
        $indexes_data = array();
        $pk_array = array(); // will be use to emphasis prim. keys in the table
        // view
        while ($row = PMA_DBI_fetch_assoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
                $pk_array[$row['Column_name']] = 1;
            }
            // Retains keys informations
            if ($row['Key_name'] != $lastIndex) {
                $indexes[] = $row['Key_name'];
                $lastIndex = $row['Key_name'];
            }
            $indexes_info[$row['Key_name']]['Sequences'][] = $row['Seq_in_index'];
            $indexes_info[$row['Key_name']]['Non_unique'] = $row['Non_unique'];
            if (isset($row['Cardinality'])) {
                $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
            }
            // I don't know what does following column mean....
            // $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];
            $indexes_info[$row['Key_name']]['Comment'] = $row['Comment'];

            $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name'] = $row['Column_name'];
            if (isset($row['Sub_part'])) {
                $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
            }
        } // end while
        if ($result) {
            PMA_DBI_free_result($result);
        }

        /**
         * Gets fields properties
         */
        $result = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table) . ';', null, PMA_DBI_QUERY_STORE);
        $fields_cnt = PMA_DBI_num_rows($result);
        // Check if we can use Relations (Mike Beck)
        if (!empty($cfgRelation['relation'])) {
            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = PMA_getForeigners($db, $table);

            if (count($res_rel) > 0) {
                $have_rel = true;
            } else {
                $have_rel = false;
            }
        } else {
            $have_rel = false;
        } // end if
        /**
         * Displays the comments of the table if MySQL >= 3.23
         */

        $break = false;
        if (!empty($show_comment)) {
            $pdf->Cell(0, 3, $GLOBALS['strTableComments'] . ' : ' . $show_comment, 0, 1);
            $break = true;
        }

        if (!empty($create_time)) {
            $pdf->Cell(0, 3, $GLOBALS['strStatCreateTime'] . ': ' . $create_time, 0, 1);
            $break = true;
        }

        if (!empty($update_time)) {
            $pdf->Cell(0, 3, $GLOBALS['strStatUpdateTime'] . ': ' . $update_time, 0, 1);
            $break = true;
        }

        if (!empty($check_time)) {
            $pdf->Cell(0, 3, $GLOBALS['strStatCheckTime'] . ': ' . $check_time, 0, 1);
            $break = true;
        }

        if ($break == true) {
            $pdf->Cell(0, 3, '', 0, 1);
            $pdf->Ln();
        }

        $pdf->SetFont('', 'B');
        if (isset($orientation) && $orientation == 'L') {
            $pdf->Cell(25, 8, ucfirst($GLOBALS['strField']), 1, 0, 'C');
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strType']), 1, 0, 'C');
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strAttr']), 1, 0, 'C');
            $pdf->Cell(10, 8, ucfirst($GLOBALS['strNull']), 1, 0, 'C');
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strDefault']), 1, 0, 'C');
            $pdf->Cell(25, 8, ucfirst($GLOBALS['strExtra']), 1, 0, 'C');
            $pdf->Cell(45, 8, ucfirst($GLOBALS['strLinksTo']), 1, 0, 'C');

            if ($paper == 'A4') {
                $comments_width = 67;
            } else {
                // this is really intended for 'letter'
                /**
                 * @todo find optimal width for all formats
                 */
                $comments_width = 50;
            }
            $pdf->Cell($comments_width, 8, ucfirst($GLOBALS['strComments']), 1, 0, 'C');
            $pdf->Cell(45, 8, 'MIME', 1, 1, 'C');
            $pdf->SetWidths(array(25, 20, 20, 10, 20, 25, 45, $comments_width, 45));
        } else {
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strField']), 1, 0, 'C');
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strType']), 1, 0, 'C');
            $pdf->Cell(20, 8, ucfirst($GLOBALS['strAttr']), 1, 0, 'C');
            $pdf->Cell(10, 8, ucfirst($GLOBALS['strNull']), 1, 0, 'C');
            $pdf->Cell(15, 8, ucfirst($GLOBALS['strDefault']), 1, 0, 'C');
            $pdf->Cell(15, 8, ucfirst($GLOBALS['strExtra']), 1, 0, 'C');
            $pdf->Cell(30, 8, ucfirst($GLOBALS['strLinksTo']), 1, 0, 'C');
            $pdf->Cell(30, 8, ucfirst($GLOBALS['strComments']), 1, 0, 'C');
            $pdf->Cell(30, 8, 'MIME', 1, 1, 'C');
            $pdf->SetWidths(array(20, 20, 20, 10, 15, 15, 30, 30, 30));
        }
        $pdf->SetFont('', '');

        while ($row = PMA_DBI_fetch_assoc($result)) {
            $type = $row['Type'];
            // reformat mysql query output - staybyte - 9. June 2001
            // loic1: set or enum types: slashes single quotes inside options
            if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
                $tmp[2] = substr(preg_replace("@([^,])''@", "\\1\\'", ',' . $tmp[2]), 1);
                $type = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
                $type_nowrap = '';

                $binary = 0;
                $unsigned = 0;
                $zerofill = 0;
            } else {
                $type_nowrap = ' nowrap="nowrap"';
                $type = preg_replace('@BINARY@i', '', $type);
                $type = preg_replace('@ZEROFILL@i', '', $type);
                $type = preg_replace('@UNSIGNED@i', '', $type);
                if (empty($type)) {
                    $type = '&nbsp;';
                }

                $binary = stristr($row['Type'], 'BINARY');
                $unsigned = stristr($row['Type'], 'UNSIGNED');
                $zerofill = stristr($row['Type'], 'ZEROFILL');
            }
            $strAttribute = ' ';
            if ($binary) {
                $strAttribute = 'BINARY';
            }
            if ($unsigned) {
                $strAttribute = 'UNSIGNED';
            }
            if ($zerofill) {
                $strAttribute = 'UNSIGNED ZEROFILL';
            }
            if (!isset($row['Default'])) {
                if ($row['Null'] != '' && $row['Null'] != 'NO') {
                    $row['Default'] = 'NULL';
                }
            }
            $field_name = $row['Field'];
            // $pdf->Ln();
            $pdf->PMA_links['RT'][$table][$field_name] = $pdf->AddLink();
            $pdf->Bookmark($field_name, 1, -1);
            $pdf->SetLink($pdf->PMA_links['doc'][$table][$field_name], -1);
            $pdf_row = array($field_name,
                $type,
                $strAttribute,
                ($row['Null'] == '' || $row['Null'] == 'NO') ? $GLOBALS['strNo'] : $GLOBALS['strYes'],
                ((isset($row['Default'])) ? $row['Default'] : ''),
                $row['Extra'],
                ((isset($res_rel[$field_name])) ? $res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field'] : ''),
                ((isset($comments[$field_name])) ? $comments[$field_name] : ''),
                ((isset($mime_map) && isset($mime_map[$field_name])) ? str_replace('_', '/', $mime_map[$field_name]['mimetype']) : '')
                );
            $links[0] = $pdf->PMA_links['RT'][$table][$field_name];
            if (isset($res_rel[$field_name]['foreign_table']) AND
                    isset($res_rel[$field_name]['foreign_field']) AND
                    isset($pdf->PMA_links['doc'][$res_rel[$field_name]['foreign_table']][$res_rel[$field_name]['foreign_field']])
                    )
            {
                $links[6] = $pdf->PMA_links['doc'][$res_rel[$field_name]['foreign_table']][$res_rel[$field_name]['foreign_field']];
            } else {
                unset($links[6]);
            }
            $pdf->Row($pdf_row, $links);

            /*$pdf->Cell(20, 8, $field_name, 1, 0, 'L', 0, $pdf->PMA_links['RT'][$table][$field_name]);
                //echo '    ' . $field_name . '&nbsp;' . "\n";
            }
        $pdf->Cell(20, 8, $type, 1, 0, 'L');
        $pdf->Cell(20, 8, $strAttribute, 1, 0, 'L');
        $pdf->Cell(15, 8, , 1, 0, 'L');
        $pdf->Cell(15, 8, ((isset($row['Default'])) ?  $row['Default'] : ''),1,0,'L');
        $pdf->Cell(15, 8, $row['Extra'], 1, 0, 'L');
           if ($have_rel) {
                if (isset($res_rel[$field_name])) {
                    $pdf->Cell(30, 8, $res_rel[$field_name]['foreign_table'] . ' -> ' . $res_rel[$field_name]['foreign_field'],1,0,'L');
                }
            }
            if ($cfgRelation['commwork']) {
                if (isset($comments[$field_name])) {
                    $pdf->Cell(0, 8, $comments[$field_name], 1, 0, 'L');
                }
            } */
        } // end while
        $pdf->SetFont('', '', 14);
        PMA_DBI_free_result($result);
    } //end each
} // end function PMA_RT_DOC

/**
 * Main logic
 */
if (!isset($pdf_page_number)) {
    $pdf_page_number = 1;
}

$show_grid              = (isset($show_grid) && $show_grid == 'on') ? 1 : 0;
$show_color             = (isset($show_color) && $show_color == 'on') ? 1 : 0;
$show_table_dimension   = (isset($show_table_dimension) && $show_table_dimension == 'on') ? 1 : 0;
$all_tab_same_wide      = (isset($all_tab_same_wide) && $all_tab_same_wide == 'on') ? 1 : 0;
$with_doc               = (isset($with_doc) && $with_doc == 'on') ? 1 : 0;
$orientation            = (isset($orientation) && $orientation == 'P') ? 'P' : 'L';
$paper                  = isset($paper) ? $paper : 'A4';
$show_keys              = (isset($show_keys) && $show_keys == 'on') ? 1 : 0;
PMA_DBI_select_db($db);

$rt = new PMA_RT($pdf_page_number, $show_table_dimension, $show_color, $show_grid, $all_tab_same_wide, $orientation, $paper, $show_keys);

?>
