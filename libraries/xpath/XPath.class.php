<?php
// +----------------------------------------------------------------------+
// | Php.XPath Version 2.2 stable                                         |
// +----------------------------------------------------------------------+
// | This is the major update and part rewrite of M.Mehls phpxml project  |
// | It is the product of the updates from Nigel Swinson side             |
// | branches (V1.N.x) that include a DOM like interface and              |
// | a major restucturing and rewriting done by Sam Blum as well as       |
// | contributions of others of the open source comunity.  As of October  |
// | 2001, M.Mehl recognized Php.XPath as the main branch and renaming of |
// | the original phpxml project, and phpxml has thus been expired as a   |
// | project name.                                                        |
// +----------------------------------------------------------------------+
// | The contents of this file are subject to the Mozilla Public License  |
// | Version 1.1 (the "License"); you may not use this file except in     |
// | compliance with the License. You may obtain a copy of the License at |
// | http://www.mozilla.org/MPL/                                          |
// |                                                                      |
// | Software distributed under the License is distributed on an "AS IS"  |
// | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
// | the License for the specific language governing rights and           |
// | limitations under the License.                                       |
// |                                                                      |
// | The Initial Developer of the Original Code is Michael P. Mehl.       |
// +----------------------------------------------------------------------+
// | Main Active Authors:                                                 |
// |   Nigel Swinson <nigelswinson@users.sourceforge.net>                 |
// |     Started around 2001-07 and creator of V1.N.x branches.           |
// |   Sam Blum <bs_php@infeer.com>                                       |
// |     Started around 2001-09 major restruct and testbench initiator.   |
// |   Daniel Allen <bigredlinux@yahoo.com>                               |
// |     Started around 2001-10 working to make Php.XPath adhere to specs |
// |   fab                                                                |
// |     minor changes, especially the return of status codes instead of  |
// |     echo and exit.                                                   |
// +----------------------------------------------------------------------+
// | Main Former Authors:                                                 |
// |   Michael P. Mehl <mpm@phpxml.org>                                   |
// |     Inital creator of V 1.0. Stoped activities around 2001-03        |
// +----------------------------------------------------------------------+
// | Requires PHP version  4.0.5 and up                                   |
// +----------------------------------------------------------------------+
// | Ref:                                                                 |
// |   @link http://sourceforge.net/projects/phpxpath/   Latest release   |
// |   @link http://www.w3.org/TR/xpath   W3C XPath Recommendation        |
// +----------------------------------------------------------------------+

//////////////////////////////////////////////////////////////////////////////////
// Class for accessing XML data through the XML Path Language
// XPath Version 1.0 defined by W3C
//
// This class offers methods for accessing the nodes of a XML document using 
// the XPath language. You can add or remove children (nodes), set or modify their 
// content and their attributes. No additional PHP extensions like DOM XML 
// or something similar are required to use these features.

class XPath {
  ////////////////////////////////////////////////////////////////////////////////
  //############### Old Public Interface #######################################//
  ////////////////////////////////////////////////////////////////////////////////

  // This is the interface that has been with us from version 1.0 - 1.N.4.  It's
  // a hotch potch of radomly named junk now, so is getting renamed to DOM style
  // function names.  
  // This interface will be expired after the 1.N.5, at first warning you and then
  // later stopping execution when still used. *So update your code*.
  // To turn warning/aborting on/off toggle the following flags.
  // But be warned: The interface will be flushed soon.
  var $deprecate_1N4_warning = TRUE;
  var $deprecate_1N4_abort = TRUE;

  /**
   * @deprecated  Use XPath() instead
   */
  function XML($fileName = '') {
    if ($this->deprecate_1N4_warning) {
      $this->_displayError("Class name 'XML' is deprecated sinc V1.N.5. Use XPath() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->XPath($fileName);
  }
  
  /**
   * @deprecated  Use importFromFile() instead
   */
  function load_file($fileName) {
    if ($this->deprecate_1N4_warning) {
      $this->_displayError("Methode 'load_file' is deprecated sinc V1.N.5. Use importFromFile() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->importFromFile($fileName);
  }
  
  /**
   * @deprecated  Use importFromString() instead
   */
  function load_string($xmlString) {
    if ($this->deprecate_1N4_warning) {
      $this->_displayError("Methode 'load_string' is deprecated sinc V1.N.5. Use importFromString() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->importFromString($xmlString);
  }
  
  /**
   * Given a context this function returns the containing XML
   *
   * This method takes a context, which is derived from the evaluate 
   * function, and it returns the XML that is contained within this
   * node
   *
   * @deprecated Use exportAsXml() instead
   * @param     array absoluteXPathArray an array of absolute XPath 
   *            addresses to nodes.
   * @param     int $index which of the results of the absoluteXPathArray 
   *            to use.
   * @return    string The string returned is valid XML
   * @see       exportAsXml(), evaluate()
   */
  function grab($absoluteXPathArray = '', $index = 0) {
    if ($this->deprecate_1N4_warning) {
      $this->_displayError("Methode 'grab' is deprecated sinc V1.N.5. Use exportAsXml() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->exportAsXml($absoluteXPathArray[$index]);
  }
   
  /**
   * @deprecated  Use exportAsXml() instead
   */
  function get_as_xml_file() {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_as_xml_file' is deprecated sinc V1.N.5. Use exportAsXml() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return '<?xml version="1.0"?>' . "\n" . $this->exportAsXml();
  }
   
  /**
   * @deprecated  Use exportAsHtml() instead
   */
  function get_as_html_file($highlight = array()) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_as_html_file' is deprecated sinc V1.N.5. Use exportAsHtml() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->exportAsHtml('', $highlight);
  }
    
  /**
   * @deprecated  Use appendChild() instead
   */
  function add_node($absoluteParentPath, $nodeName) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'add_node' is deprecated sinc V1.N.5. Use appendChild() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->appendChild($absoluteParentPath, $nodeName);
  }

  /**
   * @deprecated  Use removeChild() instead
   */
  function remove_node($absoluteXPath) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'remove_node' is deprecated sinc V1.N.5. Use removeChild() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->removeChild($absoluteXPath);
 }
  
  /**
   * @deprecated  Use appendData() instead
   */
  function add_content($absoluteXPath, $value ) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'add_content' is deprecated sinc V1.N.5. Use appendData() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    $this->_setContent(&$absoluteXPath, &$value, TRUE );
  }
  
  /**
   * @deprecated  Use replaceData() and appendData() instead
   */
  function set_content($absoluteXPath, $value, $append=FALSE) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'set_content' is deprecated sinc V1.N.5. Use replaceData() and appendData() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->_setContent(&$absoluteXPath, &$value, $append);
  }
  
  /**
   * @deprecated  Use substringData() instead
   */
  function get_content($absoluteXPath) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_content' is deprecated sinc V1.N.5. Use getData() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->substringData($absoluteXPath);
  }
  
  /**
   * Add attributes to a node.
   *
   * This method adds attributes to a node. Existing attributes *WILL BE*
   * overwritten unless $overwrite is set to FALSE.
   *
   * @deprecated  Use setAttributes() instead
   * @param     string $absoluteXPath Full document path of the node, the 
   *            attributes should be added to.  *READONLY*
   * @param     array $attributes Associative array containing the new
   *            attributes for the node.  *READONLY*
   * @param     bool $overwrite TRUE (=default): overwite attibutes / 
   *            FALSE:  Will not overwite existing attibutes *READONLY*
   * @see       getAttributes(), setAttributes(), removeAttributes()
   */
  function add_attributes($absoluteXPath, $attributes, $overwrite = TRUE) {
    if ($this->deprecate_1N4_warning) {
      $this->_displayError("Methode 'add_attributes' is deprecated sinc V1.N.5. Use setAttributes() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);    
    }
    // If overwrite is not set, then we must make sure that we don't overwrite any of
    // the existing attributes with the same name.
    if (!$overwrite) {
      $aExistingAttributes = $this->getAttributes($absoluteXPath);
      $aExistingAttributes = array_intersect($aExistingAttributes, $attributes);
      $aNewAttributes = $attributes;
      @reset($aExistingAttributes);
      while(list($name, $value) = each($aExistingAttributes)) {
        unset($aNewAttributes[$name]);
      return $this->setAttributes($absoluteXPath, $aNewAttributes);
    } else {
      return $this->setAttributes($absoluteXPath, $attributes);
    }
  } 
  
  /**
   * Sets the attributes of a node.
   *
   * This method sets the attributes of a node and overwrites all existing
   * attributes by doing this.
   *
   * @deprecated  Use setAttributes() instead
   * @param     string $absoluteXPath Full document path of the node, 
   *            the attributes of which should be set. *READONLY*
   * @param     array $attributes Associative array containing the new
   *            attributes for the node. *READONLY*
   * @see       getAttributes(), setAttributes(), removeAttributes()
   */
  function set_attributes ($absoluteXPath, $attributes) {
    // Set the attributes of the node.
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'set_attributes' is deprecated sinc V1.N.5. Use setAttributes() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    if (!is_array($attributes)) return;    

    // Remove the existing attributes
    if ($overwrite) $this->removeAttributes(array_keys($this->getAttributes()));
    return $this->setAttributes($absoluteXPath, $attributes);
  }
    
  /**
   * @deprecated  Use getAttributes() instead
   */
  function get_attributes($absoluteXPath) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_attributes' is deprecated sinc V1.N.5. Use getAttributes() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->getAttributes($absoluteXPath);
  }
    
  /**
   * @deprecated  Use nodeName() instead
   */
  function get_name($absoluteXPath) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_name' is deprecated sinc V1.N.5. Use nodeName() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->nodeName($absoluteXPath);
  }
  
  
  /**
   * @deprecated  Use nodeName() instead
   */
  function get_names($absoluteXPaths) {
    if ($this->deprecate_1N4_warning) {      
      $this->_displayError("Methode 'get_names' is deprecated sinc V1.N.5. Use nodeName() instead and read the doc.", __LINE__, $this->deprecate_1N4_abort);
    }
    return $this->nodeName($absoluteXPaths);
  }
  
  ////////////////////////////////////////////////////////////////////////////////
  //#################### Public Interface ######################################//
  ////////////////////////////////////////////////////////////////////////////////
  
  /////////////////////////////////////////////////
  // ########################################### //
  // Constructor of the class.
  
  /**
   * Constructor of the class
   *
   * This constructor initializes the class and, when a filename is given,
   * tries to read and parse the given file.
   * You may also set the XML parsing parameters with an array. 
   * E.g. $xmlOpt = array(XML_OPTION_CASE_FOLDING => FALSE);
   *
   * @param     $fileName string  Path and name of the file to read and parsed.
   * @param     $userXmlOptions array vector array of (<optionID>=><value>, <optionID>=><value>, ...)
   * @see       importFromFile(), importFromString()
   */
  function XPath($fileName='', $userXmlOptions=array()) {
    // Set the options for parsing the XML data.
    // Per default we want to keep spaces in the CDATA, as most people generally want to 
    // keep space, and you can call trim() but there's no untrim() is there? Francis Fillion <francisf@videotron.ca>
    $this->xmlOptions[XML_OPTION_CASE_FOLDING] = FALSE;
    $this->xmlOptions[XML_OPTION_SKIP_WHITE] = FALSE;
    // Don't use PHP's array_merge!
    reset($userXmlOptions);
    while (list($key) = each($userXmlOptions)) {
      $this->xmlOptions[$key] = $userXmlOptions[$key];
    }
    
    // Check whether a file was given.
    if (!empty($fileName)) {
      // Load the XML file.
      $this->importFromFile($fileName);
    }
  }
  
  /**
  * Returns the property/ies you want.
  * 
  * if $param is not given, all properties will be returned in a hash.
  * the following params are allowed:
  * - fileName
  * - hasContent
  * - caseFolding
  * - skipWhiteSpaces
  *
  * @author fab, sam
  * @param  string $param
  * @return mixed (string OR hash of all params)
  * @throws NULL on an unknown param.
  */
  function getProperties($param=NULL) {
    $properties = NULL;
    if (empty($properties)) {
      $properties = array(
        'fileName'        => $this->fileName, 
        'hasContent'      => $this->_objectHasContent(),
        'caseFolding'     => $this->xmlOptions[XML_OPTION_CASE_FOLDING],
        'skipWhiteSpaces' => $this->xmlOptions[XML_OPTION_SKIP_WHITE],
        'verboseLevel'    => $this->_verboseLevel
      );
    }
    
    if (empty($param)) return $properties;
    
    if (isSet($properties[$param])) {
      return $properties[$param];
    } else {
      return NULL;
    }
  }
  
  /**
  * returns the last occured error message.
  * @author fab
  * @access public
  * @return string (may be empty if there was no error at all)
  * @see    _setLastError(), _lastError
  */
  function getLastError() {
    return $this->_lastError;
  }
  
  
  /////////////////////////////////////////////////
  // ########################################### //
  // Input
  /**
   * Controls whether case-folding is enabled for this XML parser.
   *
   * In other words, when it comes to XML, case-folding simply means uppercasing
   * all tag- and attribute-names (NOT the content) if set to TRUE.
   *
   * @author    Sam Blum 
   * @param     $onOff bool (default TRUE) 
   */
   function setCaseFolding($onOff=TRUE) {
     $this->xmlOptions[XML_OPTION_CASE_FOLDING] = $onOff;
   }
   
  /**
   * Controls whether skip-white-spaces is enabled for this XML parser.
   *
   * In other words, when it comes to XML, skip-white-spaces will trim
   * the tag content (=the CDATA) 
   *
   * @author    Sam Blum 
   * @param     $onOff bool (default TRUE) 
   */
   function setSkipWhiteSpaces($onOff=TRUE) {
     $this->xmlOptions[XML_OPTION_SKIP_WHITE] = $onOff;
   }
   
  /**
   * xml_parser_set_option -- set options in an XML parser.
   *
   * See 'XML parser functions' in PHP doc
   *
   * @author    Sam Blum 
   * @param     $optionID int The option ID (e.g. XML_OPTION_SKIP_WHITE)
   * @param     $value int The option value.
   * @see XML parser functions in PHP doc
   */
   function setXmlOption($optionID, $value) {
     if (!is_numeric($optionID)) return;
     $this->xmlOptions[$optionID] = $value;
   }
   
  /**
   * Turn verbose (error) output ON or OFF
   * Pass a bool. TRUE to turn on, FALSE to turn off.
   * Pass a int >0 to reach higher levels of verbosity (for future use).
   *
   * @author    Sam Blum 
   * @param     mixed $levelOfVerbosity  (default is 0 = off)
   */
   function setVerbose($levelOfVerbosity = 1) {
     $level = -1;
     if ($levelOfVerbosity === TRUE) {
       $level = 1;
     } elseif ($levelOfVerbosity === FALSE) {
       $level = 0;
     } elseif (is_numeric($levelOfVerbosity)) {
       $level = $levelOfVerbosity;
     }
     if ($level >= 0) $this->_verboseLevel = $levelOfVerbosity;
   }
   

  /**
   * Reads a file and parses the XML data.
   *
   * This method reads the content of a XML file, tries to parse its
   * content and upon success stores the information retrieved from
   * the file into an array.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>, changes by fab
   * @param     string $fileName Path and name of the file to be read and parsed.
   * @return    bool TRUE on success, FALSE on failure (check getLastError())
   * @see       importFromString(), _getLastError(), 
   */
  function importFromFile($fileName) {
    // Remember file name. Used in error output to know in which file it happend
    $this->fileName = $fileName;
    // If we already have content, then complain.
    if ($this->_objectHasContent()) {
      $this->_displayError('importFromFile() called when this object already contains xml data. Use reset().', __LINE__, FALSE);
      return FALSE;
    }
    // Check whether the url exists or if the file exists and is readable.
    if ( preg_match(';^http(s)?://;', $fileName) ) {
      // Read the content of the url...this is really prone to errors, and we don't really
      // check for too many here...for now, suppressing both possible warnings...we need
      // to check if we get a none xml page or something of that nature in the future
      $content = @implode('', @file($fileName));
      if ( empty($content) ) {
        // Display an error message.
        $this->_displayError("In importFromFile(): The url '{$fileName}' could not be found or read.", __LINE__, FALSE);
        return FALSE;
      }
    } 
    elseif (!is_readable($fileName)) { // Read the content from the file
      $this->_displayError("In importFromFile(): File '{$fileName}' could not be found or read.", __LINE__, FALSE);
      return FALSE;
    }
    elseif (is_dir($fileName)) {
      $this->_displayError("In importFromFile(): '{$fileName}' is a directory.", __LINE__, FALSE);
      return FALSE;
    }    
    // Read the content of the file.
    $content = implode("", file($fileName));
    return $this->importFromString(&$content);
  }
  
  /**
   * Reads a string and parses the XML data.
   *
   * This method reads the content of a XML string, tries to parse its
   * content and upon success stores the information retrieved from
   * the string into an array.
   *
   * @author    Francis Fillion <ffillion@infotopie.com> modified from Michael P. Mehl <mpm@phpxml.org>, changes by fab
   * @param     string $xmlString name of the string to be read and parsed.
   * @return    bool TRUE on success, FALSE on failure (check getLastError())
   * @see       _handleStartElement(), _handleEndElement(),
   *            _handleCharacterData(), importFromFile()
   */
  function importFromString($xmlString) {
    // If we already have content, then complain.
    if ($this->_objectHasContent()) {
      $this->_displayError('importFromString() called when this object already contains xml data. Use reset().', __LINE__, FALSE);
      return FALSE;
    }
    // Check whether content has been read.
    if (empty($xmlString)) {
      $this->_displayError('This xml document (string) was empty', __LINE__, FALSE);
      return FALSE;
    }
    // Create an XML parser.
    $parser = xml_parser_create();
    
    // Set default XML parser options.
    if (is_array($this->xmlOptions)) {
      reset($this->xmlOptions);
      while (list($k) = each($this->xmlOptions)) {
        xml_parser_set_option($parser, $k, $this->xmlOptions[$k]);
      }
    }
    
    // Set the object for the parser.
    xml_set_object($parser, &$this);
    // Set the element handlers for the parser.
    xml_set_element_handler($parser, '_handleStartElement', '_handleEndElement');
    xml_set_character_data_handler($parser, '_handleCharacterData');
    xml_set_default_handler($parser, '_handleDefaultData');

    // Set the document node.
    $this->nodes['']['xml-declaration'] = '';
    $this->nodes['']['dtd-declaration'] = '';

    // Parse the XML file.
    if (!xml_parse($parser, $xmlString, TRUE)) {
      //error
      $source = empty($this->fileName) ? 'string' : 'file ' . basename($this->fileName) . "'";
	    $this->_displayError("XML error in given {$source} on line ".
	           xml_get_current_line_number($parser). '  column '. xml_get_current_column_number($parser) .
             '. Reason:'. xml_error_string(xml_get_error_code($parser)), __LINE__ );
      return FALSE;
    }
    
    // Free the parser.
    xml_parser_free($parser);
    return TRUE;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Output

  /**
   * Given a context this function returns the containing XML as marked up HTML
   *
   * This method takes the absolute path to a node in the XML object
   * which is derived from the evaluate function, and it returns the 
   * XML that is contained within this node as a string as marked up
   * html suitable for outputing inline to an HTML file for display or
   * debugging reasons.  So <> etc are replaced by &lt; and &gt;
   * NOTE: Instead of an absolute path you may also pass a xpath query.
   *       If the result of the query leads to 1 unique node, then the
   *       path to that node is taken.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @param     string $absoluteXPath The path to the current node (see text above)
   * @return    string The string returned is valid XML
   * @throws    FALSE on error;
   * @see       exportAsXml(), exportToFile(), evaluate()
   */
  function &exportAsHtml($absoluteXPath = '', $highlight = array()) {
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    if (is_array($absoluteXPath)) {
        $this->_displayError('exportAsHtml() called with array as $absoluteXPath parameter.  '.
                                        'This is not supported', __LINE__, FALSE);
        return FALSE;
    }
    $level = 0;
    if (!empty($absoluteXPath)) {
      // Check that they gave us the path to one of the nodes.
      if (!isSet($this->nodes[$absoluteXPath])) {
        // Display an error message.
        $this->_displayError('exportAsHtml() called with $absoluteXPath parameter that does not '.
                    'describe a single node in the XML object.', __LINE__, FALSE);
        return FALSE;
      }
      $level = $this->nodes[$absoluteXPath]['doc-pos'];
    }
    return $this->_export($highlight, $absoluteXPath, $level, 0);
  }

  /**
   * Given a context this function returns the containing XML
   *
   * This method takes the absolute path to a node in the XML object
   * which is derived from the evaluate function, and it returns the 
   * XML that is contained within this node as a string.
   * NOTE: Instead of an absolute path you may also pass a xpath query.
   *       If the result of the query leads to 1 unique node, then the
   *       path to that node is taken.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @param     string $absoluteXPath The path to the current node (see text above)
   * @return    string The string returned is valid XML
   * @throws    FALSE on error;
   * @see       exportAsHtml(), exportToFile(), evaluate()
   */
  function &exportAsXml($absoluteXPath = '') {
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    $level = 0;
    if (!empty($absoluteXPath)) {
      $level = $this->nodes[$absoluteXPath]['doc-pos'];
    }
    return $this->_export(array(), $absoluteXPath, $level, 1);
  }

  /**
   * Generates a XML file with the content of the relevant portion of the current document.
   *
   * This method creates a string containing the XML data being read
   * and modified by this class before. This string can be used to save
   * a modified document back to a file or doing other nice things with
   * it.  It encludes a <?xml ...> tag at the start of the data too.
   * NOTE: Instead of an absolute path you may also pass a xpath query.
   *       If the result of the query leads to 1 unique node, then the
   *       path to that node is taken.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @param     string $fileName
   * @param     string $absoluteXPath The path to the current node (see text above)
   * @param     string $xmlHeader ( default is '< ? xml version="1.0" ? >' )
   * @return    string The returned string contains well-formed XML data
   *            representing the content of this document suitable for
   *            writing out to a file.
   * @throws    FALSE on error;
   * @see       importFromFile(), evaluate(), exportAsHtml()
   */
  function exportToFile($fileName, $absoluteXPath='', $xmlHeader='<?xml version="1.0"?>') {   
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    // Open the file and we'll write out to it.
    $hFile = fopen($fileName, "w");
    
    // Did we open the file ok?
    $bResult = TRUE;
    if (!$hFile) {
      $this->_displayError("Failed to open the $fileName xml file.", __LINE__, FALSE);
      $bResult = FALSE;		
    } else {
      // Lock the file
    if (!flock($hFile, LOCK_EX)) {
      $this->_displayError("Couldn't get an exclusive lock on the $fileName file.", __LINE__, FALSE);
      fclose($hFile);
      return FALSE;
    }

    // setup the header, either by what came from the dom object or the default one
    if ( preg_match(";<\?xml.*?\?>;",$this->nodes['']['xml-declaration']) )
      $xmlHeader = trim($this->nodes['']['xml-declaration']);
    if ( trim($this->nodes['']['dtd-declaration']) != '' )
      $xmlHeader .= "\n".trim($this->nodes['']['dtd-declaration']);
    // Get the relevant object as a string and write it to file
    $xmlString = $this->exportAsXml($absoluteXPath);
    if (!fwrite($hFile, $xmlHeader."\n".$xmlString)) {
      $this->_displayError("Write error when writing back the $fileName file.", __LINE__, FALSE);
      $bResult = FALSE;		
    }

      // Flush and unlock the file
      fflush($hFile);
      flock($hFile, LOCK_UN);

      if (!fclose($hFile)) {
        $this->_displayError("Failed to close the $fileNamefile.", __LINE__, FALSE);
        $bResult = FALSE;		
      }
    }
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Search.
  
  /**
   * Evaluates an XPath expression.
   *
   * This method tries to evaluate an XPath expression by parsing it. A
   * XML document has to be read before this method is able to work.
   *
   * @param     string $xPathQuery XPath expression to be evaluated.
   * @param     string $context Full path of a document node, starting
   *            from which the XPath expression should be evaluated.
   * @return    array The returned array contains a list of the full
   *            document paths of all nodes that match the evaluated
   *            XPath expression.
   * @throws    FALSE on error;
   */
  function &match($xPathQuery, $baseXPath='') {
    return $this->evaluate($xPathQuery, $baseXPath);
  }
  
  /**
   * Alias for the match function
   * 
   * @see       evaluate()
   */
  function &evaluate($xPathQuery, $baseXPath='') {
    // Starting point of the user sending an xPath query
    static $slashes2descendant = array('//@'=>'/descendant::*/attribute::', '//'=>'/descendant::', '/@'=>'/attribute::');
    
    if (empty($xPathQuery)) return array();

    // Numpty check
    if (!empty($baseXPath) && !isSet($this->nodes[$baseXPath])) {
      $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'],$absoluteXPath), __LINE__, FALSE);
      return FALSE;
    }
    
    // Convert all entities.
    $xPathQuery = strtr($xPathQuery, array_flip($this->entities));
   
    // Replace a double slashes, because they'll cause problems otherwise.
    $xPathQuery = strtr($xPathQuery, $slashes2descendant);
    
    /* By Dan Allen
      I know the evaluate function is under review right now and I realize
      that it is very complex...however, I thought of one out that should
      definitely be in place...if a user specifies the full path
      like 
          /root[1]/node[1]/child[1]
      it should just return immediately
        if(in_array($xPathQuery,array_keys($this->nodes))) {
          return array($xPathQuery);
        }
      why even look?
    */
    
    // Stupid idea from W3C to take axes name containing a '-' (dash)
    // Instead of the '-' in the names we use '_'.
    $xPathQuery = strtr($xPathQuery, $this->dash2underscoreHash);
    
    return $this->_internalEvaluate($xPathQuery, $baseXPath);
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Element Name access

  /**
   * Retrieves the names of a group of document nodes.
   *          
   * This method retrieves the names of a group of document nodes
   * specified in the argument.  So if the argument was '/A[1]/B[2]' then it
   * would return 'B' if the node did exist in the tree.
   *          
   * @param     array or string $absoluteXPath Array or single full document 
   *            path(s) of the node(s), from which the names should be 
   *            retrieved.
   * @return    array or string The returned array contains either an array 
   *            of the names of the specified nodes, or just the individual
   *            name.
   */
  function &nodeName($absoluteXPath) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction('nodeName');
      echo "Node: $absoluteXPath\n";
      echo "<hr>";
    }

    //////////////////////////////////

    // Did they ask for more than one name?
    $parmIsString = FALSE;
    $paths = null;
    if (is_string($absoluteXPath)) {
      $parmIsString = TRUE;
      $paths[] = $absoluteXPath;
    } else {
      $paths = &$absoluteXPath;
    }
    
    // Build the results array
    $names = array();
    $size = sizeOf($paths);
    // Get each name in turn.
    for ($i=0; $i<$size; $i++) {
      $path = &$paths[$i];
      // Numpty check
      // Check that the path exists
      if (isSet($this->nodes[$path])) {
        $names[] = $this->nodes[$path]['name'];
      } else {
        $this->_displayError("The path '$path' isn't a path of this class.", __LINE__, FALSE);                                
      }
    }
    if ($parmIsString) {
      if (sizeOf($names)) $result = $names[0]; else $result = '';
    } else {
      $result = $names;
    }

    //////////////////////////////////

    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    return $result;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Attribute modification

  /**
   * Retrieves a list of all attributes of a node.
   *
   * This method retrieves a list of all attributes of the node specified in
   * the argument.  If you only want the value of 1 attribute, then you may
   * specify that attribute as a parameter
   *
   * @param     string $absoluteXPath Full document path of the node, from 
   *            which the list of attributes should be retrieved. *READONLY*
   * @param     string $attribute the name of the attribute that you wish to
   *            retrieve or empty if you wish to retrive all of the attributes
   *            in an associative array. *READONLY*
   * @return    array or string The returned associative array contains the all
   *            attributes of the specified node, or the individual $attribute
   *            if that parameter was specified.
   * @throws    FALSE on error;
   * @see       removeAttribute()
   */
  function getAttributes($absoluteXPath, $attribute = '') {
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    // Check that there is a node.
    if (!isSet($this->nodes[$absoluteXPath])) {
      if (is_array($attribute))  return array();
      else                      return '';
    }
    // Get the attributes of the node.
    $aAttributes = isset($this->nodes[$absoluteXPath]['attributes'])
                      ? $this->nodes[$absoluteXPath]['attributes'] 
                      : array();
    // Return the complete list or just the desired element
    if (empty($attribute) or is_array($attribute))
      return $aAttributes;
    else 
      return $aAttributes[$attribute];
  }

  /**
   * Set attributes to a node.
   *
   * This method sets a number of attributes.  Existing attributes
   * overwritten with the new values, but existing attributes will not be
   * overwritten.
   *
   * @param     string $xPathQuery Full document path of the node, the attributes
   *            should be added to.
   * @param     array $attributes Associative array containing the new
   *            attributes for the node
   * @see       getAttribute(), removeAttribute()
   */
  function setAttribute($absoluteXPath, $name, $value) {
    return $this->setAttributes($absoluteXPath, array($name => $value));
  }

  /**
   * Version of setAttribute() that sets multiple attributes.
   *
   * NOTE: Instead of an absolute path you may also pass a xpath-query.
   *       All nodes that match the xpath query will be modified.
   *
   * @param     string $xPathQuery The path to the current node (see text above)
   * @param     array  $attributes associative array of attributes to set.
   * @see              setAttribute()
   */
  function setAttributes($xPathQuery, $attributes) {
    // The attributes parameter should be an associative array.
    if (!is_array($attributes)) return;
    
    // Numpty check
    $xpv = array();
    if (isSet($this->nodes[$xPathQuery])) {
      $xpv[] = &$xPathQuery;
    } else {
      // Try to evaluate the $xPathQuery; if it returns only 1 node use it; otherwise give up.
      $xpv = &$this->match($xPathQuery);
      if (sizeOf($xpv) ==0) {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $xPathQuery), __LINE__);
      }
    }
    
    $xpvSize = sizeOf($xpv);
    for ($k=0; $k < $xpvSize; $k++) {
      $absoluteXPath = &$xpv[$k];
      // Add the attributes to the node.
      if (isSet($this->nodes[$absoluteXPath]['attributes'])) {
        $this->nodes[$absoluteXPath]['attributes'] =
        array_merge($this->nodes[$absoluteXPath]['attributes'],$attributes);
      } else {
        $this->nodes[$absoluteXPath]['attributes'] = $attributes;
      }
    } // END for ($k=0; $k < $xpvSize; $k++) 
  }


  /**
   * Removes an attribute of a node.
   *
   * This method removes either a single, or a group of attributes from a node.
   *
   * @param     string $absoluteXPath Full document path of the node, from 
   *            which the list of attributes should be retrieved. *READONLY*
   * @param     string or array $attribute the name or names of the attribute(s)
   *            that you wish to remove.  If $attribute is empty, then all
   *            attributes will be removed for the node. *READONLY*
   * @see       getAttribute(), setAttribute()
   */
  function removeAttribute($absoluteXPath, $attribute = '') {
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__);
      }
    }
    
