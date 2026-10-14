<?php

declare(strict_types=1);

namespace PhpMyAdmin\Events;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;

use function __;
use function array_column;
use function array_multisort;
use function count;
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
final class Events
{
    public const STATUS = [
        'query' => ['ENABLE', 'DISABLE', 'DISABLE ON SLAVE'],
        'display' => ['ENABLED', 'DISABLED', 'SLAVESIDE_DISABLED'],
    ];

    public const TYPE = ['RECURRING', 'ONE TIME'];

    public const INTERVAL = [
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

    /** @var list<string> */
    private array $errors = [];

    public function __construct(private readonly DatabaseInterface $dbi, private readonly Config $config)
    {
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
                $createItem = self::getDefinition($this->dbi, Current::$database, $_POST['item_original_name']);
                $dropItem = 'DROP EVENT IF EXISTS '
                    . Util::backquote($_POST['item_original_name'])
                    . ";\n";
                $result = $this->dbi->tryQuery($dropItem);
                if (! $result) {
                    $this->errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($dropItem),
                    )
                        . '<br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    $result = $this->dbi->tryQuery($itemQuery);
                    if (! $result) {
                        $this->errors[] = sprintf(
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
                            $this->errors[] = __('Sorry, we failed to restore the dropped event.') . '<br>'
                                . __('The backed up query was:')
                                . '"' . htmlspecialchars($createItem) . '"<br>'
                                . __('MySQL said: ') . $this->dbi->getError();
                        }
                    } else {
                        Current::$message = Message::success(
                            __('Event %1$s has been modified.'),
                            [Util::backquote($_POST['item_name'])],
                        );
                        $sqlQuery = $dropItem . $itemQuery;
                    }
                }
            } else {
                // 'Add a new item' mode
                $result = $this->dbi->tryQuery($itemQuery);
                if (! $result) {
                    $this->errors[] = sprintf(
                        __('The following query has failed: "%s"'),
                        htmlspecialchars($itemQuery),
                    )
                        . '<br><br>'
                        . __('MySQL said: ') . $this->dbi->getError();
                } else {
                    Current::$message = Message::success(
                        __('Event %1$s has been created.'),
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
        $where = 'EVENT_SCHEMA ' . Util::getCollateForIS($this->dbi)
            . '=' . $this->dbi->quoteString(Current::$database)
                 . ' AND EVENT_NAME=' . $this->dbi->quoteString($name);
        $query = 'SELECT ' . $columns . ' FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE ' . $where . ';';
        $item = $this->dbi->fetchSingleRow($query);
        if ($item === []) {
            return null;
        }

        $retval['item_name'] = $item['EVENT_NAME'];
        $retval['item_status'] = $item['STATUS'];
        $retval['item_type'] = $item['EVENT_TYPE'];
        $retval['item_type_toggle'] = $retval['item_type'] === 'RECURRING' ? 'ONE TIME' : 'RECURRING';

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

        $query .= 'EVENT ';
        $itemName = $request->getParsedBodyParamAsString('item_name', '');
        if ($itemName !== '') {
            $query .= Util::backquote($itemName) . ' ';
        } else {
            $this->errors[] = __('You must provide an event name!');
        }

        $query .= 'ON SCHEDULE ';
        $itemType = $request->getParsedBodyParamAsString('item_type', '');
        if (in_array($itemType, self::TYPE, true)) {
            $itemExecuteAt = $request->getParsedBodyParamAsString('item_execute_at', '');
            if ($itemType === 'RECURRING') {
                $itemIntervalValue = $request->getParsedBodyParamAsString('item_interval_value', '');
                $itemIntervalField = $request->getParsedBodyParamAsString('item_interval_field', '');
                if ($itemIntervalValue !== '' && in_array($itemIntervalField, self::INTERVAL, true)) {
                    $query .= 'EVERY ' . (int) $itemIntervalValue . ' ';
                    $query .= $itemIntervalField . ' ';
                } else {
                    $this->errors[] = __('You must provide a valid interval value for the event.');
                }

                $itemStarts = $request->getParsedBodyParamAsString('item_starts', '');
                if ($itemStarts !== '') {
                    $query .= 'STARTS ' . $this->dbi->quoteString($itemStarts) . ' ';
                }

                $itemEnds = $request->getParsedBodyParamAsString('item_ends', '');
                if ($itemEnds !== '') {
                    $query .= 'ENDS ' . $this->dbi->quoteString($itemEnds) . ' ';
                }
            } elseif ($itemExecuteAt !== '') {
                $query .= 'AT ' . $this->dbi->quoteString($itemExecuteAt) . ' ';
            } else {
                $this->errors[] = __('You must provide a valid execution time for the event.');
            }
        } else {
            $this->errors[] = __('You must provide a valid type for the event.');
        }

        $query .= 'ON COMPLETION ';
        if (! $request->hasBodyParam('item_preserve')) {
            $query .= 'NOT ';
        }

        $query .= 'PRESERVE ';
        $itemStatus = $request->getParsedBodyParamAsString('item_status', '');
        if ($itemStatus !== '') {
            foreach (self::STATUS['display'] as $key => $value) {
                if ($value == $itemStatus) {
                    $query .= self::STATUS['query'][$key] . ' ';
                    break;
                }
            }
        }

        $itemComment = $request->getParsedBodyParamAsString('item_comment', '');
        if ($itemComment !== '') {
            $query .= 'COMMENT ' . $this->dbi->quoteString($itemComment) . ' ';
        }

        $query .= 'DO ';
        $itemDefinition = $request->getParsedBodyParamAsString('item_definition', '');
        if ($itemDefinition !== '') {
            $query .= $itemDefinition;
        } else {
            $this->errors[] = __('You must provide an event definition.');
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
        if (! $this->config->selectedServer['DisableIS']) {
            $query = QueryGenerator::getInformationSchemaEventsRequest(
                Util::getCollateForIS($this->dbi),
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
        $events = $this->dbi->fetchResultSimple($query);

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

    public function getEventDefinition(string $db, string $name): string|null
    {
        return self::getDefinition($this->dbi, $db, $name);
    }

    public function hasEventPrivilege(DatabaseName $databaseName): bool
    {
        return Util::currentUserHasPrivilege($this->dbi, 'EVENT', $databaseName->getName());
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }
}
