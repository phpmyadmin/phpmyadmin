<?php
/**
 * Holds the PhpMyAdmin\MultSubmits class
 *
 * @usedby  mult_submits.inc.php
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use function count;
use function htmlspecialchars;
use function in_array;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;

/**
 * Functions for multi submit forms
 */
class MultSubmits
{
    /** @var DatabaseInterface */
    private $dbi;

    /** @var Transformations */
    private $transformations;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Operations */
    private $operations;

    /** @var Template */
    private $template;

    /**
     * @param DatabaseInterface $dbi             DatabaseInterface instance.
     * @param Template          $template        Template instance.
     * @param Transformations   $transformations Transformations instance.
     * @param RelationCleanup   $relationCleanup RelationCleanup instance.
     * @param Operations        $operations      Operations instance.
     */
    public function __construct(
        $dbi,
        Template $template,
        Transformations $transformations,
        RelationCleanup $relationCleanup,
        Operations $operations
    ) {
        $this->dbi = $dbi;
        $this->template = $template;
        $this->transformations = $transformations;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
    }

    /**
     * Gets url params
     *
     * @param string     $what     mult submit type
     * @param string     $action   action type
     * @param string     $db       database name
     * @param string     $table    table name
     * @param array      $selected selected rows(table,db)
     * @param array|null $views    table views
     *
     * @return array
     */
    public function getUrlParams(
        $what,
        $action,
        $db,
        $table,
        array $selected,
        $views
    ) {
        $urlParams = [
            'query_type' => $what,
        ];
        if (mb_strpos(' ' . $action, 'db_') === 1 || mb_strpos($action, '?route=/database/') !== false) {
            $urlParams['db'] = $db;
        } elseif (mb_strpos(' ' . $action, 'tbl_') === 1 || mb_strpos($action, '?route=/table/') !== false) {
            $urlParams['db'] = $db;
            $urlParams['table'] = $table;
        }
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }
        if ($what == 'drop_tbl' && ! empty($views)) {
            foreach ($views as $current) {
                $urlParams['views'][] = $current;
            }
        }

