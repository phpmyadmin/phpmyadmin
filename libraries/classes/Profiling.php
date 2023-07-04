<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Utils\SessionCache;

/**
 * Statement resource usage.
 */
final class Profiling
{
    public static function isSupported(DatabaseInterface $dbi): bool
    {
        if (SessionCache::has('profiling_supported')) {
            return (bool) SessionCache::get('profiling_supported');
        }

        /**
         * 5.0.37 has profiling but for example, 5.1.20 does not
         * (avoid a trip to the server for MySQL before 5.0.37)
         * and do not set a constant as we might be switching servers
         */
        if ($dbi->fetchValue('SELECT @@have_profiling')) {
            SessionCache::set('profiling_supported', true);

            return true;
        }

        SessionCache::set('profiling_supported', false);

        return false;
    }

    public static function enable(DatabaseInterface $dbi): void
    {
        if (! isset($_SESSION['profiling']) || ! self::isSupported($dbi)) {
            return;
        }

        $dbi->query('SET PROFILING=1;');
    }

    /** @psalm-return list<array{Status: non-empty-string, Duration: numeric-string}> */
    public static function getInformation(DatabaseInterface $dbi): array
    {
        if (! isset($_SESSION['profiling']) || ! self::isSupported($dbi)) {
            return [];
        }

        /** @psalm-var list<array{Status: non-empty-string, Duration: numeric-string}> $profile */
        $profile = $dbi->fetchResult('SHOW PROFILE;');

        return $profile;
    }

    /**
     * Check if profiling was requested and remember it.
     */
    public static function check(DatabaseInterface $dbi, ResponseRenderer $response): void
    {
        if (isset($_REQUEST['profiling']) && self::isSupported($dbi)) {
            $_SESSION['profiling'] = true;
        } elseif (isset($_REQUEST['profiling_form'])) {
            // the checkbox was unchecked
            unset($_SESSION['profiling']);
        }

        if (! isset($_SESSION['profiling'])) {
            return;
        }

        $scripts = $response->getHeader()->getScripts();
        $scripts->addFiles([
            'vendor/chart.umd.js',
            'vendor/jquery/jquery.tablesorter.js',
        ]);
    }
}
