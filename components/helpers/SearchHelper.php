<?php
declare(strict_types=1);

namespace app\components\helpers;

/**
 * SearchHelper
 * Utilities for search normalization
 */
class SearchHelper
{
    /**
     * Normalize patente for search: removes hyphens and converts to uppercase
     * Example: "ABC-123" or "abc123" both become "ABC123"
     *
     * @param string $patente
     * @return string Normalized patente
     */
    public static function normalizePatente(string $patente): string
    {
        return strtoupper(str_replace('-', '', trim($patente)));
    }

    /**
     * Format patente for display: adds hyphen if not present
     * Example: "ABC123" becomes "ABC-123"
     *
     * @param string $patente
     * @return string Formatted patente
     */
    public static function formatPatente(string $patente): string
    {
        $clean = strtoupper(str_replace('-', '', trim($patente)));
        if (strlen($clean) === 6) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3);
        }
        return $clean;
    }

    /**
     * Search in multiple fields with case-insensitive and partial matching
     *
     * @param string $needle Search term
     * @param array $haystack Array with keys to search
     * @return bool True if any value contains the search term (case-insensitive)
     */
    public static function multiFieldSearch(string $needle, array $haystack): bool
    {
        $needle = strtolower(trim($needle));
        foreach ($haystack as $value) {
            if (stripos((string)$value, $needle) !== false) {
                return true;
            }
        }
        return false;
    }
}
