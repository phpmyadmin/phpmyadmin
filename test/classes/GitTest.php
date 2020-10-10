<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Config class
 *
 * @package PhpMyAdmin-test
 * @group git-revision
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests behaviour of PhpMyAdmin\Config class
 *
 * @package PhpMyAdmin-test
 * @group git-revision
 */
class GitTest extends PmaTestCase
{
    /** @var Config */
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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Config(CONFIG_FILE);
        $this->config->set('ShowGitRevision', true);
        $this->object = $this->config;
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
     */
    public function testCheckGitRevisionPacksFolder(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');
        $this->object->checkGitRevision();
        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents(
            '.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted' . PHP_EOL .
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10' . PHP_EOL .
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98' . PHP_EOL .
            '17bf8b7309919f8ac593d7c563b31472780ee83b refs/remotes/origin/master' . PHP_EOL
        );
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $this->object->checkGitRevision();

        $this->assertNotEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );
        $this->assertNotEmpty(
            $this->config->get('PMA_VERSION_GIT_BRANCH')
        );

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
     * @return void
     */
    public function testCheckGitRevisionRefFile(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');
        mkdir('.git/refs/remotes/origin', 0777, true);
        file_put_contents('.git/refs/remotes/origin/master', 'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f');
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $this->object->checkGitRevision();

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
     * @return void
     */
    public function testCheckGitRevisionPacksFile(): void
    {
        mkdir('.git');
        file_put_contents('.git/config', '');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->config->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents('.git/HEAD', 'ref: refs/remotes/origin/master');
        $this->object->checkGitRevision();
        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );

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

        $this->object->checkGitRevision();

        $this->assertNotEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );
        $this->assertNotEmpty(
            $this->config->get('PMA_VERSION_GIT_BRANCH')
        );

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
     * @return void
     */
    public function testCheckGitRevisionSkipped(): void
    {
        $this->config->set('ShowGitRevision', false);
        $this->object->checkGitRevision();

        $this->assertEquals(
            null,
            $this->config->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->config->get('PMA_VERSION_GIT_COMMITHASH')
        );
    }

    /**
     * Test for git infos in session
     * @return void
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
     * @return void
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
     * @return void
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
