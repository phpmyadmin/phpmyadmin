<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/****************************************************************************
* Software : FPDF                                                           *
* Version :  1.51                                                           *
* Date :     2002/08/03                                                     *
* Author :   Olivier PLATHEY                                                *
* Website :  http://www.fpdf.org                                            *
* Licence :  Freeware                                                       *
*                                                                           *
* You are entitled to modify this soft as you want to.                      *
****************************************************************************/


$FPDF_version = (string) '1.51';


/**
 * The FPDF class
 */
class FPDF
{
    /**
     * Defines private properties
     */
    var $page;               // current page number
    var $n;                  // current object number
    var $offsets;            // array of object offsets
    var $buffer;             // buffer holding in-memory PDF
    var $pages;              // array containing pages
    var $state;              // current document state
    var $compress;           // compression flag
    var $DefOrientation;     // default orientation
    var $CurOrientation;     // current orientation
    var $OrientationChanges; // array indicating orientation changes
    var $fwPt, $fhPt;        // dimensions of page format in points
    var $fw, $fh;            // dimensions of page format in user unit
    var $wPt, $hPt;          // current dimensions of page in points
    var $k;                  // scale factor (number of points in user unit)
    var $w, $h;              // current dimensions of page in user unit
    var $lMargin;            // left margin
    var $tMargin;            // top margin
    var $rMargin;            // right margin
    var $bMargin;            // page break margin
    var $cMargin;            // cell margin
    var $x, $y;              // current position in user unit for cell positionning
    var $lasth;              // height of last cell printed
    var $LineWidth;          // line width in user unit
    var $CoreFonts;          // array of standard font names
    var $fonts;              // array of used fonts
    var $FontFiles;          // array of font files
    var $diffs;              // array of encoding differences
    var $images;             // array of used images
    var $PageLinks;          // array of links in pages
    var $links;              // array of internal links
    var $FontFamily;         // current font family
    var $FontStyle;          // current font style
    var $CurrentFont;        // current font info
    var $FontSizePt;         // current font size in points
    var $FontSize;           // current font size in user unit
    var $DrawColor;          // commands for drawing color
    var $FillColor;          // commands for filling color
    var $TextColor;          // commands for text color
    var $ColorFlag;          // indicates whether fill and text colors are different
    var $ws;                 // word spacing
    var $underline;          // whether underline is current state or not
    var $AutoPageBreak;      // automatic page breaking
    var $PageBreakTrigger;   // threshold used to trigger page breaks
    var $InFooter;           // flag set when processing footer
    var $ZoomMode;           // zoom display mode
    var $LayoutMode;         // layout display mode
    var $title;              // title
    var $subject;            // subject
    var $author;             // author
    var $keywords;           // keywords
    var $creator;            // creator
    var $AliasNbPages;       // alias for total number of pages



    /**************************************************************************
    *                                                                         *
    *      Public methods below are used by some private ones. Then they      *
    *      are placed at the top of the class.                                *
    *                                                                         *
    **************************************************************************/

    /**
     * Gets the width of a string in the current font
     *
     * @param   string   The string to check
     *
     * @return  double  The string width
     *
     * @access  public
     */
    function GetStringWidth($s)
    {
        $s     = (string) $s;
        // loic1: PHP3 compatibility
        // $cw    = &$this->CurrentFont['cw'];
        $w     = 0;
        $l     = strlen($s);
        for ($i = 0; $i < $l; $i++) {
            // $w += $cw[$s[$i]];
            $w += $this->CurrentFont['cw'][$s[$i]];
        } // end for

        return $w * $this->FontSize / 1000;
    } // end of the "GetStringWidth()" method


    /**
     * Displays an error message then exists
     *
     * @param  string  The error message
     *
     * @access public
     */
    function Error($msg)
    {
        die('<b>FPDF error: </b>' . $msg);
    } // end of the "Error()" method



    /**************************************************************************
    *                                                                         *
    *                             Private methods                             *
    *                                                                         *
    **************************************************************************/

    /**
     * Adds a line to the document
     *
     * @param   string   The string to add
     *
     * @access  private
     */
    function _out($s)
    {
        if ($this->state == 2) {
            $this->pages[$this->page] .= $s . "\n";
        } else {
            $this->buffer             .= $s . "\n";
        }
    } // end of the "_out()" method


    /**
     * Starts a new object
     *
     * @access private
     */
    function _newobj()
    {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_out($this->n . ' 0 obj');
    } // end of the "_newobj()" method


