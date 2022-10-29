<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPassword;

use function str_repeat;

/**
 * @covers \PhpMyAdmin\UserPassword
 */
class UserPasswordTest extends AbstractTestCase
{
    /** @var UserPassword */
    private $object;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['dbi'] = $this->createDatabaseInterface();

        $relation = new Relation($GLOBALS['dbi']);
        $serverPrivileges = new Privileges(
            new Template(),
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation),
            new Plugins($GLOBALS['dbi'])
        );
        $this->object = new UserPassword($serverPrivileges);
    }

    /**
     * @dataProvider providerSetChangePasswordMsg
     */
    public function testSetChangePasswordMsg(
        bool $error,
        Message $message,
        bool $noPassword,
        string $password,
        string $passwordConfirmation
    ): void {
        $this->assertEquals(
            ['error' => $error, 'msg' => $message],
            $this->object->setChangePasswordMsg(
                $password,
                $passwordConfirmation,
                $noPassword
            )
        );
    }

    /**
     * @psalm-return array{0: bool, 1: Message, 2: string, 3: string, 4: string}[]
     */
    public function providerSetChangePasswordMsg(): array
    {
        return [
            [false, Message::success('The profile has been updated.'), true, '', ''],
            [true, Message::error('The password is empty!'), false, '', ''],
            [true, Message::error('The password is empty!'), false, 'a', ''],
            [true, Message::error('The password is empty!'), false, '', 'a'],
            [true, Message::error('The passwords aren\'t the same!'), false, 'a', 'b'],
            [true, Message::error('Password is too long!'), false, str_repeat('a', 257), str_repeat('a', 257)],
            [
                false,
                Message::success('The profile has been updated.'),
                false,
                str_repeat('a', 256),
                str_repeat('a', 256),
            ],
        ];
    }
}
