<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Git;
use const CONFIG_FILE;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use function chdir;
use function file_put_contents;
use function getcwd;
use function is_string;
use function mkdir;
use function mt_rand;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * @group git-revision
 */
class GitTest extends AbstractTestCase
{
    /** @var Git */
    protected $object;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $testDir;

    /** @var string */
    protected $cwd;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setProxySettings();
        $this->config = new Config(CONFIG_FILE);
        $this->config->set('ShowGitRevision', true);
        $this->object = new Git($this->config);
        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'gittempdir_' . mt_rand();

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);
        $this->cwd = is_string(getcwd()) ? getcwd() : './';
        mkdir($this->testDir);
        chdir((string) $this->testDir);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        chdir((string) $this->cwd);
        rmdir($this->testDir);
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for isGitRevision
     */
    public function testIsGitRevision(): void
    {
        $_SESSION['git_location'] = '.cachedgitlocation';
        $_SESSION['is_git_revision'] = true;

        $git_location = '';

        $this->assertTrue(
            $this->object->isGitRevision($git_location)
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        $this->assertEquals('.cachedgitlocation', $git_location);
    }

    /**
     * Test for isGitRevision
     */
    public function testIsGitRevisionSkipped(): void
    {
        $this->config->set('ShowGitRevision', false);
        $this->assertFalse(
            $this->object->isGitRevision($git_location)
        );
    }

    /**
     * Test for isGitRevision
     *
     * @group git-revision
     */
    public function testIsGitRevisionLocalGitDir(): void
    {
        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir('.git');

        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents('.git/config', '');

        $this->assertTrue(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unlink('.git/config');
        rmdir('.git');
    }

    /**
     * Test for isGitRevision
     *
     * @group git-revision
     */
    public function testIsGitRevisionExternalGitDir(): void
    {
        file_put_contents('.git', 'gitdir: ./.customgitdir');
        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir('.customgitdir');

        $this->assertTrue(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents('.git', 'random data here');

        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        unlink('.git');
        rmdir('.customgitdir');
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @group git-revision
     */
    public function testCheckGitRevisionPacksFolder(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        file_put_contents(
            '.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted' . PHP_EOL .
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10' . PHP_EOL .
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98' . PHP_EOL .
            '17bf8b7309919f8ac593d7c563b31472780ee83b refs/remotes/origin/master' . PHP_EOL
        );
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode

        $commit = $this->object->checkGitRevision();

        $this->assertIsArray($commit);
        $this->assertArrayHasKey('hash', $commit);
        $this->assertEquals('17bf8b7309919f8ac593d7c563b31472780ee83b', $commit['hash']);

        $this->assertArrayHasKey('branch', $commit);
        $this->assertEquals('master', $commit['branch']);

        $this->assertArrayHasKey('message', $commit);
        $this->assertIsString($commit['message']);

        $this->assertArrayHasKey('is_remote_commit', $commit);
        $this->assertIsBool($commit['is_remote_commit']);

        $this->assertArrayHasKey('is_remote_branch', $commit);
        $this->assertIsBool($commit['is_remote_branch']);

        $this->assertArrayHasKey('author', $commit);
        $this->assertIsArray($commit['author']);
        $this->assertArrayHasKey('name', $commit['author']);
        $this->assertArrayHasKey('email', $commit['author']);
        $this->assertArrayHasKey('date', $commit['author']);
        $this->assertIsString($commit['author']['name']);
        $this->assertIsString($commit['author']['email']);
        $this->assertIsString($commit['author']['date']);

        $this->assertArrayHasKey('committer', $commit);
        $this->assertIsArray($commit['committer']);
        $this->assertArrayHasKey('name', $commit['committer']);
        $this->assertArrayHasKey('email', $commit['committer']);
        $this->assertArrayHasKey('date', $commit['committer']);
        $this->assertIsString($commit['committer']['name']);
        $this->assertIsString($commit['committer']['email']);
        $this->assertIsString($commit['committer']['date']);

        rmdir('.git/objects/pack');
        rmdir('.git/objects');
        unlink('.git/packed-refs');
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @group git-revision
     */
    public function testCheckGitRevisionRefFile(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');
        mkdir('.git/refs/remotes/origin', 0777, true);
        file_put_contents('.git/refs/remotes/origin/master', 'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f');
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertEquals(
            0,
            $this->config->get('PMA_VERSION_GIT')
        );

        unlink('.git/refs/remotes/origin/master');
        rmdir('.git/refs/remotes/origin');
        rmdir('.git/refs/remotes');
        rmdir('.git/refs');
        rmdir('.git/objects/pack');
        rmdir('.git/objects');
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');
    }

    /**
     * Test for checkGitRevision with packs as file
     *
     * @group git-revision
     */
    public function testCheckGitRevisionPacksFile(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        file_put_contents(
            '.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted' . PHP_EOL .
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10' . PHP_EOL .
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98' . PHP_EOL .
            '17bf8b7309919f8ac593d7c563b31472780ee83b refs/remotes/origin/master' . PHP_EOL
        );
        mkdir('.git/objects/info', 0777, true);
        file_put_contents(
            '.git/objects/info/packs',
            'P pack-faea49765800da462c70bea555848cc8c7a1c28d.pack' . PHP_EOL .
            '  pack-.pack' . PHP_EOL .
            PHP_EOL .
            'P pack-420568bae521465fd11863bff155a2b2831023.pack' . PHP_EOL .
            PHP_EOL
        );

        $commit = $this->object->checkGitRevision();

        $this->assertIsArray($commit);
        $this->assertArrayHasKey('hash', $commit);
        $this->assertEquals('17bf8b7309919f8ac593d7c563b31472780ee83b', $commit['hash']);

        $this->assertArrayHasKey('branch', $commit);
        $this->assertEquals('master', $commit['branch']);

        $this->assertArrayHasKey('message', $commit);
        $this->assertIsString($commit['message']);

        $this->assertArrayHasKey('is_remote_commit', $commit);
        $this->assertIsBool($commit['is_remote_commit']);

        $this->assertArrayHasKey('is_remote_branch', $commit);
        $this->assertIsBool($commit['is_remote_branch']);

        $this->assertArrayHasKey('author', $commit);
        $this->assertIsArray($commit['author']);
        $this->assertArrayHasKey('name', $commit['author']);
        $this->assertArrayHasKey('email', $commit['author']);
        $this->assertArrayHasKey('date', $commit['author']);
        $this->assertIsString($commit['author']['name']);
        $this->assertIsString($commit['author']['email']);
        $this->assertIsString($commit['author']['date']);

        $this->assertArrayHasKey('committer', $commit);
        $this->assertIsArray($commit['committer']);
        $this->assertArrayHasKey('name', $commit['committer']);
        $this->assertArrayHasKey('email', $commit['committer']);
        $this->assertArrayHasKey('date', $commit['committer']);
        $this->assertIsString($commit['committer']['name']);
        $this->assertIsString($commit['committer']['email']);
        $this->assertIsString($commit['committer']['date']);

        unlink('.git/objects/info/packs');
        rmdir('.git/objects/info');
        rmdir('.git/objects');
        unlink('.git/packed-refs');
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');
    }

    /**
     * Test for checkGitRevision
     */
    public function testCheckGitRevisionSkipped(): void
    {
        $this->config->set('ShowGitRevision', false);
        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );
    }

    /**
     * Test for git infos in session
     */
    public function testSessionCacheGitFolder(): void
    {
        $_SESSION['git_location'] = 'customdir/.git';
        $_SESSION['is_git_revision'] = true;
        $gitFolder = '';
        $this->assertTrue($this->object->isGitRevision($gitFolder));

        $this->assertEquals(
            $gitFolder,
            'customdir/.git'
        );
    }

    /**
     * Test that git folder is not looked up if cached value is false
     */
    public function testSessionCacheGitFolderNotRevisionNull(): void
    {
        $_SESSION['is_git_revision'] = false;
        $_SESSION['git_location'] = null;
        $gitFolder = 'defaultvaluebyref';
        $this->assertFalse($this->object->isGitRevision($gitFolder));

        // Assert that the value is replaced by cached one
        $this->assertEquals(
            $gitFolder,
            null
        );
    }

    /**
     * Test that git folder is not looked up if cached value is false
     */
    public function testSessionCacheGitFolderNotRevisionString(): void
    {
        $_SESSION['is_git_revision'] = false;
        $_SESSION['git_location'] = 'randomdir/.git';
        $gitFolder = 'defaultvaluebyref';
        $this->assertFalse($this->object->isGitRevision($gitFolder));

        // Assert that the value is replaced by cached one
        $this->assertEquals(
            $gitFolder,
            'randomdir/.git'
        );
    }
}
