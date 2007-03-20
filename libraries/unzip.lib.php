<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/* $Id$ */

    /**
     *  ZIP file unpack classes. Contributed to the phpMyAdmin project.
     *
     *  @category   phpPublic
     *  @package    File-Formats-ZIP
     *  @subpackage Unzip
     *  @filesource unzip.lib.php
     *  @version    1.0.1
     *
     *  @author     Holger Boskugel <vbwebprofi@gmx.de>
     *  @copyright  Copyright © 2003, Holger Boskugel, Berlin, Germany
     *  @license    http://opensource.org/licenses/gpl-license.php GNU Public License
     *
     *  @history
     *  2003-12-02 - HB : Patched : naming bug : Time/Size of file
     *                    Added   : ZIP file comment
     *                    Added   : Check BZIP2 support of PHP
     *  2003-11-29 - HB * Initial version
     */

    /**
     *  Unzip class, which retrieves entries from ZIP files.
     *
     *  Supports only the compression modes
     *  -  0 : Stored,
     *  -  8 : Deflated and
     *  - 12 : BZIP2
     *
     *  Based on :<BR>
     *  <BR>
     *  {@link http://www.pkware.com/products/enterprise/white_papers/appnote.html
     *  * Official ZIP file format}<BR>
     *  {@link http://msdn.microsoft.com/library/en-us/w98ddk/hh/w98ddk/storage_5l4m.asp
     *  * Microsoft DOS date/time format}
     *
     *  @category   phpPublic
     *  @package    File-Formats-ZIP
     *  @subpackage Unzip
     *  @version    1.0.1
     *  @author     Holger Boskugel <vbwebprofi@gmx.de>
     *  @uses       SimpleUnzipEntry
     *  @example    example.unzip.php Two examples
     */
    class SimpleUnzip {
// 2003-12-02 - HB >
        /**
         *  Array to store file entries
         *
         *  @var    string
         *  @access public
         *  @see    ReadFile()
         *  @since  1.0.1
         */
        var $Comment = '';
// 2003-12-02 - HB <

        /**
         *  Array to store file entries
         *
         *  @var    array
         *  @access public
         *  @see    ReadFile()
         *  @since  1.0
         */
        var $Entries = array();

        /**
         *  Name of the ZIP file
         *
         *  @var    string
         *  @access public
         *  @see    ReadFile()
         *  @since  1.0
         */
        var $Name = '';

        /**
         *  Size of the ZIP file
         *
         *  @var    integer
         *  @access public
         *  @see    ReadFile()
         *  @since  1.0
         */
        var $Size = 0;

        /**
         *  Time of the ZIP file (unix timestamp)
         *
         *  @var    integer
         *  @access public
         *  @see    ReadFile()
         *  @since  1.0
         */
        var $Time = 0;

        /**
         *  Contructor of the class
         *
         *  @param  string      File name
         *  @return SimpleUnzip Instanced class
         *  @access public
         *  @uses   SimpleUnzip::ReadFile() Opens file on new if specified
         *  @since  1.0
         */
        function SimpleUnzip($in_FileName = '')
        {
            if ($in_FileName !== '') {
                SimpleUnzip::ReadFile($in_FileName);
            }
        } // end of the 'SimpleUnzip' constructor

        /**
         *  Counts the entries
         *
         *  @return integer Count of ZIP entries
         *  @access public
         *  @uses   $Entries
         *  @since  1.0
         */
        function Count()
        {
            return count($this->Entries);
        } // end of the 'Count()' method

        /**
         *  Gets data of the specified ZIP entry
         *
         *  @param  integer Index of the ZIP entry
         *  @return mixed   Data for the ZIP entry
         *  @uses   SimpleUnzipEntry::$Data
         *  @access public
         *  @since  1.0
         */
        function GetData($in_Index)
        {
            return $this->Entries[$in_Index]->Data;
        } // end of the 'GetData()' method

        /**
         *  Gets an entry of the ZIP file
         *
         *  @param  integer             Index of the ZIP entry
         *  @return SimpleUnzipEntry    Entry of the ZIP file
         *  @uses   $Entries
         *  @access public
         *  @since  1.0
         */
        function GetEntry($in_Index)
        {
            return $this->Entries[$in_Index];
        } // end of the 'GetEntry()' method

        /**
         *  Gets error code for the specified ZIP entry
         *
         *  @param  integer     Index of the ZIP entry
         *  @return integer     Error code for the ZIP entry
         *  @uses   SimpleUnzipEntry::$Error
         *  @access public
         *  @since   1.0
         */
        function GetError($in_Index)
        {
            return $this->Entries[$in_Index]->Error;
        } // end of the 'GetError()' method

        /**
         *  Gets error message for the specified ZIP entry
         *
         *  @param  integer     Index of the ZIP entry
         *  @return string      Error message for the ZIP entry
         *  @uses   SimpleUnzipEntry::$ErrorMsg
         *  @access public
         *  @since  1.0
         */
        function GetErrorMsg($in_Index)
        {
            return $this->Entries[$in_Index]->ErrorMsg;
        } // end of the 'GetErrorMsg()' method

        /**
         *  Gets file name for the specified ZIP entry
         *
         *  @param  integer     Index of the ZIP entry
         *  @return string      File name for the ZIP entry
         *  @uses   SimpleUnzipEntry::$Name
         *  @access public
         *  @since  1.0
         */
        function GetName($in_Index)
        {
            return $this->Entries[$in_Index]->Name;
        } // end of the 'GetName()' method

        /**
         *  Gets path of the file for the specified ZIP entry
         *
         *  @param  integer     Index of the ZIP entry
         *  @return string      Path of the file for the ZIP entry
         *  @uses   SimpleUnzipEntry::$Path
         *  @access public
         *  @since  1.0
         */
        function GetPath($in_Index)
        {
            return $this->Entries[$in_Index]->Path;
        } // end of the 'GetPath()' method

        /**
         *  Gets file time for the specified ZIP entry
         *
         *  @param  integer     Index of the ZIP entry
         *  @return integer     File time for the ZIP entry (unix timestamp)
         *  @uses   SimpleUnzipEntry::$Time
         *  @access public
         *  @since  1.0
         */
        function GetTime($in_Index)
        {
            return $this->Entries[$in_Index]->Time;
        } // end of the 'GetTime()' method

        /**
         *  Reads ZIP file and extracts the entries
         *
         *  @param  string              File name of the ZIP archive
         *  @return array               ZIP entry list (see also class variable {@link $Entries $Entries})
         *  @uses   SimpleUnzipEntry    For the entries
         *  @access public
         *  @since  1.0
         */
        function ReadFile($in_FileName)
        {
            $this->Entries = array();

            // Get file parameters
            $this->Name = $in_FileName;
            $this->Time = filemtime($in_FileName);
            $this->Size = filesize($in_FileName);

            // Read file
            $oF = fopen($in_FileName, 'rb');
            $vZ = fread($oF, $this->Size);
            fclose($oF);

// 2003-12-02 - HB >
            // Cut end of central directory
            $aE = explode("\x50\x4b\x05\x06", $vZ);

            // Easiest way, but not sure if format changes
            //$this->Comment = substr($aE[1], 18);

            // Normal way
            $aP = unpack('x16/v1CL', $aE[1]);
            $this->Comment = substr($aE[1], 18, $aP['CL']);

            // Translates end of line from other operating systems
            $this->Comment = strtr($this->Comment, array("\r\n" => "\n",
                                                         "\r"   => "\n"));
// 2003-12-02 - HB <

            // Cut the entries from the central directory
            $aE = explode("\x50\x4b\x01\x02", $vZ);
            // Explode to each part
            $aE = explode("\x50\x4b\x03\x04", $aE[0]);
            // Shift out spanning signature or empty entry
            array_shift($aE);

            // Loop through the entries
            foreach ($aE as $vZ) {
                $aI = array();
                $aI['E']  = 0;
                $aI['EM'] = '';
                // Retrieving local file header information
                $aP = unpack('v1VN/v1GPF/v1CM/v1FT/v1FD/V1CRC/V1CS/V1UCS/v1FNL', $vZ);
                // Check if data is encrypted
                $bE = ($aP['GPF'] & 0x0001) ? TRUE : FALSE;
                $nF = $aP['FNL'];

                // Special case : value block after the compressed data
                if ($aP['GPF'] & 0x0008) {
                    $aP1 = unpack('V1CRC/V1CS/V1UCS', substr($vZ, -12));

                    $aP['CRC'] = $aP1['CRC'];
                    $aP['CS']  = $aP1['CS'];
                    $aP['UCS'] = $aP1['UCS'];

                    $vZ = substr($vZ, 0, -12);
                }

                // Getting stored filename
                $aI['N'] = substr($vZ, 26, $nF);

                if (substr($aI['N'], -1) == '/') {
                    // is a directory entry - will be skipped
                    continue;
                }

                // Truncate full filename in path and filename
                $aI['P'] = dirname($aI['N']);
                $aI['P'] = $aI['P'] == '.' ? '' : $aI['P'];
                $aI['N'] = basename($aI['N']);

                $vZ = substr($vZ, 26 + $nF);

                if (strlen($vZ) != $aP['CS']) {
                  $aI['E']  = 1;
                  $aI['EM'] = 'Compressed size is not equal with the value in header information.';
                } else {
                    if ($bE) {
                        $aI['E']  = 5;
                        $aI['EM'] = 'File is encrypted, which is not supported from this class.';
                    } else {
                        switch($aP['CM']) {
                            case 0: // Stored
                                // Here is nothing to do, the file ist flat.
                                break;

                            case 8: // Deflated
                                $vZ = gzinflate($vZ);
                                break;

                            case 12: // BZIP2
// 2003-12-02 - HB >
                                if (! extension_loaded('bz2')) {
                                    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
                                      @dl('php_bz2.dll');
                                    } else {
                                      @dl('bz2.so');
                                    }
                                }

                                if (extension_loaded('bz2')) {
// 2003-12-02 - HB <
                                    $vZ = bzdecompress($vZ);
// 2003-12-02 - HB >
                                } else {
                                    $aI['E']  = 7;
                                    $aI['EM'] = "PHP BZIP2 extension not available.";
                                }
// 2003-12-02 - HB <

                                break;

                            default:
                              $aI['E']  = 6;
                              $aI['EM'] = "De-/Compression method {$aP['CM']} is not supported.";
                        }

// 2003-12-02 - HB >
                        if (! $aI['E']) {
// 2003-12-02 - HB <
                            if ($vZ === FALSE) {
                                $aI['E']  = 2;
                                $aI['EM'] = 'Decompression of data failed.';
                            } else {
                                if (strlen($vZ) != $aP['UCS']) {
                                    $aI['E']  = 3;
                                    $aI['EM'] = 'Uncompressed size is not equal with the value in header information.';
                                } else {
                                    if (crc32($vZ) != $aP['CRC']) {
                                        $aI['E']  = 4;
                                        $aI['EM'] = 'CRC32 checksum is not equal with the value in header information.';
                                    }
                                }
                            }
// 2003-12-02 - HB >
                        }
// 2003-12-02 - HB <
                    }
                }

                $aI['D'] = $vZ;

                // DOS to UNIX timestamp
                $aI['T'] = mktime(($aP['FT']  & 0xf800) >> 11,
                                  ($aP['FT']  & 0x07e0) >>  5,
                                  ($aP['FT']  & 0x001f) <<  1,
                                  ($aP['FD']  & 0x01e0) >>  5,
                                  ($aP['FD']  & 0x001f),
                                  (($aP['FD'] & 0xfe00) >>  9) + 1980);

                $this->Entries[] = &new SimpleUnzipEntry($aI);
            } // end for each entries

            return $this->Entries;
        } // end of the 'ReadFile()' method
    } // end of the 'SimpleUnzip' class

    /**
     *  Entry of the ZIP file.
     *
     *  @category   phpPublic
     *  @package    File-Formats-ZIP
     *  @subpackage Unzip
     *  @version    1.0
     *  @author     Holger Boskugel <vbwebprofi@gmx.de>
     *  @example    example.unzip.php Two examples
     */
    class SimpleUnzipEntry {
        /**
         *  Data of the file entry
         *
         *  @var    mixed
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $Data = '';

        /**
         *  Error of the file entry
         *
         *  - 0 : No error raised.<BR>
         *  - 1 : Compressed size is not equal with the value in header information.<BR>
         *  - 2 : Decompression of data failed.<BR>
         *  - 3 : Uncompressed size is not equal with the value in header information.<BR>
         *  - 4 : CRC32 checksum is not equal with the value in header information.<BR>
         *  - 5 : File is encrypted, which is not supported from this class.<BR>
         *  - 6 : De-/Compression method ... is not supported.<BR>
         *  - 7 : PHP BZIP2 extension not available.
         *
         *  @var    integer
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $Error = 0;

        /**
         *  Error message of the file entry
         *
         *  @var    string
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $ErrorMsg = '';

        /**
         *  File name of the file entry
         *
         *  @var    string
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $Name = '';

        /**
         *  File path of the file entry
         *
         *  @var    string
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $Path = '';

        /**
         *  File time of the file entry (unix timestamp)
         *
         *  @var    integer
         *  @access public
         *  @see    SimpleUnzipEntry()
         *  @since  1.0
         */
        var $Time = 0;

        /**
         *  Contructor of the class
         *
         *  @param  array               Entry datas
         *  @return SimpleUnzipEntry    Instanced class
         *  @access public
         *  @since  1.0
         */
        function SimpleUnzipEntry($in_Entry)
        {
            $this->Data     = $in_Entry['D'];
            $this->Error    = $in_Entry['E'];
            $this->ErrorMsg = $in_Entry['EM'];
            $this->Name     = $in_Entry['N'];
            $this->Path     = $in_Entry['P'];
            $this->Time     = $in_Entry['T'];
        } // end of the 'SimpleUnzipEntry' constructor
    } // end of the 'SimpleUnzipEntry' class
?>
