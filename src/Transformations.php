<?php
/**
 * Set of functions used with the relation and pdf feature
 *
 * This file also provides basic functions to use in other plugins!
 * These are declared in the 'GLOBAL Plugin functions' section
 *
 * Please use short and expressive names.
 * For now, special characters which aren't allowed in
 * filenames or functions should not be used.
 *
 * Please provide a comment for your function,
 * what it does and what parameters are available.
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Plugins\TransformationsInterface;
use Twig\Attribute\AsTwigFunction;

use function array_shift;
use function class_exists;
use function closedir;
use function count;
use function explode;
use function ltrim;
use function mb_strtolower;
use function mb_substr;
use function opendir;
use function preg_match;
use function preg_replace;
use function readdir;
use function rtrim;
use function sort;
use function str_contains;
use function str_replace;
use function stripslashes;
use function strlen;
use function trim;
use function ucfirst;
use function ucwords;

/**
 * Transformations class
 */
class Transformations
{
    /** @var string[][]|null */
    private static array|null $availableMimeTypesStack = null;

    public function __construct(private readonly DatabaseInterface $dbi, private readonly Relation $relation)
    {
    }

    /**
     * Returns array of options from string with options separated by comma,
     * removes quotes
     *
     * <code>
     * getOptions("'option ,, quoted',abd,'2,3',");
     * // array {
     * //     'option ,, quoted',
     * //     'abc',
     * //     '2,3',
     * //     '',
     * // }
     * </code>
     *
     * @param string $optionString comma separated options
     *
     * @return string[]
     */
    public function getOptions(string $optionString): array
    {
        if ($optionString === '') {
            return [];
        }

        $transformOptions = explode(',', $optionString);

        $result = [];

        while (($option = array_shift($transformOptions)) !== null) {
            $trimmed = trim($option);
            if (strlen($trimmed) > 1 && $trimmed[0] === "'" && $trimmed[strlen($trimmed) - 1] === "'") {
                // '...'
                $option = mb_substr($trimmed, 1, -1);
            } elseif (isset($trimmed[0]) && $trimmed[0] === "'") {
                // '...,
                $trimmed = ltrim($option);
                $rtrimmed = '';
                /** @infection-ignore-all */
                while (($option = array_shift($transformOptions)) !== null) {
                    // ...,
                    $trimmed .= ',' . $option;
                    $rtrimmed = rtrim($trimmed);
                    if ($rtrimmed[strlen($rtrimmed) - 1] === "'") {
                        // ,...'
                        break;
                    }
                }

                $option = mb_substr($rtrimmed, 1, -1);
            }

            $result[] = stripslashes($option);
        }

        return $result;
    }

    /**
     * Gets all available MIME-types
     *
     * @return string[][]    array[mimetype], array[transformation]
     */
    public function getAvailableMimeTypes(): array
    {
        if (self::$availableMimeTypesStack !== null) {
            return self::$availableMimeTypesStack;
        }

        $stack = [];
        $subDirs = ['Input/' => 'input_', 'Output/' => '', '' => ''];

        foreach ($subDirs as $sd => $prefix) {
            $handle = opendir(ROOT_PATH . 'src/Plugins/Transformations/' . $sd);

            if (! $handle) {
                $stack[$prefix . 'transformation'] = [];
                $stack[$prefix . 'transformation_file'] = [];
                continue;
            }

            $filestack = [];
            while ($file = readdir($handle)) {
                // Ignore hidden files
                if ($file[0] === '.') {
                    continue;
                }

                // Ignore old plugins (.class in filename)
                if (str_contains($file, '.class')) {
                    continue;
                }

                $filestack[] = $file;
            }

            closedir($handle);
            sort($filestack);

            foreach ($filestack as $file) {
                if (preg_match('|^[^.].*_.*_.*\.php$|', $file) === 1) {
                    // File contains transformation functions.
                    $parts = explode('_', str_replace('.php', '', $file));
                    $mimetype = $parts[0] . '/' . $parts[1];
                    $stack['mimetype'][$mimetype] = $mimetype;

                    $stack[$prefix . 'transformation'][] = $mimetype . ': ' . $parts[2];
                    $stack[$prefix . 'transformation_file'][] = $sd . $file;
                    if ($sd === '') {
                        $stack['input_transformation'][] = $mimetype . ': ' . $parts[2];
                        $stack['input_transformation_file'][] = $sd . $file;
                    }
                } elseif (preg_match('|^[^.].*\.php$|', $file) === 1) {
                    // File is a plain mimetype, no functions.
                    $base = str_replace('.php', '', $file);

                    if ($base !== 'global') {
                        $mimetype = str_replace('_', '/', $base);
                        $stack['mimetype'][$mimetype] = $mimetype;
                        $stack['empty_mimetype'][$mimetype] = $mimetype;
                    }
                }
            }
        }

        self::$availableMimeTypesStack = $stack;

        return self::$availableMimeTypesStack;
    }

