<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Server\PrivilegesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\PrivilegesController */
class PrivilegesControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testPrivilegesController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->dummyDbi->addSelectDb('mysql');
        $this->dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ORDER BY `User` ASC, `Host` ASC;',
            [
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $this->dummyDbi->addResult(
            'SELECT *, IF(`authentication_string` = _latin1 \'\', \'N\', \'Y\') AS \'Password\' FROM `mysql`.`user` ;',
            [
                ['localhost', 'root', 'password', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', 'Y', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
                ['localhost', 'pma', 'password', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', '', '', '', '', '0', '0', '0', '0', 'mysql_native_password', 'password', 'N', 'N', '', '0.000000', 'Y'],
            ],
            ['Host', 'User', 'Password', 'Select_priv', 'Insert_priv', 'Update_priv', 'Delete_priv', 'Create_priv', 'Drop_priv', 'Reload_priv', 'Shutdown_priv', 'Process_priv', 'File_priv', 'Grant_priv', 'References_priv', 'Index_priv', 'Alter_priv', 'Show_db_priv', 'Super_priv', 'Create_tmp_table_priv', 'Lock_tables_priv', 'Execute_priv', 'Repl_slave_priv', 'Repl_client_priv', 'Create_view_priv', 'Show_view_priv', 'Create_routine_priv', 'Alter_routine_priv', 'Create_user_priv', 'Event_priv', 'Trigger_priv', 'Create_tablespace_priv', 'Delete_history_priv', 'ssl_type', 'ssl_cipher', 'x509_issuer', 'x509_subject', 'max_questions', 'max_updates', 'max_connections', 'max_user_connections', 'plugin', 'authentication_string', 'password_expired', 'is_role', 'default_role', 'max_statement_time', 'Password'],
        );
        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `mysql`;',
            [['columns_priv'], ['db'], ['tables_priv'], ['user']],
            ['Tables_in_mysql'],
        );
        $this->dummyDbi->addResult(
            '(SELECT DISTINCT `User`, `Host` FROM `mysql`.`user` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`db` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`tables_priv` ) UNION (SELECT DISTINCT `User`, `Host` FROM `mysql`.`columns_priv` ) ORDER BY `User` ASC, `Host` ASC',
            [['pma', 'localhost'], ['root', 'localhost']],
            ['User', 'Host'],
        );
        // phpcs:enable

        $request = $this->createStub(ServerRequest::class);

        $request->method('getParsedBodyParam')->willReturnMap([['old_username', '', ''], ['old_hostname', '', '']]);

        $request->method('getQueryParam')->willReturnMap([['initial', '', '']]);

        $response = new ResponseRenderer();
        (new PrivilegesController($response, new Template(), new Relation($this->dbi), $this->dbi))($request);

        $actual = $response->getHTMLResult();
        $this->assertStringContainsString('User accounts overview', $actual);
        $this->assertStringContainsString(
            'id="checkbox_sel_users_1" value="pma&amp;amp;#27;localhost" name="selected_usr[]"',
            $actual,
        );
        $this->assertStringContainsString('<code><dfn title="No privileges.">USAGE</dfn></code>', $actual);
        $this->assertStringContainsString(
            'id="checkbox_sel_users_2" value="root&amp;amp;#27;localhost" name="selected_usr[]"',
            $actual,
        );
        $this->assertStringContainsString(
            '<code><dfn title="Includes all privileges except GRANT.">ALL PRIVILEGES</dfn></code>',
            $actual,
        );
    }
}
