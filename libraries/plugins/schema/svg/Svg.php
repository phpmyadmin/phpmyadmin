<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in SVG format.
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\plugins\schema\svg;

use PMA;
use XMLWriter;

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of SVG Schema Export
 *
 * @package PhpMyAdmin
 * @access  public
 * @see     https://php.net/manual/en/book.xmlwriter.php
 */
class Svg extends XMLWriter
{
    public $title;
    public $author;
    public $font;
    public $fontSize;

    /**
     * The "PMA\libraries\plugins\schema\svg\Svg" constructor
     *
     * Upon instantiation This starts writing the RelationStatsSvg XML document
     *
     * @see XMLWriter::openMemory(),XMLWriter::setIndent(),XMLWriter::startDocument()
     */
    public function __construct()
    {
        $this->openMemory();
        /*
         * Set indenting using three spaces,
         * so output is formatted
         */

        $this->setIndent(true);
        $this->setIndentString('   ');
        /*
         * Create the XML document
         */

        $this->startDocument('1.0', 'UTF-8');
        $this->startDtd(
            'svg',
            '-//W3C//DTD SVG 1.1//EN',
            'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
        );
        $this->endDtd();
    }

    /**
     * Set document title
     *
     * @param string $value sets the title text
     *
     * @return void
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * Set document author
     *
     * @param string $value sets the author
     *
     * @return void
     */
    public function setAuthor($value)
    {
        $this->author = $value;
    }

    /**
     * Set document font
     *
     * @param string $value sets the font e.g Arial, Sans-serif etc
     *
     * @return void
     */
    public function setFont($value)
    {
        $this->font = $value;
    }

    /**
     * Get document font
     *
     * @return string returns the font name
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set document font size
     *
     * @param string $value sets the font size in pixels
     *
     * @return void
     */
    public function setFontSize($value)
    {
        $this->fontSize = $value;
    }

    /**
     * Get document font size
     *
     * @return string returns the font size
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Starts RelationStatsSvg Document
     *
     * svg document starts by first initializing svg tag
     * which contains all the attributes and namespace that needed
     * to define the svg document
     *
     * @param integer $width  total width of the RelationStatsSvg document
     * @param integer $height total height of the RelationStatsSvg document
     * @param integer $x      min-x of the view box
     * @param integer $y      min-y of the view box
     *
     * @return void
     *
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute()
     */
    public function startSvgDoc($width, $height, $x = 0, $y = 0)
    {
        $this->startElement('svg');

        if (!is_int($width)) {
            $width = intval($width);
        }

        if (!is_int($height)) {
            $height = intval($height);
        }

        if ($x != 0 || $y != 0) {
            $this->writeAttribute('viewBox', "$x $y $width $height");
        }
        $this->writeAttribute('width', ($width - $x) . 'px');
        $this->writeAttribute('height', ($height - $y) . 'px');
        $this->writeAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $this->writeAttribute('version', '1.1');
    }

    /**
     * Ends RelationStatsSvg Document
     *
     * @return void
     * @see XMLWriter::endElement(),XMLWriter::endDocument()
     */
    public function endSvgDoc()
    {
        $this->endElement();
        $this->endDocument();
    }

    /**
     * output RelationStatsSvg Document
     *
     * svg document prompted to the user for download
     * RelationStatsSvg document saved in .svg extension and can be
     * easily changeable by using any svg IDE
     *
     * @param string $fileName file name
     *
     * @return void
     * @see XMLWriter::startElement(),XMLWriter::writeAttribute()
     */
    public function showOutput($fileName)
    {
        //ob_get_clean();
        $output = $this->flush();
        PMA\libraries\Response::getInstance()
            ->disable();
        PMA_downloadHeader(
            $fileName,
            'image/svg+xml',
            strlen($output)
        );
        print $output;
    }

    /**
     * Draws RelationStatsSvg elements
     *
     * SVG has some predefined shape elements like rectangle & text
     * and other elements who have x,y co-ordinates are drawn.
     * specify their width and height and can give styles too.
     *
     * @param string     $name   RelationStatsSvg element name
     * @param int        $x      The x attr defines the left position of the element
     *                           (e.g. x="0" places the element 0 pixels from the
     *                           left of the browser window)
     * @param integer    $y      The y attribute defines the top position of the
     *                           element (e.g. y="0" places the element 0 pixels
     *                           from the top of the browser window)
     * @param int|string $width  The width attribute defines the width the element
     * @param int|string $height The height attribute defines the height the element
     * @param string     $text   The text attribute defines the text the element
     * @param string     $styles The style attribute defines the style the element
     *                           styles can be defined like CSS styles
     *
     * @return void
     *
     * @see XMLWriter::startElement(), XMLWriter::writeAttribute(),
     * XMLWriter::text(), XMLWriter::endElement()
     */
    public function printElement(
        $name,
        $x,
        $y,
        $width = '',
        $height = '',
        $text = '',
        $styles = ''
    ) {
        $this->startElement($name);
        $this->writeAttribute('width', $width);
        $this->writeAttribute('height', $height);
        $this->writeAttribute('x', $x);
        $this->writeAttribute('y', $y);
        $this->writeAttribute('style', $styles);
        if (isset($text)) {
            $this->writeAttribute('font-family', $this->font);
            $this->writeAttribute('font-size', $this->fontSize);
            $this->text($text);
        }
        $this->endElement();
    }

    /**
     * Draws RelationStatsSvg Line element
     *
     * RelationStatsSvg line element is drawn for connecting the tables.
     * arrows are also drawn by specify its start and ending
     * co-ordinates
     *
     * @param string  $name   RelationStatsSvg element name i.e line
     * @param integer $x1     Defines the start of the line on the x-axis
     * @param integer $y1     Defines the start of the line on the y-axis
     * @param integer $x2     Defines the end of the line on the x-axis
     * @param integer $y2     Defines the end of the line on the y-axis
     * @param string  $styles The style attribute defines the style the element
     *                        styles can be defined like CSS styles
     *
     * @return void
     *
     * @see XMLWriter::startElement(), XMLWriter::writeAttribute(),
     * XMLWriter::endElement()
     */
    public function printElementLine($name, $x1, $y1, $x2, $y2, $styles)
    {
        $this->startElement($name);
        $this->writeAttribute('x1', $x1);
        $this->writeAttribute('y1', $y1);
        $this->writeAttribute('x2', $x2);
        $this->writeAttribute('y2', $y2);
        $this->writeAttribute('style', $styles);
        $this->endElement();
    }
}
