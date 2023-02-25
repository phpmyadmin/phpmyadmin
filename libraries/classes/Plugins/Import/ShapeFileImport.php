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
     */
    public function readSHP(int $bytes): string|false
    {
        return ImportShp::readFromBuffer($bytes);
    }

    /**
     * Checks whether file is at EOF
     */
    public function eofSHP(): bool
    {
        $GLOBALS['eof'] ??= null;

        return (bool) $GLOBALS['eof'];
    }
}
