<?php
/**
 * Php.XPath
 *
 * +======================================================================================================+
 * | A php class for searching an XML document using XPath, and making modifications using a DOM 
 * | style API. Does not require the DOM XML PHP library. 
 * |
 * +======================================================================================================+
 * | What Is XPath:
 * | --------------
 * | - "What SQL is for a rational database, XPath is for an XML document." -- Sam Blum
 * | - "The primary purpose of XPath is to address parts of an XML document. In support of this 
 * |    primary purpose, it also provides basic facilities for manipulting it." -- W3C
 * | 
 * | XPath in action and a very nice intro is under:
 * |    http://www.zvon.org/xxl/XPathTutorial/General/examples.html
 * | Specs Can be found under:
 * |    http://www.w3.org/TR/xpath     W3C XPath Recommendation 
 * |    http://www.w3.org/TR/xpath20   W3C XPath Recommendation 
 * |
 * | NOTE: Most of the XPath-spec has been realized, but not all. Usually this should not be
 * |       problem as the missing part is either rarely used or it's simpler to do with PHP itself.
 * +------------------------------------------------------------------------------------------------------+
 * | Requires PHP version  4.0.5 and up
 * +------------------------------------------------------------------------------------------------------+
 * | Main Active Authors:
 * | --------------------
 * | Nigel Swinson <nigelswinson@users.sourceforge.net>
 * |   Started around 2001-07 and creator of V1.N.x branches.
 * | Sam Blum <bs_php@infeer.com>
 * |   Started around 2001-09 1st major restruct (V2.0) and testbench initiator.   
 * |   2nd (V3.0) major rewrite in 2002-02
 * | Daniel Allen <bigredlinux@yahoo.com>
 * |   Started around 2001-10 working to make Php.XPath adhere to specs 
 * | Main Former Author: Michael P. Mehl <mpm@phpxml.org>
 * |   Inital creator of V 1.0. Stoped activities around 2001-03        
 * +------------------------------------------------------------------------------------------------------+
 * | Code Structure:
 * | --------------_
 * | In V3.0 the code has been split in 3 main objects. To keep usability easy all 3 
 * | objects are in this file (but may be split in 3 file in future).
 * |   +-------------+ 
 * |   |  XPathBase  | XPathBase holds general- and debugging-functions. 
 * |   +------+------+
 * |          v      
 * |   +-------------+ XPathEngine is the implementation of the W3C XPath spec. It contains the 
 * |   | XPathEngine | XML-import (parser), -export  and can handle xPathQueries. It's a fully 
 * |   +------+------+ functional class but has no functions to modify the XML-document (see following).
 * |          v      
 * |   +-------------+ 
 * |   |    XPath    | XPath extends the functionality with actions to modify the XML-document.
 * |   +-------------+ We tryed to implement a DOM - like interface.
 * +------------------------------------------------------------------------------------------------------+
 * | Usage:
 * | ------
 * | Scroll to the end of this file and you will find a little sample code to get you started
 * +------------------------------------------------------------------------------------------------------+
 * | Glossary:
 * | ---------
 * | To understand how to use the functions and to pass the right parameters, read following:
 * |     
 * | Document: (full node tree, XML-tree)
 * |     After a XML-source has been imported and parsed, it's stored as a tree of nodes sometimes 
 * |     refered as 'document'.
 * |     
 * | AbsoluteXPath: (xPath, xPathSet)
 * |     A absolute XPath is a string. It 'points' to *one* node in the XML-document. We use the
 * |     term 'absolute' to emphasise that it is not an xPath-query (see xPathQuery). A valid xPath 
 * |     has the form like '/AAA[1]/BBB[2]/CCC[1]'. Usually functions that require a node (see Node) 
 * |     will also accept an abs. XPath.
 * |     
 * | Node: (node, nodeSet, node-tree)
 * |     Some funtions require or return a node (or a whole node-tree). Nodes are only used with the 
 * |     XPath-interface and have an internal structure. Every node in a XML document has a unique 
 * |     corresponding abs. xPath. That's why public functions that accept a node, will usually also 
 * |     accept a abs. xPath (a string) 'pointing' to an existing node (see absolutXPath).
 * |     
 * | XPathQuery: (xquery, query)
 * |     A xPath-query is a string that is matched against the XML-document. The result of the match 
 * |     is a xPathSet (vector of xPath's). It's always possable to pass a single absolutXPath 
 * |     instead of a xPath-query. A valid xPathQuery could look like this:
 * |     '//XXX/*[contains(., "foo")]/..' (See the link in 'What Is XPath' to learn more).
 * |     
 * |     
 * +------------------------------------------------------------------------------------------------------+
 * | Internals:
 * | ----------
 * | - The Node Tree
 * |   -------------
 * | A central role of the package is how the XML-data is stored. The whole data is in a node-tree.
 * | A node can be seen as the equvalent to a tag in the XML soure with some extra info.
 * | For instance the following XML 
 * |                        <AAA foo="x">***<BBB/><CCC/>**<BBB/>*</AAA>
 * | Would produce folowing node-tree:
 * |                              'super-root'      <-- $nodeRoot (Very handy)  
 * |                                    |                                           
 * |             'depth' 0            AAA[1]        <-- top node. The 'textParts' of this node would be
 * |                                /   |   \                     'textParts' => array('***','','**','*')
 * |             'depth' 1     BBB[1] CCC[1] BBB[2]               (NOTE: Is always size of child nodes+1)
 * | - The Node
 * |   --------
 * | The node itself is an structure desiged mainly to be used in conection with the interface of PHP.XPath.
 * | That means it's possible for functions to return a sub-node-tree that can be used as input of an other 
 * | PHP.XPath function.
 * | 
 * | The main structure of a node is:
 * |   $node = array(
 * |     'name'        => '',      # The tag name. E.g. In <FOO bar="aaa"/> it would be 'FOO'
 * |     'attributes'  => array(), # The attributes of the tag E.g. In <FOO bar="aaa"/> it would be array('bar'=>'aaa')
 * |     'textParts'   => array(), # Array of text parts surrounding the children E.g. <FOO>aa<A>bb<B/>cc</A>dd</FOO> -> array('aa','bb','cc','dd')
 * |     'childNodes'  => array(), # Array of refences (pointers) to child nodes.
 * |     
 * | For optimisation reasions some additional data is stored in the node too:
 * |     'parentNode'  => NULL     # Reference (pointer) to the parent node (or NULL if it's 'super root')
 * |     'depth'       => 0,       # The tag depth (or tree level) starting with the root tag at 0.
 * |     'pos'         => 0,       # Is the zero-based position this node has in the parent's 'childNodes'-list.
 * |     'contextPos'  => 1,       # Is the one-based position this node has by counting the siblings tags (tags with same name)
 * |     'xpath'       => ''       # Is the abs. XPath to this node.
 * | 
 * | - The NodeIndex
 * |   -------------
 * | Every node in the tree has an absolute XPath. E.g '/AAA[1]/BBB[2]' the $nodeIndex is a hash array
 * | to all the nodes in the node-tree. The key used is the absolute XPath (a string).
 * |    
 * +------------------------------------------------------------------------------------------------------+
 * | License:
 * | --------
 * | The contents of this file are subject to the Mozilla Public License Version 1.1 (the "License"); 
 * | you may Version 1.1 (the "License"); you may not use this file except in compliance with the
 * | License. You may obtain a copy of the License at http://www.mozilla.org/MPL/ .
 * |
 * | Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY 
 * | OF ANY KIND, either express or implied. See the License for the specific language governing 
 * | rights and limitations under the License.
 * |
 * | The Original Code is <phpXML/>.
 * |
 * | The Initial Developer of the Original Code is Michael P. Mehl. Portions created by Michael 
 * | P. Mehl are Copyright (C) 2001 Michael P. Mehl. All Rights Reserved.
 * | 
 * +------------------------------------------------------------------------------------------------------+
 * +------------------------------------------------------------------------------------------------------+
 *
 * @author  S.Blum / N.Swinson / D.Allen / (P.Mehl)
 * @link    http://sourceforge.net/projects/phpxpath/
 * @version 3.0 beta
 */

/************************************************************************************************
* ===============================================================================================
*                               X P a t h B a s e  -  Class                                      
* ===============================================================================================
************************************************************************************************/
class XPathBase {
  var $_lastError;
  
  // As debugging of the xml parse is spread across several functions, we need to make this a member.
  var $bDebugXmlParse = FALSE;

  /**
   * Constructor
   */
  function XPathBase() {
    # $this->bDebugXmlParse = TRUE;
    $this->properties['verboseLevel'] = 1;  // 0=silent, 1 and above produce verbose output (an echo to screen). 
  }
  
  /**
   * Resets the object so it's able to take a new xml sting/file
   *
   * Constructing objects is slow.  If you can, reuse ones that you have used already
   * by using this reset() function.
   */
  function reset() {
    $this->_lastError   = '';
  }
  
  //-----------------------------------------------------------------------------------------
  // XPathBase                    ------  Helpers  ------                                    
  //-----------------------------------------------------------------------------------------
  
