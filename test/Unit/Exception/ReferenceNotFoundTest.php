<?php

declare(strict_types=1);

/**
 * Copyright (c) 2017 Andreas Möller.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @link https://github.com/localheinz/github-changelog
 */

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Exception;

use Localheinz\GitHub\ChangeLog\Exception\ExceptionInterface;
use Localheinz\GitHub\ChangeLog\Exception\ReferenceNotFound;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

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

    public function testFromOwnerRepositoryAndReferenceCreatesException()
    {
        $faker = $this->faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $reference = $faker->sha1;

        $exception = ReferenceNotFound::fromOwnerNameAndReference(
            $owner,
            $name,
            $reference
        );

        $this->assertInstanceOf(ReferenceNotFound::class, $exception);

        $message = \sprintf(
            'Could not find reference "%s" in "%s/%s".',
            $reference,
            $owner,
            $name
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }
}
