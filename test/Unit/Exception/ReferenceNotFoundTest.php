<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Exception;

use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

/**
 * @internal
 */
final class ReferenceNotFoundTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException()
    {
        $this->assertClassExtends(\RuntimeException::class, ReferenceNotFound::class);
    }

    public function testImplementsExceptionInterface()
    {
        $this->assertClassImplementsInterface(ExceptionInterface::class, ReferenceNotFound::class);
    }

    public function testFromRepositoryAndReferenceCreatesException()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $reference = $faker->sha1;

        $exception = ReferenceNotFound::fromRepositoryAndReference(
            $repository,
            $reference
        );

        $this->assertInstanceOf(ReferenceNotFound::class, $exception);

        $message = \sprintf(
            'Could not find reference "%s" in "%s".',
            $reference,
            $repository
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }
}
