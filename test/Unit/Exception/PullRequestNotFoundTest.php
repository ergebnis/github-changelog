<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2020 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Exception;

use Ergebnis\GitHub\Changelog\Exception\ExceptionInterface;
use Ergebnis\GitHub\Changelog\Exception\PullRequestNotFound;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Exception\PullRequestNotFound
 */
final class PullRequestNotFoundTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException(): void
    {
        self::assertClassExtends(\RuntimeException::class, PullRequestNotFound::class);
    }

    public function testImplementsExceptionInterface(): void
    {
        self::assertClassImplementsInterface(ExceptionInterface::class, PullRequestNotFound::class);
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     */
    public function testFromRepositoryAndNumberCreatesException(): void
    {
        $faker = self::faker();

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
