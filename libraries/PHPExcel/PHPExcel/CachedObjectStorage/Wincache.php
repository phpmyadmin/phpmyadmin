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
 * @package	PHPExcel_CachedObjectStorage
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license	http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version	1.7.3c, 2010-06-01
 */


/**
 * PHPExcel_CachedObjectStorage_Wincache
 *
 * @category   PHPExcel
 * @package	PHPExcel_CachedObjectStorage
 * @copyright  Copyright (c) 2006 - 2010 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_CachedObjectStorage_Wincache extends PHPExcel_CachedObjectStorage_CacheBase implements PHPExcel_CachedObjectStorage_ICache {

	private $_cachePrefix = null;

	private $_cacheTime = 600;


	private function _storeData() {
		$this->_currentObject->detach();

		$obj = serialize($this->_currentObject);
		if (wincache_ucache_exists($this->_cachePrefix.$this->_currentObjectID.'.cache')) {
			wincache_ucache_set($this->_cachePrefix.$this->_currentObjectID.'.cache', $obj, $this->_cacheTime);
		} else {
			wincache_ucache_add($this->_cachePrefix.$this->_currentObjectID.'.cache', $obj, $this->_cacheTime);
		}

		$this->_currentObjectID = $this->_currentObject = null;
	}	//	function _storeData()


	/**
	 *	Add or Update a cell in cache identified by coordinate address
	 *
	 *	@param	string			$pCoord		Coordinate address of the cell to update
	 *	@param	PHPExcel_Cell	$cell		Cell to update
	 *	@return	void
	 *	@throws	Exception
	 */
	public function addCacheData($pCoord, PHPExcel_Cell $cell) {
		if (($pCoord !== $this->_currentObjectID) && ($this->_currentObjectID !== null)) {
			$this->_storeData();
		}
		$this->_cellCache[$pCoord] = true;

		$this->_currentObjectID = $pCoord;
		$this->_currentObject = $cell;

		return $cell;
	}	//	function addCacheData()


	/**
	 *	Is a value set in the current PHPExcel_CachedObjectStorage_ICache for an indexed cell?
	 *
	 *	@param	string		$pCoord		Coordinate address of the cell to check
	 *	@return	void
	 *	@return	boolean
	 */
	public function isDataSet($pCoord) {
		//	Check if the requested entry is the current object, or exists in the cache
		if (parent::isDataSet($pCoord)) {
			if ($this->_currentObjectID == $pCoord) {
				return true;
			}
			//	Check if the requested entry still exists in cache
			$success = wincache_ucache_exists($this->_cachePrefix.$pCoord.'.cache');
			if ($success === false) {
				//	Entry no longer exists in Wincache, so clear it from the cache array
				parent::deleteCacheData($pCoord);
				throw new Exception('Cell entry no longer exists in Wincache');
			}
			return true;
		}
		return false;
	}	//	function isDataSet()


	/**
	 * Get cell at a specific coordinate
	 *
	 * @param	string			$pCoord		Coordinate of the cell
	 * @throws	Exception
	 * @return	PHPExcel_Cell	Cell that was found, or null if not found
	 */
	public function getCacheData($pCoord) {
		if ($pCoord === $this->_currentObjectID) {
			return $this->_currentObject;
		}
		$this->_storeData();

		//	Check if the entry that has been requested actually exists
		$obj = null;
		if (parent::isDataSet($pCoord)) {
			$success = false;
			$obj = wincache_ucache_get($this->_cachePrefix.$pCoord.'.cache', $success);
			if ($success === false) {
				//	Entry no longer exists in Wincache, so clear it from the cache array
				parent::deleteCacheData($pCoord);
				throw new Exception('Cell entry no longer exists in Wincache');
			}
		} else {
			//	Return null if requested entry doesn't exist in cache
			return null;
		}

		//	Set current entry to the requested entry
		$this->_currentObjectID = $pCoord;
		$this->_currentObject = unserialize($obj);
		//	Re-attach the parent worksheet
		$this->_currentObject->attach($this->_parent);

		//	Return requested entry
		return $this->_currentObject;
	}	//	function getCacheData()


	/**
	 *	Delete a cell in cache identified by coordinate address
	 *
	 *	@param	string			$pCoord		Coordinate address of the cell to delete
	 *	@throws	Exception
	 */
	public function deleteCacheData($pCoord) {
		//	Delete the entry from Wincache
		wincache_ucache_delete($this->_cachePrefix.$pCoord.'.cache');

		//	Delete the entry from our cell address array
		parent::deleteCacheData($pCoord);
	}	//	function deleteCacheData()


	public function unsetWorksheetCells() {
		if(!is_null($this->_currentObject)) {
			$this->_currentObject->detach();
			$this->_currentObject = $this->_currentObjectID = null;
		}

		//	Flush the Wincache cache
		$this->__destruct();

		$this->_cellCache = array();

		//	detach ourself from the worksheet, so that it can then delete this object successfully
		$this->_parent = null;
	}	//	function unsetWorksheetCells()


	public function __construct(PHPExcel_Worksheet $parent, $arguments) {
		$cacheTime	= (isset($arguments['cacheTime']))	? $arguments['cacheTime']	: 600;

		if (is_null($this->_cachePrefix)) {
			if (function_exists('posix_getpid')) {
				$baseUnique = posix_getpid();
			} else {
				$baseUnique = mt_rand();
			}
			$this->_cachePrefix = substr(md5(uniqid($baseUnique,true)),0,8).'.';
			$this->_cacheTime = $cacheTime;

			parent::__construct($parent);
		}
	}	//	function __construct()


	public function __destruct() {
		$cacheList = $this->getCellList();
		foreach($cacheList as $cellID) {
			wincache_ucache_delete($this->_cachePrefix.$cellID.'.cache');
		}
	}	//	function __destruct()

}


?>