    /**
     * Returns the class name of the transformation
     *
     * @param string $filename transformation file name
     *
     * @return class-string<TransformationsInterface> the class name of transformation
     */
    private function getClassName(string $filename): string
    {
        return 'PhpMyAdmin\\Plugins\\Transformations\\' . str_replace('/', '\\', explode('.php', $filename)[0]);
    }

    public function getPluginInstance(string $filename): TransformationsInterface|null
    {
        $className = $this->getClassName($filename);
        if (class_exists($className)) {
            return new $className();
        }

        return null;
    }

    /**
     * Returns the description of the transformation
     *
     * @param string $file transformation file
     *
     * @return string the description of the transformation
     */
    #[AsTwigFunction('get_description')]
    public function getDescription(string $file): string
    {
        $className = $this->getClassName($file);
        if (class_exists($className)) {
            return $className::getInfo();
        }

        return '';
    }

    /**
     * Returns the name of the transformation
     *
     * @param string $file transformation file
     *
     * @return string the name of the transformation
     */
    #[AsTwigFunction('get_name')]
    public function getName(string $file): string
    {
        $className = $this->getClassName($file);
        if (class_exists($className)) {
            return $className::getName();
        }

        return '';
    }

    /**
     * Fixups old MIME or transformation name to new one
     *
     * - applies some hardcoded fixups
     * - adds spaces after _ and numbers
     * - capitalizes words
     * - removes back spaces
     *
     * @param string $value Value to fixup
     */
    public function fixUpMime(string $value): string
    {
        $value = str_replace(
            ['jpeg', 'png'],
            ['JPEG', 'PNG'],
            $value,
        );

        return str_replace(
            ' ',
            '',
            ucwords(
                (string) preg_replace('/([0-9_]+)/', '$1 ', $value),
            ),
        );
    }

    /**
     * Gets the mimetypes for all columns of a table
     *
     * @param string $db       the name of the db to check for
     * @param string $table    the name of the table to check for
     * @param bool   $strict   whether to include only results having a mimetype set
     * @param bool   $fullName whether to use full column names as the key
     *
     * @return array<string, array{
     *     column_name: string,
     *     mimetype: string,
     *     transformation: string,
     *     transformation_options: string,
     *     input_transformation: string,
     *     input_transformation_options: string
     * }>|null
     */
    public function getMime(string $db, string $table, bool $strict = false, bool $fullName = false): array|null
    {
        $browserTransformationFeature = $this->relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return null;
        }

        if ($fullName) {
            $comQry = 'SELECT CONCAT(`db_name`, \'.\', `table_name`, \'.\', `column_name`) AS column_name, ';
        } else {
            $comQry = 'SELECT `column_name`, ';
        }

