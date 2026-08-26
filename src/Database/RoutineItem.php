<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use function count;
use function max;

final class RoutineItem
{
    /**
     * @param array<string>   $paramDir
     * @param array<string>   $paramName
     * @param array<string>   $paramType
     * @param array<string>   $paramLength
     * @param array<string>   $paramOptsNum
     * @param array<string>   $paramOptsText
     * @param array<string[]> $paramLengthArray
     */
    public function __construct(
        public string $name,
        public string $originalName,
        public string $returnLength,
        public string $returnOptsNum,
        public string $returnOptsText,
        public string $definition,
        public string $comment,
        public string $definer,
        public RoutineType $type,
        public RoutineType $originalType,
        public int $numParams = 0,
        public array $paramDir = [],
        public array $paramName = [],
        public array $paramType = [],
        public array $paramLength = [],
        public array $paramOptsNum = [],
        public array $paramOptsText = [],
        public string $returnType = '',
        public bool $isDeterministic = false,
        public bool $securityTypeDefiner = false,
        public bool $securityTypeInvoker = false,
        public string $sqlDataAccess = '',
        public array $paramLengthArray = [],
    ) {
    }

    /**
     * @param array<string> $paramsName
     * @param array<string> $paramsType
     * @param array<string> $paramsLength
     * @param array<string> $paramsOptsNum
     * @param array<string> $paramsOptsText
     * @param array<string> $paramsDir
     */
    public function setParams(
        array $paramsName,
        array $paramsType,
        array $paramsLength,
        array $paramsOptsNum,
        array $paramsOptsText,
        array $paramsDir = [],
    ): void {
        $this->numParams = max(
            count($paramsName),
            count($paramsType),
            count($paramsLength),
            count($paramsOptsNum),
            count($paramsOptsText),
        );
        $this->paramName = $paramsName;
        $this->paramType = $paramsType;
        $this->paramLength = $paramsLength;
        $this->paramOptsNum = $paramsOptsNum;
        $this->paramOptsText = $paramsOptsText;
        $this->paramDir = $paramsDir;
    }
}
