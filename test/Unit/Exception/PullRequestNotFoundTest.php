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
use Localheinz\GitHub\ChangeLog\Exception\PullRequestNotFound;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class PullRequestNotFoundTest extends Framework\TestCase
{
    use Helper;

    public function testExtendsRuntimeException()
    {
        $this->assertClassExtends(\RuntimeException::class, PullRequestNotFound::class);
    }

    public function testImplementsExceptionInterface()
    {
        $this->assertClassImplementsInterface(ExceptionInterface::class, PullRequestNotFound::class);
    }

    public function testFromOwnerRepositoryAndReferenceCreatesException()
    {
        $faker = $this->faker();

        $owner = $faker->slug();
        $name = $faker->slug();
        $number = $faker->numberBetween(1);

        $exception = PullRequestNotFound::fromOwnerNameAndNumber(
            $owner,
            $name,
            $number
        );

        $this->assertInstanceOf(PullRequestNotFound::class, $exception);

        $message = \sprintf(
            'Could not find pull request "%s" in "%s/%s".',
            $number,
            $owner,
            $name
        );

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }
}
