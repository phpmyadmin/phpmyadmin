<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use DateTimeImmutable;
use DateTimeZone;
use DirectoryIterator;
use PhpMyAdmin\Utils\HttpRequest;
use stdClass;

use function array_key_exists;
use function array_shift;
use function basename;
use function bin2hex;
use function count;
use function explode;
use function fclose;
use function file_exists;
use function file_get_contents;
use function fopen;
use function fread;
use function fseek;
use function function_exists;
use function gzuncompress;
use function implode;
use function in_array;
use function intval;
use function is_bool;
use function is_dir;
use function is_file;
use function json_decode;
use function ord;
use function preg_match;
use function str_contains;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function trim;
use function unpack;

use const DIRECTORY_SEPARATOR;

/**
 * Git class to manipulate Git data
 */
class Git
{
    /**
     * Enable Git information search and process
     */
    private bool $showGitRevision;

    /**
     * The path where the to search for .git folders
     */
    private string $baseDir;

    /**
     * Git has been found and the data fetched
     */
    private bool $hasGit = false;

    public function __construct(bool $showGitRevision, string|null $baseDir = null)
    {
        $this->showGitRevision = $showGitRevision;
        $this->baseDir = $baseDir ?? ROOT_PATH;
    }

    public function hasGitInformation(): bool
    {
        return $this->hasGit;
    }

    /**
     * detects if Git revision
     *
     * @param string|null $gitLocation (optional) verified git directory
     */
    public function isGitRevision(string|null &$gitLocation = null): bool
    {
        if (! $this->showGitRevision) {
            return false;
        }

        // caching
        if (isset($_SESSION['is_git_revision']) && array_key_exists('git_location', $_SESSION)) {
            // Define location using cached value
            $gitLocation = $_SESSION['git_location'];

            return (bool) $_SESSION['is_git_revision'];
        }

        // find out if there is a .git folder
        // or a .git file (--separate-git-dir)
        $git = $this->baseDir . '.git';
        if (is_dir($git)) {
            if (! @is_file($git . '/config')) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            $gitLocation = $git;
        } elseif (is_file($git)) {
            $contents = (string) file_get_contents($git);
            $gitmatch = [];
            // Matches expected format
            if (! preg_match('/^gitdir: (.*)$/', $contents, $gitmatch)) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            if (! @is_dir($gitmatch[1])) {
                $_SESSION['git_location'] = null;
                $_SESSION['is_git_revision'] = false;

                return false;
            }

            //Detected git external folder location
            $gitLocation = $gitmatch[1];
        } else {
            $_SESSION['git_location'] = null;
            $_SESSION['is_git_revision'] = false;

            return false;
        }

        // Define session for caching
        $_SESSION['git_location'] = $gitLocation;
        $_SESSION['is_git_revision'] = true;

        return true;
    }

    private function readPackFile(string $packFile, int $packOffset): string|null
    {
        // open pack file
        $packFileRes = fopen($packFile, 'rb');
        if ($packFileRes === false) {
            return null;
        }

        // seek to start
        fseek($packFileRes, $packOffset);

        // parse header
        $headerData = fread($packFileRes, 1);
        if ($headerData === false) {
            return null;
        }

        $header = ord($headerData);
        $type = ($header >> 4) & 7;
        $hasnext = ($header & 128) >> 7;
        $size = $header & 0xf;
        $offset = 4;

        while ($hasnext) {
            $readData = fread($packFileRes, 1);
            if ($readData === false) {
                return null;
            }

            $byte = ord($readData);
            $size |= ($byte & 0x7f) << $offset;
            $hasnext = ($byte & 128) >> 7;
            $offset += 7;
        }

        // we care only about commit objects
        if ($type != 1) {
            return null;
        }

        // read data
        $commit = fread($packFileRes, $size);
        fclose($packFileRes);

        if ($commit === false) {
            return null;
        }

        return $commit;
    }

