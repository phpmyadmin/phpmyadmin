<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for Privileges template managers
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Template;

/**
 * Interface for Privileges template managers
 *
 * @package PhpMyAdmin
 */
interface PrivilegesTemplateManagerInterface
{
    /**
     * PrivilegesTemplateManagerInterface constructor.
     *
     * @param Privileges $privileges Privileges class
     * @param Template   $template   Template to use
     */
    public function __construct(Privileges $privileges, Template $template);

    /**
     * Get content to display Add userfieldset
     *
     * @param string $db    the database
     * @param string $table the table name
     *
     * @return string Content output
     */
    public function getAddUserFieldset(string $db = '', string $table = ''): string;

    /**
     * Displays on which column(s) a table-specific privilege is granted
     *
     * @param array  $columns        columns array
     * @param array  $row            first row from result or boolean false
     * @param string $nameForSelect  privilege types - Select_priv, Insert_priv
     *                               Update_priv, References_priv
     * @param string $privForHeader  privilege for header
     * @param string $name           privilege name: insert, select, update, references
     * @param string $nameForDfn     name for dfn
     * @param string $nameForCurrent name for current
     *
     * @return string html snippet
     */
    public function getColumnPrivileges(
        array $columns,
        array $row,
        string $nameForSelect,
        string $privForHeader,
        string $name,
        string $nameForDfn,
        string $nameForCurrent
    ): string;

    /**
     * Get content for privileges that are attached to a specific column
     *
     * @param array $columns columns array
     * @param array $row     first row from result or boolean false
     *
     * @return string
     */
    public function getAttachedPrivilegesToTableSpecificColumn(array $columns, array $row): string;

    /**
     * Get content for privileges that are not attached to a specific column
     *
     * @param array $row first row from result or boolean false
     *
     * @return string
     */
    public function getNotAttachedPrivilegesToTableSpecificColumn(array $row): string;

    /**
     * Get content for global privileges table with check boxes
     *
     * @param array $privTable      privileges table array
     * @param array $privTableNames names of the privilege tables
     *                              (Data, Structure, Administration)
     * @param array $row            first row from result or boolean false
     *
     * @return string
     */
    public function getGlobalPrivTableWithCheckboxes(
        array $privTable,
        array $privTableNames,
        array $row
    ): string;

    /**
     * Get content for privileges table head
     *
     * @return string
     */
    public function getPrivsTableHead(): string;

    /**
     * Get error for View Users form
     * For non superusers such as grant/create users
     *
     * @return string
     */
    public function getViewUsersError(): string;

    /**
     * Get content for "Require"
     *
     * @param array $row privilege array
     *
     * @return string html snippet
     */
    public function getRequires(array $row): string;

    /**
     * Get content for "Resource limits"
     *
     * @param array $row first row from result or boolean false
     *
     * @return string html snippet
     */
    public function getResourceLimits(array $row): string;

    /**
     * Get content for global or database specific privileges
     *
     * @param string $db    the database
     * @param string $table the table
     * @param array  $row   first row from result or boolean false
     *
     * @return string
     */
    public function getGlobalOrDbSpecificPrivs($db, $table, array $row): string;

    /**
     * Gets the currently active authentication plugins
     *
     * @param string $origAuthPlugin Default Authentication plugin
     * @param string $mode           are we creating a new user or are we just changing one?
     *                               (allowed values: 'new', 'edit', 'change_pw')
     * @param string $versions       Is MySQL version newer or older than 5.5.7
     *
     * @return string
     */
    public function getAuthPluginsDropdown($origAuthPlugin, $mode = 'new', $versions = 'new'): string;

    /**
     * Get header content to display User's properties
     *
     * @param boolean      $dbnameIsWildcard whether database name is wildcard or not
     * @param string       $urlDbname        url database name that urlencode() string
     * @param array|string $dbname           database name
     * @param string       $username         username
     * @param string       $hostname         host name
     * @param string       $entityName       entity (table or routine) name
     * @param string       $entityType       optional, type of entity ('table' or 'routine')
     *
     * @return string
     */
    public function getHeaderForUserProperties(
        $dbnameIsWildcard,
        $urlDbname,
        $dbname,
        $username,
        $hostname,
        $entityName,
        $entityType = 'table'
    ): string;

    /**
     * Get content to display user's tabel specific or database specific rights
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database, table or routine
     * @param string $dbname   database name
     *
     * @return string
     */
    public function getAllTableSpecificRights(
        $username,
        $hostname,
        $type,
        $dbname = ''
    ): string;

