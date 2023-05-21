<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Git;

use function file_put_contents;
use function mkdir;
use function mt_getrandmax;
use function random_int;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * @covers \PhpMyAdmin\Git
 * @group git-revision
 */
class GitTest extends AbstractTestCase
{
    protected Git $object;

    protected string $testDir;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setProxySettings();

        $this->testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
                        . 'gittempdir_' . random_int(0, mt_getrandmax()) . DIRECTORY_SEPARATOR;
        $this->object = new Git(true, $this->testDir);

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);
        mkdir($this->testDir);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
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

        $gitLocation = '';

        $this->assertTrue($this->object->isGitRevision($gitLocation));

        $this->assertFalse($this->object->hasGitInformation());

        $this->assertEquals('.cachedgitlocation', $gitLocation);
    }

    /**
     * Test for isGitRevision
     */
    public function testIsGitRevisionSkipped(): void
    {
        $this->object = new Git(false);
        $this->assertFalse(
            $this->object->isGitRevision($gitLocation),
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
            $this->object->isGitRevision(),
        );

        $this->assertFalse($this->object->hasGitInformation());

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($this->testDir . '.git');

        $this->assertFalse(
            $this->object->isGitRevision(),
        );

        $this->assertFalse($this->object->hasGitInformation());

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents($this->testDir . '.git/config', '');

        $this->assertTrue($this->object->isGitRevision());

        $this->assertFalse($this->object->hasGitInformation());

        unlink($this->testDir . '.git/config');
        rmdir($this->testDir . '.git');
    }

    /**
     * Test for isGitRevision
     *
     * @group git-revision
     */
    public function testIsGitRevisionExternalGitDir(): void
    {
        file_put_contents($this->testDir . '.git', 'gitdir: ' . $this->testDir . '.customgitdir');
        $this->assertFalse(
            $this->object->isGitRevision(),
        );

        $this->assertFalse($this->object->hasGitInformation());

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($this->testDir . '.customgitdir');

        $this->assertTrue($this->object->isGitRevision());

        $this->assertFalse($this->object->hasGitInformation());

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents($this->testDir . '.git', 'random data here');

        $this->assertFalse(
            $this->object->isGitRevision(),
        );

        $this->assertFalse($this->object->hasGitInformation());

        unlink($this->testDir . '.git');
        rmdir($this->testDir . '.customgitdir');
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @group git-revision
     */
    public function testCheckGitRevisionPacksFolder(): void
    {
        mkdir($this->testDir . '.git');
        file_put_contents($this->testDir . '.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertFalse($this->object->hasGitInformation());

        file_put_contents($this->testDir . '.git/HEAD', 'ref: refs/remotes/origin/master');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        file_put_contents(
            $this->testDir . '.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted' . "\n" .
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10' . "\n" .
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98' . "\n" .
            '8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e refs/remotes/origin/master' . "\n",
        );
        mkdir($this->testDir . '.git/objects/pack', 0777, true);//default = 0777, recursive mode

        $commit = $this->object->checkGitRevision();

        if (
            $commit === null
            && ! isset($_SESSION['PMA_VERSION_REMOTECOMMIT_8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e'])
        ) {
            $this->markTestSkipped('Unable to get remote commit information.');
        }

        $this->assertIsArray($commit);
        $this->assertSame('8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e', $commit['hash']);
        $this->assertSame('master', $commit['branch']);
        $this->assertSame(
            'Update po files' . "\n\n" . '[ci skip]' . "\n\n" . 'Signed-off-by: phpMyAdmin bot <bot@phpmyadmin.net>',
            $commit['message'],
        );
        $this->assertTrue($commit['is_remote_commit']);
        $this->assertTrue($commit['is_remote_branch']);
        $this->assertSame('phpMyAdmin bot', $commit['author']['name']);
        $this->assertSame('bot@phpmyadmin.net', $commit['author']['email']);
        $this->assertSame('2023-05-14T00:19:49Z', $commit['author']['date']);
        $this->assertSame('phpMyAdmin bot', $commit['committer']['name']);
        $this->assertSame('bot@phpmyadmin.net', $commit['committer']['email']);
        $this->assertSame('2023-05-14T00:19:49Z', $commit['committer']['date']);

        rmdir($this->testDir . '.git/objects/pack');
        rmdir($this->testDir . '.git/objects');
        unlink($this->testDir . '.git/packed-refs');
        unlink($this->testDir . '.git/HEAD');
        unlink($this->testDir . '.git/config');
        rmdir($this->testDir . '.git');
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @group git-revision
     */
    public function testCheckGitRevisionRefFile(): void
    {
        mkdir($this->testDir . '.git');
        file_put_contents($this->testDir . '.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertFalse($this->object->hasGitInformation());

        file_put_contents($this->testDir . '.git/HEAD', 'ref: refs/remotes/origin/master');
        mkdir($this->testDir . '.git/refs/remotes/origin', 0777, true);
        file_put_contents(
            $this->testDir . '.git/refs/remotes/origin/master',
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f',
        );
        mkdir($this->testDir . '.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertFalse($this->object->hasGitInformation());

        unlink($this->testDir . '.git/refs/remotes/origin/master');
        rmdir($this->testDir . '.git/refs/remotes/origin');
        rmdir($this->testDir . '.git/refs/remotes');
        rmdir($this->testDir . '.git/refs');
        rmdir($this->testDir . '.git/objects/pack');
        rmdir($this->testDir . '.git/objects');
        unlink($this->testDir . '.git/HEAD');
        unlink($this->testDir . '.git/config');
        rmdir($this->testDir . '.git');
    }

    /**
     * Test for checkGitRevision with packs as file
     *
     * @group git-revision
     */
    public function testCheckGitRevisionPacksFile(): void
    {
        mkdir($this->testDir . '.git');
        file_put_contents($this->testDir . '.git/config', '');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);
        $this->assertFalse($this->object->hasGitInformation());

        file_put_contents($this->testDir . '.git/HEAD', 'ref: refs/remotes/origin/master');

        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        file_put_contents(
            $this->testDir . '.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted' . "\n" .
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10' . "\n" .
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98' . "\n" .
            '8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e refs/remotes/origin/master' . "\n",
        );
        mkdir($this->testDir . '.git/objects/info', 0777, true);
        file_put_contents(
            $this->testDir . '.git/objects/info/packs',
            'P pack-faea49765800da462c70bea555848cc8c7a1c28d.pack' . "\n" .
            '  pack-.pack' . "\n" .
            "\n" .
            'P pack-420568bae521465fd11863bff155a2b2831023.pack' . "\n" .
            "\n",
        );

        $commit = $this->object->checkGitRevision();

        if (
            $commit === null
            && ! isset($_SESSION['PMA_VERSION_REMOTECOMMIT_8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e'])
        ) {
            $this->markTestSkipped('Unable to get remote commit information.');
        }

        $this->assertIsArray($commit);
        $this->assertSame('8d660283c5c88a04bac7a2b3aa9ad9eaff0fd05e', $commit['hash']);
        $this->assertSame('master', $commit['branch']);
        $this->assertSame(
            'Update po files' . "\n\n" . '[ci skip]' . "\n\n" . 'Signed-off-by: phpMyAdmin bot <bot@phpmyadmin.net>',
            $commit['message'],
        );
        $this->assertTrue($commit['is_remote_commit']);
        $this->assertTrue($commit['is_remote_branch']);
        $this->assertSame('phpMyAdmin bot', $commit['author']['name']);
        $this->assertSame('bot@phpmyadmin.net', $commit['author']['email']);
        $this->assertSame('2023-05-14T00:19:49Z', $commit['author']['date']);
        $this->assertSame('phpMyAdmin bot', $commit['committer']['name']);
        $this->assertSame('bot@phpmyadmin.net', $commit['committer']['email']);
        $this->assertSame('2023-05-14T00:19:49Z', $commit['committer']['date']);

        unlink($this->testDir . '.git/objects/info/packs');
        rmdir($this->testDir . '.git/objects/info');
        rmdir($this->testDir . '.git/objects');
        unlink($this->testDir . '.git/packed-refs');
        unlink($this->testDir . '.git/HEAD');
        unlink($this->testDir . '.git/config');
        rmdir($this->testDir . '.git');
    }

    /**
     * Test for checkGitRevision
     */
    public function testCheckGitRevisionSkipped(): void
    {
        $this->object = new Git(false);
        $commit = $this->object->checkGitRevision();

        $this->assertNull($commit);

        $this->assertFalse($this->object->hasGitInformation());
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

        $this->assertEquals($gitFolder, 'customdir/.git');
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
        $this->assertEquals($gitFolder, null);
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
        $this->assertEquals($gitFolder, 'randomdir/.git');
    }

    /**
     * Test that we can extract values from Git objects
     */
    public function testExtractDataFormTextBody(): void
    {
        $extractedData = $this->callFunction(
            $this->object,
            Git::class,
            'extractDataFormTextBody',
            [
                [
                    'tree ed7fec263e1813887001855ddca9293479289180',
                    'parent 90543399991cdb294185f90e8ae1a45e059c31ab',
                    'author William Desportes <williamdes@wdes.fr> 1657717000 +0200',
                    'committer William Desportes <williamdes@wdes.fr> 1657717000 +0200',
                    'gpgsig -----BEGIN PGP SIGNATURE-----',
                    ' ',
                    ' iQIzBAABCgAdFiEExNkf3872tKPGU\/14kKDvG4JRqIkFAmLOwQgACgkQkKDvG4JR',
                    ' qIn8Kg\/+Os5e3bFLEtd3q\/w3e4IfvR64rdadA4IUugd4pJvGqJHleJNBQ8PNqwjR',
                    ' 9W0S9PQXAsul0XW5YtuLmBMGFFQDOab2ieix9CVA1w0D7quVQR8uLNb1Gln28NuS',
                    ' 6b24Q4cAQlp5uOoKT3ohRBUtGmu8SXF8Q\/5BwPY1AuL1LqY6w6EwSsInPXK1Yq3r',
                    ' RShxRXDhonKx3NqoCdRkWmAKkQrztWGGBI7mBG\/\/X0F4hSjsuwdpHBsl6yyri9p2',
                    ' bJbyAI+xQ+rBHb0iFIoLbxj6G1EkEmpISl+4980uef24SwMVk9ZOfH8cAgBZ62Mf',
                    ' xJ3f99ujhD9dvwCQivOwcEav+fPObiLC0EzfoqZgB7rTQdxUIu7WRpShZGwfuiEv',
                    ' sBmvQcnZptYHi0Kk78fdzISCQcPBgCw0gGcv+yLOE3HuQ24B+ncCusYdxyJQqMSc',
                    ' pm9vVHpwioufy5c7aBa05K7f2b1AhiZeVpT2t\/rboIYlIhQGY9uRNGX44Qtt6Oeb',
                    ' G6aU8O7gS5+Wsj00K+uSvUE\/znxx7Ad0zVuFQGUAhd3cDp9T09+FIr4TOE+3Z4Pk',
                    ' PlssVGVBdbaNaI0\/eV6fTa6B0hMH9mhmZhtHLXdsTw5xVySz7by5DZqZldydSFtk',
                    ' tVuUPxykK6F0qY79IPBH8Unx8egIlSzKWfP0JpRd+otemBnTKWg=',
                    ' =BVHc',
                    ' -----END PGP SIGNATURE-----',
                    '',
                    'Remove ignore config.inc.php for psalm because it fails the CI',
                    '',
                    'Signed-off-by: William Desportes <williamdes@wdes.fr>',
                    '',
                ],
            ],
        );

        $this->assertSame([
            ['name' => 'William Desportes', 'email' => 'williamdes@wdes.fr', 'date' => '2022-07-13 14:56:40 +0200'],
            ['name' => 'William Desportes', 'email' => 'williamdes@wdes.fr', 'date' => '2022-07-13 14:56:40 +0200'],
            'Remove ignore config.inc.php for psalm because '
                . 'it fails the CI  Signed-off-by: William Desportes <williamdes@wdes.fr>',
        ], $extractedData);
    }
}