    private function getPackOffset(string $packFile, string $hash): int|null
    {
        // load index
        $indexData = @file_get_contents($packFile);
        if ($indexData === false) {
            return null;
        }

        // check format
        if (substr($indexData, 0, 4) != "\377tOc") {
            return null;
        }

        // check version
        $version = unpack('N', substr($indexData, 4, 4));
        if ($version[1] != 2) {
            return null;
        }

        // parse fanout table
        $fanout = unpack(
            'N*',
            substr($indexData, 8, 256 * 4),
        );

        // find where we should search
        $firstbyte = intval(substr($hash, 0, 2), 16);
        // array is indexed from 1 and we need to get
        // previous entry for start
        if ($firstbyte == 0) {
            $start = 0;
        } else {
            $start = $fanout[$firstbyte];
        }

        $end = $fanout[$firstbyte + 1];

        // stupid linear search for our sha
        $found = false;
        $offset = 8 + (256 * 4);
        for ($position = $start; $position < $end; $position++) {
            $sha = strtolower(
                bin2hex(
                    substr($indexData, $offset + ($position * 20), 20),
                ),
            );
            if ($sha === $hash) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            return null;
        }

        // read pack offset
        $offset = 8 + (256 * 4) + (24 * $fanout[256]);
        $packOffsets = unpack(
            'N',
            substr($indexData, $offset + ($position * 4), 4),
        );

        return $packOffsets[1];
    }

    /**
     * Un pack a commit with gzuncompress
     *
     * @param string $gitFolder The Git folder
     * @param string $hash      The commit hash
     */
    private function unPackGz(string $gitFolder, string $hash): array|false|null
    {
        $commit = false;

        $gitFileName = $gitFolder . '/objects/'
            . substr($hash, 0, 2) . '/' . substr($hash, 2);
        if (@file_exists($gitFileName)) {
            $commit = @file_get_contents($gitFileName);

            if ($commit === false) {
                $this->hasGit = false;

                return null;
            }

            $commitData = gzuncompress($commit);
            if ($commitData === false) {
                return null;
            }

            $commit = explode("\0", $commitData, 2);
            $commit = explode("\n", $commit[1]);
            $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
        } else {
            $packNames = [];
            // work with packed data
            $packsFile = $gitFolder . '/objects/info/packs';
            $packs = '';

            if (@file_exists($packsFile)) {
                $packs = @file_get_contents($packsFile);
            }

            if ($packs) {
                // File exists. Read it, parse the file to get the names of the
                // packs. (to look for them in .git/object/pack directory later)
                foreach (explode("\n", $packs) as $line) {
                    // skip blank lines
                    if (strlen(trim($line)) == 0) {
                        continue;
                    }

                    // skip non pack lines
                    if ($line[0] !== 'P') {
                        continue;
                    }

                    // parse names
                    $packNames[] = substr($line, 2);
                }
            } else {
                // '.git/objects/info/packs' file can be missing
                // (at least in mysGit)
                // File missing. May be we can look in the .git/object/pack
                // directory for all the .pack files and use that list of
                // files instead
                $dirIterator = new DirectoryIterator($gitFolder . '/objects/pack');
                foreach ($dirIterator as $fileInfo) {
                    $fileName = $fileInfo->getFilename();
                    // if this is a .pack file
                    if (! $fileInfo->isFile() || substr($fileName, -5) !== '.pack') {
                        continue;
                    }

                    $packNames[] = $fileName;
                }
            }

            $hash = strtolower($hash);
            foreach ($packNames as $packName) {
                $indexName = str_replace('.pack', '.idx', $packName);

                $packOffset = $this->getPackOffset($gitFolder . '/objects/pack/' . $indexName, $hash);
                if ($packOffset === null) {
                    continue;
                }

                $commit = $this->readPackFile($gitFolder . '/objects/pack/' . $packName, $packOffset);
                if ($commit !== null) {
                    $commit = gzuncompress($commit);
                    if ($commit !== false) {
                        $commit = explode("\n", $commit);
                    }
                }

                $_SESSION['PMA_VERSION_COMMITDATA_' . $hash] = $commit;
            }
        }

        return $commit;
    }

