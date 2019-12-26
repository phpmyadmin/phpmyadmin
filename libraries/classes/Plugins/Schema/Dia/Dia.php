<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in Dia format.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\Schema\Dia;

use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use XMLWriter;

/**
 * This Class inherits the XMLwriter class and
 * helps in developing structure of DIA Schema Export
 *
 * @package PhpMyAdmin
 * @access  public
 * @see     https://www.php.net/manual/en/book.xmlwriter.php
 */
class Dia extends XMLWriter
{
    /**
     * The "Dia" constructor
     *
     * Upon instantiation This starts writing the Dia XML document
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
    }

    /**
     * Starts Dia Document
     *
     * dia document starts by first initializing dia:diagram tag
     * then dia:diagramdata contains all the attributes that needed
     * to define the document, then finally a Layer starts which
     * holds all the objects.
     *
     * @param string $paper        the size of the paper/document
     * @param float  $topMargin    top margin of the paper/document in cm
     * @param float  $bottomMargin bottom margin of the paper/document in cm
     * @param float  $leftMargin   left margin of the paper/document in cm
     * @param float  $rightMargin  right margin of the paper/document in cm
     * @param string $orientation  orientation of the document, portrait or landscape
     *
     * @return void
     *
     * @access public
     * @see    XMLWriter::startElement(),XMLWriter::writeAttribute(),
     *      XMLWriter::writeRaw()
     */
    public function startDiaDoc(
        $paper,
        $topMargin,
        $bottomMargin,
        $leftMargin,
        $rightMargin,
        $orientation
    ) {
        if ($orientation == 'P') {
            $isPortrait = 'true';
        } else {
            $isPortrait = 'false';
        }
        $this->startElement('dia:diagram');
        $this->writeAttribute('xmlns:dia', 'http://www.lysator.liu.se/~alla/dia/');
        $this->startElement('dia:diagramdata');
        $this->writeRaw(
            '<dia:attribute name="background">
              <dia:color val="#ffffff"/>
            </dia:attribute>
            <dia:attribute name="pagebreak">
              <dia:color val="#000099"/>
            </dia:attribute>
            <dia:attribute name="paper">
              <dia:composite type="paper">
                <dia:attribute name="name">
                  <dia:string>#' . $paper . '#</dia:string>
                </dia:attribute>
                <dia:attribute name="tmargin">
                  <dia:real val="' . $topMargin . '"/>
                </dia:attribute>
                <dia:attribute name="bmargin">
                  <dia:real val="' . $bottomMargin . '"/>
                </dia:attribute>
                <dia:attribute name="lmargin">
                  <dia:real val="' . $leftMargin . '"/>
                </dia:attribute>
                <dia:attribute name="rmargin">
                  <dia:real val="' . $rightMargin . '"/>
                </dia:attribute>
                <dia:attribute name="is_portrait">
                  <dia:boolean val="' . $isPortrait . '"/>
                </dia:attribute>
                <dia:attribute name="scaling">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="fitto">
                  <dia:boolean val="false"/>
                </dia:attribute>
              </dia:composite>
            </dia:attribute>
            <dia:attribute name="grid">
              <dia:composite type="grid">
                <dia:attribute name="width_x">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="width_y">
                  <dia:real val="1"/>
                </dia:attribute>
                <dia:attribute name="visible_x">
                  <dia:int val="1"/>
                </dia:attribute>
                <dia:attribute name="visible_y">
                  <dia:int val="1"/>
                </dia:attribute>
                <dia:composite type="color"/>
              </dia:composite>
            </dia:attribute>
            <dia:attribute name="color">
              <dia:color val="#d8e5e5"/>
            </dia:attribute>
            <dia:attribute name="guides">
              <dia:composite type="guides">
                <dia:attribute name="hguides"/>
                <dia:attribute name="vguides"/>
              </dia:composite>
            </dia:attribute>'
        );
        $this->endElement();
        $this->startElement('dia:layer');
        $this->writeAttribute('name', 'Background');
        $this->writeAttribute('visible', 'true');
        $this->writeAttribute('active', 'true');
    }

    /**
     * Ends Dia Document
     *
     * @return void
     * @access public
     * @see    XMLWriter::endElement(),XMLWriter::endDocument()
     */
    public function endDiaDoc()
    {
        $this->endElement();
        $this->endDocument();
    }

    /**
     * Output Dia Document for download
     *
     * @param string $fileName name of the dia document
     *
     * @return void
     * @access public
     * @see    XMLWriter::flush()
     */
    public function showOutput($fileName)
    {
        if (ob_get_clean()) {
            ob_end_clean();
        }
        $output = $this->flush();
        Response::getInstance()->disable();
        Core::downloadHeader(
            $fileName,
            'application/x-dia-diagram',
            strlen($output)
        );
        print $output;
    }
}
