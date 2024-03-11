<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function date;
use function md5;
use function preg_match;
use function preg_replace;

final class InsertEditColumn
{
    public readonly string|null $default;
    public readonly string $md5;
    /**
     * trueType contains only the type (stops at first bracket)
     */
    public readonly string $trueType;
    public readonly string $pmaType;
    public readonly int $length;
    public readonly bool $firstTimestamp;

    public function __construct(
        public readonly string $field,
        public readonly string $type,
        public readonly bool $isNull,
        public readonly string $key,
        string|null $default,
        public readonly string $extra,
        int $columnLength,
        public readonly bool $isBinary,
        public readonly bool $isBlob,
        public readonly bool $isChar,
        bool $insertMode,
    ) {
        if (
            $this->type === 'datetime'
            && ! $this->isNull
            && $default === null
            && $insertMode
        ) {
            $this->default = date('Y-m-d H:i:s');
        } else {
            $this->default = $default;
        }

        $this->md5 = md5($this->field);
        $this->trueType = preg_replace('@\(.*@s', '', $this->type);
        // length is unknown for geometry fields,
        // make enough space to edit very simple WKTs
        if ($columnLength === -1) {
            $columnLength = 30;
        }

        $this->length = preg_match('@float|double@', $this->type) ? 100 : $columnLength;
        $this->pmaType = match ($this->trueType) {
            'set', 'enum' => $this->trueType,
            default => $this->type,
        };
        /**
         * TODO: This property is useless at the moment.
         * It seems like a long time ago before refactoring into classes,
         * this kept track of how many timestamps are in the table.
         */

        /**
         * Fix: By setting 'firstTimestamp' to false, we no longer make the assumption
         * that all fields of type "timestamp" should be set to NOW().
         * This resolves the issue of incorrectly updating "timestamp" fields that do not have
         * a default value of "CURRENT_TIMESTAMP" or an "extra" attribute of "ON UPDATE CURRENT_TIMESTAMP".
         */
        $this->firstTimestamp = false; // $this->trueType === 'timestamp';
    }
}