        return $urlParams;
    }

    /**
     * Builds or execute queries for multiple elements, depending on $queryType
     *
     * @param string      $queryType  query type
     * @param array       $selected   selected tables
     * @param string      $db         db name
     * @param string      $table      table name
     * @param array|null  $views      table views
     * @param string|null $primary    table primary
     * @param string|null $fromPrefix from prefix original
     * @param string|null $toPrefix   to prefix original
     *
     * @return array
     */
    public function buildOrExecuteQuery(
        $queryType,
        array $selected,
        $db,
        $table,
        $views,
        $primary,
        $fromPrefix,
        $toPrefix
    ) {
        $reload = null;
        $aQuery = '';
        $sqlQuery = '';
        $sqlQueryViews = null;
        // whether to run query after each pass
        $runParts = false;
        // whether to execute the query at the end (to display results)
        $executeQueryLater = false;
        $result = null;

        if ($queryType == 'drop_tbl') {
            $sqlQueryViews = '';
        }

        $selectedCount = count($selected);
        $deletes = false;
        $copyTable = false;

        for ($i = 0; $i < $selectedCount; $i++) {
            switch ($queryType) {
                case 'drop_tbl':
                    $this->relationCleanup->table($db, $selected[$i]);
                    $current = $selected[$i];
                    if (! empty($views) && in_array($current, $views)) {
                        $sqlQueryViews .= (empty($sqlQueryViews) ? 'DROP VIEW ' : ', ')
                            . Util::backquote($current);
                    } else {
                        $sqlQuery .= (empty($sqlQuery) ? 'DROP TABLE ' : ', ')
                            . Util::backquote($current);
                    }
                    $reload    = 1;
                    break;

                case 'check_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'CHECK TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'optimize_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'OPTIMIZE TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'analyze_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'ANALYZE TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'checksum_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'CHECKSUM TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'repair_tbl':
                    $sqlQuery .= (empty($sqlQuery) ? 'REPAIR TABLE ' : ', ')
                        . Util::backquote($selected[$i]);
                    $executeQueryLater = true;
                    break;

                case 'empty_tbl':
                    $deletes = true;
                    $aQuery = 'TRUNCATE ';
                    $aQuery .= Util::backquote($selected[$i]);
                    $runParts = true;
                    break;

                case 'add_prefix_tbl':
                    $newTableName = $_POST['add_prefix'] . $selected[$i];
                    // ADD PREFIX TO TABLE NAME
                    $aQuery = 'ALTER TABLE '
                    . Util::backquote($selected[$i])
                    . ' RENAME '
                    . Util::backquote($newTableName);
                    $runParts = true;
                    break;

                case 'replace_prefix_tbl':
                    $current = $selected[$i];
                    $subFromPrefix = mb_substr(
                        $current,
                        0,
                        mb_strlen((string) $fromPrefix)
                    );
                    if ($subFromPrefix == $fromPrefix) {
                        $newTableName = $toPrefix
                            . mb_substr(
                                $current,
                                mb_strlen((string) $fromPrefix)
                            );
                    } else {
                        $newTableName = $current;
                    }
                    // CHANGE PREFIX PATTERN
                    $aQuery = 'ALTER TABLE '
                    . Util::backquote($selected[$i])
                    . ' RENAME '
                    . Util::backquote($newTableName);
                    $runParts = true;
                    break;

                case 'copy_tbl_change_prefix':
                    $runParts = true;
                    $copyTable = true;

                    $current = $selected[$i];
                    $newTableName = $toPrefix .
                    mb_substr($current, mb_strlen((string) $fromPrefix));

                    // COPY TABLE AND CHANGE PREFIX PATTERN
                    Table::moveCopy(
                        $db,
                        $current,
                        $db,
                        $newTableName,
                        'data',
                        false,
                        'one_table'
                    );
                    break;

                case 'copy_tbl':
                    $runParts = true;
                    $copyTable = true;
                    Table::moveCopy(
                        $db,
                        $selected[$i],
                        $_POST['target_db'],
                        $selected[$i],
                        $_POST['what'],
                        false,
                        'one_table'
                    );
                    if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                        $this->operations->adjustPrivilegesCopyTable(
                            $db,
                            $selected[$i],
                            $_POST['target_db'],
                            $selected[$i]
                        );
                    }
                    break;
            } // end switch

            // All "DROP TABLE", "DROP FIELD", "OPTIMIZE TABLE" and "REPAIR TABLE"
            // statements will be run at once below
            if ($runParts && ! $copyTable) {
                $sqlQuery .= $aQuery . ';' . "\n";
                $this->dbi->selectDb($db);
                $result = $this->dbi->query($aQuery);

                if ($queryType == 'drop_tbl') {
                    $this->transformations->clear($db, $selected[$i]);
                }
            } // end if
        } // end for

        if ($deletes && ! empty($_REQUEST['pos'])) {
            $sql = new Sql();
            $_REQUEST['pos'] = $sql->calculatePosForLastPage(
                $db,
                $table,
                $_REQUEST['pos'] ?? null
            );
        }

        return [
            $result,
            $reload,
            $runParts,
            $executeQueryLater,
            $sqlQuery,
            $sqlQueryViews,
        ];
    }

    /**
     * Get query string from Selected
     *
     * @param string $what     mult_submit type
     * @param string $table    table name
     * @param array  $selected the selected columns
     * @param array  $views    table views
     *
     * @return array
     */
    public function getQueryFromSelected($what, $table, array $selected, array $views)
    {
        $fullQueryViews = null;
        $fullQuery = '';

        if ($what == 'drop_tbl') {
            $fullQueryViews = '';
        }

        foreach ($selected as $selectedValue) {
            switch ($what) {
                case 'drop_tbl':
                    $current = $selectedValue;
                    if (! empty($views) && in_array($current, $views)) {
                        $fullQueryViews .= (empty($fullQueryViews) ? 'DROP VIEW ' : ', ')
                        . Util::backquote(htmlspecialchars($current));
                    } else {
                        $fullQuery .= (empty($fullQuery) ? 'DROP TABLE ' : ', ')
                        . Util::backquote(htmlspecialchars($current));
                    }
                    break;

                case 'empty_tbl':
                    $fullQuery .= 'TRUNCATE ';
                    $fullQuery .= Util::backquote(htmlspecialchars($selectedValue))
                            . ';<br>';
                    break;
            }
        }

        if ($what == 'drop_tbl') {
            if (! empty($fullQuery)) {
                $fullQuery .= ';<br>' . "\n";
            }
            if (! empty($fullQueryViews)) {
                $fullQuery .= $fullQueryViews . ';<br>' . "\n";
            }
            unset($fullQueryViews);
        }

        $fullQueryViews = $fullQueryViews ?? null;

        return [
            $fullQuery,
            $fullQueryViews,
        ];
    }
}
