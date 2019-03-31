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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Exception;

use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound
 */
final class PullRequestNotFoundTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException(): void
    {
        $this->assertClassExtends(\RuntimeException::class, PullRequestNotFound::class);
    }

    public function testImplementsExceptionInterface(): void
    {
        $this->assertClassImplementsInterface(ExceptionInterface::class, PullRequestNotFound::class);
    }

    public function testFromRepositoryAndNumberCreatesException(): void
    {
        $faker = $this->faker();

        $number = $faker->numberBetween(1);

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $exception = PullRequestNotFound::fromRepositoryAndNumber(
            $repository,
            $number
        );

        $message = \sprintf(
            'Could not find pull request "%s" in "%s".',
            $number,
            $repository
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }
}