    // If the attribute parameter wasn't set then remove all the attributes
    if (!isSet($attribute)) {
      unset($this->nodes[$absoluteXPath]['attributes']);
      return;
    }
    
    // If the attribute parameter isn't an array then we have just to remove the 
    // one attribute
    if (!is_array($attribute)) {
      if (isset($this->nodes[$absoluteXPath]['attributes']))
        unset($this->nodes[$absoluteXPath]['attributes'][$attribute]);
      return;
    }
    
    // Remove all the elements in the array then.
    @reset($attribute);
    while(list(, $name) = each($attribute)) {
      unset($this->nodes[$absoluteXPath]['attributes'][$name]);
    }
    return;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Element Content modification

  /**
   * Retrieve all the text from a node as a single string.
   *
   * Simplified wholeText(). This function is not actually in any spec and is 
   * therefore more of a helper "shortcut."
   *
   * @author    Sam Blume <bs_php@infeer.com> and Daniel Allen <bigredlinux@yahoo.com>
   * @param     string $absoluteXPath Full document path of the node, from 
   *            which the text should be retrieved (any text() will be truncated). *READONLY*
   * @return    string The returned string contains either the value or the
   *            character data of the node.  If the node had mixed data, it concats all the
   *            text nodes into a single string
   * @see       wholeText()
   */
  function &getData($absoluteXPath) {
    // If this node had a text() node specified at the end, we want to truncate
    // text() part of the XPath, since this function is intended primarily for novice users
    if ( preg_match(":(.*)/text\(\)(\[(.*)\])?$:U",$absoluteXPath) ) {
      $absoluteXPath = substr($absoluteXPath,0,strrpos($absoluteXPath,"/text()"));
    }
    return $this->wholeText($absoluteXPath);
  }
  
  /**
   * Retrieves the normalized text of a node as an array of all the text() node contents.
   *
   * Retrieves the text content of a node as an array, where each element
   * of the array was interrupted by a non-text child element.  So if the node
   * was <a>1<b>2</b>3<c/>4</a> Then getDataParts('a[1]') would return ('1','3','4').
   * If the xpath terminated as a text() node, it will be truncated since this function
   * is intended primarily for novice users.
   *
   * @author    Sam Blume <bs_php@infeer.com> and Daniel Allen <bigredlinux@yahoo.com>
   * @param     string $absoluteXPath Full document path of the node, from 
   *            which the content should be retrieved as an array (xpath ending in a text()
   *            node will be truncated). *READONLY*
   * @return    array The returned array contains strings that are either the value or the
   *            character data of the node.
   * @throws    FALSE on error;
   * @see       getData(), wholeText()
   */
  function &getDataParts($absoluteXPath) {
    $text = array();
    // If this node had a text() node specified at the end, we want to truncate
    // text() part of the XPath, since this function is intended primarily for novice users
    if ( preg_match(":(.*)/text\(\)(\[(.*)\])?$:U",$absoluteXPath) ) {
      $absoluteXPath = substr($absoluteXPath,0,strrpos($absoluteXPath,"/text()"));
    }
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    // Return the cdata of the node.
    @reset($this->nodes[$absoluteXPath]['text']);
    while(list(, $textNode) = each($this->nodes[$absoluteXPath]['text'])) {
      $text[] = $textNode;
    }
    return $text;
  }