    /**
     * Get content to display the initials
     *
     * @param array $arrayInitials array for all initials, even non A-Z
     *
     * @return string snippet
     */
    public function getInitials(array $arrayInitials): string;

    /**
     * Get fieldset for Add/Delete user
     *
     * @return string snippet
     */
    public function getFieldsetForAddDeleteUser(): string;

    /**
     * Get the HTML snippet for routine specific privileges
     *
     * @param string $username  username for database connection
     * @param string $hostname  hostname for database connection
     * @param string $db        the database
     * @param string $routine   the routine
     * @param string $urlDbname url encoded db name
     *
     * @return string
     */
    public function getRoutineSpecificPrivileges($username, $hostname, $db, $routine, $urlDbname): string;

    /**
     * Displays a dropdown to select the user group
     * with menu items configured to each of them.
     *
     * @param string $username username
     *
     * @return string Content to select the user group
     */
    public function getChooseUserGroup($username): string;

    /**
     * Get content for User Group Dialog
     *
     * @param string $username    username
     * @param bool   $isMenuswork Is menuswork set in configuration
     *
     * @return string html
     */
    public function getUserGroupDialog(string $username, bool $isMenuswork): string;

    /**
     * Get content for table specific privileges
     *
     * @param string $username username for database connection
     * @param string $hostname hostname for database connection
     * @param string $db       the database
     * @param string $table    the table
     * @param array  $columns  columns array
     * @param array  $row      current privileges row
     *
     * @return string
     */
    public function getTableSpecificPrivileges(
        $username,
        $hostname,
        $db,
        $table,
        array $columns,
        array $row
    ): string;

    /**
     * Get content for routine based privileges
     *
     * @param string $db            database name
     * @param string $indexCheckbox starting index for rows to be added
     *
     * @return string
     */
    public function getTableBodyForSpecificDbRoutinePrivs($db, $indexCheckbox): string;

    /**
     * Returns user group edit link
     *
     * @param string $username User name
     *
     * @return string HTML code with link
     */
    public function getUserGroupEditLink($username): string;

    /**
     * Get content for change user login information
     *
     * @param string $username username
     * @param string $hostname host name
     *
     * @return string content
     */
    public function getChangeLoginInformationForm(string $username, string $hostname): string;

    /**
     * Provide a line with links to the relevant database and table
     *
     * @param string $urlDbname url database name that urlencode() string
     * @param string $dbname    database name
     * @param string $tablename table name
     *
     * @return string content
     */
    public function getLinkToDbAndTable(string $urlDbname, string $dbname, string $tablename): string;

    /**
     * Get table body for 'tableuserrights' table in userform
     *
     * @param array $dbRights user's database rights array
     *
     * @return string Content
     */
    public function getTableBodyForUserRights(array $dbRights): string;

    /**
     * Get title and textarea for export user definition in Privileges
     *
     * @param string $username username
     * @param string $hostname host name
     *
     * @return array ($title, $export)
     */
    public function getListForExportUserDefinition(string $username, string $hostname): array;

    /**
     * Displays the privileges form table
     *
     * @param string  $db     the database
     * @param string  $table  the table
     * @param boolean $submit whether to display the submit button or not
     *
     * @global  array     $cfg         the phpMyAdmin configuration
     * @global  resource  $user_link   the database connection
     *
     * @return string Content
     */
    public function getPrivilegesTable(
        string $db = '*',
        string $table = '*',
        bool $submit = true
    ): string;

    /**
     * Get content for display the users overview
     * (if less than 50 users, display them immediately)
     *
     * @param array  $usersInformation ran sql query
     * @param array  $dbRights         user's database rights array
     * @param string $pmaThemeImage    a image source link
     * @param string $textDir          text directory
     *
     * @return string Content
     */
    public function getUsersOverview(
        array $usersInformation,
        array $dbRights,
        string $pmaThemeImage,
        string $textDir
    ): string;

    /**
     * Get content for display user properties
     *
     * @param boolean      $dbnameIsWildcard whether database name is wildcard or not
     * @param string       $urlDbname        url database name that urlencode() string
     * @param string       $username         username
     * @param string       $hostname         host name
     * @param array|string $dbname           database name
     * @param string       $tablename        table name
     *
     * @return string
     */
    public function getUserProperties(
        $dbnameIsWildcard,
        $urlDbname,
        $username,
        $hostname,
        $dbname,
        $tablename
    ): string;
}
