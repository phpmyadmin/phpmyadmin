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


/**
 * Gets the "fpdf" libraries and defines the pdf font path
 */
require('./fpdf/fpdf.php3');
define('FPDF_FONTPATH', './fpdf/font/');


/**
 * Extends the "fpdf" class and prepares the work
 */
class PDF extends FPDF {
    var $x_min;
    var $y_min;
    var $l_marg;
    var $t_marg;
    var $scale ;

    function SetScale($scale = 1, $x_min = 0, $y_min = 0, $l_marg, $t_marg) {
        $this->scale  = $scale;
        $this->x_min  = $x_min;
        $this->y_min  = $y_min;
        $this->l_marg = $l_marg;
        $this->t_marg = $t_marg;
    }

    function cell_scale($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0) {
        $h = $h/$this->scale;
        $w = $w/$this->scale;
        $this->Cell($w, $h, $txt, $border, $ln, $align, $fill);
    }

    function Line_scale($x1, $y1, $x2, $y2) {
        $x1 = ($x1 - $this->x_min) / $this->scale + $this->l_marg;
        $y1 = ($y1 - $this->y_min) / $this->scale + $this->t_marg;
        $x2 = ($x2 - $this->x_min) / $this->scale + $this->l_marg;
        $y2 = ($y2 - $this->y_min) / $this->scale + $this->t_marg;
        $this->Line($x1, $y1, $x2, $y2);
    }

    function SetXY_scale($x, $y) {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $y = ($y - $this->y_min) / $this->scale + $this->t_marg;
        $this->SetXY($x, $y);
    }

    function SetX_scale($x) {
        $x = ($x - $this->x_min) / $this->scale + $this->l_marg;
        $this->SetX($x);
    }

    function SetFontSize_scale($size) {
        // Set font size in points
        $size = $size / $this->scale;
        $this->SetFontSize($size);
    }

    function SetLineWidth_scale($width) {
        $width = $width / $this->scale;
        $this->SetLineWidth($width);
    }
} // end class PDF

class RT {
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

    function RT($scale, $which_rel, $show_info = 0, $change_color = 0 , $show_grid = 0) {
        global $pdf;

        $pdf          = new PDF('L');
        $pdf->cMargin = 0;
        $pdf->Open();
        $pdf->AliasNbPages();
        $pdf->Addpage();
        $pdf->SetFont($this->ff, '', 14);
        $pdf->SetAutoPageBreak('auto');

        $sql    = 'SELECT * FROM '
                . PMA_backquote($GLOBALS['cfg']['Server']['relation'])
                . ' WHERE pdf_page_number = ' . $which_rel;
        $result = mysql_query($sql);
        if (!$result || !mysql_num_rows($result)) {
            // TODO: defines the "back url"
//            PMA_mysqlDie($GLOBALS['strInvalidPageNum'], '', FALSE, '');
            PMA_mysqlDie('strInvalidPageNum', '', FALSE, '');
        }
        while ($row = mysql_fetch_array($result)) {
            $this->add_relation($row['master_table'] , $row['master_field'], $row['foreign_table'], $row['foreign_field']);
        }

        if ($scale == 'auto') {
            $this->scale = ceil(max(($this->x_max - $this->x_min) / (297 - $this->r_marg - $this->l_marg), ($this->y_max - $this->y_min) / (210 - $this->t_marg - $this->b_marg)) * 100) / 100;
            $pdf->SetScale($this->scale, $this->x_min, $this->y_min, $this->l_marg, $this->t_marg);
        }
        else {
            $this->scale = $scale;
            $pdf->SetScale($scale);
        }
        $pdf->SetLineWidth_scale(0.1);

        $pdf->SetFontSize_scale(14);
        if ($show_grid) {
            $this->Stroke_grid();
        }
        $this->draw_relations($change_color);
        $this->draw_tables($show_info);

        $this->showRT();
    }

