<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Resource;

use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 */
final class UserTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsAuthorInterface(): void
    {
        $this->assertClassImplementsInterface(Resource\UserInterface::class, Resource\User::class);
    }

    public function testConstructorSetsLogin(): void
    {
        $login = $this->faker()->slug();

        $user = new Resource\User($login);

        self::assertSame($login, $user->login());

        $expectedHtmlUrl = \sprintf(
            'https://github.com/%s',
            $login
        );

        self::assertSame($expectedHtmlUrl, $user->htmlUrl());
    }
}
