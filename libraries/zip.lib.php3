<?php
/* $Id$ */

/**
 * Zip file creation class. 
 * Makes zip files.
 *
 * Based on :
 *
 *  http://www.zend.com/codex.php3?id=535&single=1
 *  By Eric Mueller (eric@themepark.com)
 * 
 *  http://www.zend.com/codex.php3?id=470&single=1 
 *  by Denis125 (webmaster@atlant.ru)
 *
 * Official ZIP file format: http://www.pkware.com/appnote.txt 
 */

/**
 * Zip file class
 *
 * @param 
 *
 * @return
 */

class zipfile  
{  

    var $datasec = array(); 				// array to store compressed data 
    var $ctrl_dir = array(); 				// central directory   
    var $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00"; // end of Central directory record 
    var $old_offset = 0; 

    function add_file($data, $name)   

    // adds "file" to archive   
    // $data - file contents 
    // $name - name of file in archive. Add path if your want 

    {  
        $name = str_replace("\\", "/", $name);  

        $fr = "\x50\x4b\x03\x04"; 
        $fr .= "\x14\x00";    				// ver needed to extract 
        $fr .= "\x00\x00";    				// gen purpose bit flag 
        $fr .= "\x08\x00";    				// compression method 
        $fr .= "\x00\x00\x00\x00"; 			// last mod time and date 

        $unc_len = strlen($data);  
        $crc = crc32($data);  
        $zdata = gzcompress($data);  
        $zdata = substr( substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug 
        $c_len = strlen($zdata);  
        $fr .= pack("V",$crc); 				// crc32 
        $fr .= pack("V",$c_len); 			// compressed filesize 
        $fr .= pack("V",$unc_len); 			// uncompressed filesize 
        $fr .= pack("v", strlen($name) ); 		// length of filename 
        $fr .= pack("v", 0 ); 				// extra field length 
        $fr .= $name;  
        // end of "local file header" segment 
         
        // "file data" segment 
        
        $fr .= $zdata;  

        // "data descriptor" segment (optional but necessary if archive is not served as file)
        
        $fr .= pack("V",$crc); 				// crc32 
        $fr .= pack("V",$c_len); 			// compressed filesize 
        $fr .= pack("V",$unc_len); 			// uncompressed filesize 

        // add this entry to array 
        
        $this -> datasec[] = $fr; 

        $new_offset = strlen(implode("", $this->datasec)); 

        // now add to central directory record 
        
        $cdrec = "\x50\x4b\x01\x02"; 
        $cdrec .="\x00\x00";    			// version made by 
        $cdrec .="\x14\x00";    			// version needed to extract 
        $cdrec .="\x00\x00";    			// gen purpose bit flag 
        $cdrec .="\x08\x00";    			// compression method 
        $cdrec .="\x00\x00\x00\x00"; 			// last mod time & date 
        $cdrec .= pack("V",$crc); 			// crc32 
        $cdrec .= pack("V",$c_len); 			// compressed filesize 
        $cdrec .= pack("V",$unc_len); 			// uncompressed filesize 
        $cdrec .= pack("v", strlen($name) ); 		// length of filename 
        $cdrec .= pack("v", 0 ); 			// extra field length   
        $cdrec .= pack("v", 0 ); 			// file comment length 
        $cdrec .= pack("v", 0 ); 			// disk number start 
        $cdrec .= pack("v", 0 ); 			// internal file attributes 
        $cdrec .= pack("V", 32 ); 			// external file attributes - 'archive' bit set 

        $cdrec .= pack("V", $this -> old_offset ); 	// relative offset of local header 
        $this -> old_offset = $new_offset; 

        $cdrec .= $name;
        
        // optional extra field, file comment goes here 
        // save to central directory 
        
        $this -> ctrl_dir[] = $cdrec;  
    } 

    function file() { 					// dump out file   
        $data = implode("", $this -> datasec);  
        $ctrldir = implode("", $this -> ctrl_dir);  

        return   
            $data.  
            $ctrldir.  
            $this -> eof_ctrl_dir.  
            pack("v", sizeof($this -> ctrl_dir)).	// total # of entries "on this disk" 
            pack("v", sizeof($this -> ctrl_dir)).	// total # of entries overall 
            pack("V", strlen($ctrldir)).		// size of central dir 
            pack("V", strlen($data)).			// offset to start of central dir 
            "\x00\x00";					// .zip file comment length 
    } 
}
?>