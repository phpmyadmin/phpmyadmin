<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use function preg_match;
use function preg_replace;
use function trim;

final class QueryValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $query,
        public readonly ?string $error,
    ) {}

    public static function valid(string $query): self
    {
        return new self(true, $query, null);
    }

    public static function invalid(string $query, string $error): self
    {
        return new self(false, $query, $error);
    }
}

final class QueryValidator
{
    public function validateQuery(
        string $query,
        ?string $table,
        array $context = [],
    ): QueryValidationResult {
        if ($this->hasInvalidMetadataRequest($query, $table)) {
            return QueryValidationResult::invalid(
                $query,
                'Table must be selected for metadata queries'
            );
        }

        $cleanQuery = $this->removeMetadataQueriesWithoutTable($query, $table);
        $cleanQuery = $this->normalizeQueryTermination($cleanQuery);

        if ($this->hasMetadataRequestWithSqlInjection($query, $table)) {
            return QueryValidationResult::invalid($query, 'Invalid query - potential SQL injection detected');
        }

        return QueryValidationResult::valid($cleanQuery);
    }

    private function hasInvalidMetadataRequest(string $query, ?string $table): bool
    {
        return $this->containsMetadataRequest($query) && $table === null;
    }

    private function containsMetadataRequest(string $query): bool
    {
        return preg_match(
            '/SHOW\s+(COLUMNS|INDEXES?|KEYS)\s+FROM/i',
            $query
        ) === 1;
    }

    private function removeMetadataQueriesWithoutTable(string $query, ?string $table): string
    {
        if ($table !== null) {
            return $query;
        }

        $cleanedQuery = preg_replace(
            '/SHOW\s+(COLUMNS|INDEXES?|KEYS)\s+FROM\s+[^;]+;?/i',
            '',
            $query
        );

        return $cleanedQuery ?? $query;
    }

    private function normalizeQueryTermination(string $query): string
    {
        $normalized = preg_replace('/;+\s*$/', ';', $query);
        return trim($normalized ?? $query);
    }

    private function hasMetadataRequestWithSqlInjection(string $query, ?string $table): bool
    {
        if ($table === null || !$this->containsMetadataRequest($query)) {
            return false;
        }

        // Combining patterns into a single regex for better performance
        return preg_match(
            '/(?:;\s*(?:DROP|DELETE|UPDATE)\s+|--\s*$)/i',
            $query
        ) === 1;
    }
}