  /**
   * Retrieves and concats the contents of all the text nodes for a given parent
   *
   * In the recommendation for the XPath specification level 3, W3C makes the suggestion
   * for a function called wholeText() which returns all of the content of the text nodes
   * for a particular parent XPath, so as to eliminate only the non-text child nodes and
   * return the data...what I am not sure of, is if this is supposed to be recursive into
   * the text of the child nodes along side the text nodes of the original parent
   *
   * @author   Daniel Allen <bigredlinux@yahoo.com>
   * @param    string $absoluteXPath Full document path of the node, from 
   *           which the content should be retrieved. *READONLY*
   * @return   string The returned string contains the concat cdata of all of the
               text nodes
   * @see      getData(), getDataParts(), wholeText(), substringData()
  **/
  function wholeText($absoluteXPath) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;

    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction('wholeText');
      echo "Node: $absoluteXPath\n";
      echo "<hr>";
    }

    // Try block
    do {
      // if a specific text node was given, we actually just want the substringData function
      if ( preg_match(":text\(\)(\[\d*\])?$:",$absoluteXPath) ) {
        if ($bDebugThisFunction) echo "The xpath expression contained a :text() function.\n";
        $result = $this->substringData($absoluteXPath);
        break;
      }

      // if an attribute node was given, we actually just want the substringData function
      if ( preg_match(";(.*)/(attribute::|@)([^/]*)$;U",$absoluteXPath) ) {
        if ($bDebugThisFunction) echo "The xpath expression pointed to an attribute node.\n";
        $result = $this->substringData($absoluteXPath);
        break;
      }

      // Must have been just a node then.

      // Does it point direct to a single node?
      if ( !isSet($this->nodes[$absoluteXPath]) ) {
        if ($bDebugThisFunction) echo "The xpath expression isn't a node in the tree\n".
                            "Checking to see if it is an XPath expression for a single node.\n";
        // Try to evaluate the absoluteXPath (since it really isn't an absolutePath)
        $resultArr = $this->match($absoluteXPath);
        if ( sizeOf($resultArr) == 1 ) {
          $absoluteXPath = $resultArr[0];
        } 
        else {
          $this->_displayError("The $absoluteXPath does not evaluate to a single node in this document.", __LINE__, FALSE);
          $result = '';
          break;
        }
      } 

      if ($bDebugThisFunction) {
        echo "The xpath expression $absoluteXPath points to a single node in the tree with text nodes:\n";
        print_r($this->nodes[$absoluteXPath]['text']);
      }
      $result = implode('',$this->nodes[$absoluteXPath]['text']);
    } while (0);

    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    return $result;  
  }

  /**
   * Retrieves all or part of the content of a text node.
   *
   * This method retrieves the content of a specific text node. If it's an attribute
   * node, then the value of the attribute will be retrieved, otherwise
   * it'll be the character data of the node.  The node specified must be
   * a text node (if not an attribute), or else the first text node of the parent will be used.
   * The consideration just mentioned will only pertain to elements with mixed content, where
   * the text is divided by other non-text child nodes, such as in html data.  In that case,
   * these text nodes are actually distinct nodes called text() nodes.  Obviously, elements with
   * only text as a child will have only a single text() node.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org> modified by Daniel Allen <bigredlinux@yahoo.com>
   * @param     string $absoluteXPath Full document path of the text() node, from 
   *            which the content should be retrieved. *READONLY*
   * @param     number $offset Return the string starting at this offset. 
   *            *READONLY*
   * @param     number $count Return a maximum of count characters.  0 means 
   *            entire string. *READONLY*
   * @return    string The returned string contains either the cdata of the text node 
   * @see       wholeText(), getData(), getDataParts()
   */
  function &substringData($absoluteXPath, $offset = 0, $count = 0) {
    if ( $offset < 0 or !is_int($offset) ) $offset = 0;
    // we can just use _setContent and append nothing and then return it, same exact
    // code, no reason to write it twice
    return $this->_setContent($absoluteXPath, "", FALSE, $offset, $count);
  }

  /**
   * Set the content of a node
   *
   * This method sets the content of a node.  If it's an attribute node, then
   * the value of the attribute will be set, otherwise the character data of
   * the node will be set.  It throws an error if data is already present
   *
   * @param    string $absoluteXPath Full document path to the text node
   * @param    string $value String containing the context to be set
   * @see      appendData, replaceData(), deleteData()
   */
   function insertData($absoluteXPath, $value, $offset = 0) {
     if ( $offset < 0 or !is_int($offset) ) $offset = 0;
     $this->_setContent($absoluteXPath, $value, FALSE, $offset);
   }

   /**
    * Replace the content of a text() node.
    *
    * This method replaces the content of a node. If it's an attribute node, then
    * the value of the attribute will be set, otherwise the character data of
    * the node will be set. Existing content will be overwritten.
    *
    * @param     string $absoluteXPath Full document path of the text node. *READONLY*
    * @param     string $value String containing the content to be set. *READONLY*
    * @see       appendData(), deleteData()
    */
   function replaceData($absoluteXPath, $value, $offset = 0, $count = 0) {
     if ( $offset < 0 or !is_int($offset) ) $offset = 0;
     // TRUE for replace the data for the selection, offset and count self explanatory
     $this->_setContent($absoluteXPath, $value, TRUE, $offset, $count);
   }

  /**
   * Replace a node with an unprocessed (unparsed) text string.
   *
   * This function will delete the node you define by $absoluteXPath
   * (plus it's sub-nodes) and substitute it by the string $text.
   * Often used to push in not well formed HTML.
   * WARNING: 
   *     The $data is taken 1:1 (= not even an entity converting is done). 
   *     Your in charge that the data you enter is valid XML if you intend
   *     to export and import it again the content.
   * NOTE: Instead of an absolute path you may also pass a xpath-query.
   *       All nodes that match the xpath query will be modified.
   *
   * @author    Sam Blum
   * @param     string $xPathQuery path to the node (See text above). *READONLY*
   * @param     string $data String containing the content to be set. *READONLY*
   * @return    TRUE on success
   * @throws    FALSE on error;
   * @see       replaceChild()
   */
  function replaceChildByData($xPathQuery, $data) {
    // Numpty check
    $xpv = array();
    if (isSet($this->nodes[$xPathQuery])) {
      $xpv[] = &$xPathQuery;
    } else {
      // Try to evaluate the $xPathQuery; if it returns only 1 node use it; otherwise give up.
      $xpv = &$this->match($xPathQuery);
      if (sizeOf($xpv) ==0) {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $xPathQuery), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    $xpvSize = sizeOf($xpv);
    for ($k=0; $k < $xpvSize; $k++) {
      $absoluteXPath = &$xpv[$k];
      $theNode = &$this->nodes[$absoluteXPath];
      $contextPos = $theNode['context-pos'];
      $parentNode = &$this->nodes[$theNode['parent']];
      /**
      echo "** " .$contextPos ." **<br>";
      echo "** " . var_dump($parentNode['text']) ." **<br>";
      **/
      $parentNode['text'][$contextPos-1] .= $data;
      $this->removeChild($absoluteXPath, $keepSubnodes=FALSE);
    }
    return TRUE;
  }
  
  
  /**
   * Replaces a node in the xml document
   *
   * This function will replace the entire content of the specified node(s) with
   * the specified string.  The string may either be xml data that will be parsed
   * and inserted into the tree creating new grand children as necessary, or can 
   * be a plain text string that may be not well formed xml data and will be handled
   * as text.  Additionally to preserve the integrity of the xml structure facilitating 
   * export of the object you may specify that the string is to be treated as 
   * <![CDATA[ ]]>.
   *
   * examples:
   *    replaceChild('/root[1]/element[1]','<newelement><a><b/><b/></a></newelement>');
   *    replaceChild('/root[1]/element[1]','Html <br> Data', FALSE);
   *    replaceChild('/root[1]/element[1]','Html <br> Data that id like to be able '.
   *                                   'to export to file <hr> some day', FALSE, TRUE);
   *
   * NOTE: Instead of an absolute path you may also pass a xpath-query.
   *       All nodes that match the xpath query will be modified.
   * NOTE: If you replace as CData, then when you exportAsXml, you will need to pass
   *       the $bTrimCData parameter.
   * 
   * @param     string $xPathQuery Full document path of the node. *READONLY*
   * @param     string $xmlData String containing either the XML data or the
   *                non well formed string that will be inserted as is.  *READONLY*
   * @param     bool $asXml Flag that specifies whether xmlData holds XML data that
   *                will be parsed as xml and inserted, modifying the existing tree, 
   *                or whether it should just be replace as text content, unparsed.
   * @param     bool $asCData places <![CData[ ]]> tags round xmlData so that the class
   *                may be exported to file.
   */
  function replaceChild($absoluteXPath, $xmlData, $asXml=TRUE, $asCData=FALSE) {
    // #### Not realy implemented yet.
    
    /**--------------------------------**/ 
    /** --sam Following is only temp.  **/
    $this->replaceChildByData($absoluteXPath, $xmlData);
    $xmlData = &$this->exportAsXml();
    $this->reset();
    return $this->importFromString($xmlData);
    /**--------------------------------**/ 
     
     $this->_displayError("Not implemented yet", __LINE__);
  }

  /**
   * Appends an xml string to the end of the text section of a node.
   *
   * This function will append XML after a node defined by the parameter 
   * xPathQuery (that is a absoluteXPath or xpath-query).
   * NOTE: Instead of an absolute path you may also pass a xpath-query.
   *       All nodes that match the xpath query will be modified.
   * 
   * #### Remove appendXml() and put it's functionality into appendChild()
   *
   * Not optimized at all!! 
   *
   * @author    Sam Blum
   * @param     string $xPathQuery Full document path of the node. *READONLY*
   * @param     string $xmlData String containing the XML data. *READONLY*
   * @see       replaceNodeByXml()
   */
  function appendXml($xPathQuery, $xmlData) {
    // Numpty check
    $xpv = array();
    if (isSet($this->nodes[$xPathQuery])) {
      $xpv[] = &$xPathQuery;
    } else {
      // Try to evaluate the $xPathQuery; if it returns only 1 node use it; otherwise give up.
      $xpv = &$this->match($xPathQuery);
      if (sizeOf($xpv) ==0) {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $xPathQuery), __LINE__);
      }
    }
    
    $xpvSize = sizeOf($xpv);
    for ($k=0; $k < $xpvSize; $k++) {
      $absoluteXPath = &$xpv[$k];
      
      $theNode = &$this->nodes[$absoluteXPath];
      // Check boarders
      if (!isSet($theNode['text'])) $theNode['text'][] = '';
      $maxPos = sizeOf($theNode['text'])-1;
      $theNode['text'][$maxPos] .= $xmlData;
    }
    $xmlData = &$this->exportAsXml();
    $this->reset();
    return $this->importFromString($xmlData);
  }
  
  /**
   * Append text content to the end of the text for a node.
   *
   * This method adds content to a node. If it's an attribute node, then
   * the value of the attribute will be set, otherwise the character data of
   * the node will be set. The content is appended to existing content,
   * so nothing will be overwritten.
   *
   * @param     string $xPathQuery Full document path of the node. *READONLY*
   * @param     string $value String containing the content to be added. *READONLY*
   * @see       replaceData(), deleteData()
   */
  function appendData( $absoluteXPath, $value ) {
    // FALSE for no replace, -1 offset for the end of the string
    $this->_setContent( $absoluteXPath, $value, FALSE );
  }

  /**
   * Delete text content of a node.
   *
   * Deletes a max of $count characters starting at $offset from the text content 
   * for a node.
   *
   * @param     string $xPathQuery Full document path of the node. *READONLY*
   * @param     number $offset Return the string starting at this offset. 
   *            *READONLY*
   * @param     number $count Return a maximum of count characters.  0 means 
   *            entire string. *READONLY*
   * @return    string The new text value.
   */
  function deleteData($absoluteXPath, $offset = 0, $count = 0) {
    if ( $offset < 0 or !is_int($offset) ) $offset = 0;
    // Now delete the data starting at offset for count characters...
    // count defaults to the length string, whereas $offset defaults to the beginning
    // if neither offset nor count are given, the whole string is deleted
    $this->_setContent( $absoluteXPath, "", TRUE, $offset, $count );
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Element alteration

  /**
   * Removes a node from the XML document.
   *
   * This method removes a node from the tree of nodes of the XML document.
   * If the node is a document node, remove it. Depending on the parameter 
   * $keepSubnodes all children nodes are moved up one level (TRUE) or 
   * the node and the subnodes are removed with all character data. 
   * If the node is an attribute node,
   * only this attribute will be removed, the node to which the attribute
   * belongs as well as its children will remain unmodified.
   * NOTE: Instead of an absolute path you may also pass a xpath query.
   *       If the result of the query leads to 1 unique node, then the
   *       path to that node is taken.
   *
   * @author    Sam Blum
   * @param     string $xPathQuery The path to the current node (see text above)
   * @param     bool   $keepSubnodes default TRUE = move subnodes up / FALSE cascaded delete 
   * @return    TRUE on success
   * @throws    FALSE on error;
   * @see       appendChild(),  evaluate()
   */
  function removeChildSam($xPathQuery, $keepSubnodes=FALSE) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;
    
    // Numpty check
    $xpv = array();
    if (isSet($this->nodes[$xPathQuery])) {
      $xpv[] = &$xPathQuery;
    } else {
      // Try to evaluate the $xPathQuery; if it returns 0 nodes give up; otherwise loop through the result.
      $xpv = &$this->match($xPathQuery);
      if (sizeOf($xpv) ==0) {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $xPathQuery), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction('removeChild');
      echo "Node: $xPathQuery\n";
      echo "<hr>";
    }
    $xpvSize = sizeOf($xpv);
    for ($k=0; $k < $xpvSize; $k++) {
      $absoluteXPath = &$xpv[$k]; 
      do { // try block
        //////////////////////////////////////////////
        // Check whether it's an attribute node.
        $lastSlashPos = strrpos($absoluteXPath, '/') -1;
        $attrPos = ($lastSlashPos>=0) ? strpos($absoluteXPath, '/attribute::', $lastSlashPos) : FALSE;
        
        if ($attrPos !== FALSE) {
          if ($bDebugThisFunction) echo "We are removing an attribute node\n";
          // Extract the path to the node.
          $thePath = substr($absoluteXPath, 0, $attrPos);
          
          // Get the name of the attribute.
          $attribute = $this->_afterstr($absoluteXPath, '/attribute::');
          
          // Exception empty attribute, ignor it.
          if (strLen($attribute)==0) break;
          
          // Numpty check
          if (isSet($this->nodes[$thePath])) {
            // Unset the attribute
            unSet($this->nodes[$thePath]['attributes'][$attribute]);
          }
          break; // try block
        }
        
        $nodeList = array();
        // Get abs. parent xpath
        $absoluteParentPath = $this->nodes[$absoluteXPath]['parent'];
        
        if ($bDebugThisFunction) {
          echo "\nWe want to remove '$absoluteXPath'. The parent of the 'to remove' node at '$absoluteParentPath' is ";
          echo "<div style=\"".$this->styleSheet['Node']."\">\n";
          print_r($this->nodes[$absoluteParentPath]);
          echo "</div>\n";
        }
        
        // Get a copy of the current parent
        $copyOfParrent = $this->nodes[$absoluteParentPath];
        
        // Remember the node name fragment to be removed e.g. BBB[2]
        $nodenameToBeRemoved = substr($absoluteXPath, strrpos($absoluteXPath, '/')+1);
        // Remember the pos of the node in the parents child list e.g. it's the 3rd child.
        $nodePosInParent = array_search($nodenameToBeRemoved, $this->nodes[$absoluteParentPath]['children']);
        if ($bDebugThisFunction) echo "Node to remove is at pos [{$nodePosInParent}] in the parents child list.\n";
        
        // Do we have to move the sub nodes 1 leval up
        if ($keepSubnodes) {
          // Get a list of all child nodes that must be move up
          $vxpChildrenNodes = $this->evaluate($absoluteXPath.'/*');
           
          // Save chiled list in a sub tree stucture by cutting off the 
          // parent path fragment. That is chopping off the head.
          // Unset the values in the main tree.
          $headLeng = strLen($absoluteXPath);
          $size = sizeOf($vxpChildrenNodes);
          for ($i=0; $i<$size; $i++) {
            $corePath = subStr($vxpChildrenNodes[$i], $headLeng);
            $nodeList[$corePath] = &$this->nodes[$vxpChildrenNodes[$i]];
            unset($this->nodes[$vxpChildrenNodes[$i]]);
          }
          unset($this->nodes[$absoluteXPath]);
          
          // Now add the nodes again
          reset($nodeList);
          while (list($xpath) = each($nodeList)) {
            $splitPos = strrpos($xpath, '/');
            $frontNodePart = substr($xpath, 0, $splitPos);
            $lastNode = substr($xpath, $splitPos);
            $lastNode = substr($lastNode, 1, strrpos($lastNode, '[')-1);
            $newParentPath = $absoluteParentPath . $frontNodePart;
            $newParentPath =$this->appendChild($newParentPath, $lastNode);
            if (!empty($nodeList[$xpath]['attributes'])) {
              $this->nodes[$newParentPath]['attributes'] = $nodeList[$xpath]['attributes'];
            }
            $this->nodes[$newParentPath]['text'] = $nodeList[$xpath]['text'];
            //echo "<br>newParentPath:[$newParentPath], lastNode:[$lastNode]"; 
          }
        }
        //////////////////////////////////////
        // Now we have to clean up the parent.
        // Make a new children list
        $newChildSize = sizeOf($this->nodes[$absoluteParentPath]['children']);
        $oldChildSize = sizeOf($copyOfParrent['children']);
        $newCildren = array();
        $offset = 0;
        for ($i=0; $i<$oldChildSize; $i++) {
          if ($i == $nodePosInParent) {
            for ($j=$oldChildSize; $j<$newChildSize; $j++) {
              $newCildren[$offset++] = $this->nodes[$absoluteParentPath]['children'][$j];
            }
          } else {
            $newCildren[$offset++] = $copyOfParrent['children'][$i];
          }
        }
        $this->nodes[$absoluteParentPath]['children'] = $newCildren;
        
        // Correct the childCount
        // Node fragment to be removed snip from  BBB[2] to BBB
        $nodenameToBeRemoved = substr($nodenameToBeRemoved, 0, strrpos($nodenameToBeRemoved, '['));
        $chiledCountHash = &$this->nodes[$absoluteParentPath]['childCount'];
        if (isSet($chiledCountHash[$nodenameToBeRemoved])) {
          if ($chiledCountHash[$nodenameToBeRemoved] > 1) {
            $chiledCountHash[$nodenameToBeRemoved]--;
          } else {
            unset($chiledCountHash[$nodenameToBeRemoved]);
          }
        }
        
        // Now merge the 2 text parts that used to be split by the node
        $newTextParts = array();
        $offset=0;
        $textArray = &$this->nodes[$absoluteParentPath]['text'];
        for ($i=0; $i<sizeOf($textArray); $i++) {
          $newTextParts[$offset] = $textArray[$i];
          if ($i == $nodePosInParent) {
            $i++;
            $newTextParts[$offset] .= $textArray[$i];
          }
          $offset++;
        }
        $textArray = $newTextParts;
        if ($bDebugThisFunction) {
          echo "\nThe new fixed parent node at $absoluteParentPath is ";
          echo "<div style=\"".$this->styleSheet['ParentNode']."\">\n";
          print_r($this->nodes[$absoluteParentPath]);
          echo "</div>\n";
        }      
      } while(FALSE); // END try block
    } // END for ($k=0; $k < $xpvSize; $k++) 
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, '');
    }
    return;
  }
 
  /**
   * Removes a node from the XML document.
   *
   * This method removes a node from the tree of nodes of the XML document.
   * If the node is a document node, all children of the node and its
   * character data will be removed. If the node is an attribute node,
   * only this attribute will be removed, the node to which the attribute
   * belongs as well as its children will remain unmodified.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @param     string $absoluteXPath Full path of the node to be removed.
   * @return    TRUE on success
   * @throws    FALSE on error;
   * @see       appendChild(), hasChildNodes(), evaluate()
   */
  function removeChild($absoluteXPath) {
    // Numpty check
    if (!isSet($this->nodes[$absoluteXPath])) {
      // Try to evaluate the $absoluteXPath; if it returns only 1 node use it; otherwise give up.
      $resultArr = $this->match($absoluteXPath);
      if (sizeOf($resultArr)==1) {
        $absoluteXPath = $resultArr[0];
      } else {
        $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, FALSE);
        return FALSE;
      }
    }
    
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction('removeChild');
      echo "Node: $absoluteXPath\n";
      echo "<hr>";
    }
    
    // Numpty check
    if (empty($absoluteXPath)) {
      $this->_displayError('No child to remove. Passsed parameter was empty.', __LINE__, FALSE);
      return FALSE;
    }
    //////////////////////////////////////////////
    // Check whether the node is an attribute node.
    if (preg_match('/\/attribute::/', $absoluteXPath)) {
      if ($bDebugThisFunction) echo "We are removing an attribute node\n";
      // Get the path to the attribute node's parent.
      $parent = $this->_prestr($absoluteXPath, '/attribute::');
      
      // Get the name of the attribute.
      $attribute = $this->_afterstr($absoluteXPath, '/attribute::');
      
      // Unset the attribute
      unSet($this->nodes[$parent]['attributes'][$attribute]);
    } else {  
      if ($bDebugThisFunction) echo "We are removing an element node\n";

      // Find out if the node that they gave us exists.
      if (!isSet($this->nodes[$absoluteXPath])) {
        // Not sure if this is quite so fatal, but I think it likely.  Typically
        // you will evaluate() then remove.  The alternative would be just to quit.
        $this->_displayError("The $absoluteXPath argument does not uniquely refer to a node in the XML file  use /AAA[1]/BBB[1] format. ". basename(__FILE__).':'.__LINE__);
      }

      /////////////////////////////////////
      // Get some stats about the environment of the deceased.
      
      // Get the name, the parent and the siblings of current node.
      $nameOfDeadChild    = $this->nodes[$absoluteXPath]['name'];
      $parentOfDeadChild  = $this->nodes[$absoluteXPath]['parent'];
      $sameNameSiblings   = $this->nodes[$parentOfDeadChild]['childCount'][$nameOfDeadChild];
      $siblingsCount      = count($this->nodes[$parentOfDeadChild]['children']);
      // Get base node number e.g. /AAA[1]/BBB[3]/CCC[2]  => 2
      $contextPosOfDeadChild = $this->nodes[$absoluteXPath]['context-pos'];
      $fullNameOfDeadChild = $nameOfDeadChild.'['.$contextPosOfDeadChild.']';
      // Get the child number, ie the element number within the parent.
      $aChildToIndex = array_flip($this->nodes[$parentOfDeadChild]['children']);
      $deadChildSiblingOrder = $aChildToIndex[$fullNameOfDeadChild];      
      // Construct the common element of all the same name siblings.
      $commonComponent = $parentOfDeadChild.'/'.$nameOfDeadChild;

      /////////////////////////////////////

      // Create an associative array, which contains information about
      // all nodes that required to be renamed.  The linear order of the
      // elements is important, as the first rename should be done first, then
      // the second, etc, otherwise we will "overwrite" the members on the
      // way through
      $rename = array();
      if ($bDebugThisFunction) echo "\nCreating list of nodes that need renamed.\n";
            
      // Now run through the younger siblings, as they must be renamed
      for ( $iRunner = $contextPosOfDeadChild + 1; $iRunner <= $sameNameSiblings; $iRunner++) {
        // Create the renaming entry.
        $old = $parentOfDeadChild.'/'.$nameOfDeadChild.'['.$iRunner.']';
        $new = $parentOfDeadChild.'/'.$nameOfDeadChild.'['.($iRunner - 1).']';

        $rename[$old] = $new;
      }
      
      if ($bDebugThisFunction) {
        echo "The following nodes all have to be renamed\n";
        print_r($rename);
      }

      /////////////////////////////////////
      // Fixing parent.

      if ($bDebugThisFunction) {
        echo "<table border='1'><tr><td><pre>\n";
        echo "\nThe parent was:\n";
        print_r($this->nodes[$parentOfDeadChild]);
        echo ".\n";
      }

      // Decrease the number of children.
      $this->nodes[$parentOfDeadChild]['childCount'][$nameOfDeadChild]--;
      if ($this->nodes[$parentOfDeadChild]['childCount'][$nameOfDeadChild] == 0)
          unset($this->nodes[$parentOfDeadChild]['childCount'][$nameOfDeadChild]);
      
      // Merge the text before and after the child
      if (!empty($this->nodes[$parentOfDeadChild]['text'][$deadChildSiblingOrder])) {
        $this->nodes[$parentOfDeadChild]['text'][$deadChildSiblingOrder] .= $this->nodes[$parentOfDeadChild]['text'][$deadChildSiblingOrder+1];
      }
      // Shift all the next text nodes down in the array.
      for ($iRunner = $deadChildSiblingOrder + 1; $iRunner < $siblingsCount; $iRunner++) {
        $this->nodes[$parentOfDeadChild]['text'][$iRunner] = $this->nodes[$parentOfDeadChild]['text'][$iRunner+1];
      }
      // Unset the last text node.
      unset($this->nodes[$parentOfDeadChild]['text'][$siblingsCount]);

      // Remove the child from the parents memory.  Sniff sniff :o( We must finish the grieving process!
      unSet($this->nodes[$parentOfDeadChild]['children'][$fullNameOfDeadChild]);
      // Go through the array, and rename all the younger same-name siblings, shifting the kids up
      // the array as we go.
      $contextPosOfNextSameNameSibling = $contextPosOfDeadChild + 1;
      $fullNameOfNextSameNameSibling = $nameOfDeadChild .'['.$contextPosOfNextSameNameSibling.']';
      for ($iRunner = $deadChildSiblingOrder + 1; $iRunner < $siblingsCount; $iRunner++) {
        $childName = &$this->nodes[$parentOfDeadChild]['children'][$iRunner];
        if ($childName == $fullNameOfNextSameNameSibling) {
          $childName = $nameOfDeadChild .'['.($contextPosOfNextSameNameSibling-1).']';
          $fullNameOfNextSameNameSibling = $nameOfDeadChild .'['.++$contextPosOfNextSameNameSibling.']';
        }
        $this->nodes[$parentOfDeadChild]['children'][$iRunner-1] = $childName;
      }
      // Unset the last child to indicate we have one less child now.
      unset($this->nodes[$parentOfDeadChild]['children'][$siblingsCount-1]);

      if ($bDebugThisFunction) {
        echo "\n</pre></td><td><pre>\n";
        echo "\nThe parent is now:\n";
        print_r($this->nodes[$parentOfDeadChild]);
        echo ".\n";
        echo "\n</pre></td></tr></table>\n";
      }

      /////////////////////////////////////
      // Rename all the entries in the $nodes array to correct the object for the dead child.
      
      if ($bDebugThisFunction) echo "\nModifying node list.\n";

      // Run through all nodes of the document.
      $aNodeKeys = array_keys($this->nodes);
      reset($aNodeKeys);
      while (list($key, $absoluteXPathRunner) = each($aNodeKeys)) {
        // skip super-Root
        if (empty($absoluteXPathRunner)) continue;

        // Check to see if this node starts with the common component of all nodes that are to be renamed
        if ($commonComponent == substr($absoluteXPathRunner, 0, strlen($commonComponent))) {
          if ($absoluteXPath == substr($absoluteXPathRunner, 0, strlen($absoluteXPath))) {
            if ($bDebugThisFunction) echo "Removing node: $absoluteXPathRunner\n";
            unset($this->nodes[$absoluteXPathRunner]);
            continue;
          }

          // Run through the array of nodes to be renamed.
          reset($rename);
          while (list($old, $new) = each($rename)) {
            // Does this rename prefix match our node?
            $oldLength = strlen($old);
            if ($old == substr($absoluteXPathRunner, 0, $oldLength)) {
              // Build the new name of this node in the nodes array.
              $nameOfRenamedChild  = $new.substr($absoluteXPathRunner, $oldLength);

              // Get the complete values for this node.
              $values = $this->nodes[$absoluteXPathRunner];

              // Update the context pos as we have renamed it.
              if ($values['parent'] == $parentOfDeadChild)
                $values['context-pos'] = $values['context-pos'] - 1;

              // We may need to rename the 'parent' of this node too.
              if ($old == substr($values['parent'], 0, $oldLength)) {
                $values['parent'] = $new.substr($values['parent'], $oldLength);
              }

              if ($bDebugThisFunction) {
                echo "Renaming node: $absoluteXPathRunner from $old to $new.\n";
//                echo "Values:\n"; print_r($values);
              }
              
              // Add the node to the list of nodes, remove it's old entry
              $this->nodes[$nameOfRenamedChild] = $values;
              unset($this->nodes[$absoluteXPathRunner]);

              break;
            }
          }
        } 
      }
      
      if ($bDebugThisFunction) {
        echo "The new node list is:\n";
        print_r($this->nodes);
      }
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time);
    }
    return TRUE;
  }
  
  /**
   * Adds a new node to the XML document.
   *
   * This method adds a new node to the tree of nodes of the XML document
   * being handled by this class. The new node is created according to the
   * parameters passed to this method.
   * 
   * It it assumed that adding starts with root and new nodes must have a 
   * corresponding parent. Otherwise the add will be ignored.
   * Node stucture:
   *      [<path>]['name']          // <nodeName>
   *      [<path>]['doc-pos']       // Path-'depth' starting with 0
   *      [<path>]['context-pos']   // child order
   *      [<path>]['parent']        // <parent path>
   *      [<path>]['children']      // array(<xPathFragment>, ...)         e.g. array(AAA[1],AAA[2],BBB[1])
   *      [<path>]['childCount']    // array(<nodeName> => <child Count>, ...)
   *      [<path>]['attributes']    // array(<attrName> => <attrVal>, ...)
   *      [<path>]['text']          // array of text parts: E.g. <A>hello<B/>world</A> -> array('hello','world')
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $absoluteParentPath Full path of the parent, to which the new
   *            node should be added as a child. *READONLY*
   * @param     string $nodeName Name of the new node. *READONLY*
   * @return    string The string returned by this method will contain the
   *            full document path of the created node.
   * @see       removeChild(), hasChildNodes(), evaluate()
   */
  function appendChild($absoluteParentPath, $nodeName) {
    $bDebugThisFunction= FALSE;
    
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("appendChild");
      echo "Current Node (parent): '{$absoluteParentPath}'\n";
	    echo "NewNodeName  (child) : '{$nodeName}' \n<br>";
      echo implode(' - ', array_keys($this->nodes));
    }
    
    static $emptyNode = array(
                       'name'        => '',
                       'doc-pos'     => 0, 
                       'context-pos' => 1,
                       'parent'      => '',
                       'children'    => array(),
                       'childCount'  => array(),
                       'attributes'  => array(),
                       'text'        => array()
                     );
                     
    // If first call assume it's root.
    if (empty($this->root)) {
      // Use $nodeName root element as it is the first tag.
      $this->root = '/'.$nodeName.'[1]';
      $fullNameOfNewChild = $this->root;
      $this->nodes[$fullNameOfNewChild] = $emptyNode;
      $this->nodes[$fullNameOfNewChild]['name'] = $nodeName;
      // Make a super-root.  Don't use PHP's array_merge!!
      reset($emptyNode);
      while (list($key) = each($emptyNode)) {
        $this->nodes[''][$key] = $emptyNode[$key];
      }
      $this->nodes['']['childCount'] = array($nodeName=>1);
      $this->nodes['']['children'][] = $nodeName.'[1]';
    } else {
      // Assemble the basic path for this element.      
      if ($absoluteParentPath == '/')
        $absoluteParentPath = '';

      $basicPath = $absoluteParentPath.'/'.$nodeName;
      
      // Modify the child count for this type of node      
      $tmpCount = &$this->nodes[$absoluteParentPath]['childCount'][$nodeName];
      $position = isSet($tmpCount) ? $tmpCount+1: 1;
            
      if ($bDebugThisFunction) {
        echo "\nIn parent '{$absoluteParentPath}' the child '{$nodeName}' has position '{$position}'.";
      }
      
      ///////////////////////////////////////
      // Add the new node to them nodes array.
      
      // Assable the full path for this element.
      $fullNameOfNewChild = $basicPath.'['.$position.']';
      
      // Init node if not exsisting
      if (!isSet($this->nodes[$fullNameOfNewChild]))
        $this->nodes[$fullNameOfNewChild] = $emptyNode;
      
      // Use node directly
      $newChildNode = &$this->nodes[$fullNameOfNewChild];
      // Calculate the position for the following and preceding axis detection.
      $newChildNode['doc-pos'] = $this->nodes[$absoluteParentPath]['doc-pos'] + 1;
      // Calculate the context position, which is the position of this
      // element within elements of the same name in the parent node.
      $newChildNode['context-pos'] = $position;
      // Save the information about the node.
      $newChildNode['name'] = $nodeName;
      // Set the parent
      $newChildNode['parent'] = $absoluteParentPath;
      
      if ($bDebugThisFunction) {
        echo "\nThe new node at $fullNameOfNewChild is ";
        echo "<div style=\"".$this->styleSheet['Node']."\">\n";
        print_r($newChildNode);
        echo "</div>\n";
      }      

      ///////////////////////////////////////
      // Fix the parent.
      
      // Get the parent node.
      $newParentNode = &$this->nodes[$absoluteParentPath];
      // There a bug some where that will trigger the following check. --sam 12.12.2001
      if (!isSet($newParentNode['children'])) {
        $this->_displayError("Internal node problem. When adding node '{$nodeName}' to an invalid parent node '{$absoluteParentPath}'." , __LINE__, FALSE);
      }
      
      // Count the siblings
      $siblingCount = count($newParentNode['children']);
      // Add children in the *order* they come in.
      $newParentNode['children'][$siblingCount] = $nodeName.'['.$position.']';
      // Update the number of childer in parent path
      $newParentNode['childCount'][$nodeName] = $position;
      // Add some default sensible whitespace before this new node.
      
      // Work out who the grandparent is.
      if ($newParentNode['parent']) {
        $newGrandParentNode = &$this->nodes[$newParentNode['parent']];
        $beforeText = $newGrandParentNode['text'][$newParentNode['context-pos']-1];
        $afterText = $newGrandParentNode['text'][$newParentNode['context-pos']];
        if ($bDebugThisFunction) {
          echo "Text before this child is '$beforeText'\n";
          echo "Text after this child is '$afterText'\n";
        }
        // If there is no text before or after, then default to something sensible
        if ( $beforeText == '' && $afterText == '' ) {
          $newParentNode['text'][(int)$siblingCount] = "\n\t";
          $newParentNode['text'][$siblingCount+1] = "\n";
        // If the text before and after is purely whitespace, derive from it.
        } else if (!preg_match('/[^\s]/',$beforeText) && !preg_match('/[^\s]/',$afterText)) { // --sam regex shoud be /^\s*$/ I guess
          $newParentNode['text'][(int)$siblingCount] = "$beforeText\t";
          $newParentNode['text'][$siblingCount+1] = "$afterText";
        // Otherwise default to something sensible.
        } else {
          $newParentNode['text'][(int)$siblingCount] = "\n\t";
          $newParentNode['text'][$siblingCount+1] = "\n";
        }
      // We are adding to the root, so we know what these should be.
      } else {
        $newParentNode['text'][(int)$siblingCount] = "\n\t";
        $newParentNode['text'][$siblingCount+1] = "\n";
      }

      if ($bDebugThisFunction) {
        echo "\nThe fixed parent at $absoluteParentPath is ";
        echo "<div style=\"".$this->styleSheet['ParentNode']."\">\n";
        print_r($this->nodes[$absoluteParentPath]);
        echo "</div>\n";
      }
    }

    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $fullNameOfNewChild);
    }

    // Return the path of the new node.
    return $fullNameOfNewChild;
  }


  /**
   * Returns TRUE if the given node has child nodes below it
   *
   * @author    Dietrich Ayala <dietrich@ganx4.com>
   * @param     string $absoluteXPath full path of the potentail parent node
   *            *READONLY*
   * @return    bool TRUE if this node exists and has a child, FALSE otherwise
   * @see       removeChild(), appendChild(), evaluate()
   */
  function hasChildNodes($absoluteXPath) {
    if (!isSet($this->nodes[$absoluteXPath])) return FALSE;
		if (count($this->nodes[$absoluteXPath]['children']) >= 1)	return TRUE;
		return FALSE;
  }

  /**
   * Resets the object so it's able to take a new xml sting/file
   *
   * @author    Sam Blume bs_php@infeer.com
   *
   */
  function reset() {
    $this->nodes        = array();
    $this->path         = '';
    $this->xpath        = '';
    $this->root         = '';
    $this->position     = 0;
    $this->xmlTxtBuffer = '';
    $this->fileName     = ''; //added by fab
    $this->inCData      = 0;
    $this->_lastError   = '';
  }

  ////////////////////////////////////////////////////////////////////////////////
  //################### Private Members ########################################//
  ////////////////////////////////////////////////////////////////////////////////
  
  // 0=silent, 1 and above produce verbose output (an echo to screen). 
  // @see getLastError()
  var $_verboseLevel = 1;
  
  // xml_parser_set_option -- set options in an XML parser.
  var $xmlOptions = array();
  
  // The filename, if XML was imported by a file (instead of a string)
  var $fileName = '';
  
  // List of all document nodes.
  //
  // This array contains a list of all document nodes saved as an
  // associative array.
  var $nodes = array();
   
  // Current document path.
  //
  // This variable saves the current path while parsing a XML file and adding
  // the nodes being read from the file.
  var $path = '';
  
  // Current document position.
  //
  // This variable counts the current document position while parsing a XML
  // file and adding the nodes being read from the file.
  var $position = 0;
  
  // Path of the document root.
  //
  // This string contains the full path to the node that acts as the root
  // node of the whole document.
  var $root = '';
  
  // Current XPath expression.
  //
  // This string contains the full XPath expression being parsed currently.
  var $xpath    = '';
                                                         
  // Used as tmp storage for the char data collected during xml parsing
  var $xmlTxtBuffer = '';

  // When parsing the xml file, sets to true when we are inside a CDATA section.
  var $inCData = 0;

  // As debugging of the xml parse is spread across several functions, we need
  // to make this a member.
  var $bDebugXmlParse = FALSE;

  // List of entities to be converted.
  //
  // This array contains a list of entities to be converted when an XPath
  // expression is evaluated.
  //
  // ### People seem to think that &apos is a bad idea for charset ISO-8859-1 
  //var $entities = array ( "&" => "&amp;", "<" => "&lt;", ">" => "&gt;",
  //    "'" => "&apos;", '"' => "&quot;" );
  var $entities = array('&'=>'&amp;', '<'=>'&lt;', '>'=>'&gt;', '"'=>'&quot;');
  
  // List of supported XPath axes.
  // What a stupid idea from W3C to take axes name containing a '-' (dash)
  // NOTE: Instead of the '-' in the names we use '_'.
  //       We will then do the same on the users Xpath querys
  //   -sibling => _sibling
  //   -or-     =>     _or_
  //  
  // This array contains a list of all valid axes that can be evaluated in an
  // XPath expression.
  var $axes = array ( 'child', 'descendant', 'parent', 'ancestor',
    'following_sibling', 'preceding_sibling', 'following', 'preceding',
    'attribute', 'text', 'namespace', 'self', 'descendant_or_self',
    'ancestor_or_self' );
  
  // List of supported XPath functions.
  // What a stupid idea from W3C to take function name containing a '-' (dash)
  // NOTE: Instead of the '-' in the names we use '_'.
  //       We will then do the same on the users Xpath querys 
  //   starts-with      => starts_with
  //   substring-before => substring_before
  //   substring-after  => substring_after
  //   string-length    => string_length
  //
  // This array contains a list of all valid functions that can be evaluated
  // in an XPath expression.
  var $functions = array ( 'last', 'position', 'count', 'id', 'name',
    'string', 'concat', 'starts_with', 'contains', 'substring_before',
    'substring_after', 'substring', 'string_length', 'normalize_space', 'translate',
    'boolean', 'not', 'true', 'false', 'lang', 'number', 'sum', 'floor',
    'ceiling', 'round' );
    
  // What a stupid idea from W3C to take axes name containing a '-' (dash)
  // It's hard to distinguish from a minus operator. 
  // NOTE: Instead of the '-' in the names we use '_'.
  //       We will then do the same on the users Xpath querys
  var $dash2underscoreHash = array( 
      '-sibling'    => '_sibling', 
      '-or-'        => '_or_',
      'starts-with' => 'starts_with', 
      'substring-before' => 'substring_before',
      'substring-after'  => 'substring_after', 
      'string-length'    => 'string_length',
      'normalize-space'  => 'normalize_space');
   
  // List of supported XPath operators.
  //
  // This array contains a list of all valid operators that can be evaluated
  // in a predicate of an XPath expression. The list is ordered by the
  // precedence of the operators (lowest precedence first).
  var $operators = array( ' or ', ' and ', '=', '!=', '<=', '<', '>=', '>',
    '+', '-', '*', ' div ', ' mod ' );

  // This is the array of error strings, to keep consistency.
  var $errorStrings = array(
    'AbsoluteXPathRequired' => 'The supplied string does not uniquely describe a node in the xml document: %s'
    );

  // Style sheet of strings used to make output prettier.
  var $styleSheet = array(
    'Node' => 'background-color: #FFFFEE;',
    'ParentNode' => 'background-color: #FFEEFF;',
    );

  ////////////////////////////////////////////////////////////////////////////////
  //################### Private Interface ######################################//
  ////////////////////////////////////////////////////////////////////////////////

  /////////////////////////////////////////////////
  // ########################################### //
  // Export functions
  
  /**
   * Generates a XML file with the content of the current document.
   *
   * This method creates a string containing the XML data being read
   * and modified by this class before. This string can be used to save
   * a modified document back to a file or doing other nice things with
   * it.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $highlight Array containing a list of full document
   *            paths of nodes to be highlighted by <font>...</font> tags
   *            in the generated XML string.
   * @param     string $currentXpath While doing a recursion with this method, this
   *            parameter is used for internal purpose.
   * @param     int $level While doing a recursion with this method, this
   *            parameter is used for internal purpose.
   * @param     $outputAsXml specifies whether or not you want to escape
   *            <> as &gt; and &lt;  Added by N.S.
   * @return    string The returned string contains well-formed XML data
   *            representing the content of this document.
   */
  function &_export($highlight = array(), $currentXpath = '', $level = 0, $outputAsXml = 0) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;

    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("_export");
      echo "Highlights:\n";
      print_r($highlight);
      echo "Root: $currentXpath\n";
      echo "Level: $level\n";
      echo "Output As Xml: $outputAsXml\n\n";
    }
    //////////////////////////////////////////////
    // Create a string to save the generated XML data.
    $xml = '';

    // Create two strings containing the tags for highlighting a node.
    static $highlight_start = '<font color="#FF0000"><b>';
    static $highlight_end   = '</b></font>';

    // Check whether a root node is given.
    if (empty($currentXpath)) {
      // Set it to the document root.
      $currentXpath = $this->root;
      if ($bDebugThisFunction) echo "Changing root to $this->root as it is empty\n";
    }

    // Generate a string to be displayed before the tags.
    $before = '';
    // Calculate the amount of whitespaces to display.
    for ( $i=0; $i<$level; $i++) {
       //Add a whitespaces to the string.
       $before .= '  ';
    }

    // If there is no node at $currentXpath then we have nothing to export.  Quit now
    if (!isSet($this->nodes[$currentXpath])) {
      if ($bDebugThisFunction) echo "No node at $currentXpath, returning null\n";        
      // Not completely sure yet if this is fatal, but I think it is.
      $this->_displayError("When exporting the class, the node at $currentXpath ".
                              "was not found.  This is probably due to previous internal corruption.");       
      return '';
    }

    $theNode = &$this->nodes[$currentXpath];


    // Check whether the node is selected.
    $selected = empty($highlight) ? FALSE : in_array($currentXpath, $highlight);
    $hasChildren = (sizeOf($theNode['children'])>0) ? TRUE : FALSE;

    // Check whether the node is selected for highlight.
    if ($selected ) $xml .= $highlight_start;

    // Now open the tag adding the whitespaces to the XML data.
    if (!$outputAsXml && $level>0)  $xml .= "\n".$before;

    if (empty($theNode['name'])) {
      // If the node has no name, then ring alarm bells.
      $this->_displayError("When exporting the class, the node at '{$currentXpath}' ".
                              "was found to have no node name.", __LINE__, FALSE);
      return '';
    }

    $xml .= ($outputAsXml) ? '<' : '&lt;';
    $xml .= htmlspecialchars($theNode['name']);

    // Check whether there are attributes for this node.
    if (count($theNode['attributes']) > 0) {
      if ($bDebugThisFunction) echo "Outputing the attributes\n";
      // Run through all attributes.
      $highlighting = FALSE;
      reset($theNode['attributes']);
      while (list($key) = each($theNode['attributes'])) {
        // Check whether this attribute is highlighted.
        if (is_array($highlight) and in_array($currentXpath.'/attribute::'.$key, $highlight)) {
          // Add the highlight code to the XML data.
          $xml .= $highlight_start;
          $highlighting = TRUE;
        }
    
        // Add the attribute to the XML data.
        $xml .= ' '.$key.'="'.htmlspecialchars($theNode['attributes'][$key]).'"';
    
        // Check whether this attribute is highlighted.
        if ($highlighting) {
          // Add the highlight code to the XML data.
          $xml .= $highlight_end;
          $highlighting = FALSE;
        }
      }
    } 

    if (!isset($theNode['text'])) {
      $mergedText = '';
      $useShortEnd = !$hasChildren;
    } else {
      $mergedText = implode('', $theNode['text']);
      $useShortEnd = (!$hasChildren && ($mergedText == ''));
    }

    // Check whether the node contains character data or has children.
    if ($useShortEnd) {
      // Add the end to the tag.
      $xml .= ($outputAsXml) ? '/>' : "/&gt;";
    } else {
      // Close the tag.
      $xml .= ($outputAsXml) ? '>' : '&gt;';
    }

    // Check whether the node is selected. Add the highlight code to the XML data.
    if ($selected) $xml .= $highlight_end;

    // Check whether the node has children or not.
    if (!$hasChildren) {
      $xml .= $mergedText;
    } else {
      // Run through all children in the order they where set.
      $childSize = sizeOf($theNode['children']);
      if ($bDebugThisFunction) {
		    echo "$childSize children to output.\n"; 
		    print_r($theNode['children']);
	    }
      for ($i=0; $i<$childSize; $i++) {
        if (!empty($theNode['text'][$i])) $xml .=  $theNode['text'][$i];
        // Generate the full path of the child.
        $fullchild = $currentXpath.'/'.$theNode['children'][$i];
	      if ($bDebugThisFunction) echo "Outputing child $i: $fullchild\n";
        // Add the child's XML data to the existing data.
        $xml .= $this->_export(&$highlight, $fullchild, $level + 1, $outputAsXml);
      }
      // Add the text fagment after the child node
      if (!empty($theNode['text'][$i])) {
        if ($outputAsXml) $xml .= $theNode['text'][$i];
        else              $xml .= htmlspecialchars($theNode['text'][$i]);
      }
    }
  
    // Check if we have to set a ending </foo> tag
    if (! $useShortEnd) {
      // Add the whitespaces to the XML data, but only if there were kids.
      if (!$outputAsXml && $hasChildren) $xml .= "\n".$before;
  
      // Check whether the node is selected. Add the highlight code to the XML data.
      if ($selected) $xml .= $highlight_start;
  
      // Add the closing tag.
      $xml .= ($outputAsXml) ? '</' : '&lt;/';
      $xml .= $theNode['name'];
      $xml .= ($outputAsXml) ? '>' : '&gt;';
  
      // Check whether the node is selected. Add the highlight code to the XML data.
      if ($selected) $xml .= $highlight_end;
      
      // Add a linebreak.
      if (!$outputAsXml) $xml .= "\n";
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $xml);
    }
  
    // Return the XML data.
    return $xml;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Xml parsing utitilties
  
  /**
   * Handles opening XML tags while parsing.
   *
   * While parsing a XML document for each opening tag this method is
   * called. It'll add the tag found to the tree of document nodes.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     int $parser Handler for accessing the current XML parser.
   * @param     string $name Name of the opening tag found in the document.
   * @param     array $attributes Associative array containing a list of
   *            all attributes of the tag found in the document.
   * @see       _handleEndElement(), _handleCharacterData()
   */
  function _handleStartElement($parser, $nodeName, $attributes) {
    if (empty($nodeName)) {
      $this->_displayError('XML error in file at line'. xml_get_current_line_number($parser) .'. Empty name.', __LINE__);
      return;
    }
    if ($this->bDebugXmlParse)
      echo "<blockquote>Start node: ".$nodeName . "<br>";

    $oldPath = $this->path;

    // Add a node.
    $this->path = $this->appendChild($this->path, $nodeName);

    // Add text fragments to the old node, resetting the defaults applied by appendChild()
    if (!empty($this->path)) {
      // When we read a start element, we replace the before text for this node with
      // what we actually read.  Hence the -2.
      if (isset($this->nodes[$oldPath]['text']))
        $iIndex = count($this->nodes[$oldPath]['text'])-2;
      else
        $iIndex = 0;
      $this->nodes[$oldPath]['text'][$iIndex?$iIndex:0] = $this->xmlTxtBuffer;
      $this->xmlTxtBuffer = '';
    }
  
    // Set the attributes.
    if (!empty($attributes))
      $this->nodes[$this->path]['attributes'] = $attributes;
  }
  
  /**
   * Handles closing XML tags while parsing.
   *
   * While parsing a XML document for each closing tag this method is called.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     int $parser Handler for accessing the current XML parser.
   * @param     string $name Name of the closing tag found in the document.
   * @see       _handleStartElement(), _handleCharacterData()
   */
  function _handleEndElement($parser, $name) {
    // Add text fragments.  If the node has kids, then there will be at least two
    // text entries, before and after.  So we replace the last entry with the txtBuffer.
    // If the node had no kids, then we need to set the 1 and only text node.
    $iIndex = count($this->nodes[$this->path]['text']);
    $this->nodes[$this->path]['text'][$iIndex?$iIndex-1:0] = $this->xmlTxtBuffer;
    if ($this->bDebugXmlParse) {
      echo "End node:$name: [{$this->path}]: '".htmlspecialchars($this->xmlTxtBuffer)."'<br>Text nodes:<pre>\n";
      print_r($this->nodes[$this->path]['text']);
      echo "</pre></blockquote>\n";
    }
    $this->xmlTxtBuffer = '';
    // Jump back to the parent element.
    $this->path = substr($this->path, 0, strrpos($this->path, '/'));
  }
  
  /**
   * Handles character data while parsing.
   *
   * While parsing a XML document for each character data this method
   * is called. It'll add the character data to the document tree.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     int $parser Handler for accessing the current XML parser.
   * @param     string $text Character data found in the document.
   * @see       _handleStartElement(), _handleEndElement()
   */
  function _handleCharacterData($parser, $text) {
    // Replace entities.
    if (!$this->inCData)
      $text = strtr($text, $this->entities);
    
    $this->xmlTxtBuffer .= $text;
    if ($this->bDebugXmlParse)
      echo "Handling character data: ".htmlspecialchars($text)."<br>";
  }

  /**
   * Default handler for the XML parser.  
   *
   * While parsing a XML document for string not caught by one of the other
   * handler functions, we end up here.
   *
   * @param     int $parser Handler for accessing the current XML parser.
   * @param     string $text Character data found in the document.
   * @see       _handleStartElement(), _handleEndElement()
   */
  function _handleDefaultData($parser, $text) {
    // Replace entities.
    do { // try-block
      if ($this->path) {
        $this->xmlTxtBuffer .= $text;
        if (!strcmp($text, '<![CDATA[')) {
          $this->inCData++;
        } else if (!strcmp($text, ']]>')) {
          $this->inCData--;
          if ($this->inCData < 0) $this->inCData = 0;
        }
        if ($this->bDebugXmlParse) echo "Default handler data: ".htmlspecialchars($text)."<br>";    
        break; // try-block
      }
           
      // handle the dtd and xml declarations
      if ( preg_match(";\?>$;", $this->nodes['']['xml-declaration']) ) {
        $this->nodes['']['dtd-declaration'] .= $text;
        break; // try-block
      }
       
      $this->nodes['']['xml-declaration'] .= $text;
      
    } while (FALSE); // END try-block
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // XPath expression parsing functions

  /**
   * Split a string by a searator-string -- BUT the searator-string must be located *outside* of any brackets.
   * 
   * Returns an array of strings, each of which is a substring of string formed 
   * by splitting it on boundaries formed by the string separator. 
   *
   * @param     string $separator String that should be searched.
   * @param     string $term String in which the search shall take place.
   * @return    array (see above)
   */
  function &_bracketExplode($separator, &$term) {
    // Note that it doesn't make sense for $separator to itself contain (,),[ or ],
    // but as this is a private function we should be ok.
    $resultArr   = array();
    $bracketCounter = 0;	// Record where we are in the brackets.  If we are inside
                          // a () or [] bracket, then we won't be able to reliably
                          // extract strings.
    do { // BEGIN try block
      // Check if any separator is in the term
      $sepLeng =  strlen($separator);
      if (strpos($term, $separator)===FALSE) { // no separator found so end now
        $resultArr[] = $term;
        break; // try-block
      }
      
      // Make a substitute separator out of 'unused chars'.
      $substituteSep = str_repeat(chr(2), $sepLeng);
      
      // Now determin the first bracket '(' or '['.
      $tmp1 = strpos($term, '(');
      $tmp2 = strpos($term, '[');
      if ($tmp1===FALSE) {
        $startAt = (int)$tmp2;
      } elseif ($tmp2===FALSE) {
        $startAt = (int)$tmp1;
      } else {
        $startAt = min($tmp1, $tmp2);
      }
      
      // Get prefix string part befor the first bracket.
      $preStr = substr($term, 0, $startAt);
      // Substitute separator in prefix string.
      $preStr = str_replace($separator, $substituteSep, $preStr);
      
      // Now get the rest-string (postfix string)
      $postStr = substr($term, $startAt);
      // Go all the way through the rest-string.
      $strLeng = strlen($postStr);
      for ($i=0; $i < $strLeng; $i++) {
        $char = $postStr[$i];
        // Spot (,),[,] and modify our bracket counter.  Note there is an
        // assumption here that you don't have a string(with[mis)matched]brackets.
        // This should be ok as the dodgy string will be detected elsewhere.
        if ($char=='(' || $char=='[') {
          $bracketCounter++;
          continue;
        } 
        elseif ($char==')' || $char==']') {
          $bracketCounter--;
        }
        // If no brackets surround us check for separator
        if ($bracketCounter == 0) {
          // Check whether we can find the expression starting at this index.
          if ((substr($postStr, $i, $sepLeng) == $separator)) {
            // Substitute the found separator 
            for ($j=0; $j<$sepLeng; $j++) {
              $postStr[$i+$j] = $substituteSep[$j];
            }
          }
        }
      }
      // Now explod using the substitute separator as key.
      $resultArr = explode($substituteSep, $preStr . $postStr);
    } while (FALSE); // End try block
    // Return the results that we found. May be a array with 1 entry.
    return $resultArr;
  }
  
  
  /**
   * Retrieves axis information from an XPath expression step.
   *
   * This method tries to extract the name of the axis and its node-test
   * from a given step of an XPath expression at a given node.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $step String containing a step of an XPath expression.
   * @param     string $nodePath Full document path of the node on which the
   *            step is executed.
   * @return    array This method returns an array containing information
   *            about the axis found in the step.
   * @see       _evaluateStep()
   */
  function _getAxis($step, $nodePath) {
    // Create an array to save the axis information.
    $axis = array(
      'axis'      => '',
      'node-test' => '',
      'predicate' => array()
    );
    
    do { // parse block
      $parseBlock = 1;
      
      // Check whether the step is empty or only self. 
      if ( empty($step) OR ($step == '.') OR ($step == 'current()') ) {
        // Set it to the default value.
        $step = '.';
        $axis['axis']      = 'self';
        $axis['node-test'] = '*';
        break $parseBlock;
      }
      
      // Check whether is an abbreviated syntax.
      if ($step == '*') {
        // Use the child axis and select all children.
        $axis['axis']      = 'child';
        $axis['node-test'] = '*';
        break $parseBlock;
      }

      // Check whether it's all wrapped in a function.  will be like count(.*) where .* is anything
      // text() will try to be matched here, so just explicitly ignore it
      $regex = ":(.*)\s*\((.*)\)$:U";
      if (preg_match($regex, $step, $match) && $step != "text()") {
        $function = $match[1];
        $data    = $match[2];
        if ($this->_isFunction($function)) {
          // Save the evaluated function.
          $axis['axis']      = 'function';
          $axis['node-test'] = $this->_evaluateFunction($function, $data, $nodePath, $nodeTest);
        } 
        else {
          // Use the child axis and a function.
          $axis['axis']      = 'child';
          $axis['node-test'] = $step;
        }
        break $parseBlock;
      }

      // Check whether there are predicates and add the predicate 
      // to the list of predicates without []. Get contents of
      // every [] found.
      $regex = '/\[(.*)\]/';
      preg_match_all($regex, $step, $regs); 
      if (!empty($regs[1])) {
        $axis['predicate'] = $regs[1];
        // Reduce the step.
        $step = preg_replace($regex,"",$step); //$this->_prestr($step, '[');
      }
      
      // Check whether the axis is given in plain text.
      if ($this->_searchString($step, '::') > -1) {
        // Split the step to extract axis and node-test.
        $axis['axis']      = $this->_prestr($step, '::');
        $axis['node-test'] = $this->_afterstr($step, '::');
        break $parseBlock;
      }
      
      if ($step[0] == '@') {
        // Use the attribute axis and select the attribute.
        $axis['axis']      = 'attribute';
        $axis['node-test'] = substr($step, 1);
        break $parseBlock;
      }
      
      if (eregi('\]$', $step)) {
        // Use the child axis and select a position.
        $axis['axis']      = 'child';
        $axis['node-test'] = substr($step, strpos($step, '['));
        break $parseBlock;
      }

      if ($step == '..') {
        // Select the parent axis.
        $axis['axis']      = 'parent';
        $axis['node-test'] = '*';
        break $parseBlock;
      }
      
      if (preg_match('/^[a-zA-Z0-9\-_]+$/', $step)) {
        // Select the child axis and the child.
        $axis['axis']      = 'child';
        $axis['node-test'] = $step;
        break $parseBlock;
      } 

      if ( $step == "text()" ) {
        // Handle the text node
        $axis["axis"]      = "child";
        $axis["node-test"] = "cdata";
        break $parseBlock;
      }

      // Default will be to fall back to using the child axis and a name.
      $axis['axis']      = 'child';
      $axis['node-test'] = $step;

    } while(FALSE); // end parse block
    
    // Check whether it's a valid axis.
    if (!in_array($axis['axis'], array_merge($this->axes, array('function')))) {
      // Display an error message.
      $this->_displayError('While parsing an XPath expression, in the step ' .
        str_replace($step, '<b>'.$step.'</b>', $this->xpath) .
        ' the invalid axis ' . $axis['axis'] . ' was found.', __LINE__);
    }
    
    // Return the axis information.
    return $axis;
  }    

  /**
   * Checks for a valid function name.
   *
   * This method check whether an expression contains a valid name of an
   * XPath function.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $expression Name of the function to be checked.
   * @return    boolean This method returns TRUE if the given name is a valid
   *            XPath function name, otherwise FALSE.
   * @see       evaluate()
   */
  function _isFunction(&$expression) {
    // Check whether it's in the list of supported functions.
    if (in_array($expression, $this->functions)) {
      // It's a function.
      return TRUE;
    } else {
      // It's not a function.
      return FALSE;
    }
  }
  
  /////////////////////////////////////////////////
  // ########################################### //
  // Evaluation functions
  
  /**
   * Internal recursive evaluate an-XPath-expression function.
   *
   * $this->evaluate() is the entry point and does some inits, while this 
   * function is called recursive internaly for every sub-xPath expresion we find.
   *
   * @param     string $xPathQuery XPath expression to be evaluated.
   * @param     string or array $context Full path of a document node, starting
   *            from which the XPath expression should be evaluated.
   * @see       evaluate()
   */
  function _internalEvaluate($xPathQuery, $context='') {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;
    
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("evaluate");
      echo "Path: $xPathQuery\n";
      echo "Context: $context\n";
    }
    
    // Numpty check
    if (empty($xPathQuery)) {
      $this->_displayError("The $xPathQuery argument must have a value.", __LINE__);
      return FALSE;
    }
    
    // Split the paths that are sparated by '|' into distinct xPath expresions.
    $xPaths = &$this->_bracketExplode('|', $xPathQuery);
    if ($bDebugThisFunction) { echo "<hr>Split the paths that are sparated by '|'\n"; print_r($xPaths); }
    
    // Create an empty set to save the result.
    $result = array();
    
    // Run through all paths.
    reset($xPaths);
    while (list(,$xPath) = each($xPaths)) {
      // mini syntax check
      if (!$this->_bracketsCheck($xPath)) {
        $this->_displayError('While parsing an XPath expression, in the predicate ' .
        str_replace($xPath, '<b>'.$xPath.'</b>', $xPathQuery) .
        ', there was an invalid number of brackets or a bracket mismatch.', __LINE__);
      }
      // Save the current path.
      $this->xpath = $xPath;
      // Split the path at every slash *outside* a bracket.
      $steps = &$this->_bracketExplode('/', $xPath);
      if ($bDebugThisFunction) { echo "<hr>Split the path '$xPath' at every slash *outside* a bracket.\n "; print_r($steps); }
      // Check whether the first element is empty.
      if (empty($steps[0])) {
        // Remove the first and empty element. It's a starting  '//'.
        array_shift($steps);
      }
      // Start to evaluate the steps.
      $nodes = $this->_evaluateStep($context, $steps);
      // Remove duplicated nodes.
      $nodes = array_unique($nodes);
      // Add the nodes to the result set.
      $result = array_merge($result, $nodes);
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    
    // Return the result.
    return $result;
  }
  
  /**
   * Evaluates a step of an XPath expression.
   *
   * This method tries to evaluate a step from an XPath expression at a
   * specific context.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string or array $context Full document path of the context from
   *            which starting the step should be evaluated.  Either a single
   *             context, or an array of contexts.
   * @param     array $steps Array containing the remaining steps of the
   *            current XPath expression.
   * @return    array This method returns an array containing all nodes
   *            that are the result of evaluating the given XPath step.
   * @see       evaluate()
   */
  function _evaluateStep($context, $steps) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("_evaluateStep(Context:[$context], steps:[$steps])");
      if (is_array($context)) {
        echo "Context:\n";
        print_r($context);
      } else {
        echo "Context: $context\n";
      }
      echo "Steps: ";
      print_r($steps);
      echo "<hr>\n";
    }
    //////////////////////////////////////////////
    // Create an empty array for saving the nodes found.
    $nodes = array();
    // We may have an "array" of one context.  If so convert it from 
    // array to single string.  Often, this function will be called with
    // a /Path1[1]/Path[3]/Path[2] sytle predicate.
    if (is_array($context) && (count($context) == 1)) $context = $context[0];
    // Check whether the context is an array of contexts.
    if (is_array($context)) {
      // Run through the array.
      $size = sizeOf($context);
      for ($i=0; $i<$size; $i++) {
        if ($bDebugThisFunction) echo "Evaluating step for the {$context[$i]} context...\n";        
        // Call this method for this single path.
        $nodes = array_merge($nodes, $this->_evaluateStep($context[$i], $steps));
      }
    } else {
      // Get this step.
      $step = trim(array_shift($steps));
     
      if ($bDebugThisFunction) echo "Evaluating step $step\n";        
      // Create an array to save the new contexts.
      $contexts = array();
      
      // Get the axis of the current step.
      $axis = $this->_getAxis($step, $context);
      if ($bDebugThisFunction) {
        echo __LINE__.":Axis of step is:\n";
        print_r($axis);
        echo "\n";
      }
      // Check whether it's a function.
      if ($axis['axis'] == 'function') {
        // Check whether an array was return by the function.
        if (is_array($axis['node-test'])) {
          // Add the results to the list of contexts.
          $contexts = array_merge($contexts, $axis['node-test']);
        } else {
          // Add the result to the list of contexts.
          $contexts[] = $axis['node-test'];
        }
      } else {
        // Create the name of the method.
        $method = '_handleAxis_' . $axis['axis'];
      
        // Check whether the axis handler is defined.
        if (!method_exists(&$this, $method)) {
          // Display an error message.
          $this->_displayError('While parsing an XPath expression, the axis ' .
          $axis['axis'] . ' could not be handled, because this version does not support this axis.', __LINE__);
        }
        if ($bDebugThisFunction) echo "Calling user method $method\n";        
        
        // Perform an axis action.
        $contexts = $this->$method($axis, $context);
        if ($bDebugThisFunction) {
          echo "We found these contexts from this step:\n";        
          print_r( $contexts );
          echo "\n";
        }
        
        // Check whether there are predicates.
        if (count($axis['predicate']) > 0) {
          if ($bDebugThisFunction) echo "Filtering contexts by predicate...\n";        
          
          // Check whether each node fits the predicates.
          $contexts = $this->_checkPredicates($contexts, $axis['predicate'], $axis['node-test']);
        }
      }
      
      // Check whether there are more steps left.
      if (count($steps) > 0) {
        if ($bDebugThisFunction) echo "Evaluating next step given the context of the first step...\n";        
        // Continue the evaluation of the next steps.
        $nodes = $this->_evaluateStep($contexts, $steps);
      } else {
        // Save the found contexts.
        $nodes = $contexts;
      }
    }
    
    //////////////////////////////////////////////
    // Return the nodes found.
    $result =  $nodes;
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    
    // Return the result.
    return $result;
  }
  
  /**
   * Evaluates an XPath function
   *
   * This method evaluates a given XPath function with its arguments on a
   * specific node of the document.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $function Name of the function to be evaluated.
   * @param     string $arguments String containing the arguments being
   *            passed to the function.
   * @param     string $node Full path to the document node on which the
   *            function should be evaluated.
   * @return    mixed This method returns the result of the evaluation of
   *            the function. Depending on the function the type of the 
   *            return value can be different.
   * @see       evaluate()
   */
  function _evaluateFunction($function, $arguments, $node, $nodeTest) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("_evaluateFunction(Function:[$function], Arguments:[$arguments], node:[$node], nodeTest:[$nodeTest])");
      if (is_array($arguments)) {
        echo "Arguments:\n";
        print_r($arguments);
      } else {
        echo "Arguments: $arguments\n";
      }
      echo "<hr>\n";
    }

    /////////////////////////////////////
    // Remove whitespaces.
    $function  = trim($function);
    $arguments = trim($arguments);
    // Create the name of the function handling function.
    $method = '_handleFunction_'. $function;
    
    // Check whether the function handling function is available.
    if (!method_exists(&$this, $method)) {
      // Display an error message.
      $this->_displayError("While parsing an XPath expression, ".
        "the function \"$function\" could not be handled, because this ".
        "version does not support this function.", __LINE__);
    }

    if ($bDebugThisFunction) echo "Calling function $method($node, $arguments)\n";        
    
    // Return the result of the function.
    $result = $this->$method($node, $arguments, $nodeTest);

    //////////////////////////////////////////////
    // Return the nodes found.
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    
    // Return the result.
    return $result;
  }
  
  /**
   * Evaluates a predicate on a node.
   *
   * This method tries to evaluate a predicate on a given node.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the predicate
   *            should be evaluated.
   * @param     string $predicate String containing the predicate expression
   *            to be evaluated.
   * @return    mixed This method is called recursively. The first call should
   *            return a boolean value, whether the node matches the predicate
   *            or not. Any call to the method being made during the recursion
   *            may also return other types for further processing.
   * @see       evaluate()
   */
  function _evaluatePredicate($node, $predicate, $nodeTest) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction= FALSE;
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("_evaluatePredicate");
      echo "Node: [$node]\n";
      echo "Predicate: [$predicate]\n";
      echo "<hr>";
    }
    
    // Numpty check
    if (!is_string($predicate)) {
      // Display an error message.
      $this->_displayError("While parsing an XPath expression ".
        "there was an error in the following predicate, ".
        "because it was not a string. It was a '".$predicate."'", __LINE__);
      return FALSE;
    }
    $predicate = trim($predicate);
    // Numpty check.  If they give us an empty string, then this is an error. ## N.S
    if ($predicate === '') { 
      // Display an error message.
      $this->_displayError("While parsing an XPath expression ". 
        "there was an error in the predicate " .
        "because it was the null string.  If you wish to seach ".
        "for the empty string, you must use ''.", __LINE__);
      return FALSE;
    }
    /////////////////////////////////////////////
    // Quick ways out.
    // If it is a literal string, then we return the literal string.  ## N.S. --sb
    $stringDelimiterMismatsh = 0;
    if (preg_match(':^"(.*)"$:', $predicate, $regs)) {
      $result = $regs[1];
      $stringDelimiterMismatsh = strpos(' ' . $result, '"');
      if ($bDebugThisFunction) echo "Predicate is literal\n";        
    } elseif (preg_match(":^'(.*)'$:", $predicate, $regs)) {
      $result = $regs[1];
      $stringDelimiterMismatsh = strpos(' ' . $result, "'");
      if ($bDebugThisFunction) echo "Predicate is literal\n";        
    }
    
    if ($stringDelimiterMismatsh>0) {
      $this->_displayError("While parsing an XPath expression ".
            "there was an string delimiter miss match at pos [{$stringDelimiterMismatsh}] in the predicate string '{$predicate}'.", __LINE__);
      return FALSE;
    }
    
    // Check whether the predicate is just a digit.
    if (!isSet($result)) {
      if (is_numeric($predicate)) {
        // Return the value of the digit.
        $result = doubleval($predicate);
        if ($bDebugThisFunction) echo "Predicate is double\n";        
      }
    }
    /////////////////////////////////////////////
    // Check for operators.
    // Set the default position and the type of the operator.
    $position = 0;
    $operator = '';
    
    // Run through all operators and try to find one.
    if (!isSet($result)) {
      for ($i=0; $i<sizeOf($this->operators); $i++) {
        if ($position >0) break;
        $operator = $this->operators[$i];
        // Quickcheck. If not present don't wast time searching 'the hard way'
        if (strpos($predicate, $operator)===FALSE) continue;
        // Special check
        $position = $this->_searchString($predicate, $operator);
        // Check whether a operator was found.
        if ($position <= 0 ) continue;
        // Check whether it's the equal operator.
        if ($operator == '=') {
          // Also look for other operators containing the equal sign.
          switch ($predicate[$position-1]) {
            case '<' : 
              $position--;
              $operator = '<=';
              break;
            case '>' : 
              $position--;
              $operator = '>=';
              break;
            case '!' : 
              $position--;
              $operator = '!=';
              break;
            default:
          }
        }
        if ($operator == '*') {
          // Get some substrings.
          $character = substr($predicate, $position - 1, 1);
          $attribute = substr($predicate, $position - 11, 11);
        
          // Check whether it's an attribute selection.
          if (( $character == '@' ) || ( $attribute == 'attribute::' )) {
            // Don't use the operator.
            $operator = '';
            $position = -1;
          }
        }
      } // end while each($this->operators)
      
      // Check whether an operator was found.        
      if ($position > 0) {
        if ($bDebugThisFunction) echo "\nPredicate operator is a [$operator] at pos '$position'";        
        // Get the left and the right part of the expression.
        $left_predicate  = trim(substr($predicate, 0, $position));
        $right_predicate = trim(substr($predicate, $position + strlen($operator)));
        if ($bDebugThisFunction) echo "\nLEFT:[$left_predicate]  oper:[$operator]  RIGHT:[$right_predicate]";        
      
        // Remove whitespaces.
        $left_predicate  = trim($left_predicate);
        $right_predicate = trim($right_predicate);
        // Evaluate the left and the right part.
        if ($bDebugThisFunction) echo "\nEvaluating LEFT:[$left_predicate]";
        $left = $this->_evaluatePredicate($node, $left_predicate, $nodeTest);
        if ($bDebugThisFunction) echo "$left_predicate evals as: $left - ";
        // Only evaluate the right part if we need to.
        $right = FALSE;
        if (!$left and ($operator == ' and ')) {
          if ($bDebugThisFunction) echo "\nNo point in evaluating the right predicate: [$right_predicate]";
        } else {
          if ($bDebugThisFunction) echo "\nEvaluating RIGHT:[$right_predicate]";
          $right = $this->_evaluatePredicate($node, $right_predicate, $nodeTest);
          if ($bDebugThisFunction) echo "$right_predicate evals as: $right \n";
        }
        // Check the kind of operator.
        $b_result = FALSE;
        switch ( $operator) {
          case ' or ':
            // Return the two results connected by an 'or'.
            $b_result = (bool)( $left or $right );
            break;
          case ' and ':
            // Return the two results connected by an 'and'.
            $b_result = (bool)( $left and $right );
            break;
          case '=':
            // Compare the two results.
            $b_result = (bool)( $left == $right ); 
            break;                    
          case '!=':
            // Check whether the two results are not equal.
            $b_result = (bool)( $left != $right );
            break;                    
          case '<=':
            // Compare the two results.
            $b_result = (bool)( $left <= $right );
            break;                    
          case '<':
            // Compare the two results.
            $b_result = (bool)( $left < $right );
            break;                
          case '>=':
            // Compare the two results.
            $b_result = (bool)( $left >= $right );
            break;                    
          case '>':
            // Compare the two results.
            $b_result = (bool)( $left > $right );
            break;                    
          case '+':
            // Return the result by adding one result to the other.
            $b_result = $left + $right;
            break;                    
          case '-':
            // Return the result by decrease one result by the other.
            $b_result = $left - $right;
            break;                    
          case '*':
            // Return a multiplication of the two results.
            $b_result =  $left * $right;
            break;                    
          case ' div ':
            // Return a division of the two results.
            if ($right == 0) {
              // Display an error message.
              $this->_displayError('While parsing an XPath '.
                'predicate, a error due a division by zero '.
                'occured.', __LINE__);
            } else {
              // Return the result of the division.
              $b_result = $left / $right;
            }
            break;
          case ' mod ':
            // Return a modulo of the two results.
            $b_result = $left % $right;
            break;                    
        }
        $result = $b_result;
      }
    }

    /////////////////////////////////////////////
    // Check for functions.
    // Check whether the predicate is a function.
    if (!isSet($result)) {
      // do not catch the text() node, which looks like a function in its pattern
      if (preg_match(':\(:U', $predicate) && !preg_match(":text\(\)(\[\d*\])?$:",$predicate) ) {
        // Get the position of the first bracket.
        $start = strpos($predicate, '(');
        // If we search for the right bracket from the end of the string, we can 
        // support nested function calls.  Fix by Andrei Zmievski
        $end   = strrpos($predicate, ')');
      
        // Get everything before, between and after the brackets.
        $before  = substr($predicate, 0, $start);
        $between = substr($predicate, $start + 1, $end - $start - 1);
        $after   = substr($predicate, $end + 1);
      
        // Trim each string.
        $before  = trim($before);
        $between = trim($between);
        $after   = trim($after);
      
        if ($bDebugThisFunction) echo "\nPredicate is function \"$before\"";        
        // Check whether there's something after the bracket.
        if (!empty($after)) {
          // Display an error message.
          $this->_displayError('While parsing an XPath expression there was an error in the predicate ' .
            str_replace($predicate,'<b>'.$predicate.'</b>', $this->xpath) .
            '. After a closing bracket there was something unknown: "'. $after .'"', __LINE__);
        }
      
        // Check whether it's a function.
        if (empty($before) && empty($after)) {
          // Evaluate the content of the brackets.
          $result = $this->_evaluatePredicate($node, $between, $nodeTest);
        }
        elseif ($this->_isFunction($before)) {
          // Return the evaluated function.
          $result = $this->_evaluateFunction($before, $between, $node, $nodeTest);
        } 
        else {
          // Display an error message.
          $this->_displayError('While parsing a predicate in an XPath expression, a function '.
            str_replace($before, '<b>'.$before.'</b>', $this->xpath) . 
            ' was found, which is not yet supported by the parser.', __LINE__);
        }
      }
    }
    
    /////////////////////////////////////////////
    // Else it must just be an XPath expression.
    // Check whether it's an XPath expression.
    if (!isSet($result)) {
      if ($bDebugThisFunction) echo "\nPredicate is XPath expression.";
      $a_xpath_result = $this->_internalEvaluate($predicate, $node);
      if (count($a_xpath_result) > 0) {
        // Convert the array.
        $result = explode("|", implode("|", $a_xpath_result));
        // Get the value of the first result (which means we want to concat all the text...unless
        // a specific text() node has been given, and it will switch off to substringData
        $result = $this->wholeText($a_xpath_result[0]);            
      }
    }
    
    // Else no content so return the empty string.  ## N.S
    if (!isSet($result)) $result = '';
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    
    // Return the array of nodes.
    return $result;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Check functions for tailoring a node set
  
  /**
   * -- sb:stoped
   *
   * Checks whether a node matches predicates.
   *
   * This method checks whether a list of nodes passed to this method match
   * a given list of predicates. 
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $nodes Array of full paths of all nodes to be tested.
   * @param     array $predicates Array of predicates to use.
   * @return    array The array returned by this method contains a list of
   *            all nodes matching the given predicates.
   * @see       _evaluateStep()
   */
  function _checkPredicates($nodes, $predicates, $nodeTest) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $a_start_time = $this->_beginDebugFunction("_checkPredicates(Nodes:[$nodes], Predicates:[$predicates])");
      echo "Nodes:";
      print_r($nodes);
      echo "Predicates:";
      print_r($predicates);
      echo "<hr>";
    }
    //////////////////////////////////////////////
    // Create an empty set of nodes.
    $result = array();
    
    // Run through all nodes.
    for ($i=0; $i<sizeOf($nodes); $i++) {
      $node = &$nodes[$i];
      // Create a variable whether to add this node to the node-set.
      $add = TRUE;
      
      // Run through all predicates.
      for ($j=0; $j<sizeOf($predicates); $j++) {
        $predicate = &$predicates[$j]; 
        if ($bDebugThisFunction) echo "Evaluating predicate \"$predicate\"\n";
        // Check whether the predicate is just an number.
        if (preg_match('/^\d+$/', $predicate)) {
          if ($bDebugThisFunction) echo "Taking short cut and calling _handleFunction_position() directly.\n";
          // Take a short cut.  If it is just a position, then call 
          // _handleFunction_position() directly.  70% of the
          // time this will be the case. ## N.S
          $check = (bool) ($predicate == $this->_handleFunction_position($node, '', $nodeTest));
          // Enhance the predicate.
          //                    $predicate .= "=position()";
        } else {                
          // Else do the predicate check the long and thorough way.
          $check = $this->_evaluatePredicate($node, $predicate, $nodeTest);
        }
        // Check whether it's a string.
        if (is_string($check) && ( ( $check == '' ) 
           || ( $check == $predicate ) )) {
          // Set the result to FALSE.
          $check = FALSE;
        } 
        else if (is_bool($check) )  {
          // 0 and 1 are both bools and ints.  We need to capture the bools
          // as they might have been the intended result                    ## N.S
        } else
        // Check whether it's an integer.
        if (is_int($check)) {
          // Check whether it's the current position.
          if ($check == $this->_handleFunction_position($node, '', $nodeTest)) {
            // Set it to TRUE.
            $check = TRUE;
          }
          else {
            // Set it to FALSE.
            $check = FALSE;
          }
        }
        if ($bDebugThisFunction) echo "Node $node matches predicate $predicate: " . (($check) ? "TRUE" : "FALSE") ."\n";
        // Check whether the predicate is OK for this node.
        $add = $add && $check;
      }
       
      // Check whether to add this node to the node-set.
      if ($add) {
        // Add the node to the node-set.
        $result[] = $node;
      }            
      if ($bDebugThisFunction) echo "Node $node matches: " . (($add) ? "TRUE" : "FALSE") ."\n\n";        
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($a_start_time, $result);
    }
    
    // Return the array of nodes.
    return $result;
  }
  
  /**
   * Checks whether a node matches a node-test.
   *
   * This method checks whether a node in the document matches a given
   * node-test.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $context Full path of the node, which should be tested
   *            for matching the node-test.
   * @param     string $nodeTest String containing the node-test for the
   *            node.
   * @return    boolean This method returns TRUE if the node matches the
   *            node-test, otherwise FALSE.
   * @see       evaluate()
   */
  function _checkNodeTest($context, $nodeTest) {
    // Check whether it's a function.
    if (preg_match('/\(/U', $nodeTest)) {
      // Get the type of function to use.
      $function = $this->_prestr($nodeTest, '(');
      
      // Check whether the node fits the method.
      switch ( $function) {
        case 'node':
          // Add this node to the list of nodes.
          return TRUE;
        case 'text':
          // Check whether the node has some text.
          $tmp = implode('', $this->nodes[$context]['text']);
          if (!empty($tmp)) {
            // Add this node to the list of nodes.
            return TRUE;
          }
          break;
        case 'comment':
          // Check whether the node has some comment.
          if (!empty($this->nodes[$context]['comment'])) {
            // Add this node to the list of nodes.
            return TRUE;
          }
          break;
        case 'processing-instruction':
          // Get the literal argument.
          $literal = $this->_afterstr($axis['node-test'], '(');
          
          // Cut the literal.
          $literal = substr($literal, 0, strlen($literal) - 1);
          
          // Check whether a literal was given.
          if (!empty($literal)) {
            // Check whether the node's processing instructions
            // are matching the literals given.
            if ($this->nodes[$context]['processing-instructions'] == $literal) {
              // Add this node to the node-set.
              return TRUE;
            }
          } else {
            // Check whether the node has processing
            // instructions.
            if (!empty($this->nodes[$context]['processing-instructions'])) {
              // Add this node to the node-set.
              return TRUE;
            }
          }
          break;
        default:
          // Display an error message.
          $this->_displayError('While parsing an XPath expression there was an undefined function called "' .
             str_replace($function, '<b>'.$function.'</b>', $this->xpath) .'"', __LINE__);
      }
    }
    elseif ($nodeTest == '*') {
      // Add this node to the node-set.
      return TRUE;
    }
    elseif (preg_match('/^[a-zA-Z0-9\-_]+/', $nodeTest)) {
      // Check whether the node-test can be fulfilled.
      if ($this->nodes[$context]['name'] == $nodeTest) {
        // Add this node to the node-set.
        return TRUE;
      }
    }
    else {
      // Display an error message.
      $this->_displayError("While parsing the XPath expression \"{$this->xpath}\" ".
        "an empty and therefore invalid node-test has been found.", __LINE__);
    }
    
    // Don't add this context.
    return FALSE;
  }
   
  /////////////////////////////////////////////////
  // ########################################### //
  // Functions to handle each of the different xpath axes.

  /**
   * Handles the XPath child axis.
   *
   * This method handles the XPath child axis.  It essentially filters out the
   * children to match the name specified after the /
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_Child($axis, $context) {
    // Create an empty node-set to hold the results of the child matches
    $nodes = array();

    if ( $axis["node-test"] == "cdata" ) {
      if ( !isSet($this->nodes[$context]["text"]) ) return "";
      @reset($this->nodes[$context]['text']);
      while(list($index, $text) = each($this->nodes[$context]['text'])) {
        $nodes[] = $context . "/text()[".($index + 1)."]";
      }
    }
    else {
      // Get a list of all children.
      $allChildren = &$this->nodes[$context]['children'];
      // Run through all children in the order they were set.
      for ( $i=0; $i < sizeOf($allChildren); $i++ ) {
        $child = $context.'/'.$allChildren[$i];
        // Check whether 
        if ($this->_checkNodeTest($child, $axis['node-test'])) 
        {
          // Add the child to the node-set.
          $nodes[] = $child;
        }
      }
    }
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath parent axis.
   *
   * This method handles the XPath parent axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_parent ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Check whether the parent matches the node-test.
    if ($this->_checkNodeTest($this->nodes[$context]['parent'], $axis['node-test'])) {
      // Add this node to the list of nodes.
      $nodes[] = $this->nodes[$context]['parent'];
    }
    
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath attribute axis.
   *
   * This method handles the XPath attribute axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_attribute ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Check whether all nodes should be selected.
    $nodeAttr = &$this->nodes[$context]['attributes'];
    if ($axis['node-test'] == '*') {
      // Check whether there are attributes.
      if (count($nodeAttr) > 0) {
        // Run through the attributes.
        reset($nodeAttr);
        while (list($key) = each($nodeAttr)) {
          // Add this node to the node-set.
          $nodes[] = $context.'/attribute::'.$key;
        }
      }
    }
    elseif (isSet($nodeAttr[$axis['node-test']]) AND strlen($nodeAttr[$axis['node-test']])) {
      // Add this node to the node-set.
      $nodes[] = $context . '/attribute::'. $axis['node-test'];
    }
      
    // Return the nodeset.
    return $nodes;
  }
   
  /**
   * Handles the XPath self axis.
   *
   * This method handles the XPath self axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_self ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Check whether the context match the node-test.
    if ($this->_checkNodeTest($context, $axis['node-test'])) {
      // Add this node to the node-set.
      $nodes[] = $context;
    }
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath descendant axis.
   *
   * This method handles the XPath descendant axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_descendant ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();        
    // Get a list of all children.
    $children = &$this->nodes[$context]['children'];
    // Run through all children in the order they where set.
    $childSize = sizeOf($children);
    for ($i=0; $i<$childSize; $i++) {
      $child = $context.'/'.$children[$i];
      // Check whether the child matches the node-test.
      if ($this->_checkNodeTest($child, $axis['node-test'])) {
        // Add the child to the list of nodes.
        $nodes[] = $child;
      }
      // Recurse to the next level.
      $nodes = array_merge($nodes, $this->_handleAxis_descendant($axis, $child));
    }
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath ancestor axis.
   *
   * This method handles the XPath ancestor axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_ancestor ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Get the parent of the current node.
    $parent = $this->nodes[$context]['parent'];
    
    // Check whether the parent isn't empty.
    if (!empty($parent)) {
      // Check whether the parent matches the node-test.
      if ($this->_checkNodeTest($parent, $axis['node-test'])) {
        // Add the parent to the list of nodes.
        $nodes[] = $parent;
      }
      
      // Handle all other ancestors.
      $nodes = array_merge($nodes, $this->_handleAxis_ancestor($axis, $parent));
    }
    
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath namespace axis.
   *
   * This method handles the XPath namespace axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_namespace ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Check whether all nodes should be selected.
    if (!empty($this->nodes[$context]['namespace'])) {
      // Add this node to the node-set.
      $nodes[] = $context;
    }
      
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath following axis.
   *
   * This method handles the XPath following axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_following ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Get the current document position.
    $position = $this->nodes[$context]['doc-pos'];
    
    // Run through all nodes of the document.
    reset($this->nodes);
    while (list($node) = each($this->nodes)) {
      // Check whether this is the context node.
      if ($node == $context ) break;
    }
    while (list($node) = each($this->nodes)) {
      // Check whether this is the context node.
      if ($this->nodes[$node]['doc-pos'] <= $position) break;
    }
    do {
        // Check whether the node fits the node-test.
        if ($this->_checkNodeTest($node, $axis['node-test'])) {
          // Add the node to the list of nodes.
          $nodes[] = $node;
        }
      
    } while (list($node) = each($this->nodes));
      
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath preceding axis.
   *
   * This method handles the XPath preceding axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_preceding ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Get the current document position.
    $position = $this->nodes[$context]['doc-pos'];
    
    // Run through all nodes of the document.
    reset($this->nodes);
    while (list($node) = each($this->nodes)) {
      // skip super-Root
      if (empty($node)) continue;
      // Check whether this is the context node.
      
      if ($node == $context) {
        // After this we won't look for more nodes.
        break;
      }
      if (!strncmp($node, $context, strLen($node))) {
        continue;
      }
      // Check whether the node fits the node-test.
      if ($this->_checkNodeTest($node, $axis['node-test'])) {
        // Add the node to the list of nodes.
        $nodes[] = $node;
      }
    }
      
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath following-sibling axis.
   *
   * This method handles the XPath following-sibling axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_following_sibling ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Get all children from the parent.
    $siblings = &$this->_handleAxis_child($axis, $this->nodes[$context]['parent']);
    // Create a flag whether the context node was already found.
    $found = FALSE;
    
    // Run through all siblings.
    $size = sizeOf($siblings);
    for ($i=0; $i<$size; $i++) {
      $sibling = &$siblings[$i];
      
      // Check whether the context node was already found.
      if ($found) {
        // Check whether the sibling matches the node-test.
        if ($this->_checkNodeTest($sibling, $axis['node-test'])) {
          // Add the sibling to the list of nodes.
          $nodes[] = $sibling;
        }
      }
      
      // Check if we reached *this* context node.
      if ($sibling == $context) {
        // Continue looking for other siblings.
        $found = TRUE;
      }
    }
      
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath preceding-sibling axis.
   *
   * This method handles the XPath preceding-sibling axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_preceding_sibling ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Get all children from the parent.
    $siblings = $this->_handleAxis_child($axis, $this->nodes[$context]['parent']);
    
    // Run through all siblings.
    $size = sizeOf($siblings);
    for ($i=0; $i<$size; $i++) {
      $sibling = &$siblings[$i];
      // Check whether this is the context node.
      if ($sibling == $context) {
        // Don't continue looking for other siblings.
        break;
      }
    
      // Check whether the sibling matches the node-test.
      if ($this->_checkNodeTest($sibling, $axis['node-test'])) {
        // Add the sibling to the list of nodes.
        $nodes[] = $sibling;
      }
    }
      
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath descendant-or-self axis.
   *
   * This method handles the XPath descendant-or-self axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_descendant_or_self ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Read the nodes.
    $nodes = array_merge(
      $this->_handleAxis_self($axis, $context),
      $this->_handleAxis_descendant($axis, $context)
      );
    
    // Return the nodeset.
    return $nodes;
  }
  
  /**
   * Handles the XPath ancestor-or-self axis.
   *
   * This method handles the XPath ancestor-or-self axis.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     array $axis Array containing information about the axis.
   * @param     string $context Node from which starting the axis should
   *            be processed.
   * @return    array This method returns an array containing all nodes 
   *            that were found during the evaluation of the given axis.
   * @see       evaluate()
   */
  function &_handleAxis_ancestor_or_self ( $axis, $context) {
    // Create an empty node-set.
    $nodes = array();
    
    // Read the nodes.
    $nodes = array_merge(
      $this->_handleAxis_self($axis, $context),
      $this->_handleAxis_ancestor($axis, $context)
    );
    
    // Return the nodeset.
    return $nodes;
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Functions to handle each of the different xpath functions.
  
  /**
   * Handles the XPath function last.
   *
   * This method handles the XPath function last.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_last ( $node, $arguments, $nodeTest) {
    // Calculate the size of the context.
    $parent   = $this->nodes[$node]['parent'];
    if ($nodeTest == "*")
    {
      $context = sizeOf($this->nodes[$parent]['children']);
    }
    else if ($nodeTest == "cdata")
    {
      $node = substr($node,0,strrpos($node,"/text()"));
      $context = sizeOf($this->nodes[$node]["text"]);
    }
    else
    {
      $children = $this->nodes[$parent]['childCount'];
      $context  = $children[$this->nodes[$node]['name']];
    }
    // Return the size.
    return $context;
  }
  
  /**
   * Handles the XPath function position.
   *
   * This method handles the XPath function position.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_position ( $node, $arguments, $nodeTest) {
    // return the context-position.
    if ($nodeTest == "*")
    {
      // if we are matching all children, then we need to find the position regardless of name
      $parent = $this->nodes[$node]['parent'];
      $indexChildren = array_flip($this->nodes[$parent]['children']); 
      $currentChild = substr($node,strrpos($node,"/")+1,strlen($node));
      $context = $indexChildren[$currentChild] + 1;
    }
    // if we are looking for text nodes, we go about it a bit differently
    else if( $nodeTest == "cdata" ) {
      $context = substr($node,strrpos($node,"[")+1,-1);
    }
    else
    {
      $context = $this->nodes[$node]['context-pos'];
    }
    return $context;
  }
  
  /**
   * Handles the XPath function count.
   *
   * This method handles the XPath function count.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_count ( $node, $arguments, $nodeTest) {
    // Evaluate the argument of the method as an XPath and return
    // the number of results.
    return count($this->_internalEvaluate($arguments, $node));
  }
  
  /**
   * Handles the XPath function id.
   *
   * This method handles the XPath function id.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_id ( $node, $arguments, $nodeTest) {
    // Trim the arguments.
    $arguments = trim($arguments);
    
    // Now split the arguments.
    $arguments = explode(' ', $arguments);
    
    // Check whether 
    
    // Create a list of nodes.
    $nodes = array();
    
    // Run through all nodes of the document.
    reset($this->nodes);
    while (list($node) = each($this->nodes)) {
      // skip super-Root
      if (empty($node)) continue;
      // Check whether the node has the ID we're looking for.
      if (in_array($this->nodes[$node]['attributes']['id'], $arguments)) {
        // Add this node to the list of nodes.
        $nodes[] = $node;
      }
    }
    
    // Return the list of nodes.
    return $nodes;
  }
  
  /**
   * Handles the XPath function name.
   *
   * This method handles the XPath function name.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_name ( $node, $arguments, $nodeTest) {
    // Return the name of the node.
    return $this->nodes[$node]['name'];
  }
  
  /**
   * Handles the XPath function string.
   *
   * This method handles the XPath function string.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_string ( $node, $arguments, $nodeTest) {
    // Check what type of parameter is given
    if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $arguments) 
       || preg_match('/^\.[0-9]+$/', $arguments)) {
      // Convert the digits to a number.
      $number = doubleval($arguments);
        
      // Return the number.
      return strval($number);
    }
    elseif (is_bool($arguments)) {
      // Check whether it's TRUE.
      if ($arguments == TRUE) {
        // Return TRUE as a string.
        return 'TRUE';
      }
      else {
        // Return FALSE as a string.
        return 'FALSE';
      }
    }
    elseif (!empty($arguments)) {
      // Use the argument as an XPath.
      $result = $this->_internalEvaluate($arguments, $node);
        
      // Get the first argument.
      $result = explode('|', implode('|', $result));
        
      // Return the first result as a string.
      return $result[0];
    }
    elseif (empty($arguments)) {
      // Return the current node.
      return $node;
    }
    else {
      // Return an empty string.
      return '';
    }
  }
  
  /**
   * Handles the XPath function concat.
   *
   * This method handles the XPath function concat.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_concat ( $node, $arguments, $nodeTest) {
    // Split the arguments.
    $arguments = explode(',', $arguments);
      
    // Run through each argument and evaluate it.
    for ( $i = 0; $i < sizeof($arguments); $i++) {
      // Trim each argument.
      $arguments[$i] = trim($arguments[$i]);
        
      // Evaluate it.
      $arguments[$i] = $this->_evaluatePredicate($node, $arguments[$i], $nodeTest);
    }
      
    // Put the string together.
    $arguments = implode('', $arguments);
      
    // Return the string.
    return $arguments;
  }
  
  /**
   * Handles the XPath function starts-with.
   *
   * This method handles the XPath function starts-with.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_starts_with ($node, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
      
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($node, $first, $nodeTest);
    $second = $this->_evaluatePredicate($node, $second, $nodeTest);
      
    // Check whether the first string starts with the second one.
    if (ereg('^'.$second, $first)) {
      // Return TRUE.
      return TRUE;
    } else {
      // Return FALSE.
      return FALSE;
    }
  }
  
  /**
   * Handles the XPath function contains.
   *
   * This method handles the XPath function contains.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_contains ( $node, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
    //echo "Predicate: $arguments First: ".$first." Second: ".$second."\n";
    
    // Evaluate each argument.
    $first = $this->_evaluatePredicate($node, $first, $nodeTest);
    $second = $this->_evaluatePredicate($node, $second, $nodeTest);
    //echo $second.": ".$first."\n";
    // If the search string is null, then the provided there is a value it will contain it as
    // it is considered that all strings contain the empty string. ## N.S.
    if ($second==='') return TRUE;
     
    // Check whether the first string starts with the second one.
    if (strpos($first, $second) === FALSE) {
      return FALSE;
    } else {
      return TRUE;
    }
  }
  
  /**
   * Handles the XPath function substring-before.
   *
   * This method handles the XPath function substring-before.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_substring_before ( $node, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
      
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($node, $first, $nodeTest);
    $second = $this->_evaluatePredicate($node, $second, $nodeTest);
      
    // Return the substring.
    return $this->_prestr(strval($first), strval($second));
  }
  
  /**
   * Handles the XPath function substring-after.
   *
   * This method handles the XPath function substring-after.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_substring_after ( $node, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
      
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($node, $first, $nodeTest);
    $second = $this->_evaluatePredicate($node, $second, $nodeTest);
      
    // Return the substring.
    return $this->_afterstr(strval($first), strval($second));
  }
  
  /**
   * Handles the XPath function substring.
   *
   * This method handles the XPath function substring.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_substring ( $node, $arguments, $nodeTest) {
    // Split the arguments.
    $arguments = explode(",", $arguments);
      
    // Run through all arguments.
    for ( $i = 0; $i < sizeof($arguments); $i++) {
      // Trim the string.
      $arguments[$i] = trim($arguments[$i]);
        
      // Evaluate each argument.
      $arguments[$i] = $this->_evaluatePredicate($node, $arguments[$i], $nodeTest);
    }
      
    // Check whether a third argument was given.
    if (!empty($arguments[2])) {
      // Return the substring.
      return substr(strval($arguments[0]), $arguments[1] - 1,
        $arguments[2]);
    } else {
      // Return the substring.
      return substr(strval($arguments[0]), $arguments[1] - 1);
    }
  }
  
  /**
   * Handles the XPath function string-length.
   *
   * This method handles the XPath function string-length.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_string_length ( $node, $arguments, $nodeTest) {
    // Trim the argument.
    $arguments = trim($arguments);
    // Evaluate the argument.
    $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
    // Return the length of the string.
    return strlen(strval($arguments));
  }
  
  /**
   * Handles the XPath function normalize-space.
   *
   * The normalize-space function returns the argument string with whitespace 
   * normalized by stripping leading and trailing whitespace and replacing sequences 
   * of whitespace characters by a single space.
   * If the argument is omitted, it defaults to the context node converted to a string, 
   * in other words the string-value of the context node
   *
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    string trimed string
   * @see       evaluate()
   */
  function _handleFunction_normalize_space ( $node, $arguments, $nodeTest) {
      // Trim the argument.
      if (empty($arguments)) {
        $arguments = $this->nodes[$node]['parent'].'/'.$this->nodes[$node]['name'].'['.$this->nodes[$node]['context-pos'].']';
      } else {
         $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
      }
      $arguments = trim(preg_replace (";[[:space:]]+;s",' ',$arguments));
      return $arguments;
  }
  
  /**
   * Handles the XPath function translate.
   *
   * This method handles the XPath function translate.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_translate ( $node, $arguments, $nodeTest) {
    // Split the arguments.
    $arguments = explode(',', $arguments);
    
    // Run through all arguments.
    for ( $i = 0; $i < sizeof($arguments); $i++) {
      // Trim the argument.
      $arguments[$i] = trim($arguments[$i]);
      // Evaluate the argument.
      $arguments[$i] = $this->_evaluatePredicate($node, $arguments[$i], $nodeTest);
    }
      
    // Return the translated string.
    return strtr($arguments[0], $arguments[1], $arguments[2]);
  }
  
  /**
   * Handles the XPath function boolean.
   *
   * This method handles the XPath function boolean.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_boolean ( $node, $arguments, $nodeTest) {
    // Trim the arguments.
    $arguments = trim($arguments);
    
    // Check what type of parameter is given
    if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $arguments) 
       || preg_match('/^\.[0-9]+$/', $arguments)) {
      // Convert the digits to a number.
      $number = doubleval($arguments);
      
      // Check whether the number zero.
      if ($number == 0) {
        // Return FALSE.
        return FALSE;
      } else {
        // Return TRUE.
        return TRUE;
      }
    }
    elseif (empty($arguments)) {
      // Sorry, there were no arguments.
      return FALSE;
    }
    else {
      // Try to evaluate the argument as an XPath.
      $result = $this->_internalEvaluate($arguments, $node);
      
      // Check whether we found something.
      if (count($result) > 0) {
        // Return TRUE.
        return TRUE;
      } else {
        // Return FALSE.
        return FALSE;
      }
    }
  }
  
  /**
   * Handles the XPath function not.
   *
   * This method handles the XPath function not.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_not ( $node, $arguments, $nodeTest) {
    // Trim the arguments.
    $arguments = trim($arguments);
    
    // Return the negative value of the content of the brackets.
    return !$this->_evaluatePredicate($node, $arguments, $nodeTest);
  }
  
  /**
   * Handles the XPath function TRUE.
   *
   * This method handles the XPath function TRUE.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_true ( $node, $arguments, $nodeTest) {
    // Return TRUE.
    return TRUE;
  }
  
  /**
   * Handles the XPath function FALSE.
   *
   * This method handles the XPath function FALSE.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_false ( $node, $arguments, $nodeTest) {
    // Return FALSE.
    return FALSE;
  }
  
  /**
   * Handles the XPath function lang.
   *
   * This method handles the XPath function lang.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_lang ( $node, $arguments, $nodeTest) {
    // Trim the arguments.
    $arguments = trim($arguments);
    
    // Check whether the node has an language attribute.
    if (empty($this->nodes[$node]['attributes']['xml:lang'])) {
      // Run through the ancestors.
      while ( !empty($node)) {
        // Select the parent node.
        $node = $this->nodes[$node]['parent'];
        
        // Check whether there's a language definition.
        if (!empty($this->nodes[$node]['attributes']['xml:lang'])) {
          // Check whether it's the language, the user asks for.
          if (eregi('^'.$arguments, $this->nodes[$node]['attributes']['xml:lang'])) {
            // Return TRUE.
            return TRUE;
          } else {
            // Return FALSE.
            return FALSE;
          }
        }
      }
      
      // Return FALSE.
      return FALSE;
    } else {
      // Check whether it's the language, the user asks for.
      if (eregi('^'.$arguments, $this->nodes[$node]['attributes']['xml:lang'])) {
        // Return TRUE.
        return TRUE;
      } else {
        // Return FALSE.
        return FALSE;
      }
    }
  }
  
  /**
   * Handles the XPath function number.
   *
   * This method handles the XPath function number.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_number ( $node, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
    }
    // Check the type of argument.
    if (is_numeric($arguments)) {
      // Return the argument as a number.
      return doubleval($arguments);
    }
    elseif (is_bool($arguments)) {
      // Check whether it's TRUE.
      if ($arguments == TRUE) {
        // Return 1.
        return 1;
      } else {
        // Return 0.
        return 0;
      }
    }
  }
  
  /**
   * Handles the XPath function sum.
   *
   * This method handles the XPath function sum.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_sum ( $node, $arguments, $nodeTest) {
    // Trim the arguments.
    $arguments = trim($arguments);
    
    // Evaluate the arguments as an XPath expression.
    $result = $this->_internalEvaluate($arguments, $node);
    
    // Create a variable to save the sum.
    $sum = 0;
    
    // Run through all results.
    for ($i=0; $i<sizeOf($result); $i++) {
      // Get the value of the node.
      $value = $this->substringData($result[$i]);
      // Add it to the sum.
      $sum += doubleval($value);
    }
    
    // Return the sum.
    return $sum;
  }
  
  /**
   * Handles the XPath function floor.
   *
   * This method handles the XPath function floor.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_floor ( $node, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
    }
    // Convert the arguments to a number.
    $arguments = doubleval($arguments);
    
    // Return the result
    return floor($arguments);
  }
  
  /**
   * Handles the XPath function ceiling.
   *
   * This method handles the XPath function ceiling.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_ceiling ( $node, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
    }
    
    // Convert the arguments to a number.
    $arguments = doubleval($arguments);
    
    // Return the result
    return ceil($arguments);
  }
  
  /**
   * Handles the XPath function round.
   *
   * This method handles the XPath function round.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $node Full path of the node on which the function
   *            should be processed.
   * @param     string $arguments String containing the arguments that were
   *            passed to the function.
   * @return    mixed Depending on the type of function being processed this 
   *            method returns different types.
   * @see       evaluate()
   */
  function _handleFunction_round ( $node, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($node, $arguments, $nodeTest);
    }
    
    // Convert the arguments to a number.
    $arguments = doubleval($arguments);
    
    // Return the result
    return round($arguments);
  }
  
  /////////////////////////////////////////////////
  // ########################################### //
  // General helper functions

  /**
   * Set the content of a node.
   *
   * This method modifies the content of a text node. If it's an attribute node, then
   * the value of the attribute will be modified, otherwise the complete character 
   * data of the specific text node will be set.  This function is used for all the Data
   * functions, as the modification can be reduced to a single statment once all the checks
   * and initializations are done
   *
   * @param     string $xPathQuery path to the node (See text above). *READONLY*
   * @param     string $content String containing the content to be set. *READONLY*
   * @param     bool   $replace if TRUE the given substring will be replaced with the $content...
   *            else it will be inserted or appended
   * @param     int    $offset will be where the string will be inserted or begin to be replaced..
   *            a value of 1 is the beginning, 0 will be the end
   * @see       appendData(), insertData(), replaceData(), deleteData(), substringData()
   */
  function _setContent($absoluteXPath, $content, $replace, $offset = -1, $count = 0 ) {
    // we default offset to -1 for the end of the string
    // Check whether this path goes right to a text node, if so then modify the cdata
    if ( preg_match(":(.*)/text\(\)(\[(.*)\])?$:U",$absoluteXPath,$matches) ) {
      $absoluteXPath = $matches[1];
      // default to the first text node if a text node was not specified
      $textPart = isset($matches[2]) ? substr($matches[2],1,-1) : 1;
      // Numpty check
      if ( !isSet($this->nodes[$absoluteXPath]) ) {
        // Try to evaluate the absoluteXPath (since it really isn't an absolutePath)
        $resultArr = $this->match("$absoluteXPath/text()[$textPart]");
        if ( sizeOf($resultArr) == 1 ) {
          preg_match(":(.*)/text\(\)(\[(.*)\])?$:U",$resultArr[0],$matches); 
          $absoluteXPath = $matches[1];
          $textPart = isset($matches[2]) ? substr($matches[2],1,-1) : 1;
        } 
        else {
          $this->_displayError("The $absoluteXPath/text() does not evaluate to a single text node in this document.", __LINE__, FALSE);
          return;
        }
      }
      if ( !isSet($this->nodes[$absoluteXPath]["text"][$textPart - 1]) )
      {
        $this->_displayError("The $absoluteXPath does not have a text value at position ".($textPart-1), __LINE__, FALSE);
        return;
      }
      // Get a reference to the text node
      $textNode = &$this->nodes[$absoluteXPath]["text"][$textPart - 1];
    }
    else if ( preg_match(";(.*)/(attribute::|@)([^/]*)$;U",$absoluteXPath,$matches) ) {
      $absoluteXPath = $matches[1];
      $attribute = $matches[3];
      // Numpty check (1. make sure root path exists 2. make sure attribute exists)
      if ( !isSet($this->nodes[$absoluteXPath]) ) {
        // Try to evaluate the absoluteXPath (since it really isn't an absolutePath)
        $resultArr = $this->match("$absoluteXPath/attribute::$attribute");
        if ( sizeOf($resultArr) == 1 ) {
          preg_match(";(.*)/attribute::([^/]*)$;U",$resultArr[0],$matches);
          $absoluteXPath = $matches[1];
          $attribute = $matches[2];
        } 
        else {
          $this->_displayError("The $absoluteXPath/attribute::$attribute does not evaluate to a single node.", __LINE__, FALSE);
          break;
        }
      }
      else if ( !isSet($this->nodes[$absoluteXPath]["attributes"][$attribute]) ) {
        $this->_displayError("The $absoluteXPath/attribute::$attribute value isn't a node in this document.", __LINE__, FALSE);
        break;
      }
      // Get a reference to the attribute node 
      $textNode = &$this->nodes[$absoluteXPath]['attributes'][$attribute];
    }
    else {
    // we have been given an xpath with neither a text() or attribute axis at the end
    // find the first text() node and return that
      // Numpty check
      if ( !isSet($this->nodes[$absoluteXPath]) ) {
        // Try to evaluate the absoluteXPath (since it really isn't an absolutePath)
        $resultArr = $this->match($absoluteXPath);
        if ( sizeOf($resultArr) == 1 ) {
          $absoluteXPath = $resultArr[0];
        } 
        else {
          $this->_displayError("The $absoluteXPath does not evaluate to a single node in this document.", __LINE__, FALSE);
          return;
        }
      }
      if ( !is_array($this->nodes[$absoluteXPath]["text"]) ) {
        $this->_displayError("The $absoluteXPath is not a valid element node in the document.", __LINE__, FALSE);
        return;
      }
      $textNode = &$this->nodes[$absoluteXPath]["text"][0];
    }

    // Now modify the cdata, using the reference $textNode
    if ($replace) {
      $textNode = substr($textNode, 0, $offset) . $content . (($count) ? substr($textNode, $offset+$count) : '');
    }
    else {
      // if the offset is -1, append it, else insert it at the offset
      $textNode = ($offset == -1) ? "$textNode$content" : substr($textNode, 0, $offset).$content.substr($textNode, $offset);
      // This return is for substringData, ignored by rest of the functions
      return ($count) ? substr($textNode, $offset, $count) : substr($textNode, $offset);
    }
     
  }

  /////////////////////////////////////////////////
  // ########################################### //
  // Auxilliary functions for dealing with bracketed strings.
    
  /**
   * This method checks the right ammount and match of brackets
   *
   * @author    Sam Blume <bs_php@infeer.com>
   * @param     string $term String in which is checked.
   * @return    bool TRUE: OK / FALSE: KO  
   * @see       _evaluateStep()
   */
  function _bracketsCheck(&$term) {
    $leng = strlen($term);
    $brackets = 0;
    $bracketMisscount = $bracketMissmatsh = FALSE;
    $stack = array();
    for ( $i = 0; $i < $leng; $i++) {
      switch ($term[$i]) {
        case '(' : 
        case '[' : 
          $stack[$brackets] = $term[$i]; 
          $brackets++; 
          break;
        case ')': 
          $brackets--;
          if ($brackets<0) {
            $bracketMisscount = TRUE;
            break 2;
          }
          if ($stack[$brackets] != '(') {
            $bracketMissmatsh = TRUE;
            break 2;
          }
          break;
        case ']' : 
          $brackets--;
          if ($brackets<0) {
            $bracketMisscount = TRUE;
            break 2;
          }
          if ($stack[$brackets] != '[') {
            $bracketMissmatsh = TRUE;
            break 2;
          }
          break;
      }
    }
    // Check whether we had a valid number of brackets.
    if ($brackets != 0 ) $bracketMisscount = TRUE;
    if ($bracketMisscount || $bracketMissmatsh) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Looks for a string within another string.
   *
   * This method looks for a string within another string. Brackets in the
   * string the method is looking through will be respected, which means that
   * only if the string the method is looking for is located outside of
   * brackets, the search will be successful.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $term String in which the search shall take place.
   * @param     string $expression String that should be searched.
   * @return    int This method returns -1 if no string was found, otherwise
   *            the offset at which the string was found.
   * @see       _evaluateStep()
   */
  function _searchString($term, $expression) {
    $bracketCounter = 0;
    $leng = strlen($term);
    for ( $i = 0; $i < $leng; $i++) {
      $char = $term[$i];
      if ($char=='(' || $char=='[') {
        $bracketCounter++;
        continue;
      }
      elseif ($char==')' || $char==']') {
        $bracketCounter--;
        continue;
      }
      if ($bracketCounter == 0) {
        // Check whether we can find the expression at this index.
        if (substr($term, $i, strlen($expression)) == $expression) {
          // Return the current index.
          return $i;
        }
      }
    }
    // Check whether we had a valid number of brackets.
    if ($bracketCounter != 0) {
      // Display an error message.
      $this->_displayError('While parsing an XPath expression, in the predicate ' .
        str_replace($term, '<b>'.$term.'</b>', $this->xpath) .
        ', there was an invalid number of brackets.', __LINE__);
    }
    // Nothing was found.
    return (-1);
  }
  
  /////////////////////////////////////////////////
  // ########################################### //
  // Auxilliary utilities
  
  /**
   * Retrieves a substring before a delimiter.
   *
   * This method retrieves everything from a string before a given delimiter,
   * not including the delimiter.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $string String, from which the substring should be
   *            extracted.
   * @param     string $delimiter String containing the delimiter to use.
   * @return    string Substring from the original string before the
   *            delimiter.
   * @see       _afterstr()
   */
  function _prestr(&$string, $delimiter, $offset=0) {
    // Return the substring.
    //return substr($string, 0, strlen($string) - strlen(strstr($string, "$delimiter")));
    $offset = ($offset<0) ? 0 : $offset;
    $pos = strpos($string, $delimiter, $offset);
    if ($pos===FALSE) {
      return $string;
    } else {
      return substr($string, 0, $pos);
    }
  }
  
  /**
   * Retrieves a substring after a delimiter.
   *
   * This method retrieves everything from a string after a given delimiter,
   * not including the delimiter.
   *
   * @author    Michael P. Mehl <mpm@phpxml.org>
   * @param     string $string String, from which the substring should be
   *            extracted.
   * @param     string $delimiter String containing the delimiter to use.
   * @return    string Substring from the original string after the
   *            delimiter.
   * @see       _prestr()
   */
  function _afterstr(&$string, $delimiter, $offset=0) {
    $offset = ($offset<0) ? 0 : $offset;
    // Return the substring.
    return substr($string, strpos($string, $delimiter, $offset) + strlen($delimiter));
  }
  
  /**
   * !! terminate should not be allowed !! --fab
   *
   * Displays an error message.
   *
   * This method displays an error messages and stops the execution of the
   * script.
   *
   * @author     Michael P. Mehl <mpm@phpxml.org>
   * @param      $message string Error message to be displayed.
   * @param      $lineNumber int line number given by __LINE__
   * @param      $terminate bool (default TURE) End the execution of this script.
   */
  function _displayError($message, $lineNumber='-', $terminate=TRUE) {
    // Display the error message.
    $err = '<b>XPath error in '.basename(__FILE__).':'.$lineNumber.'</b> '.$message."<br \>\n";
    $this->_setLastError($message, $lineNumber);
    if (($this->_verboseLevel > 0) OR ($terminate)) echo $err;
    // End the execution of this script.
    if ($terminate) exit;
  }
  
  /**
  * creates a textual error message and sets it. 
  * 
  * example: 'XPath error in THIS_FILE_NAME:LINE. Message: YOUR_MESSAGE';
  * 
  * i don't think the message should include any markup because not everyone wants to debug 
  * into the browser window.
  * 
  * based on the deprecated _displayError() and replaces it.
  * 
  * @author fab
  * @param  string $message a textual error message default is ''
  * @param  int    $line    the line number where the error occured, use __LINE__
  * @return void
  * @see getLastError()
  */
  function _setLastError($message='', $line='-') {
    $this->_lastError = 'XPath error in ' . basename(__FILE__) . ':' . $line . '. Message: ' . $message;
  }
  
  /**
   * Determine if the function has any content
   *
   * Returns TRUE if this object has any xml content.  i.e. after a successfull
   * load_XXX() call we will have content, but before we shouldn't.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @return    TRUE if the object holds any content, FALSE otherwise.
   */
  function _objectHasContent() {
    return (count($this->nodes));
  }
  
  /////////////////////////////////////////////////
  // ########################################### //
  // Auxilliary debug utilities to help debug functions.

  /**
   * Called to begin the debug run of a function.
   *
   * This method starts a <DIV><PRE> tag so that the entry to this function
   * is clear to the debugging user.  Call _closeDebugFunction() at the
   * end of the function to create a clean box round the function call.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @author    Sam   Blum    <bs_php@infeer.com>
   * @param     string $FunctionName the name of the function we are beginning to debug
   * @return    array the output from the gettimeofday function.
   * @see       _closeDebugFunction()
   */
  function _beginDebugFunction($function_name) {
    $fileName = basename(__FILE__);
    static $color = array('green','blue','red','lime','fuchsia', 'aqua');
    static $colIndex = -1;
    $colIndex++;
    $pre = '<pre STYLE="border:solid thin '. $color[$colIndex % 6] . '; padding:5">';
    $out = '<div align="left"> ' . $pre . "<STRONG>{$fileName} : {$function_name}</STRONG><HR>";
    echo $out;
    return microtime();
  }
  
  /**
   * Called to end the debug run of a function.
   *
   * This method ends a <DIV><PRE> block and reports the time since $aStartTime
   * is clear to the debugging user.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @param     array $a_starttime the time that the function call was started.
   * @param     any $return_value the return value from the function call that 
   *            we are debugging
   */
  function _closeDebugFunction($a_starttime, $return_value = "") {
    echo "<hr>";
    if (isSet($return_value)) {
      if (is_array($return_value))
        echo "Return Value: ".print_r($return_value)."\n";
      else if (is_numeric($return_value)) 
        echo "Return Value: '$return_value'\n";
      else if (is_bool($return_value)) 
        echo "Return Value: ".($return_value ? "TRUE" : "FALSE")."\n";
      else 
        echo "Return Value: \"".htmlspecialchars($return_value)."\"\n";
    }
    $this->_profileFunction($a_starttime, "Function took");
    echo " \n</pre></div>";
  }
  
  /**
   * Call to return time since start of function for Profiling
   *
   * @param     array $a_starttime the time that the function call was started.
   * @param     string $alert_string the string to describe what has just finished happening
   */
  function _profileFunction($a_starttime, $alert_string) {
    // Print the time it took to call this function.
    $now   = explode(' ', microtime());
    $last  = explode(' ', $a_starttime);
    $delta = (round( (($now[1] - $last[1]) + ($now[0] - $last[0]))*1000 ));
    echo "\n{$alert_string} <strong>{$delta} ms</strong>";
  }
  ////////////////////////////////////////////////////////////////////////////////////////////////
  
}
?>
