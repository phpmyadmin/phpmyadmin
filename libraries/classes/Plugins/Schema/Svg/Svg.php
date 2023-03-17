<?php
/**
 * Classes to create relation schema in SVG format.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Schema\Svg;

use XMLWriter;

use function is_string;
use function sprintf;

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of SVG Schema Export
 *
 * @see     https://www.php.net/manual/en/book.xmlwriter.php
 */
class Svg extends XMLWriter
{
    public string $title = '';

    public string $author = 'phpMyAdmin';

    public string $font = 'Arial';

    public int $fontSize = 12;

    /**
     * Upon instantiation This starts writing the RelationStatsSvg XML document
     *
     * @see XMLWriter::openMemory()
     * @see XMLWriter::setIndent()
     * @see XMLWriter::startDocument()
     */
    public function __construct()
    {
        $this->openMemory();
        // Set indenting using three spaces, so output is formatted
        $this->setIndent(true);
        $this->setIndentString('   ');
        // Create the XML document
        $this->startDocument('1.0', 'UTF-8');
        $this->startDtd('svg', '-//W3C//DTD SVG 1.1//EN', 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
        $this->endDtd();
    }

    /**
     * Set document title
     *
     * @param string $value sets the title text
     */
    public function setTitle(string $value): void
    {
        $this->title = $value;
    }

    /**
     * Set document author
     *
     * @param string $value sets the author
     */
    public function setAuthor(string $value): void
    {
        $this->author = $value;
    }

    /**
     * Set document font
     *
     * @param string $value sets the font e.g Arial, Sans-serif etc
     */
    public function setFont(string $value): void
    {
        $this->font = $value;
    }

    /**
     * Get document font
     *
     * @return string returns the font name
     */
    public function getFont(): string
    {
        return $this->font;
    }

    /**
     * Set document font size
     *
     * @param int $value sets the font size in pixels
     */
    public function setFontSize(int $value): void
    {
        $this->fontSize = $value;
    }

    /**
     * Get document font size
     *
     * @return int returns the font size
     */
    public function getFontSize(): int
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
     * @see XMLWriter::startElement()
     * @see XMLWriter::writeAttribute()
     *
     * @param int|float $width  total width of the RelationStatsSvg document
     * @param int|float $height total height of the RelationStatsSvg document
     * @param int|float $x      min-x of the view box
     * @param int|float $y      min-y of the view box
     */
    public function startSvgDoc(int|float $width, int|float $height, int|float $x = 0, int|float $y = 0): void
    {
        $this->startElement('svg');

        $width = (int) $width;
        $height = (int) $height;

        if ($x != 0 || $y != 0) {
            $this->writeAttribute('viewBox', sprintf('%d %d %d %d', $x, $y, $width, $height));
        }

        $this->writeAttribute('width', ($width - $x) . 'px');
        $this->writeAttribute('height', ($height - $y) . 'px');
        $this->writeAttribute('xmlns', 'http://www.w3.org/2000/svg');
        $this->writeAttribute('version', '1.1');
    }

    /**
     * Ends RelationStatsSvg Document
     *
     * @see XMLWriter::endElement()
     * @see XMLWriter::endDocument()
     */
    public function endSvgDoc(): void
    {
        $this->endElement();
        $this->endDocument();
    }

    public function getOutputData(): string
    {
        $data = $this->flush();

        return is_string($data) ? $data : '';
    }

    /**
     * Draws RelationStatsSvg elements
     *
     * SVG has some predefined shape elements like rectangle & text
     * and other elements who have x,y co-ordinates are drawn.
     * specify their width and height and can give styles too.
     *
     * @see XMLWriter::startElement()
     * @see XMLWriter::writeAttribute()
     * @see XMLWriter::text()
     * @see XMLWriter::endElement()
     *
     * @param string      $name   RelationStatsSvg element name
     * @param int         $x      The x attr defines the left position of the element
     *                            (e.g. x="0" places the element 0 pixels from the
     *                            left of the browser window)
     * @param int         $y      The y attribute defines the top position of the
     *                            element (e.g. y="0" places the element 0 pixels
     *                            from the top of the browser window)
     * @param int|float   $width  The width attribute defines the width the element
     * @param int         $height The height attribute defines the height the element
     * @param string|null $text   The text attribute defines the text the element
     * @param string      $styles The style attribute defines the style the element
     *                            styles can be defined like CSS styles
     */
    public function printElement(
        string $name,
        int $x,
        int $y,
        int|float $width,
        int $height,
        string|null $text = '',
        string $styles = '',
    ): void {
        $this->startElement($name);
        $this->writeAttribute('width', (string) $width);
        $this->writeAttribute('height', (string) $height);
        $this->writeAttribute('x', (string) $x);
        $this->writeAttribute('y', (string) $y);
        $this->writeAttribute('style', $styles);
        if (isset($text)) {
            $this->writeAttribute('font-family', $this->font);
            $this->writeAttribute('font-size', $this->fontSize . 'px');
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
     * @see XMLWriter::startElement()
     * @see XMLWriter::writeAttribute()
     * @see XMLWriter::endElement()
     *
     * @param string $name   RelationStatsSvg element name i.e line
     * @param int    $x1     Defines the start of the line on the x-axis
     * @param int    $y1     Defines the start of the line on the y-axis
     * @param int    $x2     Defines the end of the line on the x-axis
     * @param int    $y2     Defines the end of the line on the y-axis
     * @param string $styles The style attribute defines the style the element
     *                       styles can be defined like CSS styles
     */
    public function printElementLine(string $name, int $x1, int $y1, int $x2, int $y2, string $styles): void
    {
        $this->startElement($name);
        $this->writeAttribute('x1', (string) $x1);
        $this->writeAttribute('y1', (string) $y1);
        $this->writeAttribute('x2', (string) $x2);
        $this->writeAttribute('y2', (string) $y2);
        $this->writeAttribute('style', $styles);
        $this->endElement();
    }
}
