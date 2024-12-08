<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use function preg_match;
use function preg_replace;
use function trim;

class QueryValidator
{
    /**
     * Validates and processes a SQL query before execution
     *
     * @param string                              $query   The SQL query to validate
     * @param string|null                         $table   Selected table name
     * @param array<string, string|int|bool|null> $context Additional context like database name
     *
     * @return array{isValid: bool, query: string, error: string|null}
     */
    public function validateQuery(
        string $query,
        string|null $table,
        array $context = [],
    ): array {
        // Check if query contains metadata requests without table context
        if ($this->hasMetadataRequests($query) && $table === null) {
            return [
                'isValid' => false,
                'query' => $query,
                'error' => 'Table must be selected for metadata queries',
            ];
        }

        // Process and clean the query
        $processedQuery = $this->processQuery($query, $table, $context);

        // Check for SQL injection attempts in metadata queries
        if ($table !== null && $this->hasMetadataRequests($query) && $this->containsSuspiciousPatterns($query)) {
            return [
                'isValid' => false,
                'query' => $query,
                'error' => 'Invalid query',
            ];
        }

        return [
            'isValid' => true,
            'query' => $processedQuery,
            'error' => null,
        ];
    }

    /**
     * Checks if query contains metadata requests
     */
    private function hasMetadataRequests(string $query): bool
    {
        $metadataPatterns = [
            '/SHOW\s+COLUMNS\s+FROM/i',
            '/SHOW\s+INDEXES\s+FROM/i',
            '/SHOW\s+INDEX\s+FROM/i',
            '/SHOW\s+KEYS\s+FROM/i',
        ];

        foreach ($metadataPatterns as $pattern) {
            $result = preg_match($pattern, $query);
            if ($result === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process and clean the query
     *
     * @param string                              $query   The SQL query to process
     * @param string|null                         $table   Selected table name
     * @param array<string, string|int|bool|null> $context Additional context like database name
     */
    private function processQuery(
        string $query,
        string|null $table,
        array $context,
    ): string {
        // Remove any malformed metadata queries when no table is selected
        if ($table === null) {
            $cleanedQuery = preg_replace('/SHOW\s+(COLUMNS|INDEXES|INDEX|KEYS)\s+FROM\s+[^;]+;?/i', '', $query);
            if ($cleanedQuery === null) {
                return $query; // Return original if replacement fails
            }

            $query = $cleanedQuery;
        }

        // Clean up multiple semicolons and whitespace
        $finalQuery = preg_replace('/;+\s*$/', ';', $query);

        return trim($finalQuery ?? $query);
    }

    /**
     * Check for suspicious patterns that might indicate SQL injection attempts
     */
    private function containsSuspiciousPatterns(string $query): bool
    {
        $suspiciousPatterns = [
            '/;\s*DROP\s+/i',
            '/;\s*DELETE\s+/i',
            '/;\s*UPDATE\s+/i',
            '/--\s*$/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            $result = preg_match($pattern, $query);
            if ($result === 1) {
                return true;
            }
        }

        return false;
    }
}
