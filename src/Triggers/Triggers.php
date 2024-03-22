<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function array_column;
use function array_multisort;
use function explode;
use function htmlspecialchars;
use function in_array;
use function sprintf;
use function str_contains;

use const SORT_ASC;

/**
 * Functions for trigger management.
 */
class Triggers
{
    /** @var array<int, string> */
    private array $time = ['BEFORE', 'AFTER'];

    private const EVENTS = ['INSERT', 'UPDATE', 'DELETE'];

    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /** @return mixed[][] */
    private static function fetchTriggerInfo(DatabaseInterface $dbi, string $db, string $table): array
    {
        if (! Config::getInstance()->selectedServer['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaTriggersRequest(
                $dbi->quoteString($db),
                $table === '' ? null : $dbi->quoteString($table),
            );
        } else {
            $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db);
            if ($table !== '') {
                $query .= ' LIKE ' . $dbi->quoteString($table) . ';';
            }
        }

        /** @var mixed[][] $triggers */
        $triggers = $dbi->fetchResult($query);

        return $triggers;
    }

    /**
     * Handles editor requests for adding or editing an item
     */
    public function handleEditor(): string
    {
        $sqlQuery = '';

        $itemQuery = $this->getQueryFromRequest();

        // set by getQueryFromRequest()
        if ($GLOBALS['errors'] === []) {
            // Execute the created query
            if (! empty($_POST['editor_process_edit'])) {
                // Backup the old trigger, in case something goes wrong
                $trigger = $this->getTriggerByName(Current::$database, Current::$table, $_POST['item_original_name']);
                $createItem = $trigger->getCreateSql('');
                $dropItem = $trigger->getDropSql();
                $result = $this->dbi->tryQuery($dropItem);
                if (! $result) {
                    $GLOBALS['errors'][] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($dropItem),
                    )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    $result = $this->dbi->tryQuery($itemQuery);
                    if (! $result) {
                        $GLOBALS['errors'][] = sprintf(
                            __('The following query has failed: "%s"'),
                            htmlspecialchars($itemQuery),
                        )
                            . '<br>'
                            . __('MySQL said: ') . $this->dbi->getError();
                        // We dropped the old item, but were unable to create the
                        // new one. Try to restore the backup query.
                        $result = $this->dbi->tryQuery($createItem);

                        if (! $result) {
                            // OMG, this is really bad! We dropped the query,
                            // failed to create a new one
                            // and now even the backup query does not execute!
                            // This should not happen, but we better handle
                            // this just in case.
                            $GLOBALS['errors'][] = __('Sorry, we failed to restore the dropped trigger.') . '<br>'
                                . __('The backed up query was:')
                                . '"' . htmlspecialchars($createItem) . '"<br>'
                                . __('MySQL said: ') . $this->dbi->getError();
                        }
                    } else {
                        $GLOBALS['message'] = Message::success(
                            __('Trigger %1$s has been modified.'),
                        );
                        $GLOBALS['message']->addParam(
                            Util::backquote($_POST['item_name']),
                        );
                        $sqlQuery = $dropItem . $itemQuery;
                    }
                }
            } else {
                // 'Add a new item' mode
                $result = $this->dbi->tryQuery($itemQuery);
                if (! $result) {
                    $GLOBALS['errors'][] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($itemQuery),
                    )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    $GLOBALS['message'] = Message::success(
                        __('Trigger %1$s has been created.'),
                    );
                    $GLOBALS['message']->addParam(
                        Util::backquote($_POST['item_name']),
                    );
                    $sqlQuery = $itemQuery;
                }
            }
        }

        if ($GLOBALS['errors'] !== []) {
            $GLOBALS['message'] = Message::error(
                '<b>'
                . __(
                    'One or more errors have occurred while processing your request:',
                )
                . '</b>',
            );
            $GLOBALS['message']->addHtml('<ul>');
            foreach ($GLOBALS['errors'] as $string) {
                $GLOBALS['message']->addHtml('<li>' . $string . '</li>');
            }

            $GLOBALS['message']->addHtml('</ul>');
        }

        return Generator::getMessage($GLOBALS['message'], $sqlQuery);
    }

    /** @return Trigger|null Data necessary to create the editor. */
    public function getTriggerByName(string $db, string $table, string $name): Trigger|null
    {
        $triggers = self::getDetails($this->dbi, $db, $table);
        foreach ($triggers as $trigger) {
            if ($trigger->name->getName() === $name) {
                return $trigger;
            }
        }

        return null;
    }

    /**
     * Composes the query necessary to create a trigger from an HTTP request.
     *
     * @return string  The CREATE TRIGGER query.
     */
    public function getQueryFromRequest(): string
    {
        $GLOBALS['errors'] ??= null;

        $query = 'CREATE ';
        if (! empty($_POST['item_definer'])) {
            if (str_contains($_POST['item_definer'], '@')) {
                $arr = explode('@', $_POST['item_definer']);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $GLOBALS['errors'][] = __('The definer must be in the "username@hostname" format!');
            }
        }

        $query .= 'TRIGGER ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a trigger name!');
        }

        if (! empty($_POST['item_timing']) && in_array($_POST['item_timing'], $this->time, true)) {
            $query .= $_POST['item_timing'] . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid timing for the trigger!');
        }

        if (! empty($_POST['item_event']) && in_array($_POST['item_event'], self::EVENTS, true)) {
            $query .= $_POST['item_event'] . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid event for the trigger!');
        }

        $query .= 'ON ';
        if (
            ! empty($_POST['item_table'])
            && in_array($_POST['item_table'], $this->dbi->getTables(Current::$database), true)
        ) {
            $query .= Util::backquote($_POST['item_table']);
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid table name!');
        }

        $query .= ' FOR EACH ROW ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $GLOBALS['errors'][] = __('You must provide a trigger definition.');
        }

        return $query;
    }

    /**
     * Returns details about the TRIGGERs for a specific table or database.
     *
     * @return Trigger[]
     */
    public static function getDetails(
        DatabaseInterface $dbi,
        string $db,
        string $table = '',
    ): array {
        $result = [];
        $triggers = self::fetchTriggerInfo($dbi, $db, $table);

        foreach ($triggers as $trigger) {
            $newTrigger = Trigger::tryFromArray($trigger);
            if ($newTrigger === null) {
                continue;
            }

            $result[] = $newTrigger;
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    /** @return list<non-empty-string> */
    public function getTables(string $db): array
    {
        $query = sprintf(
            'SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`TABLES` WHERE `TABLE_SCHEMA`=%s'
            . " AND `TABLE_TYPE` IN ('BASE TABLE', 'SYSTEM VERSIONED')",
            $this->dbi->quoteString($db),
        );
        $tables = $this->dbi->fetchResult($query);
        Assert::allStringNotEmpty($tables);
        Assert::isList($tables);

        return $tables;
    }
}
