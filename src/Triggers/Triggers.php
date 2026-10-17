<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function array_column;
use function array_multisort;
use function count;
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
    private const TIMING = ['BEFORE', 'AFTER'];

    private const EVENTS = ['INSERT', 'UPDATE', 'DELETE'];

    /** @var list<string> */
    private array $errors = [];

    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /** @return list<array<string|null>> */
    private static function fetchTriggerInfo(DatabaseInterface $dbi, string $db, string $table): array
    {
        if (! Config::getInstance()->selectedServer['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaTriggersRequest(
                Util::getCollateForIS($dbi),
                $dbi->quoteString($db),
                $table === '' ? null : $dbi->quoteString($table),
            );
        } else {
            $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db);
            if ($table !== '') {
                $query .= ' LIKE ' . $dbi->quoteString($table) . ';';
            }
        }

        return $dbi->fetchResultSimple($query);
    }

    /**
     * Handles editor requests for adding or editing an item
     */
    public function handleEditor(string $itemQuery): string
    {
        $sqlQuery = '';

        // set by getQueryFromRequest()
        if ($this->errors === []) {
            // Execute the created query
            if (! empty($_POST['editor_process_edit'])) {
                // Backup the old trigger, in case something goes wrong
                $trigger = $this->getTriggerByName(Current::$database, Current::$table, $_POST['item_original_name']);
                $createItem = $trigger->getCreateSql('');
                $dropItem = $trigger->getDropSql();
                $result = $this->dbi->tryQuery($dropItem);
                if (! $result) {
                    $this->errors[] = $this->getErrorForFailedQuery($dropItem);
                } else {
                    $result = $this->dbi->tryQuery($itemQuery);
                    if (! $result) {
                        $this->errors[] = $this->getErrorForFailedQuery($itemQuery);
                        // We dropped the old item, but were unable to create the
                        // new one. Try to restore the backup query.
                        $result = $this->dbi->tryQuery($createItem);

                        if (! $result) {
                            // OMG, this is really bad! We dropped the query,
                            // failed to create a new one
                            // and now even the backup query does not execute!
                            // This should not happen, but we better handle
                            // this just in case.
                            $this->errors[] = __('Sorry, we failed to restore the dropped trigger.') . '<br>'
                                . __('The backed up query was:')
                                . '"' . htmlspecialchars($createItem) . '"<br>'
                                . __('MySQL said: ') . $this->dbi->getError();
                        }
                    } else {
                        Current::$message = Message::success(
                            __('Trigger %1$s has been modified.'),
                            [Util::backquote($_POST['item_name'])],
                        );
                        $sqlQuery = $dropItem . $itemQuery;
                    }
                }
            } else {
                // 'Add a new item' mode
                $result = $this->dbi->tryQuery($itemQuery);
                if (! $result) {
                    $this->errors[] = $this->getErrorForFailedQuery($itemQuery);
                } else {
                    Current::$message = Message::success(
                        __('Trigger %1$s has been created.'),
                        [Util::backquote($_POST['item_name'])],
                    );
                    $sqlQuery = $itemQuery;
                }
            }
        }

        if ($this->errors !== []) {
            Current::$message = Message::error(
                '<b>'
                . __(
                    'One or more errors have occurred while processing your request:',
                )
                . '</b>',
            );
            Current::$message->addHtml('<ul>');
            foreach ($this->errors as $string) {
                Current::$message->addHtml('<li>' . $string . '</li>');
            }

            Current::$message->addHtml('</ul>');
        }

        return Generator::getMessage(Current::$message ?? Message::success(), $sqlQuery);
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
    public function getQueryFromRequest(ServerRequest $request): string
    {
        $query = 'CREATE ';
        $itemDefiner = $request->getParsedBodyParamAsString('item_definer', '');
        if ($itemDefiner !== '') {
            if (str_contains($itemDefiner, '@')) {
                $arr = explode('@', $itemDefiner);
                $query .= 'DEFINER=' . Util::backquote($arr[0]);
                $query .= '@' . Util::backquote($arr[1]) . ' ';
            } else {
                $this->errors[] = __('The definer must be in the "username@hostname" format!');
            }
        }

        $query .= 'TRIGGER ';
        $itemName = $request->getParsedBodyParamAsString('item_name', '');
        if ($itemName !== '') {
            $query .= Util::backquote($itemName) . ' ';
        } else {
            $this->errors[] = __('You must provide a trigger name!');
        }

        $itemTiming = $request->getParsedBodyParamAsString('item_timing', '');
        if (in_array($itemTiming, self::TIMING, true)) {
            $query .= $itemTiming . ' ';
        } else {
            $this->errors[] = __('You must provide a valid timing for the trigger!');
        }

        $itemEvent = $request->getParsedBodyParamAsString('item_event', '');
        if (in_array($itemEvent, self::EVENTS, true)) {
            $query .= $itemEvent . ' ';
        } else {
            $this->errors[] = __('You must provide a valid event for the trigger!');
        }

        $itemTable = $request->getParsedBodyParamAsString('item_table', '');
        $query .= 'ON ';
        if (in_array($itemTable, $this->dbi->getTables(Current::$database), true)) {
            $query .= Util::backquote($itemTable);
        } else {
            $this->errors[] = __('You must provide a valid table name!');
        }

        $query .= ' FOR EACH ROW ';
        $itemDefinition = $request->getParsedBodyParamAsString('item_definition', '');
        if ($itemDefinition !== '') {
            $query .= $itemDefinition;
        } else {
            $this->errors[] = __('You must provide a trigger definition.');
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
        $tables = $this->dbi->fetchSingleColumn($query);
        Assert::allStringNotEmpty($tables);

        return $tables;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    private function getErrorForFailedQuery(string $query): string
    {
        return sprintf(
            '<div class="mb-3">' . __('The following query has failed: "%s"') . '</div>',
            htmlspecialchars($query),
        ) . __('MySQL said: ') . $this->dbi->getError();
    }
}