    /**
     * Adds a "\" before "\", "(" and ")" characters
     *
     * @param   string   The string to slash
     *
     * @return  integer  The slashed string
     *
     * @access  private
     */
    function _escape($s)
    {
        return str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $s)));
    } // end of the "_escape()" method


    /**
     * Formats a text stringrs
     *
     * @param   string   The string to format
     *
     * @return  integer  The formatted string
     *
     * @access  private
     *
     * @see     _escape()
     */
    function _textstring($s)
    {
        return '(' . $this->_escape($s) . ')';
    } // end of the "_textstring()" method


    /**
     * Outputs a stream
     *
     * @param   string   The stream to ouput
     *
     * @access  private
     *
     * @see     _out()
     */
    function _putstream($s)
    {
        $this->_out('stream');
        $this->_out($s);
        $this->_out('endstream');
    } // end of the "_putstream()" method


    /**
     * Starts document
     *
     * @access private
     */
    function _begindoc()
    {
        $this->state = 1;
        $this->_out('%PDF-1.3');
    } // end of the "_begindoc()" method


    /**
     * Puts pages
     *
     * @access private
     */
    function _putpages()
    {
        $nb = $this->page;

        if (!empty($this->AliasNbPages)) {
            // Replaces number of pages
            for ($n = 1; $n <= $nb; $n++) {
                $this->pages[$n] = str_replace($this->AliasNbPages, $nb, $this->pages[$n]);
            }
        }
        if ($this->DefOrientation == 'P') {
            $wPt = $this->fwPt;
            $hPt = $this->fhPt;
        } else {
            $wPt = $this->fhPt;
            $hPt = $this->fwPt;
        }
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';

        for ($n=1; $n <= $nb; $n++) {
            // Page
            $this->_newobj();
            $this->_out('<</Type /Page');
            $this->_out('/Parent 1 0 R');
            if (isset($this->OrientationChanges[$n])) {
                $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $hPt, $wPt));
            }
            $this->_out('/Resources 2 0 R');
            if (isset($this->PageLinks[$n])) {
                // Links
                $annots         = '/Annots [';
                reset($this->PageLinks[$n]);
                while (list(, $pl) = each($this->PageLinks[$n])) {
                    $rect       = sprintf('%.2f %.2f %.2f %.2f', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
                    $annots     .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
                    if (is_string($pl[4])) {
                        $annots .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
                    }
                    else {
                        $l      = $this->links[$pl[4]];
                        $h      = isset($this->OrientationChanges[$l[0]]) ? $wPt : $hPt;
                        $annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]>>', 1 + 2 * $l[0], $h - $l[1] * $this->k);
                    }
                } // end while
                $this->_out($annots . ']');
            } // end if
            $this->_out('/Contents ' . ($this->n+1).' 0 R>>');
            $this->_out('endobj');

            // Page content
            $p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
            $this->_newobj();
            $this->_out('<<' . $filter . '/Length ' . strlen($p) . '>>');
            $this->_putstream($p);
            $this->_out('endobj');
        } // end for

        // Pages root
        $this->offsets[1]=strlen($this->buffer);
        $this->_out('1 0 obj');
        $this->_out('<</Type /Pages');
        $kids     = '/Kids [';
        for ($i = 0; $i < $nb; $i++) {
            $kids .= (3 + 2 * $i) . ' 0 R ';
        }
        $this->_out($kids . ']');
        $this->_out('/Count ' . $nb);
        $this->_out(sprintf('/MediaBox [0 0 %.2f %.2f]', $wPt, $hPt));
        $this->_out('>>');
        $this->_out('endobj');
    } // end of the "_putpages()" method


    /**
     * Puts font faces
     *
     * @access private
     */
    function _putfonts()
    {
        $nf = $this->n;

        foreach($this->diffs AS $diff) {
            // Encodings
            $this->_newobj();
            $this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . ']>>');
            $this->_out('endobj');
        } // end while

        $mqr = get_magic_quotes_runtime();
        set_magic_quotes_runtime(0);

        foreach($this->FontFiles AS $file => $info) {
            // Font file embedding
            $this->_newobj();
            $this->FontFiles[$file]['n'] = $this->n;
            if (isset($GLOBALS['FPDF_font_path'])) {
                $file = $GLOBALS['FPDF_font_path'] . $file;
            }
            $size     = filesize($file);
            if (!$size) {
                $this->Error('Font file not found');
            }
            $this->_out('<</Length ' . $size);
            if (substr($file, -2) == '.z') {
                $this->_out('/Filter /FlateDecode');
            }
            $this->_out('/Length1 ' . $info['length1']);
            if (isset($info['length2'])) {
                $this->_out('/Length2 ' . $info['length2'] . ' /Length3 0');
            }
            $this->_out('>>');
            $f = fopen($file, 'rb');
            $this->_putstream(fread($f, $size));
            fclose($f);
            $this->_out('endobj');
        } // end while
        set_magic_quotes_runtime($mqr);

        foreach($this->fonts AS $k => $font) {
            // Font objects
            $this->_newobj();
            $this->fonts[$k]['n'] = $this->n;
            $name                 = $font['name'];
            $this->_out('<</Type /Font');
            $this->_out('/BaseFont /' . $name);
            if ($font['type'] == 'core')  {
                // Standard font
                $this->_out('/Subtype /Type1');
                if ($name != 'Symbol' && $name != 'ZapfDingbats') {
                    $this->_out('/Encoding /WinAnsiEncoding');
                }
            }
            else {
                // Additional font
                $this->_out('/Subtype /' . $font['type']);
                $this->_out('/FirstChar 32');
                $this->_out('/LastChar 255');
                $this->_out('/Widths ' . ($this->n + 1) . ' 0 R');
                $this->_out('/FontDescriptor ' . ($this->n + 2) . ' 0 R');
                if ($font['enc']) {
                    if (isset($font['diff'])) {
                        $this->_out('/Encoding ' . ($nf + $font['diff']) . ' 0 R');
                    } else {
                        $this->_out('/Encoding /WinAnsiEncoding');
                    }
                }
            } // end if... else...
            $this->_out('>>');
            $this->_out('endobj');
            if ($font['type'] != 'core')  {
                // Widths
                $this->_newobj();
                $s     = '[';
                for ($i = 32; $i <= 255; $i++) {
                    $s .= $font['cw'][chr($i)] . ' ';
                }
                $this->_out($s . ']');
                $this->_out('endobj');
                // Descriptor
                $this->_newobj();
                $s     = '<</Type /FontDescriptor /FontName /' . $name;
                foreach($font['desc'] AS $k => $v) {
                    $s .= ' /' . $k . ' ' . $v;
                }
                $file = $font['file'];
                if ($file) {
                    $s .= ' /FontFile' . ($font['type'] == 'Type1' ? '' : '2') . ' ' . $this->FontFiles[$file]['n'] . ' 0 R';
                }
                $this->_out($s . '>>');
                $this->_out('endobj');
            } // end if
        } // end while
    } // end of the "_putfonts()" method


    /**
     * Puts images
     *
     * @access private
     */
    function _putimages()
    {
        $filter = ($this->compress) ? '/Filter /FlateDecode ' : '';

        foreach($this->images AS $file => $info) {
            $this->_newobj();
            $this->images[$file]['n'] = $this->n;
            $this->_out('<</Type /XObject');
            $this->_out('/Subtype /Image');
            $this->_out('/Width ' . $info['w']);
            $this->_out('/Height ' . $info['h']);
            if ($info['cs'] == 'Indexed') {
                $this->_out('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal'])/ 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]');
            }
            else {
                $this->_out('/ColorSpace /' . $info['cs']);
                if ($info['cs'] == 'DeviceCMYK') {
                    $this->_out('/Decode [1 0 1 0 1 0 1 0]');
                }
            } // end if... else...
            $this->_out('/BitsPerComponent ' . $info['bpc']);
            $this->_out('/Filter /' . $info['f']);
            if (isset($info['parms'])) {
                $this->_out($info['parms']);
            }
            if (isset($info['trns']) && is_array($info['trns'])) {
                $trns     = '';
                $trns_cnt = count($info['trns']);
                for ($i = 0; $i < $trns_cnt; $i++) {
                    $trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
                }
                $this->_out('/Mask [' . $trns . ']');
            } // end if
            $this->_out('/Length ' . strlen($info['data']) . '>>');
            $this->_putstream($info['data']);
            $this->_out('endobj');

            // Palette
            if ($info['cs'] == 'Indexed') {
                $this->_newobj();
                $pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
                $this->_out('<<' . $filter . '/Length ' . strlen($pal) . '>>');
                $this->_putstream($pal);
                $this->_out('endobj');
            } // end if
        } // end while
    } // end of the "_putimages()" method


    /**
     * Puts resources
     *
     * @access private
     */
    function _putresources()
    {
        $this->_putfonts();
        $this->_putimages();
        // Resource dictionary
        $this->offsets[2] = strlen($this->buffer);
        $this->_out('2 0 obj');
        $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
        $this->_out('/Font <<');
        foreach($this->fonts AS $font) {
            $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
        }
        $this->_out('>>');
        if (count($this->images)) {
            $this->_out('/XObject <<');
            foreach($this->images AS $image) {
                $this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
            }
            $this->_out('>>');
        }
        $this->_out('>>');
        $this->_out('endobj');
    } // end of the "_putresources()" method


    /**
     * Puts document informations
     *
     * @access private
     */
    function _putinfo()
    {
        // loic1: PHP3 compatibility
        // $this->_out('/Producer ' . $this->_textstring('FPDF ' . FPDF_VERSION));
        $this->_out('/Producer ' . $this->_textstring('FPDF ' . $GLOBALS['FPDF_version']));
        if (!empty($this->title)) {
            $this->_out('/Title ' . $this->_textstring($this->title));
        }
        if (!empty($this->subject)) {
            $this->_out('/Subject ' . $this->_textstring($this->subject));
        }
        if (!empty($this->author)) {
            $this->_out('/Author ' . $this->_textstring($this->author));
        }
        if (!empty($this->keywords)) {
            $this->_out('/Keywords ' . $this->_textstring($this->keywords));
        }
        if (!empty($this->creator)) {
            $this->_out('/Creator ' . $this->_textstring($this->creator));
        }
        $this->_out('/CreationDate ' . $this->_textstring('D:' . date('YmdHis')));
    } // end of the "_putinfo()" method


    /**
     * Puts catalog informations
     *
     * @access private
     */
    function _putcatalog()
    {
        $this->_out('/Type /Catalog');
        $this->_out('/Pages 1 0 R');
        if ($this->ZoomMode == 'fullpage') {
            $this->_out('/OpenAction [3 0 R /Fit]');
        } else if ($this->ZoomMode == 'fullwidth') {
            $this->_out('/OpenAction [3 0 R /FitH null]');
        } else if ($this->ZoomMode == 'real') {
            $this->_out('/OpenAction [3 0 R /XYZ null null 1]');
        } else if (!is_string($this->ZoomMode)) {
            $this->_out('/OpenAction [3 0 R /XYZ null null ' . ($this->ZoomMode / 100) . ']');
        }
        if ($this->LayoutMode == 'single') {
            $this->_out('/PageLayout /SinglePage');
        } else if ($this->LayoutMode == 'continuous') {
            $this->_out('/PageLayout /OneColumn');
        } else if ($this->LayoutMode == 'two') {
            $this->_out('/PageLayout /TwoColumnLeft');
        }
    } // end of the "_putcatalog()" method


    /**
     * Puts trailer
     *
     * @access private
     */
    function _puttrailer()
    {
        $this->_out('/Size ' . ($this->n + 1));
        $this->_out('/Root ' . $this->n . ' 0 R');
        $this->_out('/Info ' . ($this->n - 1) . ' 0 R');
    } // end of the "_puttrailer()" method


    /**
     * Terminates document
     *
     * @access private
     */
    function _enddoc()
    {
        $this->_putpages();
        $this->_putresources();

        // Info
        $this->_newobj();
        $this->_out('<<');
        $this->_putinfo();
        $this->_out('>>');
        $this->_out('endobj');

        // Catalog
        $this->_newobj();
        $this->_out('<<');
        $this->_putcatalog();
        $this->_out('>>');
        $this->_out('endobj');

        // Cross-ref
        $o = strlen($this->buffer);
        $this->_out('xref');
        $this->_out('0 ' . ($this->n + 1));
        $this->_out('0000000000 65535 f ');
        for ($i = 1; $i <= $this->n; $i++) {
            $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
        }

        // Trailer
        $this->_out('trailer');
        $this->_out('<<');
        $this->_puttrailer();
        $this->_out('>>');
        $this->_out('startxref');
        $this->_out($o);
        $this->_out('%%EOF');
        $this->state=3;
    } // end of the "_enddoc()" method


    /**
     * Starts a new page
     *
     * @param  string   The page orientation
     *
     * @access private
     */
    function _beginpage($orientation)
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state              = 2;
        $this->x                  = $this->lMargin;
        $this->y                  = $this->tMargin;
        $this->lasth              = 0;
        $this->FontFamily         = '';

        // Page orientation
        if (!$orientation) {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation[0]);
        }
        if ($orientation != $this->DefOrientation) {
            $this->OrientationChanges[$this->page] = TRUE;
        }
        if ($orientation != $this->CurOrientation) {
            // Changes orientation
            if ($orientation == 'P') {
                $this->wPt = $this->fwPt;
                $this->hPt = $this->fhPt;
                $this->w   = $this->fw;
                $this->h   = $this->fh;
            }
            else {
                $this->wPt = $this->fhPt;
                $this->hPt = $this->fwPt;
                $this->w   = $this->fh;
                $this->h   = $this->fw;
            }
            $this->PageBreakTrigger = $this->h - $this->bMargin;
            $this->CurOrientation   = $orientation;
        } // end if
    } // end of the "_beginpage()" method


    /**
     * Ends page contents
     *
     * @access private
     */
    function _endpage()
    {
        $this->state=1;
    } // end of the "_endpage()" method


    /**
     * Underlines text
     *
     * @param   double  The x position
     * @param   double  The y position
     * @param   string  The text
     *
     * @return  string  The underlined text
     *
     * @access  private
     */
    function _dounderline($x,$y,$txt)
    {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w  = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');

        return sprintf('%.2f %.2f %.2f %.2f re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
    } // end of the "_dounderline()" method


    /**
     * Extracts info from a JPEG file
     *
     * @param   string  The file name and path
     *
     * @return  array   The images informations
     *
     * @access  private
     */
    function _parsejpg($file)
    {
        $a = GetImageSize($file);
        if (!$a) {
            $this->Error('Missing or incorrect image file: ' . $file);
        }
        if ($a[2] != 2) {
            $this->Error('Not a JPEG file: ' . $file);
        }
        if (!isset($a['channels']) || $a['channels'] == 3) {
            $colspace = 'DeviceRGB';
        }
        else if($a['channels'] == 4) {
            $colspace = 'DeviceCMYK';
        }
        else {
            $colspace = 'DeviceGray';
        }
        $bpc = isset($a['bits']) ? $a['bits'] : 8;

        // Reads whole file
        $f    = fopen($file, 'rb');
        $data = fread($f, filesize($file));
        fclose($f);

        return array('w'    => $a[0],
                     'h'    => $a[1],
                     'cs'   => $colspace,
                     'bpc'  => $bpc,
                     'f'    => 'DCTDecode',
                     'data' => $data);
    } // end of the "_parsejpg()" method


    /**
     * Reads a 4-byte integer from a file
     *
     * @param   string   The file name and path
     *
     * @return  integer  The 4-byte integer
     *
     * @access  private
     *
     * @see     _parsepng()
     */
    function _freadint($f)
    {
        $i = ord(fread($f, 1)) << 24;
        $i += ord(fread($f, 1)) << 16;
        $i += ord(fread($f, 1)) << 8;
        $i += ord(fread($f, 1));

        return $i;
    } // end of the "_freadint()" method


    /**
     * Extracts info from a PNG file
     *
     * @param   string  The file name and path
     *
     * @return  array   The images informations
     *
     * @access  private
     *
     * @see     _freadint()
     */
    function _parsepng($file)
    {
        $f = fopen($file, 'rb');
        if (!$f) {
            $this->Error('Can\'t open image file: ' . $file);
        }

        // Checks signature
        if (fread($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10)) {
            $this->Error('Not a PNG file: ' . $file);
        }

        // Reads header chunk
        fread($f,4);
        if (fread($f, 4) != 'IHDR') {
            $this->Error('Incorrect PNG file: ' . $file);
        }
        $w   = $this->_freadint($f);
        $h   = $this->_freadint($f);
        $bpc = ord(fread($f,1));
        if ($bpc > 8) {
            $this->Error('16-bit depth not supported: ' . $file);
        }
        $ct  = ord(fread($f, 1));
        if ($ct == 0) {
            $colspace = 'DeviceGray';
        }
        else if ($ct == 2) {
            $colspace = 'DeviceRGB';
        }
        else if ($ct == 3) {
            $colspace = 'Indexed';
        }
        else {
            $this->Error('Alpha channel not supported: ' . $file);
        }
        if (ord(fread($f, 1)) != 0) {
            $this->Error('Unknown compression method: ' . $file);
        }
        if (ord(fread($f, 1)) != 0) {
            $this->Error('Unknown filter method: ' . $file);
        }
        if (ord(fread($f, 1)) != 0) {
            $this->Error('Interlacing not supported: ' . $file);
        }
        fread($f, 4);
        $parms = '/DecodeParms <</Predictor 15 /Colors ' . ($ct == 2 ? 3 : 1)
               . ' /BitsPerComponent ' . $bpc
               . ' /Columns ' . $w . '>>';

        // Scans chunks looking for palette, transparency and image data
        $pal  = '';
        $trns = '';
        $data = '';
        do {
            $n    = $this->_freadint($f);
            $type = fread($f, 4);
            if ($type == 'PLTE') {
                // Reads palette
                $pal = fread($f, $n);
                fread($f, 4);
            }
            else if ($type == 'tRNS') {
                // Reads transparency info
                $t            = fread($f, $n);
                if ($ct == 0) {
                    $trns     = array(ord(substr($t, 1, 1)));
                }
                else if ($ct == 2) {
                    $trns     = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
                }
                else {
                    $pos      = strpos(' ' . $t, chr(0));
                    if ($pos) {
                        $trns = array($pos - 1);
                    }
                    fread($f,4);
                } // end if... else if... else
            }
            else if ($type == 'IDAT') {
                // Reads image data block
                $data .= fread($f, $n);
                fread($f, 4);
            }
            else if ($type == 'IEND') {
                break;
            }
            else {
                fread($f, $n + 4);
            } // end if... else if... else
        } while($n); // end do

        if ($colspace == 'Indexed' && empty($pal)) {
            $this->Error('Missing palette in ' . $file);
        }
        fclose($f);

        return array('w'     => $w,
                     'h'     => $h,
                     'cs'    => $colspace,
                     'bpc'   => $bpc,
                     'f'     => 'FlateDecode',
                     'parms' => $parms,
                     'pal'   => $pal,
                     'trns'  => $trns,
                     'data'  => $data);
    } // end of the "_parsepng()" method



    /**************************************************************************
    *                                                                         *
    *                             Public methods                              *
    *                                                                         *
    **************************************************************************/

    /**
     * Sets auto page break mode and triggering margin
     *
     * @param  string  The auto page break mode
     * @param  double  Maximum size till the bottom of the page to start adding
     *                 page break
     *
     * @access public
     */
    function SetAutoPageBreak($auto, $margin = 0)
    {
        $this->AutoPageBreak    = $auto;
        $this->bMargin          = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    } // end of the "SetAutoPageBreak()" method


    /**
     * Sets display mode in viewer
     *
     * @param  mixed   The zoom mode (fullpage, fullwidth, real, default,
     *                 zoom or an zoom factor -real-)
     * @param  string  The layout mode (single, continuous, two or default)
     *
     * @access public
     */
    function SetDisplayMode($zoom = 'default', $layout = 'continuous')
    {
        if (is_string($zoom)) {
            $zoom = strtolower($zoom);
        }
        $layout   = strtolower($layout);

        // Zoom mode
        if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default'
            || !is_string($zoom)) {
            $this->ZoomMode = $zoom;
        } else if ($zoom == 'zoom') {
            $this->ZoomMode = $layout;
        } else {
            $this->Error('Incorrect zoom display mode: ' . $zoom);
        } // end if... else if... else...

        // Layout mode
        if ($layout == 'single' || $layout == 'continuous' || $layout=='two' || $layout=='default') {
            $this->LayoutMode = $layout;
        } else if ($zoom != 'zoom') {
            $this->Error('Incorrect layout display mode: ' . $layout);
        } // end if... else if...
    } // end of the "SetDisplayMode()" method


    /**
     * Sets page compression
     *
     * @param  boolean  whether to compress file or not
     *
     * @access public
     */
    function SetCompression($compress)
    {
        if (function_exists('gzcompress')) {
            $this->compress = $compress;
        } else {
            $this->compress = FALSE;
        } // end if... else...
    } // end of the "SetCompression()" method


    /**
     * Sets page margins
     *
     * @param  double  The left margin
     * @param  double  The top margin
     * @param  double  The right margin
     *
     * @access public
     */
    function SetMargins($left, $top, $right = -1)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if ($right == -1) {
            $right = $left;
        }
        $this->rMargin = $right;
    } // end of the "SetMargins()" method


    /**
     * The FPDF constructor
     *
     * @param  string  The page orientation (p, portrait, l or landscape)
     * @param  string  The unit for sizes (pt, mm, cm or in)
     * @param  mixed   The page format (A3, A4, A5, letter, legal or an array
     *                 with page sizes)
     *
     * @access public
     */
    function FPDF($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        // Check for PHP locale-related bug
        if (1.1 == 1) {
            $this->Error('Don\'t call setlocale() before including class file');
        }

        // Initialization of properties
        $this->page               = 0;
        $this->n                  = 2;
        $this->buffer             = '';
        $this->pages              = array();
        $this->OrientationChanges = array();
        $this->state              = 0;
        $this->fonts              = array();
        $this->FontFiles          = array();
        $this->diffs              = array();
        $this->images             = array();
        $this->links              = array();
        $this->InFooter           = FALSE;
        $this->FontFamily         = '';
        $this->FontStyle          = '';
        $this->FontSizePt         = 12;
        $this->underline          = FALSE;
        $this->DrawColor          = '0 G';
        $this->FillColor          = '0 g';
        $this->TextColor          = '0 g';
        $this->ColorFlag          = FALSE;
        $this->ws                 = 0;

        // Standard fonts
        $this->CoreFonts['courier']      = 'Courier';
        $this->CoreFonts['courierB']     = 'Courier-Bold';
        $this->CoreFonts['courierI']     = 'Courier-Oblique';
        $this->CoreFonts['courierBI']    = 'Courier-BoldOblique';
        $this->CoreFonts['helvetica']    = 'Helvetica';
        $this->CoreFonts['helveticaB']   = 'Helvetica-Bold';
        $this->CoreFonts['helveticaI']   = 'Helvetica-Oblique';
        $this->CoreFonts['helveticaBI']  = 'Helvetica-BoldOblique';
        $this->CoreFonts['times']        = 'Times-Roman';
        $this->CoreFonts['timesB']       = 'Times-Bold';
        $this->CoreFonts['timesI']       = 'Times-Italic';
        $this->CoreFonts['timesBI']      = 'Times-BoldItalic';
        $this->CoreFonts['symbol']       = 'Symbol';
        $this->CoreFonts['zapfdingbats'] = 'ZapfDingbats';

        // Scale factor
        if ($unit == 'pt') {
            $this->k = 1;
        } else if ($unit == 'mm') {
            $this->k = 72/25.4;
        } else if ($unit == 'cm') {
            $this->k = 72/2.54;
        } else if ($unit == 'in') {
            $this->k = 72;
        } else {
            $this->Error('Incorrect unit: ' . $unit);
        } // end if... else if... else...

        // Page format
        if (is_string($format)) {
            // 2002-07-24 - Nicola Asuni (info@tecnick.com)
            // Added new page formats (45 standard ISO paper formats and 4
            // american common formats).
            // Paper cordinates are calculated in this way:
            //    (inches * 72) where (1 inch = 2.54 cm)
            switch (strtoupper($format)) {
                case '4A0':
                    $format = array(4767.87, 6740.79);
                    break;
                case '2A0':
                    $format = array(3370.39, 4767.87);
                    break;
                case 'A0':
                    $format = array(2383.94, 3370.39);
                    break;
                case 'A1':
                    $format = array(1683.78, 2383.94);
                    break;
                case 'A2':
                    $format = array(1190.55, 1683.78);
                    break;
                case 'A3':
                    $format = array(841.89, 1190.55);
                    break;
                case 'A4':
                    $format = array(595.28, 841.89);
                    break;
                case 'A5':
                    $format = array(419.53, 595.28);
                    break;
                case 'A6':
                    $format = array(297.64, 419.53);
                    break;
                case 'A7':
                    $format = array(209.76, 297.64);
                    break;
                case 'A8':
                    $format = array(147.40, 209.76);
                    break;
                case 'A9':
                    $format = array(104.88, 147.40);
                    break;
                case 'A10':
                    $format = array(73.70, 104.88);
                    break;
                case 'B0':
                    $format = array(2834.65, 4008.19);
                    break;
                case 'B1':
                    $format = array(2004.09, 2834.65);
                    break;
                case 'B2':
                    $format = array(1417.32, 2004.09);
                    break;
                case 'B3':
                    $format = array(1000.63, 1417.32);
                    break;
                case 'B4':
                    $format = array(708.66, 1000.63);
                    break;
                case 'B5':
                    $format = array(498.90, 708.66);
                    break;
                case 'B6':
                    $format = array(354.33, 498.90);
                    break;
                case 'B7':
                    $format = array(249.45, 354.33);
                    break;
                case 'B8':
                    $format = array(175.75, 249.45);
                    break;
                case 'B9':
                    $format = array(124.72, 175.75);
                    break;
                case 'B10':
                    $format = array(87.87, 124.72);
                    break;
                case 'C0':
                    $format = array(2599.37, 3676.54);
                    break;
                case 'C1':
                    $format = array(1836.85, 2599.37);
                    break;
                case 'C2':
                    $format = array(1298.27, 1836.85);
                    break;
                case 'C3':
                    $format = array(918.43, 1298.27);
                    break;
                case 'C4':
                    $format = array(649.13, 918.43);
                    break;
                case 'C5':
                    $format = array(459.21, 649.13);
                    break;
                case 'C6':
                    $format = array(323.15, 459.21);
                    break;
                case 'C7':
                    $format = array(229.61, 323.15);
                    break;
                case 'C8':
                    $format = array(161.57, 229.61);
                    break;
                case 'C9':
                    $format = array(113.39, 161.57);
                    break;
                case 'C10':
                    $format = array(79.37, 113.39);
                    break;
                case 'RA0':
                    $format = array(2437.80, 3458.27);
                    break;
                case 'RA1':
                    $format = array(1729.13, 2437.80);
                    break;
                case 'RA2':
                    $format = array(1218.90, 1729.13);
                    break;
                case 'RA3':
                    $format = array(864.57, 1218.90);
                    break;
                case 'RA4':
                    $format = array(609.45, 864.57);
                    break;
                case 'SRA0':
                    $format = array(2551.18, 3628.35);
                    break;
                case 'SRA1':
                    $format = array(1814.17, 2551.18);
                    break;
                case 'SRA2':
                    $format = array(1275.59, 1814.17);
                    break;
                case 'SRA3':
                    $format = array(907.09, 1275.59);
                    break;
                case 'SRA4':
                    $format = array(637.80, 907.09);
                    break;
                case 'LETTER':
                    $format = array(612.00, 792.00);
                    break;
                case 'LEGAL':
                    $format = array(612.00, 1008.00);
                    break;
                case 'EXECUTIVE':
                    $format = array(521.86, 756.00);
                    break;
                case 'FOLIO':
                    $format = array(612.00, 936.00);
                    break;
                default:
                    $this->Error('Unknown page format: ' . $format);
                    break;
            } // end switch
            $this->fwPt = $format[0];
            $this->fhPt = $format[1];
        }
        else {
            $this->fwPt = $format[0] * $this->k;
            $this->fhPt = $format[1] * $this->k;
        } // end if... else...
        $this->fw       = $this->fwPt / $this->k;
        $this->fh       = $this->fhPt / $this->k;

        // Page orientation
        $orientation    = strtolower($orientation);
        if ($orientation == 'p' || $orientation == 'portrait') {
            $this->DefOrientation = 'P';
            $this->wPt            = $this->fwPt;
            $this->hPt            = $this->fhPt;
        }
        else if ($orientation == 'l' || $orientation == 'landscape') {
            $this->DefOrientation = 'L';
            $this->wPt            = $this->fhPt;
            $this->hPt            = $this->fwPt;
        }
        else {
            $this->Error('Incorrect orientation: ' . $orientation);
        } // end if... else if... else...
        $this->CurOrientation     = $this->DefOrientation;
        $this->w                  = $this->wPt / $this->k;
        $this->h                  = $this->hPt / $this->k;

        // Page margins (1 cm)
        $margin          = 28.35 / $this->k;
        $this->SetMargins($margin, $margin);

        // Interior cell margin (1 mm)
        $this->cMargin   = $margin / 10;

        // Line width (0.2 mm)
        $this->LineWidth = .567 / $this->k;

        // Automatic page break
        $this->SetAutoPageBreak(TRUE, 2 * $margin);

        // Full width display mode
        $this->SetDisplayMode('fullwidth');

        // Compression
        $this->SetCompression(TRUE);
    } // end of the "FPDF()" constructor


    /**
     * Sets left margin of the page
     *
     * @param  double  The left margin
     *
     * @access public
     */
    function SetLeftMargin($margin)
    {
        $this->lMargin = $margin;
        if ($this->page > 0 && $this->x < $margin) {
            $this->x   = $margin;
        }
    } // end of the "SetLeftMargin()" method


    /**
     * Sets top margin of the page
     *
     * @param  double  The top margin
     *
     * @access public
     */
    function SetTopMargin($margin)
    {
        $this->tMargin = $margin;
    } // end of the "SetTopMargin()" method


    /**
     * Sets right margin of the page
     *
     * @param  double  The right margin
     *
     * @access public
     */
    function SetRightMargin($margin)
    {
        $this->rMargin = $margin;
    } // end of the "SetRightMargin()" method


    /**
     * Sets the title of the document (among the document properties)
     *
     * @param  string  The title of the document
     *
     * @access public
     */
    function SetTitle($title)
    {
        $this->title = $title;
    } // end of the "SetTitle()" method


    /**
     * Sets the subject of the document (among the document properties)
     *
     * @param  string  The subject of the document
     *
     * @access public
     */
    function SetSubject($subject)
    {
        $this->subject = $subject;
    } // end of the "SetSubject()" method


    /**
     * Sets the author of the document (among the document properties)
     *
     * @param  string  The author of the document
     *
     * @access public
     */
    function SetAuthor($author)
    {
        $this->author = $author;
    } // end of the "SetAuthor()" method


    /**
     * Sets keywords of the document (among the document properties)
     *
     * @param  string  The keyword list for the document
     *
     * @access public
     */
    function SetKeywords($keywords)
    {
        $this->keywords = $keywords;
    } // end of the "SetKeywords()" method


    /**
     * Sets the creator of the document (among the document properties)
     *
     * @param  string  The creator of the document
     *
     * @access public
     */
    function SetCreator($creator)
    {
        $this->creator = $creator;
    } // end of the "SetCreator()" method


    /**
     * Defines an alias for the total number of pages
     *
     * @param  string  The alias string
     *
     * @access public
     */
    function AliasNbPages($alias = '{nb}')
    {
        $this->AliasNbPages = $alias;
    } // end of the "AliasNbPages()" method


    /**
     * Selects a font
     *
     * @param   string   The font name
     * @param   string   The font style (B, I, BI)
     * @param   double   The font size (in points)
     *
     * @global  double   The character width
     *
     * @access  public
     */
    function SetFont($family, $style = '', $size = 0)
    {
        global $fpdf_charwidths;

        $family     = strtolower($family);
        if ($family == '') {
            $family = $this->FontFamily;
        }
        if ($family == 'arial') {
            $family = 'helvetica';
        }
        else if ($family == 'symbol' || $family == 'zapfdingbats') {
            $style  = '';
        }
        $style      = strtoupper($style);

        if (strpos(' ' . $style, 'U')) {
            $this->underline = TRUE;
            $style           = str_replace('U', '', $style);
        } else {
            $this->underline = FALSE;
        }
        if ($style == 'IB') {
            $style  = 'BI';
        }
        if ($size == 0) {
            $size   = $this->FontSizePt;
        }

        // Tests if the font is already selected
        if ($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size) {
            return;
        }

        // Tests if used for the first time
        $fontkey = $family . $style;
        if (!isset($this->fonts[$fontkey])) {
            // Checks if one of the standard fonts
            if (isset($this->CoreFonts[$fontkey])) {
                if (!isset($fpdf_charwidths[$fontkey])) {
                    // Loads metric file
                    $file     = $family;
                    if ($family == 'times' || $family == 'helvetica') {
                        $file .= strtolower($style);
                    }
                    $file     .= '.php';
                    if (isset($GLOBALS['FPDF_font_path'])) {
                        $file = $GLOBALS['FPDF_font_path'] . $file;
                    }
                    include($file);
                    if (!isset($fpdf_charwidths[$fontkey])) {
                        $this->Error('Could not include font metric file');
                    }
                } // end if
                $i = count($this->fonts) + 1;
                $this->fonts[$fontkey] = array('i'    => $i,
                                               'type' => 'core',
                                               'name' => $this->CoreFonts[$fontkey],
                                               'up'   => -100,
                                               'ut'   => 50,
                                               'cw'   => $fpdf_charwidths[$fontkey]);
            }
            else {
                $this->Error('Undefined font: ' . $family . ' ' . $style);
            } // end if... else...
        } // end if

        // Selects it
        $this->FontFamily  = $family;
        $this->FontStyle   = $style;
        $this->FontSizePt  = $size;
        $this->FontSize    = $size / $this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    } // end of the "SetFont()" method


    /**
     * Sends the header of the page
     *
     * This method has to be implemented in your own inherited class
     *
     * @access public
     */
    function Header()
    {
        // void
    } // end of the "Header()" method


    /**
     * Sends the footer of the page
     *
     * This method has to be implemented in your own inherited class
     *
     * @access public
     */
    function Footer()
    {
        // void
    } // end of the "Footer()" method


    /**
     * Begin the document
     *
     * @access public
     */
    function Open()
    {
        $this->_begindoc();
    } // end of the "Open()" method


    /**
     * Starts a new page
     *
     * @param  string  The page orientation (p, portrait, l or landscape)
     *
     * @access public
     */
    function AddPage($orientation = '')
    {
        // Backups some core variables
        $family             = $this->FontFamily;
        $style              = $this->FontStyle . ($this->underline ? 'U' : '');
        $size               = $this->FontSizePt;
        $lw                 = $this->LineWidth;
        $dc                 = $this->DrawColor;
        $fc                 = $this->FillColor;
        $tc                 = $this->TextColor;
        $cf                 = $this->ColorFlag;

        // If a page is already defined close it before starting the new one
        if ($this->page > 0) {
            // Page footer
            $this->InFooter = TRUE;
            $this->Footer();
            $this->InFooter = FALSE;
            // Close page
            $this->_endpage();
        }

        // Do start the new page
        $this->_beginpage($orientation);
        // Sets line cap style to square
        $this->_out('2 J');
        // Sets line width
        $this->LineWidth = $lw;
        $this->_out(sprintf('%.2f w', $lw * $this->k));
        // Sets font
        if ($family) {
            $this->SetFont($family, $style, $size);
        }
        // Sets colors
        $this->DrawColor = $dc;
        if ($dc != '0 G') {
            $this->_out($dc);
        }
        $this->FillColor = $fc;
        if ($fc != '0 g') {
            $this->_out($fc);
        }
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        // Sets Page header
        $this->Header();
        // Restores line width
        if ($this->LineWidth != $lw) {
            $this->LineWidth = $lw;
            $this->_out(sprintf('%.2f w', $lw * $this->k));
        }
        // Restores font
        if ($family) {
            $this->SetFont($family, $style, $size);
        }
        // Restores colors
        if ($this->DrawColor!=$dc) {
            $this->DrawColor = $dc;
            $this->_out($dc);
        }
        if ($this->FillColor!=$fc) {
            $this->FillColor = $fc;
            $this->_out($fc);
        }
        $this->TextColor     = $tc;
        $this->ColorFlag     = $cf;
    } // end of the "AddPage()" method


    /**
     * Terminates and closes the document
     *
     * @access public
     */
    function Close()
    {
        // Terminates document
        if ($this->page == 0) {
            $this->AddPage();
        }

        // Displays the page footer
        $this->InFooter = TRUE;
        $this->Footer();
        $this->InFooter = FALSE;

        // Closes page and document
        $this->_endpage();
        $this->_enddoc();
    } // end of the "Close()" method


    /**
     * Gets the current page number
     *
     * @return  integer  The current page number
     *
     * @access  public
     */
    function PageNo()
    {
        return $this->page;
    } // end of the "PageNo()" method


    /**
     * Sets color for all stroking operations
     *
     * @param  integer  The red level (0 to 255)
     * @param  integer  The green level (0 to 255)
     * @param  integer  The blue level (0 to 255)
     *
     * @access public
     */
    function SetDrawColor($r, $g = -1, $b = -1)
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->DrawColor = sprintf('%.3f G', $r / 255);
        } else {
            $this->DrawColor = sprintf('%.3f %.3f %.3f RG', $r / 255, $g / 255, $b / 255);
        } // end if... else...

        // If a page is defined, applies this property
        if ($this->page > 0) {
            $this->_out($this->DrawColor);
        }
    } // end of the "SetDrawColor()" method


    /**
     * Sets color for all filling operations
     *
     * @param  integer  The red level (0 to 255)
     * @param  integer  The green level (0 to 255)
     * @param  integer  The blue level (0 to 255)
     *
     * @access public
     */
    function SetFillColor($r, $g = -1, $b =-1)
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->FillColor = sprintf('%.3f g', $r / 255);
        } else {
            $this->FillColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
        } // end if... else...

        $this->ColorFlag     = ($this->FillColor != $this->TextColor);

        // If a page is defined, applies this property
        if ($this->page > 0) {
            $this->_out($this->FillColor);
        }
    } // end of the "SetDrawColor()" method


    /**
     * Sets color for text
     *
     * @param  integer  The red level (0 to 255)
     * @param  integer  The green level (0 to 255)
     * @param  integer  The blue level (0 to 255)
     *
     * @access public
     */
    function SetTextColor($r, $g = -1, $b =-1)
    {
        if (($r == 0 && $g == 0 && $b == 0) || $g == -1) {
            $this->TextColor = sprintf('%.3f g', $r / 255);
        } else {
            $this->TextColor = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
        } // end if... else...

        $this->ColorFlag     = ($this->FillColor != $this->TextColor);
    } // end of the "SetTextColor()" method


    /**
     * Sets the line width
     *
     * @param   double  The line width
     *
     * @access  public
     */
    function SetLineWidth($width)
    {
        $this->LineWidth = $width;

        // If a page is defined, applies this property
        if ($this->page > 0) {
            $this->_out(sprintf('%.2f w', $width * $this->k));
        }
    } // end of the "SetLineWidth()" method


    /**
     * Draws a line
     *
     * @param   double  The horizontal position of the starting point
     * @param   double  The vertical position of the starting point
     * @param   double  The horizontal position of the ending point
     * @param   double  The vertical position of the ending point
     *
     * @access  public
     */
    function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    } // end of the "Line()" method


    /**
     * Draws a rectangle
     *
     * @param   double  The horizontal position of the top left corner
     * @param   double  The vertical position of the top left corner
     * @param   double  The horizontal position of the bottom right corner
     * @param   double  The vertical position of the bottom right corner
     * @param   string  The rectangle style
     *
     * @access  public
     */
    function Rect($x, $y, $w, $h, $style = '')
    {
        if ($style == 'F') {
            $op = 'f';
        } else if ($style == 'FD' || $style=='DF') {
            $op = 'B';
        } else {
            $op = 'S';
        } // end if... else if... else

        $this->_out(sprintf('%.2f %.2f %.2f %.2f re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    } // end of the "Rect()" method


    /**
     * Adds a TrueType or Type1 font
     *
     * @param   string  The font name
     * @param   string  The font style (B, I, BI)
     * @param   string  The font file definition
     *
     * @access  public
     */
    function AddFont($family, $style = '', $file = '')
    {
        $family     = strtolower($family);
        if ($family == 'arial') {
            $family = 'helvetica';
        }

        $style  = strtoupper($style);
        if ($style == 'IB') {
            $style = 'BI';
        }
        if (isset($this->fonts[$family . $style])) {
            $this->Error('Font already added: ' . $family . ' ' . $style);
        }
        if ($file == '') {
            $file = str_replace(' ', '', $family) . strtolower($style) . '.php';
        }
        if (isset($GLOBALS['FPDF_font_path'])) {
            $file = $GLOBALS['FPDF_font_path'] . $file;
        }
        include($file);
        if (!isset($name)) {
            $this->Error('Could not include font definition file');
        }

        $i = count($this->fonts) + 1;
        $this->fonts[$family . $style] = array('i'    => $i,
                                               'type' => $type,
                                               'name' => $name,
                                               'desc' => $desc,
                                               'up'   => $up,
                                               'ut'   => $ut,
                                               'cw'   => $cw,
                                               'enc'  => $enc,
                                               'file' => $file);
        // Searches existing encodings
        if ($diff) {
            $d  = 0;
            $nb = count($this->diffs);
            for ($i = 1; $i <= $nb; $i++) {
                if ($this->diffs[$i] == $diff) {
                    $d = $i;
                    break;
                } // end if
            } // end for
            if ($d == 0) {
                $d               = $nb + 1;
                $this->diffs[$d] = $diff;
            } // end if
            $this->fonts[$family . $style]['diff'] = $d;
        } // end if

        if ($file) {
            if ($type == 'TrueType') {
                $this->FontFiles[$file] = array('length1' => $originalsize);
            } else {
                $this->FontFiles[$file] = array('length1' => $size1, 'length2' => $size2);
            }
        } // end if
    } // end of the "AddFont()" method


    /**
     * Sets font size
     *
     * @param   double   The font size (in points)
     *
     * @access  public
     */
    function SetFontSize($size)
    {
        if ($this->FontSizePt == $size) {
            return;
        }
        $this->FontSizePt = $size;
        $this->FontSize   = $size / $this->k;
        if ($this->page > 0) {
            $this->_out(sprintf('BT /F%d %.2f Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
        }
    } // end of the "SetFontSize()" method


    /**
     * Creates a new internal link
     *
     * @return  integer  The link id
     *
     * @access  public
     */
    function AddLink()
    {
        $n = count($this->links) + 1;
        $this->links[$n] = array(0, 0);
        return $n;
    } // end of the "AddLink()" method


    /**
     * Sets destination of internal link
     *
     * @param   integer  The link id
     * @param   double   The y position on the page
     * @param   integer  The page number
     *
     * @access  public
     */
    function SetLink($link, $y = 0, $page = -1)
    {
        if ($y == -1) {
            $y    = $this->y;
        }
        if ($page == -1) {
            $page = $this->page;
        }
        $this->links[$link] = array($page, $y);
    } // end of the "SetLink()" method


    /**
     * Put a link inside a rectangular area of the page
     *
     * @param   double   The top left x position
     * @param   double   The top left y position
     * @param   double   The rectangle width
     * @param   double   The rectangle height
     * @param   mixed    The link id or an url
     *
     * @access  public
     */
    function Link($x, $y, $w, $h, $link)
    {
        $this->PageLinks[$this->page][] = array($x * $this->k,
                                                $this->hPt - $y * $this->k,
                                                $w * $this->k,
                                                $h * $this->k,
                                                $link);
    } // end of the "Link()" method


    /**
     * Outputs a string
     *
     * @param   double  The x position
     * @param   double  The y position
     * @param   string  The string
     *
     * @access  public
     */
    function Text($x, $y, $txt)
    {
        $txt   = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
        $s     = sprintf('BT %.2f %.2f Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $txt);
        if ($this->underline && $txt != '') {
            $s .= ' ' . $this->_dounderline($x, $y, $txt);
        }
        if ($this->ColorFlag) {
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        }
        $this->_out($s);
    } // end of the "Text()" method


    /**
     * Gets whether automatic page break is on or not
     *
     * @return  boolean  Whether automatic page break is on or not
     *
     * @access  public
     */
    function AcceptPageBreak()
    {
        return $this->AutoPageBreak;
    } // end of the "AcceptPageBreak()" method


    /**
     * Output a cell
     *
     * @param   double   The cell width
     * @param   double   The cell height
     * @param   string   The text to output
     * @param   mixed    Wether to add borders or not (see the manual)
     * @param   integer  Where to put the cursor once the output is done
     * @param   string   Align mode
     * @param   integer  Whether to fill the cell with a color or not
     * @param   mixed    The link id or an url
     *
     * @access  public
     */
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = 0, $link = '')
    {
        $k = $this->k;

        if ($this->y + $h > $this->PageBreakTrigger
            && !$this->InFooter
            && $this->AcceptPageBreak()) {
            $x  = $this->x;
            $ws = $this->ws;
            if ($ws > 0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation);
            $this->x = $x;
            if ($ws > 0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3f Tw', $ws * $k));
            }
        } // end if

        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $s          = '';
        if ($fill == 1 || $border == 1) {
            if ($fill == 1) {
                $op = ($border == 1) ? 'B' : 'f';
            } else {
                $op = 'S';
            }
            $s      = sprintf('%.2f %.2f %.2f %.2f re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
        } // end if

        if (is_string($border)) {
            $x     = $this->x;
            $y     = $this->y;
            if (strpos(' ' . $border, 'L')) {
                $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y+$h)) * $k);
            }
            if (strpos(' ' . $border, 'T')) {
                $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            }
            if (strpos(' ' . $border, 'R')) {
                $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
            if (strpos(' ' . $border, 'B')) {
                $s .= sprintf('%.2f %.2f m %.2f %.2f l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
        } // end if

        if ($txt != '') {
            if ($align == 'R') {
                $dx = $w - $this->cMargin - $this->GetStringWidth($txt);
            }
            else if ($align == 'C') {
                $dx = ($w - $this->GetStringWidth($txt)) / 2;
            }
            else {
                $dx = $this->cMargin;
            }
            $txt    = str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt)));
            if ($this->ColorFlag) {
                $s  .= 'q ' . $this->TextColor . ' ';
            }
            $s      .= sprintf('BT %.2f %.2f Td (%s) Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt);
            if ($this->underline) {
                $s  .= ' ' . $this->_dounderline($this->x+$dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
            }
            if ($this->ColorFlag) {
                $s  .= ' Q';
            }
            if ($link) {
                $this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
            }
        } // end if

        if ($s) {
            $this->_out($s);
        }
        $this->lasth = $h;

        if ($ln > 0) {
            // Go to next line
            $this->y     += $h;
            if ($ln == 1) {
                $this->x = $this->lMargin;
            }
        } else {
            $this->x     += $w;
        }
    } // end of the "Cell()" method


    /**
     * Output text with automatic or explicit line breaks
     *
     * @param   double   The cell width
     * @param   double   The cell height
     * @param   string   The text to output
     * @param   mixed    Wether to add borders or not (see the manual)
     * @param   string   Align mode
     * @param   integer  Whether to fill the cell with a color or not
     *
     * @access  public
     */
    function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = 0)
    {
        // loic1: PHP3 compatibility
        // $cw    = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->lMargin - $this->x;
        }
        $wmax  = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s     = str_replace("\r", '', $txt);
        $nb    = strlen($s);
        if ($nb>0 && $s[$nb - 1] == "\n") {
            $nb--;
        }

        $b              = 0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b      = 'LRT';
                $b2     = 'LR';
            }
            else {
                $b2     = '';
                if (strpos(' ' . $border, 'L')) {
                    $b2 .= 'L';
                }
                if (strpos(' ' . $border, 'R')) {
                    $b2 .= 'R';
                }
                $b      = (strpos(' ' . $border, 'T')) ? $b2 . 'T' : $b2;
            } // end if... else...
        } // end if

        $sep = -1;
        $i   = 0;
        $j   = 0;
        $l   = 0;
        $ns  = 0;
        $nl  = 1;
        while ($i < $nb) {
            // Gets next character
            $c = $s[$i];

            // Explicit line break
            if ($c == "\n") {
                if ($this->ws > 0) {
                    $this->ws = 0;
                    $this->_out('0 Tw');
                }
                $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                $i++;
                $sep   = -1;
                $j     = $i;
                $l     = 0;
                $ns    = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
                continue;
            } // end if

            // Space character
            if ($c == ' ') {
                $sep = $i;
                $ls  = $l;
                $ns++;
            } // end if

            $l += $this->CurrentFont['cw'][$c];
            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                    if ($this->ws > 0) {
                        $this->ws = 0;
                        $this->_out('0 Tw');
                    }
                    $this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
                }
                else {
                    if ($align == 'J') {
                        $this->ws = ($ns > 1)
                                  ? ($wmax - $ls) / 1000 * $this->FontSize / ($ns - 1)
                                  : 0;
                        $this->_out(sprintf('%.3f Tw', $this->ws * $this->k));
                    }
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
                    $i = $sep + 1;
                } // end if... else...

                $sep   = -1;
                $j     = $i;
                $l     = 0;
                $ns    = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
            }
            else {
                $i++;
            } // end if... else
        } // end while

        // Last chunk
        if ($this->ws > 0) {
            $this->ws = 0;
            $this->_out('0 Tw');
        }

        if ($border && strpos(' ' . $border, 'B')) {
            $b .= 'B';
        }
        $this->Cell($w, $h, substr($s, $j, $i), $b, 2, $align, $fill);
        $this->x = $this->lMargin;
    } // end of the "MultiCell()" method


    /**
     * Output text in flowing mode
     *
     * @param   double   The line height
     * @param   string   The text to output
     * @param   mixed    The link id or an url
     *
     * @access  public
     */
    function Write($h, $txt, $link = '')
    {
        $w    = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s    = str_replace("\r", '', $txt);
        $nb   = strlen($s);
        $sep  = -1;
        $i    = 0;
        $j    = 0;
        $l    = 0;
        $nl   = 1;

        while ($i < $nb) {
            // Gets next character
            $c = $s[$i];

            // Explicit line break
            if ($c == "\n") {
                $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                $i++;
                $sep = -1;
                $j   = $i;
                $l   = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w       = $this->w - $this->rMargin - $this->x;
                    $wmax    = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                }
                $nl++;
                continue;
            }

            // Space character
            if ($c == ' ') {
                $sep = $i;
                $ls  = $l;
            } // end if

            $l += $this->CurrentFont['cw'][$c];
            if ($l > $wmax) {
                // Automatic line break
                if ($sep == -1) {
                    if($this->x > $this->lMargin)  {
                        // Move to next line
                        $this->x =$this->lMargin;
                        $this->y +=$h;
                        $w       = $this->w - $this->rMargin - $this->x;
                        $wmax    =($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                        $i++;
                        $nl++;
                        continue;
                    }
                    if ($i == $j) {
                        $i++;
                    }
                    $this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, '', 0, $link);
                }
                else {
                    $this->Cell($w, $h, substr($s, $j, $sep - $j), 0, 2, '', 0, $link);
                    $i = $sep + 1;
                } // end if... else...

                $sep         = -1;
                $j           = $i;
                $l           = 0;
                if ($nl == 1) {
                    $this->x = $this->lMargin;
                    $w       = $this->w - $this->rMargin - $this->x;
                    $wmax    = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
                }
                $nl++;
            }
            else {
                $i++;
            } // end if... else...
        } // end while

        // Last chunk
        if ($i != $j) {
            $this->Cell($l / 1000 * $this->FontSize, $h, substr($s, $j, $i), 0, 0, '', 0, $link);
        }
    } // end of the "Write()" method


    /**
     * Puts an image on the page
     *
     * @param   string   The image file (JPEG or PNG format)
     * @param   double   The top left x position
     * @param   double   The top left y position
     * @param   double   The image width
     * @param   double   The image height
     * @param   string   The image type (JPG, JPEG or PNG)
     * @param   mixed    The link id or an url
     *
     * @access  public
     */
    function Image($file, $x, $y, $w, $h = 0, $type = '', $link = '')
    {
        if (!isset($this->images[$file])) {
            // First use of image, get info
            if ($type == '') {
                $pos = strrpos($file, '.');
                if (!$pos) {
                    $this->Error('Image file has no extension and no type was specified: ' . $file);
                }
                $type = substr($file, $pos + 1);
            } // end if

            $type = strtolower($type);
            $mqr  = get_magic_quotes_runtime();
            set_magic_quotes_runtime(0);
            if ($type == 'jpg' || $type == 'jpeg') {
                $info = $this->_parsejpg($file);
            }
            else if ($type == 'png') {
                $info = $this->_parsepng($file);
            }
            else {
                $this->Error('Unsupported image file type: ' . $type);
            }
            set_magic_quotes_runtime($mqr);
            $info['i']           = count($this->images) + 1;
            $this->images[$file] = $info;
        }
        else {
            $info                = $this->images[$file];
        } // end if... else...

        // Automatic width or height calculation
        if ($w == 0) {
            $w = $h * $info['w'] / $info['h'];
        }
        if ($h == 0) {
            $h = $w * $info['h'] / $info['w'];
        }
        $this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']));

        if ($link) {
            $this->Link($x, $y, $w, $h, $link);
        }
    } // end of the "Image()" method


    /**
     * Appends a line feed
     *
     * @param   double   The line height
     *
     * @access  public
     */
    function Ln($h = '')
    {
        $this->x = $this->lMargin;
        // Sets default line height to last cell height
        if (is_string($h)) {
            $this->y += $this->lasth;
        }
        else {
            $this->y += $h;
        }
    } // end of the "Ln()" method


    /**
     * Gets x position
     *
     * @return  double  The x position
     *
     * @access  public
     */
    function GetX()
    {
        return $this->x;
    } // end of the "GetX()" method


    /**
     * Sets x position
     *
     * @param   double  The x position
     *
     * @access  public
     */
    function SetX($x)
    {
        if ($x >= 0) {
            $this->x = $x;
        } else {
            $this->x = $this->w + $x;
        }
    } // end of the "SetX()" method


    /**
     * Gets y position
     *
     * @return  double  The y position
     *
     * @access  public
     */
    function GetY()
    {
        return $this->y;
    } // end of the "GetY()" method


    /**
     * Sets y position and resets x
     *
     * @param   double  The y position
     *
     * @access  public
     */
    function SetY($y)
    {
        $this->x = $this->lMargin;
        if ($y >= 0) {
            $this->y = $y;
        } else {
            $this->y = $this->h + $y;
        }
    } // end of the "SetY()" method


    /**
     * Sets x and y positions
     *
     * @param   double  The x position
     * @param   double  The y position
     *
     * @access  public
     */
    function SetXY($x,$y)
    {
        $this->SetY($y);
        $this->SetX($x);
    } // end of the "SetXY()" method


    /**
     * Outputs PDF to file or browser
     *
     * @param   string   The file name
     * @param   boolean  Whether to display the document inside the browser
     *                   (with Acrobat plugin), to enforce download as file or
     *                   to save it on the server
     *
     * @global  string   The browser id string
     *
     * @access  public
     */
    function Output($file = '', $download = FALSE)
    {
        global $HTTP_USER_AGENT;

        if ($this->state < 3) {
            $this->Close();
        }

        // Send to browser
        if ($file == '') {
            header('Content-Type: application/pdf');
            if (headers_sent()) {
                $this->Error('Some data has already been output to browser, can\'t send PDF file');
            }
            header('Content-Length: ' . strlen($this->buffer));
            header('Content-Disposition: inline; filename=doc.pdf');
            echo $this->buffer;
        }
        // Download file
        else if ($download) {
            if (!empty($HTTP_USER_AGENT)
                && (strpos($HTTP_USER_AGENT, 'MSIE 5.5') || strpos($HTTP_USER_AGENT, 'Opera'))) {
                header('Content-Type: application/dummy');
            }
            // fix for Gecko-based browsers < 1.1
            else if (!empty($HTTP_USER_AGENT)
                  &&  (strpos($HTTP_USER_AGENT, 'Gecko') &&
                  (strpos($HTTP_USER_AGENT, 'rv:0.') || strpos($HTTP_USER_AGENT, 'rv:1.0') || strpos($HTTP_USER_AGENT, 'rv:1.1')))) {
                     header('Content-Type: application/');
            }
            else {
                header('Content-Type: application/pdf');
            }
            if (headers_sent()) {
                $this->Error('Some data has already been output to browser, can\'t send PDF file');
            }
            header('Content-Length: ' . strlen($this->buffer));
            header('Content-Disposition: attachment; filename=' . $file);
            echo $this->buffer;
        }
        // Save file locally
        else {
            $f = fopen($file, 'wb');
            if (!$f) {
                $this->Error('Unable to create output file: ' . $file);
            }
            fwrite($f, $this->buffer, strlen($this->buffer));
            fclose($f);
        } // end if... else if... else
    } // end of the "Output()" method

} // End of the "FPDF" class



/**
 * Handles silly IE contype request
 */
if (!empty($_ENV) && isset($_ENV['HTTP_USER_AGENT'])) {
    $HTTP_USER_AGENT = $_ENV['HTTP_USER_AGENT'];
}
else if (!empty($_SERVER) && isset($_SERVER['HTTP_USER_AGENT'])) {
    $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
}
else if (@getenv('HTTP_USER_AGENT')) {
    $HTTP_USER_AGENT = getenv('HTTP_USER_AGENT');
}

if ($HTTP_USER_AGENT == 'contype') {
    header('Content-Type: application/pdf');
    exit();
}
?>