  /**
   * This method checks the right ammount and match of brackets
   *
   * @param     $term (string) String in which is checked.
   * @return          (bool)   TRUE: OK / FALSE: KO  
   * @see       _evaluateStep()
   */
  function _bracketsCheck($term) {
    $leng = strlen($term);
    $brackets = 0;
    $bracketMisscount = $bracketMissmatsh = FALSE;
    $stack = array();
    for ($i=0; $i<$leng; $i++) {
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
    if ($brackets != 0) $bracketMisscount = TRUE;
    if ($bracketMisscount || $bracketMissmatsh) {
      return FALSE;
    }
    return TRUE;
  }
  
  /**
   * Looks for a string within another string -- BUT the search-string must be located *outside* of any brackets.
   *
   * This method looks for a string within another string. Brackets in the
   * string the method is looking through will be respected, which means that
   * only if the string the method is looking for is located outside of
   * brackets, the search will be successful.
   *
   * @param     $term       (string) String in which the search shall take place.
   * @param     $expression (string) String that should be searched.
   * @return                (int)    This method returns -1 if no string was found, 
   *                                 otherwise the offset at which the string was found.
   * @see       _evaluateStep()
   */
  function _searchString($term, $expression) {
    $bracketCounter = 0; // Record where we are in the brackets. 
    $leng = strlen($term);
    $exprLeng = strlen($expression);
    for ($i=0; $i<$leng; $i++) {
      $char = $term[$i];
      if ($char=='(' || $char=='[') {
        $bracketCounter++;
        continue;
      }
      elseif ($char==')' || $char==']') {
        $bracketCounter--;
      }
      if ($bracketCounter == 0) {
        // Check whether we can find the expression at this index.
        if (substr($term, $i, $exprLeng) == $expression) return $i;
      }
    }
    // Nothing was found.
    return (-1);
  }
  
  /**
   * Split a string by a searator-string -- BUT the separator-string must be located *outside* of any brackets.
   * 
   * Returns an array of strings, each of which is a substring of string formed 
   * by splitting it on boundaries formed by the string separator. 
   *
   * @param     $separator  (string) String that should be searched.
   * @param     $term       (string) String in which the search shall take place.
   * @return                (array)  see above
   */
  function _bracketExplode($separator, $term) {
    // Note that it doesn't make sense for $separator to itself contain (,),[ or ],
    // but as this is a private function we should be ok.
    $resultArr   = array();
    $bracketCounter = 0;	// Record where we are in the brackets. 
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
      
      // Get prefix string part before the first bracket.
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
   * Retrieves a substring before a delimiter.
   *
   * This method retrieves everything from a string before a given delimiter,
   * not including the delimiter.
   *
   * @param     $string     (string) String, from which the substring should be extracted.
   * @param     $delimiter  (string) String containing the delimiter to use.
   * @return                (string) Substring from the original string before the delimiter.
   * @see       _afterstr()
   */
  function _prestr(&$string, $delimiter, $offset=0) {
    // Return the substring.
    $offset = ($offset<0) ? 0 : $offset;
    $pos = strpos($string, $delimiter, $offset);
    if ($pos===FALSE) return $string; else return substr($string, 0, $pos);
  }
  
  /**
   * Retrieves a substring after a delimiter.
   *
   * This method retrieves everything from a string after a given delimiter,
   * not including the delimiter.
   *
   * @param     $string     (string) String, from which the substring should be extracted.
   * @param     $delimiter  (string) String containing the delimiter to use.
   * @return                (string) Substring from the original string after the delimiter.
   * @see       _prestr()
   */
  function _afterstr($string, $delimiter, $offset=0) {
    $offset = ($offset<0) ? 0 : $offset;
    // Return the substring.
    return substr($string, strpos($string, $delimiter, $offset) + strlen($delimiter));
  }
  
  //-----------------------------------------------------------------------------------------
  // XPathBase                ------  Debug Stuff  ------                                    
  //-----------------------------------------------------------------------------------------
  
  /**
   * Turn verbose (error) output ON or OFF
   *
   * Pass a bool. TRUE to turn on, FALSE to turn off.
   * Pass a int >0 to reach higher levels of verbosity (for future use).
   *
   * @param $levelOfVerbosity  (mixed) default is 0 = off
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
    if ($level >= 0) $this->properties['verboseLevel'] = $levelOfVerbosity;
  }
   
  /**
   * Returns the last occured error message.
   *
   * @access public
   * @return string (may be empty if there was no error at all)
   * @see    _setLastError(), _lastError
   */
  function getLastError() {
    return $this->_lastError;
  }
  
  /**
   * Creates a textual error message and sets it. 
   * 
   * example: 'XPath error in THIS_FILE_NAME:LINE. Message: YOUR_MESSAGE';
   * 
   * I don't think the message should include any markup because not everyone wants to debug 
   * into the browser window.
   * 
   * You should call _displayError() rather than _setLastError() if you would like the message,
   * dependant on their verbose settings, echoed to the screen.
   * 
   * @param $message (string) a textual error message default is ''
   * @param $line    (int)    the line number where the error occured, use __LINE__
   * @see getLastError()
   */
  function _setLastError($message='', $line='-', $file='-') {
    $this->_lastError = 'XPath error in ' . basename($file) . ':' . $line . '. Message: ' . $message;
  }
  
  /**
   * Displays an error message.
   *
   * This method displays an error messages depending on the users verbose settings 
   * and sets the last error message.  
   *
   * If also possibly stops the execution of the script.
   * ### Terminate should not be allowed --fab.  Should it??  N.S.
   *
   * @param $message    (string)  Error message to be displayed.
   * @param $lineNumber (int)     line number given by __LINE__
   * @param $terminate  (bool)    (default TURE) End the execution of this script.
   */
  function _displayError($message, $lineNumber='-', $file='-', $terminate=TRUE) {
    // Display the error message.
    $err = '<b>XPath error in '.basename($file).':'.$lineNumber.'</b> '.$message."<br \>\n";
    $this->_setLastError($message, $lineNumber, $file);
    if (($this->properties['verboseLevel'] > 0) OR ($terminate)) echo $err;
    // End the execution of this script.
    if ($terminate) exit;
  }

  /**
   * Displays a diagnostic message
   *
   * This method displays an error messages
   *
   * @param $message    (string)  Error message to be displayed.
   * @param $lineNumber (int)     line number given by __LINE__
   */
  function _displayMessage($message, $lineNumber='-', $file='-') {
    // Display the error message.
    $err = '<b>XPath message from '.basename($file).':'.$lineNumber.'</b> '.$message."<br \>\n";
    if ($this->properties['verboseLevel'] > 0) echo $err;
  }
  
  /**
   * Called to begin the debug run of a function.
   *
   * This method starts a <DIV><PRE> tag so that the entry to this function
   * is clear to the debugging user.  Call _closeDebugFunction() at the
   * end of the function to create a clean box round the function call.
   *
   * @author    Nigel Swinson <nigelswinson@users.sourceforge.net>
   * @author    Sam   Blum    <bs_php@infeer.com>
   * @param     $functionName (string) the name of the function we are beginning to debug
   * @return                  (array)  the output from the microtime() function.
   * @see       _closeDebugFunction()
   */
  function _beginDebugFunction($functionName) {
    $fileName = basename(__FILE__);
    static $color = array('green','blue','red','lime','fuchsia', 'aqua');
    static $colIndex = -1;
    $colIndex++;
    $pre = '<pre STYLE="border:solid thin '. $color[$colIndex % 6] . '; padding:5">';
    $out = '<div align="left"> ' . $pre . "<STRONG>{$fileName} : {$functionName}</STRONG><HR>";
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
   * @param     $aStartTime   (array) the time that the function call was started.
   * @param     $return_value (mixed) the return value from the function call that 
   *                                  we are debugging
   */
  function _closeDebugFunction($aStartTime, $returnValue = "") {
    echo "<hr>";
    if (isSet($returnValue)) {
      if (is_array($returnValue))
        echo "Return Value: ".print_r($returnValue)."\n";
      else if (is_numeric($returnValue)) 
        echo "Return Value: '$return_value'\n";
      else if (is_bool($returnValue)) 
        echo "Return Value: ".($returnValue ? "TRUE" : "FALSE")."\n";
      else 
        echo "Return Value: \"".htmlspecialchars($returnValue)."\"\n";
    }
    $this->_profileFunction($aStartTime, "Function took");
    echo " \n</pre></div>";
  }
  
  /**
   * Call to return time since start of function for Profiling
   *
   * @param     $aStartTime  (array)  the time that the function call was started.
   * @param     $alertString (string) the string to describe what has just finished happening
   */
  function _profileFunction($aStartTime, $alertString) {
    // Print the time it took to call this function.
    $now   = explode(' ', microtime());
    $last  = explode(' ', $aStartTime);
    $delta = (round( (($now[1] - $last[1]) + ($now[0] - $last[0]))*1000 ));
    echo "\n{$alertString} <strong>{$delta} ms</strong>";
  }
  
  /**
   * This is a debug helper function. It dumps the node-tree as HTML
   *
   * *QUICK AND DIRTY*. Needs some polishing.
   *
   * @param $node   (array)   A node 
   * @param $indent (string) (optional, default=''). For internal recursive calls.
   */
  function _treeDump($node, $indent = '') {
    $out = '';
    
    // Get rid of recursion
    $parentName = empty($node['parentNode']) ? "SUPER ROOT" :  $node['parentNode']['name'];
    unset($node['parentNode']);
    $node['parentNode'] = $parentName ;
    
    $out .= "NODE[{$node['name']}]\n";
    
    foreach($node as $key => $val) {
      if ($key === 'childNodes') continue;
      if (is_Array($val)) {
        $out .= $indent . "  [{$key}]\n" . arrayToStr($val, $indent . '    ');
      } else {
        $out .= $indent . "  [{$key}] => '{$val}' \n";
      }
    }
    
    if (!empty($node['childNodes'])) {
      $out .= $indent . "  ['childNodes'] (Size = ".sizeOf($node['childNodes']).")\n";
      foreach($node['childNodes'] as $key => $childNode) {
        $out .= $indent . "     [$key] => " . $this->_treeDump($childNode, $indent . '       ') . "\n";
      }
    }
    
    if (empty($indent)) {
      return "<pre>" . htmlspecialchars($out) . "</pre>";
    }
    return $out;
  }
} // END OF CLASS XPathBase


/************************************************************************************************
* ===============================================================================================
*                             X P a t h E n g i n e  -  Class                                    
* ===============================================================================================
************************************************************************************************/

class XPathEngine extends XPathBase {
  
  // List of supported XPath axes.
  // What a stupid idea from W3C to take axes name containing a '-' (dash)
  // NOTE: We replace the '-' with '_' to avoid the conflict with the minus operator.
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
  // NOTE: We replace the '-' with '_' to avoid the conflict with the minus operator.
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
    
  // List of supported XPath operators.
  //
  // This array contains a list of all valid operators that can be evaluated
  // in a predicate of an XPath expression. The list is ordered by the
  // precedence of the operators (lowest precedence first).
  var $operators = array( ' or ', ' and ', '=', '!=', '<=', '<', '>=', '>',
    '+', '-', '*', ' div ', ' mod ' );
  
  // The index and tree that is created during the analysis of an XML source.
  var $nodeIndex = array();
  var $nodeRoot  = array();
  var $emptyNode = array(
                     'name'        => '',       // The tag name. E.g. In <FOO bar="aaa"/> it would be 'FOO'
                     'attributes'  => array(),  // The attributes of the tag E.g. In <FOO bar="aaa"/> it would be array('bar'=>'aaa')
                     'childNodes'  => array(),  // Array of pointers to child nodes.
                     'textParts'   => array(),  // Array of text parts between the cilderen E.g. <FOO>aa<A>bb<B/>cc</A>dd</FOO> -> array('aa','bb','cc','dd')
                     'parentNode'   => NULL,     // Pointer to parent node or NULL if this node is the 'super root'
                     //-- *!* Following vars are set by the indexer and is for optimisation only *!*
                     'depth'       => 0,  // The tag depth (or tree level) starting with the root tag at 0.
                     'pos'         => 0,  // Is the zero-based position this node has in the parents 'childNodes'-list.
                     'contextPos'  => 1,  // Is the one-based position this node has by counting the siblings tags (tags with same name)
                     'xpath'       => ''  // Is the abs. XPath to this node.
                   );

  
  // These variable used during the parse XML source
  var $nodeStack       = array(); // The elements that we have still to close.
  var $parseStackIndex = 0;       // The current element of the nodeStack[] that we are adding to while 
                                  // parsing an XML source.  Corresponds to the depth of the xml node.
                                  // in our input data.
  var $parseOptions    = array(); // Used to set the PHP's XML parser options (see xml_parser_set_option)
  var $parsedTextLocation   = ''; // A reference to where we have to put char data collected during XML parsing
  var $parsInCData     = 0 ;      // Is >0 when we are inside a CDATA section.  
  var $parseSkipWhiteCache = 0;   // A cache of the skip whitespace parse option to speed up the parse.

  // This is the array of error strings, to keep consistency.
  var $errorStrings = array(
    'AbsoluteXPathRequired' => "The supplied xPath '%s' does not *uniquely* describe a node in the xml document.",
    'NoNodeMatch'           => "The supplied xPath-query '%s' does not match *any* node in the xml document."
    );
    
  /**
   * Constructor
   *
   * Optionally you may call this constructor with the XML-filename to parse and the 
   * XML option vector. Each of the entries in the option vector will be passed to
   * xml_parser_set_option().
   *
   * A option vector sample: 
   *   $xmlOpt = array(XML_OPTION_CASE_FOLDING => FALSE, 
   *                   XML_OPTION_SKIP_WHITE => TRUE);
   *
   * @param  $userXmlOptions (array) (optional) Vector of (<optionID>=><value>, 
   *                                 <optionID>=><value>, ...).  See PHP's
   *                                 xml_parser_set_option() docu for a list of possible
   *                                 options.
   * @see   importFromFile(), importFromString(), setXmlOption()
   */
  function XPathEngine($userXmlOptions=array()) {
    parent::XPathBase();
    // Default to not folding case
    $this->parseOptions[XML_OPTION_CASE_FOLDING] = FALSE;
    // And not skipping whitespace
    $this->parseOptions[XML_OPTION_SKIP_WHITE] = FALSE;
    
    // Now merge in the overrides.
    // Don't use PHP's array_merge!
    if (is_array($userXmlOptions)) {
      foreach($userXmlOptions as $key => $val) $this->parseOptions[$key] = $val;
    }
  }
  
  /**
   * Resets the object so it's able to take a new xml sting/file
   *
   * Constructing objects is slow.  If you can, reuse ones that you have used already
   * by using this reset() function.
   */
  function reset() {
    parent::reset();
    $this->properties['xmlFile']  = ''; 
    $this->parseStackIndex = 0;
    $this->parsedTextLocation = '';
    $this->parsInCData   = 0;
    $this->nodeIndex     = array();
    $this->nodeRoot      = array();
    $this->nodeStack     = array();
  }
  
  
  //-----------------------------------------------------------------------------------------
  // XPathEngine              ------  Get / Set Stuff  ------                                
  //-----------------------------------------------------------------------------------------
  
  /**
   * Returns the property/ies you want.
   * 
   * if $param is not given, all properties will be returned in a hash.
   *
   * @param  $param (string) the property you want the value of, or NULL for all the properties
   * @return        (mixed)  string OR hash of all params, or NULL on an unknown parameter.
   */
  function getProperties($param=NULL) {
    $this->properties['hasContent']      = !empty($this->nodeRoot);
    $this->properties['caseFolding']     = $this->parseOptions[XML_OPTION_CASE_FOLDING];
    $this->properties['skipWhiteSpaces'] = $this->parseOptions[XML_OPTION_SKIP_WHITE];
    
    if (empty($param)) return $this->properties;
    
    if (isSet($this->properties[$param])) {
      return $this->properties[$param];
    } else {
      return NULL;
    }
  }
  
  /**
   * xml_parser_set_option -- set options in an XML parser.
   *
   * @param $optionID (int) The option ID (e.g. XML_OPTION_SKIP_WHITE)
   * @param $value    (int) The option value.
   * @see XML parser functions in PHP doc
   */
  function setXmlOption($optionID, $value) {
    if (!is_numeric($optionID)) return;
     $this->parseOptions[$optionID] = $value;
  }
   
  /**
   * Controls whether case-folding is enabled for this XML parser.
   *
   * When it comes to XML, case-folding simply means uppercasing all tag- 
   * and attribute-names (NOT the content) if set to TRUE.  Note if you
   * have this option set, then your XPath queries will also be case folded 
   * for you.
   *
   * @param $onOff (bool) (default TRUE) 
   * @see XML parser functions in PHP doc
   */
  function setCaseFolding($onOff=TRUE) {
    $this->parseOptions[XML_OPTION_CASE_FOLDING] = $onOff;
  }
  
  /**
   * Controls whether skip-white-spaces is enabled for this XML parser.
   *
   * When it comes to XML, skip-white-spaces will trim the tag content.
   * This will speed up performance, but will make your data less human 
   * readable when you come to write it out.
   *
   * @param $onOff (bool) (default TRUE) 
   * @see XML parser functions in PHP doc
   */
  function setSkipWhiteSpaces($onOff=TRUE) {
    $this->parseOptions[XML_OPTION_SKIP_WHITE] = $onOff;
  }
   
  /**
   * Get the node defined by the $absoluteXPath.
   *
   * @param   $absoluteXPath (string) (optional, default is 'super-root') xpath to the node.
   * @return                 (array)  The node, or FALSE if the node wasn't found.
   */
  function &getNode($absoluteXPath='') {
    if ($absoluteXPath==='/') $absoluteXPath = '';
    if (!isSet($this->nodeIndex[$absoluteXPath])) return FALSE;
    return $this->nodeIndex[$absoluteXPath];
  }
  
  /**
   * Get a the content of a node text part or node attribute.
   * 
   * If the absolute Xpath references an attribute (Xpath ends with attribute::), 
   * then the text value of that node-attribute is returned.
   * Otherwise the Xpath is referencing a text part of the node. This can be either a 
   * direct reference to a text part (Xpath ends with text()[<nr>]) or indirect reference 
   * (a simple abs. Xpath to a node).
   * 1) Direct Reference (xpath ends with text()[<part-number>]):
   *   If the 'part-number' is omitted, the first text-part is assumed; starting by 1.
   *   Negative numbers are allowed, where -1 is the last text-part a.s.o.
   * 2) Indirect Reference (a simple abs. Xpath to a node):
   *   Default is to return the *whole text*; that is the concated text-parts of the matching
   *   node. (NOTE that only in this case you'll only get a copy and changes to the returned  
   *   value wounld have no effect). Optionally you may pass a parameter 
   *   $textPartNr to define the text-part you want;  starting by 1.
   *   Negative numbers are allowed, where -1 is the last text-part a.s.o.
   *
   * NOTE I : The returned value can be fetched by reference
   *          E.g. $text =& wholeText(). If you wish to modify the text.
   * NOTE II: text-part numbers out of range will return FALSE
   * SIDENOTE:The function name is a suggestion from W3C in the XPath specification level 3.
   *
   * @param   $absoluteXPath  (string)  xpath to the node (See above).
   * @param   $textPartNr     (int)     If referring to a node, specifies which text part 
   *                                    to query.
   * @return                  (&string) A *reference* to the text if the node that the other 
   *                                    parameters describe or FALSE if the node is not found.
   */
  function &wholeText($absoluteXPath, $textPartNr=NULL) {
    $status = FALSE;
    $text   = NULL;
    
    do { // try-block
      if ( preg_match(";(.*)/(attribute::|@)([^/]*)$;U", $absoluteXPath, $matches) ) {
        $absoluteXPath = $matches[1];
        $attribute = $matches[3];
        if ( !isSet($this->nodeIndex[$absoluteXPath]['attributes'][$attribute]) ) {
          $this->_displayError("The $absoluteXPath/attribute::$attribute value isn't a node in this document.", __LINE__, __FILE__, FALSE);
          break; // try-block
        }
        $text =& $this->nodeIndex[$absoluteXPath]['attributes'][$attribute];
        $status = TRUE;
        break; // try-block
      }
      
      if ( !isSet($this->nodeIndex[$absoluteXPath]) ) {
        $this->_displayError("The $absoluteXPath value isn't a node in this document.", __LINE__, __FILE__, FALSE);
        break; // try-block
      }
      
      // Get the amount of the text parts in the node.
     $textPartSize = sizeOf($this->nodeIndex[$absoluteXPath]['textParts']);
      
      // Xpath contains a 'text()'-function, thus goes right to a text node. If so interpete the Xpath.
      if ( preg_match(":(.*)/text\(\)(\[(.*)\])?$:U", $absoluteXPath, $matches) ) {
        $absoluteXPath = $matches[1];
        // default to the first text node if a text node was not specified
        $textPartNr = isSet($matches[2]) ? substr($matches[2],1,-1) : 1;
        // Support negative indexes like -1 === last a.s.o.
        if ($textPartNr < 0) $textPartNr = $textPartSize + $textPartNr +1;
        if (($textPartNr <= 0) OR ($textPartNr > $textPartSize)) {
          $this->_displayError("The $absoluteXPath/text()[$textPartNr] value isn't a node in this document.", __LINE__, __FILE__, FALSE);
          break; // try-block
        }
        $text =& $this->nodeIndex[$absoluteXPath]['textParts'][$textPartNr - 1];
        $status = TRUE;
        break; // try-block
      }
      
      // At this point we have been given an xpath with neither a 'text()' nor 'attribute::' axis at the end
      // So we assume a get to text is wanted and use the optioanl fallback parameters $textPartNr
      
      // If $textPartNr == NULL we return a *copy* of the whole concated text-parts
      if (is_null($textPartNr)) {
        unset($text);
        $text = implode('', $this->nodeIndex[$absoluteXPath]['textParts']);
        $status = TRUE;
        break; // try-block
      }
      
      // Support negative indexes like -1 === last a.s.o.
      if ($textPartNr < 0) $textPartNr = $textPartSize + $textPartNr +1;
      if (($textPartNr <= 0) OR ($textPartNr > $textPartSize)) {
        $this->_displayError("The $absoluteXPath has no text part at pos [$textPartNr] (Note: text parts start with 1).", __LINE__, __FILE__, FALSE);
        break; // try-block
      }
      $text =& $this->nodeIndex[$absoluteXPath]['textParts'][$textPartNr -1];
      $status = TRUE;
    } while (FALSE); // END try-block
    
    if (!$status) return FALSE;
    return $text;
  }
  
  //-----------------------------------------------------------------------------------------
  // XPathEngine           ------ Export the XML Document ------                             
  //-----------------------------------------------------------------------------------------
   
  /**
   * Returns the containing XML as marked up HTML with specified nodes hi-lighted
   *
   * @param $absoluteXPath    (string) The address of the node you would like to export.
   *                                   If empty the whole document will be exported.
   * @param $hilighXpathList  (array)  A list of nodes that you would like to highlight
   * @return                  (mixed)  The Xml document marked up as HTML so that it can
   *                                   be viewed in a browser, including any XML headers.
   *                                   FALSE on error.
   * @see _export()    
   */
  function exportAsHtml($absoluteXPath='', $hilightXpathList=array()) {
    $htmlString = $this->_export($absoluteXPath, $xmlHeader=NULL, $hilightXpathList);
    if (!$htmlString) return FALSE;
    return "<pre>\n" . $htmlString . "\n</pre>"; 
  }
  
  /**
   * Given a context this function returns the containing XML
   *
   * @param $absoluteXPath  (string) The address of the node you would like to export.
   *                                 If empty the whole document will be exported.
   * @param $xmlHeader      (array)  The string that you would like to appear before
   *                                 the XML content.  ie before the <root></root>.  If you
   *                                 do not specify this argument, the xmlHeader that was 
   *                                 found in the parsed xml file will be used instead.
   * @return                (mixed)  The Xml fragment/document, suitable for writing
   *                                 out to an .xml file or as part of a larger xml file, or
   *                                 FALSE on error.
   * @see _export()    
   */
  function exportAsXml($absoluteXPath='', $xmlHeader=NULL) {
    $this->hilightXpathList = NULL;
    return $this->_export($absoluteXPath, $xmlHeader); 
  }
    
  /**
   * Generates a XML string with the content of the current document and writes it to a file.
   *
   * Per default includes a <?xml ...> tag at the start of the data too. 
   *
   * @param     $fileName       (string) 
   * @param     $absoluteXPath  (string) The path to the parent node you want(see text above)
   * @param     $xmlHeader      (string) default is '< ? xml version="1.0" ? >'
   * @return                    (string) The returned string contains well-formed XML data 
   *                                     or FALSE on error.
   * @see       exportAsXml(), exportAsHtml()
   */
  function exportToFile($fileName, $absoluteXPath='', $xmlHeader='<?xml version="1.0"?>') {   
    $status = FALSE;
    do { // try-block
      if (!($hFile = fopen($fileName, "wb"))) {   // Did we open the file ok?
        $errStr = "Failed to open the $fileName xml file.";
        break; // try-block
      }
      
      if (!flock($hFile, LOCK_EX)) {  // Lock the file
        $errStr = "Couldn't get an exclusive lock on the $fileName file.";
        break; // try-block
      }
      
      if (!($xmlOut = $this->_export($absoluteXPath, $xmlHeader))) {
        $errStr = "Export failed";
        break; // try-block
      }
      
      if (!fwrite($hFile, $xmlOut)) {
        $errStr = "Write error when writing back the $fileName file.";
        break; // try-block
      }
      
      // Flush and unlock the file
      @fflush($hFile);
      $status = TRUE;
    } while(FALSE);
    
    @flock($hFile, LOCK_UN);
    @fclose($hFile);
    
    if (!$status)  $this->_displayError($errStr, __LINE__, __FILE__, FALSE);
    return $status;
  }

  /**
   * Generates a XML string with the content of the current document.
   *
   * This is the start for extracting the XML-data from the node-tree. We do some preperations
   * and then call _InternalExport() to fetch the main XML-data. You optionally may pass 
   * xpath to any node that will then be used as top node, to extract XML-parts of the 
   * document. Default is '', meaning to extract the whole document.
   *
   * You also may pass a 'xmlHeader' (usually something like <?xml version="1.0"? > that will
   * overwrite any other 'xmlHeader', if there was one in the original source.
   * Finaly, when exproting to HTML, you may pass a vector xPaths you want to hi-light.
   * The hi-lighted tags and attributes will receive a nice color. 
   * 
   * NOTE I : The output can have 2 formats:
   *       a) If "skip white spaces" is/was set. (Not Recommended - slower)
   *          The output is formatted by adding indenting and carriage returns.
   *       b) If "skip white spaces" is/was *NOT* set.
   *          'as is'. No formatting is done. The output should the same as the 
   *          the original parsed XML source. 
   *
   * @param  $absoluteXPath (string) (optional, default is root) The node we choose as top-node
   * @param  $xmlHeader     (string) (optional) content before <root/> (see text above)
   * @param  $hilightXpath  (array)  (optional) a vector of xPaths to nodes we wat to 
   *                                 hi-light (see text above)
   * @return                (mixed)  The xml string, or FALSE on error.
   */
  function _export($absoluteXPath='', $xmlHeader=NULL, $hilightXpathList='') {
    // Check whether a root node is given.
    if (empty($absoluteXpath)) $absoluteXpath = '';
    if ($absoluteXpath == '/') $absoluteXpath = '';
    if (!isSet($this->nodeIndex[$absoluteXpath])) {
      // If the $absoluteXpath was '' and it didn't exist, then the document is empty
      // and we can safely return ''.
      if ($absoluteXpath == '') return '';
      $this->_displayError("The given xpath '{$absoluteXpath}' isn't a node in this document.", __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    
    $this->hilightXpathList = $hilightXpathList;
    $this->indentStep = '  ';
    $hilightIsActive = is_array($hilightXpathList);
    if ($hilightIsActive) {
      $this->indentStep = '&nbsp;&nbsp;&nbsp;&nbsp;';
    }    
    
    // Cache this now
    $this->parseSkipWhiteCache = isSet($this->parseOptions[XML_OPTION_SKIP_WHITE]) ? $this->parseOptions[XML_OPTION_SKIP_WHITE] : FALSE;

    ///////////////////////////////////////
    // Get the starting node and begin with the header

    // Get the start node.  The super root is a special case.
    $startNode = NULL;
    if (empty($absoluteXPath)) {
      $superRoot = $this->nodeIndex[''];
      // If they didn't specify an xml header, use the one in the object
      if (is_null($xmlHeader)) {
        $xmlHeader = $this->parseSkipWhiteCache ? trim($superRoot['textParts'][0]) : $superRoot['textParts'][0];
      }
      if (isSet($superRoot['childNodes'][0])) $startNode = $superRoot['childNodes'][0];
    } else {
      $startNode = $this->nodeIndex[$absoluteXPath];
    }

    if (!empty($xmlHeader)) { 
      $xmlOut = $this->parseSkipWhiteCache ? $xmlHeader."\n" : $xmlHeader;
    } else {
      $xmlOut = '';
    }

    ///////////////////////////////////////
    // Output the document.

    if (($xmlOut .= $this->_InternalExport($startNode)) === FALSE) {
      return FALSE;
    }
    
    ///////////////////////////////////////

    // Convert our markers to hi-lights.
    if ($hilightIsActive) {
      $from = array('<', '>', chr(2), chr(3));
      $to = array('&lt;', '&gt;', '<font color="#FF0000"><b>', '</b></font>');
      $xmlOut = str_replace($from, $to, $xmlOut);
    }
    return $xmlOut; 
  }  

  /**
   * Export the xml document starting at the named node.
   *
   * @param $node (node)   The node we have to start exporting from
   * @return      (string) The string representation of the node.
   */
  function _InternalExport($node) {
    $bDebugThisFunction = FALSE;

    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("_InternalExport");
      echo "Exporting node: ".$node['xpath']."<br>\n";
    }

    ////////////////////////////////

    // Quick out.
    if (empty($node)) return '';

    // The output starts as empty.
    $xmlOut = '';

    // This loop will output the text before the current child of a parent then the 
    // current child.  Where the child is a short tag we output the child, then move
    // onto the next child.  Where the child is not a short tag, we output the open tag, 
    // then queue up on currentParentStack[] the child.  
    //
    // When we run out of children, we then output the last text part, and close the 
    // 'parent' tag before popping the stack and carrying on.
    //
    // To illustrate, the numbers in this xml file indicate what is output on each
    // pass of the while loop:
    //
    // 1
    // <1>2
    //  <2>3
    //   <3/>4
    //  </4>5
    //  <5/>6
    // </6>

    // Although this is neater done using recursion, there's a 33% performance saving
    // to be gained by using this stack mechanism.

    // Only add CR's if "skip white spaces" was set. Otherwise leave as is.
    $CR = ($this->parseSkipWhiteCache) ? "\n" : '';
    $currentIndent = '';
    $hilightIsActive = is_array($this->hilightXpathList);

    // To keep track of where we are in the document we use a node stack.  The node 
    // stack has the following parallel entries:
    //   'Parent'     => (array) A copy of the parent node that who's children we are 
    //                           exporting
    //   'ChildIndex' => (array) The child index of the corresponding parent that we
    //                           are currently exporting.
    //   'Highlighted'=> (bool)  If we are highlighting this node.  Only relevant if
    //                           the hilight is active.

    // Setup our node stack.  The loop is designed to output children of a parent, 
    // not the parent itself, so we must put the parent on as the starting point.
    $nodeStack['Parent'] = array($node['parentNode']); 
    // And add the childpos of our node in it's parent to our "child index stack".
    $nodeStack['ChildIndex'] = array($node['pos']);
    // We start at 0.
    $nodeStackIndex = 0;

    // We have not to output text before/after our node, so blank it.  (As the nodeStack[]
    // holds copies of nodes, we can do this ok without affecting the document.)
    $nodeStack['Parent'][0]['textParts'][$node['pos']] = '';

    // While we still have data on our stack
    while ($nodeStackIndex >= 0) {
      // Count the children and get a copy of the current child.
      $iChildCount = count($nodeStack['Parent'][$nodeStackIndex]['childNodes']);
      $currentChild = $nodeStack['ChildIndex'][$nodeStackIndex];
      // Only do the auto indenting if the $parseSkipWhiteCache flag was set.
      if ($this->parseSkipWhiteCache)
        $currentIndent = str_repeat($this->indentStep, $nodeStackIndex);

      if ($bDebugThisFunction)
        echo "Exporting child ".($currentChild+1)." of node {$nodeStack['Parent'][$nodeStackIndex]['xpath']}\n";

      ///////////////////////////////////////////
      // Add the text before our child.

      // Add the text part before the current child
      if (!empty($nodeStack['Parent'][$nodeStackIndex]['textParts'][$currentChild])) {
        // Only add CR indent if there were children
        if ($iChildCount)
          $xmlOut .= $CR.$currentIndent;
        $xmlOut .= $nodeStack['Parent'][$nodeStackIndex]['textParts'][$currentChild];
      }
      if ($iChildCount && $nodeStackIndex) $xmlOut .= $CR;

      ///////////////////////////////////////////

      // Are there any more children?
      if ($iChildCount <= $currentChild) {
        // Nope, so output the last text before the closing tag
        if (!empty($nodeStack['Parent'][$nodeStackIndex]['textParts'][$currentChild+1])) 
          $xmlOut .= $currentIndent.$nodeStack['Parent'][$nodeStackIndex]['textParts'][$currentChild+1].$CR;

        // Now close this tag, as we are finished with this child.

        // Potentially output an (slightly smaller indent).
        if ($this->parseSkipWhiteCache
          && count($nodeStack['Parent'][$nodeStackIndex]['childNodes'])) {
          $xmlOut .= str_repeat($this->indentStep, $nodeStackIndex - 1);
        }

        // Check whether the xml-tag is to be hilighted.
        $highlightStart = $highlightEnd = '';
        if ($hilightIsActive) {
          $currentXpath = $nodeStack['Parent'][$nodeStackIndex]['xpath'];
          if (in_array($currentXpath, $this->hilightXpathList)) {
            // Yes we hilight
            $highlightStart = chr(2);
            $highlightEnd   = chr(3);
          }
        }
        $xmlOut .=  $highlightStart
                     .'</'.$nodeStack['Parent'][$nodeStackIndex]['name'].'>'
                     .$highlightEnd;
        // Decrement the $nodeStackIndex to go back to the next unfinished parent.
        $nodeStackIndex--;

        // If the index is 0 we are finished exporting the last node, as we may have been
        // exporting an internal node.
        if ($nodeStackIndex == 0) break;

        // Indicate to the parent that we are finished with this child.
        $nodeStack['ChildIndex'][$nodeStackIndex]++;

        continue;
      }

      ///////////////////////////////////////////
      // Ok, there are children still to process.

      // Queue up the next child (I can copy because I won't modify and copying is faster.)
      $nodeStack['Parent'][$nodeStackIndex + 1] = $nodeStack['Parent'][$nodeStackIndex]['childNodes'][$currentChild];

      // Work out if it is a short child tag.
      $iGrandChildCount = count($nodeStack['Parent'][$nodeStackIndex + 1]['childNodes']);
      $shortGrandChild = (($iGrandChildCount == 0) AND (implode('',$nodeStack['Parent'][$nodeStackIndex + 1]['textParts'])==''));

      ///////////////////////////////////////////
      // Assemble the attribute string first.
      $attrStr = '';
      foreach($nodeStack['Parent'][$nodeStackIndex + 1]['attributes'] as $key=>$val) {
        // Should we hilight the attribute?
        if ($hilightIsActive AND in_array($currentXpath.'/attribute::'.$key, $this->hilightXpathList)) {
          $hiAttrStart = chr(2);
          $hiAttrEnd   = chr(3);
        } else {
          $hiAttrStart = $hiAttrEnd = '';
        }
        $attrStr .= ' '.$hiAttrStart.$key.'="'.$val.'"'.$hiAttrEnd;
      }

      ///////////////////////////////////////////
      // Work out what goes before and after the tag content

      $beforeTagContent = $currentIndent;
      if ($shortGrandChild) $afterTagContent = '/>';
      else                  $afterTagContent = '>';

      // Check whether the xml-tag is to be hilighted.
      if ($hilightIsActive) {
        $currentXpath = $nodeStack['Parent'][$nodeStackIndex + 1]['xpath'];
        if (in_array($currentXpath, $this->hilightXpathList)) {
          // Yes we hilight
          $beforeTagContent .= chr(2);
          $afterTagContent  .= chr(3);
        }
      }
      $beforeTagContent .= '<';
//      if ($shortGrandChild) $afterTagContent .= $CR;
      
      ///////////////////////////////////////////
      // Output the tag

      $xmlOut .= $beforeTagContent
                  .$nodeStack['Parent'][$nodeStackIndex + 1]['name'].$attrStr
                  .$afterTagContent;

      ///////////////////////////////////////////
      // Carry on.            

      // If it is a short tag, then we've already done this child, we just move to the next
      if ($shortGrandChild) {
        // Move to the next child, we need not go deeper in the tree.
        $nodeStack['ChildIndex'][$nodeStackIndex]++;
        // But if we are just exporting the one node we'd go no further.
        if ($nodeStackIndex == 0) break;
      } else {
        // Else queue up the child going one deeper in the stack
        $nodeStackIndex++;
        // Start with it's first child
        $nodeStack['ChildIndex'][$nodeStackIndex] = 0;
      }
    }

    $result = $xmlOut;

    ////////////////////////////////////////////

    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($aStartTime, $result);
    }

    return $result;
  }
     
  //-----------------------------------------------------------------------------------------
  // XPathEngine           ------ Import the XML Source ------                               
  //-----------------------------------------------------------------------------------------
  
  /**
   * Reads a file or URL and parses the XML data.
   *
   * Parse the XML source and (upon success) store the information into an internal structure.
   *
   * @param     $fileName (string) Path and name (or URL) of the file to be read and parsed.
   * @return              (bool)   TRUE on success, FALSE on failure (check getLastError())
   * @see       importFromString(), getLastError(), 
   */
  function importFromFile($fileName) {
    $status = FALSE;
    $errStr = '';
    do { // try-block
      // Remember file name. Used in error output to know in which file it happend
      $this->properties['xmlFile'] = $fileName;
      // If we already have content, then complain.
      if (!empty($this->nodeRoot)) {
        $errStr = 'Called when this object already contains xml data. Use reset().';
        break; // try-block
      }
      // The the source is an url try to fetch it.
      if ( preg_match(';^http(s)?://;', $fileName) ) {
        // Read the content of the url...this is really prone to errors, and we don't really
        // check for too many here...for now, suppressing both possible warnings...we need
        // to check if we get a none xml page or something of that nature in the future
        $xmlString = @implode('', @file($fileName));
        if (!empty($xmlString)) {
          $status = TRUE;
        } else {
          $errStr = "The url '{$fileName}' could not be found or read.";
        }
        break; // try-block
      } 
      
      // Reaching this point we're dealing with a real file (not an url). Check if the file exists and is readable.
      if (!is_readable($fileName)) { // Read the content from the file
        $errStr = "File '{$fileName}' could not be found or read.";
        break; // try-block
      }
      if (is_dir($fileName)) {
        $errStr = "'{$fileName}' is a directory.";
        break; // try-block
      }
      // Read the file
      if (!($fp = @fopen($fileName, 'rb'))) {
        $errStr = "Failed to open '{$fileName}' for read.";
        break; // try-block
      }
      $xmlString = fread ($fp, filesize($fileName));
      @fclose($fp);
      
      $status = TRUE;
    } while (FALSE);
    
    if (!$status) {
      $this->_displayError('In importFromFile(): '. $errStr, __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    return $this->importFromString($xmlString);
  }
  
  /**
   * Reads a string and parses the XML data.
   *
   * Parse the XML source and (upon success) store the information into an internal structure.
   * If a parent xpath is given this means that XML data is to be *appended* to that parent.
   *
   * ### If a function uses setLastError(), then say in the function header that getLastError() is useful.
   *
   * @param  $xmlString           (string) Name of the string to be read and parsed.
   * @param  $absoluteParentPath  (string) Node to append data too (see above)
   * @return                      (bool)   TRUE on success, FALSE on failure 
   *                                       (check getLastError())
   */
  function importFromString($xmlString, $absoluteParentPath = '') {
    $bDebugThisFunction = FALSE;

    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("importFromString");
      echo "Importing from string of length ".strlen($xmlString)." to node '$absoluteParentPath'\n<br>";
      echo "Parser options:\n<br>";
      print_r($this->parseOptions);
    }

    $status = FALSE;
    $errStr = '';
    do { // try-block
      // If we already have content, then complain.
      if (!empty($this->nodeRoot) AND empty($absoluteParentPath)) {
        $errStr = 'Called when this object already contains xml data. Use reset() or pass the parent Xpath as 2ed param to where tie data will append.';
        break; // try-block
      }
      // Check whether content has been read.
      if (empty($xmlString)) {
        // Nothing to do!!
        $status = TRUE;
        // If we were importing to root, build a blank root.
        if (empty($absoluteParentPath)) {
          $this->nodeRoot = array();
        }
        $this->reindexNodeTree();
//        $errStr = 'This xml document (string) was empty';
        break; // try-block
      } else {
        $xmlString = $this->_translateAmpersand($xmlString);
      }
      
      // Restart our node index with a root entry.
      $nodeStack = array();
      $this->parseStackIndex = 0;

      // If a parent xpath is given this means that XML data is to be *appended* to that parent.
      if (!empty($absoluteParentPath)) {
        // Check if parent exists
        if (!isSet($nodeIndex[$absoluteParentPath])) {
          $errStr = "You tried to append XML data to a parent '$absoluteParentPath' that does not exist.";
          break; // try-block
        } 
        // Add it as the starting point in our array.
        $this->nodeStack[0] =& $nodeIndex[$absoluteParentPath];
      } else {
        // Build a 'super-root'
        $this->nodeRoot = $this->emptyNode;
        $this->nodeRoot['name']      = '';
        $this->nodeRoot['parentNode'] = NULL;
        // Put it in as the start of our node stack.
        $this->nodeStack[0] =& $this->nodeRoot;
      }

      // Point our text buffer reference at the next text part of the root
      $this->parsedTextLocation =& $this->nodeStack[0]['textParts'][];
      $this->parsInCData = 0;
      // We cache this now.
      $this->parseSkipWhiteCache = isSet($this->parseOptions[XML_OPTION_SKIP_WHITE]) ? $this->parseOptions[XML_OPTION_SKIP_WHITE] : FALSE;
      
      // Create an XML parser.
      $parser = xml_parser_create();
      // Set default XML parser options.
      if (is_array($this->parseOptions)) {
        foreach($this->parseOptions as $key => $val) {
          xml_parser_set_option($parser, $key, $val);
        }
      }
      
      // Set the object and the element handlers for the XML parser.
      xml_set_object($parser, $this);
      xml_set_element_handler($parser, '_handleStartElement', '_handleEndElement');
      xml_set_character_data_handler($parser, '_handleCharacterData');
      xml_set_default_handler($parser, '_handleDefaultData');
      xml_set_processing_instruction_handler($parser, '_handlePI');
     
      if ($bDebugThisFunction)
       $this->_profileFunction($aStartTime, "Setup for parse");

      // Parse the XML source and on error generate an error message.
      if (!xml_parse($parser, $xmlString, TRUE)) {
        $source = empty($this->properties['xmlFile']) ? 'string' : 'file ' . basename($this->properties['xmlFile']) . "'";
        $errStr = "XML error in given {$source} on line ".
               xml_get_current_line_number($parser). '  column '. xml_get_current_column_number($parser) .
               '. Reason:'. xml_error_string(xml_get_error_code($parser));
        break; // try-block
      }
      
      // Free the parser.
      @xml_parser_free($parser);
      // And we don't need this any more.
      $this->nodeStack = array();

      if ($bDebugThisFunction)
       $this->_profileFunction($aStartTime, "Parse Object");
      
      $this->reindexNodeTree();

      if ($bDebugThisFunction)
       $this->_profileFunction($aStartTime, "Reindex Object");
      
      $status = TRUE;
    } while (FALSE);
    
    if (!$status) {
      $this->_displayError('In importFromString(): '. $errStr, __LINE__, __FILE__, FALSE);
      $bResult = FALSE;
    } else {
      $bResult = TRUE;
    }

    ////////////////////////////////////////////

    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($aStartTime, $bResult);
    }

    return $bResult;
  }
  
  
  //-----------------------------------------------------------------------------------------
  // XPathEngine               ------  XML Handlers  ------                                  
  //-----------------------------------------------------------------------------------------
  
  /**
   * Handles opening XML tags while parsing.
   *
   * While parsing a XML document for each opening tag this method is
   * called. It'll add the tag found to the tree of document nodes.
   *
   * @param $parser     (int)    Handler for accessing the current XML parser.
   * @param $name       (string) Name of the opening tag found in the document.
   * @param $attributes (array)  Associative array containing a list of
   *                             all attributes of the tag found in the document.
   * @see _handleEndElement(), _handleCharacterData()
   */
  function _handleStartElement($parser, $nodeName, $attributes) {
    if (empty($nodeName)) {
      $this->_displayError('XML error in file at line'. xml_get_current_line_number($parser) .'. Empty name.', __LINE__, __FILE__);
      return;
    }

    // Trim accumulated text if necessary.
    if ($this->parseSkipWhiteCache) {
      $iCount = count($this->nodeStack[$this->parseStackIndex]['textParts']);
      $this->nodeStack[$this->parseStackIndex]['textParts'][$iCount-1] = rtrim($this->parsedTextLocation);
    } 

    if ($this->bDebugXmlParse) {
      echo "<blockquote>" . htmlspecialchars("Start node: <".$nodeName . ">")."<br>";
      echo "Appended to stack entry: $this->parseStackIndex<br>\n";
      echo "Text part before element is: ".htmlspecialchars($this->parsedTextLocation);
      /*
      echo "<pre>";
      $dataPartsCount = count($this->nodeStack[$this->parseStackIndex]['textParts']);
      for ($i = 0; $i < $dataPartsCount; $i++) {
        echo "$i:". htmlspecialchars($this->nodeStack[$this->parseStackIndex]['textParts'][$i])."\n";
      }
      echo "</pre>";
      */
    }

    // Add a node and set path to current.
    if (!$this->_internalAppendChild($this->parseStackIndex, $nodeName)) {
      $this->_displayError('Internal error during parse of XML file at line'. xml_get_current_line_number($parser) .'. Empty name.', __LINE__, __FILE__);
      return;
    }    

    // We will have gone one deeper then in the stack.
    $this->parseStackIndex++;

    // Point our parseTxtBuffer reference at the new node.
    $this->parsedTextLocation =& $this->nodeStack[$this->parseStackIndex]['textParts'][0];
    
    // Set the attributes.
    if (!empty($attributes)) {
      if ($this->bDebugXmlParse) {
        echo 'Attributes: <br>';
        print_r($attributes);
        echo '<br>';
      }
      $this->nodeStack[$this->parseStackIndex]['attributes'] = $attributes;
    }
  }
  
  /**
   * Handles closing XML tags while parsing.
   *
   * While parsing a XML document for each closing tag this method is called.
   *
   * @param $parser (int)    Handler for accessing the current XML parser.
   * @param $name   (string) Name of the closing tag found in the document.
   * @see       _handleStartElement(), _handleCharacterData()
   */
  function _handleEndElement($parser, $name) {
    if (($this->parsedTextLocation=='') 
        && empty($this->nodeStack[$this->parseStackIndex]['textParts'])) {
      // We reach this point when parsing a tag of format <foo/>. The 'textParts'-array 
      // should stay empty and not have an empty string in it.
    } else {
      // Trim accumulated text if necessary.
      if ($this->parseSkipWhiteCache) {
        $iCount = count($this->nodeStack[$this->parseStackIndex]['textParts']);
        $this->nodeStack[$this->parseStackIndex]['textParts'][$iCount-1] = rtrim($this->parsedTextLocation);
      }
    }

    if ($this->bDebugXmlParse) {
      echo "Text part after element is: ".htmlspecialchars($this->parsedTextLocation)."<br>\n";
      echo htmlspecialchars("Parent:<{$this->parseStackIndex}>, End-node:</$name> '".$this->parsedTextLocation) . "'<br>Text nodes:<pre>\n";
      $dataPartsCount = count($this->nodeStack[$this->parseStackIndex]['textParts']);
      for ($i = 0; $i < $dataPartsCount; $i++) {
        echo "$i:". htmlspecialchars($this->nodeStack[$this->parseStackIndex]['textParts'][$i])."\n";
      }
      var_dump($this->nodeStack[$this->parseStackIndex]['textParts']);
      echo "</pre></blockquote>\n";
    }

    // Jump back to the parent element.
    $this->parseStackIndex--;

    // Set our reference for where we put any more whitespace
    $this->parsedTextLocation =& $this->nodeStack[$this->parseStackIndex]['textParts'][];

    // Note we leave the entry in the stack, as it will get blanked over by the next element
    // at this level.  The safe thing to do would be to remove it too, but in the interests 
    // of performance, we will not bother, as were it to be a problem, then it would be an
    // internal bug anyway.
    if ($this->parseStackIndex < 0) {
      $this->_displayError('Internal error during parse of XML file at line'. xml_get_current_line_number($parser) .'. Empty name.', __LINE__, __FILE__);
      return;
    }    
  }
  
  /**
   * Handles character data while parsing.
   *
   * While parsing a XML document for each character data this method
   * is called. It'll add the character data to the document tree.
   *
   * @param $parser (int)    Handler for accessing the current XML parser.
   * @param $text   (string) Character data found in the document.
   * @see       _handleStartElement(), _handleEndElement()
   */
  function _handleCharacterData($parser, $text) {
    if ($this->parsInCData >0) $text = $this->_translateAmpersand($text, $reverse=TRUE);
    
    if ($this->bDebugXmlParse) echo "Handling character data: '".htmlspecialchars($text)."'<br>";
    if ($this->parseSkipWhiteCache AND !empty($text) AND !$this->parsInCData) {
      // Special case CR. CR always comes in a separate data. Trans. it to '' or ' '. 
      // If txtBuffer is already ending with a space use '' otherwise ' '.
      $bufferHasEndingSpace = (empty($this->parsedTextLocation) OR substr($this->parsedTextLocation, -1) === ' ') ? TRUE : FALSE;
      if ($text=="\n") {
        $text = $bufferHasEndingSpace ? '' : ' ';
      } else {
        if ($bufferHasEndingSpace) {
          $text = ltrim(preg_replace('/\s+/', ' ', $text));
        } else {
          $text = preg_replace('/\s+/', ' ', $text);
        }
      }
      if ($this->bDebugXmlParse) echo "'Skip white space' is ON. reduced to : '" .htmlspecialchars($text) . "'<br>";
    }
    $this->parsedTextLocation .= $text;
  }
  
  /**
   * Default handler for the XML parser.  
   *
   * While parsing a XML document for string not caught by one of the other
   * handler functions, we end up here.
   *
   * @param $parser (int)    Handler for accessing the current XML parser.
   * @param $text   (string) Character data found in the document.
   * @see       _handleStartElement(), _handleEndElement()
   */
  function _handleDefaultData($parser, $text) {
    do { // try-block
      if (!strcmp($text, '<![CDATA[')) {
        $this->parsInCData++;
      } elseif (!strcmp($text, ']]>')) {
        $this->parsInCData--;
        if ($this->parsInCData < 0) $this->parsInCData = 0;
      }
      $this->parsedTextLocation .= $this->_translateAmpersand($text, $reverse=TRUE);
      if ($this->bDebugXmlParse) echo "Default handler data: ".htmlspecialchars($text)."<br>";    
      break; // try-block
    } while (FALSE); // END try-block
  }
  
  /**
   * Handles processing instruction (PI)
   *
   * A processing instruction has the following format: 
   * <?  target data  ? > e.g.  <? dtd version="1.0" ? >
   *
   * Currently I have no bether idea as to left it 'as is' and treat the PI data as normal 
   * text (and adding the surrounding PI-tags <? ? >). 
   *
   * @param     $parser (int)    Handler for accessing the current XML parser.
   * @param     $target (string) Name of the PI target. E.g. XML, PHP, DTD, ... 
   * @param     $data   (string) Associative array containing a list of
   * @see       PHP's manual "xml_set_processing_instruction_handler"
   */
  function _handlePI($parser, $target, $data) {
    $data = $this->_translateAmpersand($data, $reverse=TRUE);
    $this->parsedTextLocation .= "<?{$target} {$data}?>";
    return TRUE;
  }
  
  //-----------------------------------------------------------------------------------------
  // XPathEngine          ------  Node Tree Stuff  ------                                    
  //-----------------------------------------------------------------------------------------
  
  /**
   * Adds a new node to the XML document tree during xml parsing.
   *
   * This method adds a new node to the tree of nodes of the XML document
   * being handled by this class. The new node is created according to the
   * parameters passed to this method.  This method is a much watered down
   * version of appendChild(), used in parsing an xml file only.
   * 
   * It is assumed that adding starts with root and progresses through the
   * document in parse order.  New nodes must have a corresponding parent. And
   * once we have read the </> tag for the element we will never need to add
   * any more data to that node.  Otherwise the add will be ignored or fail.
   *
   * The function is faciliated by a nodeStack, which is an array of nodes that
   * we have yet to close.
   *
   * @param   $stackParentIndex (int)    The index into the nodeStack[] of the parent
   *                                     node to which the new node should be added as 
   *                                     a child. *READONLY*
   * @param   $nodeName         (string) Name of the new node. *READONLY*
   * @return                    (bool)   TRUE if we successfully added a new child to 
   *                                     the node stack at index $stackParentIndex + 1,
   *                                     FALSE on error.
   */
  function _internalAppendChild($stackParentIndex, $nodeName) {
    // This call is likely to be executed thousands of times, so every 0.01ms counts.
    // If you want to debug this function, you'll have to comment the stuff back in
    //$bDebugThisFunction = FALSE;
    
    /*
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("_internalAppendChild");
      echo "Current Node (parent-index) and the child to append : '{$stackParentIndex}' +  '{$nodeName}' \n<br>";
    }
    */
   	//////////////////////////////////////

    if (!isSet($this->nodeStack[$stackParentIndex])) {
      $errStr = "Invalid parent. You tried to append the tag '{$nodeName}' to an non-existing parent in our node stack '{$stackParentIndex}'.";
      $this->_displayError('In _internalAppendChild(): '. $errStr, __LINE__, __FILE__, FALSE); 

      /*
      if ($bDebugThisFunction)
        $this->_closeDebugFunction($aStartTime, FALSE);
      */

      return FALSE;
    }

    // Retrieve the parent node from the node stack.  This is the last node at that 
    // depth that we have yet to close.  This is where we should add the text/node.
    $parentNode =& $this->nodeStack[$stackParentIndex];
          
    // Brand new node please
    $newChildNode = $this->emptyNode;
    
    // Save the vital information about the node.
    $newChildNode['name'] = $nodeName;
    $parentNode['childNodes'][] =& $newChildNode;
    
    // Add to our node stack
    $this->nodeStack[$stackParentIndex + 1] =& $newChildNode;

    /*
    if ($bDebugThisFunction) {
      echo "The new node received index: '".($stackParentIndex + 1)."'\n";
      foreach($this->nodeStack as $key => $val) echo "$key => ".$val['name']."\n"; 
      $this->_closeDebugFunction($aStartTime, TRUE);
    }
    */

    return TRUE;
  }
  
  /**
   * Update nodeIndex and every node of the node-tree. 
   *
   * Call after you have finished any tree modifications other wise a match with 
   * an xPathQuery will produce wrong results.  The $this->nodeIndex[] is recreated 
   * and every nodes optimization data is updated.  The optimization data is all the
   * data that is duplicate information, would just take longer to find. Child nodes 
   * with value NULL are removed from the tree.
   *
   * By default the modification functions in this component will automatically re-index
   * the nodes in the tree.  Sometimes this is not the behaver you want. To surpress the 
   * reindex, set the functions $autoReindex to FALSE and call reindexNodeTree() at the 
   * end of your changes.  This sometimes leads to better code (and less CPU overhead).
   *
   * Sample:
   * =======
   * Given the xml is <AAA><B/>.<B/>.<B/></AAA> | Goal is <AAA>.<B/>.</AAA>  (Delete B[1] and B[3])
   *   $xPathSet = $xPath->match('//B'); # Will result in array('/AAA[1]/B[1]', '/AAA[1]/B[2]', '/AAA[1]/B[3]');
   * Three ways to do it.
   * 1) Top-Down  (with auto reindexing) - Safe, Slow and you get easily mix up with the the changing node index
   *    removeChild('/AAA[1]/B[1]'); // B[1] removed, thus all B[n] become B[n-1] !!
   *    removeChild('/AAA[1]/B[2]'); // Now remove B[2] (That originaly was B[3])
   * 2) Bottom-Up (with auto reindexing) -  Safe, Slow and the changing node index (caused by auto-reindex) can be ignored.
   *    for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
   *      if ($i==1) continue; 
   *      removeChild($xPathSet[$i]);
   *    }
   * 3) // Top-down (with *NO* auto reindexing) - Fast, Safe as long as you call reindexNodeTree()
   *    foreach($xPathSet as $xPath) {
   *      // Specify no reindexing
   *      if ($xPath == $xPathSet[1]) continue; 
   *      removeChild($xPath, $autoReindex=FALSE);
   *      // The object is now in a slightly inconsistent state.
   *    }
   *    // Finally do the reindex and the object is consistent again
   *    reindexNodeTree();
   *
   * @return (bool) TRUE on success, FALSE otherwise.
   * @see _recursiveReindexNodeTree()
   */
  function reindexNodeTree() {
    //return;
    $this->nodeIndex = array();
    $this->nodeIndex[''] =& $this->nodeRoot;
    // Quick out for when the tree has no data.
    if (empty($this->nodeRoot)) return TRUE;
    return $this->_recursiveReindexNodeTree('');
  }
  
  /**
   * Here's where the work is done for reindexing (see reindexNodeTree)
   *
   * @param  $absoluteParentPath (string) the xPath to the parent node
   * @return                     (bool)   TRUE on success, FALSE otherwise.
   * @see reindexNodeTree()
   */
  function _recursiveReindexNodeTree($absoluteParentPath) {
    $parentNode =& $this->nodeIndex[$absoluteParentPath];
    
    // Check for any 'dead' child nodes first and concate the text parts if found.
    for ($i=sizeOf($parentNode['childNodes'])-1; $i>=0; $i--) {
      // Check if the child node still exits (it may have been removed).
      if (!empty($parentNode['childNodes'][$i])) continue;
      // Child node was removed. We got to merge the text parts then.
      $parentNode['textParts'][$i] .= $parentNode['textParts'][$i+1];
      array_splice($parentNode['textParts'], $i+1, 1); 
      array_splice($parentNode['childNodes'], $i, 1);
    }
    
    // Now start a reindex.
    $contextHash = array();
    $childSize = sizeOf($parentNode['childNodes']);
    for ($i=0; $i<$childSize; $i++) {
      $childNode =& $parentNode['childNodes'][$i];
      // Make sure that theire is a text-part infornt of every node. (May be empty)
      if (!isSet($parentNode['textParts'][$i])) $parentNode['textParts'][$i] = '';
      // Count the nodes with same name (to determin theire context position)
      $childName = $childNode['name'];
      if (empty($contextHash[$childName])) { 
        $contextHash[$childName] = 1;
      } else {
        $contextHash[$childName]++;
      }
      // Make the node-index hash
      $newPath = $absoluteParentPath . '/' . $childName . '['.$contextHash[$childName].']';
      $this->nodeIndex[$newPath] =& $childNode;
      // Update the node info (optimisation)
      $childNode['parentNode'] =& $parentNode;
      $childNode['depth'] = $parentNode['depth'] +1;
      $childNode['pos'] = $i;
      $childNode['contextPos'] = $contextHash[$childName];
      $childNode['xpath'] = $newPath;
      $this->_recursiveReindexNodeTree($newPath);
    }
    // Make sure that theire is a text-part after the last node.
    if (!isSet($parentNode['textParts'][$i])) $parentNode['textParts'][$i] = '';
    return TRUE;
  }
  
  /** 
   * Clone a node and it's child nodes.
   *
   * NOTE: If the node has children you *MUST* use the reference operator!
   *       E.g. $clonedNode =& cloneNode($node);
   *       Otherwise the children will not point back to the parent, they will point 
   *       back to your temporary variable instead.
   *
   * @param   $node (mixed)  Either a node (hash array) or an abs. Xpath to a node in 
   *                         the current doc
   * @return        (&array) A node and it's child nodes.
   */
  function &cloneNode($node) {
    if (is_string($node) AND isSet($this->nodeIndex[$node])) {
      $node = $this->nodeIndex[$node];
    }
    
    $childSize = sizeOf($node['childNodes']);
    for ($i=0; $i<$childSize; $i++) {
      $childNode =& $this->cloneNode($node['childNodes'][$i]);  // copy child 
      $node['childNodes'][$i] =& $childNode; // reference the copy
      $childNode['parentNode'] =& $node;      // child references the parent.
    }
    return $node;
  }
  
  
/** Nice to have but __sleep() has a bug. 
    (2002-2 PHP V4.1. See bug #15350)
  
  /**
   * PHP cals this function when you call PHP's serialize. 
   *
   * It prevents cyclic referencing, which is why print_r() of an XPath object doesn't work.
   *
  function __sleep() {
    // Destroy recursive pointers
    $keys = array_keys($this->nodeIndex);
    $size = sizeOf($keys);
    for ($i=0; $i<$size; $i++) {
      unset($this->nodeIndex[$keys[$i]]['parentNode']);
    }
    unset($this->nodeIndex);
  }
  
  /**
   * PHP cals this function when you call PHP's unserialize. 
   *
   * It reindexes the node-tree
   *
  function __wakeup() {
    $this->reindexNodeTree();
  }
  
*/
  
  //-----------------------------------------------------------------------------------------
  // XPath            ------  XPath Query / Evaluation Handlers  ------                      
  //-----------------------------------------------------------------------------------------
  
  /**
   * Matches (evaluates) an XPath expression.
   *
   * This method tries to evaluate an XPath expression by parsing it. A XML source must 
   * have been imported before this method is able to work.
   *
   * @param     $xPathQuery  (string) XPath expression to be evaluated.
   * @param     $baseXPath   (string) (default is super-root) Full path of a document node, 
   *                                  from which the XPath expression should  start evaluating.
   * @return                 (array)  The returned vector contains a list of the full document 
   *                                  Xpaths of all nodes that match the evaluated XPath 
   *                                  expression.  Returns FALSE on error.
   */
  function match($xPathQuery, $baseXPath='') {
    // Replace a double slashes, because they'll cause problems otherwise.
    static $slashes2descendant = array(
        '//@' => '/descendant::*/attribute::', 
        '//'  => '/descendant::', 
        '/@'  => '/attribute::');
    // Stupid idea from W3C to take axes name containing a '-' (dash) !!!
    // We replace the '-' with '_' to avoid the conflict with the minus operator.
    static $dash2underscoreHash = array( 
        '-sibling'    => '_sibling', 
        '-or-'        => '_or_',
        'starts-with' => 'starts_with', 
        'substring-before' => 'substring_before',
        'substring-after'  => 'substring_after', 
        'string-length'    => 'string_length',
        'normalize-space'  => 'normalize_space');
    
    if (empty($xPathQuery)) return array();

    // Special case for when document is empty.
    if (empty($this->nodeRoot)) return array();

    if (!isSet($this->nodeIndex[$baseXPath])) {
      $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'],$xPathQuery), __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    
    // Replace a double slashes, and '-' (dash) in axes names.
    $xPathQuery = strtr($xPathQuery, $slashes2descendant);
    $xPathQuery = strtr($xPathQuery, $dash2underscoreHash);
    
    return $this->_internalEvaluate($xPathQuery, $baseXPath);
  }
  
  /**
   * Alias for the match function
   *
   * @see match()
   */
  function evaluate($xPathQuery, $baseXPath='') {
    return $this->match($xPathQuery, $baseXPath);
  }
  
  /**
   * Internal recursive evaluate an-XPath-expression function.
   *
   * $this->evaluate() is the entry point and does some inits, while this 
   * function is called recursive internaly for every sub-xPath expresion we find.
   *
   * @param  $xPathQuery  (string) XPath expression to be evaluated.
   * @param  $contextPath (mixed) (string or array) Full path of a document node, starting
   *                              from which the XPath expression should be evaluated.
   * @return              (array) Vector of absolute XPath's, or FALSE on error.
   * @see    evaluate()
   */
  function _internalEvaluate($xPathQuery, $contextPath='') {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("evaluate");
      echo "Path: $xPathQuery\n Context: $contextPath\n";
    }
    
    // Numpty check
    if (empty($xPathQuery)) {
      $this->_displayError("The $xPathQuery argument must have a value.", __LINE__, __FILE__);
      return FALSE;
    }
    
    // Split the paths that are sparated by '|' into distinct xPath expresions.
    $xpathQueryList = (strpos($xPathQuery, '|') === FALSE) ? array($xPathQuery) : $this->_bracketExplode('|', $xPathQuery);
    if ($bDebugThisFunction) { echo "<hr>Split the paths that are sparated by '|'\n"; print_r($xpathQueryList); }
    
    // Create an empty set to save the result.
    $result = array();
    
    // Run through all paths.
    foreach ($xpathQueryList as $xQuery) {
      // mini syntax check
      if (!$this->_bracketsCheck($xQuery)) {
        $this->_displayError('While parsing an XPath expression, in the predicate ' .
        str_replace($xQuery, '<b>'.$xQuery.'</b>', $xPathQuery) .
        ', there was an invalid number of brackets or a bracket mismatch.', __LINE__, __FILE__);
      }
      // Save the current path.
      $this->currentXpathQuery = $xQuery;
      // Split the path at every slash *outside* a bracket.
      $steps = $this->_bracketExplode('/', $xQuery);
      if ($bDebugThisFunction) { echo "<hr>Split the path '$xQuery' at every slash *outside* a bracket.\n "; print_r($steps); }
      // Check whether the first element is empty.
      if (empty($steps[0])) {
        // Remove the first and empty element. It's a starting  '//'.
        array_shift($steps);
      }
      // Start to evaluate the steps.
      $nodes = $this->_evaluateStep($contextPath, $steps);
      // Remove duplicated nodes.
      $nodes = array_unique($nodes);
      // Add the nodes to the result set.
      $result = array_merge($result, $nodes);
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($aStartTime, $result);
    }
    // Return the result.
    return $result;
  }
  
  /**
   * Evaluate a step from a XPathQuery expression at a specific contextPath.
   *
   * Steps are the arguments of a XPathQuery when divided by a '/'. A contextPath is a 
   * absolute XPath (or vector of XPaths) to a starting node(s) from which the step should 
   * be evaluated.
   *
   * @param  $contextPath  (mixed) String or vector.  A absolute XPath OR vector of XPaths 
   *                               (see above)
   * @param  $steps        (array) Vector containing the remaining steps of the current 
   *                               XPathQuery expression.
   * @return               (array) Vector of absolute XPath's as a result of the step 
   *                               evaluation.
   * @see    evaluate()
   */
  function _evaluateStep($contextPath, $steps) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction(__LINE__.":_evaluateStep(contextPath:[$contextPath], steps:[$steps])");
      if (is_array($contextPath)) {
        echo "Context:\n";
        print_r($contextPath);
      } else {
        echo "Context: $contextPath\n";
      }
      echo "Steps: ";
      print_r($steps);
      echo "<hr>\n";
    }
    $xPathSet = array(); // Create an empty array for saving the abs. XPath's found.
    // We may have an "array" of one context.  If so convert it from 
    // array to single string.  Often, this function will be called with
    // a /Path1[1]/Path[3]/Path[2] sytle predicate.
    if (is_array($contextPath) && (count($contextPath) == 1)) $contextPath = $contextPath[0];
    // Check whether the context is an array of contexts.
    if (is_array($contextPath)) {
      // Run through the array.
      $size = sizeOf($contextPath);
      for ($i=0; $i<$size; $i++) {
        if ($bDebugThisFunction) echo __LINE__.":Evaluating step for the {$contextPath[$i]} context...\n";        
        // Call this method for this single path.
        $xPathSet = array_merge($xPathSet, $this->_evaluateStep($contextPath[$i], $steps));
      }
    } else {
      $contextPaths = array();   // Create an array to save the new contexts.
      $step = trim(array_shift($steps)); // Get this step.
      if ($bDebugThisFunction) echo __LINE__.":Evaluating step $step\n";
      
      $axis = $this->_getAxis($step, $contextPath); // Get the axis of the current step.
      if ($bDebugThisFunction) { echo __LINE__.":Axis of step is:\n"; print_r($axis); echo "\n";}
      
      // Check whether it's a function.
      if ($axis['axis'] == 'function') {
        // Check whether an array was return by the function.
        if (is_array($axis['node-test'])) {
          $contextPaths = array_merge($contextPaths, $axis['node-test']);  // Add the results to the list of contexts.
        } else {
          $contextPaths[] = $axis['node-test']; // Add the result to the list of contexts.
        }
      } else {
        $method = '_handleAxis_' . $axis['axis']; // Create the name of the method.
      
        // Check whether the axis handler is defined. If not display an error message.
        if (!method_exists($this, $method)) {
          $this->_displayError('While parsing an XPath expression, the axis ' .
          $axis['axis'] . ' could not be handled, because this version does not support this axis.', __LINE__, __FILE__);
        }
        if ($bDebugThisFunction) echo __LINE__.":Calling user method $method\n";        
        
        // Perform an axis action.
        $contextPaths = $this->$method($axis, $contextPath);
        if ($bDebugThisFunction) { echo __LINE__.":We found these contexts from this step:\n"; print_r( $contextPaths ); echo "\n";}
        
        // Check whether there are predicates.
        if (count($axis['predicate']) > 0) {
          if ($bDebugThisFunction) echo __LINE__.":Filtering contexts by predicate...\n";        
          
          // Check whether each node fits the predicates.
          $contextPaths = $this->_checkPredicates($contextPaths, $axis['predicate'], $axis['node-test']);
        }
      }
      
      // Check whether there are more steps left.
      if (count($steps) > 0) {
        if ($bDebugThisFunction) echo __LINE__.":Evaluating next step given the context of the first step...\n";        
        // Continue the evaluation of the next steps.
        $xPathSet = $this->_evaluateStep($contextPaths, $steps);
      } else {
        $xPathSet = $contextPaths; // Save the found contexts.
      }
    }
    
    if ($bDebugThisFunction) $this->_closeDebugFunction($aStartTime, $xPathSet);
    
    // Return the result.
    return $xPathSet;
  }
  
  /**
   * Checks whether a node matches predicates.
   *
   * This method checks whether a list of nodes passed to this method match
   * a given list of predicates. 
   *
   * @param  $xPathSet   (array)  Array of full paths of all nodes to be tested.
   * @param  $predicates (array)  Array of predicates to use.
   * @param  $nodeTest   (string) The node test used to filter the node set.  Passed 
   *                              to evaluatePredicate()
   * @return             (array)  Vector of absolute XPath's that match the given predicates.
   * @see    _evaluateStep()
   */
  function _checkPredicates($xPathSet, $predicates, $nodeTest) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("_checkPredicates(Nodes:[$xPathSet], Predicates:[$predicates])");
      echo "XPathSet:";
      print_r($xPathSet);
      echo "Predicates:";
      print_r($predicates);
      echo "<hr>";
    }
    //////////////////////////////////////////////
    // Create an empty set of nodes.
    $result = array();
    
