<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;

use function __;
use function array_column;
use function array_multisort;
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_string;
use function sprintf;
use function str_contains;
use function strtoupper;

use const SORT_ASC;

/**
 * Functions for event management.
 */
class Events
{
    /** @var array<string, array<int, string>> */
    public readonly array $status;

    /** @var array<int, string> */
    public readonly array $type;

    /** @var array<int, string> */
    public readonly array $interval;

    public function __construct(private DatabaseInterface $dbi)
    {
        $this->status = [
            'query' => ['ENABLE', 'DISABLE', 'DISABLE ON SLAVE'],
            'display' => ['ENABLED', 'DISABLED', 'SLAVESIDE_DISABLED'],
        ];
        $this->type = ['RECURRING', 'ONE TIME'];
        $this->interval = [
            'YEAR',
            'QUARTER',
            'MONTH',
            'DAY',
            'HOUR',
            'MINUTE',
            'WEEK',
            'SECOND',
            'YEAR_MONTH',
            'DAY_HOUR',
            'DAY_MINUTE',
            'DAY_SECOND',
            'HOUR_MINUTE',
            'HOUR_SECOND',
            'MINUTE_SECOND',
        ];
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
                $createItem = self::getDefinition($this->dbi, Current::$database, $_POST['item_original_name']);
                $dropItem = 'DROP EVENT IF EXISTS '
                    . Util::backquote($_POST['item_original_name'])
                    . ";\n";
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
                        // We dropped the old item, but were unable to create
                        // the new one. Try to restore the backup query
                        $result = $this->dbi->tryQuery($createItem);
                        if (! $result) {
                            // OMG, this is really bad! We dropped the query,
                            // failed to create a new one
                            // and now even the backup query does not execute!
                            // This should not happen, but we better handle
                            // this just in case.
                            $GLOBALS['errors'][] = __('Sorry, we failed to restore the dropped event.') . '<br>'
                                . __('The backed up query was:')
                                . '"' . htmlspecialchars($createItem) . '"<br>'
                                . __('MySQL said: ') . $this->dbi->getError();
                        }
                    } else {
                        $GLOBALS['message'] = Message::success(
                            __('Event %1$s has been modified.'),
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
                        __('Event %1$s has been created.'),
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

    /**
     * This function will generate the values that are required to for the editor
     *
     * @return mixed[]    Data necessary to create the editor.
     */
    public function getDataFromRequest(): array
    {
        $retval = [];
        $indices = [
            'item_name',
            'item_original_name',
            'item_status',
            'item_execute_at',
            'item_interval_value',
            'item_interval_field',
            'item_starts',
            'item_ends',
            'item_definition',
            'item_preserve',
            'item_comment',
            'item_definer',
        ];
        foreach ($indices as $index) {
            $retval[$index] = $_POST[$index] ?? '';
        }

        $retval['item_type'] = 'ONE TIME';
        $retval['item_type_toggle'] = 'RECURRING';
        if (isset($_POST['item_type']) && $_POST['item_type'] === 'RECURRING') {
            $retval['item_type'] = 'RECURRING';
            $retval['item_type_toggle'] = 'ONE TIME';
        }

        return $retval;
    }

    /**
     * This function will generate the values that are required to complete
     * the "Edit event" form given the name of a event.
     *
     * @param string $name The name of the event.
     *
     * @return mixed[]|null Data necessary to create the editor.
     */
    public function getDataFromName(string $name): array|null
    {
        $retval = [];
        $columns = '`EVENT_NAME`, `STATUS`, `EVENT_TYPE`, `EXECUTE_AT`, '
                 . '`INTERVAL_VALUE`, `INTERVAL_FIELD`, `STARTS`, `ENDS`, '
                 . '`EVENT_DEFINITION`, `ON_COMPLETION`, `DEFINER`, `EVENT_COMMENT`';
        $where = 'EVENT_SCHEMA ' . Util::getCollateForIS() . '=' . $this->dbi->quoteString(Current::$database)
                 . ' AND EVENT_NAME=' . $this->dbi->quoteString($name);
        $query = 'SELECT ' . $columns . ' FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE ' . $where . ';';
        $item = $this->dbi->fetchSingleRow($query);
        if ($item === null || $item === []) {
            return null;
        }

        $retval['item_name'] = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type'] = $item['EVENT_TYPE'];
        if ($retval['item_type'] === 'RECURRING') {
            $retval['item_type_toggle'] = 'ONE TIME';
        } else {
            $retval['item_type_toggle'] = 'RECURRING';
        }

        $retval['item_execute_at'] = $item['EXECUTE_AT'];
        $retval['item_interval_value'] = $item['INTERVAL_VALUE'];
        $retval['item_interval_field'] = $item['INTERVAL_FIELD'];
        $retval['item_starts'] = $item['STARTS'];
        $retval['item_ends'] = $item['ENDS'];
        $retval['item_preserve'] = '';
        if ($item['ON_COMPLETION'] === 'PRESERVE') {
            $retval['item_preserve'] = " checked='checked'";
        }

        $retval['item_definition'] = $item['EVENT_DEFINITION'];
        $retval['item_definer'] = $item['DEFINER'];
        $retval['item_comment'] = $item['EVENT_COMMENT'];

        return $retval;
    }

    /**
     * Composes the query necessary to create an event from an HTTP request.
     *
     * @return string  The CREATE EVENT query.
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

        $query .= 'EVENT ';
        if (! empty($_POST['item_name'])) {
            $query .= Util::backquote($_POST['item_name']) . ' ';
        } else {
            $GLOBALS['errors'][] = __('You must provide an event name!');
        }

        $query .= 'ON SCHEDULE ';
        if (! empty($_POST['item_type']) && in_array($_POST['item_type'], $this->type, true)) {
            if ($_POST['item_type'] === 'RECURRING') {
                if (
                    ! empty($_POST['item_interval_value'])
                    && ! empty($_POST['item_interval_field'])
                    && in_array($_POST['item_interval_field'], $this->interval, true)
                ) {
                    $query .= 'EVERY ' . (int) $_POST['item_interval_value'] . ' ';
                    $query .= $_POST['item_interval_field'] . ' ';
                } else {
                    $GLOBALS['errors'][] = __('You must provide a valid interval value for the event.');
                }

                if (! empty($_POST['item_starts'])) {
                    $query .= 'STARTS ' . $this->dbi->quoteString($_POST['item_starts']) . ' ';
                }

                if (! empty($_POST['item_ends'])) {
                    $query .= 'ENDS ' . $this->dbi->quoteString($_POST['item_ends']) . ' ';
                }
            } elseif (! empty($_POST['item_execute_at'])) {
                $query .= 'AT ' . $this->dbi->quoteString($_POST['item_execute_at']) . ' ';
            } else {
                $GLOBALS['errors'][] = __('You must provide a valid execution time for the event.');
            }
        } else {
            $GLOBALS['errors'][] = __('You must provide a valid type for the event.');
        }

        $query .= 'ON COMPLETION ';
        if (empty($_POST['item_preserve'])) {
            $query .= 'NOT ';
        }

        $query .= 'PRESERVE ';
        if (! empty($_POST['item_status'])) {
            foreach ($this->status['display'] as $key => $value) {
                if ($value == $_POST['item_status']) {
                    $query .= $this->status['query'][$key] . ' ';
                    break;
                }
            }
        }

        if (! empty($_POST['item_comment'])) {
            $query .= 'COMMENT ' . $this->dbi->quoteString($_POST['item_comment']) . ' ';
        }

        $query .= 'DO ';
        if (! empty($_POST['item_definition'])) {
            $query .= $_POST['item_definition'];
        } else {
            $GLOBALS['errors'][] = __('You must provide an event definition.');
        }

        return $query;
    }

    public function getEventSchedulerStatus(): bool
    {
        $state = (string) $this->dbi->fetchValue('SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'', 1);

        return strtoupper($state) === 'ON' || $state === '1';
    }

    /**
     * Returns details about the EVENTs for a specific database.
     *
     * @param string $db   db name
     * @param string $name event name
     *
     * @return array{name:string, type:string, status:string}[] information about EVENTs
     */
    public function getDetails(string $db, string $name = ''): array
    {
        if (! Config::getInstance()->selectedServer['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaEventsRequest(
                $this->dbi->quoteString($db),
                $name === '' ? null : $this->dbi->quoteString($name),
            );
        } else {
            $query = 'SHOW EVENTS FROM ' . Util::backquote($db);
            if ($name !== '') {
                $query .= ' WHERE `Name` = ' . $this->dbi->quoteString($name);
            }
        }

        $result = [];
        $events = $this->dbi->fetchResult($query);

        /** @var string[] $event */
        foreach ($events as $event) {
            $result[] = ['name' => $event['Name'], 'type' => $event['Type'], 'status' => $event['Status']];
        }

        // Sort results by name
        $name = array_column($result, 'name');
        array_multisort($name, SORT_ASC, $result);

        return $result;
    }

    public static function getDefinition(DatabaseInterface $dbi, string $db, string $name): string|null
    {
        $result = $dbi->fetchValue(
            'SHOW CREATE EVENT ' . Util::backquote($db) . '.' . Util::backquote($name),
            'Create Event',
        );

        return is_string($result) ? $result : null;
    }
}