    function add_relation($master_table , $master_field,  $foreign_table, $foreign_field) {
        if (!isset($this->tables[$master_table])) {
            $this->tables[$master_table] = new rt_table($master_table, $this->ff);
            $this->set_min_max($this->tables[$master_table]);
        }
        if (!isset($this->tables[$foreign_table])) {
            $this->tables[$foreign_table] = new rt_table($foreign_table, $this->ff);
            $this->set_min_max($this->tables[$foreign_table]);

        }
        $this->relations[] = new rt_relation($this->tables[$master_table], $master_field, $this->tables[$foreign_table], $foreign_field);
    }

    function set_min_max($table) {
        $this->x_max = max($this->x_max, $table->x + $table->width);
        $this->y_max = max($this->y_max, $table->y + $table->height);
        $this->x_min = min($this->x_min, $table->x);
        $this->y_min = min($this->y_min, $table->y);

    }

    function draw_tables($show_info) {
        foreach ($this->tables as $table) {
            $table->draw($show_info, $this->ff);
        }
    }

    function draw_relations($change_color) {
        $i = 0;
        foreach ($this->relations as $relation) {
            $relation->draw($change_color, $i);
            $i++;
        }
    }

    function Stroke_grid() {
        global $pdf;
        $pdf->SetMargins(0, 0);
        $pdf->SetDrawColor(200, 200, 200);

        for ($l = 0; $l < 21; $l++) {
            // horizontal line
            $pdf->line(0, $l * 10, 297, $l * 10);
            $pdf->SetXY(0, $l * 10);
            $pdf->cell(5, 5, round(($l * 10 - $this->t_marg) * $this->scale + $this->y_min));
        }

        for ($j = 0; $j < 30 ;$j++) {
            // vertical line
            $pdf->line($j * 10, 0, $j * 10, 210);
            $pdf->SetXY($j * 10, 0);
            $pdf->Cell(5, 7, round(($j * 10 - $this->l_marg) * $this->scale + $this->x_min));
        }
    }

    function showRT() {
        global $pdf, $db, $pdf_page_number;
        $pdf->SetDisplayMode('fullpage');
        $pdf->Output($db . '_' . $pdf_page_number . '.pdf', TRUE);
    }
} // end class RT

class rt_relation {
    var $x_src, $y_src;
    var $src_dir ;
    var $dest_dir;
    var $x_dest, $y_dest;
    var $w_tick = 5;

    function rt_relation($master_table, $master_field,  $foreign_table, $foreign_field) {
        $src_pos    = $this->get_xy($master_table , $master_field);
        $dest_pos   = $this->get_xy($foreign_table, $foreign_field);
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
    }

    function get_xy($table, $column) {
        $pos = array_search($column, $table->fields);
                 //x_left, x_right ,y
        return array($table->x, $table->x + $table->width, $table->y + ($pos + 1.5) * $table->height_cell);

    }

    function draw($change_color, $i) {
        global $pdf;

        if ($change_color){
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
            $pdf->SetDrawColor($a * 255 * $e, $b * 255 * $e, $c * 255 * $e);       }
        else {
            $pdf->SetDrawColor(0);
        }

        $pdf->SetLineWidth_scale(0.2);
        $pdf->Line_scale($this->x_src, $this->y_src, $this->x_src + $this->src_dir * $this->w_tick, $this->y_src);
        $pdf->Line_scale($this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest, $this->x_dest, $this->y_dest);
        $pdf->SetLineWidth_scale(0.1);
        $pdf->Line_scale($this->x_src + $this->src_dir * $this->w_tick, $this->y_src, $this->x_dest + $this->dest_dir * $this->w_tick, $this->y_dest);

        //arrow
        $root2 = 2*sqrt(2);
        $pdf->Line_scale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src + $this->w_tick / $root2);
        $pdf->Line_scale($this->x_src + $this->src_dir * $this->w_tick * 0.75, $this->y_src, $this->x_src + $this->src_dir * (0.75 - 1 / $root2) * $this->w_tick, $this->y_src - $this->w_tick / $root2);