    /**
     * Extract committer, author and message from commit body
     *
     * @param mixed[] $commit The commit body
     *
     * @return array{
     *     array{name: string, email: string, date: string},
     *     array{name: string, email: string, date: string},
     *     string
     * }
     */
    private function extractDataFormTextBody(array $commit): array
    {
        $author = ['name' => '', 'email' => '', 'date' => ''];
        $committer = ['name' => '', 'email' => '', 'date' => ''];

        do {
            $dataline = array_shift($commit);
            $datalinearr = explode(' ', $dataline, 2);
            $linetype = $datalinearr[0];
            if (! in_array($linetype, ['author', 'committer'])) {
                continue;
            }

            $user = $datalinearr[1];
            preg_match('/([^<]+)<([^>]+)> ([0-9]+)( [^ ]+)?/', $user, $user);
            $timezone = new DateTimeZone($user[4] ?? '+0000');
            $date = (new DateTimeImmutable())->setTimestamp((int) $user[3])->setTimezone($timezone);

            $user2 = ['name' => trim($user[1]), 'email' => trim($user[2]), 'date' => $date->format('Y-m-d H:i:s O')];

            if ($linetype === 'author') {
                $author = $user2;
            } elseif ($linetype === 'committer') {
                $committer = $user2;
            }
        } while ($dataline != '');

        $message = trim(implode(' ', $commit));

        return [$author, $committer, $message];
    }

    /**
     * Is the commit remote
     *
     * @param mixed  $commit         The commit
     * @param bool   $isRemoteCommit Is the commit remote ?, will be modified by reference
     * @param string $hash           The commit hash
     *
     * @return stdClass|null The commit body from the GitHub API
     */
    private function isRemoteCommit(mixed $commit, bool &$isRemoteCommit, string $hash): stdClass|null
    {
        $httpRequest = new HttpRequest();

        // check if commit exists in Github
        if ($commit !== false && isset($_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash])) {
            $isRemoteCommit = (bool) $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash];

