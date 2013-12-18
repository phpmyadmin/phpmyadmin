<?php
/**
   Copyright (c) 2003, 2005, 2006, 2009 Danilo Segan <danilo@kvota.net>.

   This file is part of PHP-gettext.

   PHP-gettext is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   PHP-gettext is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with PHP-gettext; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/


/**
 * Simple class to wrap file streams, string streams, etc.
 * seek is essential, and it should be byte stream
 *
 * @package PhpMyAdmin
 */
class StreamReader
{
    
    /**
     * Read the given stream.
     *
     * @param type $bytes The content to read.
     *
     * @todo return a string [FIXME: perhaps return array of bytes?]
     * 
     * @return type 
     */
    function read($bytes)
    {
        return false;
    }

    /**
     * Go to the position in the stream.
     *
     * @param type $position The position to go.
     *
     * @todo return new position
     *
     * @return type 
     */
    function seekto($position)
    {
        return false;
    }

    /**
     * Get the curent postion in the stream.
     *
     * @todo return current position
     *
     * @return type 
     */
    function currentpos()
    {
        return false;
    }

    /**
     * Get the stream length.
     *
     * @todo return length of entire stream (limit for seekto()s)
     *
     * @return type 
     */
    function length()
    {
        return false;
    }
    
};

/**
 * This class is responsible for read a string.
 *
 * @package PhpMyAdmin
 */
class StringReader
{
    
    var $_pos;
    var $_str;

    /**
     * The constructor for the StringReader class.
     *
     * @param string $str The string to do operations.
     */
    function StringReader($str='')
    {
        $this->_str = $str;
        $this->_pos = 0;
    }

    /**
     * Read the given string content.
     *
     * @param type $bytes The string content.
     *
     * @return type 
     */
    function read($bytes)
    {
        $data = substr($this->_str, $this->_pos, $bytes);
        $this->_pos += $bytes;
        if (strlen($this->_str)<$this->_pos) {
            $this->_pos = strlen($this->_str);
        }
        return $data;
    }

    /**
     * Go to the given position in the string.
     *
     * @param int $pos The position to go.
     *
     * @return int 
     */
    function seekto($pos)
    {
        $this->_pos = $pos;
        if (strlen($this->_str)<$this->_pos) {
            $this->_pos = strlen($this->_str);
        }
        return $this->_pos;
    }

    /**
     * Get current position in the string.
     *
     * @return int 
     */
    function currentpos()
    {
        return $this->_pos;
    }

    /**
     * Get length of the a string.
     *
     * @return int 
     */
    function length()
    {
        return strlen($this->_str);
    }

};


/**
 * This class is responsible for read a file.
 *
 * @package PhpMyAdmin
 */
class FileReader
{
    
    var $_pos;
    var $_fd;
    var $_length;

    /**
     * Read the given file.
     *
     * @param string $filename The file path to read.
     *
     * @return type 
     */
    function FileReader($filename)
    {
        if (file_exists($filename)) {

            $this->_length=filesize($filename);
            $this->_pos = 0;
            $this->_fd = fopen($filename, 'rb');
            if (!$this->_fd) {
                $this->error = 3; // Cannot read file, probably permissions
                return false;
            }
        } else {
            $this->error = 2; // File doesn't exist
            return false;
        }
    }

    /**
     * Read the given file content.
     *
     * @param type $bytes The file content.
     *
     * @return type 
     */
    function read($bytes)
    {
        if ($bytes) {
            fseek($this->_fd, $this->_pos);

            // PHP 5.1.1 does not read more than 8192 bytes in one fread()
            // the discussions at PHP Bugs suggest it's the intended behaviour
            $data = '';
            while ($bytes > 0) {
                $chunk  = fread($this->_fd, $bytes);
                $data  .= $chunk;
                $bytes -= strlen($chunk);
            }
            $this->_pos = ftell($this->_fd);

            return $data;
        } else {
            return '';
        }
    }

    /**
     * Go to the given line in the file.
     *
     * @param int $pos The line to go.
     *
     * @return int 
     */
    function seekto($pos)
    {
        fseek($this->_fd, $pos);
        $this->_pos = ftell($this->_fd);
        return $this->_pos;
    }

    /**
     * Get current position in the file.
     *
     * @return int 
     */
    function currentpos()
    {
        return $this->_pos;
    }

    /**
     * Get the file size.
     *
     * @return int the size of the file in bytes. 
     */
    function length()
    {
        return $this->_length;
    }

    /**
     * Close the file
     */
    function close()
    {
        fclose($this->_fd);
    }

};

/**
 * Preloads entire file in memory first, then creates a StringReader
 * over it (it assumes knowledge of StringReader internals)
 * 
 * @package PhpMyAdmin
 */
class CachedFileReader extends StringReader
{
    
    /**
     * Read cached file.
     *
     * @param string $filename Path to the file or directory.
     * 
     * @return type 
     */
    function CachedFileReader($filename)
    {
        if (file_exists($filename)) {

            $length=filesize($filename);
            $fd = fopen($filename, 'rb');

            if (!$fd) {
                $this->error = 3; // Cannot read file, probably permissions
                return false;
            }
            $this->_str = fread($fd, $length);
            fclose($fd);

        } else {
            $this->error = 2; // File doesn't exist
            return false;
        }
    }
    
};


?>
