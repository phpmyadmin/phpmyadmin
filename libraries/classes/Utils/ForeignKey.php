<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use function strtolower;
use function strtoupper;
use function substr;
use function version_compare;

final class ForeignKey
{
    /**
     * Verifies if this table's engine supports foreign keys
     *
     * @param string $engine engine
     */
    public static function isSupported(string $engine): bool
    {
        $engine = strtoupper($engine);
        if (($engine === 'INNODB') || ($engine === 'PBXT')) {
            return true;
        }

        if ($engine === 'NDBCLUSTER' || $engine === 'NDB') {
            $ndbver = strtolower(
                $GLOBALS['dbi']->fetchValue('SELECT @@ndb_version_string') ?: '',
            );
            if (substr($ndbver, 0, 4) === 'ndb-') {
                $ndbver = substr($ndbver, 4);
            }

            return version_compare($ndbver, '7.3', '>=');
        }

        return false;
    }

    /**
     * Is Foreign key check enabled?
     */
    public static function isCheckEnabled(): bool
    {
        if ($GLOBALS['cfg']['DefaultForeignKeyChecks'] === 'enable') {
            return true;
        }

        if ($GLOBALS['cfg']['DefaultForeignKeyChecks'] === 'disable') {
            return false;
        }

        return $GLOBALS['dbi']->getVariable('FOREIGN_KEY_CHECKS') === 'ON';
    }

    /**
     * Handle foreign key check request
     */
    public static function handleDisableCheckInit(): bool
    {
        $defaultCheckValue = $GLOBALS['dbi']->getVariable('FOREIGN_KEY_CHECKS') === 'ON';
        if (isset($_REQUEST['fk_checks'])) {
            if (empty($_REQUEST['fk_checks'])) {
                // Disable foreign key checks
                $GLOBALS['dbi']->setVariable('FOREIGN_KEY_CHECKS', 'OFF');
            } else {
                // Enable foreign key checks
                $GLOBALS['dbi']->setVariable('FOREIGN_KEY_CHECKS', 'ON');
            }
        }

        return $defaultCheckValue;
    }

    /**
     * Cleanup changes done for foreign key check
     *
     * @param bool $defaultCheckValue original value for 'FOREIGN_KEY_CHECKS'
     */
    public static function handleDisableCheckCleanup(bool $defaultCheckValue): void
    {
        $GLOBALS['dbi']->setVariable('FOREIGN_KEY_CHECKS', $defaultCheckValue ? 'ON' : 'OFF');
    }
}