            return null;
        }

        $link = 'https://www.phpmyadmin.net/api/commit/' . $hash . '/';
        $isFound = $httpRequest->create($link, 'GET');
        if ($isFound === false) {
            $isRemoteCommit = false;
            $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = false;

            return null;
        }

        if ($isFound === null) {
            // no remote link for now, but don't cache this as GitHub is down
            $isRemoteCommit = false;

            return null;
        }

        $isRemoteCommit = true;
        $_SESSION['PMA_VERSION_REMOTECOMMIT_' . $hash] = true;
        if ($commit === false) {
            // if no local commit data, try loading from Github
            return json_decode((string) $isFound);
        }

        return null;
    }

    /** @return array{string|null, string|false|null} */
    private function getHashFromHeadRef(string $gitFolder, string $refHead): array
    {
        // are we on any branch?
        if (! str_contains($refHead, '/')) {
            return [trim($refHead), false];
        }

        // remove ref: prefix
        $refHead = substr(trim($refHead), 5);
        if (strpos($refHead, 'refs/heads/') === 0) {
            $branch = substr($refHead, 11);
        } else {
            $branch = basename($refHead);
        }

        $hash = null;
        $refFile = $gitFolder . '/' . $refHead;
        if (@file_exists($refFile)) {
            $hash = @file_get_contents($refFile);
            if ($hash === false) {
                $this->hasGit = false;

                return [null, null];
            }

            return [trim($hash), $branch];
        }

        // deal with packed refs
        $packedRefs = @file_get_contents($gitFolder . '/packed-refs');
        if ($packedRefs === false) {
            $this->hasGit = false;

            return [null, null];
        }

        // split file to lines
        $refLines = explode("\n", $packedRefs);
        foreach ($refLines as $line) {
            // skip comments
            if ($line[0] === '#') {
                continue;
            }

            // parse line
            $parts = explode(' ', $line);
            // care only about named refs
            if (count($parts) != 2) {
                continue;
            }

            // have found our ref?
            if ($parts[1] === $refHead) {
                $hash = $parts[0];
                break;
            }
        }

        if (! isset($hash)) {
            $this->hasGit = false;

            // Could not find ref
            return [null, null];
        }

        return [$hash, $branch];
    }

    private function getCommonDirContents(string $gitFolder): string|null
    {
        if (! is_file($gitFolder . '/commondir')) {
            return null;
        }

        $commonDirContents = @file_get_contents($gitFolder . '/commondir');
        if ($commonDirContents === false) {
            return null;
        }

        return trim($commonDirContents);
    }

    /**
     * detects Git revision, if running inside repo
     *
     * @return array{
     *     hash: string,
     *     branch: string|false,
     *     message: string,
     *     author: array{name: string, email: string, date: string},
     *     committer: array{name: string, email: string, date: string},
     *     is_remote_commit: bool,
     *     is_remote_branch: bool,
     * }|null
     */
    public function checkGitRevision(): array|null
    {
        // find out if there is a .git folder
        $gitFolder = '';
        if (! $this->isGitRevision($gitFolder)) {
            $this->hasGit = false;

            return null;
        }

        $refHead = @file_get_contents($gitFolder . '/HEAD');

        if (! $refHead) {
            $this->hasGit = false;

            return null;
        }

        $commonDirContents = $this->getCommonDirContents($gitFolder);
        if ($commonDirContents !== null) {
            $gitFolder .= DIRECTORY_SEPARATOR . $commonDirContents;
        }

        [$hash, $branch] = $this->getHashFromHeadRef($gitFolder, $refHead);
        if ($hash === null || $branch === null) {
            return null;
        }

        $commit = false;
        if (! preg_match('/^[0-9a-f]{40}$/i', $hash)) {
            $commit = false;
        } elseif (isset($_SESSION['PMA_VERSION_COMMITDATA_' . $hash])) {
            $commit = $_SESSION['PMA_VERSION_COMMITDATA_' . $hash];
        } elseif (function_exists('gzuncompress')) {
            $commit = $this->unPackGz($gitFolder, $hash);
            if ($commit === null) {
                return null;
            }
        }

        $isRemoteCommit = false;
        $commitJson = $this->isRemoteCommit(
            $commit, // Will be modified if necessary by the function
            $isRemoteCommit, // Will be modified if necessary by the function
            $hash,
        );

        $isRemoteBranch = false;
        if ($isRemoteCommit && $branch !== false) {
            // check if branch exists in Github
            if (isset($_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash])) {
                $isRemoteBranch = (bool) $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash];
            } else {
                $httpRequest = new HttpRequest();
                $link = 'https://www.phpmyadmin.net/api/tree/' . $branch . '/';
                $isFound = $httpRequest->create($link, 'GET', true);
                if (is_bool($isFound)) {
                    $isRemoteBranch = $isFound;
                    $_SESSION['PMA_VERSION_REMOTEBRANCH_' . $hash] = $isFound;
                }

                if ($isFound === null) {
                    // no remote link for now, but don't cache this as Github is down
                    $isRemoteBranch = false;
                }
            }
        }

        if ($commit !== false) {
            [$author, $committer, $message] = $this->extractDataFormTextBody($commit);
        } elseif (isset($commitJson->author, $commitJson->committer, $commitJson->message)) {
            $author = [
                'name' => (string) $commitJson->author->name,
                'email' => (string) $commitJson->author->email,
                'date' => (string) $commitJson->author->date,
            ];
            $committer = [
                'name' => (string) $commitJson->committer->name,
                'email' => (string) $commitJson->committer->email,
                'date' => (string) $commitJson->committer->date,
            ];
            $message = trim($commitJson->message);
        } else {
            $this->hasGit = false;

            return null;
        }

        $this->hasGit = true;

        return [
            'hash' => $hash,
            'branch' => $branch,
            'message' => $message,
            'author' => $author,
            'committer' => $committer,
            'is_remote_commit' => $isRemoteCommit,
            'is_remote_branch' => $isRemoteBranch,
        ];
    }
}
