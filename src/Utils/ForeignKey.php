<?php

declare(strict_types=1);

namespace PhpMyAdmin\Utils;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;

use function str_starts_with;
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
        if ($engine === 'INNODB' || $engine === 'PBXT') {
            return true;
        }

        if ($engine === 'NDBCLUSTER' || $engine === 'NDB') {
            $ndbver = strtolower(
                DatabaseInterface::getInstance()->fetchValue('SELECT @@ndb_version_string') ?: '',
            );
            if (str_starts_with($ndbver, 'ndb-')) {
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
        $config = Config::getInstance();
        if ($config->settings['DefaultForeignKeyChecks'] === 'enable') {
            return true;
        }

        if ($config->settings['DefaultForeignKeyChecks'] === 'disable') {
            return false;
        }

        return DatabaseInterface::getInstance()->getVariable('FOREIGN_KEY_CHECKS') === 'ON';
    }

    /**
     * Handle foreign key check request
     */
    public static function handleDisableCheckInit(): bool
    {
        $dbi = DatabaseInterface::getInstance();
        $defaultCheckValue = $dbi->getVariable('FOREIGN_KEY_CHECKS') === 'ON';
        if (isset($_REQUEST['fk_checks'])) {
            if (empty($_REQUEST['fk_checks'])) {
                // Disable foreign key checks
                $dbi->setVariable('FOREIGN_KEY_CHECKS', 'OFF');
            } else {
                // Enable foreign key checks
                $dbi->setVariable('FOREIGN_KEY_CHECKS', 'ON');
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
        DatabaseInterface::getInstance()->setVariable('FOREIGN_KEY_CHECKS', $defaultCheckValue ? 'ON' : 'OFF');
    }
}