        $pdf->Line_scale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest + $this->w_tick / $root2);
        $pdf->Line_scale($this->x_dest + $this->dest_dir * $this->w_tick / 2, $this->y_dest, $this->x_dest + $this->dest_dir * (0.5 + 1 / $root2) * $this->w_tick, $this->y_dest - $this->w_tick / $root2);
        $pdf->SetDrawColor(0);
    }
} // end class rt_relation

class rt_table {
    var $nb_fiels;
    var $table_name;
    var $width = 0;
    var $height;
    var $fields      = array();
    var $height_cell = 6;
    var $x, $y;

    function rt_table($table_name, $ff) {
        $this->table_name = $table_name;
        $sql              = 'DESCRIBE ' .  PMA_backquote($table_name);
        $result           = mysql_query($sql);

        // load fields
        while ($row = mysql_fetch_array($result)) {
            $this->fields[] = $row[0];
        }

        //height and width
        $this->set_width($ff);
        $this->set_height();

        //x and y
        $sql    = 'SELECT x, y FROM '
                . PMA_backquote($GLOBALS['cfg']['Server']['pdf_table_position'])
                . ' WHERE table_name = \'' . PMA_sqlAddslashes($table_name) . '\'';
        $result = mysql_query($sql);
        if (!$result || !mysql_num_rows($result)) {
            // TODO: defines the "back url"
            PMA_mysqlDie(sprintf($GLOBALS['strConfigureTableCoord'], $table_name), '', FALSE, '');
        }
        list($this->x, $this->y) = mysql_fetch_array($result);
    }

    function set_width($ff) {
        global $pdf;

        foreach ($this->fields as $field ) {
            $this->width = max($this->width, $pdf->GetStringWidth($field));
        }
        $this->width += $pdf->GetStringWidth('  ');
        $pdf->SetFont($ff, 'B');
        $this->width = max($this->width, $pdf->GetStringWidth('  ' . $this->table_name));
        $pdf->SetFont($ff, '');
    }

    function set_height() {
        $this->height = (sizeof($this->fields) + 1) * $this->height_cell;
    }

    function draw($show_info, $ff) {
        global $pdf;
        $pdf->SetXY_scale($this->x, $this->y);
        $pdf->SetFont($ff, 'B');
        $pdf->SetTextColor(200);
        $pdf->SetFillColor(0, 0, 128);
        if ($show_info){
            $pdf->Cell_scale($this->width, $this->height_cell, round($this->width) . 'x' . round($this->height) . ' ' . $this->table_name , 1, 1, 'C', 1);
        } else {
            $pdf->Cell_scale($this->width, $this->height_cell, $this->table_name, 1, 1, 'C', 1);
        }
        $pdf->SetX_scale($this->x);
        $pdf->SetFont($ff, '');
        $pdf->SetTextColor(0);
        $pdf->SetFillColor(255);

        foreach ($this->fields as $field) {
            $pdf->Cell_scale($this->width, $this->height_cell, ' ' . $field, 1, 1, 'L', 1);
            $pdf->SetX_scale($this->x);
        }
        if ($pdf->PageNo() > 1) {
            // TODO: defines the "back url"
            PMA_mysqlDie($GLOBALS['strScaleFactorSmall'], '', FALSE, '');
        }
    }
} // end class rt_table


/**
 * Main logic
 */
if (!isset($pdf_page_number)) {
    $pdf_page_number  = 1;
}
$show_grid            = (isset($show_grid) && $show_grid == 'on') ? 1 : 0;
$show_color           = (isset($show_color) && $show_color == 'on') ? 1 : 0;
$show_table_dimension = (isset($show_table_dimension) && $show_table_dimension == 'on') ? 1 : 0;

mysql_select_db($db);

$rt  = new RT('auto', $pdf_page_number, $show_table_dimension, $show_color, $show_grid);
?>
