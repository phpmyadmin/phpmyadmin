<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Fake response stub for testing purposes
 *
 * It will concatenate HTML and JSON for given calls to addHTML and addJSON
 * respectively, what make it easy to determine whether the output is correct in test
 * suite. Feel free to modify for any future test needs.
 *
 * @package    PhpMyAdmin
 * @subpackage Stubs
 */
namespace PhpMyAdmin\Tests\Stubs;

use PhpMyAdmin\Header;
use PhpMyAdmin\Message;

/**
 * Class Response
 *
 * @package PhpMyAdmin\Tests\Stubs
 */
class Response
{
    /**
     * PhpMyAdmin\Header instance
     *
     * @access private
     * @var Header
     */
    protected $header;

    /**
     * HTML data to be used in the response
     *
     * @access private
     * @var string
     */
    protected $htmlString;

    /**
     * An array of JSON key-value pairs
     * to be sent back for ajax requests
     *
     * @access private
     * @var array
     */
    protected $json;

    /**
     * Whether there were any errors during the processing of the request
     * Only used for ajax responses
     *
     * @access private
     * @var bool
     */
    protected $_isSuccess;

    /**
     * Whether we are servicing an ajax request.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isSuccess = true;
        $this->htmlString = '';
        $this->json = array();
        $this->_isAjax = false;

        $GLOBALS['lang'] = 'en';
        $this->header = new Header();
    }

    /**
     * Add HTML code to the response stub
     *
     * @param string $content A string to be appended to
     *                        the current output buffer
     *
     * @return void
     */
    public function addHTML($content)
    {
        if (is_array($content)) {
            foreach ($content as $msg) {
                $this->addHTML($msg);
            }
        } elseif ($content instanceof Message) {
            $this->htmlString .= $content->getDisplay();
        } else {
            $this->htmlString .= $content;
        }
    }

    /**
     * Add JSON code to the response stub
     *
     * @param mixed $json  Either a key (string) or an
     *                     array or key-value pairs
     * @param mixed $value Null, if passing an array in $json otherwise
     *                     it's a string value to the key
     *
     * @return void
     */
    public function addJSON($json, $value = null)
    {
        if (is_array($json)) {
            foreach ($json as $key => $value) {
                $this->addJSON($key, $value);
            }
        } else {
            if ($value instanceof Message) {
                $this->json[$json] = $value->getDisplay();
            } else {
                $this->json[$json] = $value;
            }
        }
    }

    /**
     * Return the final concatenated HTML string
     *
     * @return string
     */
    public function getHTMLResult()
    {
        return $this->htmlString;
    }

    /**
     * Return the final JSON array
     *
     * @return array
     */
    public function getJSONResult()
    {
        return $this->json;
    }

    /**
     * Current I choose to return PhpMyAdmin\Header object directly because
     * our test has nothing about the Scripts and PhpMyAdmin\Header class.
     *
     * @return Header
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set the status of an ajax response,
     * whether it is a success or an error
     *
     * @param bool $state Whether the request was successfully processed
     *
     * @return void
     */
    public function setRequestStatus($state)
    {
        $this->_isSuccess = $state;
    }

    /**
     * Get the status of an ajax response.
     *
     * @return bool
     */
    public function getSuccessSate()
    {
        return $this->_isSuccess;
    }

    /**
     * This function is used to clear all data to this
     * stub after any operations.
     *
     * @return void
     */
    public function clear()
    {
        $this->_isSuccess = true;
        $this->json = array();
        $this->htmlString = '';
    }

    /**
     * Set the ajax flag to indicate whether
     * we are servicing an ajax request
     *
     * @param bool $isAjax Whether we are servicing an ajax request
     *
     * @return void
     */
    public function setAjax($isAjax)
    {
        $this->_isAjax = (boolean) $isAjax;
    }

    /**
     * Returns true or false depending on whether
     * we are servicing an ajax request
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->_isAjax;
    }
}
