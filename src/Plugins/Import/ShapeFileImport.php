<?php
/**
 * This class extends ShapeFile class to cater the following phpMyAdmin
 * specific requirements.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\ShapeFile\ShapeFile;
use PhpMyAdmin\ShapeFile\ShapeType;

/**
 * ShapeFileImport class
 */
final class ShapeFileImport extends ShapeFile
{
    /** @param mixed[] $boundingBox */
    public function __construct(
        private readonly ImportShp $importShp,
        ShapeType $shapeType,
        array $boundingBox = ['xmin' => 0.0, 'ymin' => 0.0, 'xmax' => 0.0, 'ymax' => 0.0],
        string|null $fileName = null,
    ) {
        parent::__construct($shapeType, $boundingBox, $fileName);
    }

    /**
     * Reads given number of bytes from SHP file
     *
     * @param int $bytes number of bytes
     */
    public function readSHP(int $bytes): string|false
    {
        return $this->importShp->readFromBuffer($bytes);
    }

    /**
     * Checks whether file is at EOF
     */
    public function eofSHP(): bool
    {
        return ImportShp::$eof;
    }
}
