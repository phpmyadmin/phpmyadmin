<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains the PMA_Footnote and the PMA_Footnotes classes
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Used by PMA_Footer, this class manages footnotes
 *
 * @package PhpMyAdmin
 */
class PMA_Footnotes
{
    /**
     * An array of footnotes
     *
     * @access private
     * @var array
     */
    private $_footnotes;

    /**
     * Generates new PMA_Footnotes objects
     *
     * @return PMA_Footnotes object
     */
    public function __construct()
    {
        $this->_footnotes = array();
    }

    /**
     * Setter for the ID attribute in the BODY tag
     *
     * @param mixed $message The message to be used for the footnote.
     *                       Can be a string or a PMA_Message object.
     *
     * @return string The marker to be displayed near the element
     *                that is being referenced by the footnote
     */
    public function add($message)
    {
        if ($message instanceof PMA_Message) {
            $key     = $message->getHash();
            $message = $message->getDisplay();
        } else {
            $key = md5($message);
        }

        if (! isset($this->_footnotes[$key])) {
            $id = count($this->_footnotes) + 1;
            $this->_footnotes[$key] = new PMA_Footnote(
                $id,
                $message
            );
        }
        return $this->_footnotes[$key]->getMarker();
    }

    /**
     * Renders the footnotes
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval = '';
        if (count($this->_footnotes)) {
            $retval .= '<div class="footnotes">';
            foreach ($this->_footnotes as $footnote) {
                $retval .= $footnote->getDisplay();
            }
            $retval .= '</div>';
        }
        return $retval;
    }
}

/**
 * Each object of this class represents a footnote
 * Used by PMA_Footnotes
 *
 * @package PhpMyAdmin
 */
class PMA_Footnote
{
    /**
     * Footnote identifier
     *
     * @var int
     * @access private
     */
    private $_id;
    /**
     * The message to be used for the footnote
     *
     * @var string
     * @access private
     */
    private $_message;

    /**
     * Generates new PMA_Footnotes objects
     *
     * @param int    $id      Footnote identifier
     * @param string $message The message to be used for the footnote
     *
     * @return PMA_Footnote object
     */
    public function __construct($id, $message)
    {
        $this->_id      = $id;
        $this->_message = $message;
    }

    /**
     * Returns the marker to be displayed near the element
     * that is being referenced by this footnote
     *
     * @return string
     */
    public function getMarker()
    {
        $retval  = '<sup class="footnotemarker">' . $this->_id . '</sup>';
        $retval .= PMA_getImage(
            'b_help.png',
            '',
            array('class' => 'footnotemarker footnote_' . $this->_id)
        );
        return $retval;
    }

    /**
     * Renders the footnote
     *
     * @return string
     */
    public function getDisplay()
    {
        $retval  = '<span id="footnote_' . $this->_id . '">';
        $retval .= '<sup>' . $this->_id . '</sup> ';
        $retval .= $this->_message;
        $retval .= '</span><br />';
        return $retval;
    }
}

?>