        $comQry .= '`mimetype`, '
                    . '`transformation`, '
                    . '`transformation_options`, '
                    . '`input_transformation`, '
                    . '`input_transformation_options`'
            . ' FROM ' . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo)
            . ' WHERE `db_name` = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
            . ' AND `table_name` = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser)
            . ' AND ( `mimetype` != \'\'' . (! $strict ?
                ' OR `transformation` != \'\''
                . ' OR `transformation_options` != \'\''
                . ' OR `input_transformation` != \'\''
                . ' OR `input_transformation_options` != \'\'' : '') . ')';

        /**
         * @psalm-var array<string, array{
         *     column_name: string,
         *     mimetype: string,
         *     transformation: string,
         *     transformation_options: string,
         *     input_transformation: string,
         *     input_transformation_options: string
         * }> $result
         */
        $result = $this->dbi->fetchResult($comQry, 'column_name', null, ConnectionType::ControlUser);

        foreach ($result as $column => $values) {
            // convert mimetype to new format (f.e. Text_Plain, etc)
            $values['mimetype'] = $this->fixUpMime($values['mimetype']);

            // For transformation of form
            // output/image_jpeg__inline.inc.php
            // extract dir part.
            $dir = explode('/', $values['transformation']);
            $subdir = '';
            if (count($dir) === 2) {
                $subdir = ucfirst($dir[0]) . '/';
                $values['transformation'] = $dir[1];
            }

            $values['transformation'] = $this->fixUpMime($values['transformation']);
            $values['transformation'] = $subdir . $values['transformation'];
            $result[$column] = $values;
        }

        return $result;
    }

    /**
     * Set a single mimetype to a certain value.
     *
     * @param string $db                 the name of the db
     * @param string $table              the name of the table
     * @param string $key                the name of the column
     * @param string $mimetype           the mimetype of the column
     * @param string $transformation     the transformation of the column
     * @param string $transformationOpts the transformation options of the column
     * @param string $inputTransform     the input transformation of the column
     * @param string $inputTransformOpts the input transformation options of the column
     * @param bool   $forcedelete        force delete, will erase any existing
     *                                   comments for this column
     */
    public function setMime(
        string $db,
        string $table,
        string $key,
        string $mimetype,
        string $transformation,
        string $transformationOpts,
        string $inputTransform,
        string $inputTransformOpts,
        bool $forcedelete = false,
    ): bool {
        $browserTransformationFeature = $this->relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return false;
        }

        // lowercase mimetype & transformation
        $mimetype = mb_strtolower($mimetype);
        $transformation = mb_strtolower($transformation);

        // Do we have any parameter to set?
        $hasValue =
            $mimetype !== '' ||
            $transformation !== '' ||
            $transformationOpts !== '' ||
            $inputTransform !== '' ||
            $inputTransformOpts !== '';

        $testQry = '
             SELECT `mimetype`,
                    `comment`
               FROM ' . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo) . '
              WHERE `db_name`     = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser) . '
                AND `table_name`  = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser) . '
                AND `column_name` = ' . $this->dbi->quoteString($key, ConnectionType::ControlUser);

        $testRs = $this->dbi->queryAsControlUser($testQry);

        if ($testRs->numRows() > 0) {
            $row = $testRs->fetchAssoc();

            if (! $forcedelete && ($hasValue || $row['comment'] !== null && $row['comment'] !== '')) {
                $updQuery = 'UPDATE '
                    . Util::backquote($browserTransformationFeature->database) . '.'
                    . Util::backquote($browserTransformationFeature->columnInfo)
                    . ' SET '
                    . '`mimetype` = '
                    . $this->dbi->quoteString($mimetype, ConnectionType::ControlUser) . ', '
                    . '`transformation` = '
                    . $this->dbi->quoteString($transformation, ConnectionType::ControlUser) . ', '
                    . '`transformation_options` = '
                    . $this->dbi->quoteString($transformationOpts, ConnectionType::ControlUser) . ', '
                    . '`input_transformation` = '
                    . $this->dbi->quoteString($inputTransform, ConnectionType::ControlUser) . ', '
                    . '`input_transformation_options` = '
                    . $this->dbi->quoteString($inputTransformOpts, ConnectionType::ControlUser);
            } else {
                $updQuery = 'DELETE FROM '
                    . Util::backquote($browserTransformationFeature->database)
                    . '.' . Util::backquote($browserTransformationFeature->columnInfo);
            }

            $updQuery .= '
                WHERE `db_name`     = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser) . '
                  AND `table_name`  = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser) . '
                  AND `column_name` = ' . $this->dbi->quoteString($key, ConnectionType::ControlUser);
        } elseif ($hasValue) {
            $updQuery = 'INSERT INTO '
                . Util::backquote($browserTransformationFeature->database)
                . '.' . Util::backquote($browserTransformationFeature->columnInfo)
                . ' (db_name, table_name, column_name, mimetype, '
                . 'transformation, transformation_options, '
                . 'input_transformation, input_transformation_options) '
                . ' VALUES('
                . $this->dbi->quoteString($db, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($table, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($key, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($mimetype, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($transformation, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($transformationOpts, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($inputTransform, ConnectionType::ControlUser) . ','
                . $this->dbi->quoteString($inputTransformOpts, ConnectionType::ControlUser) . ')';
        }

        if (isset($updQuery)) {
            return (bool) $this->dbi->queryAsControlUser($updQuery);
        }

        return false;
    }

    /**
     * GLOBAL Plugin functions
     */

    /**
     * Delete related transformation details
     * after deleting database. table or column
     *
     * @param string $db     Database name
     * @param string $table  Table name
     * @param string $column Column name
     */
    public function clear(string $db, string $table = '', string $column = ''): bool
    {
        $browserTransformationFeature = $this->relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return false;
        }

        $deleteSql = 'DELETE FROM '
            . Util::backquote($browserTransformationFeature->database) . '.'
            . Util::backquote($browserTransformationFeature->columnInfo)
            . ' WHERE ';

        if ($column !== '' && $table !== '') {
            $deleteSql .= '`db_name` = \'' . $db . '\' AND '
                . '`table_name` = \'' . $table . '\' AND '
                . '`column_name` = \'' . $column . '\' ';
        } elseif ($table !== '') {
            $deleteSql .= '`db_name` = \'' . $db . '\' AND '
                . '`table_name` = \'' . $table . '\' ';
        } else {
            $deleteSql .= '`db_name` = \'' . $db . '\' ';
        }

        return (bool) $this->dbi->tryQuery($deleteSql);
    }
}
