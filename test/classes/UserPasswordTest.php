<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\Server\Plugins;
use PhpMyAdmin\Server\Privileges;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPassword;

use function str_repeat;

/** @covers \PhpMyAdmin\UserPassword */
class UserPasswordTest extends AbstractTestCase
{
    private UserPassword $object;

    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();

        $relation = new Relation($dbi);
        $serverPrivileges = new Privileges(
            new Template(),
            $dbi,
            $relation,
            new RelationCleanup($dbi, $relation),
            new Plugins($dbi),
        );
        $this->object = new UserPassword(
            $serverPrivileges,
            $this->createStub(AuthenticationPluginFactory::class),
            $dbi,
        );
    }

    /** @dataProvider providerSetChangePasswordMsg */
    public function testSetChangePasswordMsg(
        bool $error,
        Message $message,
        bool $noPassword,
        string $password,
        string $passwordConfirmation,
    ): void {
        $this->assertEquals(
            ['error' => $error, 'msg' => $message],
            $this->object->setChangePasswordMsg(
                $password,
                $passwordConfirmation,
                $noPassword,
            ),
        );
    }

    /** @psalm-return array{0: bool, 1: Message, 2: string, 3: string, 4: string}[] */
    public static function providerSetChangePasswordMsg(): array
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