    // Run through all nodes.
    $nSize = sizeOf($xPathSet);
    for ($i=0; $i<$nSize; $i++) {
      $xPath = $xPathSet[$i];
      // Create a variable whether to add this node to the node-set.
      $add = TRUE;
      
      // Run through all predicates.
      $pSize = sizeOf($predicates);
      for ($j=0; $j<$pSize; $j++) {
        $predicate = $predicates[$j]; 
        if ($bDebugThisFunction) echo "Evaluating predicate \"$predicate\"\n";
        // Check whether the predicate is just an number.
        if (preg_match('/^\d+$/', $predicate)) {
          if ($bDebugThisFunction) echo "Taking short cut and calling _handleFunction_position() directly.\n";
          // Take a short cut.  If it is just a position, then call 
          // _handleFunction_position() directly.  70% of the
          // time this will be the case. ## N.S
          $check = (bool) ($predicate == $this->_handleFunction_position($xPath, '', $nodeTest));
          // Enhance the predicate.
          //                    $predicate .= "=position()";
        } else {                
          // Else do the predicate check the long and thorough way.
          $check = $this->_evaluatePredicate($xPath, $predicate, $nodeTest);
        }
        // Check whether it's a string.
        if (is_string($check) && ( ( $check == '' ) || ( $check == $predicate ) )) {
          $check = FALSE; // Set the result to FALSE.
        } 
        else if (is_bool($check) )  {
          // 0 and 1 are both bools and ints.  We need to capture the bools
          // as they might have been the intended result                    ## N.S
        } else {
          if (is_int($check)) { // Check whether it's an integer.
            // Check whether it's the current position.
            $check = (bool) ($check == $this->_handleFunction_position($xPath, '', $nodeTest));
          }
        }
        if ($bDebugThisFunction) echo "Node $xPath matches predicate $predicate: " . (($check) ? "TRUE" : "FALSE") ."\n";
        // Check whether the predicate is OK for this node.
        $add = $add && $check;
      }
       
      // Check whether to add this node to the node-set.
      if ($add) {
        $result[] = $xPath; // Add the node to the node-set.
      }            
      if ($bDebugThisFunction) echo "Node $xPath matches: " . (($add) ? "TRUE" : "FALSE") ."\n\n";        
    }
    //////////////////////////////////////////////
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($aStartTime, $result);
    }
    // Return the array of nodes.
    return $result;
  }
  
  /**
   * Evaluates an XPath function
   *
   * This method evaluates a given XPath function with its arguments on a
   * specific node of the document.
   *
   * @param  $function      (string) Name of the function to be evaluated.
   * @param  $arguments     (string) String containing the arguments being
   *                                 passed to the function.
   * @param  $absoluteXPath (string) Full path to the document node on which the
   *                                 function should be evaluated.
   * @return                (mixed)  This method returns the result of the evaluation of
   *                                 the function. Depending on the function the type of the 
   *                                 return value can be different.
   * @see    evaluate()
   */
  function _evaluateFunction($function, $arguments, $absoluteXPath, $nodeTest='') {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("_evaluateFunction(Function:[$function], Arguments:[$arguments], node:[$absoluteXPath], nodeTest:[$nodeTest])");
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
    if (!method_exists($this, $method)) {
      // Display an error message.
      $this->_displayError("While parsing an XPath expression, ".
        "the function \"$function\" could not be handled, because this ".
        "version does not support this function.", __LINE__, __FILE__);
    }
    if ($bDebugThisFunction) echo "Calling function $method($absoluteXPath, $arguments)\n"; 
    
    // Return the result of the function.
    $result = $this->$method($absoluteXPath, $arguments, $nodeTest);
    
    //////////////////////////////////////////////
    // Return the nodes found.
    if ($bDebugThisFunction) {
      $this->_closeDebugFunction($aStartTime, $result);
    }
    // Return the result.
    return $result;
  }
  
  /**
   * Evaluates a predicate on a node.
   *
   * This method tries to evaluate a predicate on a given node.
   *
   * @param  $absoluteXPath (string) Full path of the node on which the predicate
   *                                 should be evaluated.
   * @param  $predicate     (string) String containing the predicate expression
   *                                 to be evaluated.
   * @param  $nodeTest      (string) The node test used to filter the node set.
   * @return                (mixed)  This method is called recursively. The first call 
   *                                 should return a boolean value, whether the node 
   *                                 matches the predicateor not. Any call to the 
   *                                 method being made during the recursion
   *                                 may also return other types for further processing.
   * @see    evaluate()
   */
  function _evaluatePredicate($absoluteXPath, $predicate, $nodeTest) {
    // If you are having difficulty using this function.  Then set this to TRUE and 
    // you'll get diagnostic info displayed to the output.
    $bDebugThisFunction = FALSE;
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction("_evaluatePredicate");
      echo "Node: [$absoluteXPath]\n";
      echo "Predicate: [$predicate]\n";
      echo "Node Test: [$nodeTest]\n";
      echo "<hr>";
    }
    
    do { // try-block
      // Numpty check
      if (!is_string($predicate)) {
        // Display an error message.
        $this->_displayError("While parsing an XPath expression ".
          "there was an error in the following predicate, ".
          "because it was not a string. It was a '".$predicate."'", __LINE__, __FILE__);
        $result = FALSE;
        break; // try-block
      }
      
      $predicate = trim($predicate);
      // Numpty check.  If they give us an empty string, then this is an error. ## N.S
      if ($predicate === '') { 
        // Display an error message.
        $this->_displayError("While parsing an XPath expression ". 
          "there was an error in the predicate " .
          "because it was the null string.  If you wish to seach ".
          "for the empty string, you must use ''.", __LINE__, __FILE__);
        $result = FALSE;
        break; // try-block
      }
      
      /////////////////////////////////////////////
      // Quick ways out.
      // If it is a literal string, then we return the literal string.  ## N.S. --sb
      $stringDelimiterMismatsh = 0;
      if (preg_match(':^"(.*)"$:', $predicate, $regs)) {
        $result = $regs[1];
        $stringDelimiterMismatsh = strpos(' ' . $result, '"');
        if ($bDebugThisFunction) echo "Predicate is literal: \"{$result}\"\n";        
      } elseif (preg_match(":^'(.*)'$:", $predicate, $regs)) {
        $result = $regs[1];
        $stringDelimiterMismatsh = strpos(' ' . $result, "'");
        if ($bDebugThisFunction) echo "Predicate is literal '{$result}'\n";        
      }
      if (isSet($result)) break; // try-block
      
      if ($stringDelimiterMismatsh > 0) {
        $this->_displayError("While parsing an XPath expression ".
              "there was an string delimiter miss match at pos [{$stringDelimiterMismatsh}] in the predicate string '{$predicate}'.", __LINE__, __FILE__);
        $result = FALSE;
        break; // try-block
      }
    
      // Check whether the predicate is just a digit.
      if (is_numeric($predicate)) {
        // Return the value of the digit.
        $result = doubleval($predicate);
        if ($bDebugThisFunction) echo "Predicate is double: '{$result}'\n";        
        break; // try-block
      }
      
      /////////////////////////////////////////////
      // Check for operators.
      // Set the default position and the type of the operator.
      $position = 0;
      $operator = '';
      
      // Run through all operators and try to find one.
      $opSize = sizeOf($this->operators);
      for ($i=0; $i<$opSize; $i++) {
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
        $left = $this->_evaluatePredicate($absoluteXPath, $left_predicate, $nodeTest);
        if ($bDebugThisFunction) echo "$left_predicate evals as: $left - ";
        // Only evaluate the right part if we need to.
        $right = FALSE;
        if (!$left and ($operator == ' and ')) {
          if ($bDebugThisFunction) echo "\nNo point in evaluating the right predicate: [$right_predicate]";
        } else {
          if ($bDebugThisFunction) echo "\nEvaluating RIGHT:[$right_predicate]";
          $right = $this->_evaluatePredicate($absoluteXPath, $right_predicate, $nodeTest);
          if ($bDebugThisFunction) echo "$right_predicate evals as: $right \n";
        }
        // Check the kind of operator.
        $b_result = FALSE;
        switch ($operator) {
          case ' or ': // Return the two results connected by an 'or'.
            $b_result = (bool)( $left or $right );
            break;
          case ' and ': // Return the two results connected by an 'and'.
            $b_result = (bool)( $left and $right );
            break;
          case '=': // Compare the two results.
            $b_result = (bool)( $left == $right ); 
            break;                    
          case '!=': // Check whether the two results are not equal.
            $b_result = (bool)( $left != $right );
            break;                    
          case '<=': // Compare the two results.
            $b_result = (bool)( $left <= $right );
            break;                    
          case '<': // Compare the two results.
            $b_result = (bool)( $left < $right );
            break;                
          case '>=': // Compare the two results.
            $b_result = (bool)( $left >= $right );
            break;                    
          case '>': // Compare the two results.
            $b_result = (bool)( $left > $right );
            break;                    
          case '+': // Return the result by adding one result to the other.
            $b_result = $left + $right;
            break;                    
          case '-': // Return the result by decrease one result by the other.
            $b_result = $left - $right;
            break;                    
          case '*': // Return a multiplication of the two results.
            $b_result =  $left * $right;
            break;                    
          case ' div ': // Return a division of the two results.
            if ($right == 0) {
              // Display an error message.
              $this->_displayError('While parsing an XPath '.
                'predicate, a error due a division by zero '.
                'occured.', __LINE__, __FILE__);
            } else {
              // Return the result of the division.
              $b_result = $left / $right;
            }
            break;
          case ' mod ': // Return a modulo of the two results.
            $b_result = $left % $right;
            break;                    
        }
        $result = $b_result;
      }
      if (isSet($result)) break; // try-block
    
      /////////////////////////////////////////////
      // Check for functions.
      // Check whether the predicate is a function.
      // do not catch the text() node, which looks like a function in its pattern
      if (preg_match(':\(:U', $predicate) && !preg_match(":text\(\)(\[\d*\])?$:",$predicate) ) {
        // Get the position of the first bracket.
        $start = strpos($predicate, '(');
        // If we search for the right bracket from the end of the string, we can support nested function calls.  
        // Fix by Andrei Zmievski
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
            str_replace($predicate,'<b>'.$predicate.'</b>', $this->currentXpathQuery) .
            '. After a closing bracket there was something unknown: "'. $after .'"', __LINE__, __FILE__);
        }
        
        // Check whether it's a function.
        if (empty($before) && empty($after)) {
          // Evaluate the content of the brackets.
          $result = $this->_evaluatePredicate($absoluteXPath, $between, $nodeTest);
        }
        elseif (in_array($before, $this->functions)) {
          // Return the evaluated function.
          $result = $this->_evaluateFunction($before, $between, $absoluteXPath, $nodeTest);
        } 
        else {
          // Display an error message.
          $this->_displayError('While parsing a predicate in an XPath expression, a function '.
            str_replace($before, '<b>'.$before.'</b>', $this->currentXpathQuery) . 
            ' was found, which is not yet supported by the parser.', __LINE__, __FILE__);
        }
      }
      if (isSet($result)) break; // try-block
      
      /////////////////////////////////////////////
      // Else it must just be an XPath expression.
      // Check whether it's an XPath expression.
      if ($bDebugThisFunction) echo "\nPredicate is XPath expression that is to be evaluated.";
      $tmpXpathSet = $this->_internalEvaluate($predicate, $absoluteXPath);
      if ($bDebugThisFunction) { echo "\nResult of XPath expression"; print_r($tmpXpathSet); }
      if (count($tmpXpathSet) > 0) {
        // Convert the array.
        $tmpXpathSet = explode("|", implode("|", $tmpXpathSet));
        // Get the value of the first result (which means we want to concat all the text...unless
        // a specific text() node has been given, and it will switch off to substringData
        $result = $this->wholeText($tmpXpathSet[0]);            
      }
     
    } while(FALSE);  // END try-block
    
    // Else no content so return the empty string.  ## N.S
    if (!isSet($result)) $result = '';
    
    if ($bDebugThisFunction) {
      echo "<pre>";
      var_dump($result);
      echo "</pre>";
      $this->_closeDebugFunction($aStartTime, $result);
    }
    // Return the array of nodes.
    return $result;
  }
  
  
  /**
   * Checks whether a node matches a node-test.
   *
   * This method checks whether a node in the document matches a given node-test.
   *
   * @param  $contextPath (string)  Full xpath of the node, which should be tested for 
   *                                matching the node-test.
   * @param  $nodeTest    (string)  String containing the node-test for the node.
   * @return              (boolean) This method returns TRUE if the node matches the 
   *                                node-test, otherwise FALSE.
   * @see    evaluate()
   */
  function _checkNodeTest($contextPath, $nodeTest) {
    if ($nodeTest == '*') {
      return TRUE; // Add this node to the node-set.
    }
    elseif (preg_match('/\(/U', $nodeTest)) { // Check whether it's a function.
      // Get the type of function to use.
      $function = $this->_prestr($nodeTest, '(');
      // Check whether the node fits the method.
      switch ($function) {
        case 'node':   // Add this node to the list of nodes.
          return TRUE;
        case 'text':   // Check whether the node has some text.
          $tmp = implode('', $this->nodeIndex[$contextPath]['textParts']);
          if (!empty($tmp)) {
            return TRUE; // Add this node to the list of nodes.
          }
          break;
/******** NOT supported (yet?)          
        case 'comment':  // Check whether the node has some comment.
          if (!empty($this->nodeIndex[$contextPath]['comment'])) {
            return TRUE; // Add this node to the list of nodes.
          }
          break;
        case 'processing-instruction':
          $literal = $this->_afterstr($axis['node-test'], '('); // Get the literal argument.
          $literal = substr($literal, 0, strlen($literal) - 1); // Cut the literal.
          
          // Check whether a literal was given.
          if (!empty($literal)) {
            // Check whether the node's processing instructions are matching the literals given.
            if ($this->nodeIndex[$context]['processing-instructions'] == $literal) {
              return TRUE; // Add this node to the node-set.
            }
          } else {
            // Check whether the node has processing instructions.
            if (!empty($this->nodeIndex[$contextPath]['processing-instructions'])) {
              return TRUE; // Add this node to the node-set.
            }
          }
          break;
***********/            
        default:  // Display an error message.
          $this->_displayError('While parsing an XPath expression there was an undefined function called "' .
             str_replace($function, '<b>'.$function.'</b>', $this->currentXpathQuery) .'"', __LINE__, __FILE__);
      }
    }
    elseif (preg_match('/^[a-zA-Z0-9\-_]+/', $nodeTest)) {
      // Check whether the node-test can be fulfilled.
      if (!strcmp($this->nodeIndex[$contextPath]['name'],$nodeTest)) {
        return TRUE; // Add this node to the node-set.
      }
    }
    else { // Display an error message.
      $this->_displayError("While parsing the XPath expression \"{$this->currentXpathQuery}\" ".
        "an empty and therefore invalid node-test has been found.", __LINE__, __FILE__);
    }
    
    return FALSE; // Don't add this context.
  }
  
  //-----------------------------------------------------------------------------------------
  // XPath                    ------  XPath AXIS Handlers  ------                            
  //-----------------------------------------------------------------------------------------
  
  /**
   * Retrieves axis information from an XPath expression step.
   *
   * This method tries to extract the name of the axis and its node-test
   * from a given step of an XPath expression at a given node.
   *
   * @param  $step     (string) String containing a step of an XPath expression.
   * @param  $nodePath (string) Full document path of the node on which the step is executed.
   * @return           (array)  Contains information about the axis found in the step.
   * @see    _evaluateStep()
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
        if (in_array($function, $this->functions)) {
          // Save the evaluated function.
          $axis['axis']      = 'function';
          $axis['node-test'] = $this->_evaluateFunction($function, $data, $nodePath);
        } 
        else {
          // Use the child axis and a function.
          $axis['axis']      = 'child';
          $axis['node-test'] = $step;
        }
        break $parseBlock;
      }
      
      // Check whether there are predicates and add the predicate to the list 
      // of predicates without []. Get contents of every [] found.
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
        if (!empty($this->parseOptions[XML_OPTION_CASE_FOLDING])) {
          // Case in-sensitive
          $axis['node-test'] = strtoupper($axis['node-test']);
        }
        break $parseBlock;
      }
      
      if ($step[0] == '@') {
        // Use the attribute axis and select the attribute.
        $axis['axis']      = 'attribute';
        $axis['node-test'] = substr($step, 1);
        if (!empty($this->parseOptions[XML_OPTION_CASE_FOLDING])) {
          // Case in-sensitive
          $axis['node-test'] = strtoupper($axis['node-test']);
        }
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
        if (!empty($this->parseOptions[XML_OPTION_CASE_FOLDING])) {
          // Case in-sensitive
          $axis['node-test'] = strtoupper($axis['node-test']);
        }
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
      if (!empty($this->parseOptions[XML_OPTION_CASE_FOLDING])) {
        // Case in-sensitive
        $axis['node-test'] = strtoupper($axis['node-test']);
      }
      
    } while(FALSE); // end parse block
    
    // Check whether it's a valid axis.
    if (!in_array($axis['axis'], array_merge($this->axes, array('function')))) {
      // Display an error message.
      $this->_displayError('While parsing an XPath expression, in the step ' .
        str_replace($step, '<b>'.$step.'</b>', $this->currentXpathQuery) .
        ' the invalid axis ' . $axis['axis'] . ' was found.', __LINE__, __FILE__, FALSE);
    }
    // Return the axis information.
    return $axis;
  }
   
  /**
   * Handles the XPath child axis.
   *
   * This method handles the XPath child axis.  It essentially filters out the
   * children to match the name specified after the '/'.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should 
   *                               be processed.
   * @return              (array)  A vector containing all nodes that were found, during 
   *                               the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_child($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set to hold the results of the child matches
    if ( $axis["node-test"] == "cdata" ) {
      if ( !isSet($this->nodeIndex[$contextPath]['textParts']) ) return '';
      $tSize = sizeOf($this->nodeIndex[$contextPath]['textParts']);
      for ($i=1; $i<=$tSize; $i++) { 
        $xPathSet[] = $contextPath . '/text()['.$i.']';
      }
    }
    else {
      // Get a list of all children.
      $allChildren = $this->nodeIndex[$contextPath]['childNodes'];
      
      // Run through all children in the order they where set.
      $cSize = sizeOf($allChildren);
      for ($i=0; $i<$cSize; $i++) {
        $childPath = $contextPath .'/'. $allChildren[$i]['name'] .'['. $allChildren[$i]['contextPos']  .']';
        if ($this->_checkNodeTest($childPath, $axis['node-test'])) { // node test check
          $xPathSet[] = $childPath; // Add the child to the node-set.
        }
      }
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath parent axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the 
   *                               evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_parent($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Check whether the parent matches the node-test.
    $parentPath = $this->getParentXPath($contextPath);
    if ($this->_checkNodeTest($parentPath, $axis['node-test'])) {
      $xPathSet[] = $parentPath; // Add this node to the list of nodes.
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath attribute axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_attribute($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Check whether all nodes should be selected.
    $nodeAttr = $this->nodeIndex[$contextPath]['attributes'];
    if ($axis['node-test'] == '*') {
      foreach($nodeAttr as $key=>$dummy) { // Run through the attributes.
        $xPathSet[] = $contextPath.'/attribute::'.$key; // Add this node to the node-set.
      }
    }
    elseif (!empty($nodeAttr[$axis['node-test']])) {
      $xPathSet[] = $contextPath . '/attribute::'. $axis['node-test']; // Add this node to the node-set.
    }
    return $xPathSet; // Return the nodeset.
  }
   
  /**
   * Handles the XPath self axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_self($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Check whether the context match the node-test.
    if ($this->_checkNodeTest($contextPath, $axis['node-test'])) {
      $xPathSet[] = $contextPath; // Add this node to the node-set.
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath descendant axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_descendant($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Get a list of all children.
    $allChildren = $this->nodeIndex[$contextPath]['childNodes'];
    
    // Run through all children in the order they where set.
    $cSize = sizeOf($allChildren);
    for ($i=0; $i<$cSize; $i++) {
      $childPath = $allChildren[$i]['xpath'];
      // Check whether the child matches the node-test.
      if ($this->_checkNodeTest($childPath, $axis['node-test'])) {
        $xPathSet[] = $childPath; // Add the child to the list of nodes.
      }
      // Recurse to the next level.
      $xPathSet = array_merge($xPathSet, $this->_handleAxis_descendant($axis, $childPath));
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath ancestor axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_ancestor($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
        
    $parentPath = $this->getParentXPath($contextPath); // Get the parent of the current node.
    
    // Check whether the parent isn't super-root.
    if (!empty($parentPath)) {
      // Check whether the parent matches the node-test.
      if ($this->_checkNodeTest($parentPath, $axis['node-test'])) {
        $xPathSet[] = $parentPath; // Add the parent to the list of nodes.
      }
      // Handle all other ancestors.
      $xPathSet = array_merge($xPathSet, $this->_handleAxis_ancestor($axis, $parentPath));
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath namespace axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_namespace($axis, $contextPath) {
    $this->_displayError("The axis 'namespace is not suported'", __LINE__, __FILE__, FALSE);
  }
  
  /**
   * Handles the XPath following axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_following($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    do { // try-block
      $node = $this->nodeIndex[$contextPath]; // Get the current node
      $position = $node['pos'];               // Get the current tree position.
      $parent = $node['parentNode'];
      // Check if there is a following sibling at all; if not end.
      if ($position >= sizeOf($parent['childNodes'])) break; // try-block
      // Build the starting abs. XPath
      $startXPath = $parent['childNodes'][$position+1]['xpath'];
      // Run through all nodes of the document.
      $nodeKeys = array_keys($this->nodeIndex);
      $nodeSize = sizeOf($nodeKeys);
      for ($k=0; $k<$nodeSize; $k++) {
        if ($nodeKeys[$k] == $startXPath) break; // Check whether this is the starting abs. XPath
      }
      for (; $k<$nodeSize; $k++) {
        // Check whether the node fits the node-test.
        if ($this->_checkNodeTest($nodeKeys[$k], $axis['node-test'])) {
          $xPathSet[] = $nodeKeys[$k]; // Add the node to the list of nodes.
        }
      }
    } while(FALSE);
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath preceding axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_preceding($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Run through all nodes of the document.
    foreach ($this->nodeIndex as $xPath=>$dummy) {
      if (empty($xPath)) continue; // skip super-Root
      
      // Check whether this is the context node.
      if ($xPath == $contextPath) {
        break; // After this we won't look for more nodes.
      }
      if (!strncmp($xPath, $contextPath, strLen($xPath))) {
        continue;
      }
      // Check whether the node fits the node-test.
      if ($this->_checkNodeTest($xPath, $axis['node-test'])) {
        $xPathSet[] = $xPath; // Add the node to the list of nodes.
      }
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath following-sibling axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_following_sibling($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Get all children from the parent.
    $siblings = $this->_handleAxis_child($axis, $this->getParentXPath($contextPath));
    // Create a flag whether the context node was already found.
    $found = FALSE;
    
    // Run through all siblings.
    $size = sizeOf($siblings);
    for ($i=0; $i<$size; $i++) {
      $sibling = $siblings[$i];
      
      // Check whether the context node was already found.
      if ($found) {
        // Check whether the sibling matches the node-test.
        if ($this->_checkNodeTest($sibling, $axis['node-test'])) {
          $xPathSet[] = $sibling; // Add the sibling to the list of nodes.
        }
      }
      // Check if we reached *this* context node.
      if ($sibling == $contextPath) {
        $found = TRUE; // Continue looking for other siblings.
      }
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath preceding-sibling axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_preceding_sibling($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Get all children from the parent.
    $siblings = $this->_handleAxis_child($axis, $this->getParentXPath($contextPath));
    
    // Run through all siblings.
    $size = sizeOf($siblings);
    for ($i=0; $i<$size; $i++) {
      $sibling = $siblings[$i];
      // Check whether this is the context node.
      if ($sibling == $contextPath) {
        break; // Don't continue looking for other siblings.
      }
      // Check whether the sibling matches the node-test.
      if ($this->_checkNodeTest($sibling, $axis['node-test'])) {
        $xPathSet[] = $sibling; // Add the sibling to the list of nodes.
      }
    }
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath descendant-or-self axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_descendant_or_self($axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Read the nodes.
    $xPathSet = array_merge(
                 $this->_handleAxis_self($axis, $contextPath),
                 $this->_handleAxis_descendant($axis, $contextPath)
               );
    return $xPathSet; // Return the nodeset.
  }
  
  /**
   * Handles the XPath ancestor-or-self axis.
   *
   * This method handles the XPath ancestor-or-self axis.
   *
   * @param  $axis        (array)  Array containing information about the axis.
   * @param  $contextPath (string) xpath to starting node from which the axis should be processed.
   * @return              (array)  A vector containing all nodes that were found, during the evaluation of the axis.
   * @see    evaluate()
   */
  function _handleAxis_ancestor_or_self ( $axis, $contextPath) {
    $xPathSet = array(); // Create an empty node-set.
    
    // Read the nodes.
    $xPathSet = array_merge(
                 $this->_handleAxis_self($axis, $contextPath),
                 $this->_handleAxis_ancestor($axis, $contextPath)
               );
    return $xPathSet; // Return the nodeset.
  }
  
  
  //-----------------------------------------------------------------------------------------
  // XPath                  ------  XPath FUNCTION Handlers  ------                          
  //-----------------------------------------------------------------------------------------
  
   /**
    * Handles the XPath function last.
    *
    * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
    * @param  $arguments     (string) String containing the arguments that were passed to the function.
    * @return                (mixed)  Depending on the type of function being processed
    * @see    evaluate()
    */
  function _handleFunction_last($absoluteXPath, $arguments, $nodeTest) {
    // Calculate the size of the context.
    $parentNode = $this->nodeIndex[$absoluteXPath]['parentNode'];
    if ($nodeTest == "*") {
      $contextPos = sizeOf($parentNode['childNodes']);
    }
    elseif ($nodeTest == "cdata") {
      $absoluteXPath = substr($absoluteXPath,0,strrpos($absoluteXPath,"/text()"));
      $contextPos = sizeOf($this->nodeIndex[$absoluteXPath]['textParts']);
    }
    else {
      $contextPos = 0;
      $name = $this->nodeIndex[$absoluteXPath]['name'];
      foreach($parentNode['childNodes'] as $childNode) {
        $contextPos += ($childNode['name'] === $name) ? 1 : 0;
      }
    }
    return $contextPos; // Return the size.
  }
  
  /**
   * Handles the XPath function position.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_position($absoluteXPath, $arguments, $nodeTest) {
    // return the context-position.
    if ($nodeTest == "*") { // if we are matching all children, then we need to find the position regardless of name
      // 'pos' is zero-based, not one based.
      $contextPos = $this->nodeIndex[$absoluteXPath]['pos']+1;
    }
    elseif ($nodeTest == "cdata") {  // if we are looking for text nodes, we go about it a bit differently
      $contextPos = substr($absoluteXPath,strrpos($absoluteXPath,"[")+1,-1);
    }
    else {
      $contextPos = $this->nodeIndex[$absoluteXPath]['contextPos'];
    }
    return $contextPos;
  }
  
  /**
   * Handles the XPath function count.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_count($absoluteXPath, $arguments, $nodeTest) {
    // Evaluate the argument of the method as an XPath and return the number of results.
    return count($this->_internalEvaluate($arguments, $absoluteXPath));
  }
  
  /**
   * Handles the XPath function id.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_id($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments);         // Trim the arguments.
    $arguments = explode(' ', $arguments); // Now split the arguments into an array.
    // Create a list of nodes.
    $resultXPaths = array();
    // Run through all nodes of the document.
    $keys = array_keys($this->nodeIndex);
    $kSize = $sizeOf($keys);
    for ($i=0; $i<$kSize; $i++) {
      if (empty($keys[$i])) continue; // skip super-Root
      if (in_array($this->nodeIndex[$keys[$i]]['attributes']['id'], $arguments)) {
        $resultXPaths[] = $absoluteXPath; // Add this node to the list of nodes.
      }
    }
    return $resultXPaths; // Return the list of nodes.
  }
  
  /**
   * Handles the XPath function name.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_name($absoluteXPath, $arguments, $nodeTest) {
    return $this->nodeIndex[$absoluteXPath]['name']; // Return the name of the node.
  }
  
  /**
   * Handles the XPath function string.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_string($absoluteXPath, $arguments, $nodeTest) {
    // Check what type of parameter is given
    if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $arguments) OR preg_match('/^\.[0-9]+$/', $arguments)) {
      $number = doubleval($arguments); // Convert the digits to a number.
      return strval($number); // Return the number.
    }
    elseif (is_bool($arguments)) { // Check whether it's TRUE or FALSE and return as string.
      if ($arguments === TRUE)  return 'TRUE'; else return 'FALSE';
    }
    elseif (!empty($arguments)) {
      // Use the argument as an XPath.
      $result = $this->_internalEvaluate($arguments, $absoluteXPath);
      $result = explode('|', implode('|', $result)); // Get the first argument.
      return $result[0];          // Return the first result as a string.
    }
    elseif (empty($arguments)) {
      return $absoluteXPath;      // Return the current node.
    }
    else {
      return '';  // Return an empty string.
    }
  }
  
  /**
   * Handles the XPath function concat.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_concat($absoluteXPath, $arguments, $nodeTest) {
    // Split the arguments.
    $arguments = explode(',', $arguments);
    // Run through each argument and evaluate it.
    $size = sizeof($arguments);
    for ($i=0; $i<$size; $i++) {
      $arguments[$i] = trim($arguments[$i]);  // Trim each argument.
      // Evaluate it.
      $arguments[$i] = $this->_evaluatePredicate($absoluteXPath, $arguments[$i], $nodeTest);
    }
    $arguments = implode('', $arguments);  // Put the string together and return it.
    return $arguments;
  }
  
  /**
   * Handles the XPath function starts-with.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_starts_with ($absoluteXPath, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($absoluteXPath, $first, $nodeTest);
    $second = $this->_evaluatePredicate($absoluteXPath, $second, $nodeTest);
    // Check whether the first string starts with the second one.
    return  (bool) ereg('^'.$second, $first);
  }
  
  /**
   * Handles the XPath function contains.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_contains($absoluteXPath, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
    //echo "Predicate: $arguments First: ".$first." Second: ".$second."\n";
    // Evaluate each argument.
    $first = $this->_evaluatePredicate($absoluteXPath, $first, $nodeTest);
    $second = $this->_evaluatePredicate($absoluteXPath, $second, $nodeTest);
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
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_substring_before($absoluteXPath, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($absoluteXPath, $first, $nodeTest);
    $second = $this->_evaluatePredicate($absoluteXPath, $second, $nodeTest);
    // Return the substring.
    return $this->_prestr(strval($first), strval($second));
  }
  
  /**
   * Handles the XPath function substring-after.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_substring_after($absoluteXPath, $arguments, $nodeTest) {
    // Get the arguments.
    $first  = trim($this->_prestr($arguments, ','));
    $second = trim($this->_afterstr($arguments, ','));
    // Evaluate each argument.
    $first  = $this->_evaluatePredicate($absoluteXPath, $first, $nodeTest);
    $second = $this->_evaluatePredicate($absoluteXPath, $second, $nodeTest);
    // Return the substring.
    return $this->_afterstr(strval($first), strval($second));
  }
  
  /**
   * Handles the XPath function substring.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_substring($absoluteXPath, $arguments, $nodeTest) {
    // Split the arguments.
    $arguments = explode(",", $arguments);
    $size = sizeOf($arguments);
    for ($i=0; $i<$size; $i++) { // Run through all arguments.
      $arguments[$i] = trim($arguments[$i]); // Trim the string.
      // Evaluate each argument.
      $arguments[$i] = $this->_evaluatePredicate($absoluteXPath, $arguments[$i], $nodeTest);
    }
    // Check whether a third argument was given and return the substring..
    if (!empty($arguments[2])) {
      return substr(strval($arguments[0]), $arguments[1] - 1, $arguments[2]);
    } else {
      return substr(strval($arguments[0]), $arguments[1] - 1);
    }
  }
  
  /**
   * Handles the XPath function string-length.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_string_length($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments); // Trim the argument.
    // Evaluate the argument.
    $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    return strlen(strval($arguments)); // Return the length of the string.
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
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                 (stri)g trimed string
   * @see    evaluate()
   */
  function _handleFunction_normalize_space($absoluteXPath, $arguments, $nodeTest) {
    if (empty($arguments)) {
      $arguments = $this->getParentXPath($absoluteXPath).'/'.$this->nodeIndex[$absoluteXPath]['name'].'['.$this->nodeIndex[$absoluteXPath]['contextPos'].']';
    } else {
       $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    }
    $arguments = trim(preg_replace (";[[:space:]]+;s",' ',$arguments));
    return $arguments;
  }

  /**
   * Handles the XPath function translate.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_translate($absoluteXPath, $arguments, $nodeTest) {
    $arguments = explode(',', $arguments); // Split the arguments.
    $size = sizeOf($arguments);
    for ($i=0; $i<$size; $i++) { // Run through all arguments.
      $arguments[$i] = trim($arguments[$i]); // Trim the argument.
      // Evaluate the argument.
      $arguments[$i] = $this->_evaluatePredicate($absoluteXPath, $arguments[$i], $nodeTest);
    }
    return strtr($arguments[0], $arguments[1], $arguments[2]);  // Return the translated string.
  }

  /**
   * Handles the XPath function boolean.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_boolean($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments); // Trim the arguments.
    // Check what type of parameter is given
    if (preg_match('/^[0-9]+(\.[0-9]+)?$/', $arguments) || preg_match('/^\.[0-9]+$/', $arguments)) {
      $number = doubleval($arguments);  // Convert the digits to a number.
      // If number zero return FALSE else TRUE.
      if ($number == 0) return FALSE; else return TRUE;
    }
    elseif (empty($arguments)) {
      return FALSE; // Sorry, there were no arguments.
    }
    else {
      // Try to evaluate the argument as an XPath.
      $result = $this->_internalEvaluate($arguments, $absoluteXPath);
      // If we found something return TRUE else FALSE.
      if (count($result) > 0) return FALSE; else return TRUE;
    }
  }
  
  /**
   * Handles the XPath function not.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_not($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments); // Trim the arguments.
    // Return the negative value of the content of the brackets.
    return !$this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
  }
  
  /**
   * Handles the XPath function TRUE.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_true($absoluteXPath, $arguments, $nodeTest) {
    return TRUE; // Return TRUE.
  }
  
  /**
   * Handles the XPath function FALSE.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_false($absoluteXPath, $arguments, $nodeTest) {
    return FALSE; // Return FALSE.
  }
  
  /**
   * Handles the XPath function lang.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_lang($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments); // Trim the arguments.
    $currentNode = $this->nodeIndex[$absoluteXPath];
    while (!empty($currentNode['name'])) { // Run through the ancestors.
      // Check whether the node has an language attribute.
      if (isSet($currentNode['attributes']['xml:lang'])) {
        // Check whether it's the language, the user asks for; if so return TRUE else FALSE
        return eregi('^'.$arguments, $currentNode['attributes']['xml:lang']);
      }
      $currentNode = $currentNode['parentNode']; // Move up to parent
    } // End while
    return FALSE;
  }
  
  /**
   * Handles the XPath function number.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_number($absoluteXPath, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    }
    // Check the type of argument.
    if (is_numeric($arguments)) {
      return doubleval($arguments); // Return the argument as a number.
    }
    elseif (is_bool($arguments)) {  // Return TRUE/FALSE as a number.
      if ($arguments === TRUE) return 1; else return 0;  
    }
  }

  /**
   * Handles the XPath function sum.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_sum($absoluteXPath, $arguments, $nodeTest) {
    $arguments = trim($arguments); // Trim the arguments.
    // Evaluate the arguments as an XPath expression.
    $result = $this->_internalEvaluate($arguments, $absoluteXPath);
    $sum = 0; // Create a variable to save the sum.
    // Run through all results.
    $size = sizeOf($result);
    for ($i=0; $i<$size; $i++) {
      $value = $this->substringData($result[$i]); // Get the value of the node.
      $sum += doubleval($value); // Add it to the sum.
    }
    return $sum; // Return the sum.
  }

  /**
   * Handles the XPath function floor.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_floor($absoluteXPath, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    }
    $arguments = doubleval($arguments); // Convert the arguments to a number.
    return floor($arguments);           // Return the result
  }
  
  /**
   * Handles the XPath function ceiling.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_ceiling($absoluteXPath, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    }
    $arguments = doubleval($arguments); // Convert the arguments to a number.
    return ceil($arguments);            // Return the result
  }
  
  /**
   * Handles the XPath function round.
   *
   * @param  $absoluteXPath (string) Full xpath of the node on which the function should be processed.
   * @param  $arguments     (string) String containing the arguments that were passed to the function.
   * @return                (mixed)  Depending on the type of function being processed
   * @see    evaluate()
   */
  function _handleFunction_round($absoluteXPath, $arguments, $nodeTest) {
    if (!is_numeric($arguments)) {
      $arguments = $this->_evaluatePredicate($absoluteXPath, $arguments, $nodeTest);
    }
    $arguments = doubleval($arguments); // Convert the arguments to a number.
    return round($arguments);           // Return the result
  }
  
  //-----------------------------------------------------------------------------------------
  // XPathEngine                ------  Help Stuff  ------                                   
  //-----------------------------------------------------------------------------------------
  
  /**
   * Compare to nodes if they are equal
   *
   * 2 nodes are considered equal if the abs. xpath is equal.
   * 
   * @param  $node1 (mixed) Either a xpath string to an node OR a real tree-node (hash-array)
   * @param  $node2 (mixed) Either a xpath string to an node OR a real tree-node (hash-array)
   * @return        (bool)  TRUE if equal (see text above), FALSE if not (and on error).
   */
  function equalNodes($node1, $node2) {
    $xPath_1 = is_string($node1) ? $node1 : $this->getNodePath($node1);
    $xPath_2 = is_string($node2) ? $node2 : $this->getNodePath($node2);
    return (strncasecmp ($xPath_1, $xPath_2, strLen($xPath_1)) == 0);
  }
  
  /**
   * Get the Xpath string of a node that is in a document tree.
   *
   * @param $node (array)  A real tree-node (hash-array)   
   * @return      (string) The string path to the node or FALSE on error.
   */
  function getNodePath($node) {
    if (!empty($node['xpath'])) return $node['xpath'];
    $pathInfo = array();
    do {
      if (empty($node['name']) OR empty($node['parentNode'])) break; // End criteria
      $pathInfo[] = array('name' => $node['name'], 'contextPos' => $node['contextPos']);
      $node = $node['parentNode'];
    } while (TRUE);
    
    $xPath = '';
    for ($i=sizeOf($pathInfo)-1; $i>=0; $i--) {
      $xPath .= '/' . $pathInfo[$i]['name'] . '[' . $pathInfo[$i]['contextPos'] . ']';
    }
    if (empty($xPath)) return FALSE;
    return $xPath;
  }
  
  /**
   * Retrieves the absolute parent XPath expression.
   *
   * The parents stored in the tree are only relative parents...but all the parent
   * information is stored in the xPath expression itself...so instead we use a function
   * to extract the parent from the absolute xpath expression
   *
   * @param  $childPath (string) String containing an absolute XPath expression
   * @return            (string) returns the absolute XPath of the parent
   */
   function getParentXPath($absoluteXPath) {
     $lastSlashPos = strrpos($absoluteXPath, '/'); 
     if ($lastSlashPos == 0) { // it's already the root path
       return ''; // 'super-root'
     } else {
       return (substr($absoluteXPath, 0, $lastSlashPos));
     }
   }
  
  /**
   * Returns TRUE if the given node has child nodes below it
   *
   * @param  $absoluteXPath (string) full path of the potential parent node
   * @return                (bool)   TRUE if this node exists and has a child, FALSE otherwise
   */
  function hasChildNodes($absoluteXPath) {
    return (bool) (isSet($this->nodeIndex[$absoluteXPath]) 
                   AND sizeOf($this->nodeIndex[$absoluteXPath]['childNodes']));
  }
  
  /**
   * Translate all ampersands to it's literal entities '&amp;' and back.
   *
   * I wasn't aware of this problem at first but it's important to understand why we do this.
   * At first you must know:
   * a) PHP's XML parser *translates* all entities to the equivalent char E.g. &lt; is returned as '<'
   * b) PHP's XML parser (in V 4.1.0) has problems with most *literal* entities! The only one's that are 
   *    recognized are &amp;, &lt; &gt; and &quot;. *ALL* others (like &nbsp; &copy; a.s.o.) cause an 
   *    XML_ERROR_UNDEFINED_ENTITY error. I reported this as bug at http://bugs.php.net/bug.php?id=15092
   *    (It turned out not to be a 'real' bug, but one of those nice W3C-spec things).
   * 
   * Forget position b) now. It's just for info. Because the way we will solve a) will also solve b) too. 
   *
   * THE PROBLEM
   * To understand the problem, here a sample:
   * Given is the following XML:    "<AAA> &lt; &nbsp; &gt; </AAA>"
   *   Try to parse it and PHP's XML parser will fail with a XML_ERROR_UNDEFINED_ENTITY becaus of 
   *   the unknown litteral-entity '&nbsp;'. (The numeric equivalent '&#160;' would work though). 
   * Next try is to use the numeric equivalent 160 for '&nbsp;', thus  "<AAA> &lt; &#160; &gt; </AAA>"
   *   The data we receive in the tag <AAA> is  " <   > ". So we get the *translated entities* and 
   *   NOT the 3 entities &lt; &#160; &gt. Thus, we will not even notice that there were entities at all!
   *   In *most* cases we're not able to tell if the data was given as entity or as 'normal' char.
   *   E.g. When receiving a quote or a single space were not able to tell if it was given as 'normal' char
   *   or as &nbsp; or &quot;. Thus we loose the entity-information of the XML-data!
   * 
   * THE SOLUTION
   * The better solution is to keep the data 'as is' by replacing the '&' before parsing begins.
   * E.g. Taking the original input from above, this would result in "<AAA> &amp;lt; &amp;nbsp; &amp;gt; </AAA>"
   * The data we receive now for the tag <AAA> is  " &lt; &nbsp; &gt; ". and that's what we want.
   * 
   * The bad thing is, that a global replace will also replace data in section that are NOT translated by the 
   * PHP XML-parser. That is comments (<!-- -->), IP-sections (stuff between <? ? >) and CDATA-block too.
   * So all data comming from those sections must be reversed. This is done during the XML parse phase.
   * So:
   * a) Replacement of all '&' in the XML-source.
   * b) All data that is not char-data or in CDATA-block have to be reversed during the XML-parse phase.
   *
   * @param  $xmlSource (string) The XML string
   * @return            (string) The XML string with translated ampersands.
   */
  function _translateAmpersand($xmlSource, $reverse=FALSE) {
    return ($reverse ? str_replace('&amp;', '&', $xmlSource) : str_replace('&', '&amp;', $xmlSource));
  }

} // END OF CLASS XPathEngine


