<?php
/**
 * Common functions for generating lists of Routines, Triggers and Events.
 */
declare(strict_types=1);

namespace PhpMyAdmin\Rte;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Utils\Routine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function sprintf;

/**
 * PhpMyAdmin\Rte\RteList class
 */
class RteList
{
    /** @var Template */
    public $template;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi DatabaseInterface object
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->template = new Template();
    }

    /**
     * Creates the contents for a row in the list of routines
     *
     * @param array  $routine  An array of routine data
     * @param string $rowClass Additional class
     *
     * @return string HTML code of a row for the list of routines
     */
    public function getRoutineRow(array $routine, $rowClass = '')
    {
        global $db, $table;

        $sqlDrop = sprintf(
            'DROP %s IF EXISTS %s',
            $routine['type'],
            Util::backquote($routine['name'])
        );

        // this is for our purpose to decide whether to
        // show the edit link or not, so we need the DEFINER for the routine
        $where = 'ROUTINE_SCHEMA ' . Util::getCollateForIS() . '='
            . "'" . $this->dbi->escapeString($db) . "' "
            . "AND SPECIFIC_NAME='" . $this->dbi->escapeString($routine['name']) . "'"
            . "AND ROUTINE_TYPE='" . $this->dbi->escapeString($routine['type']) . "'";
        $query = 'SELECT `DEFINER` FROM INFORMATION_SCHEMA.ROUTINES WHERE ' . $where . ';';
        $routineDefiner = $this->dbi->fetchValue($query);

        $currentUser = $this->dbi->getCurrentUser();

        // Since editing a procedure involved dropping and recreating, check also for
        // CREATE ROUTINE privilege to avoid lost procedures.
        $hasEditPrivilege = (Util::currentUserHasPrivilege('CREATE ROUTINE', $db)
            && $currentUser == $routineDefiner) || $this->dbi->isSuperuser();

        // There is a problem with Util::currentUserHasPrivilege():
        // it does not detect all kinds of privileges, for example
        // a direct privilege on a specific routine. So, at this point,
        // we show the Execute link, hoping that the user has the correct rights.
        // Also, information_schema might be hiding the ROUTINE_DEFINITION
        // but a routine with no input parameters can be nonetheless executed.

        // Check if the routine has any input parameters. If it does,
        // we will show a dialog to get values for these parameters,
        // otherwise we can execute it directly.

        $definition = $this->dbi->getDefinition(
            $db,
            $routine['type'],
            $routine['name']
        );
        $hasExecutePrivilege = Util::currentUserHasPrivilege('EXECUTE', $db);
        $executeAction = '';

        if ($definition !== null) {
            $parser = new Parser($definition);

            /**
             * @var CreateStatement $stmt
             */
            $stmt = $parser->statements[0];

            $params = Routine::getParameters($stmt);

            if ($hasExecutePrivilege) {
                $executeAction = 'execute_routine';
                for ($i = 0; $i < $params['num']; $i++) {
                    if ($routine['type'] == 'PROCEDURE'
                        && $params['dir'][$i] == 'OUT'
                    ) {
                        continue;
                    }
                    $executeAction = 'execute_dialog';
                    break;
                }
            }
        }

        $hasExportPrivilege = (Util::currentUserHasPrivilege('CREATE ROUTINE', $db)
            && $currentUser == $routineDefiner) || $this->dbi->isSuperuser();

        return $this->template->render('rte/routines/row', [
            'db' => $db,
            'table' => $table,
            'sql_drop' => $sqlDrop,
            'routine' => $routine,
            'row_class' => $rowClass,
            'has_edit_privilege' => $hasEditPrivilege,
            'has_export_privilege' => $hasExportPrivilege,
            'has_execute_privilege' => $hasExecutePrivilege,
            'execute_action' => $executeAction,
        ]);
    }

    /**
     * Creates the contents for a row in the list of triggers
     *
     * @param array  $trigger  An array of routine data
     * @param string $rowClass Additional class
     *
     * @return string HTML code of a cell for the list of triggers
     */
    public function getTriggerRow(array $trigger, $rowClass = '')
    {
        global $db, $table;

        return $this->template->render('rte/triggers/row', [
            'db' => $db,
            'table' => $table,
            'trigger' => $trigger,
            'has_drop_privilege' => Util::currentUserHasPrivilege('TRIGGER', $db),
            'has_edit_privilege' => Util::currentUserHasPrivilege('TRIGGER', $db, $table),
            'row_class' => $rowClass,
        ]);
    }

    /**
     * Creates the contents for a row in the list of events
     *
     * @param array  $event    An array of routine data
     * @param string $rowClass Additional class
     *
     * @return string HTML code of a cell for the list of events
     */
    public function getEventRow(array $event, $rowClass = '')
    {
        global $db, $table;

        $sqlDrop = sprintf(
            'DROP EVENT IF EXISTS %s',
            Util::backquote($event['name'])
        );

        return $this->template->render('rte/events/row', [
            'db' => $db,
            'table' => $table,
            'event' => $event,
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
            'sql_drop' => $sqlDrop,
            'row_class' => $rowClass,
        ]);
    }
}
