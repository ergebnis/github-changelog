<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Resource;

use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Resource\User
 */
final class UserTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsAuthorInterface(): void
    {
        self::assertClassImplementsInterface(Resource\UserInterface::class, Resource\User::class);
    }

    public function testConstructorSetsLogin(): void
    {
        $login = self::faker()->slug();

        $user = new Resource\User($login);

        self::assertSame($login, $user->login());

        $expectedHtmlUrl = \sprintf(
            'https://github.com/%s',
            $login
        );

        self::assertSame($expectedHtmlUrl, $user->htmlUrl());
    }
}
