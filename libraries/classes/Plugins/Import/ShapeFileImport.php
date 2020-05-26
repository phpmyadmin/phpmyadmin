<?php
/**
 * This class extends ShapeFile class to cater the following phpMyAdmin
 * specific requirements.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\ShapeFile\ShapeFile;

/**
 * ShapeFileImport class
 */
class ShapeFileImport extends ShapeFile
{
    /**
     * Reads given number of bytes from SHP file
     *
     * @param int $bytes number of bytes
     *
     * @return string|false
     */
    public function readSHP($bytes)
    {
        return ImportShp::readFromBuffer($bytes);
    }

    /**
     * Checks whether file is at EOF
     *
     * @return bool
     */
    public function eofSHP()
    {
        global $eof;

        return $eof;
    }
}