/************************************************************************************************
* ===============================================================================================
*                                     X P a t h  -  Class                                        
* ===============================================================================================
************************************************************************************************/

define('XPATH_QUERYHIT_ALL'   , 1);
define('XPATH_QUERYHIT_FIRST' , 2);
define('XPATH_QUERYHIT_UNIQUE', 3);

class XPath extends XPathEngine {
    
  /**
   * Constructor of the class
   *
   * Optionally you may call this constructor with the XML-filename to parse and the 
   * XML option vector. A option vector sample: 
   *   $xmlOpt = array(XML_OPTION_CASE_FOLDING => FALSE, XML_OPTION_SKIP_WHITE => TRUE);
   *
   * @param  $userXmlOptions (array)  (optional) Vector of (<optionID>=><value>, <optionID>=><value>, ...)
   * @param  $fileName       (string) (optional) Filename of XML file to load from.
   *                                  It is recommended that you call importFromFile()
   *                                  instead as you will get an error code.  If the
   *                                  import fails, the object will be set to FALSE.
   * @see    parent::XPathEngine()
   */
  function XPath($fileName='', $userXmlOptions=array()) {
    parent::XPathEngine($userXmlOptions);
    $this->properties['modMatch'] = XPATH_QUERYHIT_ALL;
/*    if ($fileName) {
      if (!$this->importFromFile($fileName)) {
        $this = FALSE;
      } */
    }
  }
  
  /**
   * Resets the object so it's able to take a new xml sting/file
   *
   * Constructing objects is slow.  If you can, reuse ones that you have used already
   * by using this reset() function.
   */
  function reset() {
    parent::reset();
    $this->xpath   = '';
    $this->properties['modMatch'] = XPATH_QUERYHIT_ALL;
  }
  
  //-----------------------------------------------------------------------------------------
  // XPath                    ------  Get / Set Stuff  ------                                
  //-----------------------------------------------------------------------------------------
  
  /**
   * Resolves and xPathQuery array depending on the property['modMatch']
   *
   * Most of the modification functions of XPath will also accept a xPathQuery (instead 
   * of an absolute Xpath). The only problem is that the query could match more the one 
   * node. The question is, if the none, the fist or all nodes are to be modified.
   * The behaver can be set with setModMatch()  
   *
   * @param $modMatch (int) One of the following:
   *                        - XPATH_QUERYHIT_ALL (default) 
   *                        - XPATH_QUERYHIT_FIRST
   *                        - XPATH_QUERYHIT_UNIQUE // If the query matches more then one node. 
   * @see  _resolveXPathQuery()
   */
  function setModMatch($modMatch = XPATH_QUERYHIT_ALL) {
    switch($modMatch) {
      case XPATH_QUERYHIT_UNIQUE : $this->properties['modMatch'] =  XPATH_QUERYHIT_UNIQUE; break;
      case XPATH_QUERYHIT_FIRST: $this->properties['modMatch'] =  XPATH_QUERYHIT_FIRST; break;
      default: $this->properties['modMatch'] = XPATH_QUERYHIT_ALL;
    }
  }
  
  //-----------------------------------------------------------------------------------------
  // XPath                    ------  DOM Like Modification  ------                          
  //-----------------------------------------------------------------------------------------
  
  //-----------------------------------------------------------------------------------------
  // XPath                  ------  Child (Node)  Set/Get  ------                           
  //-----------------------------------------------------------------------------------------
  
  /**
   * Retrieves the name(s) of a node or a group of document nodes.
   *          
   * This method retrieves the names of a group of document nodes
   * specified in the argument.  So if the argument was '/A[1]/B[2]' then it
   * would return 'B' if the node did exist in the tree.
   *          
   * @param  $xPathQuery (mixed) Array or single full document path(s) of the node(s), 
   *                             from which the names should be retrieved.
   * @return             (mixed) Array or single string of the names of the specified 
   *                             nodes, or just the individual name.  If the node did 
   *                             not exist, then returns FALSE.
   */
  function nodeName($xPathQuery) {
    // Check for a valid xPathQuery
    $xPathSet = $this->_resolveXPathQuery($xPathQuery,'nodeName');
    if (count($xPathSet) == 0) return FALSE;
    // For each node, get it's name
    $result = array();
    foreach($xPathSet as $xPath) {
      $node = &$this->getNode($xPath);
      if (!$node) {
        // ### Fatal internal error?? 
        continue;
      }
      $result[] = $node['name'];
    }
    // If just a single string, return string
    if (count($xPathSet) == 1) $result = $result[0];
    // Return result.
    return $result;
  }
  
  /**
   * Removes a node from the XML document.
   *
   * This method removes a node from the tree of nodes of the XML document. If the node 
   * is a document node, all children of the node and its character data will be removed. 
   * If the node is an attribute node, only this attribute will be removed, the node to which 
   * the attribute belongs as well as its children will remain unmodified.
   *
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery  (string) xpath to the node (See note above).
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                               the changes.  A performance helper.  See reindexNodeTree()
   * @return              (bool)   TRUE on success, FALSE on error;
   * @see    setModMatch(), reindexNodeTree()
   */
  function removeChild($xPathQuery, $autoReindex=TRUE) {
    $NULL = NULL;
    $bDebugThisFunction = FALSE;  // Get diagnostic output for this function
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction('removeChild');
      echo "Node: $xPathQuery\n";
      echo '<hr>';
    }
    $status = FALSE;
    do { // try-block
      // Check for a valid xPathQuery
      $xPathSet = $this->_resolveXPathQuery($xPathQuery,'removeChild');
      if (sizeOf($xPathSet) === 0) {
        $this->_displayError(sprintf($this->errorStrings['NoNodeMatch'], $xPathQuery), __LINE__, __FILE__, FALSE);
        break; // try-block
      }
      $mustReindex = FALSE;
      // Make chages from 'bottom-up'. In this manner the modifications will not affect itself.
      for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
        $absoluteXPath = $xPathSet[$i];
        if (preg_match(';/attribute::;', $absoluteXPath)) { // Handle the case of an attribute node
          $xPath = $this->_prestr($absoluteXPath, '/attribute::');       // Get the path to the attribute node's parent.
          $attribute = $this->_afterstr($absoluteXPath, '/attribute::'); // Get the name of the attribute.
          unSet($this->nodeIndex[$xPath]['attributes'][$attribute]);     // Unset the attribute
          if ($bDebugThisFunction) echo "We removed the attribute '$attribute' of node '$xPath'.\n";
          continue;
        }
        // Otherwise remove the node by setting it to NULL. It will be removed on the next reindexNodeTree() call.
        $mustReindex = $autoReindex;
        $theNode = $this->nodeIndex[$absoluteXPath];
        $theNode['parentNode']['childNodes'][$theNode['pos']] =& $NULL;
        if ($bDebugThisFunction) echo "We removed the node '$absoluteXPath'.\n";
      }
      // Reindex the node tree again
      if ($mustReindex) $this->reindexNodeTree();
      $status = TRUE;
    } while(FALSE);
    
    if ($bDebugThisFunction) $this->_closeDebugFunction($aStartTime, $status);
    return $status;
  }
  
  /**
   * Replace a node with any data string. The $data is taken 1:1.
   *
   * This function will delete the node you define by $absoluteXPath (plus it's sub-nodes) and 
   * substitute it by the string $text. Often used to push in not well formed HTML.
   * WARNING: 
   *   The $data is taken 1:1. 
   *   You are in charge that the data you enter is valid XML if you intend
   *   to export and import the content again.
   *
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery  (string) xpath to the node (See note above).
   * @param  $data        (string) String containing the content to be set. *READONLY*
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                               the changes.  A performance helper.  See reindexNodeTree()
   * @return              (bool)   TRUE on success, FALSE on error;
   * @see    setModMatch(), replaceChild(), reindexNodeTree()
   */
  function replaceChildByData($xPathQuery, $data, $autoReindex=TRUE) {
    $NULL = NULL;
    $bDebugThisFunction = FALSE;  // Get diagnostic output for this function
    if ($bDebugThisFunction) {
      $aStartTime = $this->_beginDebugFunction('replaceChildByData');
      echo "Node: $xPathQuery\n";
    }
    $status = FALSE;
    do { // try-block
      // Check for a valid xPathQuery
      $xPathSet = $this->_resolveXPathQuery($xPathQuery,'replaceChildByData');
      if (sizeOf($xPathSet) === 0) {
        $this->_displayError(sprintf($this->errorStrings['NoNodeMatch'], $xPathQuery), __LINE__, __FILE__, FALSE);
        break; // try-block
      }
      $mustReindex = FALSE;
      // Make chages from 'bottom-up'. In this manner the modifications will not affect itself.
      for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
        $absoluteXPath = $xPathSet[$i];
        $mustReindex = $autoReindex;
        $theNode = $this->nodeIndex[$absoluteXPath];
        $pos = $theNode['pos'];
        $theNode['parentNode']['textParts'][$pos] .= $data;
        $theNode['parentNode']['childNodes'][$pos] =& $NULL;
        if ($bDebugThisFunction) echo "We replaced the node '$absoluteXPath' with data.\n";
      }
      // Reindex the node tree again
      if ($mustReindex) $this->reindexNodeTree();
      $status = TRUE;
    } while(FALSE);
    
    if ($bDebugThisFunction) $this->_closeDebugFunction($aStartTime, ($status) ? 'Success' : '!!! FAILD !!!');
    return $status;
  }
  
  /**
   * Replace the node(s) that matches the xQuery with the passed node (or passed node-tree)
   * 
   * If the passed node is a string it's assumed to be XML and replaceChildByXml() 
   * will be called.
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery  (string) Xpath to the node being replaced.
   * @param  $node        (array)  A doc node.
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                               the changes.  A performance helper.  See reindexNodeTree()
   * @return              (array)  The last replaced $node (can be a whole sub-tree)
   * @see    reindexNodeTree()
   */
  function &replaceChild($xPathQuery, $node, $autoReindex=TRUE) {
    $NULL = NULL;
    if (is_string($node)) {
      if (!($node = $this->_xml2Document($node))) return FALSE;
    }
    // Special case if it's 'super root'. We then have to take the child node == top node
    if (empty($node['name'])) $node = $node['childNodes'][0];
    
    $status = FALSE;
    do { // try-block
      // Check for a valid xPathQuery
      $xPathSet = $this->_resolveXPathQuery($xPathQuery,'replaceChild');
      if (sizeOf($xPathSet) === 0) {
        $this->_displayError(sprintf($this->errorStrings['NoNodeMatch'], $xPathQuery), __LINE__, __FILE__, FALSE);
        break; // try-block
      }
      $mustReindex = FALSE;
      // Make chages from 'bottom-up'. In this manner the modifications will not affect itself.
      for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
        $absoluteXPath = $xPathSet[$i];
        $mustReindex = $autoReindex;
        $childNode =& $this->nodeIndex[$absoluteXPath];
        $parentNode =& $childNode['parentNode'];
        $childNode['parentNode'] =& $NULL;
        $childPos = $childNode['pos'];
        $parentNode['childNodes'][$childPos] =& $this->cloneNode($node);
      }
      if ($mustReindex) $this->reindexNodeTree();
      $status = TRUE;
    } while(FALSE);
    
    if (!$status) return FALSE;
    return $childNode;
  }
  
  /**
   * Insert passed node (or passed node-tree) at the node(s) that matches the xQuery.
   *
   * With parameters you can define if the 'hit'-node is shifted to the right or left 
   * and if it's placed before of after the text-part.
   * Per derfault the 'hit'-node is shifted to the right and the node takes the place 
   * the of the 'hit'-node. 
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   * 
   * E.g. Following is given:           AAA[1]           
   *                                  /       \          
   *                              ..BBB[1]..BBB[2] ..    
   *
   * a) insertChild('/AAA[1]/BBB[1]', <node CCC>)
   * b) insertChild('/AAA[1]/BBB[1]', <node CCC>, $shiftRight=FALSE)
   * c) insertChild('/AAA[1]/BBB[1]', <node CCC>, $shiftRight=FALSE, $afterText=FALSE)
   *
   * a)                          b)                           c)                        
   *          AAA[1]                       AAA[1]                       AAA[1]          
   *        /    |   \                   /    |   \                   /    |   \        
   *  ..BBB[1]..CCC[1]BBB[2]..     ..BBB[1]..BBB[2]..CCC[1]     ..BBB[1]..BBB[2]CCC[1]..
   *
   * #### Do a complete review of the "(optional)" tag after several arguments.
   *
   * @param  $xPathQuery  (string) Xpath to the node to append.
   * @param  $node        (array)  A doc node.
   * @param  $shiftRight  (bool)   (optional, default=TRUE) Shift the target node to the right.
   * @param  $afterText   (bool)   (optional, default=TRUE) Insert after the text.
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                                the changes.  A performance helper.  See reindexNodeTree()
   * @return              (bool)   TRUE on success, FALSE on error.
   * @see    _xml2Document(), appendChildByXml(), reindexNodeTree()
   */
  function insertChild($xPathQuery, $node, $shiftRight=TRUE, $afterText=TRUE, $autoReindex=TRUE) {
    if (is_string($node)) {
      if (!($node = $this->_xml2Document($node))) return FALSE;
    }
    // Special case if it's 'super root'. We then have to take the child node == top node
    if (empty($node['name'])) $node = $node['childNodes'][0];
  
    // Check for a valid xPathQuery
    $xPathSet = $this->_resolveXPathQuery($xPathQuery,'appendChild');
    if (sizeOf($xPathSet) === 0) {
      $this->_displayError(sprintf($this->errorStrings['NoNodeMatch'], $xPathQuery), __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    
    $mustReindex = FALSE;
    // Make chages from 'bottom-up'. In this manner the modifications will not affect itself.
    for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
      $absoluteXPath = $xPathSet[$i];
      $mustReindex = $autoReindex;
      $childNode =& $this->nodeIndex[$absoluteXPath];
      $parentNode =& $childNode['parentNode'];
      //Special case: It not possible to add siblings to the top node.
      if (empty($parentNode['name'])) continue;
      $newNode =& $this->cloneNode($node);
      $pos = $shiftRight ? $childNode['pos'] : $childNode['pos']+1;
      $parentNode['childNodes'] = array_merge(
                                    array_slice($parentNode['childNodes'], 0, $pos),
                                    array($newNode),
                                    array_slice($parentNode['childNodes'], $pos)
                                  );
      $pos += $afterText ? 1 : 0;
      $parentNode['textParts'] = array_merge(
                                   array_slice($parentNode['textParts'], 0, $pos),
                                   '',
                                   array_slice($parentNode['textParts'], $pos)
                                 );
    }
    if ($mustReindex) $this->reindexNodeTree();
    return TRUE;
  }
  
  /**
   * Appends a child to anothers children.
   *
   * If you intend to do a lot of appending, you should leave autoIndex as FALSE
   * and then call reindexNodeTree() when you are finished all the appending.
   *
   * @param  $xPathQuery  (string) Xpath to the node to append to.
   * @param  $node        (array)  A doc node.
   * @param  $afterText   (bool)   (optional, default=FALSE) Insert after the text.
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                               the changes.  A performance helper.  See reindexNodeTree()
   * @return              (bool)   TRUE on success, FALSE on error.
   * @see    reindexNodeTree()
   */
  function appendChild($xPathQuery, $node, $afterText=FALSE, $autoReindex=TRUE) {
    if (is_string($node)) {
      if (!($node = $this->_xml2Document($node))) return FALSE;
    }
    // Special case if it's 'super root'. We then have to take the child node == top node
    if (empty($node['name'])) $node = $node['childNodes'][0];
  
    // Check for a valid xPathQuery
    $xPathSet = $this->_resolveXPathQuery($xPathQuery,'appendChild');
    if (sizeOf($xPathSet) === 0) {
      $this->_displayError(sprintf($this->errorStrings['NoNodeMatch'], $xPathQuery), __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    
    $mustReindex = FALSE;
    $result = FALSE;
    // Make chages from 'bottom-up'. In this manner the modifications will not affect itself.
    for ($i=sizeOf($xPathSet)-1; $i>=0; $i--) {
      $absoluteXPath = $xPathSet[$i];
      $mustReindex = $autoReindex;
      $parentNode =& $this->nodeIndex[$absoluteXPath];
      $newNode =& $this->cloneNode($node);
      $pos = count($parentNode['childNodes']);
      $parentNode['childNodes'][] =& $newNode;
      $pos -= $afterText ? 0 : 1;
      $parentNode['textParts'] = array_merge(
                                   array_slice($parentNode['textParts'], 0, $pos),
                                   '',
                                   array_slice($parentNode['textParts'], $pos)
                                 );
      $result[] = "$absoluteXPath/{$newNode['name']}";
    }
    if ($mustReindex) $this->reindexNodeTree();
    if (count($result) == 1) $result = $result[0];
    return $result;
  }
  
  /**
   * Inserts a node before the reference node with the same parent.
   *
   * If you intend to do a lot of appending, you should leave autoIndex as FALSE
   * and then call reindexNodeTree() when you are finished all the appending.
   *
   * @param  $xPathQuery  (string) Xpath to the node to insert new node before
   * @param  $node        (array)  A doc node.
   * @param  $afterText   (bool)   (optional, default=FLASE) Insert after the text.
   * @param  $autoReindex (bool)   (optional, default=TRUE) Reindex the document to reflect 
   *                               the changes.  A performance helper.  See reindexNodeTree()
   * @return              (bool)   TRUE on success, FALSE on error.
   * @see    reindexNodeTree()
   */
  function insertBefore($xPathQuery, $node, $afterText=TRUE, $autoReindex=TRUE) {
    return $this->insertChild($xPathQuery, $node, $shiftRight=TRUE, $afterText, $autoReindex);
  }
  

  //-----------------------------------------------------------------------------------------
  // XPath                     ------  Attribute  Set/Get  ------                            
  //-----------------------------------------------------------------------------------------
  
  /** 
   * Retrieves a dedecated attribute value or a hash-array of all attributes of a node.
   * 
   * The first param $absoluteXPath must be a valid xpath OR a xpath-query that results 
   * to *one* xpath. If the second param $attrName is not set, a hash-array of all attributes 
   * of that node is returned.
   *
   * Optionally you may pass an attrubute name in $attrName and the function will return the 
   * string value of that attribute.
   *
   * @param  $absoluteXPath (string) Full xpath OR a xpath-query that results to *one* xpath.
   * @param  $attrName      (string) (Optional) The name of the attribute. See above.
   * @return                (mixed)  hash-array or a string of attributes depending if the 
   *                                 parameter $attrName was set (see above).  FALSE if the 
   *                                 node or attribute couldn't be found.
   * @see    setAttribute(), removeAttribute()
   */
  function getAttributes($absoluteXPath, $attrName=NULL) {
    // Numpty check
    if (!isSet($this->nodeIndex[$absoluteXPath])) {
      $xPathSet = $this->_resolveXPathQuery($absoluteXPath,'setAttributes');
      if (empty($xPathSet)) return FALSE;
      // only use the first entry
      $absoluteXPath = $xPathSet[0];
    }
    
    // Return the complete list or just the desired element
    if (is_null($attrName)) {
      return $this->nodeIndex[$absoluteXPath]['attributes'];
    } elseif (isSet($this->nodeIndex[$absoluteXPath]['attributes'][$attrName])) {
      return $this->nodeIndex[$absoluteXPath]['attributes'][$attrName];
    }
    return FALSE;
  }
  
  /**
   * Set attributes of a node(s).
   *
   * This method sets a number single attributes. An existing attribute is overwritten (default)
   * with the new value, but setting the last param to FALSE will prevent overwritten.
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery (string) xpath to the node (See note above).
   * @param  $name       (string) Attribute name.
   * @param  $value      (string) Attribute value.   
   * @param  $overwrite  (bool)   If the attribute is already set we overwrite it (see text above)
   * @return             (bool)   TRUE on success, FALSE on failure.
   * @see    getAttribute(), removeAttribute()
   */
  function setAttribute($xPathQuery, $name, $value, $overwrite=TRUE) {
    return $this->setAttributes($xPathQuery, array($name => $value), $overwrite);
  }
  
  /**
   * Version of setAttribute() that sets multiple attributes to node(s).
   *
   * This method sets a number of attributes. Existing attributes are overwritten (default)
   * with the new values, but setting the last param to FALSE will prevent overwritten.
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery (string) xpath to the node (See note above).
   * @param  $attributes (array)  associative array of attributes to set.
   * @param  $overwrite  (bool)   If the attributes are already set we overwrite them (see text above)
   * @return             (bool)   TRUE on success, FALSE otherwise
   * @see    setAttribute(), getAttribute(), removeAttribute()
   */
  function setAttributes($xPathQuery, $attributes, $overwrite=TRUE) {
    $status = FALSE;
    do { // try-block
      // The attributes parameter should be an associative array.
      if (!is_array($attributes)) break;  // try-block
      
      // Check for a valid xPathQuery
      $xPathSet = $this->_resolveXPathQuery($xPathQuery,'setAttributes');
      foreach($xPathSet as $absoluteXPath) {
        // Add the attributes to the node.
        $theNode =& $this->nodeIndex[$absoluteXPath];
        if (empty($theNode['attributes'])) {
          $this->nodeIndex[$absoluteXPath]['attributes'] = $attributes;
        } else {
          $theNode['attributes'] = $overwrite ? array_merge($theNode['attributes'],$attributes) : array_merge($attributes, $theNode['attributes']);
        }
      }
      $status = TRUE;
    } while(FALSE); // END try-block
    
    return $status;
  }
  
  /**
   * Removes an attribute of a node(s).
   *
   * This method removes *ALL* attributres per default unless the second parameter $attrList is set.
   * $attrList can be either a single attr-name as string OR a vector of attr-names as array.
   * E.g. 
   *  removeAttribute(<xPath>);                     # will remove *ALL* attributes.
   *  removeAttribute(<xPath>, 'A');                # will only remove attributes called 'A'.
   *  removeAttribute(<xPath>, array('A_1','A_2')); # will remove attribute 'A_1' and 'A_2'.
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param   $xPathQuery (string) xpath to the node (See note above).
   * @param   $attrList   (mixed)  (optional) if not set will delete *all* (see text above)
   * @return              (bool)   TRUE on success, FALSE if the node couldn't be found
   * @see     getAttribute(), setAttribute()
   */
  function removeAttribute($xPathQuery, $attrList=NULL) {
    // Check for a valid xPathQuery
    $xPathSet = $this->_resolveXPathQuery($xPathQuery, 'removeAttribute');
    
    if (!empty($attrList) AND is_string($attrList)) $attrList = array($attrList);
    if (!is_array($attrList)) return FALSE;
    
    foreach($xPathSet as $absoluteXPath) {
      // If the attribute parameter wasn't set then remove all the attributes
      if ($attrList[0] === NULL) {
        $this->nodeIndex[$absoluteXPath]['attributes'] = array();
        continue; 
      }
      // Remove all the elements in the array then.
      foreach($attrList as $name) {
        unset($this->nodeIndex[$absoluteXPath]['attributes'][$name]);
      }
    }
    return TRUE;
  }
  
  //-----------------------------------------------------------------------------------------
  // XPath                        ------  Text  Set/Get  ------                              
  //-----------------------------------------------------------------------------------------
  
  /**
   * Retrieve all the text from a node as a single string.
   *
   * Sample  
   * Given is: <AA> This <BB\>is <BB\>  some<BB\>text </AA>
   * Return of getData('/AA[1]') would be:  " This is   sometext "
   * The first param $absoluteXPath must be a valid xpath OR a xpath-query that results 
   * to *one* xpath. 
   *
   * @param  $absoluteXPath (string) Full xpath OR a xpath-query that results to *one* xpath.
   * @return                (mixed)  The returned string (see above), FALSE if the node 
   *                                 couldn't be found or is not unique.
   * @see getDataParts()
   */
  function getData($absoluteXPath) {
    $aDataParts = $this->getDataParts($absoluteXPath);
    if ($aDataParts === FALSE) return FALSE;
    return implode('', $aDataParts);
  }
  
  /**
   * Retrieve all the text from a node as a vector of strings
   * 
   * Where each element of the array was interrupted by a non-text child element.
   *
   * Sample  
   * Given is: <AA> This <BB\>is <BB\>  some<BB\>text </AA>
   * Return of getDataParts('/AA[1]') would be:  array([0]=>' This ', [1]=>'is ', [2]=>'  some', [3]=>'text ');
   * The first param $absoluteXPath must be a valid xpath OR a xpath-query that results 
   * to *one* xpath. 
   *
   * @param  $absoluteXPath   (string) Full xpath OR a xpath-query that results to *one* xpath.
   * @return                  (mixed)  The returned array (see above), or FALSE if node is not 
   *                                   found or is not unique.
   * @see getData()
   */
  function getDataParts($absoluteXPath) {
    // Resolve xPath argument
    $xPathSet = $this->_resolveXPathQuery($absoluteXPath, 'getDataParts');
    if (count($xPathSet) != 1) {
      $this->_displayError(sprintf($this->errorStrings['AbsoluteXPathRequired'], $absoluteXPath), __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    $absoluteXPath = $xPathSet[0];
    
    return $this->nodeIndex[$absoluteXPath]['textParts'];
  }
  
  /**
   * Retrieves a sub string of a text-part OR attribute-value.
   *
   * This method retrieves the sub string of a specific text-part OR (if the 
   * $absoluteXPath references an attribute) the the sub string  of the attribute value.
   * If no 'direct referencing' is used (Xpath ends with text()[<part-number>]), then 
   * the first text-part of the node ist returned (if exsiting).
   *
   * @param  $absoluteXPath (string) Xpath to the node (See note above).   
   * @param  $offset        (int)    (optional, default is 0) Starting offset. (Just like PHP's substr())
   * @param  $count         (number) (optional, default is ALL) Character count  (Just like PHP's substr())
   * @return                (mixed)  The sub string, FALSE if not found or on error
   * @see    XPathEngine::wholeText(), PHP's substr()
   */
  function substringData($absoluteXPath, $offset = 0, $count = NULL) {
    if (!($text = $this->wholeText($absoluteXPath))) return FALSE;
    if (is_null($count)) {
      return substr($text, $offset);
    } else {
      return substr($text, $offset, $count);
    } 
  }
  
  /**
   * Replace a sub string of a text-part OR attribute-value.
   *
   * @param  $absoluteXPath (string) Xpath to the node.   
   * @param  $replacement   (string) The string to replace with.
   * @param  $offset        (int)    (optional, default is 0) Starting offset. (Just like PHP's substr_replace ())
   * @param  $count         (number) (optional, default is 0=ALL) Character count  (Just like PHP's substr_replace())
   * @param  $textPartNr    (int)    (optional) (see _getTextSet() )
   * @return                (bool)   The new string value on success, FALSE if not found or on error
   * @see    substringData()
   */
  function replaceData($xPathQuery, $replacement, $offset = 0, $count = 0, $textPartNr=1) {
    if (!($textSet = $this->_getTextSet($xPathQuery, $textPartNr))) return FALSE;
    $tSize=sizeOf($textSet);
    for ($i=0; $i<$tSize; $i++) {
      if ($count) {
        $textSet[$i] = substr_replace($textSet[$i], $replacement, $offset, $count);
      } else {
        $textSet[$i] = substr_replace($textSet[$i], $replacement, $offset);
      } 
    }
    return TRUE;
  }
  
  /**
   * Insert a sub string in a text-part OR attribute-value.
   *
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery (string) xpath to the node (See note above).
   * @param  $data       (string) The string to replace with.
   * @param  $offset     (int)    (optional, default is 0) Offset at which to insert the data.
   * @return             (bool)   The new string on success, FALSE if not found or on error
   * @see    replaceData()
   */
  function insertData($xPathQuery, $data, $offset=0) {
    return $this->replaceData($xPathQuery, $data, $offset, 0);
  }
  
  /**
   * Append text data to the end of the text for an attribute OR node text-part.
   *
   * This method adds content to a node. If it's an attribute node, then
   * the value of the attribute will be set, otherwise the passed data will append to 
   * character data of the node text-part. Per default the first text-part is taken.
   *
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param   $xPathQuery (string) to the node(s) (See note above).
   * @param   $data       (string) String containing the content to be added.
   * @param   $textPartNr (int)    (optional, default is 1) (see _getTextSet())
   * @return              (bool)   TRUE on success, otherwise FALSE
   * @see     _getTextSet()
   */
  function appendData($xPathQuery, $data, $textPartNr=1) {
    if (!($textSet = $this->_getTextSet($xPathQuery, $textPartNr))) return FALSE;
    $tSize=sizeOf($textSet);
    for ($i=0; $i<$tSize; $i++) {
      $textSet[$i] .= $data;
    }
    return TRUE;
  }
  
  /**
   * Delete the data of a node.
   *
   * This method deletes content of a node. If it's an attribute node, then
   * the value of the attribute will be removed, otherwise the node text-part. 
   * will be deleted.  Per default the first text-part is deleted.
   *
   * NOTE: When passing a xpath-query instead of an abs. Xpath.
   *       Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param  $xPathQuery (string) to the node(s) (See note above).
   * @param  $offset     (int)    (optional, default is 0) Starting offset. (Just like PHP's substr_replace())
   * @param  $count      (number) (optional, default is 0=ALL) Character count.  (Just like PHP's substr_replace())
   * @param  $textPartNr (int)    (optional, default is 0) the text part to delete (see _getTextSet())
   * @return             (bool)   TRUE on success, otherwise FALSE
   * @see     _getTextSet()
   */
  function deleteData($xPathQuery, $offset=0, $count=0, $textPartNr=1) {
    if (!($textSet = $this->_getTextSet($xPathQuery, $textPartNr))) return FALSE;
    $tSize=sizeOf($textSet);
    for ($i=0; $i<$tSize; $i++) {
      if (!$count)
        $textSet[$i] = "";
      else
        $textSet[$i] = substr_replace($textSet[$i],'', $offset, $count);
    } 
    return TRUE;
  }
 
  //-----------------------------------------------------------------------------------------
  // XPath                      ------  Help Stuff  ------                                   
  //-----------------------------------------------------------------------------------------
 
  /**
   * Decodes the character set entities in the given string.
   *
   * This function is given for convenience, as all text strings or attributes
   * are going to come back to you with their entities still encoded.  You can
   * use this function to remove these entites.
   *
   * ### Provide an option that will do this by default.
   *
   * @param $encodedData (mixed) The string or array that has entities you would like to remove
   * @param $reverse     (bool)  If TRUE entities will be encoded rather than decoded, ie
   *                             < to &lt; rather than &lt; to <.
   * @return             (mixed) The string or array returned with entities decoded.
   */
  function decodeEntities($encodedData, $reverse=FALSE) {
    static $aEncodeTbl;
    static $aDecodeTbl;
    // Get the translation entities, but we'll cache the result to enhance performance.
    if (empty($aDecodeTbl)) {
      // Get the translation entities.
      $aEncodeTbl = get_html_translation_table(HTML_ENTITIES);
      $aDecodeTbl = array_flip($aEncodeTbl);
    }

    // If it's just a single string.
    if (!is_array($encodedData)) {
      if ($reverse) {
        return strtr($encodedData, $aEncodeTbl);
      } else {
        return strtr($encodedData, $aDecodeTbl);
      }
    }

    $result = array();
    foreach($encodedData as $string) {
      if ($reverse) {
        $result[] = strtr($string, $aEncodeTbl);
      } else {
        $result[] = strtr($string, $aDecodeTbl);
      }
    }

    return $result;
  }
  
  /**
   * Parse the XML to a node-tree. A so called 'document'
   *
   * @param  $xmlString (string) The string to turn into a document node.
   * @return            (&array)  a node-tree
   */
  function &_xml2Document($xmlString) {
    $xmlOptions = array(
                    XML_OPTION_CASE_FOLDING => $this->getProperties('caseFolding'), 
                    XML_OPTION_SKIP_WHITE   => $this->getProperties('skipWhiteSpaces')
                  );
    $xmlParser =& new XPathEngine($xmlOptions);
    $xmlParser->setVerbose(FALSE);
    // Parse the XML string
    if (!$xmlParser->importFromString($xmlString)) {
      $this->_displayError($xmlParser->getLastError(), __LINE__, __FILE__, FALSE);
      return FALSE;
    }
    return $xmlParser->getNode('/');
  }
  
  /**
   * Get a reference-list to node text part(s) or node attribute(s).
   * 
   * If the Xquery references an attribute(s) (Xquery ends with attribute::), 
   * then the text value of the node-attribute(s) is/are returned.
   * Otherwise the Xquery is referencing to text part(s) of node(s). This can be either a 
   * direct reference to text part(s) (Xquery ends with text()[<nr>]) or indirect reference 
   * (a simple Xquery to node(s)).
   * 1) Direct Reference (Xquery ends with text()[<part-number>]):
   *   If the 'part-number' is omitted, the first text-part is assumed; starting by 1.
   *   Negative numbers are allowed, where -1 is the last text-part a.s.o.
   * 2) Indirect Reference (a simple  Xquery to node(s)):
   *   Default is to return the first text part(s). Optionally you may pass a parameter 
   *   $textPartNr to define the text-part you want;  starting by 1.
   *   Negative numbers are allowed, where -1 is the last text-part a.s.o.
   *
   * NOTE I : The returned vector is a set of references to the text parts / attributes.
   *          This is handy, if you wish to modify the contents.
   * NOTE II: text-part numbers out of range will not be in the list
   * NOTE III:Instead of an absolute xpath you may also pass a xpath-query.
   *          Depending on setModMatch() one, none or multiple nodes are affected.
   *
   * @param   $xPathQuery (string) xpath to the node (See note above).
   * @param   $textPartNr (int)    String containing the content to be set.
   * @return              (mixed)  A vector of *references* to the text that match, or 
   *                               FALSE on error
   * @see XPathEngine::wholeText()
   */
  function _getTextSet($xPathQuery, $textPartNr=1) {
    $status = FALSE;
    $funcName = '_getTextSet';
    $textSet = array();
    
    do { // try-block
      // Check if it's a Xpath reference to an attribut(s). Xpath ends with attribute::)
      if ( preg_match(";(.*)/(attribute::|@)([^/]*)$;U", $xPathQuery, $matches) ) {
        $xPathQuery = $matches[1];
        $attribute = $matches[3];
        // Quick out
        if (isSet($this->nodeIndex[$xPathQuery]) ) {
          $xPathSet[] = $xPathQuery;
        } else {
          // Try to evaluate the absoluteXPath (since it seems to be an Xquery and not an abs. Xpath)
          $xPathSet = $this->_resolveXPathQuery("$xPathQuery/attribute::$attribute", $funcName);
        }
        foreach($xPathSet as $absoluteXPath) {
          preg_match(";(.*)/attribute::([^/]*)$;U", $xPathSet[0], $matches);
          $absoluteXPath = $matches[1];
          $attribute = $matches[2];
          if ( !isSet($this->nodeIndex[$absoluteXPath]['attributes'][$attribute]) ) {
            $this->_displayError("The $absoluteXPath/attribute::$attribute value isn't a node in this document.", __LINE__, __FILE__, FALSE);
            continue;
          }
          $textSet[] =& $this->nodes[$absoluteXPath]['attributes'][$attribute];
        }
        $status = TRUE;
        break; // try-block
      }
      
      // Check if it's a Xpath reference direct to a text-part(s). (xpath ends with text()[<part-number>])
      if ( preg_match(":(.*)/text\(\)(\[(.*)\])?$:U", $xPathQuery, $matches) ) {
        $xPathQuery = $matches[1];
        // default to the first text node if a text node was not specified
        $textPartNr = isSet($matches[2]) ? substr($matches[2],1,-1) : 1;
        // Quick check
        if (isSet($this->nodeIndex[$xPathQuery]) ) {
          $xPathSet[] = $xPathQuery;
        } else {
          // Try to evaluate the absoluteXPath (since it seams to be an Xquery and not an abs. Xpath)
          $xPathSet = $this->_resolveXPathQuery("$xPathQuery/text()[$textPartNr]", $funcName);
        }
      }
      else {
        // At this point we have been given an xpath with neither a 'text()' or 'attribute::' axis at the end
        // So this means to get the text-part of the node. If parameter $textPartNr was not set, use the last
        // text-part.
        if (isSet($this->nodeIndex[$xPathQuery]) ) {
          $xPathSet[] = $xPathQuery;
        } else {
          // Try to evaluate the absoluteXPath (since it seams to be an Xquery and not an abs. Xpath)
          $xPathSet = $this->_resolveXPathQuery($xPathQuery, $funcName);
        }
      }

      // Now fetch all text-parts that match. (May be 0,1 or many)
      foreach($xPathSet as $absoluteXPath) {
        unset($text);
        if ($text =& $this->wholeText($absoluteXPath, $textPartNr)) {
          $textSet[] =& $text;
        }
      }

      $status = TRUE;
    } while (FALSE); // END try-block
    
    if (!$status) return FALSE;
    return $textSet;
  }
  
  /**
   * Resolves an xPathQuery vector depending on the property['modMatch']
   * 
   * To:
   *   - all matches, 
   *   - the first
   *   - none (If the query matches more then one node.)
   * see  setModMatch() for details
   * 
   * @param  $xPathQuery (string) An xpath query targeting a single node
   * @param  $function   (string) The function in which this check was called
   * @return             (array)  Vector of $absoluteXPath's (May be empty)
   * @see    setModMatch()
   */
  function _resolveXPathQuery($xPathQuery, $function) {
    $xPathSet = array();
    do { // try-block
      if (isSet($this->nodeIndex[$xPathQuery])) {
        $xPathSet[] = $xPathQuery;
        break; // try-block
      }
      if (empty($xPathQuery)) break; // try-block
      if (substr($xPathQuery, -1) === '/') break; // If the xPathQuery ends with '/' then it cannot be a good query.
      // If this xPathQuery is not absolute then attempt to evaluate it
      $xPathSet = $this->match($xPathQuery);
      
      $resultSize = sizeOf($xPathSet);
      switch($this->properties['modMatch']) {
        case XPATH_QUERYHIT_UNIQUE : 
          if ($resultSize >1) {
            $xPathSet = array();
            if ($this->properties['verboseLevel']) $this->_displayError("Canceled function '{$function}'. The query '{$xPathQuery}' mached {$resultSize} nodes and 'modMatch' is set to XPATH_QUERYHIT_UNIQUE.", __LINE__, __FILE__, FALSE);
          }
          break;
        case XPATH_QUERYHIT_FIRST : 
          if ($resultSize >1) {
            $xPathSet = array($xPathSet[0]);
            if ($this->properties['verboseLevel']) $this->_displayError("Only modified first node in function '{$function}' because the query '{$xPathQuery}' mached {$resultSize} nodes and 'modMatch' is set to XPATH_QUERYHIT_FIRST.", __LINE__, __FILE__, FALSE);
          }
          break;
        default: ; // DO NOTHING
      }
    } while (FALSE);
    
    if ($this->properties['verboseLevel'] >= 2) $this->_displayMessage("'{$xPathQuery}' parameter from '{$function}' returned the following nodes: ".(count($xPathSet)?implode('<br>', $xPathSet):'[none]'), __LINE__, __FILE__);
    return $xPathSet;
  }
} // END OF CLASS XPath

// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------
// -----------------------------------------------------------------------------------------

/**************************************************************************************************
// Usage Sample:
// -------------
// Following code will give you an idea how to work with PHP.XPath. It's a working sample
// to help you get started. :o)
// Take the comment tags away and run this file.
**************************************************************************************************/

/**
 * Produces a short title line.
 */
function _title($title) { 
  echo "<br><hr><b>" . htmlspecialchars($title) . "</b><hr>\n";
}

  // The sampe source:
  $q = '?';
  $xmlSource = <<< EOD
  <{$q}Process_Instruction test="&copy;&nbsp;All right reserved" {$q}>
    <AAA> ,,1,,
      ..1.. <![CDATA[ bal  bla 
      kkk ]]>
      <BBB foo="bar">
        ..2..
      </BBB>..3..<CC/>   ..4..</AAA> 
EOD;
  
  // The sample code:
  $xmlOptions = array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE);
  $xPath =& new XPath($xmlOptions);
  $xPath->bDebugXmlParse = TRUE;
  if (!$xPath->importFromString($xmlSource)) {
    echo $xPath->getLastError(); exit;
  }
  
  _title("Following was imported:");
  echo $xPath->exportAsHtml();
  
  _title("Get some content");
  echo "Last text part in &lt;AAA&gt;: '" . $xPath->wholeText('/AAA[1]', -1) ."'<br>\n";
  echo "All the text in  &lt;AAA&gt;: '" . $xPath->wholeText('/AAA[1]') ."'<br>\n";
  echo "The attibute value  in  &lt;BBB&gt;: '" . $xPath->getAttributes('/AAA[1]/BBB[1]', 'FOO') ."'<br>\n";
  
  _title("Append some additional XML below /AAA/BBB:");
  $xPath->appendChild('/AAA[1]/BBB[1]', '<CCC> Step 1. Append new node </CCC>', $afterText=TRUE);
  $xPath->appendChild('/AAA[1]/BBB[1]', '<CCC> Step 2. Append new node </CCC>', $afterText=FALSE);
  $xPath->appendChild('/AAA[1]/BBB[1]', '<CCC> Step 3. Append new node </CCC>', $afterText=FALSE);
  echo $xPath->exportAsHtml();
  
  _title("Insert some additional XML below <AAA>:");
  $xPath->reindexNodeTree();
  $xPath->insertChild('/AAA[1]/BBB[1]', '<BB> Step 1. Insert new node </BB>', $shiftRight=TRUE, $afterText=TRUE);
  $xPath->insertChild('/AAA[1]/BBB[1]', '<BB> Step 2. Insert new node </BB>', $shiftRight=FALSE, $afterText=TRUE);
  $xPath->insertChild('/AAA[1]/BBB[1]', '<BB> Step 3. Insert new node </BB>', $shiftRight=FALSE, $afterText=FALSE);
  echo $xPath->exportAsHtml();

  _title("Replace the last <BB> node with new XML:");
  $xPath->reindexNodeTree();
  $xPath->replaceChild('/AAA[1]/BB[last()]', '<DDD> Replaced last BB </DDD>', $afterText=FALSE);
  echo $xPath->exportAsHtml();
  
  _title("Replace second <BB> node with normal text");
  $xPath->reindexNodeTree();
  $xPath->replaceChildByData('/AAA[1]/BB[2]', '"Some new text"', $afterText=FALSE);
  echo $xPath->exportAsHtml();

?>
