<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2010 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel_RichText
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.2, 2010-01-11
 */


/** PHPExcel root directory */
if (!defined('PHPEXCEL_ROOT')) {
	/**
	 * @ignore
	 */
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/../');
}

/** PHPExcel_IComparable */
require_once PHPEXCEL_ROOT . 'PHPExcel/IComparable.php';

/** PHPExcel_Cell */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell.php';

/** PHPExcel_Cell_DataType */
require_once PHPEXCEL_ROOT . 'PHPExcel/Cell/DataType.php';

/** PHPExcel_RichText_ITextElement */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText/ITextElement.php';

/** PHPExcel_RichText_TextElement */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText/TextElement.php';

/** PHPExcel_RichText_Run */
require_once PHPEXCEL_ROOT . 'PHPExcel/RichText/Run.php';

/** PHPExcel_Style_Font */
require_once PHPEXCEL_ROOT . 'PHPExcel/Style/Font.php';

/**
 * PHPExcel_RichText
 *
 * @category   PHPExcel
 * @package    PHPExcel_RichText
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_RichText implements PHPExcel_IComparable
{
	/**
	 * Rich text elements
	 *
	 * @var PHPExcel_RichText_ITextElement[]
	 */
	private $_richTextElements;
	
	/**
	 * Parent cell
	 *
	 * @var PHPExcel_Cell
	 */
	private $_parent;
	   
    /**
     * Create a new PHPExcel_RichText instance
     *
     * @param 	PHPExcel_Cell	$pParent
     * @throws	Exception
     */
    public function __construct(PHPExcel_Cell $pCell = null)
    {
    	// Initialise variables
    	$this->_richTextElements = array();
    	
    	// Set parent?
    	if (!is_null($pCell)) {
	    	// Set parent cell
	    	$this->_parent = $pCell;
	    		
	    	// Add cell text and style
	    	if ($this->_parent->getValue() != "") {
	    		$objRun = new PHPExcel_RichText_Run($this->_parent->getValue());
	    		$objRun->setFont(clone $this->_parent->getParent()->getStyle($this->_parent->getCoordinate())->getFont());
	    		$this->addText($objRun);
	    	}
	    		
	    	// Set parent value
	    	$this->_parent->setValueExplicit($this, PHPExcel_Cell_DataType::TYPE_STRING);
    	}
    }
    
    /**
     * Add text
     *
     * @param 	PHPExcel_RichText_ITextElement		$pText		Rich text element
     * @throws 	Exception
     * @return PHPExcel_RichText
     */
    public function addText(PHPExcel_RichText_ITextElement $pText = null)
    {
    	$this->_richTextElements[] = $pText;
    	return $this;
    }
    
    /**
     * Create text
     *
     * @param 	string	$pText	Text
     * @return	PHPExcel_RichText_TextElement
     * @throws 	Exception
     */
    public function createText($pText = '')
    {
    	$objText = new PHPExcel_RichText_TextElement($pText);
    	$this->addText($objText);
    	return $objText;
    }
    
    /**
     * Create text run
     *
     * @param 	string	$pText	Text
     * @return	PHPExcel_RichText_Run
     * @throws 	Exception
     */
    public function createTextRun($pText = '')
    {
    	$objText = new PHPExcel_RichText_Run($pText);
    	$this->addText($objText);
    	return $objText;
    }
    
    /**
     * Get plain text
     *
     * @return string
     */
    public function getPlainText()
    {
    	// Return value
    	$returnValue = '';
    	
    	// Loop through all PHPExcel_RichText_ITextElement
    	foreach ($this->_richTextElements as $text) {
    		$returnValue .= $text->getText();
    	}
    	
    	// Return
    	return $returnValue;
    }
    
    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString() {
    	return $this->getPlainText();
    }
    
    /**
     * Get Rich Text elements
     *
     * @return PHPExcel_RichText_ITextElement[]
     */
    public function getRichTextElements()
    {
    	return $this->_richTextElements;
    }
    
    /**
     * Set Rich Text elements
     *
     * @param 	PHPExcel_RichText_ITextElement[]	$pElements		Array of elements
     * @throws 	Exception
     * @return PHPExcel_RichText
     */
    public function setRichTextElements($pElements = null)
    {
    	if (is_array($pElements)) {
    		$this->_richTextElements = $pElements;
    	} else {
    		throw new Exception("Invalid PHPExcel_RichText_ITextElement[] array passed.");
    	}
    	return $this;
    }
 
    /**
     * Get parent
     *
     * @return PHPExcel_Cell
     */
    public function getParent() {
    	return $this->_parent;
    }
    
    /**
     * Set parent
     *
     * @param PHPExcel_Cell	$value
     * @return PHPExcel_RichText
     */
    public function setParent(PHPExcel_Cell $value) {
    	// Set parent
    	$this->_parent = $value;
    	
    	// Set parent value
    	$this->_parent->setValueExplicit($this, PHPExcel_Cell_DataType::TYPE_STRING);
		
		// Verify style information

		$sheet = $this->_parent->getParent();
		$cellFont = $sheet->getStyle($this->_parent->getCoordinate())->getFont()->getSharedComponent();
		foreach ($this->getRichTextElements() as $element) {
			if (!($element instanceof PHPExcel_RichText_Run)) continue;
			
			if ($element->getFont()->getHashCode() == $sheet->getDefaultStyle()->getFont()->getHashCode()) {
				if ($element->getFont()->getHashCode() != $cellFont->getHashCode()) {
					$element->setFont(clone $cellFont);
				}
			}
		}
		return $this;
    }
    
	/**
	 * Get hash code
	 *
	 * @return string	Hash code
	 */	
	public function getHashCode() {
		$hashElements = '';
		foreach ($this->_richTextElements as $element) {
			$hashElements .= $element->getHashCode();
		}
		
    	return md5(
    		  $hashElements
    		. __CLASS__
    	);
    }
    
	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone() {
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if ($key == '_parent') continue;
			
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}
}
