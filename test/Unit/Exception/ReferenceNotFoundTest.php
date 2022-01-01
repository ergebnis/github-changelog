<?php

declare(strict_types=1);

/**
 * Copyright (c) 2016-2022 Andreas Möller
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/ergebnis/github-changelog
 */

namespace Ergebnis\GitHub\Changelog\Test\Unit\Exception;

use Ergebnis\GitHub\Changelog\Exception\ExceptionInterface;
use Ergebnis\GitHub\Changelog\Exception\ReferenceNotFound;
use Ergebnis\GitHub\Changelog\Resource;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 *
 * @covers \Ergebnis\GitHub\Changelog\Exception\ReferenceNotFound
 */
final class ReferenceNotFoundTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException(): void
    {
        self::assertClassExtends(\RuntimeException::class, ReferenceNotFound::class);
    }

    public function testImplementsExceptionInterface(): void
    {
        self::assertClassImplementsInterface(ExceptionInterface::class, ReferenceNotFound::class);
    }

    /**
     * @uses \Ergebnis\GitHub\Changelog\Resource\Repository
     */
    public function testFromRepositoryAndReferenceCreatesException(): void
    {
        $faker = self::faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $reference = $faker->sha1;

        $exception = ReferenceNotFound::fromRepositoryAndReference(
            $repository,
            $reference
        );

        $message = \sprintf(
            'Could not find reference "%s" in "%s".',
            $reference,
            $repository
        );

        self::assertSame($message, $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }
}
